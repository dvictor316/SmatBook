<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('vendors')) {
            Schema::table('vendors', function (Blueprint $table) {
                if (!Schema::hasColumn('vendors', 'company_id')) {
                    $table->unsignedBigInteger('company_id')->nullable()->after('id')->index();
                }
                if (!Schema::hasColumn('vendors', 'user_id')) {
                    $table->unsignedBigInteger('user_id')->nullable()->after('company_id')->index();
                }
                if (!Schema::hasColumn('vendors', 'branch_id')) {
                    $table->string('branch_id')->nullable()->after('user_id')->index();
                }
                if (!Schema::hasColumn('vendors', 'branch_name')) {
                    $table->string('branch_name')->nullable()->after('branch_id');
                }
            });
        }

        if (Schema::hasTable('vendor_ledger_transactions')) {
            Schema::table('vendor_ledger_transactions', function (Blueprint $table) {
                if (!Schema::hasColumn('vendor_ledger_transactions', 'company_id')) {
                    $table->unsignedBigInteger('company_id')->nullable()->after('vendor_id')->index();
                }
                if (!Schema::hasColumn('vendor_ledger_transactions', 'user_id')) {
                    $table->unsignedBigInteger('user_id')->nullable()->after('company_id')->index();
                }
                if (!Schema::hasColumn('vendor_ledger_transactions', 'branch_id')) {
                    $table->string('branch_id')->nullable()->after('user_id')->index();
                }
                if (!Schema::hasColumn('vendor_ledger_transactions', 'branch_name')) {
                    $table->string('branch_name')->nullable()->after('branch_id');
                }
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('vendor_ledger_transactions')) {
            Schema::table('vendor_ledger_transactions', function (Blueprint $table) {
                foreach (['branch_name', 'branch_id', 'user_id', 'company_id'] as $column) {
                    if (Schema::hasColumn('vendor_ledger_transactions', $column)) {
                        $table->dropColumn($column);
                    }
                }
            });
        }

        if (Schema::hasTable('vendors')) {
            Schema::table('vendors', function (Blueprint $table) {
                foreach (['branch_name', 'branch_id', 'user_id', 'company_id'] as $column) {
                    if (Schema::hasColumn('vendors', $column)) {
                        $table->dropColumn($column);
                    }
                }
            });
        }
    }
};
