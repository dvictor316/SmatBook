<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('products') || !Schema::hasTable('product_units')) {
            return;
        }

        DB::table('products')
            ->select(['id', 'units_per_carton', 'purchase_price', 'price', 'wholesale_price'])
            ->where('units_per_carton', '>', 0)
            ->orderBy('id')
            ->chunkById(300, function ($products) {
                $now = now();

                foreach ($products as $product) {
                    $cartonFactor = max((float) ($product->units_per_carton ?? 0), 0);

                    if ($cartonFactor <= 1) {
                        continue;
                    }

                    DB::table('product_units')
                        ->where('product_id', $product->id)
                        ->whereIn(DB::raw('LOWER(unit_name)'), ['carton', 'ctn'])
                        ->update([
                            'unit_symbol' => 'ctn',
                            'conversion_factor' => $cartonFactor,
                            'purchase_price' => $product->purchase_price !== null
                                ? round((float) $product->purchase_price * $cartonFactor, 2)
                                : null,
                            'selling_price' => $product->price !== null
                                ? round((float) $product->price * $cartonFactor, 2)
                                : null,
                            'wholesale_price' => $product->wholesale_price !== null
                                ? round((float) $product->wholesale_price * $cartonFactor, 2)
                                : null,
                            'updated_at' => $now,
                        ]);
                }
            });
    }

    public function down(): void
    {
        //
    }
};
