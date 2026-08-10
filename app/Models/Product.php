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

    /**
     * Thread-local label used by ProductObserver to write a meaningful
     * reference string instead of the generic "Stock Update".
     * Set this before calling increment/decrement, then it auto-clears.
     */
    private static ?string $inventoryContext = null;

    public static function setInventoryContext(string $label): void
    {
        self::$inventoryContext = $label;
    }

    public static function consumeInventoryContext(): ?string
    {
        $label = self::$inventoryContext;
        self::$inventoryContext = null;
        return $label;
    }

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
        'reorder_level',
        'reorder_quantity',
        'units_per_carton', 
        'units_per_roll', 
        'base_unit_name', 
        'unit_type',      // Added for dynamic packaging logic
        'unit_id',
        'base_unit_id',
        'purchase_unit_id',
        'conversion_rate',
        'category_id', 
        'status', 
        'image', 
        'description',
        'barcode',
        'expiry_date',
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

    public function stockBreakdown(?float $quantity = null): array
    {
        $remainingUnits = max(0, (int) floor($quantity ?? ($this->stock ?? $this->stock_quantity ?? 0)));
        $unitsPerRoll = $this->unitsPerRoll();
        $unitsPerCarton = $this->unitsPerCarton();

        $cartons = 0;
        $rolls = 0;

        if ($unitsPerCarton > 0) {
            $cartons = intdiv($remainingUnits, $unitsPerCarton);
            $remainingUnits -= $cartons * $unitsPerCarton;
        }

        if ($unitsPerRoll > 0) {
            $rolls = intdiv($remainingUnits, $unitsPerRoll);
            $remainingUnits -= $rolls * $unitsPerRoll;
        }

        return [
            'cartons' => $cartons,
            'rolls' => $rolls,
            'units' => $remainingUnits,
        ];
    }

    /**
     * Get the category that owns the product.
     */
    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    public function unit()
    {
        return $this->belongsTo(Unit::class);
    }

    public function baseUnit()
    {
        return $this->belongsTo(Unit::class, 'base_unit_id');
    }

    public function purchaseUnit()
    {
        return $this->belongsTo(Unit::class, 'purchase_unit_id');
    }

    public function stockUnitSymbol(): string
    {
        return (string) (
            $this->baseUnit?->symbol
            ?? $this->unit?->symbol
            ?? $this->base_unit_name
            ?? 'pcs'
        );
    }

    public function formatStockQuantity(float|int|null $quantity = null): string
    {
        $value = (float) ($quantity ?? $this->stock ?? $this->stock_quantity ?? 0);
        $formatted = rtrim(rtrim(number_format($value, 2), '0'), '.');

        return $formatted . ' ' . $this->stockUnitSymbol();
    }

    public function purchaseQuantityToBase(float|int $quantity): float
    {
        $qty = (float) $quantity;
        $rate = (float) ($this->conversion_rate ?? 0);

        if (!empty($this->purchase_unit_id) && $rate > 0) {
            return round($qty * $rate, 6);
        }

        return $qty;
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

    public function barcodes(): HasMany
    {
        return $this->hasMany(ProductBarcode::class);
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

        $normalized = str_replace('\\', '/', ltrim($image, '/'));
        $publicDiskPath = $normalized;
        foreach (['public/', 'storage/', 'public/storage/'] as $prefix) {
            if (Str::startsWith($publicDiskPath, $prefix)) {
                $publicDiskPath = ltrim(substr($publicDiskPath, strlen($prefix)), '/');
            }
        }

        if (Str::startsWith($normalized, ['assets/', 'uploads/'])) {
            return asset($normalized);
        }

        if ($publicDiskPath !== '' && Storage::disk('public')->exists($publicDiskPath)) {
            return route('products.image', ['path' => $publicDiskPath]);
        }

        if (Str::startsWith($normalized, 'storage/')) {
            return asset($normalized);
        }

        if (file_exists(public_path('storage/' . $normalized))) {
            return asset('storage/' . $normalized);
        }

        if ($publicDiskPath !== '' && file_exists(public_path('storage/' . $publicDiskPath))) {
            return asset('storage/' . $publicDiskPath);
        }

        if (file_exists(public_path($normalized))) {
            return asset($normalized);
        }

        return '';
    }
}
