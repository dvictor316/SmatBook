<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class AutoSuccessFlash
{
    public function handle(Request $request, Closure $next)
    {
        $response = $next($request);

        if ($request->expectsJson()) {
            return $response;
        }

        if (!in_array($request->method(), ['POST', 'PUT', 'PATCH', 'DELETE'], true)) {
            return $response;
        }

        if (!$response instanceof RedirectResponse) {
            return $response;
        }

        $session = $request->session();
        $hasMessage = $session->has('success')
            || $session->has('error')
            || $session->has('warning')
            || $session->has('info')
            || $session->has('status');

        if ($hasMessage || $session->has('errors')) {
            return $response;
        }

        $session->flash('success', 'Action completed successfully.');

        return $response;
    }
}
