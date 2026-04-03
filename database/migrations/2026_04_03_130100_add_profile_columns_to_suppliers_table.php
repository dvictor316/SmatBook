<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('suppliers')) {
            return;
        }

        Schema::table('suppliers', function (Blueprint $table) {
            if (!Schema::hasColumn('suppliers', 'name')) {
                $table->string('name')->nullable()->after('id');
            }
            if (!Schema::hasColumn('suppliers', 'contact')) {
                $table->string('contact')->nullable()->after('name');
            }
            if (!Schema::hasColumn('suppliers', 'email')) {
                $table->string('email')->nullable()->after('contact');
            }
            if (!Schema::hasColumn('suppliers', 'phone')) {
                $table->string('phone')->nullable()->after('email');
            }
            if (!Schema::hasColumn('suppliers', 'address')) {
                $table->string('address')->nullable()->after('phone');
            }
            if (!Schema::hasColumn('suppliers', 'company_id')) {
                $table->unsignedBigInteger('company_id')->nullable()->after('address');
            }
            if (!Schema::hasColumn('suppliers', 'user_id')) {
                $table->unsignedBigInteger('user_id')->nullable()->after('company_id');
            }
            if (!Schema::hasColumn('suppliers', 'branch_id')) {
                $table->string('branch_id')->nullable()->after('user_id');
            }
            if (!Schema::hasColumn('suppliers', 'branch_name')) {
                $table->string('branch_name')->nullable()->after('branch_id');
            }
            if (!Schema::hasColumn('suppliers', 'opening_balance')) {
                $table->decimal('opening_balance', 18, 2)->nullable()->after('branch_name');
            }
            if (!Schema::hasColumn('suppliers', 'opening_balance_date')) {
                $table->date('opening_balance_date')->nullable()->after('opening_balance');
            }
        });
    }

    public function down(): void
    {
        if (!Schema::hasTable('suppliers')) {
            return;
        }

        Schema::table('suppliers', function (Blueprint $table) {
            foreach ([
                'opening_balance_date',
                'opening_balance',
                'branch_name',
                'branch_id',
                'user_id',
                'company_id',
                'address',
                'phone',
                'email',
                'contact',
                'name',
            ] as $column) {
                if (Schema::hasColumn('suppliers', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
