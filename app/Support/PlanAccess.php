<?php

namespace App\Support;

use App\Models\Plan;
use App\Models\Subscription;

class PlanAccess
{
    public static function resolveTierForUser($user): string
    {
        if (($user && method_exists($user, 'isSuperAdmin') && $user->isSuperAdmin())
            || strtolower((string) ($user?->email ?? '')) === 'donvictorlive@gmail.com') {
            return 'full';
        }

        if ($user?->isDemoUser()) {
            return self::normalizeTier(session('demo_customer_preview_plan')) ?? 'basic';
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

        if (str_contains($value, 'starter')) {
            return 'starter';
        }

        if (str_contains($value, 'hotel') || str_contains($value, 'hospitality')) {
            return 'hotel';
        }

        if (str_contains($value, 'livestock') || str_contains($value, 'poultry') || str_contains($value, 'layer farm')) {
            return 'enterprise';
        }

        if (str_contains($value, 'enterprise') || str_contains($value, 'institutional')) {
            return 'enterprise';
        }

        if ($value === 'pro' || str_starts_with($value, 'pro-') || str_contains($value, 'professional')
            || str_contains($value, 'pro ') || str_contains($value, 'premium')) {
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
            $subscription?->plan_name,
            $subscription?->plan,
            $subscription?->plan_relationship?->name,
            $subscription?->company?->plan ?? $user?->company?->plan,
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
