<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('roles') || !Schema::hasTable('permissions') || !Schema::hasTable('role_has_permissions')) {
            return;
        }

        $roleIds = DB::table('roles')
            ->whereIn(DB::raw('LOWER(name)'), ['state manager', 'state_manager', 'deployment manager', 'deployment_manager', 'manager'])
            ->pluck('id')
            ->all();

        if ($roleIds === []) {
            return;
        }

        $allowedPermissionIds = DB::table('permissions')
            ->where(function ($query) {
                $query->where('name', 'dashboard.overview.view')
                    ->orWhere('name', 'like', 'deployment.%')
                    ->orWhereIn('name', [
                        'customers.customers.view',
                        'customers.customers.view_own',
                        'customers.customers.create',
                    ]);
            })
            ->pluck('id')
            ->all();

        DB::table('role_has_permissions')
            ->whereIn('role_id', $roleIds)
            ->delete();

        foreach ($roleIds as $roleId) {
            foreach ($allowedPermissionIds as $permissionId) {
                DB::table('role_has_permissions')->updateOrInsert(
                    ['role_id' => $roleId, 'permission_id' => $permissionId],
                    []
                );
            }
        }
    }

    public function down(): void
    {
        // Intentionally non-destructive: previous broad permission sets cannot be inferred safely.
    }
};
