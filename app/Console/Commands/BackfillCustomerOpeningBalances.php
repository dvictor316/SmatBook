<?php

namespace App\Console\Commands;

use App\Models\Account;
use App\Models\Customer;
use App\Models\Transaction;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class BackfillCustomerOpeningBalances extends Command
{
    protected $signature   = 'customers:backfill-opening-balances
                              {--dry-run : Preview what would be posted without writing anything}
                              {--company= : Limit to a specific company_id}';

    protected $description = 'Post DR Accounts-Receivable / CR Opening-Balance-Equity journal entries '
                           . 'for every customer with a balance that does not yet have a CUST-OB-* journal entry.';

    public function handle(): int
    {
        if (!Schema::hasTable('customers') || !Schema::hasTable('transactions') || !Schema::hasTable('accounts')) {
            $this->error('Required tables (customers / transactions / accounts) are missing.');
            return self::FAILURE;
        }

        $isDryRun  = (bool) $this->option('dry-run');
        $companyId = $this->option('company') !== null ? (int) $this->option('company') : null;

        $this->info($isDryRun ? '-- DRY RUN (no writes) --' : 'Backfilling customer opening balance journal entries…');

        // 1. Find customer IDs that already have a journal entry posted
        $postedQuery = Transaction::withoutGlobalScopes()
            ->where('transaction_type', Transaction::TYPE_OPENING_BALANCE)
            ->where('reference', 'like', 'CUST-OB-%')
            ->where('debit', '>', 0);

        if ($companyId !== null) {
            $postedQuery->where('company_id', $companyId);
        }

        $postedIds = $postedQuery->distinct()->pluck('related_id')->filter()->map(fn ($v) => (int) $v)->toArray();

        // 2. Find customers with balance > 0 that have NOT been journalised
        $customerQuery = Customer::withoutGlobalScopes()
            ->whereNull('deleted_at')
            ->where('balance', '>', 0);

        if ($companyId !== null) {
            $customerQuery->where('company_id', $companyId);
        }

        if (!empty($postedIds)) {
            $customerQuery->whereNotIn('id', $postedIds);
        }

        $customers = $customerQuery->get();

        if ($customers->isEmpty()) {
            $this->info('Nothing to backfill — all customer opening balances already have journal entries.');
            return self::SUCCESS;
        }

        $this->table(
            ['ID', 'Name', 'Company', 'Balance', 'OB Date'],
            $customers->map(fn ($c) => [
                $c->id,
                $c->customer_name ?? '—',
                $c->company_id ?? '—',
                number_format((float) $c->balance, 2),
                $c->opening_balance_date ?? 'today',
            ])->all()
        );

        if ($isDryRun) {
            $this->warn("Dry run: {$customers->count()} customer(s) would receive journal entries.");
            return self::SUCCESS;
        }

        $posted  = 0;
        $failed  = 0;

        foreach ($customers as $customer) {
            try {
                DB::transaction(function () use ($customer) {
                    $this->postOpeningBalanceJournal($customer);
                });
                $posted++;
                $this->line("  ✓ #{$customer->id} {$customer->customer_name} — ₦" . number_format((float) $customer->balance, 2));
            } catch (\Throwable $e) {
                $failed++;
                $this->error("  ✗ #{$customer->id} {$customer->customer_name}: " . $e->getMessage());
            }
        }

        $this->info("Done. Posted: {$posted}, Failed: {$failed}.");
        return $failed === 0 ? self::SUCCESS : self::FAILURE;
    }

    // -------------------------------------------------------------------------
    // Private helpers (mirrors CustomerController logic exactly)
    // -------------------------------------------------------------------------

    private function postOpeningBalanceJournal(Customer $customer): void
    {
        $balance = (float) $customer->balance;

        if ($balance <= 0) {
            return;
        }

        $companyId   = (int) ($customer->company_id ?? 0);
        $userId      = (int) ($customer->user_id ?? 0);
        $reference   = 'CUST-OB-' . $customer->id;
        $txnDate     = $customer->opening_balance_date
            ? Carbon::parse($customer->opening_balance_date)->toDateString()
            : today()->toDateString();
        $description = 'Opening balance: ' . ($customer->customer_name ?? 'Customer #' . $customer->id);

        $arAccount  = $this->getOrCreateAccount(
            'Accounts Receivable', '1100',
            Account::TYPE_ASSET, Account::SUBTYPE_CURRENT_ASSET,
            $companyId, $userId
        );
        $obeAccount = $this->getOrCreateAccount(
            'Opening Balance Equity', 'OBE-001',
            Account::TYPE_EQUITY, 'Opening Balance Equity',
            $companyId, $userId
        );

        $txnColumns = Schema::getColumnListing('transactions');
        $base = array_intersect_key([
            'transaction_date' => $txnDate,
            'reference'        => $reference,
            'description'      => $description,
            'transaction_type' => Transaction::TYPE_OPENING_BALANCE,
            'related_id'       => $customer->id,
            'related_type'     => Customer::class,
            'balance'          => 0,
            'company_id'       => $companyId ?: null,
            'user_id'          => $userId ?: null,
            'branch_id'        => $customer->branch_id ?? null,
            'branch_name'      => $customer->branch_name ?? null,
        ], array_flip($txnColumns));

        // DR Accounts Receivable
        Transaction::create(array_merge($base, ['account_id' => $arAccount->id, 'debit' => $balance, 'credit' => 0]));
        // CR Opening Balance Equity
        Transaction::create(array_merge($base, ['account_id' => $obeAccount->id, 'debit' => 0, 'credit' => $balance]));
    }

    private function getOrCreateAccount(
        string $name, string $code, string $type, string $subType,
        int $companyId, int $userId
    ): Account {
        $account = Account::withoutGlobalScopes()
            ->whereNull('deleted_at')
            ->where('company_id', $companyId)
            ->where(function ($q) use ($code, $name) {
                $q->where('code', $code)->orWhere('name', $name);
            })
            ->first();

        if (!$account) {
            $columns = Schema::getColumnListing('accounts');
            $payload = array_intersect_key([
                'name'            => $name,
                'code'            => $code,
                'type'            => $type,
                'sub_type'        => $subType,
                'company_id'      => $companyId ?: null,
                'user_id'         => $userId ?: null,
                'branch_id'       => null,
                'branch_name'     => null,
                'opening_balance' => 0,
                'current_balance' => 0,
                'is_active'       => true,
            ], array_flip($columns));
            $account = Account::create($payload);
        }

        return $account;
    }
}
