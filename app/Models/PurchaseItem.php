<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Traits\TenantScoped;

class PurchaseItem extends Model
{
    use HasFactory, TenantScoped;

    protected $fillable = [
        'company_id',
        'branch_id',
        'branch_name',
        'purchase_id', 
        'product_id', 
        'qty', 
        'unit_price',
        'unit',
        'unit_type',
        'conversion_factor',
        'stock_units',
    ];

    protected $casts = [
        'qty' => 'float',
        'unit_price' => 'float',
        'conversion_factor' => 'float',
        'stock_units' => 'float',
    ];

    // Link back to the main Purchase
    public function purchase()
    {
        return $this->belongsTo(Purchase::class);
    }

    // Link to the Product details
    public function product()
    {
        return $this->belongsTo(Product::class);
    }
}
