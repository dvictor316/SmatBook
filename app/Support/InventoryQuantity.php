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
            return round($stockUnits, 6);
        }

        if (method_exists($product, 'quantityToBase')) {
            return $product->quantityToBase($qty, $unitType);
        }

        $type = strtolower(trim((string) ($unitType ?: $product->unit_type ?: 'unit')));
        $multiplier = match ($type) {
            'carton' => max(1, $product->unitsPerCarton()),
            'roll' => max(1, $product->unitsPerRoll()),
            default => 1,
        };

        return round($qty * $multiplier, 6);
    }

    public static function resolvePurchaseStockUnits(Product $product, float $qty, ?string $unitType = null): float
    {
        if (method_exists($product, 'quantityToBase')) {
            return $product->quantityToBase($qty, $unitType);
        }

        return static::resolveSaleStockUnits($product, $qty, $unitType);
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
        return "CASE
            WHEN COALESCE({$productsTable}.units_per_roll, 0) > 0
                THEN COALESCE({$productsTable}.units_per_roll, 0)
            ELSE 1
        END";
    }

    public static function productUnitsPerCartonExpression(string $productsTable = 'products'): string
    {
        return "CASE
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

    public static function purchaseStockUnitsExpression(string $purchaseItemsTable = 'purchase_items', string $productsTable = 'products'): string
    {
        $quantityColumn = Schema::hasColumn($purchaseItemsTable, 'qty')
            ? 'qty'
            : (Schema::hasColumn($purchaseItemsTable, 'quantity') ? 'quantity' : null);
        $quantity = $quantityColumn
            ? "COALESCE({$purchaseItemsTable}.{$quantityColumn}, 0)"
            : '0';
        $stockUnits = Schema::hasColumn($purchaseItemsTable, 'stock_units')
            ? "COALESCE({$purchaseItemsTable}.stock_units, 0)"
            : '0';
        $conversionFactor = Schema::hasColumn($purchaseItemsTable, 'conversion_factor')
            ? "COALESCE({$purchaseItemsTable}.conversion_factor, 0)"
            : '0';
        $unitColumn = Schema::hasColumn($purchaseItemsTable, 'unit_type')
            ? "{$purchaseItemsTable}.unit_type"
            : (Schema::hasColumn($purchaseItemsTable, 'unit')
                ? "{$purchaseItemsTable}.unit"
                : "{$productsTable}.unit_type");
        $unitType = "LOWER(COALESCE({$unitColumn}, {$productsTable}.unit_type, 'unit'))";
        $unitsPerCarton = static::productUnitsPerCartonExpression($productsTable);
        $unitsPerRoll = static::productUnitsPerRollExpression($productsTable);
        $purchaseUnitConversion = Schema::hasColumn($productsTable, 'purchase_unit_id')
            && Schema::hasColumn($productsTable, 'conversion_rate')
            ? "({$productsTable}.purchase_unit_id IS NOT NULL AND COALESCE({$productsTable}.conversion_rate, 0) > 0)"
            : '0 = 1';
        $purchaseUnitRate = Schema::hasColumn($productsTable, 'conversion_rate')
            ? "COALESCE({$productsTable}.conversion_rate, 1)"
            : '1';

        return "CASE
            WHEN {$stockUnits} > 0 THEN {$stockUnits}
            WHEN {$conversionFactor} > 0 AND {$conversionFactor} <> 1 THEN {$quantity} * {$conversionFactor}
            WHEN {$unitType} = 'carton' AND {$unitsPerCarton} > 1 THEN {$quantity} * {$unitsPerCarton}
            WHEN {$unitType} = 'roll' AND {$unitsPerRoll} > 1 THEN {$quantity} * {$unitsPerRoll}
            WHEN {$purchaseUnitConversion} THEN {$quantity} * {$purchaseUnitRate}
            WHEN {$conversionFactor} > 0 THEN {$quantity} * {$conversionFactor}
            ELSE {$quantity}
        END";
    }
}
