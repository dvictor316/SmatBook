<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('email_audit_logs', function (Blueprint $table) {
            // Add company_id and branch_id as nullable for backward compatibility
            if (!Schema::hasColumn('email_audit_logs', 'company_id')) {
                $table->unsignedBigInteger('company_id')->nullable()->after('id')->index();
            }
            if (!Schema::hasColumn('email_audit_logs', 'branch_id')) {
                $table->unsignedBigInteger('branch_id')->nullable()->after('company_id')->index();
            }
        });
    }

    public function down(): void
    {
        Schema::table('email_audit_logs', function (Blueprint $table) {
            if (Schema::hasColumn('email_audit_logs', 'company_id')) {
                $table->dropColumn('company_id');
            }
            if (Schema::hasColumn('email_audit_logs', 'branch_id')) {
                $table->dropColumn('branch_id');
            }
        });
    }
};
