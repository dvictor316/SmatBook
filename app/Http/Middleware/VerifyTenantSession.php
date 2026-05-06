<?php
namespace App\Http\Middleware;

use App\Support\ActiveBranchResolver;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class VerifyTenantSession
{
    public function handle(Request $request, Closure $next)
    {
        if (Auth::check()) {
            $user = Auth::user();
            $userCompanyId = (int) ($user->company_id ?? 0);
            $sessionTenantId = (int) session('current_tenant_id', 0);

            // If session tenant does not match the authenticated user's company,
            // correct it immediately to prevent cross-tenant data access via
            // session manipulation or stale sessions from a previous login.
            if ($userCompanyId > 0 && $sessionTenantId !== $userCompanyId) {
                session(['current_tenant_id' => $userCompanyId]);
                // Also clear any branch session that may belong to the wrong tenant.
                session()->forget(['active_branch_id', 'active_branch_name']);
            }

            if ($userCompanyId > 0 && $sessionTenantId === 0) {
                session(['current_tenant_id' => $userCompanyId]);
            }

            app(ActiveBranchResolver::class)->ensureSession($user);
        }

        return $next($request);
    }
}
