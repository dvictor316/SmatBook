<?php

namespace App\Support;

use App\Models\Company;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class SuperAdminBusinessWorkspace
{
    public function activate(User $user): Company
    {
        abort_unless(in_array(strtolower((string) $user->role), ['super_admin', 'superadmin'], true), 403);
        $resolver = app(BusinessWorkspaceResolver::class);

        $company = DB::transaction(function () use ($user, $resolver) {
            // Serialize first-time provisioning so concurrent tabs cannot create duplicate businesses.
            $account = User::withoutGlobalScopes()->lockForUpdate()->findOrFail($user->id);
            $accessible = $resolver->accessibleCompanies($account)->filter(function (Company $candidate) use ($account) {
                // Conflicting ownership must never upgrade or take over a client's company.
                foreach (['user_id', 'owner_id'] as $ownerColumn) {
                    if ((int) $candidate->{$ownerColumn} > 0 && (int) $candidate->{$ownerColumn} !== (int) $account->id) {
                        return false;
                    }
                }
                if (! $candidate->user_id && ! $candidate->owner_id) {
                    return ! User::withoutGlobalScopes()->where('company_id', $candidate->id)
                        ->whereKeyNot($account->id)->exists();
                }
                return true;
            });
            $company = $accessible->firstWhere('id', (int) session('current_tenant_id'))
                ?? $accessible->firstWhere('id', (int) $account->company_id)
                ?? $accessible->first();

            if (! $company) {
                $company = Company::withoutGlobalScope('tenant')->create([
                    'user_id' => $account->id,
                    'owner_id' => $account->id,
                    'name' => ($account->name ?: 'Super Admin').' Enterprise Business',
                    'email' => $account->email,
                    'status' => 'active',
                    'plan' => 'enterprise',
                    'country' => 'Nigeria',
                    'currency_code' => 'NGN',
                    'currency_symbol' => '₦',
                ]);
            } else {
                // Only this account's authorized business receives the complimentary entitlement.
                $company->plan = 'enterprise';
                if (! $company->user_id && ! $company->owner_id) {
                    $company->user_id = $account->id;
                    $company->owner_id = $account->id;
                }
                if ($company->isDirty()) {
                    $company->save();
                }
            }

            if (! $accessible->contains('id', (int) $account->company_id)) {
                $account->company_id = $company->id;
                $account->save();
            }
            $user->setAttribute('company_id', $account->company_id);
            $user->syncOriginalAttribute('company_id');

            return $company;
        });

        if ((int) session('current_tenant_id') !== (int) $company->id) {
            $resolver->activate($user, $company);
        } else {
            $resolver->bindCompany($user, $company);
        }
        session(['workspace_context' => 'business', 'user_plan' => 'enterprise']);
        app(ActiveBranchResolver::class)->ensureSession($user);

        return $company;
    }
}
