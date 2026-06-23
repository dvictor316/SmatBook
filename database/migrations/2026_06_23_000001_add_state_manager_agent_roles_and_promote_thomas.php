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

        $stateManagerRoleId = $this->ensureRole(
            'State Manager',
            'Can deploy leads, manage agents, and monitor assigned clients',
            'Partnership'
        );
        $agentRoleId = $this->ensureRole(
            'Agent',
            'Field agent for sales, onboarding, and client follow-up',
            'Partnership'
        );

        $this->syncPermissionsByPrefix($stateManagerRoleId, ['deployment.', 'dashboard.']);
        $this->syncPermissionsByPrefix($agentRoleId, ['dashboard.', 'customers.', 'sales.', 'follow_ups.']);

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

        DB::table('users')
            ->whereIn('id', $thomasIds)
            ->update(array_filter([
                'role' => 'state_manager',
                'role_id' => $stateManagerRoleId,
                'status' => Schema::hasColumn('users', 'status') ? 'active' : null,
                'is_verified' => Schema::hasColumn('users', 'is_verified') ? 1 : null,
                'country' => Schema::hasColumn('users', 'country') ? 'Nigeria' : null,
                'state_region' => Schema::hasColumn('users', 'state_region') ? 'FCT' : null,
                'local_council' => Schema::hasColumn('users', 'local_council') ? null : null,
                'email_verified_at' => Schema::hasColumn('users', 'email_verified_at') ? now() : null,
                'updated_at' => Schema::hasColumn('users', 'updated_at') ? now() : null,
            ], fn ($value) => $value !== null));

        if (Schema::hasTable('deployment_managers')) {
            foreach ($thomasIds as $userId) {
                $payload = array_filter([
                    'user_id' => $userId,
                    'business_name' => 'Thomas Ogbodo',
                    'status' => 'active',
                    'deployment_limit' => 100,
                    'country' => Schema::hasColumn('deployment_managers', 'country') ? 'Nigeria' : null,
                    'state_region' => Schema::hasColumn('deployment_managers', 'state_region') ? 'FCT' : null,
                    'local_council' => Schema::hasColumn('deployment_managers', 'local_council') ? null : null,
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

        $agentUpdate = array_filter([
            'role' => 'agent',
            'role_id' => $agentRoleId,
            'updated_at' => Schema::hasColumn('users', 'updated_at') ? now() : null,
        ], fn ($value) => $value !== null);

        DB::table('users')
            ->whereNotIn('id', $thomasIds)
            ->whereNotIn(DB::raw('LOWER(COALESCE(role, ""))'), ['super_admin', 'superadmin', 'administrator', 'admin'])
            ->whereIn(DB::raw('LOWER(COALESCE(role, ""))'), ['deployment_manager', 'manager', 'state_manager'])
            ->update($agentUpdate);
    }

    public function down(): void
    {
        if (!Schema::hasTable('users')) {
            return;
        }

        DB::table('users')
            ->where(function ($query) {
                $query->whereRaw('LOWER(TRIM(name)) = ?', ['thomas ogbodo'])
                    ->orWhereRaw('LOWER(TRIM(name)) LIKE ?', ['%thomas%ogbodo%']);
            })
            ->where('role', 'state_manager')
            ->update(array_filter([
                'role' => 'agent',
                'role_id' => $this->roleId('Agent'),
                'updated_at' => Schema::hasColumn('users', 'updated_at') ? now() : null,
            ], fn ($value) => $value !== null));
    }

    private function ensureRole(string $name, string $description, string $group): ?int
    {
        if (!Schema::hasTable('roles')) {
            return null;
        }

        $existing = DB::table('roles')
            ->whereRaw('LOWER(name) = ?', [strtolower($name)])
            ->first();

        if ($existing) {
            return (int) $existing->id;
        }

        DB::table('roles')->insert([
            'name' => $name,
            'description' => $description,
            'role_group' => $group,
            'is_system_role' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $roleId = (int) DB::getPdo()->lastInsertId();
        return $roleId;
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

    private function syncPermissionsByPrefix(?int $roleId, array $prefixes): void
    {
        if (!$roleId || !Schema::hasTable('permissions') || !Schema::hasTable('role_has_permissions')) {
            return;
        }

        $permissionIds = DB::table('permissions')
            ->where(function ($query) use ($prefixes) {
                foreach ($prefixes as $prefix) {
                    $query->orWhere('name', 'like', $prefix . '%');
                }
            })
            ->pluck('id')
            ->all();

        foreach ($permissionIds as $permissionId) {
            DB::table('role_has_permissions')->updateOrInsert([
                'role_id' => $roleId,
                'permission_id' => $permissionId,
            ]);
        }
    }
};
