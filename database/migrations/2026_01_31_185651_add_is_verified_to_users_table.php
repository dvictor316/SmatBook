<?php

/*
|--------------------------------------------------------------------------
| REWRITTEN MIGRATION: Add Verification Status to Users
| Location: database/migrations/xxxx_xx_xx_add_is_verified_to_users_table.php
| [2026-01-31] Core logic for EnsureManagerIsVerified Middleware
|--------------------------------------------------------------------------
*/

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // Default is false (0) so new registrations must be approved
            $table->boolean('is_verified')->default(false)->after('role');
            
            // Optional: Track who approved them
            $table->unsignedBigInteger('verified_by')->nullable()->after('is_verified');
            $table->timestamp('verified_at')->nullable()->after('verified_by');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['is_verified', 'verified_by', 'verified_at']);
        });
    }

};
