<?php

namespace App\Http\Controllers;

use App\Models\Supplier;
use App\Models\Purchase;
use App\Models\PurchaseItem;
use App\Models\SupplierPayment;
use App\Models\Account;
use App\Models\Bank;
use App\Support\LedgerService;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use PhpOffice\PhpSpreadsheet\IOFactory;

class SupplierController extends Controller
{
    private function applyCompanyUserScope($query, string $table): void
    {
        $companyId = (int) (auth()->user()?->company_id ?? session('current_tenant_id') ?? 0);
        $userId = (int) (auth()->id() ?? 0);

        if ($companyId > 0 && Schema::hasColumn($table, 'company_id')) {
            $query->where("{$table}.company_id", $companyId);
        } elseif ($userId > 0 && Schema::hasColumn($table, 'user_id')) {
            $query->where("{$table}.user_id", $userId);
        }
    }

    private function applyBranchFilter($query, string $table, bool $includeUnassignedFallback = false): void
    {
        $activeBranch = $this->getActiveBranchContext();
        $branchId = trim((string) ($activeBranch['id'] ?? ''));
        $branchName = trim((string) ($activeBranch['name'] ?? ''));

        if ($branchId === '' && $branchName === '') {
            return;
        }

        $query->where(function ($sub) use ($table, $branchId, $branchName, $includeUnassignedFallback) {
            $matched = false;

            if ($branchId !== '' && Schema::hasColumn($table, 'branch_id')) {
                $sub->where("{$table}.branch_id", $branchId);
                $matched = true;
            }
            if ($branchName !== '' && Schema::hasColumn($table, 'branch_name')) {
                $method = $matched ? 'orWhere' : 'where';
                $sub->{$method}("{$table}.branch_name", $branchName);
                $matched = true;
            }

            if ($includeUnassignedFallback && (Schema::hasColumn($table, 'branch_id') || Schema::hasColumn($table, 'branch_name'))) {
                $method = $matched ? 'orWhere' : 'where';
                $sub->{$method}(function ($fallback) use ($table) {
                    if (Schema::hasColumn($table, 'branch_id')) {
                        $fallback->whereNull("{$table}.branch_id");
                    }
                    if (Schema::hasColumn($table, 'branch_name')) {
                        $fallback->whereNull("{$table}.branch_name");
                    }
                });
            }
        });
    }

    private function newSupplierQuery()
    {
        return Supplier::withoutGlobalScope('tenant');
    }

    private function purchaseGrossTotal($purchase): float
    {
        return round(abs((float) ($purchase->total_amount ?? 0)), 2);
    }

    private function supplierPaymentQueryForSupplier(int $supplierId)
    {
        $query = SupplierPayment::query()->where('supplier_id', $supplierId);
        $this->applyTenantScope($query, 'supplier_payments');
        return $query;
    }

    private function openingBalancePaidTotal(int $supplierId): float
    {
        if (!Schema::hasTable('supplier_payments')) {
            return 0.0;
        }

        return round((float) $this->supplierPaymentQueryForSupplier($supplierId)
            ->whereNull('purchase_id')
            ->sum('amount'), 2);
    }

    private function resolvePurchasePaidAmount($purchase): float
    {
        $paidFromColumn = Schema::hasColumn('purchases', 'paid_amount')
            ? (float) ($purchase->paid_amount ?? 0)
            : 0.0;

        if (!Schema::hasTable('supplier_payments') || empty($purchase->id)) {
            return round(max(0, $paidFromColumn), 2);
        }

        $paidFromPayments = (float) $this->supplierPaymentQueryForSupplier((int) $purchase->supplier_id)
            ->where('purchase_id', $purchase->id)
            ->sum('amount');

        return round(max($paidFromColumn, $paidFromPayments), 2);
    }

    private function resolvePurchaseOutstandingAmount($purchase): float
    {
        $grossTotal = $this->purchaseGrossTotal($purchase);
        $paidAmount = $this->resolvePurchasePaidAmount($purchase);
        return round(max(0, $grossTotal - $paidAmount), 2);
    }

    private function applyTenantScope($query, string $table = 'suppliers')
    {
        $this->applyCompanyUserScope($query, $table);
        $this->applyBranchFilter($query, $table, $table === 'suppliers');

        return $query;
    }

    private function findScopedBank(int $bankId): ?Bank
    {
        if (!Schema::hasTable('banks')) {
            return null;
        }

        $tenantQuery = Bank::query();
        $this->applyCompanyUserScope($tenantQuery, 'banks');

        $branchQuery = clone $tenantQuery;
        $this->applyBranchFilter($branchQuery, 'banks');

        return $branchQuery->find($bankId) ?: $tenantQuery->find($bankId);
    }

    private function findScopedAccount(int $accountId): ?Account
    {
        if (!Schema::hasTable('accounts')) {
            return null;
        }

        $tenantQuery = Account::query();
        $this->applyCompanyUserScope($tenantQuery, 'accounts');
        if (Schema::hasColumn('accounts', 'is_active')) {
            $tenantQuery->where('is_active', 1);
        }

        $branchQuery = clone $tenantQuery;
        $this->applyBranchFilter($branchQuery, 'accounts');

        return $branchQuery->find($accountId) ?: $tenantQuery->find($accountId);
    }

    private function resolveSourceBalance(?Bank $bank, ?Account $account): float
    {
        if ($bank && Schema::hasColumn('banks', 'balance')) {
            return round((float) ($bank->balance ?? 0), 2);
        }

        if ($account) {
            if (Schema::hasColumn('accounts', 'current_balance')) {
                return round((float) ($account->current_balance ?? 0), 2);
            }
            if (Schema::hasColumn('accounts', 'opening_balance')) {
                return round((float) ($account->opening_balance ?? 0), 2);
            }
        }

        return 0.0;
    }

    private function supplierPaymentSources()
    {
        $sources = collect();

        if (Schema::hasTable('banks')) {
            $tenantBanksQuery = Bank::query()->orderBy('name');
            $this->applyCompanyUserScope($tenantBanksQuery, 'banks');

            $branchBanksQuery = clone $tenantBanksQuery;
            $this->applyBranchFilter($branchBanksQuery, 'banks');

            $banks = $branchBanksQuery->get();
            if ($banks->isEmpty()) {
                $banks = $tenantBanksQuery->get();
            }

            $sources = $sources->merge($banks->map(function ($bank) {
                return (object) [
                    'value' => 'bank:' . $bank->id,
                    'label' => $bank->name,
                    'type' => 'Bank',
                    'balance' => round((float) ($bank->balance ?? 0), 2),
                    'bank_id' => $bank->id,
                    'account_id' => null,
                ];
            }));
        }

        if (Schema::hasTable('accounts')) {
            $tenantAccountsQuery = Account::query()->orderBy('name');
            $this->applyCompanyUserScope($tenantAccountsQuery, 'accounts');
            if (Schema::hasColumn('accounts', 'is_active')) {
                $tenantAccountsQuery->where('is_active', 1);
            }
            if (Schema::hasColumn('accounts', 'type')) {
                $tenantAccountsQuery->where('type', Account::TYPE_ASSET);
            }

            $branchAccountsQuery = clone $tenantAccountsQuery;
            $this->applyBranchFilter($branchAccountsQuery, 'accounts');

            $accounts = $branchAccountsQuery->get();
            if ($accounts->isEmpty()) {
                $accounts = $tenantAccountsQuery->get();
            }

            $sources = $sources->merge($accounts->map(function ($account) {
                return (object) [
                    'value' => 'account:' . $account->id,
                    'label' => $account->name,
                    'type' => 'Account',
                    'balance' => round((float) ($account->current_balance ?? $account->opening_balance ?? 0), 2),
                    'bank_id' => null,
                    'account_id' => $account->id,
                ];
            }));
        }

        return $sources
            ->unique('value')
            ->sortBy(fn ($source) => strtolower((string) $source->label))
            ->values();
    }

    private function resolveNameColumn(): string
    {
        foreach (['name', 'supplier_name', 'company_name'] as $column) {
            if (Schema::hasColumn('suppliers', $column)) {
                return $column;
            }
        }

        return 'name';
    }

    private function getActiveBranchContext(): array
    {
        $branchId = session('active_branch_id') ? (string) session('active_branch_id') : null;
        $branchName = session('active_branch_name') ? (string) session('active_branch_name') : null;

        if (!$branchId && !$branchName && Schema::hasTable('settings')) {
            $companyId = (int) (auth()->user()?->company_id ?? session('current_tenant_id') ?? 0);
            if ($companyId > 0) {
                $key = 'branches_json_company_' . $companyId;
                $raw = (string) (DB::table('settings')->where('key', $key)->value('value') ?? '');
                $branches = json_decode($raw, true) ?: [];
                $first = collect($branches)->first();
                if ($first) {
                    $branchId = $branchId ?: ($first['id'] ?? null);
                    $branchName = $branchName ?: ($first['name'] ?? null);
                }
            }
        }

        return [
            'id' => $branchId,
            'name' => $branchName,
        ];
    }

    public function index(Request $request)
    {
        $query = $this->newSupplierQuery()->latest();
        $this->applyTenantScope($query);

        $search = trim((string) $request->input('search', ''));
        if ($search !== '') {
            $nameColumn = $this->resolveNameColumn();
            $query->where(function ($builder) use ($search, $nameColumn) {
                $builder->where($nameColumn, 'like', "%{$search}%");
                if (Schema::hasColumn('suppliers', 'email')) {
                    $builder->orWhere('email', 'like', "%{$search}%");
                }
                if (Schema::hasColumn('suppliers', 'phone')) {
                    $builder->orWhere('phone', 'like', "%{$search}%");
                }
                if (Schema::hasColumn('suppliers', 'address')) {
                    $builder->orWhere('address', 'like', "%{$search}%");
                }
            });
        }

        $suppliers = $query->paginate(20)->withQueryString();
        $outstandingBySupplier = $this->supplierOutstandingBalances($suppliers->getCollection()->pluck('id')->all());
        $suppliers->getCollection()->transform(function ($supplier) use ($outstandingBySupplier) {
            $payables = (float) ($outstandingBySupplier[$supplier->id] ?? 0);
            $supplier->outstanding_payables = $payables + (float) ($supplier->opening_balance ?? 0);
            return $supplier;
        });

        $totalPayables = $this->calculateTotalPayables();

        return view('Customers.suppliers', compact('suppliers', 'totalPayables'));
    }

    public function create()
    {
        return view('Customers.suppliers-create');
    }

    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'name'    => 'required|string|max:191',
            'contact' => 'nullable|string|max:191',
            'email'   => 'nullable|email|max:191',
            'phone'   => 'nullable|string|max:191',
            'address' => 'nullable|string|max:255',
            'opening_balance' => 'nullable|numeric',
            'opening_balance_date' => 'nullable|date',
        ]);

        $payload = $this->sanitizeForSupplierColumns([
            'contact' => $request->input('contact'),
            'email' => $request->input('email'),
            'phone' => $request->input('phone'),
            'address' => $request->input('address'),
            'opening_balance' => $request->input('opening_balance'),
            'opening_balance_date' => $request->input('opening_balance_date'),
            'company_id' => auth()->user()?->company_id ?? null,
            'user_id' => auth()->id(),
            'branch_id' => $this->getActiveBranchContext()['id'],
            'branch_name' => $this->getActiveBranchContext()['name'],
        ]);

        if (Schema::hasColumn('suppliers', 'company_id')) {
            $payload['company_id'] = auth()->user()?->company_id ?? session('current_tenant_id');
        }
        if (Schema::hasColumn('suppliers', 'user_id')) {
            $payload['user_id'] = auth()->id();
        }
        if (Schema::hasColumn('suppliers', 'branch_id')) {
            $payload['branch_id'] = $this->getActiveBranchContext()['id'];
        }
        if (Schema::hasColumn('suppliers', 'branch_name')) {
            $payload['branch_name'] = $this->getActiveBranchContext()['name'];
        }

        $nameColumn = $this->resolveNameColumn();
        $payload[$nameColumn] = $request->input('name');
        $supplier = Supplier::create($payload);

        return redirect()->route('suppliers.index')->with('success', 'Supplier added successfully.');
    }

    public function edit($id)
    {
        $supplier = $this->applyTenantScope($this->newSupplierQuery())->findOrFail($id);
        return view('Customers.suppliers-edit', compact('supplier'));
    }

    public function show($id)
    {
        $supplier = $this->applyTenantScope($this->newSupplierQuery())->findOrFail($id);

        $hasPurchases = Schema::hasTable('purchases');
        $purchaseDateColumn = $hasPurchases && Schema::hasColumn('purchases', 'purchase_date')
            ? 'purchase_date'
            : ($hasPurchases && Schema::hasColumn('purchases', 'date') ? 'date' : 'created_at');
        $totalColumn = $hasPurchases && Schema::hasColumn('purchases', 'total_amount')
            ? 'total_amount'
            : ($hasPurchases && Schema::hasColumn('purchases', 'total') ? 'total' : 'grand_total');
        $receivedColumn = $hasPurchases && Schema::hasColumn('purchases', 'received_status')
            ? 'received_status'
            : ($hasPurchases && Schema::hasColumn('purchases', 'status') ? 'status' : null);

        $purchases = $hasPurchases
            ? tap(Purchase::query()->where('supplier_id', $supplier->id), function ($query) {
                $this->applyTenantScope($query, 'purchases');
                $this->applyBranchScopeToPurchases($query);
            })
                ->orderByDesc($purchaseDateColumn)
                ->limit(25)
                ->get()
                ->map(function ($purchase) {
                    $purchase->resolved_total_amount = $this->purchaseGrossTotal($purchase);
                    $purchase->resolved_paid_amount = $this->resolvePurchasePaidAmount($purchase);
                    $purchase->resolved_outstanding_amount = $this->resolvePurchaseOutstandingAmount($purchase);
                    return $purchase;
                })
            : collect();

        $summary = [
            'total_purchases' => 0,
            'total_spend' => 0,
            'received_items' => 0,
            'pending_items' => 0,
        ];

        if ($hasPurchases) {
            $summaryQuery = Purchase::query()->where('supplier_id', $supplier->id);
            $this->applyTenantScope($summaryQuery, 'purchases');
            $this->applyBranchScopeToPurchases($summaryQuery);
            $summary['total_purchases'] = (clone $summaryQuery)->count();
            if ($totalColumn) {
                $summary['total_spend'] = round((float) $purchases->sum('resolved_total_amount'), 2);
            }
        }

        $summary['outstanding_payables'] = $this->calculateSupplierOutstandingBalance($supplier->id);
        $summary['total_paid'] = 0;
        $summary['opening_balance_paid'] = $this->openingBalancePaidTotal($supplier->id);
        $summary['opening_balance_original'] = round((float) ($supplier->opening_balance ?? 0) + $summary['opening_balance_paid'], 2);

        $purchaseItemsByPurchase = collect();
        if ($hasPurchases && Schema::hasTable('purchase_items')) {
            $purchaseIdsQuery = Purchase::query()->where('supplier_id', $supplier->id);
            $this->applyTenantScope($purchaseIdsQuery, 'purchases');
            $this->applyBranchScopeToPurchases($purchaseIdsQuery);
            $purchaseIds = $purchaseIdsQuery->pluck('id');
            $hasProductNameColumn = Schema::hasColumn('purchase_items', 'product_name');
            $qtyColumn = Schema::hasColumn('purchase_items', 'qty')
                ? 'qty'
                : (Schema::hasColumn('purchase_items', 'quantity') ? 'quantity' : null);
            $receivedQtyColumn = Schema::hasColumn('purchase_items', 'received_qty')
                ? 'received_qty'
                : (Schema::hasColumn('purchase_items', 'received_quantity') ? 'received_quantity' : null);
            $rateColumn = Schema::hasColumn('purchase_items', 'unit_price')
                ? 'unit_price'
                : (Schema::hasColumn('purchase_items', 'rate') ? 'rate' : null);
            $totalItemColumn = Schema::hasColumn('purchase_items', 'total_price')
                ? 'total_price'
                : (Schema::hasColumn('purchase_items', 'subtotal') ? 'subtotal' : null);

            if ($qtyColumn) {
                $summary['pending_items'] = (float) (PurchaseItem::whereIn('purchase_id', $purchaseIds)->sum($qtyColumn) ?? 0);
            }
            if ($receivedQtyColumn) {
                $summary['received_items'] = (float) (PurchaseItem::whereIn('purchase_id', $purchaseIds)->sum($receivedQtyColumn) ?? 0);
                if ($summary['pending_items'] > 0) {
                    $summary['pending_items'] = max(0, $summary['pending_items'] - $summary['received_items']);
                }
            }

            $productNameExpression = $hasProductNameColumn
                ? "COALESCE(products.name, purchase_items.product_name, 'Item')"
                : "COALESCE(products.name, 'Item')";

            $itemQuery = PurchaseItem::query()
                ->whereIn('purchase_id', $purchaseIds)
                ->leftJoin('products', 'products.id', '=', 'purchase_items.product_id')
                ->select(
                    'purchase_items.purchase_id',
                    'purchase_items.product_id',
                    DB::raw($productNameExpression . ' as product_name'),
                    $qtyColumn ? "purchase_items.{$qtyColumn} as qty" : DB::raw('0 as qty'),
                    $receivedQtyColumn ? "purchase_items.{$receivedQtyColumn} as received_qty" : DB::raw('0 as received_qty'),
                    $rateColumn ? "purchase_items.{$rateColumn} as unit_price" : DB::raw('0 as unit_price'),
                    $totalItemColumn ? "purchase_items.{$totalItemColumn} as line_total" : DB::raw('0 as line_total')
                );

            $purchaseItemsByPurchase = $itemQuery->get()->groupBy('purchase_id');
        }

        $supplierPayments = collect();
        if (Schema::hasTable('supplier_payments')) {
            $supplierPaymentsQuery = SupplierPayment::with(['purchase', 'bank'])
                ->where('supplier_id', $supplier->id)
                ->latest('payment_date')
                ->latest('id');
            $this->applyTenantScope($supplierPaymentsQuery, 'supplier_payments');
            $supplierPayments = $supplierPaymentsQuery->limit(50)->get();
            $summary['total_paid'] = (float) $supplierPayments->sum('amount');
        }

        return view('Customers.suppliers-show', compact(
            'supplier',
            'purchases',
            'summary',
            'purchaseDateColumn',
            'totalColumn',
            'receivedColumn',
            'purchaseItemsByPurchase',
            'supplierPayments'
        ));
    }

    public function pay($id)
    {
        $supplier = $this->applyTenantScope($this->newSupplierQuery())->findOrFail($id);
        $purchaseDateColumn = Schema::hasColumn('purchases', 'purchase_date')
            ? 'purchase_date'
            : (Schema::hasColumn('purchases', 'date') ? 'date' : 'created_at');

        $purchasesQuery = Purchase::query()
            ->where('supplier_id', $supplier->id)
            ->orderBy($purchaseDateColumn)
            ->orderBy('id');
        $this->applyTenantScope($purchasesQuery, 'purchases');
        $this->applyBranchScopeToPurchases($purchasesQuery);
        $outstandingPurchases = $purchasesQuery->get()->map(function ($purchase) {
            $purchase->resolved_total_amount = $this->purchaseGrossTotal($purchase);
            $purchase->resolved_paid_amount = $this->resolvePurchasePaidAmount($purchase);
            $purchase->outstanding_balance = $this->resolvePurchaseOutstandingAmount($purchase);
            return $purchase;
        })->filter(fn ($purchase) => $purchase->outstanding_balance > 0)->values();

        $paymentSources = $this->supplierPaymentSources();

        $supplierPayments = collect();
        if (Schema::hasTable('supplier_payments')) {
            $supplierPaymentsQuery = SupplierPayment::with(['purchase', 'bank', 'account'])
                ->where('supplier_id', $supplier->id)
                ->latest('payment_date')
                ->latest('id');
            $this->applyTenantScope($supplierPaymentsQuery, 'supplier_payments');
            $supplierPayments = $supplierPaymentsQuery->limit(50)->get();
        }

        $openingBalancePaid = $this->openingBalancePaidTotal($supplier->id);
        $openingBalanceDue = round((float) ($supplier->opening_balance ?? 0), 2);
        $openBills = (int) $outstandingPurchases->count();
        $summary = [
            'outstanding_payables' => round((float) $outstandingPurchases->sum('outstanding_balance') + $openingBalanceDue, 2),
            'total_paid' => (float) $supplierPayments->sum('amount'),
            'open_bills' => $openBills,
            'opening_balance_due' => $openingBalanceDue,
            'opening_balance_paid' => $openingBalancePaid,
            'opening_balance_original' => round($openingBalanceDue + $openingBalancePaid, 2),
            'open_obligations' => $openBills + ($openingBalanceDue > 0 ? 1 : 0),
        ];

        return view('Customers.supplier-payments', compact(
            'supplier',
            'outstandingPurchases',
            'paymentSources',
            'supplierPayments',
            'summary'
        ));
    }

    public function statement(Request $request, $id)
    {
        $supplier = $this->applyTenantScope($this->newSupplierQuery())->findOrFail($id);
        $purchaseDateColumn = Schema::hasColumn('purchases', 'purchase_date')
            ? 'purchase_date'
            : (Schema::hasColumn('purchases', 'date') ? 'date' : 'created_at');

        $purchasesQuery = Purchase::query()->where('supplier_id', $supplier->id);
        $this->applyTenantScope($purchasesQuery, 'purchases');
        $this->applyBranchScopeToPurchases($purchasesQuery);
        $purchases = $purchasesQuery->get();

        $payments = collect();
        if (Schema::hasTable('supplier_payments')) {
            $paymentsQuery = SupplierPayment::query()->with('bank')->where('supplier_id', $supplier->id);
            $this->applyTenantScope($paymentsQuery, 'supplier_payments');
            $payments = $paymentsQuery->get();
        }

        $entries = collect();

        if ((float) ($supplier->opening_balance ?? 0) > 0) {
            $entries->push([
                'date' => $supplier->opening_balance_date ?: optional($supplier->created_at)->toDateString() ?: now()->toDateString(),
                'type' => 'opening_balance',
                'reference' => 'SUP-OPEN-' . $supplier->id,
                'description' => 'Supplier opening balance',
                'debit' => 0,
                'credit' => (float) $supplier->opening_balance,
            ]);
        }

        $entries = $entries
            ->merge($purchases->map(function ($purchase) use ($purchaseDateColumn) {
                return [
                    'date' => optional($purchase->{$purchaseDateColumn} ?? $purchase->created_at)->toDateString() ?: optional($purchase->created_at)->toDateString() ?: now()->toDateString(),
                    'type' => 'purchase',
                    'reference' => $purchase->purchase_no ?: ('PUR-' . $purchase->id),
                    'description' => 'Purchase raised',
                    'debit' => 0,
                    'credit' => (float) ($purchase->total_amount ?? 0),
                ];
            }))
            ->merge($payments->map(function ($payment) {
                return [
                    'date' => optional($payment->payment_date ?? $payment->created_at)->toDateString() ?: optional($payment->created_at)->toDateString() ?: now()->toDateString(),
                    'type' => 'payment',
                    'reference' => $payment->reference ?: ($payment->payment_group ?: ('PAY-' . $payment->id)),
                    'description' => 'Supplier payment' . ($payment->bank?->name ? ' via ' . $payment->bank->name : ''),
                    'debit' => (float) ($payment->amount ?? 0),
                    'credit' => 0,
                ];
            }))
            ->sortBy([
                ['date', 'asc'],
                ['reference', 'asc'],
            ])
            ->values();

        $runningBalance = 0.0;
        $entries = $entries->map(function ($entry) use (&$runningBalance) {
            $runningBalance += (float) $entry['credit'] - (float) $entry['debit'];
            $entry['balance'] = round($runningBalance, 2);
            return $entry;
        });

        $summary = [
            'total_billed' => round((float) $purchases->sum('total_amount') + (float) ($supplier->opening_balance ?? 0), 2),
            'total_paid' => round((float) $payments->sum('amount'), 2),
            'balance_due' => round(max(0, $runningBalance), 2),
        ];

        return view('Customers.supplier-statement', compact('supplier', 'entries', 'summary'));
    }

    public function storePayment(Request $request, $id): RedirectResponse
    {
        $supplier = $this->applyTenantScope($this->newSupplierQuery())->findOrFail($id);

        $request->validate([
            'payment_date' => 'required|date',
            'payment_source' => 'nullable|string|max:50',
            'bank_id' => Schema::hasTable('banks') ? 'nullable|exists:banks,id' : 'nullable',
            'reference' => 'nullable|string|max:191',
            'method' => 'nullable|string|max:100',
            'note' => 'nullable|string|max:1000',
            'payment_amount' => 'nullable|numeric|min:0',
            'allocations' => 'nullable|array',
            'opening_balance_amount' => 'nullable|numeric|min:0',
        ]);

        $allocations = collect($request->input('allocations', []))
            ->mapWithKeys(fn ($value, $purchaseId) => [(int) $purchaseId => round(max(0, (float) $value), 2)])
            ->filter(fn ($value) => $value > 0);
        $openingBalancePayment = round(max(0, (float) $request->input('opening_balance_amount', 0)), 2);
        $paymentAmount = round(max(0, (float) $request->input('payment_amount', 0)), 2);

        $paymentDate = $request->input('payment_date');
        $paymentGroup = trim((string) $request->input('reference', '')) ?: ('SUPPAY-' . now()->format('YmdHis'));
        $paymentSource = trim((string) $request->input('payment_source', ''));
        if ($paymentSource === '' && $request->filled('bank_id')) {
            $paymentSource = 'bank:' . (int) $request->input('bank_id');
        }

        $bank = null;
        $account = null;
        if (str_starts_with($paymentSource, 'bank:')) {
            $bank = $this->findScopedBank((int) substr($paymentSource, 5));
        } elseif (str_starts_with($paymentSource, 'account:')) {
            $account = $this->findScopedAccount((int) substr($paymentSource, 8));
        }

        if ($paymentAmount > 0 && $allocations->isEmpty() && $openingBalancePayment <= 0) {
            $purchasesQuery = Purchase::query()
                ->where('supplier_id', $supplier->id)
                ->orderBy(
                    Schema::hasColumn('purchases', 'purchase_date')
                        ? 'purchase_date'
                        : (Schema::hasColumn('purchases', 'date') ? 'date' : 'created_at')
                )
                ->orderBy('id');
            $this->applyTenantScope($purchasesQuery, 'purchases');
            $this->applyBranchScopeToPurchases($purchasesQuery);
            $candidatePurchases = $purchasesQuery->get();
            if ($candidatePurchases->isEmpty()) {
                $fallbackPurchasesQuery = Purchase::query()
                    ->where('supplier_id', $supplier->id)
                    ->orderBy(
                        Schema::hasColumn('purchases', 'purchase_date')
                            ? 'purchase_date'
                            : (Schema::hasColumn('purchases', 'date') ? 'date' : 'created_at')
                    )
                    ->orderBy('id');
                $this->applyTenantScope($fallbackPurchasesQuery, 'purchases');
                $candidatePurchases = $fallbackPurchasesQuery->get();
            }

            $remainingPaymentAmount = $paymentAmount;
            foreach ($candidatePurchases as $purchase) {
                if ($remainingPaymentAmount <= 0) {
                    break;
                }

                $outstandingBalance = $this->resolvePurchaseOutstandingAmount($purchase);
                if ($outstandingBalance <= 0) {
                    continue;
                }

                $allocated = min($outstandingBalance, $remainingPaymentAmount);
                $allocations->put((int) $purchase->id, round($allocated, 2));
                $remainingPaymentAmount = round($remainingPaymentAmount - $allocated, 2);
            }

            if ($remainingPaymentAmount > 0) {
                $openingBalancePayment = min((float) ($supplier->opening_balance ?? 0), $remainingPaymentAmount);
            }
        }

        if ($allocations->isEmpty() && $openingBalancePayment <= 0) {
            return back()->withInput()->with('error', 'Enter at least one supplier payment amount before saving.');
        }

        $totalPayment = round($paymentAmount > 0 ? $paymentAmount : ($allocations->sum() + $openingBalancePayment), 2);
        if ($bank || $account) {
            $availableBalance = $this->resolveSourceBalance($bank, $account);
            if ($totalPayment > $availableBalance) {
                return back()->withInput()->with('error', 'Selected payment source does not have enough funds to cover the payment total of ₦' . number_format($totalPayment, 2) . '.');
            }
        }

        try {
            DB::transaction(function () use ($supplier, $allocations, $openingBalancePayment, $request, $paymentDate, $paymentGroup, $bank, $account) {
                $purchasesQuery = Purchase::query()
                    ->where('supplier_id', $supplier->id)
                    ->whereIn('id', $allocations->keys())
                    ->lockForUpdate();
                $this->applyTenantScope($purchasesQuery, 'purchases');
                $this->applyBranchScopeToPurchases($purchasesQuery);
                $purchases = $purchasesQuery->get()->keyBy('id');
                if ($purchases->isEmpty() && $allocations->isNotEmpty()) {
                    $fallbackPurchasesQuery = Purchase::query()
                        ->where('supplier_id', $supplier->id)
                        ->whereIn('id', $allocations->keys())
                        ->lockForUpdate();
                    $this->applyTenantScope($fallbackPurchasesQuery, 'purchases');
                    $purchases = $fallbackPurchasesQuery->get()->keyBy('id');
                }
                $activeBranch = $this->getActiveBranchContext();

                foreach ($allocations as $purchaseId => $amountRequested) {
                    /** @var \App\Models\Purchase|null $purchase */
                    $purchase = $purchases->get($purchaseId);
                    if (!$purchase) {
                        continue;
                    }

                    $remaining = $this->resolvePurchaseOutstandingAmount($purchase);
                    $amount = min($remaining, max(0, (float) $amountRequested));
                    if ($amount <= 0) {
                        continue;
                    }

                    $newPaid = round($this->resolvePurchasePaidAmount($purchase) + $amount, 2);
                    if (Schema::hasColumn('purchases', 'paid_amount')) {
                        $purchase->paid_amount = $newPaid;
                    }
                    if (Schema::hasColumn('purchases', 'paid_at')) {
                        $purchase->paid_at = $paymentDate;
                    }
                    if (Schema::hasColumn('purchases', 'bank_id') && $bank) {
                        $purchase->bank_id = $bank->id;
                    }
                    $newBalance = max(0, $this->purchaseGrossTotal($purchase) - $newPaid);
                    $purchase->status = $newBalance <= 0 ? 'paid' : 'partial';
                    $purchase->save();

                    if (Schema::hasTable('supplier_payments')) {
                        $paymentPayload = [
                            'supplier_id' => $supplier->id,
                            'purchase_id' => $purchase->id,
                            'company_id' => auth()->user()?->company_id ?? session('current_tenant_id'),
                            'user_id' => auth()->id(),
                            'branch_id' => $purchase->branch_id ?? $activeBranch['id'],
                            'branch_name' => $purchase->branch_name ?? $activeBranch['name'],
                            'bank_id' => $bank?->id,
                            'payment_group' => $paymentGroup,
                            'reference' => $paymentGroup,
                            'amount' => $amount,
                            'method' => $request->input('method') ?: 'Bank Transfer',
                            'note' => $request->input('note'),
                            'payment_date' => $paymentDate,
                            'created_by' => auth()->id(),
                        ];

                        if (Schema::hasColumn('supplier_payments', 'account_id')) {
                            $paymentPayload['account_id'] = $account?->id;
                        }

                        SupplierPayment::create($paymentPayload);
                    }

                    // Deduct from bank balance if applicable
                    if ($bank && Schema::hasColumn('banks', 'balance')) {
                        $bank->balance = max(0, (float)$bank->balance - $amount);
                        $bank->save();
                    }
                    if ($account && Schema::hasColumn('accounts', 'current_balance')) {
                        $account->current_balance = max(0, (float) $account->current_balance - $amount);
                        $account->save();
                    }

                    LedgerService::postPurchasePayment(
                        $purchase->fresh(),
                        $amount,
                        $request->input('method') ?: ($bank?->name ?: ($account?->name ?: 'Bank Transfer')),
                        $paymentGroup
                    );
                }

                if ($openingBalancePayment > 0) {
                    $currentOpeningBalance = (float) ($supplier->opening_balance ?? 0);
                    $amount = min($currentOpeningBalance, $openingBalancePayment);

                    if ($amount > 0) {
                        $supplier->opening_balance = max(0, $currentOpeningBalance - $amount);
                        $supplier->save();

                        if (Schema::hasTable('supplier_payments')) {
                            $paymentPayload = [
                                'supplier_id' => $supplier->id,
                                'purchase_id' => null,
                                'company_id' => auth()->user()?->company_id ?? session('current_tenant_id'),
                                'user_id' => auth()->id(),
                                'branch_id' => $this->getActiveBranchContext()['id'],
                                'branch_name' => $this->getActiveBranchContext()['name'],
                                'bank_id' => $bank?->id,
                                'payment_group' => $paymentGroup,
                                'reference' => $paymentGroup,
                                'amount' => $amount,
                                'method' => $request->input('method') ?: 'Bank Transfer',
                                'note' => $request->input('note') ?: 'Supplier opening balance payment.',
                                'payment_date' => $paymentDate,
                                'created_by' => auth()->id(),
                            ];

                            if (Schema::hasColumn('supplier_payments', 'account_id')) {
                                $paymentPayload['account_id'] = $account?->id;
                            }

                            SupplierPayment::create($paymentPayload);
                        }

                        // Deduct from bank balance if applicable
                        if ($bank && Schema::hasColumn('banks', 'balance')) {
                            $bank->balance = max(0, (float)$bank->balance - $amount);
                            $bank->save();
                        }
                        if ($account && Schema::hasColumn('accounts', 'current_balance')) {
                            $account->current_balance = max(0, (float) $account->current_balance - $amount);
                            $account->save();
                        }
                    }
                }
            });
        } catch (\Throwable $exception) {
            return back()->withInput()->with('error', 'Supplier payment could not be saved. ' . $exception->getMessage());
        }

        return redirect()
            ->route('suppliers.pay', $supplier->id)
            ->with('success', 'Supplier payment recorded successfully.');
    }

    public function update(Request $request, $id): RedirectResponse
    {
        $supplier = $this->applyTenantScope($this->newSupplierQuery())->findOrFail($id);
        $request->validate([
            'name'    => 'required|string|max:191',
            'contact' => 'nullable|string|max:191',
            'email'   => 'nullable|email|max:191',
            'phone'   => 'nullable|string|max:191',
            'address' => 'nullable|string|max:255',
            'opening_balance' => 'nullable|numeric',
            'opening_balance_date' => 'nullable|date',
        ]);

        $payload = $this->sanitizeForSupplierColumns([
            'contact' => $request->input('contact'),
            'email' => $request->input('email'),
            'phone' => $request->input('phone'),
            'address' => $request->input('address'),
            'opening_balance' => $request->input('opening_balance'),
            'opening_balance_date' => $request->input('opening_balance_date'),
        ]);
        $nameColumn = $this->resolveNameColumn();
        $payload[$nameColumn] = $request->input('name');

        $supplier->update($payload);
        return redirect()->route('suppliers.index')->with('success', 'Supplier updated successfully.');
    }

    public function destroy($id): RedirectResponse
    {
        $supplier = $this->applyTenantScope($this->newSupplierQuery())->findOrFail($id);
        $supplier->delete();
        return redirect()->route('suppliers.index')->with('success', 'Supplier deleted.');
    }

    public function downloadImportTemplate()
    {
        $content = implode(',', [
            'name',
            'email',
            'phone',
            'address',
            'contact',
        ]) . PHP_EOL;

        return response($content, 200, [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="supplier-import-template.csv"',
        ]);
    }

    public function import(Request $request): RedirectResponse
    {
        Log::info('Supplier import request received.', [
            'user_id' => auth()->id(),
            'has_file' => $request->hasFile('import_file'),
            'filename' => $request->file('import_file')?->getClientOriginalName(),
            'size' => $request->file('import_file')?->getSize(),
        ]);

        $request->validate([
            'import_file' => 'required|file|mimes:csv,txt,xls,xlsx|max:20480',
            'update_existing' => 'nullable|boolean',
        ]);

        try {
            if (!Schema::hasTable('suppliers')) {
                return back()->with('error', 'Suppliers table is not available in this workspace.');
            }

            $file = $request->file('import_file');
            $header = null;
            foreach ($this->spreadsheetRowIterator($file) as $row) {
                $header = $row;
                break;
            }

            if (!$header) {
                return back()->with('error', 'The import file is empty.');
            }

            $header = array_map(fn ($value) => $this->normalizeImportHeaderCell($value), $header);
            foreach (['name'] as $requiredColumn) {
                if (!in_array($requiredColumn, $header, true)) {
                    return back()->with('error', 'Missing required import column: ' . $requiredColumn);
                }
            }

            $created = 0;
            $updated = 0;
            $updatedExisting = 0;
            $skipped = 0;
            $duplicates = 0;
            $missingRequired = 0;
            $rowErrors = [];
            $companyId = (int) (auth()->user()?->company_id ?? 0);
            $userId = (int) (auth()->id() ?? 0);
            $updateExisting = $request->boolean('update_existing');

            foreach ($this->spreadsheetRowIterator($file) as $rowNumber => $row) {
                if ($rowNumber === 0) {
                    continue;
                }

                $rowData = [];
                foreach ($header as $index => $column) {
                    $rowData[$column] = trim((string) ($row[$index] ?? ''));
                }

                if (($rowData['name'] ?? '') === '') {
                    $skipped++;
                    $missingRequired++;
                    if (count($rowErrors) < 10) {
                        $rowErrors[] = 'Row ' . ($rowNumber + 1) . ': missing name';
                    }
                    continue;
                }

                try {
                    $lookupEmail = $rowData['email'] ?? '';
                    $lookupPhone = $rowData['phone'] ?? '';

                    $supplierQuery = $this->newSupplierQuery();
                    if ($companyId > 0 && Schema::hasColumn('suppliers', 'company_id')) {
                        $supplierQuery->where('company_id', $companyId);
                    } elseif ($userId > 0 && Schema::hasColumn('suppliers', 'user_id')) {
                        $supplierQuery->where('user_id', $userId);
                    }

                    if ($lookupEmail !== '' && Schema::hasColumn('suppliers', 'email')) {
                        $supplierQuery->where(function ($query) use ($lookupEmail, $lookupPhone) {
                            $query->where('email', $lookupEmail);

                            if ($lookupPhone !== '' && Schema::hasColumn('suppliers', 'phone')) {
                                $query->orWhere('phone', $lookupPhone);
                            }
                        });
                    } elseif ($lookupPhone !== '' && Schema::hasColumn('suppliers', 'phone')) {
                        $supplierQuery->where('phone', $lookupPhone);
                    } else {
                        $nameColumn = $this->resolveNameColumn();
                        $supplierQuery->where($nameColumn, $rowData['name']);
                    }

                    $supplier = $supplierQuery->first();
                    $isNew = !$supplier;
                    if ($supplier && !$updateExisting) {
                        $skipped++;
                        $duplicates++;
                        if (count($rowErrors) < 10) {
                            $rowErrors[] = 'Row ' . ($rowNumber + 1) . ': duplicate supplier detected';
                        }
                        continue;
                    }

                    $nameColumn = $this->resolveNameColumn();
                    $payload = $this->sanitizeForSupplierColumns([
                        'contact' => $rowData['contact'] ?? null,
                        'email' => $lookupEmail !== '' ? $lookupEmail : null,
                        'phone' => $lookupPhone !== '' ? $lookupPhone : null,
                        'address' => $rowData['address'] ?? null,
                        'opening_balance' => is_numeric($rowData['opening_balance'] ?? ($rowData['balance'] ?? null))
                            ? (float) ($rowData['opening_balance'] ?? $rowData['balance'])
                            : 0,
                        'opening_balance_date' => is_numeric($rowData['opening_balance'] ?? ($rowData['balance'] ?? null))
                            && (float) ($rowData['opening_balance'] ?? $rowData['balance']) > 0
                                ? ($rowData['opening_balance_date'] ?? now()->toDateString())
                                : null,
                        'company_id' => $companyId > 0 ? $companyId : null,
                        'user_id' => $userId > 0 ? $userId : null,
                    ]);
                    $payload[$nameColumn] = $rowData['name'];

                    $supplier = $supplier ?: new Supplier();
                    $supplier->fill($payload);
                    $supplier->save();

                    if ($isNew) {
                        $created++;
                    } else {
                        $updated++;
                        $updatedExisting++;
                    }
                } catch (\Throwable $rowException) {
                    Log::warning('Supplier import row skipped.', [
                        'row' => $rowNumber + 1,
                        'supplier' => $rowData['name'] ?? null,
                        'error' => $rowException->getMessage(),
                    ]);
                    $skipped++;
                }
            }

            $summary = "Supplier import completed. Created: {$created}, Updated: {$updated}, Skipped: {$skipped}.";
            if ($updatedExisting > 0) {
                $summary .= " Updated existing: {$updatedExisting}.";
            }
            if ($duplicates > 0 || $missingRequired > 0) {
                $summary .= " Duplicates skipped: {$duplicates}, Missing required: {$missingRequired}.";
            }

            $redirect = redirect()->route('suppliers.index')->with('success', $summary);
            if (!empty($rowErrors)) {
                $redirect->with('warning', 'Some rows were skipped: ' . implode(' | ', $rowErrors));
            }
            return $redirect;
        } catch (\Throwable $exception) {
            Log::error('Supplier import failed.', [
                'user_id' => auth()->id(),
                'error' => $exception->getMessage(),
            ]);

            return back()->withInput()->with(
                'error',
                'The supplier import could not be completed. Please confirm the spreadsheet columns and try again.'
            );
        }
    }

    private function sanitizeForSupplierColumns(array $data): array
    {
        $allowed = array_flip(Schema::getColumnListing('suppliers'));
        return array_intersect_key($data, $allowed);
    }

    private function applyBranchScopeToPurchases($query)
    {
        $activeBranch = $this->getActiveBranchContext();
        $branchId = trim((string) ($activeBranch['id'] ?? ''));
        $branchName = trim((string) ($activeBranch['name'] ?? ''));

        if ($branchId === '' && $branchName === '') {
            return $query;
        }

        return $query->where(function ($sub) use ($branchId, $branchName) {
            if ($branchId !== '' && Schema::hasColumn('purchases', 'branch_id')) {
                $sub->where('purchases.branch_id', $branchId);
            }
            if ($branchName !== '' && Schema::hasColumn('purchases', 'branch_name')) {
                $sub->orWhere('purchases.branch_name', $branchName);
            }
        });
    }

    private function calculateSupplierOutstandingBalance(int $supplierId): float
    {
        if (!Schema::hasTable('purchases')) {
            return 0.0;
        }

        $purchaseColumns = ['id', 'supplier_id', 'total_amount'];
        if (Schema::hasColumn('purchases', 'paid_amount')) {
            $purchaseColumns[] = 'paid_amount';
        }

        $query = Purchase::query()->where('supplier_id', $supplierId);
        $this->applyTenantScope($query, 'purchases');
        $this->applyBranchScopeToPurchases($query);
        $purchases = $query->get($purchaseColumns);

        return round((float) $purchases->sum(fn ($purchase) => $this->resolvePurchaseOutstandingAmount($purchase)), 2);
    }

    private function supplierOutstandingBalances(array $supplierIds): array
    {
        $supplierIds = collect($supplierIds)
            ->map(fn ($id) => (int) $id)
            ->filter(fn ($id) => $id > 0)
            ->values();

        if ($supplierIds->isEmpty() || !Schema::hasTable('purchases')) {
            return [];
        }

        $query = Purchase::query()
            ->whereIn('supplier_id', $supplierIds->all())
            ->select(array_values(array_filter([
                'id',
                'supplier_id',
                'total_amount',
                Schema::hasColumn('purchases', 'paid_amount') ? 'paid_amount' : null,
            ])));

        $this->applyTenantScope($query, 'purchases');
        $this->applyBranchScopeToPurchases($query);

        return $query->get()
            ->groupBy('supplier_id')
            ->map(fn ($purchases) => round((float) collect($purchases)->sum(fn ($purchase) => $this->resolvePurchaseOutstandingAmount($purchase)), 2))
            ->all();
    }

    private function calculateTotalPayables(): float
    {
        $openingBalances = 0.0;
        $purchaseBalances = 0.0;

        $supplierQuery = $this->newSupplierQuery();
        $this->applyTenantScope($supplierQuery);
        if (Schema::hasColumn('suppliers', 'opening_balance')) {
            $openingBalances = (float) $supplierQuery->sum('opening_balance');
        }

        if (Schema::hasTable('purchases')) {
            $query = Purchase::query()->select(array_values(array_filter([
                'id',
                'supplier_id',
                'total_amount',
                Schema::hasColumn('purchases', 'paid_amount') ? 'paid_amount' : null,
            ])));
            $this->applyTenantScope($query, 'purchases');
            $this->applyBranchScopeToPurchases($query);
            $purchaseBalances = round((float) $query->get()->sum(fn ($purchase) => $this->resolvePurchaseOutstandingAmount($purchase)), 2);
        }

        return $openingBalances + $purchaseBalances;
    }

    private function normalizeImportHeaderCell($value): string
    {
        $header = strtolower(trim((string) $value));
        $header = preg_replace('/^\x{FEFF}/u', '', $header) ?? $header;

        $aliases = [
            'supplier' => 'name',
            'supplier_name' => 'name',
            'vendor' => 'name',
            'vendor_name' => 'name',
            'mobile' => 'phone',
            'phone_number' => 'phone',
            'email_address' => 'email',
            'opening_bal' => 'opening_balance',
            'opening_balance' => 'opening_balance',
            'balance' => 'opening_balance',
            'ob' => 'opening_balance',
            'opening_balance_date' => 'opening_balance_date',
            'opening_date' => 'opening_balance_date',
        ];

        return $aliases[$header] ?? $header;
    }

    private function spreadsheetRowIterator(\Illuminate\Http\UploadedFile $file): \Generator
    {
        $extension = strtolower((string) $file->getClientOriginalExtension());

        if (in_array($extension, ['csv', 'txt'], true)) {
            $handle = fopen($file->getRealPath(), 'r');
            if ($handle === false) {
                return;
            }

            try {
                $delimiter = $this->detectCsvDelimiter($handle);
                while (($line = fgets($handle)) !== false) {
                    $line = $this->normalizeCsvLine($line);
                    if ($line === '') {
                        continue;
                    }

                    $row = str_getcsv($line, $delimiter);
                    $row = $this->expandEmbeddedDelimitedRow($row);
                    if ($row === [null] || $row === false) {
                        continue;
                    }

                    yield $row;
                }
            } finally {
                fclose($handle);
            }

            return;
        }

        $reader = IOFactory::createReaderForFile($file->getRealPath());
        if (method_exists($reader, 'setReadDataOnly')) {
            $reader->setReadDataOnly(true);
        }
        if (method_exists($reader, 'setReadEmptyCells')) {
            $reader->setReadEmptyCells(false);
        }

        $spreadsheet = $reader->load($file->getRealPath());

        try {
            $sheet = $spreadsheet->getActiveSheet();
            $highestRow = (int) $sheet->getHighestDataRow();
            $highestColumn = $sheet->getHighestDataColumn();

            if ($highestRow <= 0 || $highestColumn === '') {
                return;
            }

            foreach ($sheet->rangeToArray("A1:{$highestColumn}{$highestRow}", null, true, false, false) as $cells) {
                $cells = array_map(fn ($value) => is_scalar($value) ? trim((string) $value) : $value, $cells);
                $hasValue = collect($cells)->contains(fn ($value) => trim((string) $value) !== '');
                if (!$hasValue) {
                    continue;
                }

                yield $cells;
            }
        } finally {
            $spreadsheet->disconnectWorksheets();
            unset($spreadsheet);
        }
    }

    private function detectCsvDelimiter($handle): string
    {
        $default = ',';
        $candidates = [',', ';', "\t", '|'];
        $firstLine = fgets($handle);
        if ($firstLine === false) {
            rewind($handle);
            return $default;
        }

        $firstLine = $this->normalizeCsvLine($firstLine);
        $bestDelimiter = $default;
        $bestCount = 0;
        foreach ($candidates as $candidate) {
            $count = substr_count($firstLine, $candidate);
            if ($count > $bestCount) {
                $bestDelimiter = $candidate;
                $bestCount = $count;
            }
        }

        rewind($handle);
        return $bestDelimiter;
    }

    private function normalizeCsvLine(string $line): string
    {
        if ($line === '') {
            return '';
        }

        if (str_starts_with($line, "\xEF\xBB\xBF")) {
            $line = substr($line, 3);
        }

        if (!mb_check_encoding($line, 'UTF-8')) {
            $line = mb_convert_encoding($line, 'UTF-8', 'UTF-16LE,UTF-16BE,Windows-1252,ISO-8859-1');
        }

        return trim($line);
    }

    private function expandEmbeddedDelimitedRow($row): array
    {
        if (!is_array($row)) {
            return [];
        }

        if (count($row) !== 1) {
            return $row;
        }

        $cell = (string) ($row[0] ?? '');
        if ($cell === '') {
            return $row;
        }

        $delimiterCandidates = [',', ';', "\t", '|'];
        foreach ($delimiterCandidates as $delimiter) {
            if (strpos($cell, $delimiter) !== false) {
                return str_getcsv($cell, $delimiter);
            }
        }

        return $row;
    }
}
