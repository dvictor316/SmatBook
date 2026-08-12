<?php

namespace App\Support;

use App\Models\Company;
use Illuminate\Support\Facades\Schema;

class HotelAccess
{
    public static function userIsHotelTenant($user): bool
    {
        $companyId = (int) ($user?->company_id ?? 0);
        if ($companyId <= 0) {
            return false;
        }

        if (Schema::hasTable('hotel_properties')) {
            $hasProperty = \DB::table('hotel_properties')->where('company_id', $companyId)->exists();
            if ($hasProperty) {
                return true;
            }
        }

        if (!Schema::hasTable('companies')) {
            return false;
        }

        $column = Company::businessTypeColumn();
        if (!$column) {
            return false;
        }

        $value = \DB::table('companies')->where('id', $companyId)->value($column);
        return str_contains(strtolower((string) $value), 'hotel');
    }

    public static function hotelCompanyIds(): array
    {
        if (!Schema::hasTable('companies')) {
            return [];
        }

        if (Schema::hasTable('hotel_properties')) {
            return \DB::table('hotel_properties')->select('company_id')->distinct()->pluck('company_id')->map(fn($id) => (int) $id)->all();
        }

        $column = Company::businessTypeColumn();
        if (!$column) {
            return [];
        }

        return \DB::table('companies')
            ->whereRaw("LOWER(COALESCE({$column}, '')) LIKE ?", ['%hotel%'])
            ->pluck('id')
            ->map(fn($id) => (int) $id)
            ->all();
    }
}
