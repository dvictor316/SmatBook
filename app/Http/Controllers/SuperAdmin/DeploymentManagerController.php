<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\{Auth, DB, Hash, Mail, Log, Storage, Schema};
use Illuminate\Support\{Str, Carbon};
use Illuminate\Validation\Rule;
use App\Models\{Company, User, Subscription, ActivityLog, DeploymentManager, DeploymentCompany, Plan};
use App\Models\DeploymentManagerPayout;
use App\Models\Role;
use App\Support\{SystemEventMailer, DeploymentCommissionPayoutService};

class DeploymentManagerController extends Controller
{
    public function __construct(
        private readonly DeploymentCommissionPayoutService $deploymentCommissionPayouts
    ) {
    }

    private array $paidStatuses = ['paid', 'completed', 'success', 'successful', 'verified'];
    private array $activeStatuses = ['active', 'trial'];
    private array $pendingStatuses = ['pending', 'awaiting payment', 'awaiting_payment', 'unpaid'];


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

    private function deploymentRoleOptions(): array
    {
        if (Schema::hasTable('roles')) {
            $roles = Role::query()
                ->orderBy('name')
                ->pluck('name')
                ->filter()
                ->values()
                ->all();

            if ($roles !== []) {
                return $roles;
            }
        }

        return [
            'Administrator',
            'Store Manager',
            'Sales Manager',
            'Finance Manager',
            'Account Officer',
            'Cashier',
        ];
    }

    private function deploymentHelpArticles(): array
    {
        return [
            'onboarding' => [
                [
                    'slug' => 'registering-a-new-client',
                    'title' => 'Registering a New Client',
                    'summary' => 'Walk through customer registration, plan selection, workspace setup, and payment handoff.',
                    'content' => 'Use the Register Customer flow to create the customer account, assign a valid subscription plan, and generate the secure checkout handoff. Confirm the company name, subdomain, customer email, and billing cycle before completing the setup.',
                ],
                [
                    'slug' => 'tracking-subscription-renewals',
                    'title' => 'Tracking Subscription Renewals',
                    'summary' => 'Monitor expiring subscriptions and follow up before workspace access is interrupted.',
                    'content' => 'Open the Subscriptions area to review active, expiring, and renewal-ready clients. Focus first on subscriptions expiring within the next 7 days and confirm payment status before starting any renewal conversation.',
                ],
            ],
            'payments' => [
                [
                    'slug' => 'understanding-commission-payouts',
                    'title' => 'Understanding Commission Payouts',
                    'summary' => 'See how available, processing, and paid commissions move through the payout pipeline.',
                    'content' => 'Commissions move from available to processing to paid. Keep payout profile details complete and verified so the system can prepare payout requests without manual cleanup.',
                ],
                [
                    'slug' => 'reviewing-client-payments',
                    'title' => 'Reviewing Client Payments',
                    'summary' => 'Use the payments view to verify successful payments and follow up on pending ones.',
                    'content' => 'The payments views show completed and pending transactions tied to your managed clients. Review pending items daily and confirm whether the customer needs a fresh payment link, invoice, or renewal assistance.',
                ],
            ],
            'support' => [
                [
                    'slug' => 'opening-a-support-ticket',
                    'title' => 'Opening a Support Ticket',
                    'summary' => 'Log deployment issues with enough detail for quick follow-up.',
                    'content' => 'Create a support ticket when a client setup, payment flow, or workspace activation needs internal follow-up. Include the client name, affected workspace, and the exact issue observed so the team can respond quickly.',
                ],
                [
                    'slug' => 'using-deployment-reports',
                    'title' => 'Using Deployment Reports',
                    'summary' => 'Read the deployment reports to monitor performance, activity, and revenue quality.',
                    'content' => 'Use the performance, activity, and revenue reports together. Performance shows throughput, activity shows operational actions, and revenue highlights successful monetization across the clients you manage.',
                ],
            ],
        ];
    }

    // =============================================================
    // 1. VERIFICATION & ONBOARDING
    // =============================================================

    public function showVerificationForm()
    {
        $user = Auth::user();
        $manager = DeploymentManager::where('user_id', $user->id)->first();

        if (($manager && $manager->status === 'active') || $user->is_verified == 1) {
            return redirect()->route('deployment.dashboard')
                ->with('info', 'Your deployment workspace is already verified and active.');
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
        $paidSubscriptions = $this->managedSubscriptions()
            ->where(function ($query) {
                $query->whereIn(DB::raw("LOWER(COALESCE(payment_status, ''))"), $this->paidStatuses);

                if (Schema::hasColumn('subscriptions', 'paid_at')) {
                    $query->orWhereNotNull('paid_at');
                }

                if (Schema::hasColumn('subscriptions', 'payment_date')) {
                    $query->orWhereNotNull('payment_date');
                }
            });

        $totalRevenue = (clone $paidSubscriptions)
            ->whereYear('created_at', $now->year)
            ->whereMonth('created_at', $now->month)
            ->sum('amount');

        $totalCommissions = 0.0;
        $paidCommissions = 0.0;
        $pendingCommissions = 0.0;
        $processingPayouts = 0.0;
        $lastPayout = null;

        if (Schema::hasTable('deployment_commissions')) {
            $payoutSummary = $this->deploymentCommissionPayouts->summaryForManager(Auth::id());
            $paidCommissions = (float) ($payoutSummary['paid'] ?? 0);
            $pendingCommissions = (float) ($payoutSummary['available'] ?? 0);
            $processingPayouts = (float) ($payoutSummary['processing'] ?? 0);
            $lastPayout = $payoutSummary['last_payout'] ?? null;
            $totalCommissions = $paidCommissions + $pendingCommissions + $processingPayouts;
        } else {
            // Fallback for environments that do not have the commission ledger table yet.
            $totalCommissions = ($totalRevenue * $commissionRate) / 100;
        }

        $metrics = [
            'totalCompanies'         => $this->managedCompanies()->count(),
            'activeSubscriptions'    => $this->managedSubscriptions()
                ->whereIn(DB::raw("LOWER(COALESCE(status, ''))"), $this->activeStatuses)
                ->where(function ($query) {
                    $query->whereIn(DB::raw("LOWER(COALESCE(payment_status, ''))"), array_merge($this->paidStatuses, ['free']));
                    if (Schema::hasColumn('subscriptions', 'paid_at')) {
                        $query->orWhereNotNull('paid_at');
                    }
                })
                ->where(function ($q) use ($now) {
                    $q->whereNull('end_date')->orWhere('end_date', '>=', $now);
                })
                ->count(),
            'monthlyRevenue'         => $totalRevenue,
            'pendingApprovals'       => $this->managedCompanies()->whereIn(DB::raw("LOWER(COALESCE(status, ''))"), ['pending', 'awaiting approval'])->count(),
            'trialCount'             => $this->managedCompanies()->whereIn(DB::raw("LOWER(COALESCE(status, ''))"), ['trial'])->count(),
            'pendingPayments'        => $this->managedSubscriptions()->whereIn(DB::raw("LOWER(COALESCE(payment_status, ''))"), $this->pendingStatuses)->count(),
            'pendingPaymentsValue'   => $this->managedSubscriptions()->whereIn(DB::raw("LOWER(COALESCE(payment_status, ''))"), $this->pendingStatuses)->sum('amount'),
            'commissionRate'         => $commissionRate,
            'totalCommissions'       => $totalCommissions,
            'paidCommissions'        => $paidCommissions,
            'pendingCommissions'     => $pendingCommissions,
            'processingPayouts'      => $processingPayouts,
            'expiringSoonSubscriptions' => $this->expiringManagedSubscriptionsQuery(7)->count(),
            'expiredSubscriptions'   => $this->managedSubscriptions()
                ->whereRaw("LOWER(COALESCE(status, '')) = 'expired'")
                ->count(),
            'autoPayoutEnabled'      => (bool) ($managerProfile->auto_payout_enabled ?? false),
            'minimumPayoutAmount'    => (float) ($managerProfile->minimum_payout_amount ?? 0),
            'payoutStatus'           => (string) ($managerProfile->payout_status ?? 'not_configured'),
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

        return view('deployment.dashboard', compact('metrics', 'companies', 'recentSubscriptions', 'recentActivities', 'expiringSubscriptions', 'managerProfile', 'lastPayout'));
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

        $deploymentPlans = $this->deploymentPlanOptions();

        return view('deployment.users.create', compact('limit', 'currentCount', 'deploymentPlans'));
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

    $subdomainRules = ['required', 'string', 'max:50', 'alpha_dash'];
    $companySlugColumn = $this->resolveCompanySlugColumn();
    if ($companySlugColumn !== null) {
        $subdomainRules[] = Rule::unique('companies', $companySlugColumn);
    }

    $validated = $request->validate([
        'company_name'    => 'required|string|max:255',
        'subdomain'       => $subdomainRules,
        'company_email'   => 'nullable|email|max:255',
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
    $validated['company_email'] = $validated['company_email'] ?? $validated['email'];

    $manager = auth()->user();

    // ── Run DB work inside transaction ──────────────────────
    // session() is NOT called inside — that was causing the bug
    try {
        $result = DB::transaction(function () use ($validated, $manager) {

            // 1. Create customer user
            $customer = User::create($this->filterPayloadForTable('users', [
                'name'              => $validated['name'],
                'email'             => $validated['email'],
                'password'          => Hash::make($validated['password']),
                'role'              => 'admin',
                'is_verified'       => 0,
                'status'            => 'pending',
                'phone'             => $validated['customer_phone'] ?? null,
                'email_verified_at' => null,
            ]));

            // 2. Create company
            $company = Company::create($this->filterPayloadForTable('companies', array_merge([
                'domain_prefix' => $validated['subdomain'],
                'subdomain'     => $validated['subdomain'],
                'domain'        => $validated['subdomain'],
                'company_name'  => $validated['company_name'],
                'name'          => $validated['company_name'],
                'email'         => $validated['company_email'],
                'phone'         => $validated['phone'] ?? null,
                'industry'      => $validated['industry'] ?? null,
                'status'        => 'pending',
                'plan'          => $validated['plan_name'],
                'user_id'       => $customer->id,
                'owner_id'      => $customer->id,
                'deployed_by'   => $manager->id,
            ], $this->companySubscriptionColumnsPayload($validated))));

            // 3. Link customer → company
            $customer->update($this->filterPayloadForTable('users', ['company_id' => $company->id]));

            // 4. Create subscription
            // deployed_by is stored in DB — used as fallback if session is lost
            $subscription = Subscription::create($this->filterPayloadForTable('subscriptions', [
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
            ]));

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

private function resolveCompanySlugColumn(): ?string
{
    foreach (['domain_prefix', 'subdomain', 'domain'] as $column) {
        if (Schema::hasColumn('companies', $column)) {
            return $column;
        }
    }

    return null;
}

private function companySubscriptionColumnsPayload(array $validated): array
{
    $payload = [];

    if (Schema::hasColumn('companies', 'subscription_start')) {
        $payload['subscription_start'] = now();
    }

    if (Schema::hasColumn('companies', 'subscription_end')) {
        $payload['subscription_end'] = strtolower((string) $validated['billing_cycle']) === 'yearly'
            ? now()->addYear()
            : now()->addMonth();
    }

    return $payload;
}

private function filterPayloadForTable(string $table, array $payload): array
{
    if (!Schema::hasTable($table)) {
        return $payload;
    }

    return collect($payload)
        ->filter(fn ($_value, $column) => Schema::hasColumn($table, (string) $column))
        ->all();
}

private function normalizeDeploymentPlanPayload(array $validated): array
{
    $submittedPlanId = strtolower(trim((string) ($validated['plan_id'] ?? '')));
    $submittedCycle  = strtolower(trim((string) ($validated['billing_cycle'] ?? '')));
    $submittedName   = trim((string) ($validated['plan_name'] ?? ''));
    $submittedPrice  = (float) ($validated['plan_price'] ?? 0);

    $allowedPlans = collect($this->deploymentPlanOptions())
        ->mapWithKeys(fn (array $plan, string $key) => [
            $key => [
                'name' => $plan['name'],
                'price' => (float) $plan['price'],
                'billing_cycle' => $plan['billing_cycle'],
            ],
        ])
        ->all();

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
        $validated['plan_id'] = $this->resolvePlanIdFromCatalog($canon['name'], $canon['billing_cycle']);
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
            $validated['plan_id'] = (int) $plan->id;
            return $validated;
        }
    }

    throw \Illuminate\Validation\ValidationException::withMessages([
        'plan_id' => ['Unsupported plan selected. Please choose a valid plan.'],
    ]);
}

private function resolvePlanIdFromCatalog(string $planName, string $billingCycle): ?int
{
    $plan = Plan::findByCatalogName($planName, $billingCycle);

    return $plan?->id ? (int) $plan->id : null;
}

private function deploymentPlanDefinitions(): array
{
    return [
        'basic-solo-monthly' => ['name' => 'Basic Solo', 'price' => 3000.0, 'billing_cycle' => 'monthly'],
        'basic-monthly' => ['name' => 'Basic', 'price' => 5500.0, 'billing_cycle' => 'monthly'],
        'professional-solo-monthly' => ['name' => 'Professional Solo', 'price' => 7000.0, 'billing_cycle' => 'monthly'],
        'professional-monthly' => ['name' => 'Professional', 'price' => 19500.0, 'billing_cycle' => 'monthly'],
        'enterprise-solo-monthly' => ['name' => 'Enterprise Solo', 'price' => 15000.0, 'billing_cycle' => 'monthly'],
        'enterprise-monthly' => ['name' => 'Enterprise', 'price' => 28500.0, 'billing_cycle' => 'monthly'],
        'basic-solo-yearly' => ['name' => 'Basic Solo', 'price' => 30000.0, 'billing_cycle' => 'yearly'],
        'basic-yearly' => ['name' => 'Basic', 'price' => 55000.0, 'billing_cycle' => 'yearly'],
        'professional-solo-yearly' => ['name' => 'Professional Solo', 'price' => 70000.0, 'billing_cycle' => 'yearly'],
        'professional-yearly' => ['name' => 'Professional', 'price' => 195000.0, 'billing_cycle' => 'yearly'],
        'enterprise-solo-yearly' => ['name' => 'Enterprise Solo', 'price' => 150000.0, 'billing_cycle' => 'yearly'],
        'enterprise-yearly' => ['name' => 'Enterprise', 'price' => 285000.0, 'billing_cycle' => 'yearly'],
    ];
}

private function deploymentPlanOptions(): array
{
    $definitions = $this->deploymentPlanDefinitions();
    $plans = [];

    foreach ($definitions as $key => $definition) {
        $resolvedPlan = Plan::findByCatalogName($definition['name'], $definition['billing_cycle']);
        $price = (float) ($resolvedPlan?->price ?? $definition['price']);
        $commission = round(($price * self::COMMISSION_RATE) / 100, 2);

        $plans[$key] = [
            'catalog_key' => $key,
            'plan_id' => $resolvedPlan?->id ? (string) $resolvedPlan->id : $key,
            'name' => $resolvedPlan?->name ?? $definition['name'],
            'price' => $price,
            'price_label' => $this->formatDeploymentAmount($price),
            'billing_cycle' => $definition['billing_cycle'],
            'commission_label' => $this->formatDeploymentAmount($commission),
            'save_label' => null,
        ];
    }

    foreach ($plans as $key => &$plan) {
        if ($plan['billing_cycle'] !== 'yearly') {
            continue;
        }

        $monthlyKey = str_replace('-yearly', '-monthly', $key);
        $monthlyPrice = (float) ($plans[$monthlyKey]['price'] ?? 0);
        $saveAmount = max(0, ($monthlyPrice * 12) - (float) $plan['price']);
        $plan['save_label'] = $this->formatDeploymentAmount($saveAmount);
    }
    unset($plan);

    return $plans;
}

private function formatDeploymentAmount(float $amount): string
{
    $precision = abs($amount - round($amount)) < 0.01 ? 0 : 2;

    return number_format($amount, $precision);
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
        $deploymentPlans = $this->deploymentPlanOptions();
        return view('deployment.users.create', compact('companies', 'deploymentPlans'));
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
        $roles = $this->deploymentRoleOptions();
        return view('deployment.users.edit', compact('user', 'companies', 'roles'));
    }

    public function updateUser(Request $request, $id) {
        $user = User::whereIn('company_id', $this->managedCompanyIds())->findOrFail($id);
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255|unique:users,email,' . $user->id,
            'role' => 'required|string|max:80',
        ]);

        $user->update($validated);
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
        $summary = $this->deploymentCommissionPayouts->summaryForManager(Auth::id());
        $paidCommissions = (float) ($summary['paid'] ?? 0);
        $pendingCommission = (float) ($summary['available'] ?? 0);
        $pendingCommissions = $pendingCommission;
        $processingPayouts = (float) ($summary['processing'] ?? 0);
        $failedPayouts = (float) ($summary['failed'] ?? 0);
        $totalCommissions = $totalCommission;
        $recentPayouts = Schema::hasTable('deployment_manager_payouts')
            ? DeploymentManagerPayout::query()->where('manager_id', Auth::id())->latest()->limit(8)->get()
            : collect();

        return view('deployment.commissions.index', compact(
            'commissions',
            'manager',
            'rate',
            'totalCommission',
            'totalCommissions',
            'pendingCommission',
            'pendingCommissions',
            'paidCommissions',
            'processingPayouts',
            'failedPayouts',
            'recentPayouts'
        ));
    }

    public function updatePayoutProfile(Request $request)
    {
        $validated = $request->validate([
            'payout_bank_name' => 'required|string|max:191',
            'payout_bank_code' => 'nullable|string|max:50',
            'payout_account_name' => 'required|string|max:191',
            'payout_account_number' => 'required|string|max:100',
            'payout_provider' => 'required|in:paystack,flutterwave',
            'minimum_payout_amount' => 'nullable|numeric|min:0',
            'auto_payout_enabled' => 'nullable|boolean',
        ]);

        $manager = DeploymentManager::query()->where('user_id', Auth::id())->firstOrFail();
        $providerChanged = strtolower((string) ($manager->payout_provider ?? '')) !== strtolower((string) $validated['payout_provider']);
        $bankChanged = (string) ($manager->payout_account_number ?? '') !== (string) $validated['payout_account_number']
            || (string) ($manager->payout_bank_code ?? '') !== (string) ($validated['payout_bank_code'] ?? '');

        $manager->update([
            'payout_bank_name' => $validated['payout_bank_name'],
            'payout_bank_code' => $validated['payout_bank_code'] ?? null,
            'payout_account_name' => $validated['payout_account_name'],
            'payout_account_number' => $validated['payout_account_number'],
            'payout_provider' => $validated['payout_provider'],
            'minimum_payout_amount' => $validated['minimum_payout_amount'] ?? ($manager->minimum_payout_amount ?? 5000),
            'auto_payout_enabled' => (bool) ($request->boolean('auto_payout_enabled')),
            'payout_status' => !empty($validated['payout_bank_code']) ? 'verified' : 'pending_verification',
            'payout_recipient_code' => ($providerChanged || $bankChanged) ? null : $manager->payout_recipient_code,
        ]);

        try {
            if ($manager->auto_payout_enabled) {
                $this->deploymentCommissionPayouts->attemptAutoPayout($manager->user_id);
            }
        } catch (\Throwable $e) {
            Log::warning('Auto payout did not run after payout profile update.', [
                'manager_id' => $manager->user_id,
                'error' => $e->getMessage(),
            ]);
        }

        return back()->with('success', 'Payout profile updated successfully.');
    }

    public function requestPayout()
    {
        $payout = $this->deploymentCommissionPayouts->createPayoutForManager(Auth::id(), false, Auth::id());

        if (!$payout) {
            return back()->with('error', 'No eligible commission is available for payout yet.');
        }

        if ($payout->status === 'manual_review') {
            return back()->with('info', 'Payout record created for manual review. Complete bank details or gateway setup to continue.');
        }

        return back()->with('success', 'Payout request submitted successfully.');
    }

    public function commissionDetails($id) {
        $commission = DB::table('deployment_commissions')
            ->join('subscriptions', 'deployment_commissions.subscription_id', '=', 'subscriptions.id')
            ->join('companies', 'deployment_commissions.company_id', '=', 'companies.id')
            ->where('deployment_commissions.id', $id)
            ->where('deployment_commissions.manager_id', Auth::id())
            ->select(
                'deployment_commissions.*',
                'subscriptions.plan',
                'subscriptions.billing_cycle',
                'subscriptions.payment_status',
                'subscriptions.start_date',
                'subscriptions.end_date',
                'companies.name as company_name',
                'companies.domain_prefix as company_domain'
            )->first();
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

    public function supportTickets()
    {
        $tickets = ActivityLog::query()
            ->where('user_id', Auth::id())
            ->where('module', 'deployment_support')
            ->latest()
            ->paginate(15);

        return view('deployment.support.tickets', compact('tickets'));
    }

    public function createTicket()
    {
        $managedCompanies = $this->managedCompanies()->orderBy('name')->get();
        return view('deployment.support.create-ticket', compact('managedCompanies'));
    }

    public function storeTicket(Request $request)
    {
        $validated = $request->validate([
            'subject' => 'required|string|max:255',
            'priority' => 'required|in:low,medium,high,urgent',
            'company_id' => 'nullable|integer',
            'message' => 'required|string|min:10',
        ]);

        ActivityLog::create([
            'user_id' => Auth::id(),
            'module' => 'deployment_support',
            'description' => $validated['subject'],
            'action' => 'ticket_created',
            'properties' => json_encode([
                'priority' => $validated['priority'],
                'company_id' => $validated['company_id'] ?? null,
                'message' => $validated['message'],
            ]),
        ]);

        return redirect()->route('deployment.support.tickets')->with('success', 'Support ticket created successfully.');
    }

    public function viewTicket($id)
    {
        $ticket = ActivityLog::query()
            ->where('user_id', Auth::id())
            ->where('module', 'deployment_support')
            ->findOrFail($id);

        return view('deployment.support.view-ticket', compact('ticket'));
    }
    public function replyTicket(Request $request, $id) { return back()->with('success', 'Reply sent'); }
    public function helpCenter()
    {
        $articles = $this->deploymentHelpArticles();
        return view('deployment.help.index', compact('articles'));
    }

    public function helpCategory($category)
    {
        $articles = $this->deploymentHelpArticles();
        $categoryKey = strtolower((string) $category);
        abort_unless(array_key_exists($categoryKey, $articles), 404);

        $categoryArticles = $articles[$categoryKey];
        return view('deployment.help.category', compact('category', 'categoryArticles'));
    }

    public function helpArticle($slug)
    {
        $article = collect($this->deploymentHelpArticles())
            ->flatten(1)
            ->firstWhere('slug', $slug);

        abort_unless($article, 404);

        return view('deployment.help.article', compact('article'));
    }

    public function notifications()
    {
        $user = Auth::user();
        $notifications = $user->notifications()->latest()->paginate(15);
        $unreadCount = $user->unreadNotifications()->count();

        return view('deployment.notifications.index', compact('notifications', 'unreadCount'));
    }

    public function markNotificationRead($id)
    {
        $notification = Auth::user()->notifications()->where('id', $id)->firstOrFail();
        if (!$notification->read_at) {
            $notification->markAsRead();
        }

        return back()->with('success', 'Notification marked as read.');
    }

    public function markAllNotificationsRead()
    {
        Auth::user()->unreadNotifications->markAsRead();
        return back()->with('success', 'All notifications marked as read.');
    }
    public function deleteNotification($id) { return back()->with('success', 'Deleted'); }

    public function profile() {
        $user = Auth::user();
        $manager = DeploymentManager::where('user_id', Auth::id())->first();
        $stats = [
            'managed_companies' => count($this->managedCompanyIds()),
            'active_subscriptions' => $this->managedSubscriptions()->whereRaw("LOWER(COALESCE(status, '')) IN ('active','trial')")->count(),
            'pending_payments' => $this->managedSubscriptions()->whereRaw("LOWER(COALESCE(payment_status, '')) IN ('pending','unpaid')")->count(),
        ];

        return view('deployment.profile', [
            'user' => $user,
            'manager' => $manager,
            'stats' => $stats,
        ]);
    }

    public function updateProfile(Request $request) {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255|unique:users,email,' . Auth::id(),
            'business_name' => 'nullable|string|max:255',
            'phone' => 'nullable|string|max:25',
            'address' => 'nullable|string|max:500',
        ]);

        $user = Auth::user();
        $user->update([
            'name' => $validated['name'],
            'email' => $validated['email'],
        ]);

        $manager = DeploymentManager::where('user_id', $user->id)->first();
        if ($manager) {
            $manager->update([
                'business_name' => $validated['business_name'] ?? $manager->business_name,
                'phone' => $validated['phone'] ?? $manager->phone,
                'address' => $validated['address'] ?? $manager->address,
            ]);
        }

        return back()->with('success', 'Profile Updated.');
    }

    public function updateAvatar(Request $request)
    {
        $request->validate([
            'profile_photo' => 'required|image|max:2048',
        ]);

        $user = Auth::user();

        if (!empty($user->profile_photo)) {
            Storage::disk('public')->delete($user->profile_photo);
        }

        $user->update([
            'profile_photo' => $request->file('profile_photo')->store('profiles', 'public'),
        ]);

        return back()->with('success', 'Avatar updated successfully.');
    }

    public function settings()
    {
        $user = Auth::user();
        $manager = DeploymentManager::where('user_id', $user->id)->first();
        $settings = [
            'notify_deploy' => Schema::hasColumn('users', 'notify_deploy') ? (bool) ($user->notify_deploy ?? false) : false,
            'auto_payout_enabled' => (bool) ($manager->auto_payout_enabled ?? false),
            'minimum_payout_amount' => (float) ($manager->minimum_payout_amount ?? 5000),
            'payout_provider' => $manager->payout_provider ?? 'paystack',
        ];

        return view('deployment.settings', compact('user', 'manager', 'settings'));
    }

    public function updateSettings(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'notify_deploy' => 'nullable|boolean',
            'auto_payout_enabled' => 'nullable|boolean',
            'minimum_payout_amount' => 'nullable|numeric|min:0',
            'payout_provider' => 'nullable|in:paystack,flutterwave',
        ]);

        $user = Auth::user();
        $user->name = $validated['name'];
        if (Schema::hasColumn('users', 'notify_deploy')) {
            $user->notify_deploy = $request->boolean('notify_deploy');
        }
        $user->save();

        $manager = DeploymentManager::where('user_id', $user->id)->first();
        if ($manager) {
            $manager->update([
                'auto_payout_enabled' => $request->boolean('auto_payout_enabled'),
                'minimum_payout_amount' => $validated['minimum_payout_amount'] ?? ($manager->minimum_payout_amount ?? 5000),
                'payout_provider' => $validated['payout_provider'] ?? ($manager->payout_provider ?? 'paystack'),
            ]);
        }

        return back()->with('success', 'Settings updated');
    }

    public function updatePassword(Request $request)
    {
        $validated = $request->validate([
            'password' => 'required|string|min:8|confirmed',
        ]);

        Auth::user()->update([
            'password' => Hash::make($validated['password']),
        ]);

        return back()->with('success', 'Password updated');
    }

    // =============================================================
    // 10. ROUTE-COMPATIBILITY ALIASES
    // =============================================================

    public function createCompany()
    {
        return redirect()->route('deployment.customers.create')
            ->with('info', 'Complete the customer form below to create a new deployment workspace.');
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
        return redirect()->route('saas.success', ['id' => $id])
            ->with('info', 'Subscription receipt opened successfully.');
    }
}
