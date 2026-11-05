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
        'brand',
        'fashion_size',
        'fashion_color',
        'variant_group_id',
    ];

    protected $casts = [
        'stock' => 'float',
        'stock_quantity' => 'float',
        'units_per_carton' => 'float',
        'units_per_roll' => 'float',
        'conversion_rate' => 'float',
    ];



    public function rollsPerCarton(): int
    {
        $unitsPerCarton = $this->unitsPerCarton();
        $unitsPerRoll = $this->unitsPerRoll();

        return $unitsPerRoll > 0 && $unitsPerCarton > 0
            ? (int) floor($unitsPerCarton / $unitsPerRoll)
            : $unitsPerCarton;
    }

    public function unitsPerRoll(): float
    {
        return max((float) ($this->units_per_roll ?? 0), 0);
    }

    public function unitsPerCarton(): float
    {
        return max((float) ($this->units_per_carton ?? 0), 0);
    }

    public static function isStandaloneCartonConfiguration(
        ?string $unitType,
        ?string $baseUnitName,
        float $unitsPerCarton,
        ?string $measurementMode = null
    ): bool
    {
        $unitType = strtolower(trim((string) $unitType));
        $baseUnitName = strtolower(trim((string) $baseUnitName));
        $measurementMode = strtolower(trim((string) $measurementMode));

        return in_array($unitType, ['carton', 'cartons', 'ctn'], true)
            && ($measurementMode === '' || $measurementMode === 'custom')
            && $unitsPerCarton <= 1
            && in_array($baseUnitName, ['', 'unit', 'units', 'piece', 'pieces', 'pc', 'pcs', 'carton', 'cartons', 'ctn'], true);
    }

    public function usesStandaloneCartonStockUnit(): bool
    {
        return static::isStandaloneCartonConfiguration(
            $this->unit_type,
            $this->base_unit_name,
            $this->unitsPerCarton()
        );
    }

    public function salesUnitConfiguration(): array
    {
        $activeUnits = Schema::hasTable('product_units')
            ? collect($this->activeProductUnits)
            : collect();
        $selectionConfigured = Schema::hasColumn('product_units', 'is_sales_unit') && $activeUnits->isNotEmpty();
        $hasExplicitCartonUnit = $activeUnits->contains(function ($unit) {
            $unitName = strtolower(trim((string) ($unit->unit_name ?: $unit->unit_symbol)));
            $unitSymbol = strtolower(trim((string) ($unit->unit_symbol ?: $unit->unit_name)));

            return in_array($unitName, ['carton', 'cartons', 'ctn'], true)
                || in_array($unitSymbol, ['carton', 'cartons', 'ctn'], true);
        });

        if ($this->usesStandaloneCartonStockUnit() && !$hasExplicitCartonUnit) {
            $hasEnabledUnit = !$selectionConfigured
                || $activeUnits->contains(fn ($unit) => (bool) ($unit->is_sales_unit ?? true));
            $options = $hasEnabledUnit
                ? [[
                    'name' => 'carton',
                    'symbol' => 'ctn',
                    'conversion_factor' => 1,
                    'selling_price' => $this->retail_price ?? $this->price ?? 0,
                    'wholesale_price' => $this->wholesale_price ?? 0,
                    'is_default_sales_unit' => strtolower((string) $this->unit_type) === 'carton',
                ]]
                : [];

            return [
                'options' => $options,
                'selection_configured' => $selectionConfigured,
            ];
        }

        $options = $activeUnits
            ->filter(fn ($unit) => !$selectionConfigured || (bool) ($unit->is_sales_unit ?? true))
            ->map(function ($unit) {
                $unitName = strtolower(trim((string) ($unit->unit_name ?: $unit->unit_symbol)));
                $factor = (float) $unit->conversion_factor;
                if ($factor <= 1 && in_array($unitName, ['carton', 'cartons', 'ctn'], true)) {
                    $factor = $this->unitsPerCarton();
                }

                return [
                    'name' => (string) ($unit->unit_name ?: $unit->unit_symbol),
                    'symbol' => (string) ($unit->unit_symbol ?: $unit->unit_name),
                    'conversion_factor' => max(1, $factor),
                    'selling_price' => $unit->selling_price,
                    'wholesale_price' => $unit->wholesale_price,
                    'is_default_sales_unit' => (bool) ($unit->is_default_sales_unit ?? false),
                ];
            })
            ->values();

        if ($options->isEmpty() && !$selectionConfigured) {
            $baseUnitName = (string) ($this->base_unit_name ?: 'pcs');
            $options->push([
                'name' => $baseUnitName,
                'symbol' => $baseUnitName,
                'conversion_factor' => 1,
                'selling_price' => $this->retail_price ?? $this->price ?? 0,
                'wholesale_price' => $this->wholesale_price ?? 0,
                'is_default_sales_unit' => strtolower((string) $this->unit_type) === 'unit',
            ]);

            $baseUnitKey = strtolower(trim($baseUnitName));
            if ($this->unitsPerRoll() > 1) {
                $options->push([
                    'name' => 'roll',
                    'symbol' => 'roll',
                    'conversion_factor' => $this->unitsPerRoll(),
                    'selling_price' => null,
                    'wholesale_price' => null,
                    'is_default_sales_unit' => strtolower((string) $this->unit_type) === 'roll',
                ]);
            }
            if ($this->unitsPerCarton() > 1 && !in_array($baseUnitKey, ['carton', 'cartons', 'ctn'], true)) {
                $options->push([
                    'name' => 'carton',
                    'symbol' => 'ctn',
                    'conversion_factor' => $this->unitsPerCarton(),
                    'selling_price' => null,
                    'wholesale_price' => null,
                    'is_default_sales_unit' => strtolower((string) $this->unit_type) === 'carton',
                ]);
            }
        }

        return [
            'options' => $options->all(),
            'selection_configured' => $selectionConfigured,
        ];
    }

    public function stockBreakdown(?float $quantity = null): array
    {
        $remainingUnits = max(0, (float) ($quantity ?? ($this->stock ?? $this->stock_quantity ?? 0)));
        $unitsPerRoll = $this->unitsPerRoll();
        $unitsPerCarton = $this->unitsPerCarton();

        $cartons = 0;
        $rolls = 0;

        if ($unitsPerCarton > 0) {
            $cartons = (int) floor($remainingUnits / $unitsPerCarton);
            $remainingUnits -= $cartons * $unitsPerCarton;
        }

        if ($unitsPerRoll > 0) {
            $rolls = (int) floor($remainingUnits / $unitsPerRoll);
            $remainingUnits -= $rolls * $unitsPerRoll;
        }

        return [
            'cartons' => $cartons,
            'rolls' => $rolls,
            'units' => round($remainingUnits, 6),
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
        if ($this->usesStandaloneCartonStockUnit()) {
            return 'ctn';
        }

        $legacyLabel = strtolower(trim((string) ($this->base_unit_name ?? '')));
        if ($legacyLabel !== '' && !in_array($legacyLabel, ['unit', 'units'], true)) {
            return $legacyLabel;
        }

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

    public function variantLabel(): string
    {
        return collect([$this->brand, $this->fashion_size, $this->fashion_color])
            ->filter(fn ($value) => filled($value))
            ->implode(' · ');
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

    public function productUnits(): HasMany
    {
        return $this->hasMany(ProductUnit::class);
    }

    public function activeProductUnits(): HasMany
    {
        return $this->productUnits()->where('status', 'active')->orderByDesc('is_base_unit')->orderBy('unit_name');
    }

    public function resolveUnitConversion(?string $unitName = null): array
    {
        $requestedUnit = strtolower(trim((string) $unitName));
        $baseUnit = null;

        if (Schema::hasTable('product_units')) {
            $baseUnit = $this->productUnits()
                ->where('is_base_unit', true)
                ->where('status', 'active')
                ->first();
        }

        if ($requestedUnit !== '' && Schema::hasTable('product_units')) {
            $matchedUnit = $this->productUnits()
                ->where('status', 'active')
                ->where(function ($query) use ($requestedUnit) {
                    $query->whereRaw('LOWER(unit_name) = ?', [$requestedUnit])
                        ->orWhereRaw('LOWER(unit_symbol) = ?', [$requestedUnit]);
                })
                ->first();

            if ($matchedUnit) {
                $matchedFactor = (float) $matchedUnit->conversion_factor;
                if ($matchedFactor <= 1 && in_array($requestedUnit, ['carton', 'ctn'], true)) {
                    $matchedFactor = (float) $this->unitsPerCarton();
                }

                return [
                    'unit_name' => (string) ($matchedUnit->unit_symbol ?: $matchedUnit->unit_name),
                    'conversion_factor' => max(1, $matchedFactor),
                    'purchase_price' => $matchedUnit->purchase_price,
                    'selling_price' => $matchedUnit->selling_price,
                    'wholesale_price' => $matchedUnit->wholesale_price,
                ];
            }
        }

        if ($requestedUnit !== '') {
            $legacyFactor = match ($requestedUnit) {
                'carton', 'ctn' => max(1, $this->unitsPerCarton()),
                'roll' => max(1, $this->unitsPerRoll()),
                default => 1,
            };

            return [
                'unit_name' => $unitName,
                'conversion_factor' => $legacyFactor,
                'purchase_price' => null,
                'selling_price' => null,
                'wholesale_price' => null,
            ];
        }

        return [
            'unit_name' => (string) ($baseUnit?->unit_symbol ?: $baseUnit?->unit_name ?: $this->stockUnitSymbol()),
            'conversion_factor' => max(1, (float) ($baseUnit?->conversion_factor ?? 1)),
            'purchase_price' => $baseUnit?->purchase_price,
            'selling_price' => $baseUnit?->selling_price,
            'wholesale_price' => $baseUnit?->wholesale_price,
        ];
    }

    public function quantityToBase(float|int $quantity, ?string $unitName = null): float
    {
        $conversion = $this->resolveUnitConversion($unitName);

        return round(max(0, (float) $quantity) * max(1, (float) $conversion['conversion_factor']), 6);
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
