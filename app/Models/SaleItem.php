<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Traits\TenantScoped;

class SaleItem extends Model
{
    use HasFactory, TenantScoped;

  protected $fillable = [
    'company_id',
    'branch_id',
    'branch_name',
    'sale_id',
    'product_id',
    'qty',
    'unit_type',
    'stock_units',
    'unit_price',
    'discount',
    'tax',
    'subtotal',    // Added
    'total_price', // Added
];
    protected $casts = [
        'qty'        => 'integer',
        'stock_units'=> 'float',
        'unit_price' => 'float',
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
