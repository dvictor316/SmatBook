<?php

namespace App\Support;

use App\Models\Account;
use App\Models\Expense;
use App\Models\Payment;
use App\Models\Purchase;
use App\Models\Sale;
use App\Models\Supplier;
use App\Models\SupplierPayment;
use App\Models\Transaction;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class LedgerService
{
    private static ?array $transactionColumns = null;
    private static ?array $accountColumns = null;

    /**
     * Company context used by resolveAccount/resolveCashAccount.
     * Set at the start of each post* method so helpers work in
     * both web (Auth user) and artisan (no session) contexts.
     */
    private static ?int $currentCompanyId = null;

    public static function postSale(Sale $sale, ?int $depositAccountId = null): void
    {
        if (!self::isReady()) {
            return;
        }

        $total = (float) ($sale->total ?? 0);
        $tax = max(0, (float) ($sale->tax ?? $sale->tax_amount ?? 0));
        $netSales = max(0, $total - $tax);
        if ($total <= 0) {
            return;
        }

        self::$currentCompanyId = (int) ($sale->company_id
            ?? Auth::user()?->company_id
            ?? session('current_tenant_id')
            ?? 0) ?: null;

        $reference = $sale->invoice_no ?: ('SALE-' . $sale->id);
        $date = self::resolveDate($sale->order_date ?? $sale->created_at);
        $userId = $sale->user_id ?? auth()->id();
        $branchId = $sale->branch_id ?? null;
        $branchName = $sale->branch_name ?? $sale->branch_label ?? null;

        Transaction::query()
            ->where('related_id', $sale->id)
            ->where('related_type', Sale::class)
            ->whereIn('transaction_type', [Transaction::TYPE_SALE, Transaction::TYPE_RECEIPT])
            ->delete();

        $receivableAccount = self::resolveAccount('Accounts Receivable', 'Asset', ['receivable', 'debtor'], 'AUTO-AST-AR');
        $salesRevenueAccount = self::resolveAccount('Sales Revenue', 'Revenue', ['sales', 'income'], 'AUTO-REV-SALES');
        $taxPayableAccount = $tax > 0
            ? self::resolveAccount('Tax Payable', 'Liability', ['tax payable', 'vat payable', 'output vat', 'firs payable'], 'AUTO-LIB-TAX')
            : null;

        if ($netSales > 0) {
            self::postDoubleEntry(
                debitAccountId: $receivableAccount->id,
                creditAccountId: $salesRevenueAccount->id,
                amount: $netSales,
                date: $date,
                reference: $reference,
                description: 'Sale posted: ' . $reference,
                transactionType: Transaction::TYPE_SALE,
                relatedId: $sale->id,
                relatedType: Sale::class,
                userId: $userId,
                branchId: $branchId,
                branchName: $branchName
            );
        }

        if ($tax > 0 && $taxPayableAccount) {
            self::postDoubleEntry(
                debitAccountId: $receivableAccount->id,
                creditAccountId: $taxPayableAccount->id,
                amount: min($tax, $total),
                date: $date,
                reference: $reference,
                description: 'Sales tax posted: ' . $reference,
                transactionType: Transaction::TYPE_SALE,
                relatedId: $sale->id,
                relatedType: Sale::class,
                userId: $userId,
                branchId: $branchId,
                branchName: $branchName
            );
        }

        $paid = (float) ($sale->paid ?? $sale->amount_paid ?? 0);
        if ($paid > 0) {
            // Prefer explicitly selected COA deposit account over payment-method guessing
            if ($depositAccountId) {
                $cashAccount = Account::withoutGlobalScopes()->find($depositAccountId)
                    ?? self::resolveCashAccount($sale->payment_method ?? null);
            } else {
                $cashAccount = self::resolveCashAccount($sale->payment_method ?? null);
            }

            self::postDoubleEntry(
                debitAccountId: $cashAccount->id,
                creditAccountId: $receivableAccount->id,
                amount: min($paid, $total),
                date: $date,
            reference: ($sale->receipt_no ?: $reference),
            description: 'Sale receipt: ' . $reference,
            transactionType: Transaction::TYPE_RECEIPT,
            relatedId: $sale->id,
            relatedType: Sale::class,
            userId: $userId,
            branchId: $branchId,
            branchName: $branchName
        );
    }
    }

    public static function postPurchase(Purchase $purchase): void
    {
        if (!self::isReady()) {
            return;
        }

        $total = (float) ($purchase->total_amount ?? 0);
        $tax = max(0, (float) ($purchase->tax_amount ?? $purchase->tax ?? 0));
        $netInventory = max(0, $total - $tax);
        if ($total <= 0) {
            return;
        }

        self::$currentCompanyId = (int) ($purchase->company_id
            ?? Auth::user()?->company_id
            ?? session('current_tenant_id')
            ?? 0) ?: null;

        $reference = $purchase->purchase_no ?: ($purchase->purchase_id ?? ('PUR-' . $purchase->id));
        $date = self::resolveDate($purchase->purchase_date ?? $purchase->created_at);
        $userId = auth()->id();
        $branchId = $purchase->branch_id ?? null;
        $branchName = $purchase->branch_name ?? $purchase->branch_label ?? null;

        Transaction::query()
            ->where('related_id', $purchase->id)
            ->where('related_type', Purchase::class)
            ->where('transaction_type', Transaction::TYPE_PURCHASE)
            ->delete();

        $inventoryOrPurchase = self::resolveAccount('Inventory', 'Asset', ['inventory', 'stock'], 'AUTO-AST-INV');
        $payableAccount = self::resolveAccount('Accounts Payable', 'Liability', ['payable', 'creditor'], 'AUTO-LIB-AP');
        $inputVatAccount = $tax > 0
            ? self::resolveAccount('Input VAT', 'Asset', ['input vat', 'vat receivable', 'recoverable vat', 'tax receivable'], 'AUTO-AST-TAX')
            : null;

        if ($netInventory > 0) {
            self::postDoubleEntry(
                debitAccountId: $inventoryOrPurchase->id,
                creditAccountId: $payableAccount->id,
                amount: $netInventory,
                date: $date,
                reference: $reference,
                description: 'Purchase posted: ' . $reference,
                transactionType: Transaction::TYPE_PURCHASE,
                relatedId: $purchase->id,
                relatedType: Purchase::class,
                userId: $userId,
                branchId: $branchId,
                branchName: $branchName
            );
        }

        if ($tax > 0 && $inputVatAccount) {
            self::postDoubleEntry(
                debitAccountId: $inputVatAccount->id,
                creditAccountId: $payableAccount->id,
                amount: min($tax, $total),
                date: $date,
                reference: $reference,
                description: 'Purchase tax posted: ' . $reference,
                transactionType: Transaction::TYPE_PURCHASE,
                relatedId: $purchase->id,
                relatedType: Purchase::class,
                userId: $userId,
                branchId: $branchId,
                branchName: $branchName
            );
        }
    }

    public static function postPurchasePayment(
        Purchase $purchase,
        float $amount,
        ?string $paymentMethod = null,
        ?string $reference = null,
        ?int $paymentAccountId = null,
        ?string $paymentDate = null
    ): void {
        if (!self::isReady() || $amount <= 0) {
            return;
        }

        self::$currentCompanyId = (int) ($purchase->company_id
            ?? Auth::user()?->company_id
            ?? session('current_tenant_id')
            ?? 0) ?: null;

        $payableAccount = self::resolveAccount('Accounts Payable', 'Liability', ['payable', 'creditor'], 'AUTO-LIB-AP');
        $cashAccount = null;
        if ($paymentAccountId && $paymentAccountId > 0) {
            $cashAccount = Account::withoutGlobalScopes()->find($paymentAccountId);
        }
        if (!$cashAccount && $paymentMethod) {
            $cashAccount = self::resolveAssetAccountByName($paymentMethod);
        }
        if (!$cashAccount) {
            $cashAccount = self::resolveCashAccount($paymentMethod);
        }

        $ref = $reference ?: ($purchase->purchase_no ?: ('PUR-' . $purchase->id)) . '-PAY';
        $date = self::resolveDate($paymentDate ?? $purchase->paid_at ?? $purchase->updated_at ?? now());
        $userId = auth()->id();
        $branchId = $purchase->branch_id ?? null;
        $branchName = $purchase->branch_name ?? $purchase->branch_label ?? null;

        self::postDoubleEntry(
            debitAccountId: $payableAccount->id,
            creditAccountId: $cashAccount->id,
            amount: $amount,
            date: $date,
            reference: $ref,
            description: 'Purchase payment: ' . $ref,
            transactionType: Transaction::TYPE_PAYMENT,
            relatedId: $purchase->id,
            relatedType: Purchase::class,
            userId: $userId,
            branchId: $branchId,
            branchName: $branchName
        );
    }

    public static function postSupplierOpeningBalancePayment(
        int $supplierId,
        float $amount,
        ?string $paymentMethod = null,
        ?string $reference = null,
        ?int $paymentAccountId = null,
        ?string $paymentDate = null,
        ?int $userId = null,
        ?string $branchId = null,
        ?string $branchName = null
    ): void {
        if (!self::isReady() || $amount <= 0 || $supplierId <= 0) {
            return;
        }

        self::$currentCompanyId = (int) (
            Auth::user()?->company_id
            ?? session('current_tenant_id')
            ?? 0
        ) ?: null;

        $payableAccount = self::resolveAccount('Accounts Payable', 'Liability', ['payable', 'creditor'], 'AUTO-LIB-AP');
        $cashAccount = null;
        if ($paymentAccountId && $paymentAccountId > 0) {
            $cashAccount = Account::withoutGlobalScopes()->find($paymentAccountId);
        }
        if (!$cashAccount && $paymentMethod) {
            $cashAccount = self::resolveAssetAccountByName($paymentMethod);
        }
        if (!$cashAccount) {
            $cashAccount = self::resolveCashAccount($paymentMethod);
        }

        $ref = $reference ?: ('SUP-OPEN-' . $supplierId . '-PAY');

        self::postDoubleEntry(
            debitAccountId: $payableAccount->id,
            creditAccountId: $cashAccount->id,
            amount: $amount,
            date: self::resolveDate($paymentDate ?? now()),
            reference: $ref,
            description: 'Supplier opening balance payment: ' . $ref,
            transactionType: Transaction::TYPE_PAYMENT,
            relatedId: $supplierId,
            relatedType: Supplier::class,
            userId: $userId ?? auth()->id(),
            branchId: $branchId,
            branchName: $branchName
        );
    }

    public static function backfillSupplierPaymentLedgerEntries(
        ?int $companyId = null,
        ?int $userId = null,
        ?string $branchId = null,
        ?string $branchName = null
    ): int {
        if (!self::isReady() || !Schema::hasTable('supplier_payments')) {
            return 0;
        }

        $query = SupplierPayment::withoutGlobalScopes()->orderBy('id');

        if ($companyId && Schema::hasColumn('supplier_payments', 'company_id')) {
            $query->where('company_id', $companyId);
        } elseif ($userId && Schema::hasColumn('supplier_payments', 'user_id')) {
            $query->where('user_id', $userId);
        }

        $branchId = trim((string) ($branchId ?? ''));
        $branchName = trim((string) ($branchName ?? ''));
        if ($branchId !== '' || $branchName !== '') {
            $query->where(function ($sub) use ($branchId, $branchName) {
                if ($branchId !== '' && Schema::hasColumn('supplier_payments', 'branch_id')) {
                    $sub->where('branch_id', $branchId);
                }
                if ($branchName !== '' && Schema::hasColumn('supplier_payments', 'branch_name')) {
                    $method = ($branchId !== '' && Schema::hasColumn('supplier_payments', 'branch_id')) ? 'orWhere' : 'where';
                    $sub->{$method}('branch_name', $branchName);
                }
            });
        }

        $backfilled = 0;

        foreach ($query->get() as $payment) {
            $reference = trim((string) ($payment->reference ?: $payment->payment_group ?: ''));
            $relatedId = (int) ($payment->purchase_id ?: $payment->supplier_id ?: 0);
            $relatedType = $payment->purchase_id ? Purchase::class : Supplier::class;

            if ($relatedId <= 0) {
                continue;
            }

            self::$currentCompanyId = (int) ($payment->company_id ?? $companyId ?? Auth::user()?->company_id ?? session('current_tenant_id') ?? 0) ?: null;

            $existing = Transaction::withoutGlobalScopes()
                ->where('related_id', $relatedId)
                ->where('related_type', $relatedType)
                ->where('transaction_type', Transaction::TYPE_PAYMENT)
                ->when($reference !== '', fn ($txn) => $txn->where('reference', $reference))
                ->get();

            $isBalancedPair = $existing->count() >= 2
                && abs((float) $existing->sum('debit') - (float) $existing->sum('credit')) < 0.01;

            if ($isBalancedPair) {
                continue;
            }

            if ($existing->isNotEmpty()) {
                $existing->each->delete();
            }

            if ($payment->purchase_id) {
                $purchase = Purchase::withoutGlobalScopes()->find((int) $payment->purchase_id);
                if (!$purchase) {
                    continue;
                }

                self::postPurchasePayment(
                    $purchase,
                    (float) $payment->amount,
                    $payment->method ?: null,
                    $reference !== '' ? $reference : null,
                    (int) ($payment->account_id ?? 0) ?: null,
                    optional($payment->payment_date)->toDateString() ?: optional($payment->created_at)->toDateString()
                );
                $backfilled++;
                continue;
            }

            self::postSupplierOpeningBalancePayment(
                (int) $payment->supplier_id,
                (float) $payment->amount,
                $payment->method ?: null,
                $reference !== '' ? $reference : null,
                (int) ($payment->account_id ?? 0) ?: null,
                optional($payment->payment_date)->toDateString() ?: optional($payment->created_at)->toDateString(),
                (int) ($payment->created_by ?? $payment->user_id ?? auth()->id() ?? 0) ?: null,
                $payment->branch_id ? (string) $payment->branch_id : null,
                $payment->branch_name ? (string) $payment->branch_name : null
            );
            $backfilled++;
        }

        return $backfilled;
    }

    public static function postSalePayment(Sale $sale, Payment $payment, ?string $externalReference = null): void
    {
        if (!self::isReady()) {
            return;
        }

        $amount = (float) ($payment->amount ?? 0);
        if ($amount <= 0) {
            return;
        }

        $existing = Transaction::query()
            ->where('related_id', $payment->id)
            ->where('related_type', Payment::class)
            ->where('transaction_type', Transaction::TYPE_RECEIPT)
            ->exists();

        if ($existing) {
            Transaction::query()
                ->where('related_id', $payment->id)
                ->where('related_type', Payment::class)
                ->where('transaction_type', Transaction::TYPE_RECEIPT)
                ->delete();
        }

        self::$currentCompanyId = (int) ($payment->company_id
            ?? $sale->company_id
            ?? Auth::user()?->company_id
            ?? session('current_tenant_id')
            ?? 0) ?: null;

        $receivableAccount = self::resolveAccount('Accounts Receivable', 'Asset', ['receivable', 'debtor'], 'AUTO-AST-AR');
        $cashAccount = null;
        $paymentAccountId = (int) ($payment->payment_account_id ?? $payment->account_id ?? 0);
        if ($paymentAccountId > 0) {
            $cashAccount = Account::withoutGlobalScopes()->find($paymentAccountId);
        }
        if (!$cashAccount) {
            $cashAccount = self::resolveCashAccount($payment->method ?? $sale->payment_method ?? null);
        }
        $reference = $externalReference ?: ($payment->payment_id ?: ('PAY-' . $payment->id));
        $date = self::resolveDate($payment->created_at);
        $userId = $payment->created_by ?? $sale->user_id ?? auth()->id();
        $branchId = $payment->branch_id ?? $sale->branch_id ?? null;
        $branchName = $payment->branch_name ?? $sale->branch_name ?? $sale->branch_label ?? null;

        self::postDoubleEntry(
            debitAccountId: $cashAccount->id,
            creditAccountId: $receivableAccount->id,
            amount: $amount,
            date: $date,
            reference: $reference,
            description: 'Sale payment received: ' . ($sale->invoice_no ?: ('SALE-' . $sale->id)),
            transactionType: Transaction::TYPE_RECEIPT,
            relatedId: $payment->id,
            relatedType: Payment::class,
            userId: $userId,
            branchId: $branchId,
            branchName: $branchName
        );
    }

    public static function postStandalonePayment(Payment $payment): void
    {
        if (!self::isReady()) {
            return;
        }

        $amount = (float) ($payment->amount ?? 0);
        if ($amount <= 0) {
            return;
        }

        $existing = Transaction::query()
            ->where('related_id', $payment->id)
            ->where('related_type', Payment::class)
            ->where('transaction_type', Transaction::TYPE_PAYMENT)
            ->exists();
        if ($existing) {
            return;
        }

        $cashAccount = null;
        $paymentAccountId = (int) ($payment->payment_account_id ?? $payment->account_id ?? 0);
        if ($paymentAccountId > 0) {
            $cashAccount = Account::withoutGlobalScopes()->find($paymentAccountId);
        }
        if (!$cashAccount) {
            $cashAccount = self::resolveCashAccount($payment->method ?? null);
        }
        $revenueAccount = self::resolveAccount('Other Income', 'Revenue', ['income', 'other income', 'sales'], 'AUTO-REV-OTH');
        $reference = $payment->reference ?: ($payment->payment_id ?: ('PAY-' . $payment->id));
        $branchId = $payment->branch_id ?? null;
        $branchName = $payment->branch_name ?? null;

        self::postDoubleEntry(
            debitAccountId: $cashAccount->id,
            creditAccountId: $revenueAccount->id,
            amount: $amount,
            date: self::resolveDate($payment->created_at),
            reference: $reference,
            description: 'Standalone payment posted: ' . $reference,
            transactionType: Transaction::TYPE_PAYMENT,
            relatedId: (int) $payment->id,
            relatedType: Payment::class,
            userId: $payment->created_by ?? auth()->id(),
            branchId: $branchId,
            branchName: $branchName
        );
    }

    public static function postCustomerPayment(Payment $payment): void
    {
        if (!self::isReady()) {
            return;
        }

        $amount = (float) ($payment->amount ?? 0);
        if ($amount <= 0) {
            return;
        }

        $existing = Transaction::query()
            ->where('related_id', $payment->id)
            ->where('related_type', Payment::class)
            ->where('transaction_type', Transaction::TYPE_RECEIPT)
            ->exists();
        if ($existing) {
            Transaction::query()
                ->where('related_id', $payment->id)
                ->where('related_type', Payment::class)
                ->where('transaction_type', Transaction::TYPE_RECEIPT)
                ->delete();
        }

        self::$currentCompanyId = (int) ($payment->company_id
            ?? Auth::user()?->company_id
            ?? session('current_tenant_id')
            ?? 0) ?: null;

        $cashAccount = null;
        $paymentAccountId = (int) ($payment->payment_account_id ?? $payment->account_id ?? 0);
        if ($paymentAccountId > 0) {
            $cashAccount = Account::withoutGlobalScopes()->find($paymentAccountId);
        }
        if (!$cashAccount) {
            $cashAccount = self::resolveCashAccount($payment->method ?? null);
        }

        $receivableAccount = self::resolveAccount('Accounts Receivable', 'Asset', ['receivable', 'debtor'], 'AUTO-AST-AR');
        $reference = $payment->reference ?: ($payment->payment_id ?: ('PAY-' . $payment->id));
        $sale = $payment->sale;
        $branchId = $payment->branch_id ?? ($sale?->branch_id ?? null);
        $branchName = $payment->branch_name ?? ($sale?->branch_name ?? $sale?->branch_label ?? null);

        self::postDoubleEntry(
            debitAccountId: $cashAccount->id,
            creditAccountId: $receivableAccount->id,
            amount: $amount,
            date: self::resolveDate($payment->created_at),
            reference: $reference,
            description: 'Customer payment received: ' . $reference,
            transactionType: Transaction::TYPE_RECEIPT,
            relatedId: (int) $payment->id,
            relatedType: Payment::class,
            userId: $payment->created_by ?? auth()->id(),
            branchId: $branchId,
            branchName: $branchName
        );
    }

    public static function postExpense(Expense $expense): void
    {
        if (!self::isReady()) {
            return;
        }

        $amount = (float) ($expense->amount ?? 0);
        if ($amount <= 0) {
            return;
        }

        if (!in_array(strtolower((string) ($expense->status ?? '')), ['paid'], true)
            && !in_array(strtolower((string) ($expense->payment_status ?? '')), ['paid', 'completed'], true)) {
            return;
        }

        self::$currentCompanyId = (int) ($expense->company_id
            ?? Auth::user()?->company_id
            ?? session('current_tenant_id')
            ?? 0) ?: null;

        $existing = Transaction::query()
            ->where('related_id', $expense->id)
            ->where('related_type', Expense::class)
            ->where('transaction_type', 'Expense')
            ->exists();
        if ($existing) {
            return;
        }

        $expenseAccount = self::resolveAccount(
            (string) ($expense->category ?: 'General Expense'),
            'Expense',
            ['expense', 'operating', 'cost'],
            'AUTO-EXP-GEN'
        );
        $cashAccount = self::resolveCashAccount($expense->payment_mode ?? null);
        $reference = $expense->reference ?: ($expense->expense_id ?: ('EXP-' . $expense->id));

        self::postDoubleEntry(
            debitAccountId: $expenseAccount->id,
            creditAccountId: $cashAccount->id,
            amount: $amount,
            date: self::resolveDate($expense->created_at),
            reference: $reference,
            description: 'Expense posted: ' . ($expense->company_name ?: $reference),
            transactionType: 'Expense',
            relatedId: (int) $expense->id,
            relatedType: Expense::class,
            userId: $expense->created_by ?? auth()->id()
        );
    }

    public static function postPurchaseReturn(
        int $relatedId,
        float $amount,
        string $reference,
        ?string $date = null,
        ?int $userId = null,
        string $relatedType = 'purchase_return'
    ): void {
        if (!self::isReady() || $amount <= 0) {
            return;
        }

        Transaction::query()
            ->where('related_id', $relatedId)
            ->where('related_type', $relatedType)
            ->where('transaction_type', Transaction::TYPE_ADJUSTMENT)
            ->delete();

        $payableAccount = self::resolveAccount('Accounts Payable', 'Liability', ['payable', 'creditor'], 'AUTO-LIB-AP');
        $inventoryAccount = self::resolveAccount('Inventory', 'Asset', ['inventory', 'stock'], 'AUTO-AST-INV');

        self::postDoubleEntry(
            debitAccountId: $payableAccount->id,
            creditAccountId: $inventoryAccount->id,
            amount: $amount,
            date: self::resolveDate($date),
            reference: $reference,
            description: 'Purchase return posted: ' . $reference,
            transactionType: Transaction::TYPE_ADJUSTMENT,
            relatedId: $relatedId,
            relatedType: $relatedType,
            userId: $userId
        );
    }

    public static function postSalesReturn(
        int $relatedId,
        float $amount,
        string $reference,
        ?string $date = null,
        ?int $userId = null,
        string $relatedType = 'credit_note'
    ): void {
        if (!self::isReady() || $amount <= 0) {
            return;
        }

        Transaction::query()
            ->where('related_id', $relatedId)
            ->where('related_type', $relatedType)
            ->where('transaction_type', Transaction::TYPE_ADJUSTMENT)
            ->delete();

        $salesRevenueAccount = self::resolveAccount('Sales Revenue', 'Revenue', ['sales', 'income'], 'AUTO-REV-SALES');
        $receivableAccount = self::resolveAccount('Accounts Receivable', 'Asset', ['receivable', 'debtor'], 'AUTO-AST-AR');

        self::postDoubleEntry(
            debitAccountId: $salesRevenueAccount->id,
            creditAccountId: $receivableAccount->id,
            amount: $amount,
            date: self::resolveDate($date),
            reference: $reference,
            description: 'Sales return posted: ' . $reference,
            transactionType: Transaction::TYPE_ADJUSTMENT,
            relatedId: $relatedId,
            relatedType: $relatedType,
            userId: $userId
        );
    }

    private static function isReady(): bool
    {
        return Schema::hasTable('accounts') && Schema::hasTable('transactions');
    }

    private static function resolveDate($date): string
    {
        return Carbon::parse($date ?? now())->toDateString();
    }

    private static function postDoubleEntry(
        int $debitAccountId,
        int $creditAccountId,
        float $amount,
        string $date,
        string $reference,
        string $description,
        string $transactionType,
        int $relatedId,
        string $relatedType,
        ?int $userId = null,
        ?string $branchId = null,
        ?string $branchName = null
    ): void {
        if ($amount <= 0) {
            return;
        }

        $payload = [
            'transaction_date' => $date,
            'reference' => $reference,
            'description' => $description,
            'transaction_type' => $transactionType,
            'related_id' => $relatedId,
            'related_type' => $relatedType,
            'user_id' => $userId,
            'amount' => $amount,
            'balance' => 0,
        ];
        $payload = array_merge($payload, self::resolveTenantPayload('transactions'));
        if ($branchId !== null && Schema::hasColumn('transactions', 'branch_id')) {
            $payload['branch_id'] = $branchId;
        }
        if ($branchName !== null && Schema::hasColumn('transactions', 'branch_name')) {
            $payload['branch_name'] = $branchName;
        }

        Transaction::create(self::filterTransactionPayload(array_merge($payload, [
            'account_id' => $debitAccountId,
            'debit' => $amount,
            'credit' => 0,
        ])));

        Transaction::create(self::filterTransactionPayload(array_merge($payload, [
            'account_id' => $creditAccountId,
            'debit' => 0,
            'credit' => $amount,
        ])));
    }

    private static function filterTransactionPayload(array $payload): array
    {
        if (self::$transactionColumns === null) {
            self::$transactionColumns = Schema::getColumnListing('transactions');
        }

        return array_filter(
            $payload,
            static fn ($value, $key) => in_array($key, self::$transactionColumns, true),
            ARRAY_FILTER_USE_BOTH
        );
    }

    private static function resolveCashAccount(?string $paymentMethod = null): Account
    {
        $cid = self::$currentCompanyId;
        $base = Account::withoutGlobalScopes()->where('type', 'Asset')->where('is_active', 1);
        if ($cid && Schema::hasColumn('accounts', 'company_id')) {
            $base->where('company_id', $cid);
        }

        if ($paymentMethod && stripos($paymentMethod, 'cash') !== false) {
            $cash = (clone $base)->whereRaw('LOWER(name) LIKE ?', ['%cash%'])->first();
            if ($cash) {
                return $cash;
            }
        }

        $bank = (clone $base)->whereRaw('LOWER(name) LIKE ?', ['%bank%'])->first();
        if ($bank) {
            return $bank;
        }

        $cash = (clone $base)->whereRaw('LOWER(name) LIKE ?', ['%cash%'])->first();
        if ($cash) {
            return $cash;
        }

        return self::resolveAccount('Main Bank Account', 'Asset', ['bank', 'cash'], 'AUTO-AST-CASH');
    }

    private static function resolveAssetAccountByName(string $name): ?Account
    {
        $cid = self::$currentCompanyId;
        $normalized = strtolower(trim($name));
        if ($normalized === '') {
            return null;
        }

        $base = Account::withoutGlobalScopes()->where('type', 'Asset')->where('is_active', 1);
        if ($cid && Schema::hasColumn('accounts', 'company_id')) {
            $base->where('company_id', $cid);
        }

        return (clone $base)
            ->whereRaw('LOWER(name) = ?', [$normalized])
            ->first()
            ?: (clone $base)
                ->whereRaw('LOWER(name) LIKE ?', ['%' . $normalized . '%'])
                ->orderBy('id')
                ->first();
    }

    private static function resolveAccount(string $name, string $type, array $keywords, string $autoCodePrefix): Account
    {
        $cid = self::$currentCompanyId;

        $base = Account::withoutGlobalScopes()->where('type', $type);
        if ($cid && Schema::hasColumn('accounts', 'company_id')) {
            $base->where('company_id', $cid);
        }

        $account = (clone $base)->where('name', $name)->first();
        if ($account) {
            return $account;
        }

        foreach (self::accountAliases($name, $keywords) as $alias) {
            $account = (clone $base)
                ->whereRaw('LOWER(name) = ?', [strtolower($alias)])
                ->first();
            if ($account) {
                return $account;
            }
        }

        foreach (self::accountAliases($name, $keywords) as $alias) {
            $account = (clone $base)
                ->whereRaw('LOWER(name) LIKE ?', ['%' . strtolower($alias) . '%'])
                ->orderByDesc('is_active')
                ->orderBy('id')
                ->first();
            if ($account) {
                return $account;
            }
        }

        $payload = [
            'code' => self::nextCode($autoCodePrefix),
            'name' => $name,
            'type' => $type,
            'sub_type' => self::defaultSubTypeForAccount($name, $type),
            'opening_balance' => 0,
            'current_balance' => 0,
            'is_active' => 1,
        ];
        $payload = array_merge($payload, self::resolveTenantPayload('accounts'));

        return Account::create(self::filterAccountPayload($payload));
    }

    private static function nextCode(string $prefix): string
    {
        $id = (int) (DB::table('accounts')->max('id') ?? 0) + 1;
        return $prefix . '-' . str_pad((string) $id, 5, '0', STR_PAD_LEFT);
    }

    private static function accountAliases(string $name, array $keywords): array
    {
        $normalizedName = strtolower(trim($name));

        $aliases = match ($normalizedName) {
            'accounts payable' => ['accounts payable', 'account payable', 'trade payable', 'creditor', 'creditors'],
            'accounts receivable' => ['accounts receivable', 'account receivable', 'trade receivable', 'debtor', 'debtors'],
            'sales revenue' => ['sales revenue', 'sales income', 'revenue from sales'],
            'inventory' => ['inventory', 'stock', 'stock on hand'],
            'main bank account' => ['main bank account', 'bank account', 'cash at bank'],
            'petty cash' => ['petty cash', 'cash on hand'],
            'input vat' => ['input vat', 'vat receivable', 'recoverable vat', 'tax receivable'],
            'tax payable' => ['tax payable', 'vat payable', 'vat firs', 'firs payable'],
            default => [],
        };

        return collect(array_merge([$normalizedName], $aliases, array_map(
            static fn ($keyword) => strtolower(trim((string) $keyword)),
            $keywords
        )))
            ->filter(fn ($value) => $value !== '')
            ->unique()
            ->values()
            ->all();
    }

    private static function defaultSubTypeForAccount(string $name, string $type): ?string
    {
        $normalizedName = strtolower(trim($name));
        $normalizedType = strtolower(trim($type));

        if ($normalizedType === 'asset') {
            return 'Current Asset';
        }

        if ($normalizedType === 'liability') {
            return match ($normalizedName) {
                'accounts payable' => 'Accounts Payable',
                'tax payable' => 'Tax Payable',
                default => 'Current Liability',
            };
        }

        if ($normalizedType === 'equity') {
            return 'Opening Balance Equity';
        }

        if ($normalizedType === 'revenue') {
            return 'Sales Revenue';
        }

        if ($normalizedType === 'expense') {
            return 'Operating Expense';
        }

        return null;
    }

    private static function resolveTenantPayload(string $table): array
    {
        $payload = [];
        $user = Auth::user();
        $companyId = $user?->company_id ?? session('current_tenant_id');
        $userId = $user?->id;
        $branchId = trim((string) session('active_branch_id', ''));
        $branchName = trim((string) session('active_branch_name', ''));

        if ($branchId === '' && $branchName === '' && $companyId && Schema::hasTable('settings')) {
            $branchKey = 'branches_json_company_' . $companyId;
            $rawBranches = (string) (DB::table('settings')->where('key', $branchKey)->value('value') ?? '');
            $branches = json_decode($rawBranches, true) ?: [];
            $firstBranch = collect($branches)
                ->filter(fn ($branch) => !empty($branch['id']) || !empty($branch['name']))
                ->first();
            if ($firstBranch) {
                $branchId = trim((string) ($firstBranch['id'] ?? ''));
                $branchName = trim((string) ($firstBranch['name'] ?? ''));
            }
        }

        if (Schema::hasColumn($table, 'company_id')) {
            $payload['company_id'] = $companyId ?: null;
        }
        if (Schema::hasColumn($table, 'user_id')) {
            $payload['user_id'] = $userId ?: null;
        }
        if (Schema::hasColumn($table, 'branch_id')) {
            $payload['branch_id'] = $branchId !== '' ? $branchId : null;
        }
        if (Schema::hasColumn($table, 'branch_name')) {
            $payload['branch_name'] = $branchName !== '' ? $branchName : null;
        }

        return $payload;
    }

    private static function filterAccountPayload(array $payload): array
    {
        if (self::$accountColumns === null) {
            self::$accountColumns = Schema::getColumnListing('accounts');
        }

        return array_filter(
            $payload,
            static fn ($value, $key) => in_array($key, self::$accountColumns, true),
            ARRAY_FILTER_USE_BOTH
        );
    }
}
