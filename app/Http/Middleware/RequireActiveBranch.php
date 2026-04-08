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
        if ($this->activeBranchResolver->ensureSession($request->user())) {
            return $next($request);
        }

        $path = ltrim($request->path(), '/');
        $allow = [
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

        if ($request->expectsJson()) {
            return response()->json(['message' => $message], 422);
        }

        return redirect()
            ->to(\App\Support\SafeRoute::to('branches.index', '/settings/branches'))
            ->with('error', $message);
    }
}
