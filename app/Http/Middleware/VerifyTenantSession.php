<?php

namespace App\Http\Middleware;

use App\Support\ActiveBranchResolver;
use App\Support\BusinessWorkspaceResolver;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class VerifyTenantSession
{
    public function __construct(private readonly BusinessWorkspaceResolver $workspaceResolver) {}

    public function handle(Request $request, Closure $next)
    {
        if (Auth::check()) {
            $user = Auth::user();
            $sessionTenantId = (int) session('current_tenant_id', 0);
            $role = strtolower((string) ($user->role ?? ''));
            $isSuperAdmin = in_array($role, ['super_admin', 'superadmin'], true);

            if ($isSuperAdmin
                && session('workspace_context') === 'platform'
                && $sessionTenantId === 0) {
                return $next($request);
            }

            // A regular user without a company must never inherit a tenant
            // selected by a previous session or another account.
            if ($sessionTenantId > 0 && ! $this->workspaceResolver->canAccess($user, $sessionTenantId) && ! $isSuperAdmin) {
                session()->forget(['current_tenant_id', 'current_tenant_name', 'active_branch_id', 'active_branch_name']);
                $sessionTenantId = 0;
            }

            $activeCompanyId = $this->workspaceResolver->activeCompanyId($user);
            if ($activeCompanyId > 0 && $sessionTenantId !== $activeCompanyId) {
                $company = $this->workspaceResolver->accessibleCompanies($user)->firstWhere('id', $activeCompanyId);
                if ($company) {
                    $this->workspaceResolver->activate($user, $company);
                } else {
                    session()->forget(['active_branch_id', 'active_branch_name', 'active_branch_scope']);
                    session(['current_tenant_id' => $activeCompanyId]);
                    $user->setAttribute('company_id', $activeCompanyId);
                }
            } elseif ($activeCompanyId > 0) {
                $user->setAttribute('company_id', $activeCompanyId);
            }

            app(ActiveBranchResolver::class)->ensureSession($user);
        }

        return $next($request);
    }
}
