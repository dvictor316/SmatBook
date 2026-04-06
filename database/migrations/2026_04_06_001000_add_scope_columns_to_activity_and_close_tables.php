<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        foreach (['activity_logs', 'accounting_periods', 'close_tasks', 'close_approvals'] as $tableName) {
            if (!Schema::hasTable($tableName)) {
                continue;
            }

            Schema::table($tableName, function (Blueprint $table) use ($tableName) {
                if (!Schema::hasColumn($tableName, 'company_id')) {
                    $table->unsignedBigInteger('company_id')->nullable()->index();
                }
                if (!Schema::hasColumn($tableName, 'branch_id')) {
                    $table->string('branch_id')->nullable()->index();
                }
                if (!Schema::hasColumn($tableName, 'branch_name')) {
                    $table->string('branch_name')->nullable()->index();
                }
            });
        }
    }

    public function down(): void
    {
        foreach (['activity_logs', 'accounting_periods', 'close_tasks', 'close_approvals'] as $tableName) {
            if (!Schema::hasTable($tableName)) {
                continue;
            }

            Schema::table($tableName, function (Blueprint $table) use ($tableName) {
                if (Schema::hasColumn($tableName, 'branch_name')) {
                    $table->dropColumn('branch_name');
                }
                if (Schema::hasColumn($tableName, 'branch_id')) {
                    $table->dropColumn('branch_id');
                }
                if (Schema::hasColumn($tableName, 'company_id')) {
                    $table->dropColumn('company_id');
                }
            });
        }
    }
};
