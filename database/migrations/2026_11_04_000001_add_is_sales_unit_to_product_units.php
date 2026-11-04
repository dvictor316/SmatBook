<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('product_units') && !Schema::hasColumn('product_units', 'is_sales_unit')) {
            Schema::table('product_units', function (Blueprint $table) {
                $table->boolean('is_sales_unit')->default(true);
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('product_units') && Schema::hasColumn('product_units', 'is_sales_unit')) {
            Schema::table('product_units', function (Blueprint $table) {
                $table->dropColumn('is_sales_unit');
            });
        }
    }
};
