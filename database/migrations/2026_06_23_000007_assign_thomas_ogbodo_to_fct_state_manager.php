<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('users')) {
            return;
        }

        $thomasIds = DB::table('users')
            ->where(function ($query) {
                $query->whereRaw('LOWER(TRIM(name)) = ?', ['thomas ogbodo'])
                    ->orWhereRaw('LOWER(TRIM(name)) LIKE ?', ['%thomas%ogbodo%']);
            })
            ->pluck('id')
            ->all();

        if ($thomasIds === []) {
            return;
        }

        $userPayload = array_filter([
            'role' => 'state_manager',
            'status' => Schema::hasColumn('users', 'status') ? 'active' : null,
            'is_verified' => Schema::hasColumn('users', 'is_verified') ? 1 : null,
            'country' => Schema::hasColumn('users', 'country') ? 'Nigeria' : null,
            'state_region' => Schema::hasColumn('users', 'state_region') ? 'FCT' : null,
            'updated_at' => Schema::hasColumn('users', 'updated_at') ? now() : null,
        ], fn ($value) => $value !== null);

        DB::table('users')->whereIn('id', $thomasIds)->update($userPayload);

        if (!Schema::hasTable('deployment_managers')) {
            return;
        }

        foreach ($thomasIds as $userId) {
            $payload = array_filter([
                'user_id' => $userId,
                'business_name' => 'Thomas Ogbodo',
                'status' => 'active',
                'deployment_limit' => 100,
                'country' => Schema::hasColumn('deployment_managers', 'country') ? 'Nigeria' : null,
                'state_region' => Schema::hasColumn('deployment_managers', 'state_region') ? 'FCT' : null,
                'commission_rate' => Schema::hasColumn('deployment_managers', 'commission_rate') ? 35.00 : null,
                'auto_payout_enabled' => Schema::hasColumn('deployment_managers', 'auto_payout_enabled') ? 1 : null,
                'created_at' => Schema::hasColumn('deployment_managers', 'created_at') ? now() : null,
                'updated_at' => Schema::hasColumn('deployment_managers', 'updated_at') ? now() : null,
            ], fn ($value) => $value !== null);

            DB::table('deployment_managers')->updateOrInsert(
                ['user_id' => $userId],
                $payload
            );
        }
    }

    public function down(): void
    {
        if (!Schema::hasTable('users')) {
            return;
        }

        $payload = [];
        if (Schema::hasColumn('users', 'country')) {
            $payload['country'] = null;
        }
        if (Schema::hasColumn('users', 'state_region')) {
            $payload['state_region'] = null;
        }
        if (Schema::hasColumn('users', 'updated_at')) {
            $payload['updated_at'] = now();
        }

        if ($payload === []) {
            return;
        }

        DB::table('users')
            ->where(function ($query) {
                $query->whereRaw('LOWER(TRIM(name)) = ?', ['thomas ogbodo'])
                    ->orWhereRaw('LOWER(TRIM(name)) LIKE ?', ['%thomas%ogbodo%']);
            })
            ->update($payload);
    }
};
