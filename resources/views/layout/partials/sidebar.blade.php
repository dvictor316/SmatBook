
@php
    $user = auth()->user();
    $workspaceContext = session('workspace_context', 'platform');

    // If no user, don't show sidebar
    if (!$user) { return; } 

    // PRIORITY 1: Check deployment_managers table FIRST
    // This is CRITICAL - must check database, not just role
    $isDeploymentManager = \Illuminate\Support\Facades\Cache::remember(
        'ui:sidebar:is_deployment_manager:' . $user->id,
        now()->addMinute(),
        fn () => \App\Models\DeploymentManager::where('user_id', $user->id)->exists()
    );

    // PRIORITY 2: Check if TRUE super admin (only specific emails/roles)
    $isSuperAdmin = false;
    if (!$isDeploymentManager) {
        $role = strtolower($user->role ?? '');
        // Only these are TRUE super admins (platform administrators)
        $isSuperAdmin = in_array($role, ['super_admin', 'superadmin']) || 
                       $user->email === 'donvictorlive@gmail.com';
    }

    // PRIORITY 3: Determine plan for regular tenants
    $plan = 'basic'; // default
    $isAgentPortalUser = !$isDeploymentManager && !$isSuperAdmin && strtolower((string) ($user->role ?? '')) === 'agent';
    $isDemoWorkspace = method_exists($user, 'isDemoUser') && $user->isDemoUser();
    $demoSidebarPlan = strtolower(trim((string) session('demo_customer_preview_plan', 'basic')));

    // Super admins always get enterprise sidebar — no subscription lookup needed
    if ($isSuperAdmin) {
        $plan = 'enterprise';
    }

    if ($isDemoWorkspace) {
        if (str_contains($demoSidebarPlan, 'starter')) {
            $plan = 'starter';
        } elseif (str_contains($demoSidebarPlan, 'enterprise')) {
            $plan = 'enterprise';
        } elseif (str_contains($demoSidebarPlan, 'prof') || str_contains($demoSidebarPlan, 'pro')) {
            $plan = 'pro';
        } else {
            $plan = 'basic';
        }
    }

    $shouldResolveBusinessPlan = !$isDeploymentManager && !$isSuperAdmin && !$isDemoWorkspace;

    if ($shouldResolveBusinessPlan) {
        $companyId = $user->company_id ?? optional($user->company)->id;

        $plan = \Illuminate\Support\Facades\Cache::remember(
            'ui:sidebar:plan:' . $user->id . ':' . ($companyId ?: 'none'),
            now()->addMinute(),
            function () use ($companyId, $user) {
                $resolvedPlan = 'basic';

                // Get active paid subscription, preferring company-scoped records.
                $subscription = \App\Models\Subscription::query()
                    ->where(function ($q) use ($companyId, $user) {
                        if (!empty($companyId) && \Illuminate\Support\Facades\Schema::hasColumn('subscriptions', 'company_id')) {
                            $q->where('company_id', $companyId);
                        }
                        $q->orWhere('user_id', $user->id);
                    })
                    ->whereRaw('LOWER(payment_status) = ?', ['paid'])
                    ->whereRaw('LOWER(status) = ?', ['active'])
                    ->latest('paid_at')
                    ->latest('id')
                    ->first();

                if ($subscription) {
                    $planName = strtolower(
                        $subscription->plan
                        ?? $subscription->plan_name
                        ?? (optional($user->company)->plan ?? '')
                    );

                    if (str_contains($planName, 'starter')) {
                        $resolvedPlan = 'starter';
                    } elseif (str_contains($planName, 'enterprise')) {
                        $resolvedPlan = 'enterprise';
                    } elseif (str_contains($planName, 'prof') || str_contains($planName, 'pro')) {
                        $resolvedPlan = 'pro';
                    }
                } elseif (!empty(optional($user->company)->plan)) {
                    // Fallback when subscription row is missing but company plan exists.
                    $companyPlan = strtolower((string) optional($user->company)->plan);
                    if (str_contains($companyPlan, 'starter')) {
                        $resolvedPlan = 'starter';
                    } elseif (str_contains($companyPlan, 'enterprise')) {
                        $resolvedPlan = 'enterprise';
                    } elseif (str_contains($companyPlan, 'prof') || str_contains($companyPlan, 'pro') || str_contains($companyPlan, 'premium')) {
                        $resolvedPlan = 'pro';
                    }
                }

                return $resolvedPlan;
            }
        );
    }
@endphp

@unless(Route::is(['index-two', 'index-three', 'index-four', 'index-five']))

    @if($isDeploymentManager)
        @include('layout.partials.sidebars.deployment_manager')
    @elseif($isAgentPortalUser)
        @include('layout.partials.sidebars.agent')
    @elseif($isSuperAdmin && $workspaceContext === 'business')
        @if($plan === 'enterprise')
            @include('layout.partials.sidebars.enterprise')
        @elseif($plan === 'pro')
            @include('layout.partials.sidebars.pro')
        @elseif($plan === 'starter')
            <div class="sidebar" id="sidebar">
                <div class="sidebar-inner slimscroll">
                    <div id="sidebar-menu" class="sidebar-menu">
                        @include('layout.partials.sidebars.starter')
                    </div>
                </div>
            </div>
        @else
            <div class="sidebar" id="sidebar">
                <div class="sidebar-inner slimscroll">
                    <div id="sidebar-menu" class="sidebar-menu">
                        @include('layout.partials.sidebars.basic')
                    </div>
                </div>
            </div>
        @endif
    @elseif($isSuperAdmin)
        @include('layout.partials.sidebars.super_admin')
    @elseif($plan === 'enterprise')
        @include('layout.partials.sidebars.enterprise')
    @elseif($plan === 'pro')
        @include('layout.partials.sidebars.pro')
    @elseif($plan === 'starter')
        <div class="sidebar" id="sidebar">
            <div class="sidebar-inner slimscroll">
                <div id="sidebar-menu" class="sidebar-menu">
                    @include('layout.partials.sidebars.starter')
                </div>
            </div>
        </div>
    @else

        <div class="sidebar" id="sidebar">
            <div class="sidebar-inner slimscroll">
                <div id="sidebar-menu" class="sidebar-menu">
                    @include('layout.partials.sidebars.basic')
                </div>
            </div>
        </div>
    @endif
@endunless
