<?php

namespace App\Http\Middleware;

use App\Models\ActivityLog;
use App\Services\DemoProvisioningService;
use App\Support\DemoSettings;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * Blocks login and access for demo users whose 48-hour window has expired.
 * Also blocks demo users from reaching superadmin routes.
 */
class BlockExpiredDemoUser
{
    public function __construct(
        private readonly DemoSettings $demoSettings,
        private readonly DemoProvisioningService $demoProvisioningService
    ) {
    }

    public function handle(Request $request, Closure $next)
    {
        $user = Auth::user();

        if (!$user) {
            return $next($request);
        }

        // Load the user's company (if any)
        $companyId = $user->company_id;
        if (!$companyId) {
            return $next($request);
        }

        $company = \App\Models\Company::find($companyId);

        if (!$company || !$company->isDemo()) {
            return $next($request);
        }

        if (! $this->demoSettings->isEnabled()) {
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()->route('login')
                ->with('error', 'Demo access is temporarily disabled by the administrator.');
        }

        // If demo has expired, log out and redirect
        if ($company->demoIsExpired()) {
            ActivityLog::record('Demo', 'blocked_expired', "Expired demo user {$user->email} attempted to access the system", [
                'user_id'    => $user->id,
                'company_id' => $companyId,
            ]);

            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()->route('login')
                ->with('error', 'Your SmartProbook demo access has expired. Please contact us to upgrade to a full account.');
        }

        if ($this->demoSettings->autoResetOnSessionStart() && ! $request->session()->has('demo_workspace_bootstrapped')) {
            $this->demoProvisioningService->resetDemoWorkspace($company, $user);
            $request->session()->put('demo_workspace_bootstrapped', true);
        }

        $request->session()->put('is_demo_workspace', true);

        return $next($request);
    }
}
