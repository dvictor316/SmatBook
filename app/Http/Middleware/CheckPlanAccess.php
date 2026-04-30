<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Support\PlanAccess;

class CheckPlanAccess
{
    public function handle(Request $request, Closure $next, ...$requiredPlans)
    {
        if ((bool) env('TEMP_OPEN_ACCESS', false)) {
            return $next($request);
        }

        $user = Auth::user();
        $normalizedUserPlan = PlanAccess::resolveTierForUser($user);

        if ($normalizedUserPlan === 'full') {
            return $next($request);
        }

        if (empty($requiredPlans)) {
            return $next($request);
        }

        $allowed = array_values(array_filter(array_map(fn ($plan) => $this->normalizePlan((string) $plan), $requiredPlans)));

        if (!in_array($normalizedUserPlan, $allowed, true)) {
            return redirect()->route('user.dashboard')
            ->with('error', 'Your current subscription plan does not include this feature.');
        }

        return $next($request);
    }

    private function normalizePlan(string $plan): string
    {
        return match (PlanAccess::normalizeTier($plan)) {
            'pro' => 'pro',
            'enterprise' => 'enterprise',
            'basic' => 'basic',
            'full' => 'full',
            default => strtolower(trim($plan)),
        };
    }
}
