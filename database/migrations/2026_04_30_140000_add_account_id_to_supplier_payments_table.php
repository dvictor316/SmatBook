<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('supplier_payments')) {
            return;
        }

        Schema::table('supplier_payments', function (Blueprint $table) {
            if (!Schema::hasColumn('supplier_payments', 'account_id')) {
                $table->unsignedBigInteger('account_id')->nullable()->after('bank_id');
                $table->index('account_id');
            }

            if (!Schema::hasColumn('supplier_payments', 'source_balance_before')) {
                $table->decimal('source_balance_before', 15, 2)->nullable()->after('account_id');
            }

            if (!Schema::hasColumn('supplier_payments', 'source_balance_after')) {
                $table->decimal('source_balance_after', 15, 2)->nullable()->after('source_balance_before');
            }
        });
    }

    public function down(): void
    {
        if (!Schema::hasTable('supplier_payments')) {
            return;
        }

        Schema::table('supplier_payments', function (Blueprint $table) {
            if (Schema::hasColumn('supplier_payments', 'source_balance_after')) {
                $table->dropColumn('source_balance_after');
            }

            if (Schema::hasColumn('supplier_payments', 'source_balance_before')) {
                $table->dropColumn('source_balance_before');
            }

            if (Schema::hasColumn('supplier_payments', 'account_id')) {
                $table->dropIndex(['account_id']);
                $table->dropColumn('account_id');
            }
        });
    }
};