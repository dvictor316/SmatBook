<?php

namespace App\Models;

use App\Models\Traits\TenantScoped;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProductUnit extends Model
{
    use HasFactory, TenantScoped;

    protected $fillable = [
        'company_id',
        'user_id',
        'product_id',
        'unit_id',
        'unit_name',
        'unit_symbol',
        'conversion_factor',
        'is_base_unit',
        'is_purchase_unit',
        'is_default_sales_unit',
        'purchase_price',
        'selling_price',
        'wholesale_price',
        'barcode',
        'status',
    ];

    protected $casts = [
        'conversion_factor' => 'float',
        'is_base_unit' => 'boolean',
        'is_purchase_unit' => 'boolean',
        'is_default_sales_unit' => 'boolean',
        'purchase_price' => 'float',
        'selling_price' => 'float',
        'wholesale_price' => 'float',
    ];

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function unit()
    {
        return $this->belongsTo(Unit::class);
    }
}
