<?php

namespace App\Support;

use App\Models\Account;
use App\Models\Bank;
use App\Models\Expense;
use App\Models\Payment;
use App\Models\Product;
use App\Models\Purchase;
use App\Models\PurchaseReturn;
use App\Models\Sale;
use App\Models\Supplier;
use App\Models\SupplierPayment;
use App\Models\Transaction;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use InvalidArgumentException;

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
            ->whereIn('transaction_type', [Transaction::TYPE_SALE, Transaction::TYPE_RECEIPT, Transaction::TYPE_JOURNAL])
            ->delete();

        $receivableAccount = self::resolveAccount('Accounts Receivable', 'Asset', ['receivable', 'debtor'], 'AUTO-AST-AR');
        $salesRevenueAccount = self::resolveAccount('Sales Revenue', 'Revenue', ['sales', 'income'], 'AUTO-REV-SALES');
        $taxPayableAccount = $tax > 0
            ? self::resolveAccount('Tax Payable', 'Liability', ['tax payable', 'vat payable', 'output vat', 'firs payable'], 'AUTO-LIB-TAX')
            : null;
        $cogsAccount = self::resolveAccount('Cost of Goods Sold', 'Expense', ['cost of goods sold', 'cogs', 'cost of sales'], 'AUTO-EXP-COGS');
        $inventoryAccount = self::resolveAccount('Inventory', 'Asset', ['inventory', 'stock'], 'AUTO-AST-INV');

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

        $sale->loadMissing('items.product');
        $inventoryCost = round($sale->items->sum(function ($item) {
            $product = $item->product;
            if (!$product) {
                return 0;
            }

            $stockUnits = (float) ($item->stock_units ?? 0);
            if ($stockUnits <= 0) {
                $stockUnits = InventoryQuantity::resolveSaleStockUnits(
                    $product,
                    (float) ($item->qty ?? 0),
                    $item->unit_type ?? null,
                    null
                );
            }

            $unitCost = (float) ($product->purchase_price ?? 0);

            return $stockUnits > 0 && $unitCost > 0
                ? round($stockUnits * $unitCost, 2)
                : 0;
        }), 2);

        if ($inventoryCost > 0) {
            self::postDoubleEntry(
                debitAccountId: $cogsAccount->id,
                creditAccountId: $inventoryAccount->id,
                amount: $inventoryCost,
                date: $date,
                reference: $reference,
                description: 'Cost of goods sold posted: ' . $reference,
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

    /**
     * Post a double-entry opening balance journal for a newly created chart-of-accounts entry.
     * Bypasses the branch-required guard so it works for company-wide accounts.
     * Idempotent: calling it twice for the same account is safe.
     */
    public static function postAccountOpeningBalance(Account $account): void
    {
        if (!self::isReady()) {
            return;
        }

        $ob = (float) ($account->opening_balance ?? 0);
        if (abs($ob) < 0.005) {
            return;
        }

        // Idempotent guard — never double-post
        $alreadyPosted = Transaction::withoutGlobalScopes()
            ->where('related_id', $account->id)
            ->where('related_type', Account::class)
            ->where('transaction_type', Transaction::TYPE_OPENING_BALANCE)
            ->exists();
        if ($alreadyPosted) {
            return;
        }

        self::$currentCompanyId = (int) (
            $account->company_id
            ?? Auth::user()?->company_id
            ?? session('current_tenant_id')
            ?? 0
        ) ?: null;

        // Resolve or auto-create the system Opening Balance Equity counter-account
        $equityAccount = self::resolveAccount(
            'Opening Balance Equity',
            Account::TYPE_EQUITY,
            ['opening balance equity', 'opening balance'],
            'SYS-OPENING-EQUITY'
        );

        $reference  = 'OB-ACCT-' . $account->id;
        $date       = now()->toDateString();
        $userId     = (int) ($account->user_id ?? Auth::id() ?? 0) ?: null;
        $branchId   = trim((string) ($account->branch_id ?? ''));
        $branchName = trim((string) ($account->branch_name ?? ''));

        // Debit-normal accounts (Asset/Expense): DR this account, CR Opening Balance Equity
        // Credit-normal accounts (Liability/Equity/Revenue): DR Opening Balance Equity, CR this account
        $normalizedType  = strtolower(trim((string) ($account->type ?? '')));
        $isDebitNormal   = in_array($normalizedType, ['asset', 'expense'], true);
        $debitAccountId  = $isDebitNormal ? $account->id : $equityAccount->id;
        $creditAccountId = $isDebitNormal ? $equityAccount->id : $account->id;

        // Build base payload without calling validatedBranchContext (branch is optional here)
        $columns = Schema::getColumnListing('transactions');
        $base = [
            'transaction_date' => $date,
            'reference'        => $reference,
            'description'      => 'Opening balance: ' . $account->name,
            'transaction_type' => Transaction::TYPE_OPENING_BALANCE,
            'related_id'       => $account->id,
            'related_type'     => Account::class,
            'user_id'          => $userId,
            'balance'          => 0,
        ];
        if (in_array('company_id', $columns, true)) {
            $base['company_id'] = $account->company_id ?? null;
        }
        if (in_array('branch_id', $columns, true) && $branchId !== '') {
            $base['branch_id'] = $branchId;
        }
        if (in_array('branch_name', $columns, true) && $branchName !== '') {
            $base['branch_name'] = $branchName;
        }

        Transaction::create(self::filterTransactionPayload(array_merge($base, [
            'account_id' => $debitAccountId,
            'debit'      => $ob,
            'credit'     => 0,
        ])));

        Transaction::create(self::filterTransactionPayload(array_merge($base, [
            'account_id' => $creditAccountId,
            'debit'      => 0,
            'credit'     => $ob,
        ])));
    }

    /**
     * Post an opening inventory journal when a new product is created with opening stock.
     *
     * DR Inventory (Asset)         = qty × purchase_price
     * CR Opening Balance Equity    = qty × purchase_price
     *
     * Idempotent: safe to call multiple times for the same product.
     */
    public static function postProductOpeningStock(
        Product $product,
        float $qty,
        ?string $branchId = null,
        ?string $branchName = null,
        ?int $companyId = null,
        ?int $userId = null,
        ?string $date = null
    ): void {
        if (!self::isReady()) {
            return;
        }

        $purchasePrice = (float) ($product->purchase_price ?? 0);
        if ($qty <= 0 || $purchasePrice <= 0) {
            return;
        }

        $amount = round($qty * $purchasePrice, 2);
        if ($amount <= 0) {
            return;
        }

        // Idempotent guard — never double-post opening stock for the same product
        $alreadyPosted = Transaction::withoutGlobalScopes()
            ->where('related_id', $product->id)
            ->where('related_type', Product::class)
            ->where('transaction_type', Transaction::TYPE_OPENING_BALANCE)
            ->exists();
        if ($alreadyPosted) {
            return;
        }

        self::$currentCompanyId = (int) (
            $companyId
            ?? $product->company_id
            ?? Auth::user()?->company_id
            ?? session('current_tenant_id')
            ?? 0
        ) ?: null;

        $inventoryAccount = self::resolveAccount('Inventory', 'Asset', ['inventory', 'stock'], 'AUTO-AST-INV');
        $equityAccount    = self::resolveAccount('Opening Balance Equity', Account::TYPE_EQUITY, ['opening balance equity', 'opening balance'], 'SYS-OPENING-EQUITY');

        $reference  = 'PROD-OB-' . $product->id;
        $date       = $date ?? now()->toDateString();
        $branchId   = trim((string) ($branchId ?? ''));
        $branchName = trim((string) ($branchName ?? ''));
        $userId     = $userId ?? Auth::id();

        $columns = Schema::getColumnListing('transactions');
        $base = [
            'transaction_date' => $date,
            'reference'        => $reference,
            'description'      => 'Opening stock: ' . $product->name,
            'transaction_type' => Transaction::TYPE_OPENING_BALANCE,
            'related_id'       => $product->id,
            'related_type'     => Product::class,
            'user_id'          => $userId,
            'balance'          => 0,
        ];
        if (in_array('company_id', $columns, true)) {
            $base['company_id'] = $product->company_id ?? $companyId ?? null;
        }
        if (in_array('branch_id', $columns, true) && $branchId !== '') {
            $base['branch_id'] = $branchId;
        }
        if (in_array('branch_name', $columns, true) && $branchName !== '') {
            $base['branch_name'] = $branchName;
        }

        // DR Inventory (asset increases)
        Transaction::create(self::filterTransactionPayload(array_merge($base, [
            'account_id' => $inventoryAccount->id,
            'debit'      => $amount,
            'credit'     => 0,
        ])));

        // CR Opening Balance Equity (equity increases)
        Transaction::create(self::filterTransactionPayload(array_merge($base, [
            'account_id' => $equityAccount->id,
            'debit'      => 0,
            'credit'     => $amount,
        ])));
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
            $desiredSourceAccountId = self::resolveSupplierPaymentSourceAccountId($payment, $companyId, $userId, $branchId, $branchName);

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

            $existingCreditAccountId = (int) ($existing->firstWhere('credit', '>', 0)->account_id ?? 0);
            $needsSourceRepoint = $desiredSourceAccountId > 0
                && $existingCreditAccountId > 0
                && $existingCreditAccountId !== $desiredSourceAccountId;

            if ($isBalancedPair && !$needsSourceRepoint) {
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
                    $desiredSourceAccountId ?: ((int) ($payment->account_id ?? 0) ?: null),
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
                $desiredSourceAccountId ?: ((int) ($payment->account_id ?? 0) ?: null),
                optional($payment->payment_date)->toDateString() ?: optional($payment->created_at)->toDateString(),
                (int) ($payment->created_by ?? $payment->user_id ?? auth()->id() ?? 0) ?: null,
                $payment->branch_id ? (string) $payment->branch_id : null,
                $payment->branch_name ? (string) $payment->branch_name : null
            );
            $backfilled++;
        }

        return $backfilled;
    }

    public static function backfillBankLedgerAccounts(
        ?int $companyId = null,
        ?int $userId = null,
        ?string $branchId = null,
        ?string $branchName = null
    ): int {
        if (!Schema::hasTable('banks') || !Schema::hasTable('accounts')) {
            return 0;
        }

        $query = Bank::withoutGlobalScopes()->orderBy('id');

        if ($companyId && Schema::hasColumn('banks', 'company_id')) {
            $query->where('company_id', $companyId);
        } elseif ($userId && Schema::hasColumn('banks', 'user_id')) {
            $query->where('user_id', $userId);
        }

        $branchId = trim((string) ($branchId ?? ''));
        $branchName = trim((string) ($branchName ?? ''));
        if ($branchId !== '' || $branchName !== '') {
            $query->where(function ($sub) use ($branchId, $branchName) {
                if ($branchId !== '' && Schema::hasColumn('banks', 'branch_id')) {
                    $sub->where('branch_id', $branchId);
                }
                if ($branchName !== '' && Schema::hasColumn('banks', 'branch_name')) {
                    $method = ($branchId !== '' && Schema::hasColumn('banks', 'branch_id')) ? 'orWhere' : 'where';
                    $sub->{$method}('branch_name', $branchName);
                }
            });
        }

        $created = 0;
        foreach ($query->get() as $bank) {
            $bankName = trim((string) ($bank->name ?? ''));
            if ($bankName === '') {
                continue;
            }

            $accountQuery = Account::withoutGlobalScopes()
                ->where('type', Account::TYPE_ASSET)
                ->where('name', $bankName);

            $bankCompanyId = (int) ($bank->company_id ?? $companyId ?? 0);
            $bankUserId = (int) ($bank->user_id ?? $userId ?? 0);
            $bankBranchId = trim((string) ($bank->branch_id ?? $branchId));
            $bankBranchName = trim((string) ($bank->branch_name ?? $branchName));

            if ($bankCompanyId > 0 && Schema::hasColumn('accounts', 'company_id')) {
                $accountQuery->where('company_id', $bankCompanyId);
            } elseif ($bankUserId > 0 && Schema::hasColumn('accounts', 'user_id')) {
                $accountQuery->where('user_id', $bankUserId);
            }

            $existing = $accountQuery->first();
            if ($existing) {
                continue;
            }

            // Seed from the bank master balance only. Historical supplier payments are
            // posted into the ledger separately and must not be folded into the opening
            // balance here, otherwise the asset balance gets counted twice.
            $seedBalance = (float) ($bank->balance ?? 0);

            $payload = [
                'code' => self::nextCode('AST'),
                'name' => $bankName,
                'type' => Account::TYPE_ASSET,
                'sub_type' => Account::SUBTYPE_CURRENT_ASSET,
                'opening_balance' => $seedBalance,
                'current_balance' => $seedBalance,
                'description' => 'Auto-backfilled bank ledger account.',
                'is_active' => 1,
            ];

            if ($bankCompanyId > 0 && Schema::hasColumn('accounts', 'company_id')) {
                $payload['company_id'] = $bankCompanyId;
            }
            if ($bankUserId > 0 && Schema::hasColumn('accounts', 'user_id')) {
                $payload['user_id'] = $bankUserId;
            }
            if ($bankBranchId !== '' && Schema::hasColumn('accounts', 'branch_id')) {
                $payload['branch_id'] = $bankBranchId;
            }
            if ($bankBranchName !== '' && Schema::hasColumn('accounts', 'branch_name')) {
                $payload['branch_name'] = $bankBranchName;
            }

            Account::create(self::filterAccountPayload($payload));
            $created++;
        }

        return $created;
    }

    private static function resolveSupplierPaymentSourceAccountId(
        SupplierPayment $payment,
        ?int $companyId = null,
        ?int $userId = null,
        ?string $branchId = null,
        ?string $branchName = null
    ): int {
        $accountId = (int) ($payment->account_id ?? 0);
        if ($accountId > 0) {
            return $accountId;
        }

        $bankId = (int) ($payment->bank_id ?? 0);
        if ($bankId <= 0 || !Schema::hasTable('banks')) {
            return 0;
        }

        $bank = Bank::withoutGlobalScopes()->find($bankId);
        if (!$bank) {
            return 0;
        }

        $bankName = trim((string) ($bank->name ?? ''));
        if ($bankName === '') {
            return 0;
        }

        $query = Account::withoutGlobalScopes()
            ->where('type', Account::TYPE_ASSET)
            ->whereRaw('LOWER(name) = ?', [strtolower($bankName)]);

        $scopeCompanyId = (int) ($bank->company_id ?? $companyId ?? 0);
        $scopeUserId = (int) ($bank->user_id ?? $userId ?? 0);
        $scopeBranchId = trim((string) ($bank->branch_id ?? $branchId));
        $scopeBranchName = trim((string) ($bank->branch_name ?? $branchName));

        if ($scopeCompanyId > 0 && Schema::hasColumn('accounts', 'company_id')) {
            $query->where('company_id', $scopeCompanyId);
        } elseif ($scopeUserId > 0 && Schema::hasColumn('accounts', 'user_id')) {
            $query->where('user_id', $scopeUserId);
        }

        if ($scopeBranchId !== '' || $scopeBranchName !== '') {
            $query->where(function ($sub) use ($scopeBranchId, $scopeBranchName) {
                if ($scopeBranchId !== '' && Schema::hasColumn('accounts', 'branch_id')) {
                    $sub->where('branch_id', $scopeBranchId);
                }
                if ($scopeBranchName !== '' && Schema::hasColumn('accounts', 'branch_name')) {
                    $method = ($scopeBranchId !== '' && Schema::hasColumn('accounts', 'branch_id')) ? 'orWhere' : 'where';
                    $sub->{$method}('branch_name', $scopeBranchName);
                }
            });
        }

        return (int) ($query->value('id') ?? 0);
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

    public static function postCustomerAdvanceDeposit(Payment $payment): void
    {
        if (!self::isReady()) {
            return;
        }

        $amount = (float) ($payment->wallet_amount ?? $payment->amount ?? 0);
        if ($amount <= 0) {
            return;
        }

        Transaction::query()
            ->where('related_id', $payment->id)
            ->where('related_type', Payment::class)
            ->where('transaction_type', Transaction::TYPE_RECEIPT)
            ->delete();

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

        $advanceAccount = self::resolveAccount('Customer Advances', 'Liability', ['customer advance', 'customer deposit', 'unearned revenue'], 'AUTO-LIB-CADV');
        $reference = $payment->reference ?: ($payment->payment_id ?: ('PAY-' . $payment->id));

        self::postDoubleEntry(
            debitAccountId: $cashAccount->id,
            creditAccountId: $advanceAccount->id,
            amount: $amount,
            date: self::resolveDate($payment->created_at),
            reference: $reference,
            description: 'Customer advance deposit received: ' . $reference,
            transactionType: Transaction::TYPE_RECEIPT,
            relatedId: (int) $payment->id,
            relatedType: Payment::class,
            userId: $payment->created_by ?? auth()->id(),
            branchId: $payment->branch_id ?? null,
            branchName: $payment->branch_name ?? null
        );
    }

    public static function postCustomerWalletSettlement(Sale $sale, Payment $payment): void
    {
        if (!self::isReady()) {
            return;
        }

        $amount = (float) ($payment->wallet_amount ?? $payment->amount ?? 0);
        if ($amount <= 0) {
            return;
        }

        Transaction::query()
            ->where('related_id', $payment->id)
            ->where('related_type', Payment::class)
            ->where('transaction_type', Transaction::TYPE_RECEIPT)
            ->delete();

        self::$currentCompanyId = (int) ($payment->company_id
            ?? $sale->company_id
            ?? Auth::user()?->company_id
            ?? session('current_tenant_id')
            ?? 0) ?: null;

        $advanceAccount = self::resolveAccount('Customer Advances', 'Liability', ['customer advance', 'customer deposit', 'unearned revenue'], 'AUTO-LIB-CADV');
        $receivableAccount = self::resolveAccount('Accounts Receivable', 'Asset', ['receivable', 'debtor'], 'AUTO-AST-AR');
        $reference = $payment->reference ?: ($payment->payment_id ?: ('PAY-' . $payment->id));

        self::postDoubleEntry(
            debitAccountId: $advanceAccount->id,
            creditAccountId: $receivableAccount->id,
            amount: $amount,
            date: self::resolveDate($payment->created_at),
            reference: $reference,
            description: 'Customer wallet applied to sale: ' . ($sale->invoice_no ?: ('SALE-' . $sale->id)),
            transactionType: Transaction::TYPE_RECEIPT,
            relatedId: (int) $payment->id,
            relatedType: Payment::class,
            userId: $payment->created_by ?? $sale->user_id ?? auth()->id(),
            branchId: $payment->branch_id ?? $sale->branch_id ?? null,
            branchName: $payment->branch_name ?? $sale->branch_name ?? $sale->branch_label ?? null
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
        $branchId = $expense->branch_id ?? null;
        $branchName = $expense->branch_name ?? null;

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
            userId: $expense->created_by ?? auth()->id(),
            branchId: $branchId,
            branchName: $branchName
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

        $branch = self::resolveRelatedBranchContext($relatedId, $relatedType);

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
            userId: $userId,
            branchId: $branch['id'] ?? null,
            branchName: $branch['name'] ?? null
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

        $branch = self::resolveRelatedBranchContext($relatedId, $relatedType);

        $salesRevenueAccount = self::resolveAccount('Sales Revenue', 'Revenue', ['sales', 'income'], 'AUTO-REV-SALES');
        $creditAccount = self::resolveAccount('Accounts Receivable', 'Asset', ['receivable', 'debtor'], 'AUTO-AST-AR');

        if ($relatedType === 'credit_note' && Schema::hasTable('credit_notes') && Schema::hasTable('sales')) {
            $sale = DB::table('credit_notes')
                ->join('sales', 'credit_notes.sale_id', '=', 'sales.id')
                ->where('credit_notes.id', $relatedId)
                ->select('sales.payment_status', 'sales.balance')
                ->first();

            $paidStatus = strtolower((string) ($sale->payment_status ?? ''));
            $saleBalance = round((float) ($sale->balance ?? 0), 2);
            if ($paidStatus === 'paid' || $saleBalance <= 0) {
                $creditAccount = self::resolveAccount('Customer Advances', 'Liability', ['customer advance', 'customer deposit', 'unearned revenue'], 'AUTO-LIB-CADV');
            }
        }

        self::postDoubleEntry(
            debitAccountId: $salesRevenueAccount->id,
            creditAccountId: $creditAccount->id,
            amount: $amount,
            date: self::resolveDate($date),
            reference: $reference,
            description: 'Sales return posted: ' . $reference,
            transactionType: Transaction::TYPE_ADJUSTMENT,
            relatedId: $relatedId,
            relatedType: $relatedType,
            userId: $userId,
            branchId: $branch['id'] ?? null,
            branchName: $branch['name'] ?? null
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

        [$branchId, $branchName] = self::validatedBranchContext($branchId, $branchName, $transactionType, $relatedType, $reference);

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

    private static function validatedBranchContext(
        ?string $branchId,
        ?string $branchName,
        string $transactionType,
        string $relatedType,
        string $reference
    ): array {
        if (!Schema::hasColumn('transactions', 'branch_id')) {
            return [$branchId, $branchName];
        }

        if ($relatedType === \App\Models\IntercompanyTransaction::class) {
            return [$branchId, $branchName];
        }

        $resolvedId = trim((string) $branchId);
        $resolvedName = trim((string) $branchName);

        if ($resolvedId === '' && $resolvedName === '') {
            throw new InvalidArgumentException(
                sprintf('Branch context is required for %s posting [%s].', $transactionType, $reference)
            );
        }

        return [
            $resolvedId !== '' ? $resolvedId : null,
            $resolvedName !== '' ? $resolvedName : null,
        ];
    }

    private static function resolveRelatedBranchContext(int $relatedId, string $relatedType): array
    {
        if ($relatedId <= 0) {
            return ['id' => null, 'name' => null];
        }

        if ($relatedType === PurchaseReturn::class && class_exists(PurchaseReturn::class)) {
            $purchaseReturn = PurchaseReturn::query()->with('purchase')->find($relatedId);
            if ($purchaseReturn?->purchase) {
                return [
                    'id' => $purchaseReturn->purchase->branch_id ?? null,
                    'name' => $purchaseReturn->purchase->branch_name ?? $purchaseReturn->purchase->branch_label ?? null,
                ];
            }
        }

        if ($relatedType === 'credit_note' && Schema::hasTable('credit_notes')) {
            $creditNote = DB::table('credit_notes')->where('id', $relatedId)->first();
            if ($creditNote) {
                if (!empty($creditNote->branch_id) || !empty($creditNote->branch_name)) {
                    return [
                        'id' => $creditNote->branch_id ?: null,
                        'name' => $creditNote->branch_name ?: null,
                    ];
                }

                $saleId = (int) ($creditNote->sale_id ?? 0);
                if ($saleId > 0 && class_exists(Sale::class)) {
                    $sale = Sale::query()->find($saleId);
                    if ($sale) {
                        return [
                            'id' => $sale->branch_id ?? null,
                            'name' => $sale->branch_name ?? $sale->branch_label ?? null,
                        ];
                    }
                }
            }
        }

        if (class_exists($relatedType) && is_subclass_of($relatedType, Model::class)) {
            $record = $relatedType::query()->find($relatedId);
            if ($record) {
                return [
                    'id' => $record->branch_id ?? null,
                    'name' => $record->branch_name ?? $record->branch_label ?? null,
                ];
            }
        }

        return ['id' => null, 'name' => null];
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
