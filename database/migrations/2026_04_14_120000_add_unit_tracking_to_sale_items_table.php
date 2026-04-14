<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('sale_items')) {
            return;
        }

        Schema::table('sale_items', function (Blueprint $table) {
            if (!Schema::hasColumn('sale_items', 'unit_type')) {
                $table->string('unit_type')->nullable()->after('qty');
            }

            if (!Schema::hasColumn('sale_items', 'stock_units')) {
                $table->decimal('stock_units', 15, 2)->nullable()->after('unit_type');
            }
        });

        $qtyColumn = Schema::hasColumn('sale_items', 'qty')
            ? 'qty'
            : (Schema::hasColumn('sale_items', 'quantity') ? 'quantity' : null);

        if ($qtyColumn === null || !Schema::hasTable('products')) {
            return;
        }

        DB::table('sale_items')
            ->join('products', 'products.id', '=', 'sale_items.product_id')
            ->select([
                'sale_items.id',
                "sale_items.{$qtyColumn} as qty_value",
                'sale_items.unit_type as sale_unit_type',
                'products.unit_type as product_unit_type',
                'products.units_per_carton',
                'products.units_per_roll',
            ])
            ->orderBy('sale_items.id')
            ->chunk(500, function ($rows) {
                foreach ($rows as $row) {
                    $qty = (float) ($row->qty_value ?? 0);
                    $resolvedType = strtolower(trim((string) ($row->sale_unit_type ?: $row->product_unit_type ?: 'unit')));
                    $unitsPerRoll = max((float) ($row->units_per_roll ?? 0), 0);
                    $unitsPerCarton = max((float) ($row->units_per_carton ?? 0), 0);

                    $multiplier = match ($resolvedType) {
                        'carton' => $unitsPerRoll > 0 && $unitsPerCarton > 0 ? ($unitsPerRoll * $unitsPerCarton) : max($unitsPerCarton, 1),
                        'roll' => max($unitsPerRoll, 1),
                        default => 1,
                    };

                    DB::table('sale_items')
                        ->where('id', $row->id)
                        ->update([
                            'unit_type' => $row->sale_unit_type ?: $row->product_unit_type,
                            'stock_units' => round(max($qty, $qty * $multiplier), 2),
                        ]);
                }
            });
    }

    public function down(): void
    {
        if (!Schema::hasTable('sale_items')) {
            return;
        }

        Schema::table('sale_items', function (Blueprint $table) {
            if (Schema::hasColumn('sale_items', 'stock_units')) {
                $table->dropColumn('stock_units');
            }

            if (Schema::hasColumn('sale_items', 'unit_type')) {
                $table->dropColumn('unit_type');
            }
        });
    }
};
