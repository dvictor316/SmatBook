<?php

namespace App\Support;

use App\Models\Plan;
use App\Models\Subscription;

class PlanAccess
{
    public static function resolveTierForUser($user): string
    {
        $role = strtolower(trim((string) ($user?->role ?? '')));

        if (in_array($role, ['super_admin', 'superadmin'], true)
            || strtolower((string) ($user?->email ?? '')) === 'donvictorlive@gmail.com') {
            return 'full';
        }

        $subscription = Subscription::resolveCurrentForUser($user);

        foreach (self::planCandidates($user, $subscription) as $candidate) {
            $tier = self::normalizeTier($candidate);
            if ($tier !== null) {
                return $tier;
            }
        }

        $amountTier = self::resolveTierFromSubscriptionAmount($subscription);
        if ($amountTier !== null) {
            return $amountTier;
        }

        return 'basic';
    }

    public static function normalizeTier(?string $plan): ?string
    {
        $value = strtolower(trim((string) $plan));

        if ($value === '') {
            return null;
        }

        if (in_array($value, ['super_admin', 'superadmin', 'full'], true)) {
            return 'full';
        }

        if (str_contains($value, 'enterprise')) {
            return 'enterprise';
        }

        if ($value === 'pro' || $value === 'professional' || str_contains($value, 'professional') || str_contains($value, 'pro ')) {
            return 'pro';
        }

        if (str_contains($value, 'basic')) {
            return 'basic';
        }

        return null;
    }

    private static function planCandidates($user, ?Subscription $subscription): array
    {
        return [
            $subscription?->planLabel(),
            optional($subscription?->plan_relationship)->name,
            optional($user?->company)->plan,
            session('user_plan'),
        ];
    }

    private static function resolveTierFromSubscriptionAmount(?Subscription $subscription): ?string
    {
        $amount = (float) ($subscription?->amount ?? 0);
        if ($amount <= 0) {
            return null;
        }

        $matchedPlan = Plan::query()
            ->where('price', $amount)
            ->orderByDesc('id')
            ->first();

        if ($matchedPlan) {
            return self::normalizeTier((string) $matchedPlan->name);
        }

        return null;
    }
}