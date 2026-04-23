<?php

namespace App\Http\Controllers;

use App\Models\Account;
use App\Models\Bank;
use App\Models\Customer;
use App\Models\Payment;
use App\Models\Sale;
use App\Models\Transaction;
use App\Support\LedgerService;
use App\Traits\HasUniqueReceiptNumber;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Storage;
use PhpOffice\PhpSpreadsheet\IOFactory;

class CustomerController extends Controller
{
    use HasUniqueReceiptNumber;

    private function applyTenantScope($query)
    {
        $companyId = (int) (auth()->user()?->company_id ?? session('current_tenant_id') ?? 0);
        $userId = (int) (auth()->id() ?? 0);

        if ($companyId > 0 && Schema::hasColumn('customers', 'company_id')) {
            $query->where('company_id', $companyId);
        } elseif ($userId > 0 && Schema::hasColumn('customers', 'user_id')) {
            $query->where('user_id', $userId);
        }

        return $query;
    }

    /**
     * Display a listing of customers.
     */
    public function index(Request $request)
    {
        $customerQuery = $this->buildCustomerQuery($request);
        $summaryCustomers = (clone $customerQuery)->get();

        $customers = $customerQuery
            ->paginate(20)
            ->withQueryString();

        $customers->getCollection()->transform(function ($customer) {
            $customer->computed_balance = (float) ($customer->balance ?? 0) + (float) ($customer->sales_balance_sum ?? 0);
            return $customer;
        });

        $summary = $this->buildCustomerListSummary($summaryCustomers);
        $reportMeta = $this->buildCustomerListReportMeta($request, $summaryCustomers);

        return view('Customers.customers', compact('customers', 'summary', 'reportMeta'));
    }

    /**
     * Show the form for creating a new customer.
     */
    public function create()
    {
        $availableBranches = $this->getAvailableBranches();
        return view('Customers.add-customer', compact('availableBranches'));
    }

    /**
     * Store a newly created customer in storage.
     */
    public function store(Request $request)
    {
        $request->validate([
            'customer_name' => 'required|string|max:191',
            'email'         => 'nullable|email|max:191',
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
        $selectedBranchId = trim((string) $request->input('branch_id', ''));
        $branchMatch = $selectedBranchId !== ''
            ? collect($this->getAvailableBranches())->firstWhere('id', $selectedBranchId)
            : null;
        $resolvedBranch = $branchMatch ?? $this->getActiveBranchContext();
        if (Schema::hasColumn('customers', 'branch_id')) {
            $data['branch_id'] = $resolvedBranch['id'] ?? null;
        }
        if (Schema::hasColumn('customers', 'branch_name')) {
            $data['branch_name'] = $resolvedBranch['name'] ?? null;
        }

        $customer = Customer::create($data);

        // Post opening balance journal entry so it reflects on the balance sheet
        $openingBalance = (float) ($data['balance'] ?? 0);
        if ($openingBalance > 0) {
            $this->postCustomerOpeningBalanceJournal($customer, $openingBalance);
        }

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
            'email'         => 'nullable|email|max:191',
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

        // Sync opening balance journal entries so the balance sheet always stays current
        $this->reverseCustomerOpeningBalanceJournal($customer->id);
        $freshBalance = (float) ($customer->fresh()->balance ?? 0);
        if ($freshBalance > 0) {
            $this->postCustomerOpeningBalanceJournal($customer->fresh(), $freshBalance);
        }

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
        $customerQuery = $this->buildCustomerQuery($request, 'active');
        $summaryCustomers = (clone $customerQuery)->get();

        $customers = $customerQuery
            ->paginate(20)
            ->withQueryString();

        $customers->getCollection()->transform(function ($customer) {
            $customer->computed_balance = (float) ($customer->balance ?? 0) + (float) ($customer->sales_balance_sum ?? 0);
            return $customer;
        });

        $summary = $this->buildCustomerListSummary($summaryCustomers);
        $reportMeta = $this->buildCustomerListReportMeta($request, $summaryCustomers, 'Active Customers');

        return view('Customers.customers', compact('customers', 'summary', 'reportMeta'));
    }

    public function deactiveView(Request $request)
    {
        $customerQuery = $this->buildCustomerQuery($request, 'deactive');
        $summaryCustomers = (clone $customerQuery)->get();

        $customers = $customerQuery
            ->paginate(20)
            ->withQueryString();

        $customers->getCollection()->transform(function ($customer) {
            $customer->computed_balance = (float) ($customer->balance ?? 0) + (float) ($customer->sales_balance_sum ?? 0);
            return $customer;
        });

        $summary = $this->buildCustomerListSummary($summaryCustomers);
        $reportMeta = $this->buildCustomerListReportMeta($request, $summaryCustomers, 'Inactive Customers');

        return view('Customers.customers', compact('customers', 'summary', 'reportMeta'));
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

        $this->applyCustomerCreatedAtFilter($query, $request);

        $this->withSalesBalances($query);

        return $query;
    }

    private function applyCustomerCreatedAtFilter($query, Request $request): void
    {
        [$rangeStart, $rangeEnd] = $this->resolveCustomerQuickRange($request->input('quick_range'));

        $startDate = $request->filled('start_date')
            ? Carbon::parse($request->input('start_date'))->startOfDay()
            : $rangeStart;
        $endDate = $request->filled('end_date')
            ? Carbon::parse($request->input('end_date'))->endOfDay()
            : $rangeEnd;

        if ($startDate) {
            $query->where('created_at', '>=', $startDate);
        }

        if ($endDate) {
            $query->where('created_at', '<=', $endDate);
        }
    }

    private function resolveCustomerQuickRange(?string $quickRange): array
    {
        $quickRange = strtolower(trim((string) $quickRange));
        $now = now();

        return match ($quickRange) {
            'today' => [$now->copy()->startOfDay(), $now->copy()->endOfDay()],
            'yesterday' => [$now->copy()->subDay()->startOfDay(), $now->copy()->subDay()->endOfDay()],
            'this_week' => [$now->copy()->startOfWeek(), $now->copy()->endOfWeek()],
            'last_week' => [$now->copy()->subWeek()->startOfWeek(), $now->copy()->subWeek()->endOfWeek()],
            'last_7_days' => [$now->copy()->subDays(6)->startOfDay(), $now->copy()->endOfDay()],
            'last_30_days' => [$now->copy()->subDays(29)->startOfDay(), $now->copy()->endOfDay()],
            'this_month' => [$now->copy()->startOfMonth(), $now->copy()->endOfMonth()],
            'last_month' => [$now->copy()->subMonthNoOverflow()->startOfMonth(), $now->copy()->subMonthNoOverflow()->endOfMonth()],
            'this_year' => [$now->copy()->startOfYear(), $now->copy()->endOfYear()],
            'last_year' => [$now->copy()->subYear()->startOfYear(), $now->copy()->subYear()->endOfYear()],
            default => [null, null],
        };
    }

    private function buildCustomerListSummary($customers): array
    {
        $normalized = collect($customers)->map(function ($customer) {
            $computedBalance = (float) ($customer->balance ?? 0) + (float) ($customer->sales_balance_sum ?? 0);
            $customer->computed_balance = $computedBalance;

            return $customer;
        });

        return [
            'total_customers' => $normalized->count(),
            'active_customers' => $normalized->filter(fn ($customer) => strtolower((string) ($customer->status ?? '')) === 'active')->count(),
            'contacts_on_file' => $normalized->filter(fn ($customer) => filled($customer->phone) || filled($customer->email))->count(),
            'total_opening_balance' => (float) $normalized->sum(fn ($customer) => (float) ($customer->balance ?? 0)),
            'total_invoice_due' => (float) $normalized->sum(fn ($customer) => (float) ($customer->sales_balance_sum ?? 0)),
            'total_receivables' => (float) $normalized->sum(fn ($customer) => (float) ($customer->computed_balance ?? 0)),
        ];
    }

    private function buildCustomerListReportMeta(Request $request, $customers, string $title = 'Customers'): array
    {
        [$rangeStart, $rangeEnd] = $this->resolveCustomerQuickRange($request->input('quick_range'));

        $periodFrom = $request->filled('start_date')
            ? Carbon::parse($request->input('start_date'))->startOfDay()
            : $rangeStart;
        $periodTo = $request->filled('end_date')
            ? Carbon::parse($request->input('end_date'))->endOfDay()
            : $rangeEnd;

        $quickRangeLabels = [
            'today' => 'Today',
            'yesterday' => 'Yesterday',
            'this_week' => 'This Week',
            'last_week' => 'Last Week',
            'last_7_days' => 'Last 7 Days',
            'last_30_days' => 'Last 30 Days',
            'this_month' => 'This Month',
            'last_month' => 'Last Month',
            'this_year' => 'This Year',
            'last_year' => 'Last Year',
        ];

        $quickRange = strtolower(trim((string) $request->input('quick_range')));
        $branch = $this->getActiveBranchContext();
        $latestCustomer = collect($customers)->sortByDesc('created_at')->first();

        return [
            'title' => $title,
            'generated_at' => now(),
            'period_from' => $periodFrom,
            'period_to' => $periodTo,
            'period_label' => $periodFrom && $periodTo
                ? $periodFrom->format('d M Y') . ' to ' . $periodTo->format('d M Y')
                : ($quickRangeLabels[$quickRange] ?? 'All customer records'),
            'branch_name' => $branch['name'] ?? 'Workspace Default',
            'status_scope' => $request->filled('status') ? ucfirst((string) $request->input('status')) : 'All statuses',
            'latest_customer_date' => $latestCustomer?->created_at,
        ];
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

    private function getAvailableBranches(): array
    {
        $companyId = (int) (auth()->user()?->company_id ?? 0);
        if ($companyId <= 0) {
            return [];
        }
        $key = 'branches_json_company_' . $companyId;
        $raw = (string) (DB::table('settings')->where('key', $key)->value('value') ?? '');
        $decoded = json_decode($raw, true);

        return collect(is_array($decoded) ? $decoded : [])
            ->filter(fn ($branch) => !empty($branch['id']) && !empty($branch['name']))
            ->values()
            ->all();
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

    private function excludeOpeningBalanceSales($query, string $salesTable = 'sales')
    {
        if (!Schema::hasColumn('sales', 'invoice_no')) {
            return $query;
        }

        return $query->where(function ($sub) use ($salesTable) {
            $sub->whereNull("{$salesTable}.invoice_no")
                ->orWhere("{$salesTable}.invoice_no", 'not like', 'OPENING-BAL-%');
        });
    }

    private function withSalesBalances($query): void
    {
        if (!Schema::hasTable('sales') || !Schema::hasColumn('sales', 'customer_id')) {
            return;
        }

        if (Schema::hasColumn('sales', 'balance')) {
            $query->withSum(['sales as sales_balance_sum' => function ($saleQuery) {
                $saleQuery->where('balance', '>', 0);
                $this->excludeOpeningBalanceSales($saleQuery, 'sales');
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
        $this->excludeOpeningBalanceSales($query, 'sales');
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
            $this->excludeOpeningBalanceSales($salesQuery, 'sales');
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

    private function resolveCustomerOpeningBalanceSnapshot(Customer $customer): array
    {
        $openingReference = 'OPENING-BAL-' . $customer->id;
        $openingSaleQuery = Sale::query()
            ->where('customer_id', $customer->id)
            ->where('invoice_no', $openingReference);
        $this->applySaleTenantScope($openingSaleQuery);
        $this->applySaleBranchFilter($openingSaleQuery, 'sales');
        $openingSale = $openingSaleQuery->latest('id')->first();

        $openingPaymentsQuery = Payment::query()->where('customer_id', $customer->id);
        $this->applyTenantScope($openingPaymentsQuery);
        if (Schema::hasTable('payments')) {
            $branchId = trim((string) ($this->getActiveBranchContext()['id'] ?? ''));
            $branchName = trim((string) ($this->getActiveBranchContext()['name'] ?? ''));
            if ($branchId !== '' || $branchName !== '') {
                $openingPaymentsQuery->where(function ($sub) use ($branchId, $branchName) {
                    if ($branchId !== '' && Schema::hasColumn('payments', 'branch_id')) {
                        $sub->where('branch_id', $branchId);
                    }
                    if ($branchName !== '' && Schema::hasColumn('payments', 'branch_name')) {
                        $sub->orWhere('branch_name', $branchName);
                    }
                });
            }
        }

        $openingPayments = $openingSale
            ? (clone $openingPaymentsQuery)->where('sale_id', $openingSale->id)->get()
            : collect();
        $openingPaid = round((float) $openingPayments->sum('amount'), 2);

        $openingOriginal = $openingSale
            ? max(
                (float) ($openingSale->total ?? 0),
                (float) ($openingSale->paid ?? $openingSale->amount_paid ?? 0) + (float) ($openingSale->balance ?? 0),
                (float) ($customer->balance ?? 0) + $openingPaid
            )
            : max(0, (float) ($customer->balance ?? 0) + $openingPaid);

        return [
            'sale' => $openingSale,
            'payments' => $openingPayments,
            'original' => round($openingOriginal, 2),
            'paid' => $openingPaid,
            'due' => round(max(0, $openingOriginal - $openingPaid), 2),
        ];
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
        $openingReference = 'OPENING-BAL-' . $customer->id;

        $outstandingSalesQuery = Sale::query()
            ->where('customer_id', $customer->id)
            ->where('balance', '>', 0)
            ->where(function ($query) use ($openingReference) {
                $query->whereNull('invoice_no')
                    ->orWhere('invoice_no', '!=', $openingReference);
            })
            ->orderBy('order_date')
            ->orderBy('id');
        $this->applySaleTenantScope($outstandingSalesQuery);
        $this->applySaleBranchFilter($outstandingSalesQuery, 'sales');
        $outstandingSales = $outstandingSalesQuery->get();

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
        $openingSnapshot = $this->resolveCustomerOpeningBalanceSnapshot($customer);
        $outstandingOpeningBalance = (float) ($openingSnapshot['due'] ?? 0);
        $outstandingInvoicesTotal = (float) $outstandingSales->sum('balance');
        $supportsStandalonePayments = !$this->paymentsRequireSaleId();
        $activeBranch = $this->getActiveBranchContext();
        $paymentHistory = Payment::with(['account', 'sale', 'creator'])
            ->where('customer_id', $customer->id)
            ->orderBy('created_at')
            ->get();

        $paymentTimeline = collect();
        $runningOpeningBalance = (float) ($openingSnapshot['original'] ?? 0);
        if ($runningOpeningBalance > 0) {
            $openingDate = $customer->opening_balance_date
                ? \Illuminate\Support\Carbon::parse($customer->opening_balance_date)->startOfDay()
                : ($openingSnapshot['sale']?->order_date
                    ? \Illuminate\Support\Carbon::parse($openingSnapshot['sale']->order_date)->startOfDay()
                    : ($openingSnapshot['sale']?->created_at ?: $customer->created_at));

            $paymentTimeline->push([
                'date' => $openingDate,
                'reference' => 'OPENING',
                'payment_id' => $openingSnapshot['sale']?->invoice_no ?: 'OPENING-BAL-' . $customer->id,
                'type' => 'Opening Balance',
                'entry_icon' => 'fa-flag-checkered',
                'method' => 'Opening Entry',
                'status' => $runningOpeningBalance > 0 ? 'Outstanding' : 'Cleared',
                'status_class' => $runningOpeningBalance > 0 ? 'warning' : 'success',
                'account' => 'Customer Opening Balance',
                'branch' => $openingSnapshot['sale']?->branch_name ?: ($activeBranch['name'] ?? 'Workspace Default'),
                'note' => 'Opening balance carried into customer ledger.',
                'source_label' => 'Opening Entry',
                'invoice_amount' => $runningOpeningBalance,
                'payment_amount' => 0.0,
                'amount' => $runningOpeningBalance,
                'running_balance' => $runningOpeningBalance,
                'created_by' => 'System',
            ]);
        }

        foreach ($paymentHistory as $payment) {
            $isOpeningPayment = !empty($openingSnapshot['sale']) && (int) $payment->sale_id === (int) $openingSnapshot['sale']->id;
            if ($isOpeningPayment) {
                $runningOpeningBalance = max(0, $runningOpeningBalance - (float) $payment->amount);
            }

            $paymentTimeline->push([
                'date' => $payment->created_at,
                'reference' => $payment->reference ?: ($payment->payment_id ?: ('PAY-' . $payment->id)),
                'payment_id' => $payment->payment_id ?: ('PAY-' . $payment->id),
                'type' => $isOpeningPayment ? 'Opening Balance / Payment' : 'Payment',
                'entry_icon' => $isOpeningPayment ? 'fa-wallet' : 'fa-money-bill-wave',
                'method' => $payment->method ?: 'Unspecified',
                'status' => $payment->status ?: 'Completed',
                'status_class' => match (strtolower((string) ($payment->status ?? 'completed'))) {
                    'completed', 'paid', 'success' => 'success',
                    'pending', 'partial' => 'warning',
                    'failed', 'cancelled' => 'danger',
                    default => 'info',
                },
                'account' => $payment->account?->name ?: 'Not assigned',
                'branch' => $payment->branch_name ?: ($payment->sale?->branch_name ?: ($activeBranch['name'] ?? 'Workspace Default')),
                'note' => $payment->note ?: ($isOpeningPayment ? 'Opening balance payment received.' : 'Customer payment received.'),
                'source_label' => $isOpeningPayment ? 'Opening Balance Allocation' : (($payment->sale?->invoice_no) ?: 'Direct Customer Payment'),
                'invoice_amount' => 0.0,
                'payment_amount' => (float) $payment->amount,
                'amount' => (float) $payment->amount,
                'running_balance' => $isOpeningPayment ? $runningOpeningBalance : null,
                'created_by' => $payment->creator?->name ?: 'System',
            ]);
        }

        $timelineDates = collect([
            $paymentTimeline->first()['date'] ?? null,
            $paymentTimeline->last()['date'] ?? null,
        ])->filter();

        $reportMeta = [
            'generated_at' => now(),
            'period_from' => $timelineDates->isNotEmpty() ? \Illuminate\Support\Carbon::parse($paymentTimeline->min('date'))->copy() : now(),
            'period_to' => $timelineDates->isNotEmpty() ? \Illuminate\Support\Carbon::parse($paymentTimeline->max('date'))->copy() : now(),
            'branch_name' => $activeBranch['name'] ?? 'Workspace Default',
            'customer_code' => 'CL-' . str_pad((string) $customer->id, 4, '0', STR_PAD_LEFT),
            'open_invoice_count' => $outstandingSales->count(),
            'history_count' => $paymentTimeline->count(),
            'latest_account' => $paymentHistory->last()?->account?->name ?: 'Not assigned',
            'latest_method' => $paymentHistory->last()?->method ?: 'Not specified',
        ];

        return view('Customers.receive-payment', compact(
            'customer',
            'outstandingSales',
            'paymentDestinations',
            'outstandingOpeningBalance',
            'outstandingInvoicesTotal',
            'supportsStandalonePayments',
            'paymentHistory',
            'openingSnapshot',
            'paymentTimeline',
            'reportMeta'
        ));
    }

    public function storeReceivedPayment(Request $request, $id)
    {
        $customer = $this->applyTenantScope(Customer::query())->findOrFail($id);

        $request->validate([
            'payment_date' => 'required|date',
            'received_amount' => 'required|numeric|min:0.01',
            'payment_target' => 'nullable|string|max:50',
            'reference' => 'nullable|string|max:191',
            'method' => 'nullable|string|max:100',
            'note' => 'nullable|string|max:1000',
        ]);

        $receivedAmount = round((float) $request->input('received_amount', 0), 2);

        $activeBranch = $this->getActiveBranchContext();
        $referenceBase = trim((string) $request->input('reference', ''));
        $paymentDate = $this->normalizePaymentTimestamp($request->input('payment_date'));
        $resolvedAccountId = $this->resolveReceivePaymentAccountId($request->input('payment_target'));

        try {
            DB::transaction(function () use (
                $customer,
                $receivedAmount,
                $request,
                $activeBranch,
                $referenceBase,
                $paymentDate,
                $resolvedAccountId
            ) {
                $customer = Customer::query()->lockForUpdate()->findOrFail($customer->id);

                // Get outstanding sales ordered by date
                $outstandingSalesQuery = Sale::query()
                    ->where('customer_id', $customer->id)
                    ->where('balance', '>', 0)
                    ->where(function ($query) use ($customer) {
                        $openingReference = 'OPENING-BAL-' . $customer->id;
                        $query->whereNull('invoice_no')
                            ->orWhere('invoice_no', '!=', $openingReference);
                    })
                    ->orderBy('order_date')
                    ->orderBy('id');
                $this->applySaleTenantScope($outstandingSalesQuery);
                $this->applySaleBranchFilter($outstandingSalesQuery, 'sales');
                $outstandingSales = $outstandingSalesQuery->lockForUpdate()->get();

                $remainingAmount = $receivedAmount;

                foreach ($outstandingSales as $sale) {
                    if ($remainingAmount <= 0) {
                        break;
                    }

                    $remaining = round((float) ($sale->balance ?? 0), 2);
                    $amount = min($remaining, $remainingAmount);
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
                        'receipt_no' => $this->generatePaymentReceiptNo(),
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

                    $remainingAmount -= $amount;
                }

                $openingOutstanding = round((float) ($customer->balance ?? 0), 2);
                if ($remainingAmount > 0 && $openingOutstanding > 0) {
                    $openingPaymentAmount = min($openingOutstanding, $remainingAmount);
                    $openingSale = $this->createOrRefreshOpeningBalanceSale(
                        $customer,
                        $openingOutstanding,
                        $activeBranch,
                        $paymentDate
                    );

                    $paymentPayload = [
                        'sale_id' => $openingSale?->id,
                        'customer_id' => $customer->id,
                        'branch_id' => $openingSale?->branch_id ?? $activeBranch['id'],
                        'branch_name' => $openingSale?->branch_name ?? $activeBranch['name'],
                        'reference' => $referenceBase !== '' ? $referenceBase : ('OPENING-BAL-' . $customer->id),
                        'amount' => $openingPaymentAmount,
                        'receipt_no' => $this->generatePaymentReceiptNo(),
                        'method' => $request->input('method') ?: 'Bank Transfer',
                        'status' => $openingOutstanding <= $openingPaymentAmount ? 'Completed' : 'Pending',
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

                    $openingPayment = Payment::create($paymentPayload);
                    $openingPayment->forceFill([
                        'created_at' => $paymentDate,
                        'updated_at' => $paymentDate,
                    ])->saveQuietly();

                    $customer->update([
                        'balance' => max(0, $openingOutstanding - $openingPaymentAmount),
                    ]);

                    if ($openingSale) {
                        $openingSale = $this->createOrRefreshOpeningBalanceSale(
                            $customer->fresh(),
                            $openingOutstanding,
                            $activeBranch,
                            $paymentDate
                        );
                        $openingPayment->sale_id = $openingSale?->id;
                        $openingPayment->saveQuietly();
                    }

                    LedgerService::postCustomerPayment($openingPayment->fresh());

                    $remainingAmount -= $openingPaymentAmount;
                }

            });
        } catch (\Throwable $exception) {
            return back()->withInput()->with('error', 'Customer payment could not be saved. ' . $exception->getMessage());
        }

        return redirect()
            ->route('customers.receive-payment', $customer->id)
            ->with('success', 'Payment received successfully and outstanding balances updated.');
    }

    private function createOrRefreshOpeningBalanceSale(Customer $customer, float $openingOutstanding, array $activeBranch, string $paymentDate): ?Sale
    {
        if (!Schema::hasTable('sales')) {
            return null;
        }

        $openingReference = 'OPENING-BAL-' . $customer->id;
        $query = method_exists(Sale::class, 'withTrashed')
            ? Sale::withTrashed()
            : Sale::query();

        $query->where('invoice_no', $openingReference);
        $this->applySaleTenantScope($query);
        $existing = $query->latest('id')->first();

        if ($existing && method_exists($existing, 'trashed') && $existing->trashed()) {
            $existing->restore();
        }

        $existingPaid = (float) ($existing?->paid ?? $existing?->amount_paid ?? 0);
        $openingSaleTotal = $existing
            ? max((float) ($existing->total ?? 0), $openingOutstanding + $existingPaid)
            : $openingOutstanding;

        $payload = [];
        if (Schema::hasColumn('sales', 'company_id')) {
            $payload['company_id'] = auth()->user()?->company_id ?? session('current_tenant_id');
        }
        if (Schema::hasColumn('sales', 'branch_id')) {
            $payload['branch_id'] = $activeBranch['id'];
        }
        if (Schema::hasColumn('sales', 'branch_name')) {
            $payload['branch_name'] = $activeBranch['name'];
        }
        if (Schema::hasColumn('sales', 'customer_id')) {
            $payload['customer_id'] = $customer->id;
        }
        if (Schema::hasColumn('sales', 'customer_name')) {
            $payload['customer_name'] = $customer->customer_name;
        }
        if (Schema::hasColumn('sales', 'user_id')) {
            $payload['user_id'] = auth()->id();
        }
        if (Schema::hasColumn('sales', 'invoice_no')) {
            $payload['invoice_no'] = $openingReference;
        }
        if (Schema::hasColumn('sales', 'receipt_no')) {
            $payload['receipt_no'] = $this->generateSaleReceiptNo();
        }
        if (Schema::hasColumn('sales', 'order_number')) {
            $payload['order_number'] = 'OPENING-' . $customer->id;
        }
        if (Schema::hasColumn('sales', 'order_date')) {
            $payload['order_date'] = $paymentDate;
        }
        if (Schema::hasColumn('sales', 'subtotal')) {
            $payload['subtotal'] = $openingSaleTotal;
        }
        if (Schema::hasColumn('sales', 'total')) {
            $payload['total'] = $openingSaleTotal;
        }
        if (Schema::hasColumn('sales', 'paid')) {
            $payload['paid'] = max(0, $openingSaleTotal - (float) ($customer->balance ?? 0));
        }
        if (Schema::hasColumn('sales', 'amount_paid')) {
            $payload['amount_paid'] = max(0, $openingSaleTotal - (float) ($customer->balance ?? 0));
        }
        if (Schema::hasColumn('sales', 'balance')) {
            $payload['balance'] = (float) ($customer->balance ?? 0);
        }
        if (Schema::hasColumn('sales', 'payment_method')) {
            $payload['payment_method'] = 'Opening Balance';
        }
        if (Schema::hasColumn('sales', 'payment_status')) {
            $payload['payment_status'] = (float) ($customer->balance ?? 0) <= 0 ? 'paid' : 'partial';
        }
        if (Schema::hasColumn('sales', 'order_status')) {
            $payload['order_status'] = (float) ($customer->balance ?? 0) <= 0 ? 'completed' : 'pending';
        }

        if ($existing) {
            $existing->update($payload);
            return $existing->fresh();
        }

        return Sale::create($payload);
    }

    private function normalizePaymentTimestamp(?string $paymentDate): string
    {
        $now = now();
        $raw = trim((string) $paymentDate);

        if ($raw === '') {
            return $now->toDateTimeString();
        }

        $parsed = \Illuminate\Support\Carbon::parse($raw);

        // When a date-only value is submitted, keep today's real clock time
        // so statement ordering reflects when the entry was actually made.
        if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $raw) === 1) {
            $parsed->setTime($now->hour, $now->minute, $now->second, $now->microsecond);
        }

        return $parsed->toDateTimeString();
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

        // Remove any CUST-OB-* journal entries for this customer so orphaned
        // transactions don't distort the balance sheet or trial balance.
        $this->reverseCustomerOpeningBalanceJournal($customer->id);

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
        $headers = ['customer_name', 'email', 'phone', 'address', 'balance', 'credit_limit', 'status', 'notes', 'branch_name'];
        $rows = [
            ['Adebayo Stores', 'accounts@adebayo.example', '08030000000', '12 Market Road', '25000', '100000', 'active', 'Opening credit balance', ''],
            ['Walk-in Customer', '', '', '', '0', '0', 'active', 'General retail customer', ''],
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
            $headerRowNumber = null;

            foreach ($this->spreadsheetRowIterator($file) as $rowNumber => $row) {
                $nonEmpty = array_filter($row, fn ($v) => trim((string) $v) !== '');
                if (empty($nonEmpty)) {
                    continue;
                }

                $normalizedRow = array_map(fn ($value) => $this->normalizeImportHeaderCell($value), $row);
                if ($this->looksLikeCustomerImportHeader($normalizedRow)) {
                    $header = $row;
                    $headerRowNumber = $rowNumber;
                    break;
                }
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
            $availableBranches = collect($this->getAvailableBranches());
            $activeBranch = $this->getActiveBranchContext();

            foreach ($this->spreadsheetRowIterator($file) as $rowNumber => $row) {
                if ($rowNumber <= (int) $headerRowNumber) {
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

                    $csvBranchName = trim((string) ($rowData['branch_name'] ?? ''));
                    $branchMatch = $csvBranchName !== ''
                        ? $availableBranches->first(fn ($b) => strtolower(trim((string) ($b['name'] ?? ''))) === strtolower($csvBranchName))
                        : null;
                    $rowBranchId = $branchMatch ? ($branchMatch['id'] ?? null) : ($activeBranch['id'] ?? null);
                    $rowBranchName = $branchMatch ? ($branchMatch['name'] ?? null) : ($activeBranch['name'] ?? null);
                    $payload = $this->sanitizeForCustomerColumns([
                        'customer_name' => $rowData['customer_name'],
                        'email' => $lookupEmail !== '' ? $lookupEmail : null,
                        'phone' => $lookupPhone !== '' ? $lookupPhone : null,
                        'address' => $rowData['address'] ?? null,
                        'balance' => is_numeric($rowData['balance'] ?? null) ? (float) $rowData['balance'] : 0,
                        'opening_balance_date' => is_numeric($rowData['balance'] ?? null) && (float) $rowData['balance'] > 0
                            ? ($rowData['opening_balance_date'] ?? now()->toDateString())
                            : null,
                        'credit_limit' => is_numeric($rowData['credit_limit'] ?? null) ? (float) $rowData['credit_limit'] : 0,
                        'status' => in_array(strtolower((string) ($rowData['status'] ?? 'active')), ['active', 'deactive'], true)
                            ? strtolower((string) $rowData['status'])
                            : 'active',
                        'notes' => $rowData['notes'] ?? null,
                        'company_id' => $companyId > 0 ? $companyId : null,
                        'user_id' => $userId > 0 ? $userId : null,
                        'branch_id' => $rowBranchId,
                        'branch_name' => $rowBranchName,
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

    /**
     * Post double-entry journal for a customer opening balance.
     * DR Accounts Receivable  (customer owes us → asset increases)
     * CR Opening Balance Equity (balancing equity entry)
     */
    private function postCustomerOpeningBalanceJournal(Customer $customer, float $balance): void
    {
        if ($balance <= 0 || !Schema::hasTable('accounts') || !Schema::hasTable('transactions')) {
            return;
        }

        $companyId   = (int) ($customer->company_id ?? auth()->user()?->company_id ?? session('current_tenant_id') ?? 0);
        $userId      = (int) ($customer->user_id ?? auth()->id() ?? 0);
        $reference   = 'CUST-OB-' . $customer->id;
        $txnDate     = $customer->opening_balance_date
            ? Carbon::parse($customer->opening_balance_date)->toDateString()
            : today()->toDateString();
        $description = 'Opening balance: ' . ($customer->customer_name ?? 'Customer #' . $customer->id);

        $arAccount  = $this->getOrCreateChartAccountForCustomer(
            'Accounts Receivable', '1100', Account::TYPE_ASSET, Account::SUBTYPE_CURRENT_ASSET,
            $companyId, $userId
        );
        $obeAccount = $this->getOrCreateChartAccountForCustomer(
            'Opening Balance Equity', 'OBE-001', Account::TYPE_EQUITY, 'Opening Balance Equity',
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

        // DR Accounts Receivable — customer owes us
        Transaction::create(array_merge($base, ['account_id' => $arAccount->id, 'debit' => $balance, 'credit' => 0]));
        // CR Opening Balance Equity — balancing entry
        Transaction::create(array_merge($base, ['account_id' => $obeAccount->id, 'debit' => 0, 'credit' => $balance]));
    }

    /**
     * Delete any existing CUST-OB-* journal entries for a customer.
     * Called before re-posting so balance-sheet figures always stay in sync.
     */
    private function reverseCustomerOpeningBalanceJournal(int $customerId): void
    {
        if (!Schema::hasTable('transactions')) {
            return;
        }

        Transaction::withoutGlobalScopes()
            ->where('reference', 'CUST-OB-' . $customerId)
            ->where('transaction_type', Transaction::TYPE_OPENING_BALANCE)
            ->where('related_id', $customerId)
            ->where('related_type', Customer::class)
            ->delete();
    }

    /**
     * Find or create a Chart of Accounts entry needed for opening-balance journals.
     */
    private function getOrCreateChartAccountForCustomer(
        string $name, string $code, string $type, string $subType,
        int $companyId, int $userId
    ): Account {
        // 1. Look up by company + name + type (semantic match — most reliable)
        $lookup = Account::withoutGlobalScopes()
            ->when($companyId > 0, fn ($q) => $q->where('company_id', $companyId))
            ->where('name', $name)
            ->where('type', $type)
            ->first();

        if ($lookup) {
            return $lookup;
        }

        // 2. Determine a unique code for this company.
        //    accounts.code has a GLOBAL unique index, so we suffix per-company
        //    if the standard code is already taken by another company.
        $finalCode = $code;
        if (Account::withoutGlobalScopes()->where('code', $code)->exists()) {
            $finalCode = $companyId > 0 ? $code . '-C' . $companyId : $code . '-X';
            $attempt = 0;
            while (Account::withoutGlobalScopes()->where('code', $finalCode)->exists()) {
                $finalCode = $code . '-C' . $companyId . '-' . (++$attempt);
            }
        }

        $columns = Schema::getColumnListing('accounts');
        $payload = array_intersect_key([
            'name'            => $name,
            'code'            => $finalCode,
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

        return Account::create($payload);
    }

    private function normalizeImportHeaderCell($value): string
    {
        $header = (string) $value;
        // Strip UTF-8 BOM, non-breaking spaces, and control characters
        $header = str_replace("\xEF\xBB\xBF", '', $header);
        $header = str_replace("\xC2\xA0", ' ', $header);
        $header = preg_replace('/[\x00-\x1F\x7F]/', '', $header) ?? $header;
        $header = strtolower(trim($header));
        // Normalise whitespace/dashes to underscore
        $header = preg_replace('/[\s\-]+/', '_', $header) ?? $header;

        // Common column aliases → canonical names
        $aliases = [
            'name'              => 'customer_name',
            'full_name'         => 'customer_name',
            'customer'          => 'customer_name',
            'contact_name'      => 'customer_name',
            'mobile'            => 'phone',
            'mobile_number'     => 'phone',
            'phone_number'      => 'phone',
            'telephone'         => 'phone',
            'tel'               => 'phone',
            'email_address'     => 'email',
            'e_mail'            => 'email',
            'addr'              => 'address',
            'street'            => 'address',
            'opening_bal'       => 'balance',
            'opening_balance'   => 'balance',
            'ob'                => 'balance',
            'credit'            => 'credit_limit',
            'credit_line'       => 'credit_limit',
            'max_credit'        => 'credit_limit',
            'remark'            => 'notes',
            'remarks'           => 'notes',
            'comment'           => 'notes',
            'comments'          => 'notes',
            'description'       => 'notes',
            'branch'            => 'branch_name',
            'branch_id'         => 'branch_name',
        ];

        return $aliases[$header] ?? $header;
    }

    private function looksLikeCustomerImportHeader(array $normalizedRow): bool
    {
        $normalizedRow = collect($normalizedRow)
            ->map(fn ($value) => trim((string) $value))
            ->filter()
            ->values()
            ->all();

        if (empty($normalizedRow)) {
            return false;
        }

        if (in_array('customer_name', $normalizedRow, true)) {
            return true;
        }

        $knownColumns = [
            'customer_name',
            'email',
            'phone',
            'address',
            'balance',
            'credit_limit',
            'status',
            'notes',
            'branch_name',
        ];

        return count(array_intersect($knownColumns, $normalizedRow)) >= 2;
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
