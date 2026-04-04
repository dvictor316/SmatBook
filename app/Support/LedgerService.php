<?php

namespace App\Support;

use App\Models\Account;
use App\Models\Expense;
use App\Models\Payment;
use App\Models\Purchase;
use App\Models\Sale;
use App\Models\Transaction;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class LedgerService
{
    private static ?array $transactionColumns = null;
    private static ?array $accountColumns = null;

    public static function postSale(Sale $sale): void
    {
        if (!self::isReady()) {
            return;
        }

        $total = (float) ($sale->total ?? 0);
        if ($total <= 0) {
            return;
        }

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

        self::postDoubleEntry(
            debitAccountId: $receivableAccount->id,
            creditAccountId: $salesRevenueAccount->id,
            amount: $total,
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

        $paid = (float) ($sale->paid ?? $sale->amount_paid ?? 0);
        if ($paid > 0) {
            $cashAccount = self::resolveCashAccount($sale->payment_method ?? null);

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
        if ($total <= 0) {
            return;
        }

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

        self::postDoubleEntry(
            debitAccountId: $inventoryOrPurchase->id,
            creditAccountId: $payableAccount->id,
            amount: $total,
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
            return;
        }

        $receivableAccount = self::resolveAccount('Accounts Receivable', 'Asset', ['receivable', 'debtor'], 'AUTO-AST-AR');
        $cashAccount = self::resolveCashAccount($payment->method ?? $sale->payment_method ?? null);
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
        if (!empty($payment->payment_account_id)) {
            $cashAccount = Account::query()->find((int) $payment->payment_account_id);
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
            return;
        }

        $cashAccount = null;
        if (!empty($payment->payment_account_id)) {
            $cashAccount = Account::query()->find((int) $payment->payment_account_id);
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
        $query = Account::query()->where('type', 'Asset')->where('is_active', 1);

        if ($paymentMethod && stripos($paymentMethod, 'cash') !== false) {
            $cash = (clone $query)->whereRaw('LOWER(name) LIKE ?', ['%cash%'])->first();
            if ($cash) {
                return $cash;
            }
        }

        $bank = (clone $query)->whereRaw('LOWER(name) LIKE ?', ['%bank%'])->first();
        if ($bank) {
            return $bank;
        }

        $cash = (clone $query)->whereRaw('LOWER(name) LIKE ?', ['%cash%'])->first();
        if ($cash) {
            return $cash;
        }

        return self::resolveAccount('Main Bank Account', 'Asset', ['bank', 'cash'], 'AUTO-AST-CASH');
    }

    private static function resolveAccount(string $name, string $type, array $keywords, string $autoCodePrefix): Account
    {
        $account = Account::query()->where('type', $type)->where('name', $name)->first();
        if ($account) {
            return $account;
        }

        foreach ($keywords as $keyword) {
            $account = Account::query()
                ->where('type', $type)
                ->whereRaw('LOWER(name) LIKE ?', ['%' . strtolower($keyword) . '%'])
                ->first();
            if ($account) {
                return $account;
            }
        }

        $account = Account::query()->where('type', $type)->where('is_active', 1)->first();
        if ($account) {
            return $account;
        }

        $payload = [
            'code' => self::nextCode($autoCodePrefix),
            'name' => $name,
            'type' => $type,
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

    private static function resolveTenantPayload(string $table): array
    {
        $payload = [];
        $user = Auth::user();
        $companyId = $user?->company_id;
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
