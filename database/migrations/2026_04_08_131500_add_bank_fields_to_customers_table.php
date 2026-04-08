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
                $table->string('bank_name')->nullable();
            }
            if (!Schema::hasColumn('customers', 'account_holder')) {
                $table->string('account_holder')->nullable();
            }
            if (!Schema::hasColumn('customers', 'account_number')) {
                $table->string('account_number')->nullable();
            }
            if (!Schema::hasColumn('customers', 'ifsc')) {
                $table->string('ifsc')->nullable();
            }
            if (!Schema::hasColumn('customers', 'branch')) {
                $table->string('branch')->nullable();
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
