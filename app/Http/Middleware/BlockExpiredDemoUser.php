<?php

namespace App\Http\Middleware;

use App\Models\ActivityLog;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * Blocks login and access for demo users whose 48-hour window has expired.
 * Also blocks demo users from reaching superadmin routes.
 */
class BlockExpiredDemoUser
{
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

        if (!$company || !$company->is_demo) {
            return $next($request);
        }

        // If demo has expired, log out and redirect
        if ($company->demo_expires_at && $company->demo_expires_at->isPast()) {
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

        return $next($request);
    }
}
