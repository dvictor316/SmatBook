<?php

namespace App\Http\Middleware;

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
        ];

        if ($request->routeIs($allowedRoutes)) {
            return $next($request);
        }

        // Admin Override
        if ($user->email === 'donvictorlive@gmail.com') {
            return $next($request);
        }

        // Enforcement Logic
        if (!$user->subscription || $user->subscription->status !== 'Active') {
            if ($user->subscription && strtolower($user->subscription->plan_name) === 'custom') {
                return redirect()->route('management.review');
            }
            return redirect()->route('membership-plans');
        }

        return $next($request);
    }
}
