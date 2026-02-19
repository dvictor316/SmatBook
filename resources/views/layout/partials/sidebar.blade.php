{{-- 
    File: resources/views/layout/partials/sidebar.blade.php
    
    CRITICAL FIX: Check deployment_managers table FIRST before checking role
    This prevents deployment managers with 'administrator' role from being 
    treated as super admins.
--}}

@php
    $user = auth()->user();
    
    // If no user, don't show sidebar
    if (!$user) { return; } 

    // PRIORITY 1: Check deployment_managers table FIRST
    // This is CRITICAL - must check database, not just role
    $isDeploymentManager = \App\Models\DeploymentManager::where('user_id', $user->id)->exists();
    
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
    if (!$isDeploymentManager && !$isSuperAdmin) {
        $companyId = $user->company_id ?? optional($user->company)->id;

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
                ?? ($user->company->plan ?? '')
            );
            
            if (str_contains($planName, 'enterprise')) {
                $plan = 'enterprise';
            } elseif (str_contains($planName, 'prof') || str_contains($planName, 'pro')) {
                $plan = 'pro';
            } else {
                $plan = 'basic';
            }
        } elseif (!empty($user->company?->plan)) {
            // Fallback when subscription row is missing but company plan exists.
            $companyPlan = strtolower((string) $user->company->plan);
            if (str_contains($companyPlan, 'enterprise')) {
                $plan = 'enterprise';
            } elseif (str_contains($companyPlan, 'prof') || str_contains($companyPlan, 'pro') || str_contains($companyPlan, 'premium')) {
                $plan = 'pro';
            }
        }
    }
@endphp

@unless(Route::is(['index-two', 'index-three', 'index-four', 'index-five']))
    {{-- Deployment/plan sidebars are self-contained containers. --}}
    @if($isDeploymentManager)
        @include('layout.partials.sidebars.deployment_manager')
    @elseif($isSuperAdmin)
        @include('layout.partials.sidebars.super_admin')
    @elseif($plan === 'enterprise')
        @include('layout.partials.sidebars.enterprise')
    @elseif($plan === 'pro')
        @include('layout.partials.sidebars.pro')
    @else
        {{-- Basic sidebar is menu-only, so wrap it here. --}}
        <div class="sidebar" id="sidebar">
            <div class="sidebar-inner slimscroll">
                <div id="sidebar-menu" class="sidebar-menu">
                    @include('layout.partials.sidebars.basic')
                </div>
            </div>
        </div>
    @endif
@endunless
