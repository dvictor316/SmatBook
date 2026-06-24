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

        $stateManagerIds = $this->matchingUserIds([
            'thomas ogbodo',
            'dauda uche',
        ]);

        $dukeIds = $this->matchingUserIds([
            'ogbodo duke',
            'duke ogbodo',
        ]);

        if ($stateManagerIds !== []) {
            DB::table('users')
                ->whereIn('id', $stateManagerIds)
                ->update(array_filter([
                    'role' => 'state_manager',
                    'role_id' => $this->roleId('State Manager'),
                    'status' => Schema::hasColumn('users', 'status') ? 'active' : null,
                    'is_verified' => Schema::hasColumn('users', 'is_verified') ? 1 : null,
                    'email_verified_at' => Schema::hasColumn('users', 'email_verified_at') ? now() : null,
                    'updated_at' => Schema::hasColumn('users', 'updated_at') ? now() : null,
                ], fn ($value) => $value !== null));
        }

        if (Schema::hasTable('deployment_managers')) {
            foreach ($stateManagerIds as $userId) {
                $user = DB::table('users')->where('id', $userId)->first();
                if (!$user) {
                    continue;
                }

                DB::table('deployment_managers')->updateOrInsert(
                    ['user_id' => $userId],
                    array_filter([
                        'business_name' => $user->name ?? 'State Manager',
                        'phone' => Schema::hasColumn('deployment_managers', 'phone') ? ($user->phone ?? null) : null,
                        'status' => 'active',
                        'deployment_limit' => 100,
                        'country' => Schema::hasColumn('deployment_managers', 'country') ? ($user->country ?? null) : null,
                        'state_region' => Schema::hasColumn('deployment_managers', 'state_region') ? ($user->state_region ?? null) : null,
                        'local_council' => Schema::hasColumn('deployment_managers', 'local_council') ? ($user->local_council ?? null) : null,
                        'commission_rate' => Schema::hasColumn('deployment_managers', 'commission_rate') ? 35.00 : null,
                        'auto_payout_enabled' => Schema::hasColumn('deployment_managers', 'auto_payout_enabled') ? 1 : null,
                        'created_at' => Schema::hasColumn('deployment_managers', 'created_at') ? now() : null,
                        'updated_at' => Schema::hasColumn('deployment_managers', 'updated_at') ? now() : null,
                    ], fn ($value) => $value !== null)
                );
            }

            DB::table('deployment_managers')
                ->when($stateManagerIds !== [], fn ($query) => $query->whereNotIn('user_id', $stateManagerIds))
                ->update(array_filter([
                    'status' => 'suspended',
                    'updated_at' => Schema::hasColumn('deployment_managers', 'updated_at') ? now() : null,
                ], fn ($value) => $value !== null));
        }

        $agentUpdateIds = DB::table('users')
            ->whereNotIn(DB::raw("LOWER(COALESCE(role, ''))"), ['super_admin', 'superadmin', 'administrator', 'admin'])
            ->whereIn(DB::raw("LOWER(COALESCE(role, ''))"), ['deployment_manager', 'manager', 'state_manager'])
            ->when($stateManagerIds !== [], fn ($query) => $query->whereNotIn('id', $stateManagerIds))
            ->when($dukeIds !== [], fn ($query) => $query->whereNotIn('id', $dukeIds))
            ->pluck('id')
            ->all();

        if ($agentUpdateIds !== []) {
            DB::table('users')
                ->whereIn('id', $agentUpdateIds)
                ->update(array_filter([
                    'role' => 'agent',
                    'role_id' => $this->roleId('Agent'),
                    'updated_at' => Schema::hasColumn('users', 'updated_at') ? now() : null,
                ], fn ($value) => $value !== null));
        }
    }

    public function down(): void
    {
    }

    private function matchingUserIds(array $names): array
    {
        $query = DB::table('users');

        $query->where(function ($subQuery) use ($names) {
            foreach ($names as $name) {
                $normalized = strtolower(trim($name));
                $pattern = '%' . str_replace(' ', '%', $normalized) . '%';

                $subQuery->orWhereRaw('LOWER(TRIM(name)) = ?', [$normalized])
                    ->orWhereRaw('LOWER(TRIM(name)) LIKE ?', [$pattern])
                    ->orWhereRaw('LOWER(TRIM(email)) LIKE ?', [$pattern]);
            }
        });

        return $query->pluck('id')->map(fn ($id) => (int) $id)->all();
    }

    private function roleId(string $name): ?int
    {
        if (!Schema::hasTable('roles')) {
            return null;
        }

        $id = DB::table('roles')
            ->whereRaw('LOWER(name) = ?', [strtolower($name)])
            ->value('id');

        return $id ? (int) $id : null;
    }
};
