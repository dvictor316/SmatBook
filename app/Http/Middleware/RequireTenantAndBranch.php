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

        // If session is missing tenant/branch but user is authenticated,
        // restore from the user model before blocking access.
        if (Auth::check()) {
            $user = Auth::user();

            if (!session('current_tenant_id') && !empty($user->company_id)) {
                session(['current_tenant_id' => $user->company_id]);
            }

            if (!session('active_branch_id')) {
                app(ActiveBranchResolver::class)->ensureSession($user);
            }
        }

        $tenant = session('current_tenant_id');
        if (!$tenant) {
            return redirect()->route('onboarding')->with('info', 'Please complete setup to begin.');
        }

        return $next($request);
    }
}
