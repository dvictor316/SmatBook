<?php

namespace App\Services;

use App\Models\Account;
use App\Models\Sale;
use App\Models\Transaction;
use Illuminate\Support\Facades\Auth;

class JournalService
{
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
            return;
        }

        $ref  = $sale->invoice_no ?? ('INV-' . $sale->id);
        $date = $sale->order_date ?? today();

        $arAccount      = $this->getOrCreateAccount($sale, 'Accounts Receivable', '1100', Account::TYPE_ASSET, Account::SUBTYPE_CURRENT_ASSET);
        $revenueAccount = $this->getOrCreateAccount($sale, 'Sales Revenue',       '4001', Account::TYPE_REVENUE);

        // DR Accounts Receivable — customer now owes us
        $this->postLine($sale, $arAccount,      $totalAmount, 0,            $ref, "Invoice {$ref} – Accounts Receivable", $date);
        // CR Sales Revenue — revenue recognised
        $this->postLine($sale, $revenueAccount, 0,            $totalAmount, $ref, "Invoice {$ref} – Sales Revenue",        $date);

        // If already fully/partially paid at creation, clear the AR leg
        $paidAmount = (float) ($sale->amount_paid ?? 0);
        if ($paidAmount > 0) {
            // Default to Petty Cash as the deposit account for invoices created as paid
            $depositAccount = $this->getOrCreateAccount($sale, 'Petty Cash', '1002', Account::TYPE_ASSET, Account::SUBTYPE_CURRENT_ASSET);
            $this->postPaymentJournal($sale, $paidAmount, $depositAccount, $date);
        }
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

        // DR: chosen deposit account increases (cash/bank/etc.)
        $this->postLine($sale, $depositAccount, $amount, 0,      $ref, "Payment received – {$ref} – {$depositAccount->name}", $date);
        // CR: accounts receivable decreases
        $this->postLine($sale, $arAccount,      0,      $amount, $ref, "Payment received – {$ref} (AR cleared)",               $date);
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
                $date
    ): void {
        Transaction::create([
            'account_id'       => $account->id,
            'transaction_date' => $date,
            'reference'        => $reference,
            'description'      => $description,
            'debit'            => $debit,
            'credit'           => $credit,
            'balance'          => 0, // recalculated by Transaction::boot() via account->updateBalance()
            'transaction_type' => Transaction::TYPE_JOURNAL,
            'related_id'       => $sale->id,
            'related_type'     => Sale::class,
            'user_id'          => (int) (Auth::id() ?? $sale->user_id ?? 0),
            'company_id'       => (int) ($sale->company_id ?? 0),
            'branch_id'        => (string) ($sale->branch_id ?? ''),
            'branch_name'      => (string) ($sale->branch_name ?? ''),
        ]);
    }
}
