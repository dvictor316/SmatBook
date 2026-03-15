<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class CheckRole
{
    /**
     * Handle an incoming request.
     * domain => env('SESSION_DOMAIN', null)
     */
    public function handle(Request $request, Closure $next, ...$roles): Response
    {
        if (!Auth::check()) {
            return redirect()->route('login');
        }

        if ((bool) env('TEMP_OPEN_ACCESS', false)) {
            return $next($request);
        }

        $user = Auth::user();
        $allowedRoles = collect($roles)
            ->map(fn ($role) => $this->normalizeRoleKey($role))
            ->filter()
            ->values()
            ->all();

        // Master Access for specific email
        if ($user->email === 'donvictorlive@gmail.com') {
            return $next($request);
        }

        // Super Admin bypass: They can go anywhere
        if ($this->normalizeRoleKey($user->role ?? null) === 'super_admin') {
            return $next($request);
        }

        $relatedRoleName = method_exists($user, 'role') ? optional($user->role()->first())->name : null;
        $userRoleKeys = collect([
            $this->normalizeRoleKey($user->role ?? null),
            $this->normalizeRoleKey($relatedRoleName),
        ])->filter()->unique()->values()->all();

        // Check if the user's role is in the allowed list
        if (array_intersect($userRoleKeys, $allowedRoles)) {
            return $next($request);
        }

        // Unauthorized access
        abort(403, 'You do not have permission to access this resource.');
    }

    private function normalizeRoleKey(?string $role): ?string
    {
        $normalized = strtolower(trim((string) $role));

        if ($normalized === '') {
            return null;
        }

        return match ($normalized) {
            'super admin', 'super_admin', 'superadmin' => 'super_admin',
            'administrator', 'admin' => 'administrator',
            'deployment manager', 'deployment_manager', 'manager' => 'deployment_manager',
            'store manager', 'store_manager' => 'store_manager',
            'sales manager', 'sales_manager' => 'sales_manager',
            'finance manager', 'finance_manager' => 'finance_manager',
            'account officer', 'account_officer', 'accountant' => 'accountant',
            default => Str::snake(str_replace('-', ' ', $normalized)),
        };
    }
}
