<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class RememberAccessiblePage
{
    public function handle(Request $request, Closure $next)
    {
        $response = $next($request);

        if ($request->user() && $request->isMethod('GET') && !$request->expectsJson() && !$request->ajax()
            && $response->getStatusCode() === 200
            && str_contains((string) $response->headers->get('Content-Type'), 'text/html')) {
            $request->session()->put('access_denied.last_page', $request->fullUrl());
            $request->session()->forget('access_denied.paths');
        }

        return $response;
    }
}
