<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class Product extends Model
{
    use \App\Traits\Multitenantable;
    use HasFactory;

    protected $appends = ['image_url'];

    protected $fillable = [
        'user_id',
        'company_id',
        'branch_id',
        'branch_name',
        'name', 
        'sku', 
        'price', 
        'retail_price',
        'wholesale_price',
        'special_price',
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



    public function rollsPerCarton(): int
    {
        return max((int) ($this->units_per_carton ?? 0), 0);
    }

    public function unitsPerRoll(): int
    {
        return max((int) ($this->units_per_roll ?? 0), 0);
    }

    public function unitsPerCarton(): int
    {
        $unitsPerRoll = $this->unitsPerRoll();

        return $unitsPerRoll > 0
            ? $this->rollsPerCarton() * $unitsPerRoll
            : $this->rollsPerCarton();
    }

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

    public function branchStocks(): HasMany
    {
        return $this->hasMany(ProductBranchStock::class);
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

    public function getImageUrlAttribute(): string
    {
        $image = trim((string) ($this->image ?? ''));

        if ($image === '') {
            return '';
        }

        if (Str::startsWith($image, ['http://', 'https://'])) {
            return $image;
        }

        $normalized = ltrim($image, '/');

        if (Str::startsWith($normalized, 'storage/')) {
            return asset($normalized);
        }

        if (Str::startsWith($normalized, 'assets/')) {
            return asset($normalized);
        }

        if (Storage::disk('public')->exists($normalized)) {
            return Storage::url($normalized);
        }

        if (file_exists(public_path('storage/' . $normalized))) {
            return asset('storage/' . $normalized);
        }

        if (file_exists(public_path($normalized))) {
            return asset($normalized);
        }

        return '';
    }
}
