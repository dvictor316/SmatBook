<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Add deployed_by to subscriptions so the deployment manager link survives session loss.
     * Without this, resolveDeploymentManagerId() can only fall back to the deployment_companies
     * table lookup — which works but requires the DB table to exist.
     */
    public function up(): void
    {
        if (Schema::hasTable('subscriptions') && !Schema::hasColumn('subscriptions', 'deployed_by')) {
            Schema::table('subscriptions', function (Blueprint $table) {
                $table->unsignedBigInteger('deployed_by')->nullable()->after('user_id')
                    ->comment('User ID of the deployment manager who created this subscription');
                $table->index('deployed_by', 'subscriptions_deployed_by_index');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('subscriptions') && Schema::hasColumn('subscriptions', 'deployed_by')) {
            Schema::table('subscriptions', function (Blueprint $table) {
                $table->dropIndex('subscriptions_deployed_by_index');
                $table->dropColumn('deployed_by');
            });
        }
    }
};
