<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class RequireActiveBranch
{
    public function handle(Request $request, Closure $next)
    {
        $branchId = trim((string) session('active_branch_id', ''));
        $branchName = trim((string) session('active_branch_name', ''));

        if ($branchId !== '' || $branchName !== '') {
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
