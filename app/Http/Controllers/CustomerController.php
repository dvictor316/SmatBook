<?php

namespace App\Http\Controllers;

use App\Models\Account;
use App\Models\Bank;
use App\Models\Customer;
use App\Models\Payment;
use App\Models\Sale;
use App\Support\LedgerService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use PhpOffice\PhpSpreadsheet\IOFactory;

class CustomerController extends Controller
{
    private function applyTenantScope($query)
    {
        $companyId = (int) (auth()->user()?->company_id ?? session('current_tenant_id') ?? 0);
        $userId = (int) (auth()->id() ?? 0);
        $activeBranch = $this->getActiveBranchContext();
        $branchId = trim((string) ($activeBranch['id'] ?? ''));
        $branchName = trim((string) ($activeBranch['name'] ?? ''));

        if ($companyId > 0 && Schema::hasColumn('customers', 'company_id')) {
            $query->where('company_id', $companyId);
        } elseif ($userId > 0 && Schema::hasColumn('customers', 'user_id')) {
            $query->where('user_id', $userId);
        }

        if ($branchId !== '' || $branchName !== '') {
            $query->where(function ($sub) use ($branchId, $branchName) {
                if ($branchId !== '' && Schema::hasColumn('customers', 'branch_id')) {
                    $sub->where('branch_id', $branchId);
                }
                if ($branchName !== '' && Schema::hasColumn('customers', 'branch_name')) {
                    $sub->orWhere('branch_name', $branchName);
                }
            });
        }

        return $query;
    }

    /**
     * Display a listing of customers.
     */
    public function index(Request $request)
    {
        $customers = $this->buildCustomerQuery($request)
            ->paginate(20)
            ->withQueryString();

        $customers->getCollection()->transform(function ($customer) {
            $customer->computed_balance = (float) ($customer->balance ?? 0) + (float) ($customer->sales_balance_sum ?? 0);
            return $customer;
        });

        $totalReceivables = $this->calculateTotalReceivables();

        return view('Customers.customers', compact('customers', 'totalReceivables'));
    }

    /**
     * Show the form for creating a new customer.
     */
    public function create()
    {
        return view('Customers.add-customer'); 
    }

    /**
     * Store a newly created customer in storage.
     */
    public function store(Request $request)
    {
        $request->validate([
            'customer_name' => 'required|string|max:191',
            'email'         => 'nullable|email|max:191|unique:customers,email',
            'phone'         => 'nullable|string|max:191',
            'balance'       => 'nullable|numeric|min:0',
            'opening_balance_date' => 'nullable|date',
            'credit_limit'  => 'nullable|numeric|min:0',
            'image'         => 'nullable|image|mimes:jpeg,png,jpg|max:2048',
        ]);

        // Capture all possible form fields, then keep only real DB columns.
        $data = $request->only([
            'customer_name', 'email', 'phone', 'currency', 'website', 'notes', 'credit_limit',
            'opening_balance_date',
            'billing_name', 'billing_address_line1', 'billing_address_line2', 
            'billing_country', 'billing_city', 'billing_state', 'billing_pincode',
            'shipping_name', 'shipping_address_line1', 'shipping_address_line2', 
            'shipping_country', 'shipping_city', 'shipping_state', 'shipping_pincode',
            'bank_name', 'branch', 'account_holder', 'account_number', 'ifsc'
        ]);

        $data['status'] = 'active'; 
        $data['balance'] = (float) $request->input('balance', 0.00);
        if ($request->filled('opening_balance_date')) {
            $data['opening_balance_date'] = $request->input('opening_balance_date');
        }
        if ($request->filled('credit_limit')) {
            $data['credit_limit'] = (float) $request->input('credit_limit', 0.00);
        }

        if ($request->hasFile('image')) {
            $data['image'] = $request->file('image')->store('profiles', 'public');
        }

        if ($request->filled('opening_balance_date')) {
            $data['opening_balance_date'] = $request->input('opening_balance_date');
        }
        $data = $this->sanitizeForCustomerColumns($data);

        if (Schema::hasColumn('customers', 'company_id')) {
            $data['company_id'] = auth()->user()?->company_id ?? session('current_tenant_id');
        }
        if (Schema::hasColumn('customers', 'user_id')) {
            $data['user_id'] = auth()->id();
        }
        if (Schema::hasColumn('customers', 'branch_id')) {
            $data['branch_id'] = $this->getActiveBranchContext()['id'];
        }
        if (Schema::hasColumn('customers', 'branch_name')) {
            $data['branch_name'] = $this->getActiveBranchContext()['name'];
        }

        Customer::create($data);

        return redirect()->route('customers.index')->with('success', 'Customer added successfully.');
    }

    /**
     * Display the specified customer.
     */
    public function show($id)
    {
        $customer = $this->applyTenantScope(Customer::with(['sales', 'invoices']))->findOrFail($id);
        $invoices = $customer->invoices;
        $salesBalance = $this->calculateCustomerSalesBalance($customer->id);
        $customer->computed_balance = (float) ($customer->balance ?? 0) + $salesBalance;

        $invoicescards = [
            [
                'title'  => 'Total Invoices',
                'amount' => $invoices->count(),
                'icon'   => 'clipboard-text',
                'class'  => 'bg-blue-light',
            ],
            [
                'title'  => 'Total Sales',
                'amount' => '₦' . number_format($customer->sales->sum('total'), 2),
                'icon'   => 'archive',
                'class'  => 'bg-green-light',
            ],
            [
                'title'  => 'Pending Balance',
                'amount' => '₦' . number_format($customer->computed_balance, 2),
                'icon'   => 'clock',
                'class'  => 'bg-orange-light',
            ],
            [
                'title'  => 'Total Paid',
                'amount' => '₦' . number_format($customer->sales->sum('amount_paid'), 2),
                'icon'   => 'check-circle',
                'class'  => 'bg-emerald-light',
            ]
        ];

        return view('Customers.customer-details', compact('customer', 'invoices', 'invoicescards'));
    }

    /**
     * Show the form for editing the specified customer.
     */
    public function edit($id)
    {
        $customer = $this->applyTenantScope(Customer::query())->findOrFail($id);
        return view('Customers.edit-customer', compact('customer'));
    }

   /**
     * Update the specified customer in storage.
     */
    public function update(Request $request, $id)
    {
        $customer = $this->applyTenantScope(Customer::query())->findOrFail($id);

        $request->validate([
            'customer_name' => 'required|string|max:191',
            'email'         => 'nullable|email|max:191|unique:customers,email,' . $id,
            'image'         => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
            'status'        => 'required|in:active,deactive',
            'balance'       => 'nullable|numeric|min:0',
            'opening_balance_date' => 'nullable|date',
            'credit_limit'  => 'nullable|numeric|min:0',
        ]);

        // Capture all possible form fields for update, then keep only real DB columns.
        $data = $request->only([
            'customer_name', 'email', 'phone', 'status', 'currency', 'website', 'notes', 'balance', 'credit_limit',
            'opening_balance_date',
            'billing_name', 'billing_address_line1', 'billing_address_line2', 
            'billing_country', 'billing_city', 'billing_state', 'billing_pincode',
            'shipping_name', 'shipping_address_line1', 'shipping_address_line2', 
            'shipping_country', 'shipping_city', 'shipping_state', 'shipping_pincode',
            'bank_name', 'branch', 'account_holder', 'account_number', 'ifsc'
        ]);

        if ($request->hasFile('image')) {
            if ($customer->image && Storage::disk('public')->exists($customer->image)) {
                Storage::disk('public')->delete($customer->image);
            }
            $data['image'] = $request->file('image')->store('profiles', 'public');
        }

        $data = $this->sanitizeForCustomerColumns($data);

        $customer->update($data);

        // CHANGE THIS LINE: 
        // From: return redirect()->route('customers.show', $customer->id)
        // To:   return redirect()->route('customers.index')
        
        $redirectTo = (string) $request->input('redirect_to', '');
        if ($redirectTo === 'show') {
            return redirect()->route('customers.show', $customer->id)
                ->with('success', 'Customer record updated successfully.');
        }

        return redirect()->route('customers.index')
            ->with('success', 'Customer record updated successfully.');
    }
    /**
     * Toggle Status and Filters.
     */
    public function activeView(Request $request)
    {
        $customers = $this->buildCustomerQuery($request, 'active')
            ->paginate(20)
            ->withQueryString();

        $customers->getCollection()->transform(function ($customer) {
            $customer->computed_balance = (float) ($customer->balance ?? 0) + (float) ($customer->sales_balance_sum ?? 0);
            return $customer;
        });

        $totalReceivables = $this->calculateTotalReceivables();

        return view('Customers.customers', compact('customers', 'totalReceivables'));
    }

    public function deactiveView(Request $request)
    {
        $customers = $this->buildCustomerQuery($request, 'deactive')
            ->paginate(20)
            ->withQueryString();

        $customers->getCollection()->transform(function ($customer) {
            $customer->computed_balance = (float) ($customer->balance ?? 0) + (float) ($customer->sales_balance_sum ?? 0);
            return $customer;
        });

        $totalReceivables = $this->calculateTotalReceivables();

        return view('Customers.customers', compact('customers', 'totalReceivables'));
    }

    private function buildCustomerQuery(Request $request, ?string $fixedStatus = null)
    {
        $query = Customer::query()->latest();
        $this->applyTenantScope($query);

        $search = trim((string) $request->input('search', ''));
        if ($search !== '') {
            $query->where(function ($builder) use ($search) {
                $builder->where('customer_name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhere('phone', 'like', "%{$search}%");
            });
        }

        if ($fixedStatus !== null) {
            $query->where('status', $fixedStatus);
        } elseif ($request->filled('status')) {
            $query->where('status', trim((string) $request->input('status')));
        }

        $this->withSalesBalances($query);

        return $query;
    }

    private function getActiveBranchContext(): array
    {
        $branchId = session('active_branch_id') ? (string) session('active_branch_id') : null;
        $branchName = session('active_branch_name') ? (string) session('active_branch_name') : null;

        if (!$branchId && !$branchName && Schema::hasTable('settings')) {
            $companyId = (int) (auth()->user()?->company_id ?? 0);
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

    private function applySaleBranchFilter($query, string $salesTable = 'sales')
    {
        $branchId = trim((string) ($this->getActiveBranchContext()['id'] ?? ''));
        $branchName = trim((string) ($this->getActiveBranchContext()['name'] ?? ''));

        if ($branchId === '' && $branchName === '') {
            return $query;
        }

        return $query->where(function ($sub) use ($salesTable, $branchId, $branchName) {
            if ($branchId !== '' && Schema::hasColumn('sales', 'branch_id')) {
                $sub->where("{$salesTable}.branch_id", $branchId);
            } elseif ($branchName !== '' && Schema::hasColumn('sales', 'branch_name')) {
                $sub->where("{$salesTable}.branch_name", $branchName);
            }

            if ($branchName !== '') {
                $sub->orWhereRaw(
                    "JSON_UNQUOTE(JSON_EXTRACT(COALESCE({$salesTable}.payment_details, '{}'), '$.branch_name')) = ?",
                    [$branchName]
                )->orWhereRaw(
                    "JSON_UNQUOTE(JSON_EXTRACT(COALESCE({$salesTable}.payment_details, '{}'), '$.branch.name')) = ?",
                    [$branchName]
                );
            }
        });
    }

    private function applySaleTenantScope($query)
    {
        $companyId = (int) (auth()->user()?->company_id ?? 0);
        $userId = (int) (auth()->id() ?? 0);

        if ($companyId > 0 && Schema::hasColumn('sales', 'company_id')) {
            $query->where('company_id', $companyId);
        } elseif ($userId > 0 && Schema::hasColumn('sales', 'user_id')) {
            $query->where('user_id', $userId);
        }

        return $query;
    }

    private function withSalesBalances($query): void
    {
        if (!Schema::hasTable('sales') || !Schema::hasColumn('sales', 'customer_id')) {
            return;
        }

        if (Schema::hasColumn('sales', 'balance')) {
            $query->withSum(['sales as sales_balance_sum' => function ($saleQuery) {
                $saleQuery->where('balance', '>', 0);
                $this->applySaleTenantScope($saleQuery);
                $this->applySaleBranchFilter($saleQuery, 'sales');
            }], 'balance');
        }
    }

    private function calculateCustomerSalesBalance(int $customerId): float
    {
        if (!Schema::hasTable('sales') || !Schema::hasColumn('sales', 'customer_id') || !Schema::hasColumn('sales', 'balance')) {
            return 0.0;
        }

        $query = Sale::query()->where('customer_id', $customerId)->where('balance', '>', 0);
        $this->applySaleTenantScope($query);
        $this->applySaleBranchFilter($query, 'sales');

        return (float) $query->sum('balance');
    }

    private function calculateTotalReceivables(): float
    {
        $openingBalances = 0.0;
        $salesBalances = 0.0;

        $customerQuery = Customer::query();
        $this->applyTenantScope($customerQuery);
        if (Schema::hasColumn('customers', 'balance')) {
            $openingBalances = (float) $customerQuery->sum('balance');
        }

        if (Schema::hasTable('sales') && Schema::hasColumn('sales', 'balance')) {
            $salesQuery = Sale::query()->where('balance', '>', 0);
            $this->applySaleTenantScope($salesQuery);
            $this->applySaleBranchFilter($salesQuery, 'sales');
            $salesBalances = (float) $salesQuery->sum('balance');
        }

        return $openingBalances + $salesBalances;
    }

    private function paymentsRequireSaleId(): bool
    {
        if (!Schema::hasTable('payments')) {
            return false;
        }

        try {
            $columnInfo = DB::select("SHOW COLUMNS FROM payments WHERE Field = 'sale_id'");
            if (!empty($columnInfo) && isset($columnInfo[0]->Null)) {
                return strtolower((string) $columnInfo[0]->Null) === 'no';
            }
        } catch (\Throwable $exception) {
            return false;
        }

        return false;
    }

    private function resolveReceivePaymentAccountId(?string $paymentTarget): ?int
    {
        $paymentTarget = trim((string) $paymentTarget);
        if ($paymentTarget === '') {
            return null;
        }

        [$targetType, $targetId] = array_pad(explode(':', $paymentTarget, 2), 2, null);
        $targetType = strtolower((string) $targetType);
        $targetId = (int) $targetId;

        if ($targetId <= 0) {
            return null;
        }

        if ($targetType === 'account' && Schema::hasTable('accounts')) {
            return Account::query()->whereKey($targetId)->value('id');
        }

        if ($targetType === 'bank' && Schema::hasTable('banks')) {
            $bank = Bank::query()->find($targetId);
            if (!$bank) {
                return null;
            }

            if (!Schema::hasTable('accounts')) {
                return null;
            }

            $accountName = $bank->name ?? $bank->bank_name ?? 'Bank Account';
            $payload = [
                'type' => 'Asset',
                'is_active' => 1,
                'company_id' => auth()->user()?->company_id ?? session('current_tenant_id'),
                'user_id' => auth()->id(),
            ];
            if (Schema::hasColumn('accounts', 'code')) {
                $payload['code'] = 'BANK-' . str_pad((string) ($targetId ?: 1), 5, '0', STR_PAD_LEFT);
            }
            if (Schema::hasColumn('accounts', 'opening_balance')) {
                $payload['opening_balance'] = 0;
            }
            if (Schema::hasColumn('accounts', 'current_balance')) {
                $payload['current_balance'] = 0;
            }

            $account = Account::firstOrCreate(['name' => $accountName], $payload);
            return (int) $account->id;
        }

        return null;
    }

    public function receivePayment($id)
    {
        $customer = $this->applyTenantScope(Customer::query())->findOrFail($id);

        $outstandingSalesQuery = Sale::query()
            ->where('customer_id', $customer->id)
            ->where('balance', '>', 0)
            ->orderBy('order_date')
            ->orderBy('id');
        $this->applySaleTenantScope($outstandingSalesQuery);
        $this->applySaleBranchFilter($outstandingSalesQuery, 'sales');
        $outstandingSales = $outstandingSalesQuery->get();

        $paymentHistoryQuery = Payment::with(['sale', 'account'])
            ->where('customer_id', $customer->id)
            ->latest();
        $companyId = (int) (auth()->user()?->company_id ?? session('current_tenant_id') ?? 0);
        $userId = (int) (auth()->id() ?? 0);
        if ($companyId > 0 && Schema::hasColumn('payments', 'company_id')) {
            $paymentHistoryQuery->where('company_id', $companyId);
        } elseif ($userId > 0 && Schema::hasColumn('payments', 'user_id')) {
            $paymentHistoryQuery->where('user_id', $userId);
        } elseif ($userId > 0 && Schema::hasColumn('payments', 'created_by')) {
            $paymentHistoryQuery->where('created_by', $userId);
        }
        if (Schema::hasColumn('payments', 'branch_id') || Schema::hasColumn('payments', 'branch_name')) {
            $activeBranch = $this->getActiveBranchContext();
            $branchId = trim((string) ($activeBranch['id'] ?? ''));
            $branchName = trim((string) ($activeBranch['name'] ?? ''));
            if ($branchId !== '' || $branchName !== '') {
                $paymentHistoryQuery->where(function ($sub) use ($branchId, $branchName) {
                    if ($branchId !== '' && Schema::hasColumn('payments', 'branch_id')) {
                        $sub->where('branch_id', $branchId);
                    }
                    if ($branchName !== '' && Schema::hasColumn('payments', 'branch_name')) {
                        $sub->orWhere('branch_name', $branchName);
                    }
                });
            }
        }
        $paymentHistory = $paymentHistoryQuery->limit(50)->get();

        $paymentDestinations = collect();
        if (Schema::hasTable('accounts')) {
            $accountsQuery = Account::query()->orderBy('name');
            $companyId = (int) (auth()->user()?->company_id ?? session('current_tenant_id') ?? 0);
            $userId = (int) (auth()->id() ?? 0);
            if ($companyId > 0 && Schema::hasColumn('accounts', 'company_id')) {
                $accountsQuery->where('company_id', $companyId);
            } elseif ($userId > 0 && Schema::hasColumn('accounts', 'user_id')) {
                $accountsQuery->where('user_id', $userId);
            }
            if (Schema::hasColumn('accounts', 'is_active')) {
                $accountsQuery->where('is_active', 1);
            }
            $paymentDestinations = $paymentDestinations->merge(
                $accountsQuery->get()->map(function ($account) {
                    return (object) [
                        'value' => 'account:' . $account->id,
                        'label' => $account->name,
                        'type' => 'Account',
                    ];
                })
            );
        }

        if (Schema::hasTable('banks')) {
            $banksQuery = Bank::query()->orderBy('name');
            $companyId = (int) (auth()->user()?->company_id ?? session('current_tenant_id') ?? 0);
            $userId = (int) (auth()->id() ?? 0);
            if ($companyId > 0 && Schema::hasColumn('banks', 'company_id')) {
                $banksQuery->where('company_id', $companyId);
            } elseif ($userId > 0 && Schema::hasColumn('banks', 'user_id')) {
                $banksQuery->where('user_id', $userId);
            }
            $activeBranch = $this->getActiveBranchContext();
            $branchId = trim((string) ($activeBranch['id'] ?? ''));
            $branchName = trim((string) ($activeBranch['name'] ?? ''));
            if ($branchId !== '' || $branchName !== '') {
                $banksQuery->where(function ($sub) use ($branchId, $branchName) {
                    if ($branchId !== '' && Schema::hasColumn('banks', 'branch_id')) {
                        $sub->where('branch_id', $branchId);
                    }
                    if ($branchName !== '' && Schema::hasColumn('banks', 'branch_name')) {
                        $sub->orWhere('branch_name', $branchName);
                    }
                });
            }

            $existingLabels = $paymentDestinations->pluck('label')->map(fn ($label) => strtolower((string) $label))->all();
            $paymentDestinations = $paymentDestinations->merge(
                $banksQuery->get()
                    ->reject(fn ($bank) => in_array(strtolower((string) ($bank->name ?? '')), $existingLabels, true))
                    ->map(function ($bank) {
                        return (object) [
                            'value' => 'bank:' . $bank->id,
                            'label' => $bank->name,
                            'type' => 'Bank',
                        ];
                    })
            );
        }

        $customer->computed_balance = (float) ($customer->balance ?? 0) + (float) $outstandingSales->sum('balance');
        $outstandingOpeningBalance = (float) ($customer->balance ?? 0);
        $outstandingInvoicesTotal = (float) $outstandingSales->sum('balance');
        $supportsStandalonePayments = !$this->paymentsRequireSaleId();

        return view('Customers.receive-payment', compact(
            'customer',
            'outstandingSales',
            'paymentHistory',
            'paymentDestinations',
            'outstandingOpeningBalance',
            'outstandingInvoicesTotal',
            'supportsStandalonePayments'
        ));
    }

    public function storeReceivedPayment(Request $request, $id)
    {
        $customer = $this->applyTenantScope(Customer::query())->findOrFail($id);

        $request->validate([
            'payment_date' => 'required|date',
            'received_amount' => 'nullable|numeric|min:0.01',
            'payment_target' => 'nullable|string|max:50',
            'reference' => 'nullable|string|max:191',
            'method' => 'nullable|string|max:100',
            'note' => 'nullable|string|max:1000',
            'allocations' => 'required|array',
        ]);

        $rawAllocations = collect($request->input('allocations', []))
            ->map(fn ($value) => round((float) $value, 2));

        $openingAllocation = max(0, (float) $rawAllocations->get('opening_balance', 0));
        $saleAllocations = $rawAllocations
            ->except(['opening_balance'])
            ->mapWithKeys(fn ($value, $saleId) => [(int) $saleId => max(0, (float) $value)])
            ->filter(fn ($value) => $value > 0);

        $requestedTotal = round($openingAllocation + (float) $saleAllocations->sum(), 2);
        if ($requestedTotal <= 0) {
            return back()->withInput()->with('error', 'Enter at least one payment amount before saving.');
        }
        $receivedAmount = round((float) $request->input('received_amount', 0), 2);
        if ($receivedAmount > 0 && abs($receivedAmount - $requestedTotal) > 0.009) {
            return back()->withInput()->with('error', 'Amount received must match the total allocated amount before saving.');
        }
        if ($openingAllocation > 0 && $this->paymentsRequireSaleId()) {
            return back()->withInput()->with('error', 'This database requires every payment to be linked to an invoice, so opening balance collection is not available here yet.');
        }

        $activeBranch = $this->getActiveBranchContext();
        $referenceBase = trim((string) $request->input('reference', ''));
        $paymentDate = $request->input('payment_date');
        $resolvedAccountId = $this->resolveReceivePaymentAccountId($request->input('payment_target'));

        try {
            DB::transaction(function () use (
                $customer,
                $saleAllocations,
                $openingAllocation,
                $request,
                $activeBranch,
                $referenceBase,
                $paymentDate,
                $resolvedAccountId
            ) {
                $salesQuery = Sale::query()
                    ->where('customer_id', $customer->id)
                    ->whereIn('id', $saleAllocations->keys())
                    ->lockForUpdate();
                $this->applySaleTenantScope($salesQuery);
                $this->applySaleBranchFilter($salesQuery, 'sales');
                $sales = $salesQuery->get()->keyBy('id');

                foreach ($saleAllocations as $saleId => $amountRequested) {
                    /** @var \App\Models\Sale|null $sale */
                    $sale = $sales->get($saleId);
                    if (!$sale) {
                        continue;
                    }

                    $remaining = round((float) ($sale->balance ?? 0), 2);
                    $amount = min($remaining, max(0, (float) $amountRequested));
                    if ($amount <= 0) {
                        continue;
                    }

                    $paymentPayload = [
                        'sale_id' => $sale->id,
                        'customer_id' => $customer->id,
                        'branch_id' => $sale->branch_id ?? $activeBranch['id'],
                        'branch_name' => $sale->branch_name ?? $sale->branch_label ?? $activeBranch['name'],
                        'reference' => $referenceBase !== '' ? $referenceBase : ($sale->invoice_no ?: ('SALE-' . $sale->id . '-PAY')),
                        'amount' => $amount,
                        'method' => $request->input('method') ?: 'Bank Transfer',
                        'status' => $remaining <= $amount ? 'Completed' : 'Pending',
                        'note' => $request->input('note') ?: 'Customer payment received.',
                        'created_by' => auth()->id(),
                    ];

                    if (Schema::hasColumn('payments', 'payment_account_id')) {
                        $paymentPayload['payment_account_id'] = $resolvedAccountId;
                    }
                    if (Schema::hasColumn('payments', 'company_id')) {
                        $paymentPayload['company_id'] = auth()->user()?->company_id ?? session('current_tenant_id');
                    }
                    if (Schema::hasColumn('payments', 'user_id')) {
                        $paymentPayload['user_id'] = auth()->id();
                    }

                    $payment = Payment::create($paymentPayload);
                    $payment->forceFill([
                        'created_at' => $paymentDate,
                        'updated_at' => $paymentDate,
                    ])->saveQuietly();

                    $newPaid = min((float) ($sale->total ?? 0), (float) ($sale->paid ?? $sale->amount_paid ?? 0) + $amount);
                    $newBalance = max(0, (float) ($sale->total ?? 0) - $newPaid);
                    $saleUpdate = [];
                    if (Schema::hasColumn('sales', 'paid')) {
                        $saleUpdate['paid'] = $newPaid;
                    }
                    if (Schema::hasColumn('sales', 'amount_paid')) {
                        $saleUpdate['amount_paid'] = $newPaid;
                    }
                    if (Schema::hasColumn('sales', 'balance')) {
                        $saleUpdate['balance'] = $newBalance;
                    }
                    if (Schema::hasColumn('sales', 'payment_status')) {
                        $saleUpdate['payment_status'] = $newBalance <= 0 ? 'paid' : 'partial';
                    }
                    if (Schema::hasColumn('sales', 'order_status')) {
                        $saleUpdate['order_status'] = $newBalance <= 0 ? 'completed' : ($sale->order_status ?? 'pending');
                    }
                    if (!empty($saleUpdate)) {
                        $sale->update($saleUpdate);
                    }

                    LedgerService::postSalePayment($sale->fresh(), $payment->fresh(), $payment->reference);
                }

                if ($openingAllocation > 0 && Schema::hasColumn('customers', 'balance')) {
                    $customer->refresh();
                    $openingOutstanding = round((float) ($customer->balance ?? 0), 2);
                    $openingAmount = min($openingOutstanding, $openingAllocation);

                    if ($openingAmount > 0) {
                        $paymentPayload = [
                            'sale_id' => null,
                            'customer_id' => $customer->id,
                            'branch_id' => $activeBranch['id'],
                            'branch_name' => $activeBranch['name'],
                            'reference' => $referenceBase !== '' ? $referenceBase : ('CUST-OPEN-' . $customer->id),
                            'amount' => $openingAmount,
                            'method' => $request->input('method') ?: 'Bank Transfer',
                            'status' => 'Completed',
                            'note' => $request->input('note') ?: 'Customer opening balance payment received.',
                            'created_by' => auth()->id(),
                        ];

                        if (Schema::hasColumn('payments', 'payment_account_id')) {
                            $paymentPayload['payment_account_id'] = $resolvedAccountId;
                        }
                        if (Schema::hasColumn('payments', 'company_id')) {
                            $paymentPayload['company_id'] = auth()->user()?->company_id ?? session('current_tenant_id');
                        }
                        if (Schema::hasColumn('payments', 'user_id')) {
                            $paymentPayload['user_id'] = auth()->id();
                        }

                        $payment = Payment::create($paymentPayload);
                        $payment->forceFill([
                            'created_at' => $paymentDate,
                            'updated_at' => $paymentDate,
                        ])->saveQuietly();

                        $customer->update([
                            'balance' => max(0, $openingOutstanding - $openingAmount),
                        ]);

                        LedgerService::postCustomerPayment($payment->fresh());
                    }
                }
            });
        } catch (\Throwable $exception) {
            return back()->withInput()->with('error', 'Customer payment could not be saved. ' . $exception->getMessage());
        }

        return redirect()
            ->route('customers.receive-payment', $customer->id)
            ->with('success', 'Payment received successfully and outstanding balances updated.');
    }

    public function activate($id)
    {
        $this->applyTenantScope(Customer::query())->findOrFail($id)->update(['status' => 'active']);
        return back()->with('success', 'Customer activated.');
    }

    public function deactivate($id)
    {
        $this->applyTenantScope(Customer::query())->findOrFail($id)->update(['status' => 'deactive']);
        return back()->with('success', 'Customer deactivated.');
    }

    /**
     * Remove the specified customer from storage.
     */
    public function destroy($id)
    {
        $customer = $this->applyTenantScope(Customer::query())->findOrFail($id);
        
        if ($customer->image) {
            Storage::disk('public')->delete($customer->image);
        }

        $customer->delete();
        return redirect()->route('customers.index')->with('success', 'Customer deleted successfully.');
    }

    public function export($id)
    {
        $customer = $this->applyTenantScope(Customer::query())->findOrFail($id);

        $filename = 'customer_' . $customer->id . '.csv';
        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => "attachment; filename={$filename}",
        ];

        $callback = function () use ($customer) {
            $out = fopen('php://output', 'w');
            fputcsv($out, ['ID', 'Customer Name', 'Email', 'Phone', 'Status', 'Balance', 'Created At']);
            fputcsv($out, [
                $customer->id,
                $customer->customer_name,
                $customer->email,
                $customer->phone,
                $customer->status,
                $customer->balance,
                optional($customer->created_at)->toDateTimeString(),
            ]);
            fclose($out);
        };

        return response()->stream($callback, 200, $headers);
    }

    public function apiIndex(Request $request)
    {
        $query = Customer::query()->select(['id', 'customer_name', 'email', 'phone', 'status', 'balance']);
        $this->applyTenantScope($query);

        if ($search = trim((string) $request->query('search', ''))) {
            $query->where(function ($q) use ($search) {
                $q->where('customer_name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%")
                  ->orWhere('phone', 'like', "%{$search}%");
            });
        }

        return response()->json($query->latest()->limit(100)->get());
    }

    public function downloadImportTemplate()
    {
        $headers = ['customer_name', 'email', 'phone', 'address', 'balance', 'credit_limit', 'status', 'notes'];
        $rows = [
            ['Adebayo Stores', 'accounts@adebayo.example', '08030000000', '12 Market Road', '25000', '100000', 'active', 'Opening credit balance'],
            ['Walk-in Customer', '', '', '', '0', '0', 'active', 'General retail customer'],
        ];

        $content = implode(',', $headers) . "\n";
        foreach ($rows as $row) {
            $content .= implode(',', array_map(function ($value) {
                $escaped = str_replace('"', '""', (string) $value);
                return '"' . $escaped . '"';
            }, $row)) . "\n";
        }

        return response($content, 200, [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename=customers-import-template.csv',
        ]);
    }

    public function import(Request $request)
    {
        \Log::info('Customer import request received.', [
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
            foreach (['customer_name'] as $requiredColumn) {
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

                if (($rowData['customer_name'] ?? '') === '') {
                    $skipped++;
                    $missingRequired++;
                    if (count($rowErrors) < 10) {
                        $rowErrors[] = 'Row ' . ($rowNumber + 1) . ': missing customer_name';
                    }
                    continue;
                }

                try {
                    $lookupEmail = $rowData['email'] ?? '';
                    $lookupPhone = $rowData['phone'] ?? '';

                    $customerQuery = Customer::query();
                    if ($companyId > 0 && Schema::hasColumn('customers', 'company_id')) {
                        $customerQuery->where('company_id', $companyId);
                    } elseif ($userId > 0 && Schema::hasColumn('customers', 'user_id')) {
                        $customerQuery->where('user_id', $userId);
                    }

                    if ($lookupEmail !== '' && Schema::hasColumn('customers', 'email')) {
                        $customerQuery->where(function ($query) use ($lookupEmail, $lookupPhone) {
                            $query->where('email', $lookupEmail);

                            if ($lookupPhone !== '' && Schema::hasColumn('customers', 'phone')) {
                                $query->orWhere('phone', $lookupPhone);
                            }
                        });
                    } elseif ($lookupPhone !== '' && Schema::hasColumn('customers', 'phone')) {
                        $customerQuery->where('phone', $lookupPhone);
                    } else {
                        $customerQuery->where('customer_name', $rowData['customer_name']);
                    }

                    $customer = $customerQuery->first();
                    $isNew = !$customer;
                    if ($customer && !$updateExisting) {
                        $skipped++;
                        $duplicates++;
                        if (count($rowErrors) < 10) {
                            $rowErrors[] = 'Row ' . ($rowNumber + 1) . ': duplicate customer detected';
                        }
                        continue;
                    }
                    $customer = $customer ?: new Customer();

                    $payload = $this->sanitizeForCustomerColumns([
                        'customer_name' => $rowData['customer_name'],
                        'email' => $lookupEmail !== '' ? $lookupEmail : null,
                        'phone' => $lookupPhone !== '' ? $lookupPhone : null,
                        'address' => $rowData['address'] ?? null,
                        'balance' => is_numeric($rowData['balance'] ?? null) ? (float) $rowData['balance'] : 0,
                        'credit_limit' => is_numeric($rowData['credit_limit'] ?? null) ? (float) $rowData['credit_limit'] : 0,
                        'status' => in_array(strtolower((string) ($rowData['status'] ?? 'active')), ['active', 'deactive'], true)
                            ? strtolower((string) $rowData['status'])
                            : 'active',
                        'notes' => $rowData['notes'] ?? null,
                        'company_id' => $companyId > 0 ? $companyId : null,
                        'user_id' => $userId > 0 ? $userId : null,
                    ]);

                    $customer->fill($payload);
                    $customer->save();

                    if ($isNew) {
                        $created++;
                    } else {
                        $updated++;
                        $updatedExisting++;
                    }
                } catch (\Throwable $rowException) {
                    \Log::warning('Customer import row skipped.', [
                        'row' => $rowNumber + 1,
                        'customer_name' => $rowData['customer_name'] ?? null,
                        'error' => $rowException->getMessage(),
                    ]);
                    $skipped++;
                }
            }

            $summary = "Customer import completed. Created: {$created}, Updated: {$updated}, Skipped: {$skipped}.";
            if ($updatedExisting > 0) {
                $summary .= " Updated existing: {$updatedExisting}.";
            }
            if ($duplicates > 0 || $missingRequired > 0) {
                $summary .= " Duplicates skipped: {$duplicates}, Missing required: {$missingRequired}.";
            }

            $redirect = redirect()->route('customers.index')->with('success', $summary);
            if (!empty($rowErrors)) {
                $redirect->with('warning', 'Some rows were skipped: ' . implode(' | ', $rowErrors));
            }
            return $redirect;
        } catch (\Throwable $exception) {
            \Log::error('Customer import failed.', [
                'user_id' => auth()->id(),
                'error' => $exception->getMessage(),
            ]);

            return back()->withInput()->with(
                'error',
                'The customer import could not be completed. Please confirm the spreadsheet columns and try again.'
            );
        }
    }

    private function sanitizeForCustomerColumns(array $data): array
    {
        $allowed = array_flip(Schema::getColumnListing('customers'));
        return array_intersect_key($data, $allowed);
    }

    private function normalizeImportHeaderCell($value): string
    {
        $header = strtolower(trim((string) $value));
        return preg_replace('/^\x{FEFF}/u', '', $header) ?? $header;
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
            foreach ($spreadsheet->getActiveSheet()->getRowIterator() as $row) {
                $cells = [];
                $cellIterator = $row->getCellIterator();
                $cellIterator->setIterateOnlyExistingCells(false);

                foreach ($cellIterator as $cell) {
                    $cells[] = $cell?->getFormattedValue();
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
        $firstLine = fgets($handle);
        if ($firstLine === false) {
            rewind($handle);
            return ',';
        }

        $firstLine = $this->normalizeCsvLine($firstLine);

        $candidates = [',', ';', "\t", '|'];
        $bestDelimiter = ',';
        $bestScore = -1;

        foreach ($candidates as $candidate) {
            $score = substr_count($firstLine, $candidate);
            if ($score > $bestScore) {
                $bestScore = $score;
                $bestDelimiter = $candidate;
            }
        }

        rewind($handle);

        return $bestDelimiter;
    }

    private function normalizeCsvLine(string $line): string
    {
        if (str_starts_with($line, "\xFF\xFE")) {
            $line = mb_convert_encoding(substr($line, 2), 'UTF-8', 'UTF-16LE');
        } elseif (str_starts_with($line, "\xFE\xFF")) {
            $line = mb_convert_encoding(substr($line, 2), 'UTF-8', 'UTF-16BE');
        } elseif (str_contains($line, "\x00")) {
            $line = mb_convert_encoding($line, 'UTF-8', 'UTF-16LE');
        }

        $line = preg_replace('/^\x{FEFF}/u', '', $line) ?? $line;

        return trim($line);
    }

    private function expandEmbeddedDelimitedRow(array $row): array
    {
        if (count($row) !== 1) {
            return $row;
        }

        $cell = trim((string) ($row[0] ?? ''));
        if ($cell === '') {
            return $row;
        }

        $delimiters = [',', ';', "\t", '|'];
        $bestDelimiter = null;
        $bestScore = 0;

        foreach ($delimiters as $delimiter) {
            $score = substr_count($cell, $delimiter);
            if ($score > $bestScore) {
                $bestScore = $score;
                $bestDelimiter = $delimiter;
            }
        }

        if ($bestDelimiter === null || $bestScore === 0) {
            return $row;
        }

        $trimmedCell = preg_replace('/^"(.*)"$/s', '$1', $cell) ?? $cell;
        $expanded = str_getcsv($trimmedCell, $bestDelimiter);

        return is_array($expanded) && count($expanded) > 1 ? $expanded : $row;
    }
}
