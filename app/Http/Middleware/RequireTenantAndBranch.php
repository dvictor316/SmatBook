<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class RequireTenantAndBranch
{
    public function handle(Request $request, Closure $next)
    {
        // Allow onboarding, branch setup, and logout routes
        $allowed = [
            'onboarding', 'branch.setup', 'logout', 'saas-setup', 'saas-logout',
        ];
        $route = $request->route()?->getName();
        if (in_array($route, $allowed, true)) {
            return $next($request);
        }

        $tenant = session('current_tenant_id');
        $branch = session('active_branch_id');
        if (!$tenant || !$branch) {
            // Redirect to onboarding or branch selection
            return redirect()->route('onboarding')->with('info', 'Please complete setup and select a branch.');
        }
        return $next($request);
    }
}
