<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('subscriptions') && ! Schema::hasColumn('subscriptions', 'is_business_addition')) {
            Schema::table('subscriptions', function (Blueprint $table) {
                $table->boolean('is_business_addition')->default(false)->after('company_id');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('subscriptions') && Schema::hasColumn('subscriptions', 'is_business_addition')) {
            Schema::table('subscriptions', function (Blueprint $table) {
                $table->dropColumn('is_business_addition');
            });
        }
    }
};
