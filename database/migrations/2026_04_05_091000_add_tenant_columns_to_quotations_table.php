<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('quotations')) {
            return;
        }

        Schema::table('quotations', function (Blueprint $table) {
            if (!Schema::hasColumn('quotations', 'company_id')) {
                $table->unsignedBigInteger('company_id')->nullable()->after('customer_id')->index();
            }
            if (!Schema::hasColumn('quotations', 'user_id')) {
                $table->unsignedBigInteger('user_id')->nullable()->after('company_id')->index();
            }
            if (!Schema::hasColumn('quotations', 'branch_id')) {
                $table->string('branch_id', 64)->nullable()->after('user_id')->index();
            }
            if (!Schema::hasColumn('quotations', 'branch_name')) {
                $table->string('branch_name', 191)->nullable()->after('branch_id');
            }
        });
    }

    public function down(): void
    {
        if (!Schema::hasTable('quotations')) {
            return;
        }

        Schema::table('quotations', function (Blueprint $table) {
            foreach (['branch_name', 'branch_id', 'user_id', 'company_id'] as $column) {
                if (Schema::hasColumn('quotations', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
