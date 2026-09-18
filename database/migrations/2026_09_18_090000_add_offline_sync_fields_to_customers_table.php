<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('customers', function (Blueprint $table) {
            $table->string('client_record_id', 64)->nullable()->after('user_id');
            $table->timestamp('client_recorded_at')->nullable()->after('client_record_id');
            $table->unique(['company_id', 'client_record_id'], 'customers_company_client_record_unique');
        });
    }

    public function down(): void
    {
        Schema::table('customers', function (Blueprint $table) {
            $table->dropUnique('customers_company_client_record_unique');
            $table->dropColumn(['client_record_id', 'client_recorded_at']);
        });
    }
};
