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
                        'phone' => Schema::hasColumn('deployment_managers', 'phone') ? ($user->phone ?? null) : null,
                        'status' => 'active',
                        'deployment_limit' => 100,
                        'country' => Schema::hasColumn('deployment_managers', 'country') ? ($user->country ?? null) : null,
                        'state_region' => Schema::hasColumn('deployment_managers', 'state_region') ? ($user->state_region ?? null) : null,
                        'local_council' => Schema::hasColumn('deployment_managers', 'local_council') ? ($user->local_council ?? null) : null,
                        'commission_rate' => Schema::hasColumn('deployment_managers', 'commission_rate') ? 35.00 : null,
                        'auto_payout_enabled' => Schema::hasColumn('deployment_managers', 'auto_payout_enabled') ? 1 : null,
                        'updated_at' => Schema::hasColumn('deployment_managers', 'updated_at') ? now() : null,
                        'created_at' => Schema::hasColumn('deployment_managers', 'created_at') ? now() : null,
                    ], fn ($value) => $value !== null)
                );
            }
        }

        $dukeIds = $this->matchingUserIds([
            'duke ogbodo',
            'ogbodo duke',
        ]);

        $registeredBusinessIds = array_values(array_unique(array_merge(
            $dukeIds,
            $this->matchingUserIds([
                'mrs. eze florence',
                'eze florence',
                'florence eze',
                'ndeze2@gmail.com',
            ])
        )));

        // Registered businesses are bucketed for reporting only; do not convert them to agents.
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
