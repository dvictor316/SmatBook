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

        if ((bool) env('TEMP_OPEN_ACCESS', false)) {
            return $next($request);
        }

        $user = Auth::user();

        // Super Admin bypass: They can go anywhere
        if ($user->role === 'super_admin') {
            return $next($request);
        }

        // Check if the user's role is in the allowed list
        if (in_array($user->role, $roles)) {
            return $next($request);
        }

        // Unauthorized access
        abort(403, 'You do not have permission to access this resource.');
    }
}
