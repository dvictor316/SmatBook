<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('categories')) {
            return;
        }

        Schema::table('categories', function (Blueprint $table) {
            try {
                $table->dropUnique('categories_name_unique');
            } catch (\Throwable $e) {
                // Ignore when the unique index does not exist in this environment.
            }

            if (!Schema::hasColumn('categories', 'company_id')) {
                $table->unsignedBigInteger('company_id')->nullable()->after('id')->index();
            }

            if (!Schema::hasColumn('categories', 'user_id')) {
                $table->unsignedBigInteger('user_id')->nullable()->after('company_id')->index();
            }
        });
    }

    public function down(): void
    {
        if (!Schema::hasTable('categories')) {
            return;
        }

        Schema::table('categories', function (Blueprint $table) {
            try {
                $table->unique('name', 'categories_name_unique');
            } catch (\Throwable $e) {
                // Ignore if already restored.
            }
        });
    }
};
