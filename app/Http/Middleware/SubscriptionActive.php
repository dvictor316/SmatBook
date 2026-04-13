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
            if ($this->shouldReturnJson($request)) {
                return response()->json(['message' => 'Your session expired. Please login again to continue.'], 401);
            }

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
            'subscription.expired',
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
            if ($this->shouldReturnJson($request)) {
                return response()->json(['message' => 'An active subscription is required.'], 402);
            }

            return redirect()->route('membership-plans');
        }

        if (strtolower((string) $subscription->plan_name) === 'custom' && !$subscription->isValid()) {
            if ($this->shouldReturnJson($request)) {
                return response()->json(['message' => 'Your custom subscription is awaiting review.'], 402);
            }

            return redirect()->route('management.review');
        }

        if ($subscription->isExpired()) {
            if ($this->shouldReturnJson($request)) {
                return response()->json(['message' => 'Your subscription has expired.'], 402);
            }

            return redirect()->route('subscription.expired');
        }

        if (!$subscription->isValid()) {
            if (strtolower((string) $subscription->plan_name) === 'custom') {
                if ($this->shouldReturnJson($request)) {
                    return response()->json(['message' => 'Your custom subscription is awaiting review.'], 402);
                }

                return redirect()->route('management.review');
            }

            if ($this->shouldReturnJson($request)) {
                return response()->json(['message' => 'An active subscription is required.'], 402);
            }

            return redirect()->route('membership-plans');
        }

        return $next($request);
    }

    private function shouldReturnJson(Request $request): bool
    {
        return $request->expectsJson()
            || $request->wantsJson()
            || $request->ajax()
            || strtolower((string) $request->header('X-Requested-With')) === 'xmlhttprequest';
    }
}
