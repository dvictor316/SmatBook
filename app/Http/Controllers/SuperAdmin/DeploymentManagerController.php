<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\{Auth, DB, Hash, Mail, Log, Storage, Schema};
use Illuminate\Support\{Str, Carbon};
use App\Models\{Company, User, Subscription, ActivityLog, DeploymentManager, DeploymentCompany, Plan};
use App\Support\SystemEventMailer;

class DeploymentManagerController extends Controller
{


    // Commission rate for deployment managers
    const COMMISSION_RATE = 35; // 35% commission on all deployments

    // ---------------------------------------------------------------
    // SHARED HELPERS
    // ---------------------------------------------------------------
    
    private function managedCompanyIds(): array
    {
        $mappedIds = DB::table('deployment_companies')
            ->where('manager_id', Auth::id())
            ->pluck('company_id')
            ->toArray();

        $legacyIds = Company::where('deployed_by', Auth::id())
            ->pluck('id')
            ->toArray();

        return array_values(array_unique(array_merge($mappedIds, $legacyIds)));
    }

    private function managedCompanies(): \Illuminate\Database\Eloquent\Builder
    {
        return Company::whereIn('id', $this->managedCompanyIds());
    }

    private function managedSubscriptions(): \Illuminate\Database\Eloquent\Builder
    {
        return Subscription::whereIn('company_id', $this->managedCompanyIds());
    }

    // =============================================================
    // 1. VERIFICATION & ONBOARDING
    // =============================================================

    public function showVerificationForm()
    {
        $user = Auth::user();
        $manager = DeploymentManager::where('user_id', $user->id)->first();

        if (($manager && $manager->status === 'active') || $user->is_verified == 1) {
            return redirect()->route('deployment.dashboard');
        }

        return view('deployment.verify-profile', compact('user', 'manager'));
    }

    public function submitVerification(Request $request)
    {
        $request->validate([
            'business_name' => 'required|string|max:255',
            'phone'         => 'required|string|max:20',
            'address'       => 'required|string',
            'id_type'       => 'required|in:CAC,BVN,NIN,Passport',
            'id_number'     => 'required|string|max:50',
        ]);

        $manager = DeploymentManager::where('user_id', Auth::id())->firstOrFail();
        $manager->update($request->only(['business_name', 'phone', 'address', 'id_type', 'id_number']));

        return redirect()->route('manager.pending.notice')
            ->with('info', 'Profile submitted. Awaiting SuperAdmin approval.');
    }

    public function approveManager($id)
    {
        try {
            DB::transaction(function () use ($id) {
                $manager = DeploymentManager::findOrFail($id);

                $updatePayload = [
                    'status' => 'active',
                ];

                if (Schema::hasColumn('deployment_managers', 'approved_at')) {
                    $updatePayload['approved_at'] = now();
                }
                if (Schema::hasColumn('deployment_managers', 'commission_rate')) {
                    $updatePayload['commission_rate'] = self::COMMISSION_RATE;
                }

                $manager->update($updatePayload);

                $user = User::findOrFail($manager->user_id);
                $user->update([
                    'is_verified' => 1,
                    'role' => 'deployment_manager',
                    'email_verified_at' => $user->email_verified_at ?? now(),
                ]);

                $this->ensureManagerHasWorkspace($user);

                DB::afterCommit(function () use ($user) {
                    SystemEventMailer::notifyManagerApproved($user, Auth::user());
                });
            });

            return back()->with('success', 'Partner approved successfully with 35% commission rate.');

        } catch (\Exception $e) {
            Log::error("Approval Sync Failed: " . $e->getMessage());
            return back()->with('error', 'Approval failed: ' . $e->getMessage());
        }
    }

    // =============================================================
    // 2. DASHBOARD & ANALYTICS
    // =============================================================

    public function index()
    {
        $now = Carbon::now();
        $user = Auth::user();

        $this->ensureManagerHasWorkspace($user);
        $this->expireDueManagedSubscriptions();

        $managerProfile = DeploymentManager::where('user_id', Auth::id())->first();
        $commissionRate = (float) ($managerProfile->commission_rate ?? self::COMMISSION_RATE);

        // Use created_at fallback because some rows may not have payment_date populated.
        $totalRevenue = $this->managedSubscriptions()
            ->where('payment_status', 'paid')
            ->whereYear('created_at', $now->year)
            ->whereMonth('created_at', $now->month)
            ->sum('amount');

        $totalCommissions = 0.0;
        $paidCommissions = 0.0;
        $pendingCommissions = 0.0;

        if (Schema::hasTable('deployment_commissions')) {
            $amountColumn = Schema::hasColumn('deployment_commissions', 'commission_amount')
                ? 'commission_amount'
                : 'amount';

            $commissionRows = DB::table('deployment_commissions')
                ->where('manager_id', Auth::id())
                ->select([$amountColumn . ' as amount', 'status'])
                ->get();

            $totalCommissions = (float) $commissionRows->sum('amount');
            $paidCommissions = (float) $commissionRows
                ->filter(fn ($row) => in_array(strtolower((string) ($row->status ?? '')), ['paid', 'credited']))
                ->sum('amount');
            $pendingCommissions = (float) $commissionRows
                ->filter(fn ($row) => strtolower((string) ($row->status ?? '')) === 'pending')
                ->sum('amount');
        } else {
            // Fallback for environments that do not have the commission ledger table yet.
            $totalCommissions = ($totalRevenue * $commissionRate) / 100;
        }

        $metrics = [
            'totalCompanies'         => $this->managedCompanies()->count(),
            'activeSubscriptions'    => $this->managedSubscriptions()
                ->where('payment_status', 'paid')
                ->where('status', 'Active')
                ->where(function ($q) use ($now) {
                    $q->whereNull('end_date')->orWhere('end_date', '>=', $now);
                })
                ->count(),
            'monthlyRevenue'         => $totalRevenue,
            'pendingApprovals'       => $this->managedCompanies()->where('status', 'pending')->count(),
            'trialCount'             => $this->managedCompanies()->where('status', 'trial')->count(),
            'pendingPayments'        => $this->managedSubscriptions()->whereIn('payment_status', ['pending', 'unpaid'])->count(),
            'pendingPaymentsValue'   => $this->managedSubscriptions()->whereIn('payment_status', ['pending', 'unpaid'])->sum('amount'),
            'commissionRate'         => $commissionRate,
            'totalCommissions'       => $totalCommissions,
            'paidCommissions'        => $paidCommissions,
            'pendingCommissions'     => $pendingCommissions,
            'expiringSoonSubscriptions' => $this->expiringManagedSubscriptionsQuery(7)->count(),
            'expiredSubscriptions'   => $this->managedSubscriptions()
                ->whereRaw("LOWER(COALESCE(status, '')) = 'expired'")
                ->count(),
        ];

        $companies = $this->managedCompanies()->withCount('users')->latest()->get();
        
        $recentSubscriptions = $this->managedSubscriptions()
            ->with('company')
            ->latest()
            ->limit(10)
            ->get();

        $recentActivities = ActivityLog::where('user_id', Auth::id())
            ->where('module', 'deployment')
            ->latest()
            ->limit(10)
            ->get();

        $expiringSubscriptions = $this->expiringManagedSubscriptionsQuery(7)
            ->with(['company', 'user'])
            ->orderBy('end_date', 'asc')
            ->limit(8)
            ->get();

        return view('deployment.dashboard', compact('metrics', 'companies', 'recentSubscriptions', 'recentActivities', 'expiringSubscriptions'));
    }

    private function expireDueManagedSubscriptions(): void
    {
        $companyIds = $this->managedCompanyIds();
        if (empty($companyIds)) {
            return;
        }

        if (method_exists(Subscription::class, 'expireDueSubscriptions')) {
            Subscription::expireDueSubscriptions($companyIds);
            return;
        }

        // Backward-compatible fallback when an older Subscription model is loaded.
        Subscription::query()
            ->whereIn('company_id', $companyIds)
            ->whereRaw("LOWER(COALESCE(status, '')) IN ('active','trial')")
            ->whereNotNull('end_date')
            ->whereDate('end_date', '<', now()->toDateString())
            ->update([
                'status' => 'Expired',
                'updated_at' => now(),
            ]);
    }

    private function expiringManagedSubscriptionsQuery(int $days = 7): \Illuminate\Database\Eloquent\Builder
    {
        return $this->managedSubscriptions()
            ->whereRaw("LOWER(COALESCE(status, '')) IN ('active','trial')")
            ->whereNotNull('end_date')
            ->whereDate('end_date', '>=', now()->toDateString())
            ->whereDate('end_date', '<=', now()->addDays($days)->toDateString());
    }

    private function ensureManagerHasWorkspace($user) 
    {
        $hasCompany = Company::where('user_id', $user->id)->exists();
        if (!$hasCompany) {
            $dmInfo = DeploymentManager::where('user_id', $user->id)->first();
            $prefix = strtolower(preg_replace('/[^A-Za-z0-9]/', '', ($dmInfo->business_name ?? $user->name))) . $user->id;

            Subscription::updateOrCreate(
                ['user_id' => $user->id],
                [
                    'plan' => 'Enterprise',
                    'subscriber_name' => $user->name,
                    'amount' => 0,
                    'status' => 'Active',
                    'payment_status' => 'paid',
                    'domain_prefix' => $prefix,
                    'start_date' => now(),
                    'end_date' => now()->addYear()
                ]
            );

            Company::updateOrCreate(
                ['user_id' => $user->id],
                [
                    'owner_id' => $user->id,
                    'domain_prefix' => $prefix,
                    'name' => ($dmInfo->business_name ?? $user->name),
                    'status' => 'active',
                    'plan' => 'Enterprise'
                ]
            );
        }
    }

    // =============================================================
    // 3. CUSTOMER REGISTRATION (REWRITTEN FOR PAYMENT FLOW)
    // =============================================================

    public function create()
    {
        Log::info('Showing customer registration form', ['manager_id' => auth()->id()]);
        
        $managerRecord = DeploymentManager::where('user_id', Auth::id())->first();
        $currentCount = count($this->managedCompanyIds());
        $limit = $managerRecord->deployment_limit ?? 100;

        if ($currentCount >= $limit) {
            return redirect()->route('deployment.companies.index')
                ->with('warning', "Deployment limit reached ({$currentCount}/{$limit}).");
        }

        return view('deployment.users.create', compact('limit', 'currentCount'));
    }

    /**
     * Display a listing of Deployment Managers.
     * This is the 'usersList' method the error was complaining about.
     */
    public function usersList()
    {
        $actor = Auth::user();
        $role = strtolower((string) ($actor->role ?? ''));
        $isSuperAdmin = in_array($role, ['super_admin', 'superadmin'], true)
            || in_array($role, ['administrator', 'admin'], true)
            || strtolower((string) ($actor->email ?? '')) === 'donvictorlive@gmail.com';

        if ($isSuperAdmin) {
            $users = DB::table('deployment_managers')
                ->leftJoin('users', 'deployment_managers.user_id', '=', 'users.id')
                ->select(
                    'deployment_managers.*',
                    DB::raw("COALESCE(users.name, deployment_managers.business_name, 'N/A') as manager_name"),
                    DB::raw("COALESCE(users.email, '') as email")
                )
                ->orderByDesc('deployment_managers.created_at')
                ->paginate(15);
        } else {
            $users = User::whereIn('company_id', $this->managedCompanyIds())
                ->with('company')
                ->latest()
                ->paginate(10);
        }

        return view('deployment.users.index', compact('users'));
    }



// ─────────────────────────────────────────────────────────────
// DROP-IN REPLACEMENT: store() method only
// ─────────────────────────────────────────────────────────────

public function store(Request $request)
{
    $request->merge([
        'phone' => $this->normalizePhoneForStorage($request->input('phone')),
        'customer_phone' => $this->normalizePhoneForStorage($request->input('customer_phone')),
    ]);

    $validated = $request->validate([
        'company_name'    => 'required|string|max:255',
        'subdomain'       => 'required|string|max:50|alpha_dash|unique:companies,domain_prefix',
        'phone'           => ['nullable', 'string', 'max:25', 'regex:/^\+?[0-9]{7,20}$/'],
        'industry'        => 'nullable|string|max:100',
        'plan_id'         => 'required|string',
        'plan_name'       => 'required|string',
        'plan_price'      => 'required|numeric|min:0',
        'billing_cycle'   => 'required|in:monthly,yearly',
        'email'           => 'required|email|unique:users,email',
        'password'        => [
            'required',
            'string',
            'confirmed',
            \Illuminate\Validation\Rules\Password::min(8)->letters()->numbers(),
        ],
        'name'            => 'required|string|max:255',
        'customer_phone'  => ['nullable', 'string', 'max:25', 'regex:/^\+?[0-9]{7,20}$/'],
    ], [
        'password.*' => 'Password must be at least 8 characters and include letters and numbers.',
        'phone.regex' => 'Company phone format is invalid. Use digits with optional leading +.',
        'customer_phone.regex' => 'Customer phone format is invalid. Use digits with optional leading +.',
    ]);

    // Server-side guard: never trust client-submitted plan values.
    // Canonicalize plan fields from allowed map / DB, and reject tampered payloads.
    $validated = $this->normalizeDeploymentPlanPayload($validated);

    $manager = auth()->user();

    // ── Run DB work inside transaction ──────────────────────
    // session() is NOT called inside — that was causing the bug
    try {
        $result = DB::transaction(function () use ($validated, $manager) {

            // 1. Create customer user
            $customer = User::create([
                'name'              => $validated['name'],
                'email'             => $validated['email'],
                'password'          => Hash::make($validated['password']),
                'role'              => 'admin',
                'is_verified'       => 0,
                'status'            => 'pending',
                'phone'             => $validated['customer_phone'] ?? null,
                'email_verified_at' => null,
            ]);

            // 2. Create company
            $company = Company::create([
                'domain_prefix' => $validated['subdomain'],
                'company_name'  => $validated['company_name'],
                'name'          => $validated['company_name'],
                'email'         => $validated['email'],
                'phone'         => $validated['phone'] ?? null,
                'industry'      => $validated['industry'] ?? null,
                'status'        => 'pending',
                'plan'          => $validated['plan_name'],
                'user_id'       => $customer->id,
                'owner_id'      => $customer->id,
                'deployed_by'   => $manager->id,
            ]);

            // 3. Link customer → company
            $customer->update(['company_id' => $company->id]);

            // 4. Create subscription
            // deployed_by is stored in DB — used as fallback if session is lost
            $subscription = Subscription::create([
                'user_id'         => $customer->id,
                'company_id'      => $company->id,
                'domain_prefix'   => $validated['subdomain'],
                'plan'            => $validated['plan_name'],
                'plan_id'         => $validated['plan_id'],
                'plan_name'       => $validated['plan_name'],
                'subscriber_name' => $validated['name'],
                'amount'          => $validated['plan_price'],
                'billing_cycle'   => $validated['billing_cycle'],
                'status'          => 'Pending',
                'payment_status'  => 'pending',
                'deployed_by'     => $manager->id,  // KEY: stored in DB for fallback
            ]);

            DB::afterCommit(function () use ($customer, $validated, $manager) {
                SystemEventMailer::notifyRegistration($customer, 'user', [
                    'registered_by' => $manager->email ?? $manager->name ?? 'Deployment Manager',
                    'plan' => $validated['plan_name'] ?? null,
                    'billing_cycle' => $validated['billing_cycle'] ?? null,
                ]);
            });

            DeploymentCompany::updateOrCreate(
                [
                    'manager_id' => $manager->id,
                    'company_id' => $company->id,
                ],
                [
                    'deployment_status'  => 'pending',
                    'manager_commission' => round(((float) $validated['plan_price']) * (self::COMMISSION_RATE / 100), 2),
                    'setup_config'       => [
                        'plan_id' => $validated['plan_id'],
                        'plan' => $validated['plan_name'],
                        'billing_cycle' => $validated['billing_cycle'],
                    ],
                ]
            );

            Log::info('Deployment customer created', [
                'manager_id'      => $manager->id,
                'customer_id'     => $customer->id,
                'subscription_id' => $subscription->id,
            ]);

            return [
                'subscription_id' => $subscription->id,
                'customer_id'     => $customer->id,
                'company_id'      => $company->id,
            ];
        });

    } catch (\Illuminate\Validation\ValidationException $e) {
        return back()->withErrors($e->errors())->withInput();
    } catch (\Illuminate\Database\QueryException $e) {
        Log::error('DB error creating deployment customer', ['error' => $e->getMessage()]);
        return back()->with('error', 'Database error: ' . $e->getMessage())->withInput();
    } catch (\Exception $e) {
        Log::error('Failed to create deployment customer', [
            'error' => $e->getMessage(),
            'line'  => $e->getLine(),
        ]);
        return back()->with('error', 'Registration failed: ' . $e->getMessage())->withInput();
    }

    // ── FIX: Set session AFTER transaction completes successfully ──
    // This guarantees the session data is written and readable
    // by the checkout and payment controllers.
    session([
        'checkout_from_deployment'   => true,
        'deployment_customer_id'     => $result['customer_id'],
        'deployment_company_id'      => $result['company_id'],
        'deployment_manager_id'      => $manager->id,
        'deployment_manager_email'   => $manager->email,
        'deployment_commission_rate' => 35,
        'deployment_plan_name'       => $validated['plan_name'],
        'deployment_subscription_id' => $result['subscription_id'],
    ]);

    Log::info('Deployment session set', [
        'manager_id'      => $manager->id,
        'subscription_id' => $result['subscription_id'],
        'session_id'      => session()->getId(),
    ]);

    // Keep deployment manager authenticated through checkout.
    // This prevents identity/sidebar drift if checkout fails midway.
    Log::info('Deployment manager remains authenticated for checkout handoff', [
        'manager_id'      => $manager->id,
        'customer_id'     => $result['customer_id'],
        'subscription_id' => $result['subscription_id'],
        'session_id'      => session()->getId(),
    ]);

    // ── Redirect directly to checkout — clean URL, no extra params ──
    return redirect()->route('saas.checkout', $result['subscription_id'])
        ->with('success', 'Customer account created. Continue checkout to activate workspace.');
}

private function normalizePhoneForStorage(?string $phone): ?string
{
    $raw = trim((string) $phone);
    if ($raw === '') {
        return null;
    }

    $hasPlus = str_starts_with($raw, '+');
    $digitsOnly = preg_replace('/\D+/', '', $raw) ?? '';
    if ($digitsOnly === '') {
        return null;
    }

    return $hasPlus ? ('+' . $digitsOnly) : $digitsOnly;
}

private function normalizeDeploymentPlanPayload(array $validated): array
{
    $submittedPlanId = strtolower(trim((string) ($validated['plan_id'] ?? '')));
    $submittedCycle  = strtolower(trim((string) ($validated['billing_cycle'] ?? '')));
    $submittedName   = trim((string) ($validated['plan_name'] ?? ''));
    $submittedPrice  = (float) ($validated['plan_price'] ?? 0);

    $allowedPlans = [
        'basic-monthly' => ['name' => 'Basic',        'price' => 3000.0,   'billing_cycle' => 'monthly'],
        'professional-monthly' => ['name' => 'Professional', 'price' => 7000.0,   'billing_cycle' => 'monthly'],
        'enterprise-monthly' => ['name' => 'Enterprise',   'price' => 15000.0,  'billing_cycle' => 'monthly'],
        'basic-yearly' => ['name' => 'Basic',        'price' => 30000.0,  'billing_cycle' => 'yearly'],
        'professional-yearly' => ['name' => 'Professional', 'price' => 70000.0,  'billing_cycle' => 'yearly'],
        'enterprise-yearly' => ['name' => 'Enterprise',   'price' => 150000.0, 'billing_cycle' => 'yearly'],
    ];

    // Preferred path: known UI plan ids.
    if (isset($allowedPlans[$submittedPlanId])) {
        $canon = $allowedPlans[$submittedPlanId];
        $sameName = strcasecmp($submittedName, $canon['name']) === 0;
        $sameCycle = $submittedCycle === $canon['billing_cycle'];
        $samePrice = abs($submittedPrice - $canon['price']) < 0.01;

        if (!$sameName || !$sameCycle || !$samePrice) {
            throw \Illuminate\Validation\ValidationException::withMessages([
                'plan_id' => ['Plan selection is invalid or has been modified. Please select a plan again.'],
            ]);
        }

        $validated['plan_name'] = $canon['name'];
        $validated['plan_price'] = $canon['price'];
        $validated['billing_cycle'] = $canon['billing_cycle'];
        return $validated;
    }

    // Fallback path: numeric DB plan id if available.
    if (ctype_digit($submittedPlanId)) {
        $plan = Plan::find((int) $submittedPlanId);
        if ($plan) {
            $dbName = (string) $plan->name;
            $dbCycle = strtolower((string) ($plan->billing_cycle ?? $submittedCycle));
            $dbPrice = (float) $plan->price;

            $sameName = strcasecmp($submittedName, $dbName) === 0;
            $sameCycle = $submittedCycle === $dbCycle;
            $samePrice = abs($submittedPrice - $dbPrice) < 0.01;

            if (!$sameName || !$sameCycle || !$samePrice) {
                throw \Illuminate\Validation\ValidationException::withMessages([
                    'plan_id' => ['Plan details do not match server records. Please reselect the plan.'],
                ]);
            }

            $validated['plan_name'] = $dbName;
            $validated['plan_price'] = $dbPrice;
            $validated['billing_cycle'] = $dbCycle;
            return $validated;
        }
    }

    throw \Illuminate\Validation\ValidationException::withMessages([
        'plan_id' => ['Unsupported plan selected. Please choose a valid plan.'],
    ]);
}


    /**
     * Display a listing of companies.
     */
    public function companiesIndex(Request $request)
    {
        $companies = $this->managedCompanies()
            ->with(['user', 'subscription'])
            ->when($request->search, function($query, $search) {
                return $query->where(function ($subQuery) use ($search) {
                    $subQuery->where('name', 'LIKE', "%{$search}%")
                        ->orWhere('domain_prefix', 'LIKE', "%{$search}%");
                });
            })
            ->latest()
            ->paginate(10);

        return view('deployment.companies.index', compact('companies'));
    }

    /**
     * Active Companies
     */
    public function activeCompanies()
    {
        $companies = $this->managedCompanies()
            ->whereRaw('LOWER(status) = ?', ['active'])
            ->with(['subscription', 'user'])
            ->latest()
            ->paginate(10);

        // Point to the same index view to avoid "View not found" or "Undefined variable"
        return view('deployment.companies.index', compact('companies'));
    }

    /**
     * Pending Companies
     */
    public function pendingCompanies()
    {
        $companies = $this->managedCompanies()
            ->whereRaw('LOWER(status) = ?', ['pending'])
            ->with(['subscription', 'user'])
            ->latest()
            ->paginate(10);

        return view('deployment.companies.index', compact('companies'));
    }

    public function viewCompany($id) {
        $company = $this->managedCompanies()->with('subscription', 'users')->findOrFail($id);
        return view('deployment.companies.view', compact('company'));
    }

    public function editCompany($id) {
        $company = $this->managedCompanies()->findOrFail($id);
        return view('deployment.companies.edit', compact('company'));
    }

    public function updateCompany(Request $request, $id) {
        $company = $this->managedCompanies()->findOrFail($id);
        $company->update($request->only(['name', 'email', 'phone', 'address']));
        return redirect()->route('deployment.companies.view', $id)->with('success', 'Updated.');
    }

    public function deleteCompany($id) {
        $company = $this->managedCompanies()->findOrFail($id);
        DB::transaction(function() use ($id, $company) {
            DB::table('deployment_companies')->where('company_id', $id)->delete();
            Subscription::where('company_id', $id)->delete();
            User::where('company_id', $id)->delete();
            $company->delete();
        });
        return redirect()->route('deployment.companies.index')->with('success', 'Company Removed.');
    }

    public function suspendCompany($id) {
        $this->managedCompanies()->findOrFail($id)->update(['status' => 'suspended']);
        return back()->with('success', 'Suspended.');
    }

    public function activateCompany($id) {
        $this->managedCompanies()->findOrFail($id)->update(['status' => 'active']);
        return back()->with('success', 'Activated.');
    }

    // =============================================================
    // 5. SUBSCRIPTION MANAGEMENT
    // =============================================================

    public function subscriptionOverview() {
        $subscriptions = $this->managedSubscriptions()
            ->with(['company', 'user'])
            ->latest('end_date')
            ->paginate(15);

        $stats = [
            'total_revenue' => (clone $this->managedSubscriptions())
                ->whereRaw('LOWER(payment_status) = ?', ['paid'])
                ->sum('amount'),
            'active_count' => (clone $this->managedSubscriptions())
                ->whereRaw('LOWER(status) = ?', ['active'])
                ->count(),
            'expiring_count' => (clone $this->managedSubscriptions())
                ->whereRaw('LOWER(status) = ?', ['active'])
                ->whereDate('end_date', '<=', now()->addDays(30))
                ->whereDate('end_date', '>=', now())
                ->count(),
            'pending_count' => (clone $this->managedSubscriptions())
                ->where(function ($q) {
                    $q->whereRaw('LOWER(status) IN (?, ?)', ['pending', 'awaiting payment'])
                        ->orWhereRaw('LOWER(payment_status) IN (?, ?)', ['pending', 'unpaid']);
                })
                ->count(),
        ];

        return view('deployment.subscriptions.overview', compact('subscriptions', 'stats'));
    }

    public function subscriptionRenewals()
    {
        $renewals = $this->managedSubscriptions()
            ->with(['company', 'user'])
            ->whereRaw('LOWER(status) IN (?, ?)', ['active', 'expiring'])
            ->orderBy('end_date', 'asc')
            ->paginate(15);

        return view('deployment.subscriptions.renewals', compact('renewals'));
    }

    public function renewSubscription($id) {
        $subscription = $this->managedSubscriptions()->findOrFail($id);
        $newEnd = Carbon::parse($subscription->end_date)->addMonths(1);
        $subscription->update(['end_date' => $newEnd, 'status' => 'active']);
        Company::find($subscription->company_id)?->update(['subscription_end' => $newEnd]);
        return back()->with('success', 'Renewed by 1 month.');
    }

    public function expiringSubscriptions() 
    {
        $renewals = $this->managedSubscriptions()
            ->with(['company', 'user'])
            ->whereRaw('LOWER(status) = ?', ['active'])
            ->where('end_date', '<=', now()->addDays(30))
            ->paginate(15);

        return view('deployment.subscriptions.expiring', compact('renewals'));
    }

    public function subscriptionHistory($id) {
        $subscription = $this->managedSubscriptions()->with('company')->findOrFail($id);
        $history = ActivityLog::where('module', 'deployment')->where('description', 'like', "%{$subscription->company->name}%")->latest()->get();
        return view('deployment.subscriptions.history', compact('subscription', 'history'));
    }

    // =============================================================
    // 6. USER MANAGEMENT
    // =============================================================

    public function usersIndex() 
    {
        $user = Auth::user();
        if ($user->role === 'super_admin' || $user->hasRole('super_admin')) {
            $users = DeploymentManager::with('user')->latest()->paginate(10);
        } else {
            $users = User::whereIn('company_id', $this->managedCompanyIds())
                ->with('company')->latest()->paginate(10);
        }
        return view('deployment.users.index', compact('users'));
    }

    public function createUser() {
        $companies = $this->managedCompanies()->get();
        return view('deployment.users.create', compact('companies'));
    }

    public function storeUser(Request $request) {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'role' => 'required|string|max:80',
            'company_id' => 'required|integer|exists:companies,id',
        ]);

        if (!in_array((int) $validated['company_id'], $this->managedCompanyIds(), true)) {
            abort(403);
        }

        $tempPassword = Str::random(12);
        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'role' => $validated['role'],
            'company_id' => $validated['company_id'],
            'status' => 'active',
            'is_verified' => 1,
            'email_verified_at' => now(),
            'password' => Hash::make($tempPassword),
        ]);

        $company = Company::find($validated['company_id']);
        $loginUrl = route('saas-login');

        try {
            Mail::send('emails.customer-welcome', [
                'email' => $user->email,
                'password' => $tempPassword,
                'name' => $user->name,
                'workspaceUrl' => $loginUrl,
                'companyName' => $company?->company_name ?? $company?->name ?? 'SmartProbook',
            ], fn($m) => $m->to($user->email, $user->name)->subject('Your SmartProbook Login Credentials'));
        } catch (\Exception $e) {
            Log::error('Deployment user welcome email failed', ['error' => $e->getMessage(), 'user_id' => $user->id]);
        }

        return redirect()->route('deployment.users.index')->with('success', 'User created and credentials sent by email.');
    }

    public function viewUser($id) {
        $user = User::whereIn('company_id', $this->managedCompanyIds())->with('company')->findOrFail($id);
        return view('deployment.users.view', compact('user'));
    }

    public function editUser($id) {
        $user = User::whereIn('company_id', $this->managedCompanyIds())->findOrFail($id);
        $companies = $this->managedCompanies()->get();
        return view('deployment.users.edit', compact('user', 'companies'));
    }

    public function updateUser(Request $request, $id) {
        $user = User::whereIn('company_id', $this->managedCompanyIds())->findOrFail($id);
        $user->update($request->only(['name', 'email', 'role']));
        return redirect()->route('deployment.users.view', $id)->with('success', 'User Updated.');
    }

    public function activateUser($id) {
        $record = DeploymentManager::find($id) ?: User::findOrFail($id);
        $record->update(['status' => 'active']);
        return back()->with('success', 'User has been activated.');
    }

    public function suspendUser($id) {
        $record = DeploymentManager::find($id) ?: User::findOrFail($id);
        $record->update(['status' => 'suspended']);
        return back()->with('success', 'User has been suspended.');
    }

    public function deactivateUser($id) {
        $record = DeploymentManager::find($id) ?: User::findOrFail($id);
        $record->update(['status' => 'inactive']);
        return back()->with('success', 'User has been deactivated.');
    }

    // =============================================================
    // 7. FINANCIALS
    // =============================================================

    public function commissionsIndex() {
        $manager = DeploymentManager::where('user_id', Auth::id())->first();
        $rate = $manager->commission_rate ?? self::COMMISSION_RATE;
        $commissions = DB::table('deployment_commissions')
            ->leftJoin('users', 'deployment_commissions.manager_id', '=', 'users.id')
            ->join('subscriptions', 'deployment_commissions.subscription_id', '=', 'subscriptions.id')
            ->join('companies', 'deployment_commissions.company_id', '=', 'companies.id')
            ->where('deployment_commissions.manager_id', Auth::id())
            ->select([
                'deployment_commissions.*',
                'subscriptions.plan',
                'subscriptions.billing_cycle',
                'companies.name as company_name',
                'companies.domain_prefix as subdomain',
                'users.name as manager_name',
                'users.email as manager_email',
            ])
            ->latest('deployment_commissions.created_at')->get();

        $amountColumn = \Illuminate\Support\Facades\Schema::hasColumn('deployment_commissions', 'commission_amount')
            ? 'commission_amount'
            : 'amount';
        $totalCommission = $commissions->sum($amountColumn);
        $paidCommissions = $commissions->whereIn('status', ['credited', 'paid'])->sum($amountColumn);
        $pendingCommission = $commissions->where('status', 'pending')->sum($amountColumn);
        $pendingCommissions = $pendingCommission;
        $totalCommissions = $totalCommission;

        return view('deployment.commissions.index', compact(
            'commissions',
            'rate',
            'totalCommission',
            'totalCommissions',
            'pendingCommission',
            'pendingCommissions',
            'paidCommissions'
        ));
    }

    public function commissionDetails($id) {
        $commission = DB::table('deployment_commissions')
            ->join('subscriptions', 'deployment_commissions.subscription_id', '=', 'subscriptions.id')
            ->join('companies', 'deployment_commissions.company_id', '=', 'companies.id')
            ->where('deployment_commissions.id', $id)
            ->where('deployment_commissions.manager_id', Auth::id())
            ->select('deployment_commissions.*', 'subscriptions.plan', 'companies.name as company_name')->first();
        return view('deployment.commissions.details', compact('commission'));
    }

    public function pendingCommissions() { 
        $commissions = DB::table('deployment_commissions')
            ->join('subscriptions', 'deployment_commissions.subscription_id', '=', 'subscriptions.id')
            ->join('companies', 'deployment_commissions.company_id', '=', 'companies.id')
            ->where('deployment_commissions.manager_id', Auth::id())
            ->where('deployment_commissions.status', 'pending')
            ->select('deployment_commissions.*', 'subscriptions.plan', 'companies.name as company_name')->get();
        return view('deployment.commissions.pending', compact('commissions')); 
    }

    public function paidCommissions() { 
        $commissions = DB::table('deployment_commissions')
            ->join('subscriptions', 'deployment_commissions.subscription_id', '=', 'subscriptions.id')
            ->join('companies', 'deployment_commissions.company_id', '=', 'companies.id')
            ->where('deployment_commissions.manager_id', Auth::id())
            ->whereIn('deployment_commissions.status', ['credited', 'paid'])
            ->select('deployment_commissions.*', 'subscriptions.plan', 'companies.name as company_name')->get();
        return view('deployment.commissions.paid', compact('commissions')); 
    }
    
    public function invoicesIndex() {
        $invoices = $this->managedSubscriptions()
            ->with('company')
            ->whereRaw('LOWER(payment_status) = ?', ['paid'])
            ->latest()
            ->paginate(20);
        return view('deployment.invoices.index', compact('invoices'));
    }

    public function createInvoice() {
        $companies = $this->managedCompanies()->get();
        return view('deployment.invoices.create', compact('companies'));
    }

    public function storeInvoice(Request $request) {
        return redirect()->route('deployment.invoices.index')->with('success', 'Invoice created');
    }

    public function viewInvoice($id) {
        $invoice = $this->managedSubscriptions()->findOrFail($id);
        return view('deployment.invoices.view', compact('invoice'));
    }

    public function downloadInvoice($id) {
        $invoice = $this->managedSubscriptions()->findOrFail($id);
        return view('SuperAdmin.subscriptions.print', compact('invoice'));
    }

    public function paymentsIndex() { 
        $payments = $this->managedSubscriptions()->with('company')->latest()->paginate(20);
        return view('deployment.payments.index', compact('payments')); 
    }

    public function viewPayment($id) {
        $payment = $this->managedSubscriptions()->findOrFail($id);
        return view('deployment.payments.view', compact('payment'));
    }

    public function pendingPayments() { 
        $payments = $this->managedSubscriptions()
            ->with('company')
            ->whereRaw('LOWER(payment_status) IN (?, ?)', ['pending', 'unpaid'])
            ->latest()
            ->paginate(20);
        return view('deployment.payments.pending', compact('payments')); 
    }

    public function completedPayments() { 
        $payments = $this->managedSubscriptions()
            ->with('company')
            ->whereRaw('LOWER(payment_status) = ?', ['paid'])
            ->latest()
            ->paginate(20);
        return view('deployment.payments.completed', compact('payments')); 
    }

    // =============================================================
    // 8. REPORTS & ANALYTICS
    // =============================================================

    public function performanceReport() {
        $report = [
            'totalDeployed' => count($this->managedCompanyIds()),
            'activeNow' => $this->managedCompanies()->whereRaw('LOWER(status) = ?', ['active'])->count(),
            'pendingNow' => $this->managedCompanies()->whereRaw('LOWER(status) = ?', ['pending'])->count(),
            'suspendedNow' => $this->managedCompanies()->whereRaw('LOWER(status) = ?', ['suspended'])->count(),
        ];

        $recentCompanies = $this->managedCompanies()
            ->with('subscription')
            ->latest()
            ->limit(10)
            ->get();

        return view('deployment.reports.performance', compact('report', 'recentCompanies'));
    }

    public function clientActivityReport() {
        $activities = ActivityLog::where('user_id', Auth::id())
            ->where(function ($q) {
                $q->where('module', 'deployment')->orWhereNull('module');
            })
            ->latest()
            ->paginate(20);

        return view('deployment.reports.client-activity', compact('activities'));
    }

    public function revenueReport() {
        $rows = $this->managedSubscriptions()
            ->whereRaw('LOWER(payment_status) = ?', ['paid'])
            ->select(
                DB::raw('DATE_FORMAT(created_at, "%b") as month'),
                DB::raw('MONTH(created_at) as month_num'),
                DB::raw('SUM(amount) as total')
            )
            ->whereYear('created_at', now()->year)
            ->groupBy('month', 'month_num')
            ->orderBy('month_num')
            ->get();

        $totalRevenue = $rows->sum('total');
        $paidCount = $this->managedSubscriptions()->whereRaw('LOWER(payment_status) = ?', ['paid'])->count();

        return view('deployment.reports.revenue', compact('rows', 'totalRevenue', 'paidCount'));
    }

    public function customReport() {
        $statusSummary = $this->managedSubscriptions()
            ->select('status', DB::raw('COUNT(*) as total'), DB::raw('SUM(amount) as amount_total'))
            ->groupBy('status')
            ->orderBy('status')
            ->get();

        $cycleSummary = $this->managedSubscriptions()
            ->select('billing_cycle', DB::raw('COUNT(*) as total'), DB::raw('SUM(amount) as amount_total'))
            ->groupBy('billing_cycle')
            ->orderBy('billing_cycle')
            ->get();

        return view('deployment.reports.custom', compact('statusSummary', 'cycleSummary'));
    }

    public function analytics()
    {
        $now = Carbon::now();
        $periodDays = (int) request('period', 30);
        $from = $now->copy()->subDays($periodDays)->startOfDay();

        $companiesQuery = $this->managedCompanies();
        $subscriptionsQuery = $this->managedSubscriptions();

        $totalCompanies = (clone $companiesQuery)->count();
        $pendingApprovals = (clone $companiesQuery)->where('status', 'pending')->count();
        $totalUsers = User::whereIn('company_id', $this->managedCompanyIds())->count();
        $totalRevenue = (clone $subscriptionsQuery)
            ->where('payment_status', 'paid')
            ->where('created_at', '>=', $from)
            ->sum('amount');

        $revenueRows = (clone $subscriptionsQuery)
            ->where('payment_status', 'paid')
            ->where('created_at', '>=', $from)
            ->select(
                DB::raw('DATE_FORMAT(created_at, "%b") as month'),
                DB::raw('MONTH(created_at) as month_num'),
                DB::raw('SUM(amount) as total')
            )
            ->groupBy('month', 'month_num')
            ->orderBy('month_num')
            ->get();

        $revenueTrendLabels = $revenueRows->pluck('month')->values();
        $revenueTrendData = $revenueRows->pluck('total')->map(fn ($v) => (float) $v)->values();

        $statusCounts = [
            'active' => (clone $companiesQuery)->where('status', 'active')->count(),
            'pending' => (clone $companiesQuery)->where('status', 'pending')->count(),
            'suspended' => (clone $companiesQuery)->where('status', 'suspended')->count(),
        ];

        $topCompanies = (clone $companiesQuery)
            ->withCount('users')
            ->with(['subscription'])
            ->withSum(['subscription as payments_sum_amount' => function ($q) {
                $q->where('payment_status', 'paid');
            }], 'amount')
            ->orderByDesc('payments_sum_amount')
            ->limit(10)
            ->get();

        // Backward-compatible variables used by older cards/sections.
        $revenueTrends = $revenueRows;
        $planStats = (clone $companiesQuery)
            ->select('plan', DB::raw('COUNT(*) as total'))
            ->groupBy('plan')
            ->pluck('total', 'plan')
            ->toArray();
        $statusStats = (clone $companiesQuery)
            ->select('status', DB::raw('COUNT(*) as total'))
            ->groupBy('status')
            ->pluck('total', 'status')
            ->toArray();

        return view('deployment.analytics', compact(
            'totalCompanies',
            'totalUsers',
            'totalRevenue',
            'pendingApprovals',
            'revenueTrendLabels',
            'revenueTrendData',
            'statusCounts',
            'topCompanies',
            'revenueTrends',
            'planStats',
            'statusStats'
        ));
    }

    public function exportData()
    {
        $fileName = 'managers_export_' . date('Y-m-d') . '.csv';
        $managers = DeploymentManager::with('user')->get();
        $headers = ["Content-type" => "text/csv", "Content-Disposition" => "attachment; filename=$fileName"];
        $callback = function() use($managers) {
            $file = fopen('php://output', 'w');
            fputcsv($file, ['ID', 'Name', 'Email', 'Status', 'Created At']);
            foreach ($managers as $m) fputcsv($file, [$m->id, $m->user->name ?? 'N/A', $m->user->email ?? 'N/A', $m->status, $m->created_at]);
            fclose($file);
        };
        return response()->stream($callback, 200, $headers);
    }

    public function generateExport() { return $this->exportData(); }

    // =============================================================
    // 9. SUPPORT & SETTINGS
    // =============================================================

    public function supportTickets() { return view('deployment.support.tickets'); }
    public function createTicket() { return view('deployment.support.create-ticket'); }
    public function storeTicket(Request $request) { return redirect()->route('deployment.support.tickets')->with('success', 'Ticket created'); }
    public function viewTicket($id) { return view('deployment.support.view-ticket', compact('id')); }
    public function replyTicket(Request $request, $id) { return back()->with('success', 'Reply sent'); }
    public function helpCenter() { return view('deployment.help.index'); }
    public function helpCategory($category) { return view('deployment.help.category', compact('category')); }
    public function helpArticle($slug) { return view('deployment.help.article', compact('slug')); }
    public function notifications() { return view('deployment.notifications.index'); }
    public function markNotificationRead($id) { return back()->with('success', 'Read'); }
    public function markAllNotificationsRead() { return back()->with('success', 'All Read'); }
    public function deleteNotification($id) { return back()->with('success', 'Deleted'); }

    public function profile() {
        return view('deployment.profile', [
            'user' => Auth::user(), 
            'manager' => DeploymentManager::where('user_id', Auth::id())->first()
        ]);
    }

    public function updateProfile(Request $request) {
        Auth::user()->update($request->only(['name', 'email']));
        return back()->with('success', 'Profile Updated.');
    }

    public function updateAvatar(Request $request) { return back()->with('success', 'Avatar updated'); }

    public function settings() { return view('deployment.settings'); }

    public function updateSettings(Request $request) { return back()->with('success', 'Settings updated'); }

    public function updatePassword(Request $request) { return back()->with('success', 'Password updated'); }

    // =============================================================
    // 10. ROUTE-COMPATIBILITY ALIASES
    // =============================================================

    public function createCompany()
    {
        return redirect()->route('deployment.customers.create');
    }

    public function show($id)
    {
        return $this->viewUser($id);
    }

    public function edit($id)
    {
        return $this->editUser($id);
    }

    public function update(Request $request, $id)
    {
        return $this->updateUser($request, $id);
    }

    public function activate($id)
    {
        return $this->activateUser($id);
    }

    public function suspend($id)
    {
        return $this->suspendUser($id);
    }

    public function deactivate($id)
    {
        return $this->deactivateUser($id);
    }

    public function processPayment(Request $request, $id)
    {
        return app(\App\Http\Controllers\SubscriptionController::class)->processPayment($request, $id);
    }

    public function receipt($id)
    {
        return redirect()->route('saas.success', ['id' => $id]);
    }
}
