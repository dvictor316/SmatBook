<?php

namespace App\Support;

use App\Models\ActiveUserSession;
use App\Models\Subscription;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;

class DeviceSessionManager
{
    public function ensureCurrentSession(Request $request, User $user): array
    {
        if (!Schema::hasTable('active_user_sessions')) {
            return ['allowed' => true];
        }

        $sessionId = (string) $request->session()->getId();
        if ($sessionId === '') {
            return ['allowed' => true];
        }

        $companyId = $this->resolveCompanyId($user);
        $fingerprint = $this->fingerprint($request);
        $this->pruneExpired($user->id, $companyId);

        ActiveUserSession::query()
            ->where('user_id', $user->id)
            ->where('device_fingerprint', $fingerprint)
            ->where('session_id', '!=', $sessionId)
            ->delete();

        $current = ActiveUserSession::query()
            ->where('session_id', $sessionId)
            ->first();

        if ($current) {
            $current->forceFill([
                'last_seen_at' => now(),
                'ip_address' => $request->ip(),
                'user_agent' => substr((string) $request->userAgent(), 0, 1000),
                'company_id' => $companyId,
            ])->save();

            return ['allowed' => true];
        }

        $userLimit = $this->allowedUserSessions($user);
        $activeUserSessions = ActiveUserSession::query()
            ->where('user_id', $user->id)
            ->count();

        if ($userLimit !== null && $activeUserSessions >= $userLimit) {
            return [
                'allowed' => false,
                'message' => $this->userLimitMessage($user, $userLimit),
            ];
        }

        $workspaceLimit = $this->allowedWorkspaceSessions($user, $companyId);
        if ($workspaceLimit !== null && $companyId) {
            $activeWorkspaceSessions = ActiveUserSession::query()
                ->where('company_id', $companyId)
                ->count();

            if ($activeWorkspaceSessions >= $workspaceLimit) {
                return [
                    'allowed' => false,
                    'message' => "This workspace has reached its plan device limit of {$workspaceLimit}. Log out from another device or upgrade the plan.",
                ];
            }
        }

        ActiveUserSession::query()->create([
            'user_id' => $user->id,
            'company_id' => $companyId,
            'session_id' => $sessionId,
            'device_fingerprint' => $fingerprint,
            'ip_address' => $request->ip(),
            'user_agent' => substr((string) $request->userAgent(), 0, 1000),
            'authenticated_at' => now(),
            'last_seen_at' => now(),
        ]);

        return ['allowed' => true];
    }

    public function forgetCurrentSession(Request $request): void
    {
        if (!Schema::hasTable('active_user_sessions')) {
            return;
        }

        $sessionId = (string) $request->session()->getId();
        if ($sessionId === '') {
            return;
        }

        ActiveUserSession::query()
            ->where('session_id', $sessionId)
            ->delete();
    }

    private function pruneExpired(?int $userId = null, ?int $companyId = null): void
    {
        if (!Schema::hasTable('active_user_sessions')) {
            return;
        }

        $cutoff = now()->subMinutes((int) config('session.lifetime', 120));

        $query = ActiveUserSession::query()->where('last_seen_at', '<', $cutoff);

        if ($userId) {
            $query->orWhere(function ($sub) use ($userId, $cutoff) {
                $sub->where('user_id', $userId)->where('last_seen_at', '<', $cutoff);
            });
        }

        if ($companyId) {
            $query->orWhere(function ($sub) use ($companyId, $cutoff) {
                $sub->where('company_id', $companyId)->where('last_seen_at', '<', $cutoff);
            });
        }

        $query->delete();
    }

    private function allowedUserSessions(User $user): ?int
    {
        return $this->isSuperAdmin($user) ? 2 : 1;
    }

    private function allowedWorkspaceSessions(User $user, ?int $companyId): ?int
    {
        if ($this->isSuperAdmin($user) || !$companyId) {
            return null;
        }

        $subscription = Subscription::resolveCurrentForUser($user);

        return $subscription?->resolvedUserLimit();
    }

    private function resolveCompanyId(User $user): ?int
    {
        $companyId = (int) ($user->company_id ?? 0);
        if ($companyId > 0) {
            return $companyId;
        }

        $ownedCompanyId = (int) ($user->ownedCompany?->id ?? 0);
        if ($ownedCompanyId > 0) {
            return $ownedCompanyId;
        }

        $subscription = Subscription::resolveCurrentForUser($user);
        $subscriptionCompanyId = (int) ($subscription?->company_id ?? 0);

        return $subscriptionCompanyId > 0 ? $subscriptionCompanyId : null;
    }

    private function isSuperAdmin(User $user): bool
    {
        $role = strtolower(trim((string) ($user->role ?? '')));

        return in_array($role, ['super_admin', 'superadmin'], true)
            || strtolower((string) $user->email) === 'donvictorlive@gmail.com';
    }

    private function userLimitMessage(User $user, int $limit): string
    {
        if ($this->isSuperAdmin($user)) {
            return "Super admin access is limited to {$limit} devices at a time. Log out from another device first.";
        }

        return 'This account is already active on another device. Please log out from that device first.';
    }

    private function fingerprint(Request $request): string
    {
        return sha1(implode('|', [
            (string) $request->userAgent(),
            (string) $request->ip(),
        ]));
    }
}
