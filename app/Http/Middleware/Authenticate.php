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
        if ($this->shouldReturnJson($request)) {
            return null;
        }

        $host = $request->getHost();
        $mainDomain = $this->mainDomain();

        if ($host !== $mainDomain && str_contains($host, $mainDomain)) {
            return route('login');
        }

        return route('saas-login');
    }

    private function shouldReturnJson(Request $request): bool
    {
        return $request->expectsJson()
            || $request->wantsJson()
            || $request->ajax()
            || strtolower((string) $request->header('X-Requested-With')) === 'xmlhttprequest';
    }

    private function mainDomain(): string
    {
        return ltrim((string) (config('app.domain') ?: parse_url((string) config('app.url'), PHP_URL_HOST) ?: 'smartprobook.com'), '.');
    }
}
