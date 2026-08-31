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
        // Allow onboarding/auth/subscription routes to resolve their own state.
        $allowed = [
            'onboarding',
            'branch.setup',
            'login',
            'login-account',
            'login-account.post',
            'saas-login',
            'saas-login.post',
            'logout',
            'emergency.logout',
            'membership-plans',
            'pricing',
            'subscription.expired',
            'subscription.upgrade.redirect',
            'saas.select_plan',
            'saas.select_plan.save',
            'saas.setup',
            'saas.setup.legacy',
            'saas.store',
            'saas.checkout',
            'saas.success',
            'saas.payment.success',
            'saas.payment.cancel',
            'registration.pending.notice',
            'manager.pending.notice',
            'manager.verification.form',
            'manager.submit.verification',
            'submit.verification',
        ];
        $route = $request->route()?->getName();
        if (in_array($route, $allowed, true)) {
            return $next($request);
        }

        if (Auth::check()) {
            $user = Auth::user();
            $userCompanyId = (int) ($user->company_id ?? 0);
            $sessionTenantId = (int) session('current_tenant_id', 0);

            // Restore missing tenant session from the authenticated user's record.
            if ($sessionTenantId === 0 && $userCompanyId > 0) {
                session(['current_tenant_id' => $userCompanyId]);
                $sessionTenantId = $userCompanyId;
            }

            // Enforce strict match: session must agree with the authenticated
            // user's company. A mismatch means a stale or manipulated session —
            // reset it to prevent cross-tenant data access.
            if ($userCompanyId > 0 && $sessionTenantId !== $userCompanyId) {
                session(['current_tenant_id' => $userCompanyId]);
                // Flush branch context too; it may belong to the wrong tenant.
                session()->forget(['active_branch_id', 'active_branch_name']);
            }

            app(ActiveBranchResolver::class)->ensureSession($user);
        }

        $tenant = session('current_tenant_id');
        if (!$tenant) {
            return redirect()->route('onboarding')->with('info', 'Please complete setup to begin.');
        }

        return $next($request);
    }
}
