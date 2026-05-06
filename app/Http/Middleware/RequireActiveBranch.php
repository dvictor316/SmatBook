<?php

namespace App\Http\Middleware;

use App\Support\ActiveBranchResolver;
use Closure;
use Illuminate\Http\Request;

class RequireActiveBranch
{
    public function __construct(
        private readonly ActiveBranchResolver $activeBranchResolver
    ) {
    }

    public function handle(Request $request, Closure $next)
    {
        // Super admin has no branch — give full pass-through.
        $user = $request->user();
        if ($user && in_array(strtolower((string) ($user->role ?? '')), ['super_admin', 'superadmin'], true)) {
            return $next($request);
        }

        // "All branches" scope is a valid, intentional selection — let it through.
        if ($request->session()->get('active_branch_scope') === 'all') {
            return $next($request);
        }

        if ($this->activeBranchResolver->ensureSession($user)) {
            return $next($request);
        }

        $path = ltrim($request->path(), '/');
        $allow = [
            '',
            'home',
            'dashboard',
            'settings/branches',
            'settings/branches/activate',
            'branches',
            'settings',
        ];

        foreach ($allow as $prefix) {
            if ($path === $prefix || str_starts_with($path, $prefix . '/')) {
                return $next($request);
            }
        }

        $message = 'Please select an active branch to continue.';

        if ($this->shouldReturnJson($request)) {
            return response()->json(['message' => $message], 422);
        }

        return redirect()
            ->to(\App\Support\SafeRoute::to('branches.index', '/settings/branches'))
            ->with('error', $message);
    }

    private function shouldReturnJson(Request $request): bool
    {
        return $request->expectsJson()
            || $request->wantsJson()
            || $request->ajax()
            || strtolower((string) $request->header('X-Requested-With')) === 'xmlhttprequest';
    }
}
