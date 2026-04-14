<?php

namespace App\Support;

use App\Models\Product;
use Illuminate\Support\Facades\Schema;

class InventoryQuantity
{
    public static function resolveSaleStockUnits(Product $product, float $qty, ?string $unitType = null, ?float $stockUnits = null): float
    {
        $qty = max(0, $qty);
        $stockUnits = $stockUnits !== null ? (float) $stockUnits : 0.0;

        if ($stockUnits > 0) {
            return round($stockUnits, 2);
        }

        $type = strtolower(trim((string) ($unitType ?: $product->unit_type ?: 'unit')));
        $multiplier = match ($type) {
            'carton' => max(1, $product->unitsPerCarton()),
            'roll' => max(1, $product->unitsPerRoll()),
            default => 1,
        };

        return round(max($qty, $qty * $multiplier), 2);
    }

    public static function saleItemQuantityColumn(string $saleItemsTable = 'sale_items'): string
    {
        if (Schema::hasColumn($saleItemsTable, 'qty')) {
            return "COALESCE({$saleItemsTable}.qty, 0)";
        }

        if (Schema::hasColumn($saleItemsTable, 'quantity')) {
            return "COALESCE({$saleItemsTable}.quantity, 0)";
        }

        return '0';
    }

    public static function productUnitsPerRollExpression(string $productsTable = 'products'): string
    {
        return "GREATEST(COALESCE({$productsTable}.units_per_roll, 0), 1)";
    }

    public static function productUnitsPerCartonExpression(string $productsTable = 'products'): string
    {
        return "CASE
            WHEN COALESCE({$productsTable}.units_per_roll, 0) > 0 AND COALESCE({$productsTable}.units_per_carton, 0) > 0
                THEN COALESCE({$productsTable}.units_per_carton, 0) * COALESCE({$productsTable}.units_per_roll, 0)
            WHEN COALESCE({$productsTable}.units_per_carton, 0) > 0
                THEN COALESCE({$productsTable}.units_per_carton, 0)
            ELSE 1
        END";
    }

    public static function saleStockUnitsExpression(string $saleItemsTable = 'sale_items', string $productsTable = 'products'): string
    {
        $qtyExpression = static::saleItemQuantityColumn($saleItemsTable);
        $stockUnitsExpression = Schema::hasColumn($saleItemsTable, 'stock_units')
            ? "COALESCE({$saleItemsTable}.stock_units, 0)"
            : '0';
        $unitTypeExpression = Schema::hasColumn($saleItemsTable, 'unit_type')
            ? "LOWER(COALESCE({$saleItemsTable}.unit_type, {$productsTable}.unit_type, 'unit'))"
            : "LOWER(COALESCE({$productsTable}.unit_type, 'unit'))";
        $unitsPerRollExpression = static::productUnitsPerRollExpression($productsTable);
        $unitsPerCartonExpression = static::productUnitsPerCartonExpression($productsTable);

        return "CASE
            WHEN {$stockUnitsExpression} > 0 THEN {$stockUnitsExpression}
            WHEN {$unitTypeExpression} = 'carton' THEN {$qtyExpression} * {$unitsPerCartonExpression}
            WHEN {$unitTypeExpression} = 'roll' THEN {$qtyExpression} * {$unitsPerRollExpression}
            ELSE {$qtyExpression}
        END";
    }
}
