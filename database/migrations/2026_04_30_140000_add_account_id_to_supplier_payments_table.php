<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('supplier_payments') || Schema::hasColumn('supplier_payments', 'account_id')) {
            return;
        }

        Schema::table('supplier_payments', function (Blueprint $table) {
            $table->unsignedBigInteger('account_id')->nullable()->after('bank_id');
            $table->index('account_id');
        });
    }

    public function down(): void
    {
        if (!Schema::hasTable('supplier_payments') || !Schema::hasColumn('supplier_payments', 'account_id')) {
            return;
        }

        Schema::table('supplier_payments', function (Blueprint $table) {
            $table->dropIndex(['account_id']);
            $table->dropColumn('account_id');
        });
    }
};