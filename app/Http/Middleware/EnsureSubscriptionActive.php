<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;

class EnsureSubscriptionActive
{
    public function handle(Request $request, Closure $next)
    {
        if ((bool) env('TEMP_OPEN_ACCESS', false)) {
            return $next($request);
        }

        $user = Auth::user();

        // 1. Bypass for Admin
        if ($user && $user->email === 'donvictorlive@gmail.com') {
            return $next($request);
        }

        // 2. Access control logic
        if ($user && $user->company) {
            $expiryDate = $user->company->expires_at;

            // Check if expired
            if ($expiryDate && Carbon::parse($expiryDate)->isPast()) {
                
                // CRITICAL: Allow these routes to bypass to avoid "Too many redirects"
                $allowedPaths = ['membership-plans', 'payment/*', 'logout', 'login', '_debugbar/*'];
                
                foreach ($allowedPaths as $path) {
                    if ($request->is($path)) {
                        return $next($request);
                    }
                }

                // 3. Force Redirect to Membership Plans
                return redirect()->route('membership-plans')
                    ->with('error', 'Subscription Expired: Access to the Intelligence Console is restricted. Please uplink to continue.');
            }
        }

        return $next($request);
    }
}
