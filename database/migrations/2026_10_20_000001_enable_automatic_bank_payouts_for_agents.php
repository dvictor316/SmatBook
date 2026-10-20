<?php

use App\Support\RoleNames;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('deployment_managers') || !Schema::hasColumn('deployment_managers', 'auto_payout_enabled')) {
            return;
        }

        $roleNames = Schema::hasTable('roles') ? DB::table('roles')->pluck('name', 'id') : collect();

        DB::table('users')->orderBy('id')->chunkById(200, function ($users) use ($roleNames) {
            $agentIds = $users->filter(function ($user) use ($roleNames) {
                $role = !empty($user->role_id) ? $roleNames->get($user->role_id) : ($user->role ?? null);

                return RoleNames::key($role) === 'agent';
            })->pluck('id')->all();

            DB::table('deployment_managers')->whereIn('user_id', $agentIds)->update(['auto_payout_enabled' => true]);
        });
    }

    public function down(): void
    {
        // Preserve payout preferences: the previous values cannot be reconstructed safely.
    }
};
