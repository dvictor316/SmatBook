<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('banks')) {
            return;
        }

        Schema::table('banks', function (Blueprint $table) {
            if (!Schema::hasColumn('banks', 'company_id')) {
                $table->unsignedBigInteger('company_id')->nullable()->after('id');
            }
            if (!Schema::hasColumn('banks', 'user_id')) {
                $table->unsignedBigInteger('user_id')->nullable()->after('company_id');
            }
            if (!Schema::hasColumn('banks', 'branch_id')) {
                $table->string('branch_id')->nullable()->after('user_id');
            }
            if (!Schema::hasColumn('banks', 'branch_name')) {
                $table->string('branch_name')->nullable()->after('branch_id');
            }
        });
    }

    public function down(): void
    {
        if (!Schema::hasTable('banks')) {
            return;
        }

        Schema::table('banks', function (Blueprint $table) {
            if (Schema::hasColumn('banks', 'branch_name')) {
                $table->dropColumn('branch_name');
            }
            if (Schema::hasColumn('banks', 'branch_id')) {
                $table->dropColumn('branch_id');
            }
            if (Schema::hasColumn('banks', 'user_id')) {
                $table->dropColumn('user_id');
            }
            if (Schema::hasColumn('banks', 'company_id')) {
                $table->dropColumn('company_id');
            }
        });
    }
};
