<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasColumn('sale_items', 'product_name')) {
            Schema::table('sale_items', function (Blueprint $table) {
                $table->string('product_name')->nullable()->after('product_id');
            });
        }

        // Back-fill existing rows from the products table
        DB::statement("
            UPDATE sale_items si
            JOIN products p ON p.id = si.product_id
            SET si.product_name = p.name
            WHERE si.product_name IS NULL
              AND si.product_id IS NOT NULL
        ");
    }

    public function down(): void
    {
        if (Schema::hasColumn('sale_items', 'product_name')) {
            Schema::table('sale_items', function (Blueprint $table) {
                $table->dropColumn('product_name');
            });
        }
    }
};
