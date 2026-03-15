<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class RoleMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @param  string  ...$roles
     * @return mixed
     */
    public function handle(Request $request, Closure $next, ...$roles)
    {
        $user = Auth::user();

        // If user is not authenticated, redirect to login
        if (!$user) {
            return redirect()->route('saas-login')
                ->with('error', 'Please login to continue.');
        }

        if ((bool) env('TEMP_OPEN_ACCESS', false)) {
            return $next($request);
        }

        $allowedRoles = array_map(fn ($role) => strtolower(trim((string) $role)), $roles);
        $userRole = strtolower((string) ($user->role ?? ''));
        $roleMatch = in_array($userRole, $allowedRoles, true);

        if (!$roleMatch && method_exists($user, 'role') && $user->relationLoaded('role') ? $user->role : $user->role()->first()) {
            $roleMatch = in_array(strtolower((string) optional($user->role)->name), $allowedRoles, true);
        }

        // Check if user has any of the allowed roles
        if (!$roleMatch) {
            // Check if this is a deployment manager trying to access their area
            if (in_array('deployment_manager', $roles) || in_array('manager', $roles)) {
                return redirect()->route('home')
                    ->with('error', 'Access denied. You do not have the required permissions.');
            }

            // For super admin routes
            if (in_array('super_admin', $roles) || in_array('administrator', $roles)) {
                return redirect()->route('home')
                    ->with('error', 'Super admin access required.');
            }

            // Generic unauthorized access
            return redirect()->route('home')
                ->with('error', 'Unauthorized access.');
        }

        return $next($request);
    }
}
