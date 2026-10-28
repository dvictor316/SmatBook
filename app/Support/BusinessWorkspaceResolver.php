<?php

namespace App\Support;

use App\Models\Company;
use App\Models\Setting;
use App\Models\Subscription;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Schema;

class BusinessWorkspaceResolver
{
    public function accessibleCompanies(User $user): Collection
    {
        if (! Schema::hasTable('companies')) {
            return collect();
        }

        $primaryCompanyId = (int) ($user->getRawOriginal('company_id') ?? 0);

        $retiredIds = Schema::hasTable('settings')
            ? Setting::where('key', 'like', 'workspace_retired_company_%')->pluck('key')
                ->map(fn ($key) => preg_match('/^workspace_retired_company_(\d+)$/', $key, $match) ? (int) $match[1] : 0)->filter()->all()
            : [];

        return Company::withoutGlobalScope('tenant')
            ->whereNotIn('id', $retiredIds)
            ->where(function ($query) use ($user, $primaryCompanyId) {
                // Disagreeing owner fields must not grant a second account access.
                $query->where(function ($owned) use ($user) {
                    $owned->where('user_id', $user->id)
                        ->where(fn ($owner) => $owner->whereNull('owner_id')->orWhere('owner_id', 0)->orWhere('owner_id', $user->id));
                })->orWhere(function ($legacyOwned) use ($user) {
                    $legacyOwned->where('owner_id', $user->id)
                        ->where(fn ($owner) => $owner->whereNull('user_id')->orWhere('user_id', 0)->orWhere('user_id', $user->id));
                });

                if ($primaryCompanyId > 0) {
                    $query->orWhere(function ($assigned) use ($user, $primaryCompanyId) {
                        $assigned->where('id', $primaryCompanyId);
                        // Platform privileges do not confer ownership of a client's business.
                        if ($user->isSuperAdmin()) {
                            $assigned->where(fn ($q) => $q->whereNull('user_id')->orWhere('user_id', 0))
                                ->where(fn ($q) => $q->whereNull('owner_id')->orWhere('owner_id', 0));
                        }
                    });
                }
            })
            ->orderBy('name')
            ->get();
    }

    public function canAccess(User $user, int $companyId, ?Collection $accessibleCompanies = null): bool
    {
        if ($accessibleCompanies !== null) {
            return $companyId > 0 && $accessibleCompanies->contains(
                fn (Company $company) => (int) $company->id === $companyId
            );
        }

        if (! Schema::hasTable('companies')) {
            return $companyId > 0
                && $companyId === (int) ($user->getRawOriginal('company_id') ?? 0);
        }

        return $companyId > 0 && $this->accessibleCompanies($user)->contains(
            fn (Company $company) => (int) $company->id === $companyId
        );
    }

    public function activeCompanyId(User $user, ?Collection $accessibleCompanies = null): int
    {
        $sessionCompanyId = (int) session('current_tenant_id', 0);
        $primaryCompanyId = (int) ($user->getRawOriginal('company_id') ?? 0);
        $isSuperAdmin = $user->isSuperAdmin();

        if (
            $sessionCompanyId > 0
            && $sessionCompanyId === $primaryCompanyId
            && !$isSuperAdmin
            && (!Schema::hasTable('companies') || Company::withoutGlobalScope('tenant')->whereKey($sessionCompanyId)->exists())
            && (!Schema::hasTable('settings') || !Setting::where('key', 'workspace_retired_company_'.$sessionCompanyId)->exists())
        ) {
            return $sessionCompanyId;
        }

        if ($this->canAccess($user, $sessionCompanyId, $accessibleCompanies)) {
            return $sessionCompanyId;
        }

        if ($this->canAccess($user, $primaryCompanyId, $accessibleCompanies)) {
            return $primaryCompanyId;
        }

        $accessibleCompanies ??= $this->accessibleCompanies($user);

        return (int) ($accessibleCompanies->first()?->id ?? 0);
    }

    public function clearContext(User $user): void
    {
        session()->forget([
            'current_tenant_id', 'current_tenant_name', 'active_branch_id',
            'active_branch_name', 'active_branch_scope', 'is_demo_workspace', 'user_plan',
        ]);
        // This is request-local; never overwrite the user's primary assignment in the database.
        $user->setAttribute('company_id', null);
        $user->setRelation('company', null);
    }

    public function bindCompany(User $user, Company $company): void
    {
        session([
            'current_tenant_id' => (int) $company->id,
            'current_tenant_name' => $company->name ?: ($company->company_name ?: 'Business'),
        ]);
        $user->setAttribute('company_id', (int) $company->id);
        $user->setRelation('company', $company);
    }

    public function activate(User $user, Company $company, ?Collection $accessibleCompanies = null): void
    {
        abort_unless(
            $this->canAccess($user, (int) $company->id, $accessibleCompanies),
            403,
            'You do not have access to this business workspace.'
        );

        $subscription = Schema::hasTable('subscriptions')
            ? Subscription::withoutGlobalScope('tenant')
                ->where('company_id', $company->id)
                ->orderByRaw("CASE WHEN LOWER(COALESCE(status, '')) IN ('active', 'trial') THEN 0 ELSE 1 END")
                ->latest('id')
                ->first()
            : null;

        session()->forget([
            'active_branch_id',
            'active_branch_name',
            'active_branch_scope',
            'is_demo_workspace',
        ]);

        session([
            'current_tenant_id' => (int) $company->id,
            'current_tenant_name' => $company->name ?? $company->company_name ?? 'Business',
            'workspace_context' => 'business',
            'user_plan' => strtolower((string) ($subscription?->planLabel() ?: $company->plan ?: 'basic')),
        ]);

        $this->bindCompany($user, $company);
    }
}
