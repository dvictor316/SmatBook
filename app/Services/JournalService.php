<?php

namespace App\Services;

use App\Models\Account;
use App\Models\Sale;
use App\Models\Transaction;
use App\Support\LedgerService;
use Illuminate\Support\Facades\Auth;

class JournalService
{
    public function removeInvoiceJournals(Sale $sale): void
    {
        Transaction::query()
            ->where('related_id', $sale->id)
            ->where('related_type', Sale::class)
            ->whereIn('transaction_type', [
                Transaction::TYPE_JOURNAL,
                Transaction::TYPE_SALE,
                Transaction::TYPE_RECEIPT,
            ])
            ->delete();
    }

    /**
     * Post journal entries when an invoice is created (non-draft).
     *
     * DR Accounts Receivable  (asset ↑)
     * CR Sales Revenue        (revenue ↑)
     *
     * If the invoice is already paid/partial on creation, also clears AR:
     * DR Cash/Bank/POS        (asset ↑)
     * CR Accounts Receivable  (asset ↓)
     */
    public function postInvoiceCreated(Sale $sale): void
    {
        $totalAmount = (float) ($sale->total ?? 0);
        if ($totalAmount <= 0) {
            $this->removeInvoiceJournals($sale);
            return;
        }

        $this->removeInvoiceJournals($sale);
        LedgerService::postSale($sale->fresh(['customer', 'items.product']));
    }

    /**
     * Post journal entries when a payment is recorded against an invoice.
     * The caller passes the Chart of Accounts deposit account directly.
     *
     * DR $depositAccount   (asset ↑)
     * CR Accounts Receivable (asset ↓)
     */
    public function postPaymentJournal(Sale $sale, float $amount, Account $depositAccount, $date = null): void
    {
        if ($amount <= 0) {
            return;
        }
        $date = $date ?? today();
        $ref  = $sale->invoice_no ?? ('INV-' . $sale->id);

        $arAccount = $this->getOrCreateAccount($sale, 'Accounts Receivable', '1100', Account::TYPE_ASSET, Account::SUBTYPE_CURRENT_ASSET);

        $this->postLine($sale, $depositAccount, $amount, 0, $ref, "Payment received – {$ref} – {$depositAccount->name}", $date, Transaction::TYPE_RECEIPT);
        $this->postLine($sale, $arAccount, 0, $amount, $ref, "Payment received – {$ref} (AR cleared)", $date, Transaction::TYPE_RECEIPT);
    }

    // -------------------------------------------------------------------------
    // Private helpers
    // -------------------------------------------------------------------------

    /**
     * Find the account by code (per company), then by name, then auto-create it.
     */
    private function getOrCreateAccount(
        Sale   $sale,
        string $name,
        string $code,
        string $type,
        string $subType = ''
    ): Account {
        $companyId = (int) ($sale->company_id ?? 0);

        // 1. Match by code within this company
        $account = Account::withoutGlobalScopes()
            ->where('company_id', $companyId)
            ->where('code', $code)
            ->first();

        // 2. Fall back to name match (handles manually-renamed accounts)
        if (!$account) {
            $account = Account::withoutGlobalScopes()
                ->where('company_id', $companyId)
                ->where('name', $name)
                ->first();
        }

        // 3. Auto-create a system account so journals always have a home
        if (!$account) {
            $account = Account::create([
                'name'            => $name,
                'code'            => $code,
                'type'            => $type,
                'sub_type'        => $subType,
                'company_id'      => $companyId,
                'user_id'         => (int) (Auth::id() ?? $sale->user_id ?? 0),
                'branch_id'       => (string) ($sale->branch_id ?? ''),
                'branch_name'     => (string) ($sale->branch_name ?? ''),
                'opening_balance' => 0,
                'current_balance' => 0,
                'is_active'       => true,
            ]);
        }

        return $account;
    }

    /**
     * Insert a single debit or credit line into the transactions table.
     */
    private function postLine(
        Sale    $sale,
        Account $account,
        float   $debit,
        float   $credit,
        string  $reference,
        string  $description,
                $date,
        string  $transactionType = Transaction::TYPE_JOURNAL
    ): void {
        $branch = $this->resolveBranchContext($sale);

        Transaction::create([
            'account_id'       => $account->id,
            'transaction_date' => $date,
            'reference'        => $reference,
            'description'      => $description,
            'debit'            => $debit,
            'credit'           => $credit,
            'balance'          => 0, // recalculated by Transaction::boot() via account->updateBalance()
            'transaction_type' => $transactionType,
            'related_id'       => $sale->id,
            'related_type'     => Sale::class,
            'user_id'          => (int) (Auth::id() ?? $sale->user_id ?? 0),
            'company_id'       => (int) ($sale->company_id ?? 0),
            'branch_id'        => $branch['id'],
            'branch_name'      => $branch['name'],
        ]);
    }

    private function resolveBranchContext(Sale $sale): array
    {
        $branchId = trim((string) ($sale->getRawOriginal('branch_id') ?? $sale->branch_id ?? ''));
        $branchName = trim((string) ($sale->getRawOriginal('branch_name') ?? $sale->branch_name ?? $sale->branch_label ?? ''));

        if ($branchId === '') {
            $branchId = trim((string) session('active_branch_id', ''));
        }
        if ($branchName === '') {
            $branchName = trim((string) session('active_branch_name', ''));
        }

        if (($branchId === '' || $branchName === '') && !$sale->relationLoaded('customer')) {
            $sale->loadMissing('customer');
        }

        $customer = $sale->customer;
        if ($branchId === '') {
            $branchId = trim((string) ($customer?->branch_id ?? ''));
        }
        if ($branchName === '') {
            $branchName = trim((string) ($customer?->branch_name ?? ''));
        }

        return [
            'id' => $branchId !== '' ? $branchId : null,
            'name' => $branchName !== '' ? $branchName : null,
        ];
    }
}
