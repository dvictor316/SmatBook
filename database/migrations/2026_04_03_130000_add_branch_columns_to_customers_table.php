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
            if (!Schema::hasColumn('customers', 'branch_id')) {
                $table->string('branch_id')->nullable()->after('company_id');
            }
            if (!Schema::hasColumn('customers', 'branch_name')) {
                $table->string('branch_name')->nullable()->after('branch_id');
            }
        });
    }

    public function down(): void
    {
        if (!Schema::hasTable('customers')) {
            return;
        }

        Schema::table('customers', function (Blueprint $table) {
            if (Schema::hasColumn('customers', 'branch_name')) {
                $table->dropColumn('branch_name');
            }
            if (Schema::hasColumn('customers', 'branch_id')) {
                $table->dropColumn('branch_id');
            }
        });
    }
};
