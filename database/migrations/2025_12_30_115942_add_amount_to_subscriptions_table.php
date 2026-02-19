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
        Schema::table('subscriptions', function (Blueprint $table) {
            // Check if 'amount' already exists to avoid "Duplicate column" error
            if (!Schema::hasColumn('subscriptions', 'amount')) {
                
                // Safety: Check if 'package_id' exists before using it in 'after()'
                if (Schema::hasColumn('subscriptions', 'package_id')) {
                    $table->decimal('amount', 10, 2)->default(0)->after('package_id');
                } else {
                    // Fallback: If package_id is missing, add after 'user_id' or 'id'
                    $table->decimal('amount', 10, 2)->default(0)->after('id');
                }
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('subscriptions', function (Blueprint $table) {
            if (Schema::hasColumn('subscriptions', 'amount')) {
                $table->dropColumn('amount');
            }
        });
    }
};