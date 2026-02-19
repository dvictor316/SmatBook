<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Adding the missing columns required by the DeploymentManagerController.
     */
    public function up(): void
    {
        Schema::table('subscriptions', function (Blueprint $table) {
            // Adding expires_at after status for logical grouping
            $table->timestamp('expires_at')->nullable()->after('status');
            
            // Optional: Adding starts_at to track when the current period began
            $table->timestamp('starts_at')->nullable()->after('status');
            
            // Adding an index since we will be ordering by this column for renewals
            $table->index('expires_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('subscriptions', function (Blueprint $table) {
            $table->dropIndex(['expires_at']);
            $table->dropColumn(['starts_at', 'expires_at']);
        });
    }
};