<?php

namespace App\Support;

use App\Models\ActivityLog;
use App\Models\User;
use Illuminate\Support\Carbon;

class InternalTestAccess
{
    public function isEnabled(): bool
    {
        if (!config('internal.test_mode', false)) {
            return false;
        }

        $expiresAt = config('internal.test_mode_expires_at');
        if (empty($expiresAt)) {
            return true;
        }

        try {
            return Carbon::parse($expiresAt)->isFuture();
        } catch (\Throwable) {
            return false;
        }
    }

    public function canBypassSubscriptionOrPlan(?User $user): bool
    {
        if (!$user || !$this->isEnabled()) {
            return false;
        }

        if (!$user->internal_test_access_enabled) {
            return false;
        }

        if ($user->internal_test_access_expires_at && $user->internal_test_access_expires_at->isPast()) {
            return false;
        }

        return $user->hasRole('super_admin')
            || $user->hasRole('administrator')
            || $user->hasPermissionTo('manage_users');
    }

    public function canImpersonate(?User $user): bool
    {
        return $this->canBypassSubscriptionOrPlan($user)
            && $user?->hasRole('super_admin');
    }

    public function logUsage(User $user, string $action, array $properties = []): void
    {
        ActivityLog::record(
            'internal_test_mode',
            $action,
            'Controlled internal testing access used.',
            [
                'user_id' => $user->id,
                'company_id' => $user->company_id,
                'properties' => array_merge([
                    'internal_test_mode' => true,
                    'expires_at' => optional($user->internal_test_access_expires_at)?->toDateTimeString(),
                ], $properties),
            ]
        );
    }
}
