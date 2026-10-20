<?php

namespace App\Support;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;

class AccessDeniedRedirect
{
    public function respond(Request $request)
    {
        $message = 'You do not have permission to access that page or perform that action.';
        $blocked = array_unique(array_merge($request->session()->get('access_denied.paths', []), [$request->getPathInfo()]));
        $request->session()->put('access_denied.paths', $blocked);

        $candidates = [
            $request->headers->get('referer'),
            $request->session()->get('access_denied.last_page'),
        ];
        foreach (['user.dashboard', 'inventory.Products', 'profile'] as $name) {
            if (Route::has($name)) {
                $candidates[] = route($name);
            }
        }

        foreach (array_unique(array_filter($candidates)) as $url) {
            if ($this->isAccessible($request, $url, $blocked)) {
                $request->session()->put('access_denied.paths', array_merge($blocked, [parse_url($url, PHP_URL_PATH)]));

                return redirect()->to($url)->with('error', $message);
            }
        }

        // An authenticated, permission-free destination also covers users with every grant revoked.
        return redirect()->route('access.recovery')->with('error', $message);
    }

    private function isAccessible(Request $request, string $url, array $blocked): bool
    {
        $target = Request::create($url);
        if ($target->getSchemeAndHttpHost() !== $request->getSchemeAndHttpHost()
            || in_array($target->getPathInfo(), $blocked, true)) {
            return false;
        }

        try {
            $route = app('router')->getRoutes()->match($target);
            if (str_contains((string) $route->getName(), 'logout')) {
                return false;
            }
            // Check route and controller authorization without executing a page or action.
            foreach ($route->gatherMiddleware() as $middleware) {
                [$alias, $arguments] = array_pad(explode(':', $middleware, 2), 2, '');
                $class = app('router')->getMiddleware()[$alias] ?? $alias;
                if (in_array($class, [
                    \App\Http\Middleware\CheckPermission::class,
                    \App\Http\Middleware\CheckRole::class,
                    \App\Http\Middleware\EnsureBusinessOwner::class,
                    \App\Http\Middleware\EnsureHotelPermission::class,
                    \App\Http\Middleware\CheckPlanAccess::class,
                ], true)) {
                    $target->setUserResolver(fn () => $request->user());
                    $target->setLaravelSession($request->session());
                    $response = app($class)->handle($target, fn () => response('', 204), ...array_filter(explode(',', $arguments)));
                    if ($response->getStatusCode() !== 204) {
                        return false;
                    }
                }
            }
        } catch (HttpExceptionInterface $e) {
            return false;
        }

        return true;
    }
}
