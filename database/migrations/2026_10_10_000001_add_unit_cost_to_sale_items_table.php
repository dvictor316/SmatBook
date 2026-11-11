<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('sale_items') && !Schema::hasColumn('sale_items', 'unit_cost')) {
            Schema::table('sale_items', function (Blueprint $table) {
                $table->decimal('unit_cost', 15, 4)->nullable()->after('unit_price');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('sale_items') && Schema::hasColumn('sale_items', 'unit_cost')) {
            Schema::table('sale_items', function (Blueprint $table) {
                $table->dropColumn('unit_cost');
            });
        }
    }
};
