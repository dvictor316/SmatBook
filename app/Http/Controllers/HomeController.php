<?php

namespace App\Http\Controllers;

use App\Models\{Company, Subscription, User, DeploymentManager, Domain};
use App\Models\Customer;
use App\Models\Product;
use App\Models\Quotation;
use App\Support\SystemEventMailer;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\{Auth, Cache, DB, Http, Log, Schema, Storage, Hash};
use Illuminate\Http\Request;
use Carbon\Carbon;

class HomeController extends Controller
{
    private function replaceUserMedia(User $user, string $field, \Illuminate\Http\UploadedFile $file, string $directory): void
    {
        $existingPath = trim((string) ($user->{$field} ?? ''));
        if ($existingPath !== '' && Storage::disk('public')->exists($existingPath)) {
            Storage::disk('public')->delete($existingPath);
        }

        $user->{$field} = $file->store($directory, 'public');
    }

    private function onlyExistingQuotationColumns(array $payload): array
    {
        if (!Schema::hasTable('quotations')) {
            return $payload;
        }

        return collect($payload)
            ->filter(fn ($value, $column) => Schema::hasColumn('quotations', $column))
            ->all();
    }

    private function normalizeDateInput(?string $value): ?string
    {
        $value = trim((string) $value);
        if ($value === '') {
            return null;
        }

        foreach (['d-m-Y', 'Y-m-d', 'd/m/Y', 'm/d/Y'] as $format) {
            try {
                return Carbon::createFromFormat($format, $value)->toDateString();
            } catch (\Throwable $e) {
            }
        }

        try {
            return Carbon::parse($value)->toDateString();
        } catch (\Throwable $e) {
            return null;
        }
    }

    private function generateUniqueQuotationId(): string
    {
        do {
            $candidate = 'QTN-' . now()->format('ymd') . '-' . strtoupper(substr(bin2hex(random_bytes(3)), 0, 6));
        } while (Quotation::where('quotation_id', $candidate)->exists());

        return $candidate;
    }

    private function quotationQuery()
    {
        $query = Quotation::with('customer');
        $companyId = (int) (Auth::user()?->company_id ?? session('current_tenant_id') ?? 0);
        $userId = (int) (Auth::id() ?? 0);
        $branchId = trim((string) session('active_branch_id', ''));
        $branchName = trim((string) session('active_branch_name', ''));

        if ($companyId > 0 && Schema::hasColumn('quotations', 'company_id')) {
            $query->where('company_id', $companyId)
                ->orWhere(function ($sub) use ($userId) {
                    $sub->whereNull('company_id')
                        ->where('user_id', $userId);
                });
        }

        if ($branchId !== '' || $branchName !== '') {
            $query->where(function ($sub) use ($branchId, $branchName) {
                if ($branchId !== '' && Schema::hasColumn('quotations', 'branch_id')) {
                    $sub->where('branch_id', $branchId);
                }
                if ($branchName !== '' && Schema::hasColumn('quotations', 'branch_name')) {
                    $sub->orWhere('branch_name', $branchName);
                }
            });
        }

        return $query;
    }

    private function findScopedQuotation($id): Quotation
    {
        return $this->quotationQuery()->findOrFail($id);
    }

    private function quotationItems(Quotation $quotation): array
    {
        $items = json_decode((string) ($quotation->items_json ?? '[]'), true);
        return is_array($items) ? array_values($items) : [];
    }

    private function quotationInvoicePrefill(Quotation $quotation): array
    {
        $issueDate = $quotation->issue_date ? Carbon::parse($quotation->issue_date)->format('d-m-Y') : now()->format('d-m-Y');
        $expiryDate = $quotation->expiry_date ? Carbon::parse($quotation->expiry_date)->format('d-m-Y') : now()->addDays(7)->format('d-m-Y');

        return [
            'customer_id' => $quotation->customer_id,
            'invoice_date' => $issueDate,
            'due_date' => $expiryDate,
            'description' => $quotation->description ?? $quotation->note,
            'items' => $this->quotationItems($quotation),
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | INDEX — Master router for all user types
    |
    | 5 destination types:
    |  1. Super Admin          → /superadmin/dashboard
    |  2. Deployment Manager   → /deployment/dashboard
    |  3. New customer         → /saas/checkout/{id}   (unpaid subscription)
    |  4. Setup-pending tenant → /saas/setup/{id}      (no domain yet)
    |  5. Active tenant        → https://subdomain.smartprobook.com
    |--------------------------------------------------------------------------
    */
    public function index()
    {
        $user = Auth::user();

        if (!$user) {
            Log::warning('Home accessed without authentication');
            return redirect()->route('login');
        }

        Log::info('=== HOME ROUTE ACCESSED ===', [
            'user_id' => $user->id,
            'email'   => $user->email,
            'role'    => $user->role,
        ]);

        if ($this->isTempOpenAccess()) {
            if ($this->isSuperAdmin($user)) {
                if (session('workspace_context') === 'business') {
                    return redirect()->route('workspace.business.dashboard');
                }
                return redirect()->route('super_admin.dashboard');
            }

            $isDeploymentManager = DeploymentManager::where('user_id', $user->id)->exists()
                || in_array(strtolower((string) ($user->role ?? '')), ['deployment_manager', 'manager'], true);

            if ($isDeploymentManager) {
                return $this->handleDeploymentManagerRedirect($user);
            }

            return redirect()->route('user.dashboard');
        }

        // ── PRIORITY 1: Check deployment_managers table FIRST ──
        // Must happen before role check — deployment managers may have
        // role='administrator' which would otherwise match super admin.
        $isDeploymentManager = DeploymentManager::where('user_id', $user->id)->exists();

        if ($isDeploymentManager) {
            Log::info('User is DEPLOYMENT MANAGER (found in deployment_managers table)', [
                'user_id' => $user->id,
            ]);
            return $this->handleDeploymentManagerRedirect($user);
        }

        // ── PRIORITY 2: Super Admin ──
        if ($this->isSuperAdmin($user)) {
            Log::info('User is SUPER ADMIN', ['user_id' => $user->id]);
            if (session('workspace_context') === 'business') {
                Log::info('Super admin requested BUSINESS WORKSPACE context', ['user_id' => $user->id]);
                return redirect()->route('workspace.business.dashboard');
            }
            return redirect()->route('super_admin.dashboard');
        }

        // ── PRIORITY 3: Regular Tenant ──
        Log::info('User is REGULAR TENANT', ['user_id' => $user->id]);
        return $this->handleRegularUserRedirect($user);
    }

    /*
    |--------------------------------------------------------------------------
    | DEPLOYMENT MANAGER REDIRECT
    |--------------------------------------------------------------------------
    */
    private function handleDeploymentManagerRedirect($user)
    {
        $manager = DeploymentManager::where('user_id', $user->id)->first();

        Log::info('Processing deployment manager', [
            'user_id'     => $user->id,
            'status'      => $manager->status ?? 'no_record',
            'is_verified' => $user->is_verified,
        ]);

        if (!$manager) {
            Log::info('→ Redirecting to verification form');
            return redirect()->route('manager.verification.form');
        }

        $status = strtolower((string) ($manager->status ?? 'pending_info'));

        if ($status === 'pending_info') {
            Log::info('→ Redirecting to verification form');
            return redirect()->route('manager.verification.form');
        }

        // Suspended / Inactive / Rejected → log out
        if (in_array($status, ['suspended', 'inactive', 'rejected'], true)) {
            Log::warning('Manager account restricted', ['status' => $status]);
            Auth::logout();
            return redirect()->route('login')
                ->with('error', 'Your account has been ' . $status . '. Please contact support.');
        }

        // Pending approval
        if ($status === 'pending' || !$user->is_verified) {
            Log::info('→ Manager pending approval');
            return redirect()->route('manager.pending.notice');
        }

        // Active
        if ($status === 'active') {
            Log::info('→ Redirecting to DEPLOYMENT DASHBOARD');
            // Clear any tenant context that may have leaked from a previous client checkout
            session()->forget(['current_tenant_id', 'current_tenant_name']);
            return redirect()->route('deployment.dashboard');
        }

        // Fallback — send back to verification to complete profile
        return redirect()->route('manager.verification.form');
    }

    /*
    |--------------------------------------------------------------------------
    | REGULAR USER REDIRECT
    |
    | Flow:
    |  no subscription          → /membership-plans
    |  unpaid subscription       → /saas/checkout/{id}   ← handles deployment-created customers
    |  paid but inactive         → /saas/setup/{id}
    |  paid, active, no company  → /saas/setup/{id}
    |  paid, active, has company → https://subdomain.smartprobook.com
    |--------------------------------------------------------------------------
    */
    private function handleRegularUserRedirect($user)
    {
        // Fetch latest subscription regardless of payment status
        $subscription = Subscription::where('user_id', $user->id)
            ->latest()
            ->first();

        Log::info('Regular user redirect', [
            'user_id'          => $user->id,
            'has_subscription' => (bool) $subscription,
            'payment_status'   => $subscription?->payment_status,
            'sub_status'       => $subscription?->status,
        ]);

        // No subscription at all
        if (!$subscription) {
            Log::info('→ No subscription, redirecting to plans');
            return redirect()->route('membership-plans')
                ->with('info', 'Please select a plan to get started.');
        }

        // Unpaid → checkout (covers deployment-created customers on first login)
        if ($subscription->payment_status !== 'paid') {
            Log::info('→ Unpaid subscription, redirecting to checkout', [
                'subscription_id' => $subscription->id,
            ]);
            return redirect()->route('saas.checkout', $subscription->id);
        }

        // Paid but expired
        if ($subscription->isExpired()) {
            Log::info('→ Expired subscription, redirecting to plans', [
                'subscription_id' => $subscription->id,
                'end_date' => (string) $subscription->end_date,
            ]);
            return redirect()->route('subscription.expired');
        }

        // Paid but subscription not yet Active
        if ($subscription->status !== 'Active') {
            Log::info('→ Inactive subscription, redirecting to setup', [
                'subscription_id' => $subscription->id,
            ]);
            return redirect()->route('saas.setup', $subscription->id);
        }

        // Active subscription — check company/domain exists
        $company = Company::where(function ($q) use ($user) {
            $q->where('user_id', $user->id)
              ->orWhere('owner_id', $user->id);
        })->first();

        if (!$company || empty($company->domain_prefix)) {
            Log::info('→ No company/domain, redirecting to setup', [
                'subscription_id' => $subscription->id,
            ]);
            return redirect()->route('saas.setup', $subscription->id);
        }

        if (!$this->companyWorkspaceSubdomainReady($company, $subscription, $user)) {
            Log::warning('→ Workspace subdomain not ready, keeping user on central workspace', [
                'company_id' => $company->id,
                'subscription_id' => $subscription->id,
                'domain_prefix' => $company->domain_prefix,
            ]);

            return $this->redirectToCentralBusinessWorkspace(
                $company,
                $subscription,
                'Your workspace is available, but the secure custom subdomain is still being finalized. You can continue safely from the central dashboard for now.'
            );
        }

        $mainDomain = trim((string) config('session.domain', env('SESSION_DOMAIN', 'smartprobook.com')), ". \t\n\r\0\x0B");
        $workspaceUrl = 'https://' . $company->domain_prefix . '.' . $mainDomain;

        if (!$this->workspaceHttpsReady($workspaceUrl)) {
            Log::warning('→ Workspace HTTPS endpoint not ready, keeping user on central workspace', [
                'company_id' => $company->id,
                'subscription_id' => $subscription->id,
                'workspace_url' => $workspaceUrl,
            ]);

            return $this->redirectToCentralBusinessWorkspace(
                $company,
                $subscription,
                'Your workspace is being finalized. Continue from the central dashboard until the secure subdomain is ready.'
            );
        }

        Log::info('→ Redirecting to workspace', [
            'url'             => $workspaceUrl,
            'subscription_id' => $subscription->id,
        ]);

        session([
            'user_plan' => strtolower($subscription->plan ?? $subscription->plan_name ?? 'basic'),
            'current_tenant_id' => $company->id,
            'current_tenant_name' => $company->name ?? $company->company_name ?? 'Workspace',
            'workspace_context' => 'business',
        ]);

        return redirect()->away($workspaceUrl);
    }

    private function redirectToCentralBusinessWorkspace(Company $company, Subscription $subscription, ?string $message = null)
    {
        session([
            'user_plan' => strtolower($subscription->plan ?? $subscription->plan_name ?? $company->plan ?? 'basic'),
            'current_tenant_id' => $company->id,
            'current_tenant_name' => $company->name ?? $company->company_name ?? 'Workspace',
            'workspace_context' => 'business',
        ]);

        $redirect = redirect()->route('workspace.business.dashboard');

        return $message ? $redirect->with('info', $message) : $redirect;
    }

    private function companyWorkspaceSubdomainReady(Company $company, Subscription $subscription, User $user): bool
    {
        $prefix = trim((string) ($company->domain_prefix ?? ''));
        if ($prefix === '' || !Schema::hasTable('domains')) {
            return false;
        }

        $mainDomain = trim((string) config('session.domain', env('SESSION_DOMAIN', 'smartprobook.com')), ". \t\n\r\0\x0B");
        $expectedDomain = strtolower($prefix . '.' . $mainDomain);

        $domain = Domain::withoutGlobalScopes()
            ->where(function ($query) use ($company, $subscription, $user, $expectedDomain) {
                $query->whereRaw('LOWER(COALESCE(domain_name, "")) = ?', [$expectedDomain]);

                if (!empty($subscription->id) && Schema::hasColumn('domains', 'subscription_id')) {
                    $query->orWhere('subscription_id', $subscription->id);
                }

                if (!empty($company->user_id) && Schema::hasColumn('domains', 'tenant_id')) {
                    $query->orWhere('tenant_id', $company->user_id);
                }

                if (!empty($user->email) && Schema::hasColumn('domains', 'email')) {
                    $query->orWhere('email', $user->email);
                }
            })
            ->when(Schema::hasColumn('domains', 'approved_at'), fn ($query) => $query->orderByDesc('approved_at'))
            ->when(Schema::hasColumn('domains', 'setup_completed_at'), fn ($query) => $query->orderByDesc('setup_completed_at'))
            ->latest('id')
            ->first();

        if (!$domain) {
            return false;
        }

        $status = strtolower(trim((string) ($domain->status ?? '')));
        if ($status !== 'active') {
            return false;
        }

        $approved = !Schema::hasColumn('domains', 'approved_at') || !empty($domain->approved_at);
        $setupComplete = !Schema::hasColumn('domains', 'setup_completed_at') || !empty($domain->setup_completed_at);

        return $approved && $setupComplete;
    }

    private function workspaceHttpsReady(string $workspaceUrl): bool
    {
        $host = parse_url($workspaceUrl, PHP_URL_HOST);
        if (!is_string($host) || trim($host) === '') {
            return false;
        }

        $cacheKey = 'workspace_https_ready:' . strtolower($host);

        return Cache::remember($cacheKey, now()->addMinutes(10), function () use ($workspaceUrl) {
            try {
                $pingUrl = rtrim($workspaceUrl, '/') . '/session/ping';

                $response = Http::timeout(5)
                    ->connectTimeout(3)
                    ->acceptJson()
                    ->withOptions([
                        'allow_redirects' => true,
                    ])
                    ->get($pingUrl);

                if (!$response->ok()) {
                    return false;
                }

                $payload = $response->json();

                return is_array($payload) && ($payload['ok'] ?? false) === true;
            } catch (\Throwable $e) {
                Log::warning('Workspace HTTPS readiness probe failed', [
                    'workspace_url' => $workspaceUrl,
                    'error' => $e->getMessage(),
                ]);

                return false;
            }
        });
    }

    /*
    |--------------------------------------------------------------------------
    | IS SUPER ADMIN — strict check, never matches deployment managers
    |
    | Only 'super_admin' / 'superadmin' roles + Victor's email.
    | 'administrator' is NOT included — deployment managers carry that role.
    |--------------------------------------------------------------------------
    */
    private function isSuperAdmin($user): bool
    {
        if ($user->email === 'donvictorlive@gmail.com') {
            return true;
        }

        return in_array(strtolower($user->role ?? ''), ['super_admin', 'superadmin']);
    }

    private function isTempOpenAccess(): bool
    {
        return (bool) env('TEMP_OPEN_ACCESS', false);
    }

    /*
    |--------------------------------------------------------------------------
    | DASHBOARD — Tenant plan-based dashboard view
    | Route: GET /dashboard   name: dashboard (or home.dashboard)
    |--------------------------------------------------------------------------
    */
    public function dashboard()
    {
        $user = Auth::user();

        $company = Company::where('user_id', $user->id)
            ->orWhere('owner_id', $user->id)
            ->first();

        $subscription = Subscription::where('user_id', $user->id)
            ->where('payment_status', 'paid')
            ->latest()
            ->first();

        if (!$subscription || !$company) {
            return redirect()->route('membership-plans');
        }

        if ($subscription->isExpired()) {
            return redirect()->route('subscription.expired');
        }

        if (strtolower((string) $subscription->status) !== 'active') {
            return redirect()->route('saas.setup', $subscription->id);
        }

        $planName = strtolower($subscription->plan ?? $subscription->plan_name ?? 'basic');

        // Store in session so layout/sidebar knows which plan sidebar to include
        session(['user_plan' => $planName]);

        return view('dashboard', compact('user', 'company', 'subscription'));
    }

    public function subscriptionExpired()
    {
        $user = Auth::user();

        if (!$user) {
            return redirect()->route('login');
        }

        $subscription = Subscription::resolveCurrentForUser($user)
            ?? Subscription::where('user_id', $user->id)->latest()->first();

        if (!$subscription) {
            return redirect()->route('membership-plans')
                ->with('info', 'Please select a plan to continue.');
        }

        if (!$subscription->isExpired()) {
            return redirect()->route('home');
        }

        return view('subscription.expired', compact('subscription'));
    }

    // Quotations / Delivery pages used by sidebar links
    public function quotations()
    {
        $quotations = new LengthAwarePaginator([], 0, 20);
        if (Schema::hasTable('quotations')) {
            $query = Quotation::with('customer')->latest();
            $companyId = (int) (Auth::user()?->company_id ?? session('current_tenant_id') ?? 0);
            $userId = (int) (Auth::id() ?? 0);
            $branchId = trim((string) session('active_branch_id', ''));
            $branchName = trim((string) session('active_branch_name', ''));

            if ($companyId > 0 && Schema::hasColumn('quotations', 'company_id')) {
                $query->where('company_id', $companyId)
                    ->orWhere(function ($sub) use ($userId) {
                        $sub->whereNull('company_id')
                            ->where('user_id', $userId);
                    });
            } elseif ($companyId > 0 && Schema::hasTable('customers')) {
                $query->whereHas('customer', function ($sub) use ($companyId) {
                    if (Schema::hasColumn('customers', 'company_id')) {
                        $sub->where('company_id', $companyId);
                    }
                });
            }

            if ($branchId !== '' || $branchName !== '') {
                $query->where(function ($sub) use ($branchId, $branchName) {
                    if ($branchId !== '' && Schema::hasColumn('quotations', 'branch_id')) {
                        $sub->where('branch_id', $branchId);
                    }
                    if ($branchName !== '' && Schema::hasColumn('quotations', 'branch_name')) {
                        $sub->orWhere('branch_name', $branchName);
                    }
                })->orWhereHas('customer', function ($sub) use ($branchId, $branchName) {
                    if ($branchId !== '' && Schema::hasColumn('customers', 'branch_id')) {
                        $sub->where('branch_id', $branchId);
                    }
                    if ($branchName !== '' && Schema::hasColumn('customers', 'branch_name')) {
                        $sub->orWhere('branch_name', $branchName);
                    }
                });
            }

            $quotations = $query->paginate(20);
        }
        return view('Quotations.quotations', compact('quotations'));
    }

    public function add_quotations()
    {
        $customers = collect();
        $products = collect();
        if (Schema::hasTable('customers')) {
            $customerNameColumn = Schema::hasColumn('customers', 'name') ? 'name' : 'customer_name';
            $customersQuery = Customer::orderBy($customerNameColumn);
            $companyId = (int) (Auth::user()?->company_id ?? session('current_tenant_id') ?? 0);
            $userId = (int) (Auth::id() ?? 0);
            $branchId = trim((string) session('active_branch_id', ''));
            $branchName = trim((string) session('active_branch_name', ''));

            if ($companyId > 0 && Schema::hasColumn('customers', 'company_id')) {
                $customersQuery->where('company_id', $companyId)
                    ->orWhere(function ($sub) use ($userId) {
                        $sub->whereNull('company_id')
                            ->where('user_id', $userId);
                    });
            }
            if ($branchId !== '' || $branchName !== '') {
                $customersQuery->where(function ($sub) use ($branchId, $branchName) {
                    if ($branchId !== '' && Schema::hasColumn('customers', 'branch_id')) {
                        $sub->where('branch_id', $branchId);
                    }
                    if ($branchName !== '' && Schema::hasColumn('customers', 'branch_name')) {
                        $sub->orWhere('branch_name', $branchName);
                    }
                });
            }

            $customers = $customersQuery->get();
        }
        if (Schema::hasTable('products')) {
            $productsQuery = Product::query()->orderBy(Schema::hasColumn('products', 'name') ? 'name' : 'id');
            $companyId = (int) (Auth::user()?->company_id ?? session('current_tenant_id') ?? 0);
            $userId = (int) (Auth::id() ?? 0);
            $branchId = trim((string) session('active_branch_id', ''));
            $branchName = trim((string) session('active_branch_name', ''));

            if ($companyId > 0 && Schema::hasColumn('products', 'company_id')) {
                $productsQuery->where('company_id', $companyId)
                    ->orWhere(function ($sub) use ($userId) {
                        $sub->whereNull('company_id')
                            ->where('user_id', $userId);
                    });
            }
            if ($branchId !== '' || $branchName !== '') {
                $productsQuery->where(function ($sub) use ($branchId, $branchName) {
                    if ($branchId !== '' && Schema::hasColumn('products', 'branch_id')) {
                        $sub->where('branch_id', $branchId);
                    }
                    if ($branchName !== '' && Schema::hasColumn('products', 'branch_name')) {
                        $sub->orWhere('branch_name', $branchName);
                    }
                });
            }

            $products = $productsQuery->get();
        }
        return view('Quotations.add-quotations', compact('customers', 'products'));
    }

    public function edit_quotations($id = null)
    {
        $quotation = null;
        if (Schema::hasTable('quotations')) {
            $quotation = $id ? $this->findScopedQuotation($id) : $this->quotationQuery()->latest()->first();
        }
        $customers = collect();
        $products = collect();
        if (Schema::hasTable('customers')) {
            $customerNameColumn = Schema::hasColumn('customers', 'name') ? 'name' : 'customer_name';
            $customersQuery = Customer::orderBy($customerNameColumn);
            $companyId = (int) (Auth::user()?->company_id ?? session('current_tenant_id') ?? 0);
            $userId = (int) (Auth::id() ?? 0);
            $branchId = trim((string) session('active_branch_id', ''));
            $branchName = trim((string) session('active_branch_name', ''));

            if ($companyId > 0 && Schema::hasColumn('customers', 'company_id')) {
                $customersQuery->where('company_id', $companyId)
                    ->orWhere(function ($sub) use ($userId) {
                        $sub->whereNull('company_id')
                            ->where('user_id', $userId);
                    });
            }
            if ($branchId !== '' || $branchName !== '') {
                $customersQuery->where(function ($sub) use ($branchId, $branchName) {
                    if ($branchId !== '' && Schema::hasColumn('customers', 'branch_id')) {
                        $sub->where('branch_id', $branchId);
                    }
                    if ($branchName !== '' && Schema::hasColumn('customers', 'branch_name')) {
                        $sub->orWhere('branch_name', $branchName);
                    }
                });
            }

            $customers = $customersQuery->get();
        }
        if (Schema::hasTable('products')) {
            $productsQuery = Product::query()->orderBy(Schema::hasColumn('products', 'name') ? 'name' : 'id');
            $companyId = (int) (Auth::user()?->company_id ?? session('current_tenant_id') ?? 0);
            $userId = (int) (Auth::id() ?? 0);
            $branchId = trim((string) session('active_branch_id', ''));
            $branchName = trim((string) session('active_branch_name', ''));

            if ($companyId > 0 && Schema::hasColumn('products', 'company_id')) {
                $productsQuery->where('company_id', $companyId)
                    ->orWhere(function ($sub) use ($userId) {
                        $sub->whereNull('company_id')
                            ->where('user_id', $userId);
                    });
            }
            if ($branchId !== '' || $branchName !== '') {
                $productsQuery->where(function ($sub) use ($branchId, $branchName) {
                    if ($branchId !== '' && Schema::hasColumn('products', 'branch_id')) {
                        $sub->where('branch_id', $branchId);
                    }
                    if ($branchName !== '' && Schema::hasColumn('products', 'branch_name')) {
                        $sub->orWhere('branch_name', $branchName);
                    }
                });
            }

            $products = $productsQuery->get();
        }
        return view('Quotations.edit-quotations', compact('quotation', 'customers', 'products'));
    }

    public function storeQuotation(Request $request)
    {
        $customerValidation = Schema::hasTable('customers') ? 'nullable|exists:customers,id' : 'nullable';
        $validated = $request->validate([
            'quotation_id' => 'nullable|string|max:100|unique:quotations,quotation_id',
            'customer_id' => $customerValidation,
            'issue_date' => 'nullable|string|max:50',
            'expiry_date' => 'nullable|string|max:50',
            'items' => 'required|array|min:1',
            'items.*.qty' => 'required|numeric|min:1',
            'items.*.rate' => 'required|numeric|min:0',
            'total' => 'required|numeric|min:0',
            'status' => 'nullable|string|max:50',
            'description' => 'nullable|string|max:5000',
            'note' => 'nullable|string|max:5000',
        ]);

        $items = collect($request->input('items', []))
            ->filter(fn ($item) => filled($item['name'] ?? null) || filled($item['product_id'] ?? null))
            ->values();

        if ($items->isEmpty()) {
            return back()->withInput()->with('error', 'Add at least one quotation item before saving.');
        }

        if (empty($validated['quotation_id'])) {
            $validated['quotation_id'] = $this->generateUniqueQuotationId();
        }
        $status = trim((string) ($validated['status'] ?? 'Pending'));
        if ($request->input('action') === 'send' && $status === 'Pending') {
            $status = 'Sent';
        }
        $validated['status'] = $status;

        $subtotal = 0;
        $discountTotal = 0;
        $taxTotal = 0;

        foreach ($items as $item) {
            $qty = (float) ($item['qty'] ?? 0);
            $rate = (float) ($item['rate'] ?? 0);
            $discount = (float) ($item['discount'] ?? 0);
            $tax = (float) ($item['tax'] ?? 0);
            $lineSubtotal = $qty * $rate;
            $subtotal += $lineSubtotal;
            $discountTotal += $discount;
            $taxTotal += $tax;
        }

        $customer = null;
        if (!empty($validated['customer_id']) && Schema::hasTable('customers')) {
            $customer = Customer::find($validated['customer_id']);
        }

        $issueDate = $this->normalizeDateInput($validated['issue_date'] ?? null) ?? now()->toDateString();
        $expiryDate = $this->normalizeDateInput($validated['expiry_date'] ?? null) ?? now()->addDays(7)->toDateString();

        $payload = [
            'quotation_id' => $validated['quotation_id'],
            'customer_id' => $validated['customer_id'] ?? null,
            'company_id' => Auth::user()?->company_id ?? session('current_tenant_id'),
            'user_id' => Auth::id(),
            'branch_id' => session('active_branch_id'),
            'branch_name' => session('active_branch_name'),
            'customer_name' => $customer?->customer_name ?? $customer?->name ?? 'Walk-in Customer',
            'issue_date' => $issueDate,
            'expiry_date' => $expiryDate,
            'subtotal' => $subtotal,
            'tax' => $taxTotal,
            'discount' => $discountTotal,
            'total' => $validated['total'],
            'status' => $validated['status'],
            'description' => $validated['description'] ?? $validated['note'] ?? null,
            'items_json' => json_encode($items->all()),
            'note' => $validated['note'] ?? $validated['description'] ?? null,
        ];

        try {
            Quotation::create($this->onlyExistingQuotationColumns($payload));
        } catch (\Throwable $e) {
            Log::error('Quotation create failed', [
                'message' => $e->getMessage(),
                'user_id' => Auth::id(),
            ]);

            return back()->withInput()->with('error', 'Quotation could not be saved. ' . $e->getMessage());
        }

        $message = $request->input('action') === 'send'
            ? 'Quotation saved and marked as sent.'
            : 'Quotation created successfully.';

        return redirect()->route('quotations')->with('success', $message);
    }

    public function updateQuotation(Request $request, $id)
    {
        $quotation = $this->findScopedQuotation($id);
        $customerValidation = Schema::hasTable('customers') ? 'nullable|exists:customers,id' : 'nullable';

        $validated = $request->validate([
            'quotation_id' => 'required|string|max:100|unique:quotations,quotation_id,' . $quotation->id,
            'customer_id' => $customerValidation,
            'issue_date' => 'nullable|string|max:50',
            'expiry_date' => 'nullable|string|max:50',
            'items' => 'required|array|min:1',
            'items.*.qty' => 'required|numeric|min:1',
            'items.*.rate' => 'required|numeric|min:0',
            'total' => 'required|numeric|min:0',
            'status' => 'nullable|string|max:50',
            'description' => 'nullable|string|max:5000',
            'note' => 'nullable|string|max:5000',
        ]);

        $items = collect($request->input('items', []))
            ->filter(fn ($item) => filled($item['name'] ?? null) || filled($item['product_id'] ?? null))
            ->values();

        if ($items->isEmpty()) {
            return back()->withInput()->with('error', 'Add at least one quotation item before saving.');
        }

        $customer = null;
        if (!empty($validated['customer_id']) && Schema::hasTable('customers')) {
            $customer = Customer::find($validated['customer_id']);
        }

        $subtotal = 0;
        $discountTotal = 0;
        $taxTotal = 0;

        foreach ($items as $item) {
            $qty = (float) ($item['qty'] ?? 0);
            $rate = (float) ($item['rate'] ?? 0);
            $discount = (float) ($item['discount'] ?? 0);
            $tax = (float) ($item['tax'] ?? 0);
            $subtotal += $qty * $rate;
            $discountTotal += $discount;
            $taxTotal += $tax;
        }

        $validated['status'] = trim((string) ($validated['status'] ?? 'Pending'));
        $issueDate = $this->normalizeDateInput($validated['issue_date'] ?? null) ?? ($quotation->issue_date ? Carbon::parse($quotation->issue_date)->toDateString() : now()->toDateString());
        $expiryDate = $this->normalizeDateInput($validated['expiry_date'] ?? null) ?? ($quotation->expiry_date ? Carbon::parse($quotation->expiry_date)->toDateString() : now()->addDays(7)->toDateString());

        $quotation->update($this->onlyExistingQuotationColumns([
            'quotation_id' => $validated['quotation_id'],
            'customer_id' => $validated['customer_id'] ?? null,
            'company_id' => $quotation->company_id ?? (Auth::user()?->company_id ?? session('current_tenant_id')),
            'user_id' => $quotation->user_id ?? Auth::id(),
            'branch_id' => $quotation->branch_id ?? session('active_branch_id'),
            'branch_name' => $quotation->branch_name ?? session('active_branch_name'),
            'customer_name' => $customer?->customer_name ?? $customer?->name ?? $quotation->customer_name ?? 'Walk-in Customer',
            'issue_date' => $issueDate,
            'expiry_date' => $expiryDate,
            'subtotal' => $subtotal,
            'tax' => $taxTotal,
            'discount' => $discountTotal,
            'total' => $validated['total'],
            'status' => $validated['status'],
            'description' => $validated['description'] ?? $validated['note'] ?? null,
            'items_json' => json_encode($items->all()),
            'note' => $validated['note'] ?? $validated['description'] ?? null,
        ]));

        return redirect()->route('quotations')->with('success', 'Quotation updated successfully.');
    }

    public function showQuotation($id)
    {
        $quotation = $this->findScopedQuotation($id);
        $items = $this->quotationItems($quotation);

        return view('Quotations.show-quotation', compact('quotation', 'items'));
    }

    public function markQuotationSent($id)
    {
        $quotation = $this->findScopedQuotation($id);
        $quotation->update($this->onlyExistingQuotationColumns([
            'status' => 'Sent',
        ]));

        return redirect()->route('quotations')->with('success', 'Quotation marked as sent.');
    }

    public function sendQuotation(Request $request, $id)
    {
        $quotation = $this->findScopedQuotation($id);
        $recipient = $quotation->customer?->email;

        if (!filter_var($recipient, FILTER_VALIDATE_EMAIL)) {
            return back()->with('error', 'This quotation has no valid customer email address.');
        }

        $sent = SystemEventMailer::sendMessage(
            $recipient,
            'Quotation ' . ($quotation->quotation_id ?? ('#' . $quotation->id)),
            'Customer Quotation',
            'Your quotation has been prepared and is ready for review.',
            [
                'Customer' => $quotation->customer_name ?? $quotation->customer?->customer_name ?? $quotation->customer?->name ?? 'Walk-in Customer',
                'Quotation Number' => $quotation->quotation_id ?? ('#' . $quotation->id),
                'Issue Date' => $quotation->issue_date ? Carbon::parse($quotation->issue_date)->format('d M Y') : 'N/A',
                'Valid Until' => $quotation->expiry_date ? Carbon::parse($quotation->expiry_date)->format('d M Y') : 'N/A',
                'Total Amount' => 'NGN ' . number_format((float) ($quotation->total ?? 0), 2),
            ]
        );

        if ($sent) {
            $quotation->update($this->onlyExistingQuotationColumns(['status' => 'Sent']));
        }

        return back()->with($sent ? 'success' : 'error', $sent ? 'Quotation emailed successfully.' : 'Quotation email could not be sent.');
    }

    public function downloadQuotation($id)
    {
        $quotation = $this->findScopedQuotation($id);
        $items = $this->quotationItems($quotation);

        return Pdf::loadView('Quotations.show-quotation', compact('quotation', 'items'))
            ->download(($quotation->quotation_id ?? ('quotation-' . $quotation->id)) . '.pdf');
    }

    public function convertQuotationToInvoice($id)
    {
        $quotation = $this->findScopedQuotation($id);

        return redirect()->route('add-invoice')
            ->with('quotation_prefill', $this->quotationInvoicePrefill($quotation))
            ->with('info', 'Quotation loaded into invoice form.');
    }

    public function cloneQuotationAsInvoice($id)
    {
        return $this->convertQuotationToInvoice($id);
    }

    public function destroyQuotation($id)
    {
        $quotation = $this->findScopedQuotation($id);
        $quotation->delete();
        return redirect()->route('quotations')->with('success', 'Quotation deleted successfully.');
    }

    public function delivery_challans()
    {
        return view('Quotations.delivery-challans');
    }

    public function add_delivery_challans()
    {
        return view('Quotations.add-delivery-challans');
    }

    public function edit_delivery_challans()
    {
        return view('Quotations.edit-delivery-challans');
    }

    /*
    |--------------------------------------------------------------------------
    | PROFILE — View profile page
    | Route: GET /profile   name: profile (or home.profile)
    |--------------------------------------------------------------------------
    */
    public function profile()
    {
        $user = Auth::user();

        $profileFields = ['name', 'email', 'role', 'profile_photo', 'cover_photo'];
        $filledFields  = 0;

        foreach ($profileFields as $field) {
            if (!empty($user->$field)) {
                $filledFields++;
            }
        }

        $completeness = ($filledFields / count($profileFields)) * 100;

        return view('Pages.profile', compact('user', 'completeness'));
    }

    /*
    |--------------------------------------------------------------------------
    | UPDATE PROFILE IMAGES — profile photo + cover photo
    | Route: POST /profile/images   name: profile.images (or similar)
    |--------------------------------------------------------------------------
    */
    public function updateProfileImages(Request $request)
    {
        $request->validate([
            'profile_photo' => 'nullable|image|mimes:jpeg,png,jpg,webp|max:2048',
            'cover_photo'   => 'nullable|image|mimes:jpeg,png,jpg,webp|max:5120',
        ]);

        $user = Auth::user();

        if ($request->hasFile('profile_photo')) {
            $this->replaceUserMedia($user, 'profile_photo', $request->file('profile_photo'), 'users/profiles');
        }

        if ($request->hasFile('cover_photo')) {
            $this->replaceUserMedia($user, 'cover_photo', $request->file('cover_photo'), 'users/covers');
        }

        $user->save();

        return back()->with('success', 'Profile imagery updated successfully.');
    }

    public function uploadAvatar(Request $request)
    {
        $request->validate([
            'profile_photo' => 'required|image|mimes:jpeg,png,jpg,webp|max:2048',
        ]);

        $user = Auth::user();
        $this->replaceUserMedia($user, 'profile_photo', $request->file('profile_photo'), 'users/profiles');
        $user->save();

        return back()->with('success', 'Avatar updated successfully.');
    }

    /*
    |--------------------------------------------------------------------------
    | UPDATE PROFILE — Name and other text fields
    | Route: POST /profile/update   name: profile.update (or similar)
    |--------------------------------------------------------------------------
    */
    public function updateProfile(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
        ]);

        $user = Auth::user();
        $user->update($request->only(['name']));

        return back()->with('success', 'Profile details updated successfully.');
    }

    /*
    |--------------------------------------------------------------------------
    | CHANGE PASSWORD
    | Route: POST /profile/password   name: profile.password (or similar)
    |--------------------------------------------------------------------------
    */
    public function changePassword(Request $request)
    {
        $request->validate([
            'current_password' => 'required',
            'password'         => 'required|confirmed|min:8',
        ]);

        $user = Auth::user();

        // Verify current password before allowing change
        if (!Hash::check($request->current_password, $user->password)) {
            return back()->withErrors([
                'current_password' => 'The current password you entered is incorrect.',
            ]);
        }

        $user->password = Hash::make($request->password);
        $user->save();

        return back()->with('success', 'Password changed successfully.');
    }

    /*
    |--------------------------------------------------------------------------
    | MISC PAGES
    |--------------------------------------------------------------------------
    */
    public function blankpage()
    {
        return view('Pages.blank-page');
    }

    public function calendar()
    {
        return view('Applications.calendar');
    }

    public function inbox()
    {
        return view('Applications.inbox');
    }

    public function accountRequest(Request $request)
    {
        return view('pages.account-request');
    }
}
