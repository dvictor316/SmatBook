<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class Product extends Model
{
    use \App\Traits\Multitenantable;
    use HasFactory;

    protected $fillable = [
        'name', 
        'sku', 
        'price', 
        'purchase_price', 
        'stock', 
        'stock_quantity', // Added to maintain parity with controller logic
        'units_per_carton', 
        'units_per_roll', 
        'base_unit_name', 
        'unit_type',      // Added for dynamic packaging logic
        'category_id', 
        'status', 
        'image', 
        'description',
        'barcode'
    ];

    /**
     * Get the category that owns the product.
     */
    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    /**
     * Get the sale items associated with the product.
     */
    public function saleItems()
    {
        return $this->hasMany(SaleItem::class);
    }

    /**
     * Automated Stock History Tracking
     */
    protected static function booted()
    {
        // Keep only stock/stock_quantity sync here.
        // Inventory history logging is handled by ProductObserver to avoid double inserts.
        static::saving(function ($product) {
            if ($product->isDirty('stock')) {
                $product->stock_quantity = $product->stock;
            }
        });
    }
}
