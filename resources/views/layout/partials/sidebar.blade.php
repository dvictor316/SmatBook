
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
        $isSuperAdmin = $user->isSuperAdmin() ||
                       $user->email === 'donvictorlive@gmail.com';
    }

    $isAgentPortalUser = !$isDeploymentManager && !$isSuperAdmin && $user->hasRole('agent');
    $plan = $isSuperAdmin ? 'enterprise' : 'basic';

    if (!$isDeploymentManager && !$isSuperAdmin && !$isAgentPortalUser) {
        $plan = \App\Support\PlanAccess::resolveTierForUser($user);
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
