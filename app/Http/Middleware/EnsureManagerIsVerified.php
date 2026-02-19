<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

class EnsureManagerIsVerified
{
    /**
     * REWRITTEN: VERIFICATION GUARD
     * Ensures sensitive deployment tools remain locked until SuperAdmin approval.
     */
    public function handle(Request $request, Closure $next)
    {
        $user = Auth::user();

        // 1. Target check for Managers
        if ($user && ($user->role === 'deployment_manager' || $user->role === 'manager')) {
            
            // 2. Check verification status
            if (!$user->is_verified) {
                
                /**
                 * THE LOOP FIX: 
                 * We must check the CURRENT URL path as well as the route name.
                 * Sometimes 'routeIs' fails if the route is defined inside a prefix 
                 * that the middleware is also protecting.
                 */
                $isAllowedRoute = $request->routeIs(['manager.verification.*', 'manager.pending.notice', 'logout']);
                $isAllowedPath = $request->is('manager/pending-approval*') || $request->is('logout');

                if ($isAllowedRoute || $isAllowedPath) {
                    return $next($request);
                }

                // 3. Prevent crashing if route name is missing, but prioritize named route
                if (Route::has('manager.pending.notice')) {
                    return redirect()->route('manager.pending.notice')
                        ->with('warning', 'Your account is awaiting Super Admin verification.');
                }

                // Hard fallback to prevent the loop
                return redirect('/manager/pending-approval');
            }
        }

        return $next($request);
    }
}