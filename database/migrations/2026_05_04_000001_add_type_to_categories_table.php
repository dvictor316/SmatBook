<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Add a 'type' column to categories so that product categories and expense
     * categories (and any other future category types) are kept strictly separate.
     *
     * Allowed values: 'product' | 'expense' | null (legacy / unclassified)
     */
    public function up(): void
    {
        Schema::table('categories', function (Blueprint $table) {
            if (!Schema::hasColumn('categories', 'type')) {
                // nullable so all existing rows default to null (unclassified / legacy)
                $table->string('type', 50)->nullable()->default(null)->after('name');
            }
        });
    }

    public function down(): void
    {
        Schema::table('categories', function (Blueprint $table) {
            if (Schema::hasColumn('categories', 'type')) {
                $table->dropColumn('type');
            }
        });
    }
};
