<?php

namespace App\Http\Middleware;

use App\Models\Subscription;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class SubscriptionActive
{
    public function handle(Request $request, Closure $next)
    {
        if (!Auth::check()) {
            return redirect()->route('login');
        }

        if ((bool) env('TEMP_OPEN_ACCESS', false)) {
            return $next($request);
        }

        $user = Auth::user();
        
        // Routes that should NOT be blocked by this middleware
        $allowedRoutes = [
            'management.review',
            'membership-plans',
            'logout',
            'emergency.logout',
            'user.dashboard',
            'tenant.dashboard',
            'plan-billing',
        ];

        if ($request->routeIs($allowedRoutes) || $request->routeIs('payment.*')) {
            return $next($request);
        }

        if (
            in_array(strtolower((string) ($user->role ?? '')), ['super_admin', 'superadmin'], true) ||
            strtolower((string) ($user->email ?? '')) === 'donvictorlive@gmail.com'
        ) {
            return $next($request);
        }

        $subscription = Subscription::resolveCurrentForUser($user);

        if (!$subscription) {
            return redirect()->route('membership-plans');
        }

        if (strtolower((string) $subscription->plan_name) === 'custom' && !$subscription->isValid()) {
            return redirect()->route('management.review');
        }

        if ($subscription->isExpired()) {
            return redirect()->route('user.dashboard')
                ->with('error', 'Your subscription has expired. Renew your plan to restore full access.');
        }

        if (!$subscription->isValid()) {
            if (strtolower((string) $subscription->plan_name) === 'custom') {
                return redirect()->route('management.review');
            }
            return redirect()->route('membership-plans');
        }

        return $next($request);
    }
}
