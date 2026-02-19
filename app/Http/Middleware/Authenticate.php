<?php

namespace App\Http\Middleware;

use Illuminate\Auth\Middleware\Authenticate as Middleware;
use Illuminate\Http\Request;

class Authenticate extends Middleware
{
    /**
     * Get the path the user should be redirected to when they are not authenticated.
     */
    protected function redirectTo(Request $request): ?string
    {
        if ($request->expectsJson()) {
            return null;
        }

        // 1. Detect Host for Subdomain-specific Login
        $host = $request->getHost();
        $mainDomain = 'smatbook.com';

        // 2. Subdomain Logic
        // If the user is on {tenant}.smatbook.com, redirect to tenant login
        if ($host !== $mainDomain && str_contains($host, $mainDomain)) {
            return route('login');
        }

        // 3. Central Logic
        // For smatbook.com/any-route, send to your custom saas-login
        return route('saas-login');
    }
}
