<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('expenses')) {
            return;
        }

        Schema::table('expenses', function (Blueprint $table) {
            if (!Schema::hasColumn('expenses', 'branch_id')) {
                $table->string('branch_id', 64)->nullable()->after('company_id')->index();
            }
            if (!Schema::hasColumn('expenses', 'branch_name')) {
                $table->string('branch_name', 191)->nullable()->after('branch_id');
            }
        });
    }

    public function down(): void
    {
        if (!Schema::hasTable('expenses')) {
            return;
        }

        Schema::table('expenses', function (Blueprint $table) {
            if (Schema::hasColumn('expenses', 'branch_name')) {
                $table->dropColumn('branch_name');
            }
            if (Schema::hasColumn('expenses', 'branch_id')) {
                $table->dropColumn('branch_id');
            }
        });
    }
};
