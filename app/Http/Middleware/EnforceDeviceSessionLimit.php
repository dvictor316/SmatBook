<?php

namespace App\Http\Middleware;

use App\Support\DeviceSessionManager;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class EnforceDeviceSessionLimit
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

        $result = $this->deviceSessionManager->ensureCurrentSession($request, $request->user());

        if (($result['allowed'] ?? true) !== true) {
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            if ($this->shouldReturnJson($request)) {
                return response()->json([
                    'message' => (string) ($result['message'] ?? 'This account cannot be used on another device right now.'),
                ], 401);
            }

            return redirect()
                ->guest($this->resolveLoginRedirect($request))
                ->with('error', (string) ($result['message'] ?? 'This account cannot be used on another device right now.'));
        }

        return $next($request);
    }

    private function resolveLoginRedirect(Request $request): string
    {
        $host = (string) $request->getHost();
        $mainDomain = ltrim((string) (config('app.domain') ?: parse_url((string) config('app.url'), PHP_URL_HOST) ?: 'smartprobook.com'), '.');

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
}
