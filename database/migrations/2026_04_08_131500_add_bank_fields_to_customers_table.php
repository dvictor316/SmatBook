<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('customers')) {
            return;
        }

        Schema::table('customers', function (Blueprint $table) {
            if (!Schema::hasColumn('customers', 'bank_name')) {
                $table->string('bank_name')->nullable()->after('shipping_pincode');
            }
            if (!Schema::hasColumn('customers', 'account_holder')) {
                $table->string('account_holder')->nullable()->after('bank_name');
            }
            if (!Schema::hasColumn('customers', 'account_number')) {
                $table->string('account_number')->nullable()->after('account_holder');
            }
            if (!Schema::hasColumn('customers', 'ifsc')) {
                $table->string('ifsc')->nullable()->after('account_number');
            }
            if (!Schema::hasColumn('customers', 'branch')) {
                $table->string('branch')->nullable()->after('ifsc');
            }
        });
    }

    public function down(): void
    {
        if (!Schema::hasTable('customers')) {
            return;
        }

        Schema::table('customers', function (Blueprint $table) {
            foreach (['branch', 'ifsc', 'account_number', 'account_holder', 'bank_name'] as $column) {
                if (Schema::hasColumn('customers', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
