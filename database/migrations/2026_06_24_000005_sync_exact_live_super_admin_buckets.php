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

        $registeredBusinessIds = $this->matchingUserIds([
            'duke ogbodo',
            'ogbodo duke',
            'mrs. eze florence',
            'eze florence',
            'ndeze2@gmail.com',
        ]);

        if (Schema::hasTable('super_admin_user_bucket_overrides')) {
            DB::table('super_admin_user_bucket_overrides')
                ->whereIn('bucket', ['state_manager', 'registered_business'])
                ->delete();

            foreach ($stateManagerIds as $userId) {
                DB::table('super_admin_user_bucket_overrides')->updateOrInsert(
                    ['user_id' => $userId, 'bucket' => 'state_manager'],
                    ['note' => 'Exact live bucket sync: state manager.', 'updated_at' => now(), 'created_at' => now()]
                );
            }

            foreach ($registeredBusinessIds as $userId) {
                DB::table('super_admin_user_bucket_overrides')->updateOrInsert(
                    ['user_id' => $userId, 'bucket' => 'registered_business'],
                    ['note' => 'Exact live bucket sync: registered business.', 'updated_at' => now(), 'created_at' => now()]
                );
            }
        }

        if ($stateManagerIds !== []) {
            DB::table('users')
                ->whereIn('id', $stateManagerIds)
                ->update(array_filter([
                    'role' => 'state_manager',
                    'role_id' => $this->roleId('State Manager'),
                    'status' => Schema::hasColumn('users', 'status') ? 'active' : null,
                    'updated_at' => Schema::hasColumn('users', 'updated_at') ? now() : null,
                ], fn ($value) => $value !== null));
        }

        $agentIds = DB::table('users')
            ->whereNotIn(DB::raw("LOWER(COALESCE(role, ''))"), ['super_admin', 'superadmin', 'administrator', 'admin'])
            ->whereIn(DB::raw("LOWER(COALESCE(role, ''))"), ['deployment_manager', 'manager', 'state_manager'])
            ->when($stateManagerIds !== [], fn ($query) => $query->whereNotIn('id', $stateManagerIds))
            ->pluck('id')
            ->all();

        if ($agentIds !== []) {
            DB::table('users')
                ->whereIn('id', $agentIds)
                ->update(array_filter([
                    'role' => 'agent',
                    'role_id' => $this->roleId('Agent'),
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
                        'updated_at' => Schema::hasColumn('deployment_managers', 'updated_at') ? now() : null,
                        'created_at' => Schema::hasColumn('deployment_managers', 'created_at') ? now() : null,
                    ], fn ($value) => $value !== null)
                );
            }

            if ($stateManagerIds !== []) {
                DB::table('deployment_managers')
                    ->whereNotIn('user_id', $stateManagerIds)
                    ->update(array_filter([
                        'status' => 'suspended',
                        'updated_at' => Schema::hasColumn('deployment_managers', 'updated_at') ? now() : null,
                    ], fn ($value) => $value !== null));
            }
        }
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
};
