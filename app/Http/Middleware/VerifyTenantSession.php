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

            if ($isSuperAdmin && session('workspace_context') === 'platform') {
                $this->workspaceResolver->clearContext($user);
                return $next($request);
            }

            if ($sessionTenantId > 0 && ! $this->workspaceResolver->canAccess($user, $sessionTenantId)) {
                $this->workspaceResolver->clearContext($user);
                $sessionTenantId = 0;
            }

            $activeCompanyId = $this->workspaceResolver->activeCompanyId($user);
            $company = $this->workspaceResolver->accessibleCompanies($user)->firstWhere('id', $activeCompanyId);
            if ($company) {
                if ($sessionTenantId !== $activeCompanyId) {
                    $this->workspaceResolver->activate($user, $company);
                } else {
                    // Refresh both identity and label, including an already-loaded Eloquent relation.
                    $this->workspaceResolver->bindCompany($user, $company);
                }
            } elseif ($activeCompanyId > 0 && ! \Illuminate\Support\Facades\Schema::hasTable('companies')) {
                // Support installations that still use legacy user-company assignments.
                session(['current_tenant_id' => $activeCompanyId]);
                $user->setAttribute('company_id', $activeCompanyId);
                $user->unsetRelation('company');
            } else {
                $this->workspaceResolver->clearContext($user);
            }

            app(ActiveBranchResolver::class)->ensureSession($user);
        }

        return $next($request);
    }
}
