<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    
    public function up()
{
    Schema::table('users', function (Blueprint $table) {
        // Fix the role column: add it if missing, or ensure it has a default
        if (!Schema::hasColumn('users', 'role')) {
            $table->string('role')->default('user')->after('password');
        }

        // Add social login columns if they are missing
        if (!Schema::hasColumn('users', 'provider_id')) {
            $table->string('provider_id')->nullable()->after('role');
        }
        if (!Schema::hasColumn('users', 'provider_name')) {
            $table->string('provider_name')->nullable()->after('provider_id');
        }
    });
}

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        //
    }
};
