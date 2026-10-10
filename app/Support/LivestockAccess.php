<?php

namespace App\Support;

use App\Models\Company;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class LivestockAccess
{
    public static function userIsLivestockTenant($user): bool
    {
        $companyId = (int) ($user?->company_id ?? 0);
        if ($companyId <= 0) {
            return false;
        }

        if (method_exists($user, 'isDemoUser') && $user->isDemoUser()) {
            return true;
        }

        $signals = [];
        if (Schema::hasTable('companies')) {
            $company = Company::withoutGlobalScopes()->find($companyId);
            $signals = [$company?->plan, $company?->businessTypeValue()];
        }
        if (Schema::hasTable('subscriptions')) {
            $subscription = DB::table('subscriptions')->where('company_id', $companyId)->latest('id')->first();
            $signals[] = $subscription->plan ?? null;
            $signals[] = $subscription->plan_name ?? null;
        }

        foreach ($signals as $signal) {
            $value = strtolower((string) $signal);
            if (str_contains($value, 'livestock') || str_contains($value, 'poultry') || str_contains($value, 'layer farm') || str_contains($value, 'agriculture')) {
                return true;
            }
        }

        return Schema::hasTable('livestock_farms') && DB::table('livestock_farms')->where('company_id', $companyId)->exists();
    }

    public static function livestockCompanyIds(): array
    {
        if (! Schema::hasTable('companies')) {
            return [];
        }

        $ids = collect();
        $companyColumns = array_values(array_filter(['plan', Company::businessTypeColumn()]));

        foreach ($companyColumns as $column) {
            if (! Schema::hasColumn('companies', $column)) {
                continue;
            }

            $ids = $ids->merge(DB::table('companies')->where(function ($query) use ($column) {
                foreach (['livestock', 'poultry', 'layer farm', 'agriculture'] as $term) {
                    $query->orWhereRaw("LOWER(COALESCE({$column}, '')) LIKE ?", ['%'.$term.'%']);
                }
            })->pluck('id'));
        }

        if (Schema::hasTable('subscriptions') && Schema::hasColumn('subscriptions', 'company_id')) {
            foreach (['plan', 'plan_name'] as $column) {
                if (! Schema::hasColumn('subscriptions', $column)) {
                    continue;
                }

                $ids = $ids->merge(DB::table('subscriptions')->where(function ($query) use ($column) {
                    foreach (['livestock', 'poultry', 'layer farm', 'agriculture'] as $term) {
                        $query->orWhereRaw("LOWER(COALESCE({$column}, '')) LIKE ?", ['%'.$term.'%']);
                    }
                })->pluck('company_id'));
            }
        }

        if (Schema::hasTable('livestock_farms')) {
            $ids = $ids->merge(DB::table('livestock_farms')->pluck('company_id'));
        }

        return $ids->map(fn ($id) => (int) $id)->filter()->unique()->values()->all();
    }
}
