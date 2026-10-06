<?php

namespace App\Support;

use App\Models\Company;
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

        return Company::withoutGlobalScope('tenant')
            ->where(function ($query) use ($user, $primaryCompanyId) {
                $query->where('user_id', $user->id)
                    ->orWhere('owner_id', $user->id);

                if ($primaryCompanyId > 0) {
                    $query->orWhere('id', $primaryCompanyId);
                }
            })
            ->orderBy('name')
            ->get();
    }

    public function canAccess(User $user, int $companyId): bool
    {
        if (! Schema::hasTable('companies')) {
            return $companyId > 0
                && $companyId === (int) ($user->getRawOriginal('company_id') ?? 0);
        }

        return $companyId > 0 && $this->accessibleCompanies($user)->contains(
            fn (Company $company) => (int) $company->id === $companyId
        );
    }

    public function activeCompanyId(User $user): int
    {
        $sessionCompanyId = (int) session('current_tenant_id', 0);
        if ($this->canAccess($user, $sessionCompanyId)) {
            return $sessionCompanyId;
        }

        $primaryCompanyId = (int) ($user->getRawOriginal('company_id') ?? 0);
        if ($this->canAccess($user, $primaryCompanyId)) {
            return $primaryCompanyId;
        }

        return (int) ($this->accessibleCompanies($user)->first()?->id ?? 0);
    }

    public function activate(User $user, Company $company): void
    {
        abort_unless($this->canAccess($user, (int) $company->id), 403, 'You do not have access to this business workspace.');

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

        $user->setAttribute('company_id', (int) $company->id);
    }
}
