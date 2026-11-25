<?php

namespace App\Console\Commands;

use App\Models\Account;
use App\Models\Transaction;
use App\Support\LedgerService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class AuditAccountOpenings extends Command
{
    protected $signature = 'ledger:audit-openings
        {--all-companies : Preview every business}
        {--company= : Business ID}
        {--account= : Account ID}
        {--date= : Verified effective opening date}
        {--amount= : Verified signed opening amount}
        {--apply : Post the opening for the selected account}';

    protected $description = 'Find account openings without journals and optionally post one verified opening';

    public function handle(): int
    {
        $company = $this->option('company');
        $accountId = $this->option('account');
        $date = $this->option('date');
        $amount = $this->option('amount');
        $apply = $this->option('apply');
        $all = $this->option('all-companies');
        $validCompany = ctype_digit((string) $company) && (int) $company > 0;
        if (($all ? $company !== null : !$validCompany)
            || ($accountId !== null && (!$validCompany || !ctype_digit((string) $accountId) || !(int) $accountId))
            || ($apply && (!$validCompany || !(int) $accountId
                || !preg_match('/^-?[0-9]+(?:\.[0-9]{1,2})?$/', (string) $amount)
                || Validator::make(['date' => $date], ['date' => 'required|date_format:Y-m-d'])->fails()))) {
            $this->error('Use --all-companies or --company=ID. To post one opening, specify --company=ID --account=ID --date=YYYY-MM-DD --amount=AMOUNT --apply.');

            return self::FAILURE;
        }

        $candidates = Account::withoutGlobalScopes()
            ->whereNull('accounts.deleted_at')->where('accounts.company_id', '>', 0)
            ->whereRaw('ABS(accounts.opening_balance) >= 0.005')
            ->when($company, fn ($query) => $query->where('accounts.company_id', $company))
            ->when($accountId, fn ($query) => $query->where('accounts.id', $accountId))
            ->whereNotExists(fn ($query) => $query->selectRaw('1')->from('transactions')
                ->whereColumn('transactions.account_id', 'accounts.id')
                ->whereNull('transactions.deleted_at')
                ->where('transactions.transaction_type', Transaction::TYPE_OPENING_BALANCE)
                ->where('transactions.related_type', Account::class)
                ->whereColumn('transactions.related_id', 'accounts.id'));

        if ($apply) {
            return DB::transaction(function () use ($candidates, $date, $amount) {
                $account = $candidates->lockForUpdate()->first();
                if (!$account || !$this->canPost($account)) {
                    $this->error('No eligible account opening. Check the existing journals before posting.');

                    return self::FAILURE;
                }
                if (round((float) $account->opening_balance, 2) !== round((float) $amount, 2)) {
                    $this->error('The verified amount differs from the saved opening amount.');

                    return self::FAILURE;
                }

                LedgerService::postAccountOpeningBalance($account, $date);
                $this->info("Posted opening journal OB-ACCT-{$account->id} dated {$date}.");

                return self::SUCCESS;
            });
        }

        $rows = $candidates->orderBy('accounts.company_id')->orderBy('accounts.id')->get()->map(fn (Account $account) => [
            $account->company_id, $account->id, $account->code, $account->name,
            $account->branch_name ?: ($account->branch_id ?: 'Unassigned'),
            number_format((float) $account->opening_balance, 2), $account->created_at?->toDateString(),
            $this->canPost($account) ? 'Verify opening date and amount' : 'Review existing opening history or account type',
        ]);
        $this->table(['Company', 'Account', 'Code', 'Name', 'Branch', 'Saved opening', 'Account created', 'Review'], $rows);

        return self::SUCCESS;
    }

    private function canPost(Account $account): bool
    {
        return in_array($account->type, [Account::TYPE_ASSET, Account::TYPE_LIABILITY, Account::TYPE_EQUITY], true)
            && !Transaction::withoutGlobalScopes()->where('transaction_type', Transaction::TYPE_OPENING_BALANCE)
                ->where(fn ($query) => $query->where('account_id', $account->id)
                    ->orWhere('reference', 'OB-ACCT-'.$account->id)
                    ->orWhere(fn ($related) => $related->where('related_type', Account::class)->where('related_id', $account->id)))
                ->exists();
    }
}
