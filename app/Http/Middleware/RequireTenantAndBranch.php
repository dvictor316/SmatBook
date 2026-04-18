<?php

namespace App\Http\Middleware;

use App\Support\ActiveBranchResolver;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

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
        if (!$tenant) {
            return redirect()->route('onboarding')->with('info', 'Please complete setup to begin.');
        }

        // If branch is not set, try to auto-select the default branch
        $branch = session('active_branch_id');
        if (!$branch && Auth::check()) {
            app(ActiveBranchResolver::class)->ensureSession(Auth::user());
        }

        return $next($request);
    }
}
