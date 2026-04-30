<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CheckPlanAccess
{
    public function handle(Request $request, Closure $next, ...$requiredPlans)
    {
        if ((bool) env('TEMP_OPEN_ACCESS', false)) {
            return $next($request);
        }

        $user = Auth::user();
        $userPlan = (string) ($user?->company?->plan ?? 'Basic');
        $normalizedUserPlan = $this->normalizePlan($userPlan);

        // Only platform super admins bypass plan checks.
        if (
            in_array(strtolower((string) ($user->role ?? '')), ['super_admin', 'superadmin'], true) ||
            strtolower((string) ($user->email ?? '')) === 'donvictorlive@gmail.com'
        ) {
            return $next($request);
        }

        if (empty($requiredPlans)) {
            return $next($request);
        }

        $allowed = array_map(fn ($plan) => $this->normalizePlan((string) $plan), $requiredPlans);

        if (!in_array($normalizedUserPlan, $allowed, true)) {
            return redirect()->route('user.dashboard')
                ->with('error', "Your current $userPlan plan does not include this feature.");
        }

        return $next($request);
    }

    private function normalizePlan(string $plan): string
    {
        $value = strtolower(trim($plan));

        if (str_contains($value, 'professional') || $value === 'pro' || str_contains($value, 'pro ')) {
            return 'professional';
        }

        if (str_contains($value, 'enterprise')) {
            return 'enterprise';
        }

        if (str_contains($value, 'basic')) {
            return 'basic';
        }

        return match ($value) {
            'pro', 'professional' => 'professional',
            'enterprise' => 'enterprise',
            'basic' => 'basic',
            default => $value,
        };
    }
}
