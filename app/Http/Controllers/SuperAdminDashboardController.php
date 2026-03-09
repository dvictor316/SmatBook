<?php

namespace App\Http\Controllers;

use App\Models\Company;
use App\Models\User;
use App\Models\Subscription;
use App\Models\Product; 
use App\Models\Sale;
use App\Models\DeploymentManager;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use App\Support\SystemEventMailer;

class SuperAdminDashboardController extends Controller
{

    public function index()
    {
        $user = Auth::user();
        if (method_exists(Subscription::class, 'expireDueSubscriptions')) {
            Subscription::expireDueSubscriptions();
        } else {
            Subscription::query()
                ->whereRaw("LOWER(COALESCE(status, '')) IN ('active','trial')")
                ->whereNotNull('end_date')
                ->whereDate('end_date', '<', now()->toDateString())
                ->update([
                    'status' => 'Expired',
                    'updated_at' => now(),
                ]);
        }

        // FIXED: More inclusive security check
        if (!$this->isSuperAdmin($user)) {
            Log::warning('Unauthorized super admin access attempt', [
                'user_id' => $user->id,
                'email' => $user->email,
                'role' => $user->role,
                'role_lowercase' => strtolower($user->role)
            ]);
            
            abort(403, 'Unauthorized access to Master Panel.');
        }

        Log::info('Super admin dashboard accessed successfully', [
            'user_id' => $user->id,
            'email' => $user->email,
            'role' => $user->role
        ]);

        try {
            $deploymentLimit = 50; 

            $salesRevenue = Schema::hasTable('sales') ? ((float) (DB::table('sales')->sum('total') ?? 0)) : 0.0;
            $subscriptionRevenue = Schema::hasTable('subscriptions')
                ? ((float) (Subscription::whereRaw('LOWER(payment_status) = ?', ['paid'])->sum('amount') ?? 0))
                : 0.0;
            $platformRevenue = $subscriptionRevenue > 0 ? $subscriptionRevenue : $salesRevenue;

            $activeSubs = Schema::hasTable('subscriptions')
                ? Subscription::whereRaw('LOWER(status) = ?', ['active'])->count()
                : 0;
            $activeCompanies = Company::whereRaw("LOWER(COALESCE(status, 'active')) = ?", ['active'])->count();

            $stockValue = 0;
            if (Schema::hasTable('products')) {
                $hasProductPrice = Schema::hasColumn('products', 'product_price');
                $hasPrice = Schema::hasColumn('products', 'price');
                $priceExpr = $hasProductPrice ? 'product_price' : ($hasPrice ? 'price' : '0');
                $stockValue = (float) (DB::table('products')
                    ->selectRaw("SUM(COALESCE({$priceExpr}, 0) * COALESCE(stock, 0)) as total_stock_value")
                    ->value('total_stock_value') ?? 0);
            }

            // METRICS
            $metrics = [
                'total_companies'  => Company::count(), 
                'total_tenants'    => $activeCompanies > 0 ? $activeCompanies : Company::count(),
                'total_users'      => User::count(),
                'verified_users'   => Schema::hasColumn('users', 'is_verified') ? User::where('is_verified', 1)->count() : 0,
                'active_subs'      => $activeSubs > 0 ? $activeSubs : $activeCompanies,
                'paid_subs'        => Schema::hasTable('subscriptions')
                                      ? Subscription::whereRaw('LOWER(payment_status) = ?', ['paid'])->count()
                                      : 0,
                'total_subs'       => Schema::hasTable('subscriptions')
                                      ? Subscription::count()
                                      : 0,
                'platform_revenue' => $platformRevenue,
                'pending_setups'   => Schema::hasTable('subscriptions')
                                      ? Subscription::whereRaw('LOWER(status) IN (?, ?)', ['pending', 'awaiting payment'])->count()
                                      : 0,
                'pending_managers' => Schema::hasTable('deployment_managers') 
                                      ? DB::table('deployment_managers')->whereIn('status', ['pending', 'pending_info'])->count() 
                                      : 0,
                'active_managers'  => Schema::hasTable('deployment_managers') 
                                      ? DB::table('deployment_managers')->where('status', 'active')->count() 
                                      : 0,
                'suspended_managers'  => Schema::hasTable('deployment_managers') 
                                      ? DB::table('deployment_managers')->where('status', 'suspended')->count() 
                                      : 0,
                'total_stock_val'  => $stockValue,
                'expiring_soon_subs' => Schema::hasTable('subscriptions')
                                      ? Subscription::expiringSoon(7)->count()
                                      : 0,
                'expired_subs'       => Schema::hasTable('subscriptions')
                                      ? Subscription::whereRaw("LOWER(COALESCE(status, '')) = 'expired'")->count()
                                      : 0,
            ];

            $stats = $metrics;

            // REVENUE TRENDS
            $revenueTrends = collect();
            if (Schema::hasTable('subscriptions')) {
                $revenueTrends = Subscription::whereRaw('LOWER(payment_status) = ?', ['paid'])
                    ->select(
                        DB::raw('MONTHNAME(created_at) as month'), 
                        DB::raw('SUM(amount) as total'), 
                        DB::raw('COUNT(*) as subscriptions_count'),
                        DB::raw('MONTH(created_at) as month_num')
                    )
                    ->whereYear('created_at', date('Y'))
                    ->groupBy('month', 'month_num')
                    ->orderBy('month_num', 'asc')
                    ->get();
            }
            if ($revenueTrends->isEmpty() && Schema::hasTable('sales')) {
                $revenueTrends = DB::table('sales')
                    ->select(
                        DB::raw('MONTHNAME(created_at) as month'),
                        DB::raw('SUM(total) as total'),
                        DB::raw('COUNT(*) as subscriptions_count'),
                        DB::raw('MONTH(created_at) as month_num')
                    )
                    ->whereYear('created_at', date('Y'))
                    ->groupBy('month', 'month_num')
                    ->orderBy('month_num', 'asc')
                    ->get();
            }

            // TENANT GROWTH
            $tenantGrowth = collect();
            if (Schema::hasTable('companies')) {
                $tenantGrowth = Company::has('subscription')
                    ->select(
                        DB::raw('MONTHNAME(created_at) as month'),
                        DB::raw('COUNT(*) as count'),
                        DB::raw('MONTH(created_at) as month_num')
                    )
                    ->whereYear('created_at', date('Y'))
                    ->groupBy('month', 'month_num')
                    ->orderBy('month_num', 'asc')
                    ->get();
            }

            // PLAN DISTRIBUTION
            $planStats = [];
            if (Schema::hasTable('subscriptions')) {
                $planExpr = "COALESCE(NULLIF(plan_name, ''), plan, 'Basic')";
                $planStats = Subscription::selectRaw("{$planExpr} as plan_label, COUNT(*) as total")
                    ->groupByRaw($planExpr)
                    ->pluck('total', 'plan_label')
                    ->toArray();
            }
            if (empty($planStats)) {
                $companyPlanExpr = "COALESCE(NULLIF(plan, ''), 'Basic')";
                $planStats = Company::selectRaw("{$companyPlanExpr} as plan_label, COUNT(*) as total")
                    ->groupByRaw($companyPlanExpr)
                    ->pluck('total', 'plan_label')
                    ->toArray();
            }

            // REVENUE BY PLAN
            $revenueByPlan = collect();
            if (Schema::hasTable('subscriptions')) {
                $planExpr = "COALESCE(NULLIF(plan_name, ''), plan, 'Basic')";
                $revenueByPlan = Subscription::whereRaw('LOWER(payment_status) = ?', ['paid'])
                    ->whereYear('created_at', date('Y'))
                    ->select(
                        DB::raw('MONTHNAME(created_at) as month'),
                        DB::raw('MONTH(created_at) as month_num'),
                        DB::raw("{$planExpr} as plan_name"),
                        DB::raw('SUM(amount) as revenue')
                    )
                    ->groupByRaw("MONTHNAME(created_at), MONTH(created_at), {$planExpr}")
                    ->orderBy('month_num', 'asc')
                    ->get();
            }
            if ($revenueByPlan->isEmpty()) {
                $companyPlanExpr = "COALESCE(NULLIF(plan, ''), 'Basic')";
                $revenueByPlan = Company::select(
                        DB::raw('MONTHNAME(created_at) as month'),
                        DB::raw('MONTH(created_at) as month_num'),
                        DB::raw("{$companyPlanExpr} as plan_name"),
                        DB::raw('COUNT(*) as revenue')
                    )
                    ->whereYear('created_at', date('Y'))
                    ->groupByRaw("MONTHNAME(created_at), MONTH(created_at), {$companyPlanExpr}")
                    ->orderBy('month_num', 'asc')
                    ->get();
            }

            // DEPLOYMENTS
            $deployments = collect();
            if (Schema::hasTable('deployment_managers')) {
                $deployments = DB::table('deployment_managers')
                    ->join('users', 'deployment_managers.user_id', '=', 'users.id')
                    ->select('deployment_managers.*', 'users.email', 'users.name as manager_name', 'users.is_verified')
                    ->latest('deployment_managers.created_at')
                    ->get();
            }

            // TOP DEPLOYMENT MANAGERS (for compact authorization progress bars)
            $managerPerformance = ['rows' => [], 'max' => 1];
            if (Schema::hasTable('deployment_managers')) {
                $managerRows = collect();
                if (Schema::hasTable('deployment_commissions')) {
                    $managerRows = DB::table('deployment_managers')
                        ->leftJoin('deployment_commissions', 'deployment_managers.user_id', '=', 'deployment_commissions.manager_id')
                        ->select(
                            'deployment_managers.id',
                            'deployment_managers.business_name',
                            'deployment_managers.status',
                            DB::raw("COALESCE(SUM(CASE WHEN deployment_commissions.status IN ('pending','paid') THEN deployment_commissions.amount ELSE 0 END), 0) as perf_score")
                        )
                        ->groupBy('deployment_managers.id', 'deployment_managers.business_name', 'deployment_managers.status')
                        ->orderByDesc('perf_score')
                        ->limit(3)
                        ->get();
                }

                // Fallback when commission rows are missing: rank by manager status weight.
                if ($managerRows->isEmpty()) {
                    $statusScore = ['active' => 3, 'pending' => 2, 'suspended' => 1];
                    $managerRows = DB::table('deployment_managers')
                        ->select('id', 'business_name', 'status')
                        ->latest('created_at')
                        ->limit(3)
                        ->get()
                        ->map(function ($m) use ($statusScore) {
                            return (object) [
                                'id' => $m->id,
                                'business_name' => $m->business_name,
                                'status' => $m->status,
                                'perf_score' => (float) ($statusScore[strtolower((string) ($m->status ?? ''))] ?? 1.0),
                            ];
                        })
                        ->sortByDesc('perf_score')
                        ->take(3)
                        ->values();
                }

                $rows = $managerRows->map(function ($row) {
                    $name = trim((string) ($row->business_name ?? ''));
                    if ($name === '') {
                        $name = 'Manager #' . ($row->id ?? 'N/A');
                    }
                    return [
                        'name' => $name,
                        'score' => (float) ($row->perf_score ?? 0),
                        'status' => (string) ($row->status ?? 'pending'),
                    ];
                })->values();

                $managerPerformance = [
                    'rows' => $rows->toArray(),
                    'max' => max(1.0, (float) ($rows->max('score') ?? 1.0)),
                ];
            }

            // STATUS DISTRIBUTION
            $statusDistribution = [
                'labels' => ['Active', 'Pending', 'Suspended'],
                'values' => [
                    $metrics['active_managers'],
                    $metrics['pending_managers'],
                    $metrics['suspended_managers']
                ]
            ];

            $monthlyRevenueMap = [];
            $monthlyOrdersMap = [];
            foreach ($revenueTrends as $row) {
                $monthlyRevenueMap[(int) $row->month_num] = (float) $row->total;
                $monthlyOrdersMap[(int) $row->month_num] = (int) ($row->subscriptions_count ?? 0);
            }

            $companyRows = Company::select(
                    DB::raw('MONTH(created_at) as month_num'),
                    DB::raw('COUNT(*) as total')
                )
                ->whereYear('created_at', date('Y'))
                ->groupBy('month_num')
                ->orderBy('month_num', 'asc')
                ->get();
            $monthlyCompaniesMap = $companyRows->pluck('total', 'month_num')->toArray();

            $userRows = User::select(
                    DB::raw('MONTH(created_at) as month_num'),
                    DB::raw('COUNT(*) as total')
                )
                ->whereYear('created_at', date('Y'))
                ->groupBy('month_num')
                ->orderBy('month_num', 'asc')
                ->get();
            $monthlyUsersMap = $userRows->pluck('total', 'month_num')->toArray();

            $chartSeries = [
                'labels' => [],
                'revenue' => [],
                'orders' => [],
                'companies' => [],
                'users' => [],
            ];
            for ($month = 1; $month <= 12; $month++) {
                $chartSeries['labels'][] = date('M', mktime(0, 0, 0, $month, 1));
                $chartSeries['revenue'][] = (float) ($monthlyRevenueMap[$month] ?? 0);
                $chartSeries['orders'][] = (int) ($monthlyOrdersMap[$month] ?? 0);
                $chartSeries['companies'][] = (int) ($monthlyCompaniesMap[$month] ?? 0);
                $chartSeries['users'][] = (int) ($monthlyUsersMap[$month] ?? 0);
            }

            $activityHeatmap = [];
            if (Schema::hasTable('subscriptions')) {
                $heatRows = Subscription::select(
                        DB::raw('DAYOFWEEK(created_at) as dow'),
                        DB::raw('HOUR(created_at) as hr'),
                        DB::raw('COUNT(*) as total')
                    )
                    ->whereYear('created_at', date('Y'))
                    ->groupBy('dow', 'hr')
                    ->get();

                $dayMap = [2 => 'Mon', 3 => 'Tue', 4 => 'Wed', 5 => 'Thu', 6 => 'Fri', 7 => 'Sat', 1 => 'Sun'];
                foreach ($heatRows as $row) {
                    $dayKey = $dayMap[(int) $row->dow] ?? null;
                    if ($dayKey === null) {
                        continue;
                    }
                    $activityHeatmap[$dayKey][(int) $row->hr] = (int) $row->total;
                }
            }
            if (empty($activityHeatmap) && Schema::hasTable('sales')) {
                $heatRows = DB::table('sales')
                    ->select(
                        DB::raw('DAYOFWEEK(created_at) as dow'),
                        DB::raw('HOUR(created_at) as hr'),
                        DB::raw('COUNT(*) as total')
                    )
                    ->whereYear('created_at', date('Y'))
                    ->groupBy('dow', 'hr')
                    ->get();

                $dayMap = [2 => 'Mon', 3 => 'Tue', 4 => 'Wed', 5 => 'Thu', 6 => 'Fri', 7 => 'Sat', 1 => 'Sun'];
                foreach ($heatRows as $row) {
                    $dayKey = $dayMap[(int) $row->dow] ?? null;
                    if ($dayKey === null) {
                        continue;
                    }
                    $activityHeatmap[$dayKey][(int) $row->hr] = (int) $row->total;
                }
            }

            $totalMgr = $metrics['active_managers'] + $metrics['pending_managers'] + $metrics['suspended_managers'];
            $systemHealth = [
                'company_provisioning_rate' => $metrics['total_tenants'] > 0 ? round(($metrics['active_subs'] / $metrics['total_tenants']) * 100, 1) : 0,
                'manager_verification_rate' => $totalMgr > 0 ? round(($metrics['active_managers'] / $totalMgr) * 100, 1) : 0,
                'payment_success_rate' => $metrics['total_subs'] > 0 ? round(($metrics['paid_subs'] / $metrics['total_subs']) * 100, 1) : 0,
                'user_verification_rate' => $metrics['total_users'] > 0 ? round(($metrics['verified_users'] / $metrics['total_users']) * 100, 1) : 0,
            ];

            // COUNTRY DATA
            $countryData = [];
            if (Schema::hasTable('companies')) {
                $countryData = Company::whereNotNull('country')
                    ->selectRaw('country, COUNT(*) as count')
                    ->groupBy('country')
                    ->pluck('count', 'country')
                    ->toArray();
            }

            // RECENT TENANTS
            $recentTenants = Company::has('subscription')
                ->with(['user', 'subscription'])
                ->latest()
                ->limit(5)
                ->get();
            if ($recentTenants->isEmpty()) {
                $recentTenants = Company::with(['user', 'subscription'])
                    ->latest()
                    ->limit(5)
                    ->get();
            }

            // PLATFORM ACTIVITY
            $platformActivity = collect();
            if (Schema::hasTable('subscriptions')) {
                $platformActivity = Subscription::with(['company', 'company.user'])
                    ->whereRaw('LOWER(payment_status) = ?', ['paid'])
                    ->latest()
                    ->limit(10)
                    ->get();
            }
            if ($platformActivity->isEmpty() && Schema::hasTable('sales')) {
                $platformActivity = Sale::query()
                    ->latest()
                    ->limit(10)
                    ->get()
                    ->map(function ($sale) {
                        return (object) [
                            'id' => $sale->id,
                            'subscriber_name' => $sale->customer_name ?: ('Order ' . ($sale->order_number ?? $sale->id)),
                            'amount' => $sale->total ?? 0,
                            'created_at' => $sale->created_at,
                            'company' => null,
                        ];
                    });
            }

            $expiringSubscriptions = collect();
            if (Schema::hasTable('subscriptions')) {
                $expiringSubscriptions = Subscription::with(['company', 'user'])
                    ->expiringSoon(7)
                    ->orderBy('end_date', 'asc')
                    ->limit(10)
                    ->get();
            }

            // VIEW ATTRIBUTES
            $userRole    = $user->role;
            $permissions = ['view_reports', 'manage_users', 'manage_domains', 'super_access', 'verify_managers'];
            $domain      = env('SESSION_DOMAIN', 'Default System');
            $isDeploymentView = false;
            $viewPath = 'SuperAdmin.dashboard';

            return view($viewPath, compact(
                'stats', 'metrics', 'revenueTrends', 'tenantGrowth', 'revenueByPlan', 
                'recentTenants', 'platformActivity', 'planStats', 'countryData', 
                'userRole', 'permissions', 'deployments', 'domain', 
                'deploymentLimit', 'statusDistribution', 'isDeploymentView',
                'chartSeries', 'activityHeatmap', 'systemHealth', 'managerPerformance',
                'expiringSubscriptions'
            ));

        } catch (\Exception $e) {
            Log::error('SuperAdmin Dashboard Error: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString()
            ]);
            
            $emptyMetrics = [
                'total_companies' => 0, 'total_tenants' => 0, 'active_subs' => 0, 
                'platform_revenue' => 0, 'total_users' => 0, 'pending_setups' => 0, 
                'pending_managers' => 0, 'active_managers' => 0, 'total_stock_val' => 0,
                'expiring_soon_subs' => 0, 'expired_subs' => 0
            ];

            return view('SuperAdmin.dashboard', [
                'stats' => $emptyMetrics,
                'metrics' => $emptyMetrics,
                'deploymentLimit' => 0,
                'revenueTrends' => collect(),
                'tenantGrowth' => collect(),
                'revenueByPlan' => collect(),
                'recentTenants' => collect(),
                'platformActivity' => collect(),
                'deployments' => collect(),
                'planStats' => [],
                'countryData' => [],
                'statusDistribution' => ['labels' => [], 'values' => []],
                'chartSeries' => ['labels' => [], 'revenue' => [], 'orders' => [], 'companies' => [], 'users' => []],
                'managerPerformance' => ['rows' => [], 'max' => 1],
                'activityHeatmap' => [],
                'systemHealth' => [
                    'company_provisioning_rate' => 0,
                    'manager_verification_rate' => 0,
                    'payment_success_rate' => 0,
                    'user_verification_rate' => 0,
                ],
                'userRole' => $user->role,
                'permissions' => [],
                'isDeploymentView' => false,
                'domain' => env('SESSION_DOMAIN', 'Error State'),
                'expiringSubscriptions' => collect(),
            ])->with('error', 'System Error: ' . $e->getMessage());
        }
    }

    /**
     * Check if user is super admin
     * CRITICAL: This is more inclusive than the old check
     */
    private function isSuperAdmin($user): bool
    {
        $role = strtolower($user->role ?? '');
        
        // Check multiple possible super admin roles
        $validRoles = [
            'super_admin',
            'superadmin', 
            'administrator',
            'admin'
        ];
        
        $isValidRole = in_array($role, $validRoles);
        $isVictorEmail = ($user->email === 'donvictorlive@gmail.com');
        
        return $isValidRole || $isVictorEmail;
    }


    public function create()
    {
        // 1. Define roles expected by the @foreach ($roles as ...) in your blade file
        $roles = [
            'admin' => 'Administrator',
            'manager' => 'Manager',
            'staff' => 'Staff',
            'user' => 'Standard User'
        ];

        // 2. Fetch companies for the dropdown select seen in your stack trace
        $companies = Company::orderBy('name', 'asc')->get();

        // 3. Environment Context
        $domain = env('SESSION_DOMAIN', 'System');

        // 4. Return view with variables
        return view('deployment.users.create', compact('roles', 'companies', 'domain'));
    }


    public function approveManager($id)
    {
        try {
            DB::beginTransaction();

            $manager = DeploymentManager::findOrFail($id);
            
            $manager->update([
                'status' => 'active',
                'updated_at' => now()
            ]);

            $user = User::find($manager->user_id);
            if ($user) {
                $user->update([
                    'is_verified' => 1,
                    'verified_at' => now(),
                    'role' => 'deployment_manager'
                ]);

                $this->ensureManagerHasWorkspace($user);

                DB::afterCommit(function () use ($user) {
                    SystemEventMailer::notifyManagerApproved($user, Auth::user());
                });

                Log::info("Deployment Manager Approved, Workspace Created, & Notification Triggered: {$user->email}");
            }

            DB::commit();
            $displayName = $manager->business_name ?? ($user->name ?? 'Manager');
            return redirect()->back()->with('success', "Manager '{$displayName}' authorized and verified successfully!");

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Manager Approval Error: ' . $e->getMessage());
            return redirect()->back()->with('error', 'Critical error during approval: ' . $e->getMessage());
        }
    }

    public function rejectManager($id)
    {
        try {
            DB::beginTransaction();
            $manager = DeploymentManager::findOrFail($id);
            
            $manager->update([
                'status' => 'rejected',
                'updated_at' => now()
            ]);

            User::where('id', $manager->user_id)->update(['is_verified' => 0]);

            DB::commit();
            return redirect()->back()->with('success', "Manager rejected.");
        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()->with('error', 'Failed: ' . $e->getMessage());
        }
    }

    public function suspendManager($id)
    {
        try {
            DB::beginTransaction();
            $manager = DeploymentManager::findOrFail($id);
            
            $manager->update(['status' => 'suspended']);
            User::where('id', $manager->user_id)->update(['is_verified' => 0]);

            DB::commit();
            return redirect()->back()->with('success', "Manager suspended successfully.");
        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()->with('error', 'Failed to suspend.');
        }
    }
    
    public function deleteManager($id)
    {
        try {
            DB::transaction(function () use ($id) {
                $manager = DeploymentManager::findOrFail($id);
                $userId = $manager->user_id;

                $manager->delete();
                User::where('id', $userId)->delete();
            });

            $domain = env('SESSION_DOMAIN', 'the current environment');
            return back()->with('success', "Manager and associated user account have been purged from {$domain}.");

        } catch (\Exception $e) {
            \Log::error("Manager Deletion Failed: " . $e->getMessage());
            return back()->with('error', 'Deletion Failed: ' . $e->getMessage());
        }
    }

    public function reactivateManager($id)
    {
        try {
            DB::beginTransaction();
            $manager = DeploymentManager::findOrFail($id);
            
            $manager->update(['status' => 'active']);
            User::where('id', $manager->user_id)->update(['is_verified' => 1]);

            DB::commit();
            return redirect()->back()->with('success', "Manager reactivated successfully!");
        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()->with('error', 'Failed to reactivate.');
        }
    }

    /**
     * Route alias used by /superadmin/managers/{id}/activate
     */
    public function activateManager($id)
    {
        return $this->reactivateManager($id);
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
                    'subdomain' => $prefix,
                    'name' => ($dmInfo->business_name ?? $user->name),
                    'status' => 'active',
                    'plan' => 'Enterprise'
                ]
            );
        }
    }

public function pendingManagers()
{
    $pending = DB::table('deployment_managers')
        ->leftJoin('users', 'deployment_managers.user_id', '=', 'users.id')
        ->select(
            'deployment_managers.*',
            DB::raw("COALESCE(users.email, '') as email"),
            DB::raw("COALESCE(users.name, deployment_managers.business_name, 'N/A') as manager_name")
        )
        ->whereIn('deployment_managers.status', ['pending', 'pending_info'])
        ->orderByDesc('deployment_managers.created_at')
        ->get();

    return view('SuperAdmin.managers.pending', compact('pending'));  // ← fixed
}
    public function approvedManagers(Request $request)
    {
        $query = DB::table('deployment_managers')
            ->leftJoin('users', 'deployment_managers.user_id', '=', 'users.id')
            ->select(
                'deployment_managers.*',
                DB::raw("COALESCE(users.email, '') as email"),
                DB::raw("COALESCE(users.name, deployment_managers.business_name, 'N/A') as manager_name")
            )
            ->whereIn('deployment_managers.status', ['active', 'suspended']);

        if ($request->filled('search')) {
            $search = '%' . trim((string) $request->search) . '%';
            $query->where(function ($q) use ($search) {
                $q->where('users.name', 'like', $search)
                    ->orWhere('users.email', 'like', $search)
                    ->orWhere('deployment_managers.business_name', 'like', $search)
                    ->orWhere('deployment_managers.phone', 'like', $search);
            });
        }

        if ($request->filled('status')) {
            $query->where('deployment_managers.status', trim((string) $request->status));
        }

        $managers = $query->orderBy('deployment_managers.business_name', 'asc')
            ->paginate(15)
            ->withQueryString();

        return view('SuperAdmin.managers.approved', compact('managers'));
    }

    public function listManagers(Request $request)
    {
        $query = DB::table('deployment_managers')
            ->leftJoin('users', 'deployment_managers.user_id', '=', 'users.id')
            ->select(
                'deployment_managers.*',
                DB::raw("COALESCE(users.email, '') as email"),
                DB::raw("COALESCE(users.name, deployment_managers.business_name, 'N/A') as manager_name")
            );

        if ($request->filled('search')) {
            $search = '%' . trim((string) $request->search) . '%';
            $query->where(function ($q) use ($search) {
                $q->where('users.name', 'like', $search)
                    ->orWhere('users.email', 'like', $search)
                    ->orWhere('deployment_managers.business_name', 'like', $search)
                    ->orWhere('deployment_managers.phone', 'like', $search);
            });
        }

        if ($request->filled('status')) {
            $query->where('deployment_managers.status', trim((string) $request->status));
        }

        $managers = $query->orderByDesc('deployment_managers.created_at')
            ->paginate(15)
            ->withQueryString();

        return view('SuperAdmin.managers.approved', compact('managers'));
    }

    public function suspendedManagers(Request $request)
    {
        $query = DB::table('deployment_managers')
            ->leftJoin('users', 'deployment_managers.user_id', '=', 'users.id')
            ->select(
                'deployment_managers.*',
                DB::raw("COALESCE(users.email, '') as email"),
                DB::raw("COALESCE(users.name, deployment_managers.business_name, 'N/A') as manager_name")
            )
            ->where('deployment_managers.status', 'suspended');

        if ($request->filled('search')) {
            $search = '%' . trim((string) $request->search) . '%';
            $query->where(function ($q) use ($search) {
                $q->where('users.name', 'like', $search)
                    ->orWhere('users.email', 'like', $search)
                    ->orWhere('deployment_managers.business_name', 'like', $search)
                    ->orWhere('deployment_managers.phone', 'like', $search);
            });
        }

        $managers = $query->orderBy('deployment_managers.business_name', 'asc')
            ->paginate(15)
            ->withQueryString();

        return view('SuperAdmin.managers.approved', compact('managers'));
    }

    public function transferUsers(Request $request)
    {
        $query = Subscription::query()
            ->leftJoin('users', 'subscriptions.user_id', '=', 'users.id')
            ->leftJoin('companies', 'subscriptions.company_id', '=', 'companies.id')
            ->select(
                'subscriptions.*',
                DB::raw("COALESCE(users.name, subscriptions.subscriber_name, 'N/A') as customer_name"),
                DB::raw("COALESCE(users.email, '') as customer_email"),
                DB::raw("COALESCE(companies.name, companies.company_name, '') as company_name")
            )
            ->whereRaw("LOWER(COALESCE(subscriptions.payment_gateway, '')) = 'bank_transfer'")
            ->where(function ($q) {
                $q->whereNull('subscriptions.deployed_by')
                    ->orWhere('subscriptions.deployed_by', 0);
            });

        if ($request->filled('search')) {
            $search = '%' . trim((string) $request->search) . '%';
            $query->where(function ($q) use ($search) {
                $q->where('users.name', 'like', $search)
                    ->orWhere('users.email', 'like', $search)
                    ->orWhere('subscriptions.subscriber_name', 'like', $search)
                    ->orWhere('subscriptions.transfer_reference', 'like', $search)
                    ->orWhere('subscriptions.transaction_reference', 'like', $search);
            });
        }

        if ($request->filled('status')) {
            $status = strtolower(trim((string) $request->status));
            if ($status === 'pending') {
                $query->whereRaw("LOWER(COALESCE(subscriptions.payment_status, '')) = 'pending_verification'");
            } elseif ($status === 'approved') {
                $query->whereRaw("LOWER(COALESCE(subscriptions.status, '')) = 'active'");
            } elseif ($status === 'rejected') {
                $query->whereRaw("LOWER(COALESCE(subscriptions.payment_status, '')) = 'failed'");
            } elseif ($status === 'suspended') {
                $query->whereRaw("LOWER(COALESCE(subscriptions.status, '')) = 'suspended'");
            }
        }

        $transferUsers = $query->orderByDesc('subscriptions.transfer_submitted_at')
            ->orderByDesc('subscriptions.created_at')
            ->paginate(15)
            ->withQueryString();

        return view('SuperAdmin.users.transfer', compact('transferUsers'));
    }

    public function approveSubscription($id)
    {
        try {
            $subscription = Subscription::findOrFail($id);
            $subscription->update([
                'status' => 'Active',
                'payment_status' => 'paid',
                'approved_at' => now(),
                'approved_by' => auth()->id()
            ]);
            return redirect()->back()->with('success', 'Subscription approved!');
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Failed to approve.');
        }
    }

    public function exportAnalytics(Request $request)
    {
        $type = $request->get('type', 'revenue');
        $format = $request->get('format', 'csv');

        try {
            $data = match ($type) {
                'revenue' => Subscription::where('payment_status', 'paid')->with('company')->get()->map(fn($s) => ['ID' => $s->id, 'Company' => $s->company->name ?? 'N/A', 'Amount' => $s->amount, 'Date' => $s->created_at]),
                'managers' => DB::table('deployment_managers')->join('users', 'deployment_managers.user_id', '=', 'users.id')->select('users.name', 'users.email', 'deployment_managers.status')->get(),
                'tenants' => Company::has('subscription')->with(['subscription'])->get()->map(fn($c) => ['Company' => $c->name, 'Plan' => $c->subscription->plan_name ?? 'N/A', 'Status' => $c->status, 'Joined' => $c->created_at]),
                default => throw new \Exception('Invalid type')
            };

            return $this->generateExport($data, "{$type}_report", $format);
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Export failed: ' . $e->getMessage());
        }
    }

    private function generateExport($data, $filename, $format)
    {
        $filename = $filename . '_' . date('Y-m-d') . '.csv';
        $headers = ['Content-Type' => 'text/csv', 'Content-Disposition' => "attachment; filename=\"{$filename}\""];
        
        return response()->stream(function() use ($data) {
            $file = fopen('php://output', 'w');
            if ($data->isNotEmpty()) fputcsv($file, array_keys((array)$data->first()));
            foreach ($data as $row) fputcsv($file, (array)$row);
            fclose($file);
        }, 200, $headers);
    }

    public function exportStats()
    {
        return $this->exportAnalytics(request()->merge([
            'type' => 'revenue',
            'format' => 'csv',
        ]));
    }
}
