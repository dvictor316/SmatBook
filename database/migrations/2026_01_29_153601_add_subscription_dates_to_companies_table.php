<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
public function up(): void
{
    Schema::table('companies', function (Blueprint $table) {
        // Essential for the Deployment Manager handshake and dashboard metrics
        if (!Schema::hasColumn('companies', 'subdomain')) {
            $table->string('subdomain')->nullable()->after('domain');
        }
        
        if (!Schema::hasColumn('companies', 'subscription_start')) {
            $table->timestamp('subscription_start')->nullable()->after('status');
        }

        if (!Schema::hasColumn('companies', 'subscription_end')) {
            $table->timestamp('subscription_end')->nullable()->after('subscription_start');
        }
    });
}

public function down(): void
{
    Schema::table('companies', function (Blueprint $table) {
        $table->dropColumn(['subdomain', 'subscription_start', 'subscription_end']);
    });
}
};
