<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('users')) {
            return;
        }

        Schema::table('users', function (Blueprint $table) {
            if (!Schema::hasColumn('users', 'is_protected_super_admin')) {
                $table->boolean('is_protected_super_admin')->default(false)->after('role');
            }

            if (!Schema::hasColumn('users', 'internal_test_access_enabled')) {
                $table->boolean('internal_test_access_enabled')->default(false)->after('is_protected_super_admin');
            }

            if (!Schema::hasColumn('users', 'internal_test_access_expires_at')) {
                $table->timestamp('internal_test_access_expires_at')->nullable()->after('internal_test_access_enabled');
            }
        });

        $ownerEmail = strtolower(trim((string) config('internal.owner_email', '')));

        $query = DB::table('users')
            ->whereIn(DB::raw('LOWER(role)'), ['super_admin', 'super admin', 'superadmin'])
            ->orderBy('id');

        $owner = $ownerEmail !== ''
            ? (clone $query)->whereRaw('LOWER(email) = ?', [$ownerEmail])->first()
            : null;

        if (!$owner) {
            $owner = $query->first();
        }

        if ($owner) {
            DB::table('users')
                ->where('id', $owner->id)
                ->update(['is_protected_super_admin' => true]);
        }
    }

    public function down(): void
    {
        if (!Schema::hasTable('users')) {
            return;
        }

        Schema::table('users', function (Blueprint $table) {
            foreach ([
                'internal_test_access_expires_at',
                'internal_test_access_enabled',
                'is_protected_super_admin',
            ] as $column) {
                if (Schema::hasColumn('users', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
