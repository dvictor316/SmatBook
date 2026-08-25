<?php

namespace App\Support;

use App\Models\Company;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class HotelAccess
{
    public static function userIsHotelTenant($user): bool
    {
        $companyId = (int) ($user?->company_id ?? 0);
        if ($companyId <= 0) {
            return false;
        }

        foreach (self::hotelSignalsForUser($user, $companyId) as $signal) {
            if (str_contains(strtolower((string) $signal), 'hotel') || str_contains(strtolower((string) $signal), 'hospitality')) {
                return true;
            }
        }

        return false;
    }

    public static function hotelCompanyIds(): array
    {
        if (!Schema::hasTable('companies')) {
            return [];
        }

        $ids = collect();
        $column = Company::businessTypeColumn();

        if ($column) {
            $ids = $ids->merge(DB::table('companies')
                ->whereRaw("LOWER(COALESCE({$column}, '')) LIKE ?", ['%hotel%'])
                ->orWhereRaw("LOWER(COALESCE({$column}, '')) LIKE ?", ['%hospitality%'])
                ->pluck('id'));
        }

        if (Schema::hasColumn('companies', 'plan')) {
            $ids = $ids->merge(DB::table('companies')
                ->whereRaw("LOWER(COALESCE(plan, '')) LIKE ?", ['%hotel%'])
                ->pluck('id'));
        }

        if (Schema::hasTable('subscriptions') && Schema::hasColumn('subscriptions', 'company_id')) {
            $subscriptionQuery = DB::table('subscriptions')
                ->whereNotNull('company_id')
                ->where(function ($query) {
                    if (Schema::hasColumn('subscriptions', 'plan')) {
                        $query->whereRaw("LOWER(COALESCE(plan, '')) LIKE ?", ['%hotel%']);
                    }

                    if (Schema::hasColumn('subscriptions', 'plan_name')) {
                        $query->orWhereRaw("LOWER(COALESCE(plan_name, '')) LIKE ?", ['%hotel%']);
                    }
                });

            $ids = $ids->merge($subscriptionQuery->pluck('company_id'));
        }

        return $ids
            ->map(fn($id) => (int) $id)
            ->filter()
            ->unique()
            ->values()
            ->all();
    }

    private static function hotelSignalsForUser($user, int $companyId): array
    {
        $signals = [];

        if (Schema::hasTable('companies')) {
            $companyColumns = array_values(array_filter([
                'plan',
                Company::businessTypeColumn(),
            ], fn ($column) => $column && Schema::hasColumn('companies', $column)));

            if (!empty($companyColumns)) {
                $company = DB::table('companies')
                    ->where('id', $companyId)
                    ->first($companyColumns);

                foreach ($companyColumns as $column) {
                    $signals[] = $company->{$column} ?? null;
                }
            }
        }

        if (Schema::hasTable('subscriptions')) {
            $subscriptionColumns = array_values(array_filter([
                Schema::hasColumn('subscriptions', 'plan') ? 'plan' : null,
                Schema::hasColumn('subscriptions', 'plan_name') ? 'plan_name' : null,
            ]));

            if (!empty($subscriptionColumns)) {
                $subscription = DB::table('subscriptions')
                    ->where(function ($query) use ($user, $companyId) {
                        if (Schema::hasColumn('subscriptions', 'company_id')) {
                            $query->where('company_id', $companyId);
                        }

                        if ($user?->id && Schema::hasColumn('subscriptions', 'user_id')) {
                            $query->orWhere('user_id', $user->id);
                        }
                    })
                    ->latest('id')
                    ->first($subscriptionColumns);

                foreach ($subscriptionColumns as $column) {
                    $signals[] = $subscription->{$column} ?? null;
                }
            }
        }

        return array_filter($signals, fn ($signal) => trim((string) $signal) !== '');
    }
}
