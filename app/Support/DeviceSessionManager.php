<?php

namespace App\Support;

use App\Models\ActiveUserSession;
use App\Models\Subscription;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class DeviceSessionManager
{
    private const DEVICE_COOKIE = 'spb_device_id';

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
        $this->pruneMissingBackedSessions($user->id, $companyId);
        $this->pruneLegacySameBrowserSessions($request, $user->id, $sessionId);

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
                ->distinct('user_id')
                ->count('user_id');

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

    private function pruneMissingBackedSessions(?int $userId = null, ?int $companyId = null): void
    {
        if (!Schema::hasTable('active_user_sessions')) {
            return;
        }

        $query = ActiveUserSession::query();

        if ($userId) {
            $query->where('user_id', $userId);
        }

        if ($companyId) {
            $query->where('company_id', $companyId);
        }

        $query->get()->each(function (ActiveUserSession $session) {
            if (!$this->backingSessionExists((string) ($session->session_id ?? ''))) {
                $session->delete();
            }
        });
    }

    private function backingSessionExists(string $sessionId): bool
    {
        if ($sessionId === '') {
            return false;
        }

        $driver = strtolower((string) config('session.driver', 'file'));

        if ($driver === 'file') {
            $sessionPath = rtrim((string) config('session.files'), DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . $sessionId;

            return File::exists($sessionPath);
        }

        if ($driver === 'database') {
            $table = (string) config('session.table', 'sessions');
            if (!Schema::hasTable($table)) {
                return false;
            }

            return DB::table($table)->where('id', $sessionId)->exists();
        }

        return true;
    }

    private function pruneLegacySameBrowserSessions(Request $request, int $userId, string $sessionId): void
    {
        $userAgent = substr((string) $request->userAgent(), 0, 1000);
        if ($userAgent === '') {
            return;
        }

        // Older fingerprints included the IP address. Store/branch switching can
        // regenerate sessions or move between host contexts, leaving a stale
        // active-session row that blocks the same browser from logging back in.
        ActiveUserSession::query()
            ->where('user_id', $userId)
            ->where('session_id', '!=', $sessionId)
            ->where('user_agent', $userAgent)
            ->delete();
    }

    private function allowedUserSessions(User $user): ?int
    {
        if ($this->isSuperAdmin($user)) {
            return 2;
        }

        if ($this->isPlatformStaff($user)) {
            return null;
        }

        // Free unlimited workspaces created by superadmin are a sponsorship-style
        // license: each account can only be active on one system at a time.
        // Paid plans, including paid custom licenses, return null here and are
        // controlled by the workspace seat limit below.
        return $this->isFreeCustomOnlyAccount($user) ? 1 : null;
    }

    private function allowedWorkspaceSessions(User $user, ?int $companyId): ?int
    {
        if ($this->isSuperAdmin($user) || !$companyId) {
            return null;
        }

        $subscription = Subscription::resolveCurrentForUser($user);

        // Paid/custom paid licenses use the purchased user_limit as the normal
        // seat allowance. Example: a paid custom license for 15 users allows
        // up to 15 distinct users in the workspace.
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

    private function isPlatformStaff(User $user): bool
    {
        $role = strtolower(trim((string) ($user->role ?? '')));

        return in_array($role, [
            'state_manager',
            'deployment_manager',
            'agent',
            'sales_agent',
        ], true);
    }

    private function isFreeCustomOnlyAccount(User $user): bool
    {
        $subscription = Subscription::resolveCurrentForUser($user);

        if (!$subscription) {
            return false;
        }

        $companyId = $this->resolveCompanyId($user);
        $freeCurrentSubscription = strtolower((string) ($subscription->payment_status ?? '')) === 'free'
            && strtolower((string) ($subscription->status ?? '')) === 'active';

        if (!$freeCurrentSubscription) {
            return false;
        }

        return !$this->hasActivePaidLicense($user, $companyId);
    }

    private function hasActivePaidLicense(User $user, ?int $companyId): bool
    {
        return Subscription::withoutGlobalScope('tenant')
            ->where(function ($query) use ($user, $companyId) {
                $query->where('user_id', $user->id);

                if ($companyId) {
                    $query->orWhere('company_id', $companyId);
                }
            })
            ->whereIn(DB::raw("LOWER(COALESCE(payment_status, ''))"), [
                'paid',
                'completed',
                'success',
                'successful',
                'verified',
            ])
            ->whereIn(DB::raw("LOWER(COALESCE(status, ''))"), ['active', 'trial'])
            ->exists();
    }

    private function userLimitMessage(User $user, int $limit): string
    {
        if ($this->isSuperAdmin($user)) {
            return "Super admin access is limited to {$limit} devices at a time. Log out from another device first.";
        }

        if ($this->isFreeCustomOnlyAccount($user)) {
            return 'This free custom account is already active on another system. Please log out from that system first.';
        }

        return 'This account is already active on another device. Please log out from that device first.';
    }

    private function fingerprint(Request $request): string
    {
        $deviceId = (string) $request->cookies->get(self::DEVICE_COOKIE, '');

        if ($deviceId === '') {
            $deviceId = (string) $request->session()->get(self::DEVICE_COOKIE, '');
        }

        if ($deviceId === '') {
            $deviceId = (string) Str::uuid();
        }

        $request->session()->put(self::DEVICE_COOKIE, $deviceId);
        cookie()->queue(cookie(self::DEVICE_COOKIE, $deviceId, 60 * 24 * 365 * 2, null, null, $request->isSecure(), true, false, 'Lax'));

        return sha1(implode('|', [
            (string) $request->userAgent(),
            $deviceId,
        ]));
    }
}
