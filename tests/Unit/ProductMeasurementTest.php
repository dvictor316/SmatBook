<?php

namespace Tests\Unit;

use App\Models\Product;
use PHPUnit\Framework\TestCase;

class ProductMeasurementTest extends TestCase
{
    public function test_decimal_kg_stock_is_not_rounded_in_carton_breakdown(): void
    {
        $product = new Product([
            'base_unit_name' => 'kg',
            'stock' => 110.75,
            'units_per_carton' => 20,
            'units_per_roll' => 0,
        ]);

        $this->assertSame(20.0, $product->unitsPerCarton());
        $this->assertSame([
            'cartons' => 5,
            'rolls' => 0,
            'units' => 10.75,
        ], $product->stockBreakdown());
        $this->assertSame('110.75 kg', $product->formatStockQuantity());
    }
}
