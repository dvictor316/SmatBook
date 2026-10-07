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
}
