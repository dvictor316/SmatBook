<?php

namespace App\Http\Middleware;

use App\Support\InternalTestAccess;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;

class EnsureSubscriptionActive
{
    public function __construct(
        private readonly InternalTestAccess $internalTestAccess
    ) {
    }

    public function handle(Request $request, Closure $next)
    {
        if ($this->internalTestAccess->canBypassSubscriptionOrPlan(Auth::user())) {
            $this->internalTestAccess->logUsage(Auth::user(), 'legacy_subscription_bypass', [
                'route' => optional($request->route())->getName(),
            ]);
            return $next($request);
        }

        $user = Auth::user();

        // 1. Bypass for Admin
        if ($user && method_exists($user, 'isSuperAdmin') && $user->isSuperAdmin()) {
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
