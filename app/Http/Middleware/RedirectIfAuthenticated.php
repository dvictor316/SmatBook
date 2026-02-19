<?php

namespace App\Http\Middleware;

use App\Providers\RouteServiceProvider;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class RedirectIfAuthenticated
{
    /**
     * Handle an incoming request.
     *
     * This middleware ONLY handles what happens when an authenticated user
     * tries to access GUEST-ONLY routes (like /login, /register, /forgot-password)
     * 
     * It does NOT handle general authentication redirects - that's HomeController@index
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next, string ...$guards): Response
    {
        $guards = empty($guards) ? [null] : $guards;

        foreach ($guards as $guard) {
            if (Auth::guard($guard)->check()) {
                // User is authenticated trying to access guest routes
                // Simply redirect to /home
                // HomeController@index will handle all role-based routing
                return redirect(RouteServiceProvider::HOME);
            }
        }

        // User is NOT authenticated, let them access guest routes
        return $next($request);
    }
}