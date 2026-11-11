<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Traits\TenantScoped;
use App\Models\Product;
use App\Support\InventoryQuantity;

class SaleItem extends Model
{
    use HasFactory, TenantScoped;

  protected $fillable = [
    'company_id',
    'branch_id',
    'branch_name',
    'sale_id',
    'product_id',
    'product_name',
    'price_list_id',
    'price_level',
    'qty',
    'unit_type',
    'stock_units',
    'unit_price',
    'unit_cost',
    'discount',
    'tax',
    'subtotal',    // Added
    'total_price', // Added
];
    protected $casts = [
        'qty'        => 'float',
        'stock_units'=> 'float',
        'unit_price' => 'float',
        'unit_cost' => 'float',
        'discount'   => 'float',
        'tax'        => 'float',
    ];

    public function sale()
    {
        return $this->belongsTo(Sale::class);
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public static function costPerSaleUnit(?Product $product, float $qty, ?string $unitType = null, ?float $stockUnits = null): ?float
    {
        $baseUnitCost = (float) ($product?->purchase_price ?? 0);
        if (!$product || $baseUnitCost <= 0) {
            return null;
        }

        $stockUnits ??= InventoryQuantity::resolveSaleStockUnits($product, $qty, $unitType);

        return round($baseUnitCost * ($qty > 0 ? $stockUnits / $qty : 1), 4);
    }

    /**
     * ACCESSORS 
     * These calculated fields populate your invoice rows.
     */

    // Matches {{ (float)$item->tax }} in Blade
    public function getTaxAttribute($value)
    {
        // If tax isn't stored on the line item, try to get it from the product
        return $value ?? ($this->product->tax ?? 0);
    }

    // Matches {{ $item->total_price }} in Blade
    public function getTotalPriceAttribute(): float
    {
        $base = $this->qty * $this->unit_price;
        
        // Calculate Discount amount (as a percentage)
        $discountAmt = $base * ($this->discount / 100);
        
        // Calculate Tax amount (on price after discount)
        $taxAmt = ($base - $discountAmt) * ($this->tax / 100);

        return ($base - $discountAmt) + $taxAmt;
    }
}
