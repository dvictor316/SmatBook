<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('sales')) {
            Schema::table('sales', function (Blueprint $table) {
                if (!Schema::hasColumn('sales', 'branch_id')) {
                    $table->string('branch_id', 64)->nullable()->after('company_id');
                }

                if (!Schema::hasColumn('sales', 'branch_name')) {
                    $table->string('branch_name', 191)->nullable()->after('branch_id');
                }
            });
        }

        if (Schema::hasTable('payments')) {
            Schema::table('payments', function (Blueprint $table) {
                if (!Schema::hasColumn('payments', 'branch_id')) {
                    $table->string('branch_id', 64)->nullable()->after('sale_id');
                }

                if (!Schema::hasColumn('payments', 'branch_name')) {
                    $table->string('branch_name', 191)->nullable()->after('branch_id');
                }
            });
        }

        if (Schema::hasTable('inventory_history')) {
            Schema::table('inventory_history', function (Blueprint $table) {
                if (!Schema::hasColumn('inventory_history', 'branch_id')) {
                    $table->string('branch_id', 64)->nullable()->after('product_id');
                }

                if (!Schema::hasColumn('inventory_history', 'branch_name')) {
                    $table->string('branch_name', 191)->nullable()->after('branch_id');
                }
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('inventory_history')) {
            Schema::table('inventory_history', function (Blueprint $table) {
                if (Schema::hasColumn('inventory_history', 'branch_name')) {
                    $table->dropColumn('branch_name');
                }

                if (Schema::hasColumn('inventory_history', 'branch_id')) {
                    $table->dropColumn('branch_id');
                }
            });
        }

        if (Schema::hasTable('payments')) {
            Schema::table('payments', function (Blueprint $table) {
                if (Schema::hasColumn('payments', 'branch_name')) {
                    $table->dropColumn('branch_name');
                }

                if (Schema::hasColumn('payments', 'branch_id')) {
                    $table->dropColumn('branch_id');
                }
            });
        }

        if (Schema::hasTable('sales')) {
            Schema::table('sales', function (Blueprint $table) {
                if (Schema::hasColumn('sales', 'branch_name')) {
                    $table->dropColumn('branch_name');
                }

                if (Schema::hasColumn('sales', 'branch_id')) {
                    $table->dropColumn('branch_id');
                }
            });
        }
    }
};
