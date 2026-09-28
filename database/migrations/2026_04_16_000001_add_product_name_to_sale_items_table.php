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

        DB::table('sale_items')
            ->whereNull('product_name')
            ->whereNotNull('product_id')
            ->whereExists(function ($query) {
                $query->selectRaw('1')
                    ->from('products')
                    ->whereColumn('products.id', 'sale_items.product_id');
            })
            ->update([
                'product_name' => DB::raw('(SELECT products.name FROM products WHERE products.id = sale_items.product_id)'),
            ]);
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
