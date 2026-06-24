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
            'ogbodo thomas',
            'dauda uche',
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
                        'status' => 'active',
                        'deployment_limit' => 100,
                        'country' => Schema::hasColumn('deployment_managers', 'country') ? ($user->country ?? null) : null,
                        'state_region' => Schema::hasColumn('deployment_managers', 'state_region') ? ($user->state_region ?? null) : null,
                        'local_council' => Schema::hasColumn('deployment_managers', 'local_council') ? ($user->local_council ?? null) : null,
                        'updated_at' => Schema::hasColumn('deployment_managers', 'updated_at') ? now() : null,
                        'created_at' => Schema::hasColumn('deployment_managers', 'created_at') ? now() : null,
                    ], fn ($value) => $value !== null)
                );
            }

            if (Schema::hasColumn('deployment_managers', 'status')) {
                DB::table('deployment_managers')
                    ->whereRaw("LOWER(COALESCE(status, '')) = ?", ['suspended'])
                    ->update(array_filter([
                        'status' => 'active',
                        'updated_at' => Schema::hasColumn('deployment_managers', 'updated_at') ? now() : null,
                    ], fn ($value) => $value !== null));
            }
        }

        if (Schema::hasColumn('users', 'status')) {
            DB::table('users')
                ->whereIn(DB::raw("LOWER(COALESCE(role, ''))"), ['state_manager', 'deployment_manager', 'manager'])
                ->whereIn(DB::raw("LOWER(COALESCE(status, ''))"), ['suspended', 'inactive'])
                ->update(array_filter([
                    'status' => 'active',
                    'updated_at' => Schema::hasColumn('users', 'updated_at') ? now() : null,
                ], fn ($value) => $value !== null));
        }

        $this->trimStateManagerPermissions();
    }

    public function down(): void
    {
    }

    private function matchingUserIds(array $names): array
    {
        return DB::table('users')
            ->where(function ($query) use ($names) {
                foreach ($names as $name) {
                    $normalized = strtolower(trim($name));
                    $pattern = '%' . str_replace(' ', '%', $normalized) . '%';

                    $query->orWhereRaw('LOWER(TRIM(name)) = ?', [$normalized])
                        ->orWhereRaw('LOWER(TRIM(name)) LIKE ?', [$pattern])
                        ->orWhereRaw('LOWER(TRIM(email)) LIKE ?', [$pattern]);
                }
            })
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values()
            ->all();
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

    private function trimStateManagerPermissions(): void
    {
        if (!Schema::hasTable('roles') || !Schema::hasTable('permissions') || !Schema::hasTable('role_has_permissions')) {
            return;
        }

        $roleId = $this->roleId('State Manager');
        if (!$roleId) {
            return;
        }

        $allowedPermissionIds = DB::table('permissions')
            ->where(function ($query) {
                $query->where('name', 'dashboard.overview.view')
                    ->orWhere('name', 'like', 'deployment.%');
            })
            ->pluck('id')
            ->all();

        if ($allowedPermissionIds === []) {
            return;
        }

        DB::table('role_has_permissions')
            ->where('role_id', $roleId)
            ->whereNotIn('permission_id', $allowedPermissionIds)
            ->delete();
    }
};
