<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Postulation & Correction Statement:
 * 1. The SQL error 1054 confirmed that the 'domain_prefix' column is missing from the 'companies' table.
 * 2. While 'subdomain' exists, your logic specifically queries for 'domain_prefix'.
 * 3. I have updated this migration to add the missing column to prevent the QueryException.
 */

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (! Schema::hasTable('companies') || Schema::hasColumn('companies', 'domain_prefix')) {
            return;
        }

        Schema::table('companies', function (Blueprint $table) {
            $table->string('domain_prefix')->nullable()->after('subdomain')->index();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (! Schema::hasTable('companies') || ! Schema::hasColumn('companies', 'domain_prefix')) {
            return;
        }

        Schema::table('companies', function (Blueprint $table) {
            $table->dropColumn('domain_prefix');
        });
    }
};
