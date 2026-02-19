<?php

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
            // Check if 'role' column already exists to prevent Duplicate Column error
            if (!Schema::hasColumn('users', 'role')) {
                // Using enum ensures ONLY these specific roles can be entered in the DB
                $table->enum('role', [
                    'super_admin',
                    'administrator',
                    'deployment_manager',
                    'store_manager',
                    'accountant',
                    'cashier'
                ])->default('cashier')->after('email'); 
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // Check if column exists before trying to drop it
            if (Schema::hasColumn('users', 'role')) {
                $table->dropColumn('role');
            }
        });
    }
};