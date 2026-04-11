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
                return $this->logoutExpiredSession($request);
            }
        }

        $request->session()->put('last_activity', $now);

        return $next($request);
    }

    private function logoutExpiredSession(Request $request)
    {
        $this->deviceSessionManager->forgetCurrentSession($request);

        Auth::logout();
        $request->session()->flush();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        $message = 'Your session expired. Please login again to continue.';

        if ($request->expectsJson()) {
            return response()->json(['message' => $message], 401);
        }

        return redirect()
            ->guest($this->resolveLoginRedirect($request))
            ->withErrors(['login' => $message]);
    }

    private function resolveLoginRedirect(Request $request): string
    {
        $host = (string) $request->getHost();
        $mainDomain = 'smatbook.com';

        if ($host !== $mainDomain && str_contains($host, $mainDomain)) {
            return route('login');
        }

        return route('saas-login');
    }
}
