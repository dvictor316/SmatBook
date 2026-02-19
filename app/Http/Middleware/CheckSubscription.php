<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Facades\Auth;
use App\Models\Subscription;

class CheckSubscription
{
    public function handle(Request $request, Closure $next): Response
    {
        // 1. MASTER KEY: Always let you in (Admin Override)
        if (Auth::check() && Auth::user()->email === 'donvictorlive@gmail.com') {
            return $next($request);
        }

        // 2. EXCEPTIONS: Routes that MUST be accessible to avoid infinite redirect loops
        // This includes checkout, payment callbacks, and the state-saver
      // 2. EXCEPTIONS: Routes that MUST be accessible
$allowedRoutes = [
    'user.setup', 
    'user.setup.post',   // Added to match your web.php
    'user.setup.save', 
    'saas.setup.store',  // Added to match your web.php
    'saas.checkout', 
    'saas.success',      // CRITICAL: Allows the receipt to be viewed
    'membership-plans',
    'plan.select',
    'tenant.checkpoint', 
    'payment.callback'
];

        // Check by Route Name OR by URL path for safety
        if (in_array($request->route()->getName(), $allowedRoutes) || 
            $request->is('checkout*') || 
            $request->is('payment*') || 
            $request->is('tenant/checkpoint*')) {
            return $next($request);
        }

        $user = Auth::user();
        $tenant = $user->tenant; // Ensure your User model has the tenant() relationship

        // 3. ENFORCEMENT: Check Tenant Status
        if ($tenant && $tenant->is_active) {
            return $next($request);
        }

        // 4. PERSISTENCE LOGIC: Where did they stop?
        if ($tenant) {
            if ($tenant->onboarding_step == 3) {
                // If they reached payment, send them back to checkout
                $pendingSubscription = Subscription::query()
                    ->where('user_id', $user->id)
                    ->whereIn('payment_status', ['pending', 'unpaid'])
                    ->latest('id')
                    ->first();

                if ($pendingSubscription) {
                    return redirect()->route('saas.checkout', $pendingSubscription->id);
                }

                return redirect()->route('membership-plans');
            }
            
            if ($tenant->onboarding_step == 2) {
                // If they stopped at plan selection
                return redirect()->route('membership-plans');
            }
        }

        // 5. DEFAULT FALLBACK: Send to start of registration/plans
        return redirect()->route('membership-plans');
    }
}
