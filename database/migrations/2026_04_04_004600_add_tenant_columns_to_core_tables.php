<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('accounts')) {
            Schema::table('accounts', function (Blueprint $table) {
                if (!Schema::hasColumn('accounts', 'company_id')) {
                    $table->unsignedBigInteger('company_id')->nullable()->after('id')->index();
                }
                if (!Schema::hasColumn('accounts', 'user_id')) {
                    $table->unsignedBigInteger('user_id')->nullable()->after('company_id')->index();
                }
                if (!Schema::hasColumn('accounts', 'branch_id')) {
                    $table->string('branch_id', 64)->nullable()->after('user_id')->index();
                }
                if (!Schema::hasColumn('accounts', 'branch_name')) {
                    $table->string('branch_name', 191)->nullable()->after('branch_id');
                }
            });
        }

        if (Schema::hasTable('transactions')) {
            Schema::table('transactions', function (Blueprint $table) {
                if (!Schema::hasColumn('transactions', 'company_id')) {
                    $table->unsignedBigInteger('company_id')->nullable()->after('account_id')->index();
                }
                if (!Schema::hasColumn('transactions', 'branch_id')) {
                    $table->string('branch_id', 64)->nullable()->after('company_id')->index();
                }
                if (!Schema::hasColumn('transactions', 'branch_name')) {
                    $table->string('branch_name', 191)->nullable()->after('branch_id');
                }
            });
        }

        if (Schema::hasTable('products')) {
            Schema::table('products', function (Blueprint $table) {
                if (!Schema::hasColumn('products', 'company_id')) {
                    $table->unsignedBigInteger('company_id')->nullable()->after('user_id')->index();
                }
                if (!Schema::hasColumn('products', 'branch_id')) {
                    $table->string('branch_id', 64)->nullable()->after('company_id')->index();
                }
                if (!Schema::hasColumn('products', 'branch_name')) {
                    $table->string('branch_name', 191)->nullable()->after('branch_id');
                }
            });
        }

        if (Schema::hasTable('categories')) {
            Schema::table('categories', function (Blueprint $table) {
                if (!Schema::hasColumn('categories', 'company_id')) {
                    $table->unsignedBigInteger('company_id')->nullable()->after('id')->index();
                }
                if (!Schema::hasColumn('categories', 'user_id')) {
                    $table->unsignedBigInteger('user_id')->nullable()->after('company_id')->index();
                }
            });
        }

        if (Schema::hasTable('banks')) {
            Schema::table('banks', function (Blueprint $table) {
                if (!Schema::hasColumn('banks', 'company_id')) {
                    $table->unsignedBigInteger('company_id')->nullable()->after('id')->index();
                }
                if (!Schema::hasColumn('banks', 'user_id')) {
                    $table->unsignedBigInteger('user_id')->nullable()->after('company_id')->index();
                }
                if (!Schema::hasColumn('banks', 'branch_id')) {
                    $table->string('branch_id', 64)->nullable()->after('user_id')->index();
                }
                if (!Schema::hasColumn('banks', 'branch_name')) {
                    $table->string('branch_name', 191)->nullable()->after('branch_id');
                }
            });
        }

        if (Schema::hasTable('sales')) {
            Schema::table('sales', function (Blueprint $table) {
                if (!Schema::hasColumn('sales', 'company_id')) {
                    $table->unsignedBigInteger('company_id')->nullable()->after('id')->index();
                }
                if (!Schema::hasColumn('sales', 'branch_id')) {
                    $table->string('branch_id', 64)->nullable()->after('company_id')->index();
                }
                if (!Schema::hasColumn('sales', 'branch_name')) {
                    $table->string('branch_name', 191)->nullable()->after('branch_id');
                }
            });
        }

        if (Schema::hasTable('sale_items')) {
            Schema::table('sale_items', function (Blueprint $table) {
                if (!Schema::hasColumn('sale_items', 'company_id')) {
                    $table->unsignedBigInteger('company_id')->nullable()->after('id')->index();
                }
                if (!Schema::hasColumn('sale_items', 'branch_id')) {
                    $table->string('branch_id', 64)->nullable()->after('company_id')->index();
                }
                if (!Schema::hasColumn('sale_items', 'branch_name')) {
                    $table->string('branch_name', 191)->nullable()->after('branch_id');
                }
            });
        }

        if (Schema::hasTable('purchases')) {
            Schema::table('purchases', function (Blueprint $table) {
                if (!Schema::hasColumn('purchases', 'company_id')) {
                    $table->unsignedBigInteger('company_id')->nullable()->after('id')->index();
                }
                if (!Schema::hasColumn('purchases', 'user_id')) {
                    $table->unsignedBigInteger('user_id')->nullable()->after('company_id')->index();
                }
                if (!Schema::hasColumn('purchases', 'branch_id')) {
                    $table->string('branch_id', 64)->nullable()->after('user_id')->index();
                }
                if (!Schema::hasColumn('purchases', 'branch_name')) {
                    $table->string('branch_name', 191)->nullable()->after('branch_id');
                }
            });
        }

        if (Schema::hasTable('purchase_items')) {
            Schema::table('purchase_items', function (Blueprint $table) {
                if (!Schema::hasColumn('purchase_items', 'company_id')) {
                    $table->unsignedBigInteger('company_id')->nullable()->after('id')->index();
                }
                if (!Schema::hasColumn('purchase_items', 'branch_id')) {
                    $table->string('branch_id', 64)->nullable()->after('company_id')->index();
                }
                if (!Schema::hasColumn('purchase_items', 'branch_name')) {
                    $table->string('branch_name', 191)->nullable()->after('branch_id');
                }
            });
        }

        if (Schema::hasTable('payments')) {
            Schema::table('payments', function (Blueprint $table) {
                if (!Schema::hasColumn('payments', 'company_id')) {
                    $table->unsignedBigInteger('company_id')->nullable()->after('sale_id')->index();
                }
                if (!Schema::hasColumn('payments', 'user_id')) {
                    $table->unsignedBigInteger('user_id')->nullable()->after('company_id')->index();
                }
                if (!Schema::hasColumn('payments', 'branch_id')) {
                    $table->string('branch_id', 64)->nullable()->after('user_id')->index();
                }
                if (!Schema::hasColumn('payments', 'branch_name')) {
                    $table->string('branch_name', 191)->nullable()->after('branch_id');
                }
            });
        }

        if (Schema::hasTable('inventory_history')) {
            Schema::table('inventory_history', function (Blueprint $table) {
                if (!Schema::hasColumn('inventory_history', 'company_id')) {
                    $table->unsignedBigInteger('company_id')->nullable()->after('user_id')->index();
                }
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('inventory_history')) {
            Schema::table('inventory_history', function (Blueprint $table) {
                if (Schema::hasColumn('inventory_history', 'company_id')) {
                    $table->dropColumn('company_id');
                }
            });
        }

        if (Schema::hasTable('payments')) {
            Schema::table('payments', function (Blueprint $table) {
                foreach (['branch_name', 'branch_id', 'user_id', 'company_id'] as $column) {
                    if (Schema::hasColumn('payments', $column)) {
                        $table->dropColumn($column);
                    }
                }
            });
        }

        if (Schema::hasTable('purchase_items')) {
            Schema::table('purchase_items', function (Blueprint $table) {
                foreach (['branch_name', 'branch_id', 'company_id'] as $column) {
                    if (Schema::hasColumn('purchase_items', $column)) {
                        $table->dropColumn($column);
                    }
                }
            });
        }

        if (Schema::hasTable('purchases')) {
            Schema::table('purchases', function (Blueprint $table) {
                foreach (['branch_name', 'branch_id', 'user_id', 'company_id'] as $column) {
                    if (Schema::hasColumn('purchases', $column)) {
                        $table->dropColumn($column);
                    }
                }
            });
        }

        if (Schema::hasTable('sale_items')) {
            Schema::table('sale_items', function (Blueprint $table) {
                foreach (['branch_name', 'branch_id', 'company_id'] as $column) {
                    if (Schema::hasColumn('sale_items', $column)) {
                        $table->dropColumn($column);
                    }
                }
            });
        }

        if (Schema::hasTable('sales')) {
            Schema::table('sales', function (Blueprint $table) {
                foreach (['branch_name', 'branch_id', 'company_id'] as $column) {
                    if (Schema::hasColumn('sales', $column)) {
                        $table->dropColumn($column);
                    }
                }
            });
        }

        if (Schema::hasTable('banks')) {
            Schema::table('banks', function (Blueprint $table) {
                foreach (['branch_name', 'branch_id', 'user_id', 'company_id'] as $column) {
                    if (Schema::hasColumn('banks', $column)) {
                        $table->dropColumn($column);
                    }
                }
            });
        }

        if (Schema::hasTable('categories')) {
            Schema::table('categories', function (Blueprint $table) {
                foreach (['user_id', 'company_id'] as $column) {
                    if (Schema::hasColumn('categories', $column)) {
                        $table->dropColumn($column);
                    }
                }
            });
        }

        if (Schema::hasTable('products')) {
            Schema::table('products', function (Blueprint $table) {
                foreach (['branch_name', 'branch_id', 'company_id'] as $column) {
                    if (Schema::hasColumn('products', $column)) {
                        $table->dropColumn($column);
                    }
                }
            });
        }

        if (Schema::hasTable('transactions')) {
            Schema::table('transactions', function (Blueprint $table) {
                foreach (['branch_name', 'branch_id', 'company_id'] as $column) {
                    if (Schema::hasColumn('transactions', $column)) {
                        $table->dropColumn($column);
                    }
                }
            });
        }

        if (Schema::hasTable('accounts')) {
            Schema::table('accounts', function (Blueprint $table) {
                foreach (['branch_name', 'branch_id', 'user_id', 'company_id'] as $column) {
                    if (Schema::hasColumn('accounts', $column)) {
                        $table->dropColumn($column);
                    }
                }
            });
        }
    }
};
