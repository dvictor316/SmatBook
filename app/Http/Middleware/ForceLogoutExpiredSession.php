<?php

namespace App\Http\Middleware;

use App\Support\DeviceSessionManager;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ForceLogoutExpiredSession
{
    public function __construct(
        private readonly DeviceSessionManager $deviceSessionManager
    ) {
    }

    public function handle(Request $request, Closure $next)
    {
        if (!Auth::check()) {
            return $next($request);
        }

        $lifetimeMinutes = max(0, (int) config('session.lifetime', 120));
        $lastActivity = (int) $request->session()->get('last_activity', 0);
        $now = now()->timestamp;

        if ($lifetimeMinutes > 0 && $lastActivity > 0) {
            $expiresAfter = $lifetimeMinutes * 60;

            if (($now - $lastActivity) >= $expiresAfter) {
                // Check if the route requires authentication.
                // For public routes (e.g. /membership-plans), silently clear the expired
                // auth state and let the request continue as a guest instead of forcing
                // a login redirect — which would block plan discovery for returning users.
                $routeMiddleware = $request->route()?->gatherMiddleware() ?? [];
                $requiresAuth = collect($routeMiddleware)->contains(
                    fn ($m) => $m === 'auth' || str_starts_with((string) $m, 'auth:')
                );

                if (!$requiresAuth) {
                    $this->deviceSessionManager->forgetCurrentSession($request);
                    Auth::logout();
                    $request->session()->flush();
                    $request->session()->invalidate();
                    $request->session()->regenerateToken();
                    return $next($request);
                }

                return $this->logoutExpiredSession($request);
            }
        }

        $request->session()->put('last_activity', $now);

        return $next($request);
    }

    private function logoutExpiredSession(Request $request)
    {
        app(\App\Http\Controllers\AuthController::class)->clearClientAuthState($request);

        $message = 'Your session expired. Please login again to continue.';

        if ($this->shouldReturnJson($request)) {
            return response()->json(['message' => $message], 401);
        }

        return redirect()
            ->guest($this->resolveLoginRedirect($request, ['expired' => 1, 'flush' => 1]))
            ->withErrors(['login' => $message]);
    }

    private function resolveLoginRedirect(Request $request, array $query = []): string
    {
        $host = (string) $request->getHost();
        $mainDomain = ltrim((string) (config('app.domain') ?: parse_url((string) config('app.url'), PHP_URL_HOST) ?: 'smartprobook.com'), '.');

        if ($host !== $mainDomain && str_contains($host, $mainDomain)) {
            return route('login', $query);
        }

        return route('saas-login', $query);
    }

    private function shouldReturnJson(Request $request): bool
    {
        return $request->expectsJson()
            || $request->wantsJson()
            || $request->ajax()
            || strtolower((string) $request->header('X-Requested-With')) === 'xmlhttprequest';
    }
}
