<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Facades\Auth;

class CheckRole
{
    /**
     * Handle an incoming request.
     * domain => env('SESSION_DOMAIN', null)
     */
    public function handle(Request $request, Closure $next, ...$roles): Response
    {
        if (!Auth::check()) {
            return redirect()->route('login');
        }

        $user = Auth::user();
        if ($user->isSuperAdmin()) {
            return $next($request);
        }
        foreach ($roles as $role) {
            if ($user->hasRole((string) $role)) {
                return $next($request);
            }
        }

        // Unauthorized access
        abort(403, 'You do not have permission to access this resource.');
    }

}
