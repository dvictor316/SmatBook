<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PriceList extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'company_id', 'branch_id', 'branch_name', 'name', 'code', 'currency',
        'discount_type', 'discount_value', 'type', 'adjustment_value',
        'is_default', 'valid_from', 'valid_to', 'applies_to', 'is_active',
        'notes', 'description', 'created_by',
    ];

    protected $casts = [
        'valid_from'     => 'date',
        'valid_to'       => 'date',
        'is_default'     => 'boolean',
        'is_active'      => 'boolean',
        'discount_value' => 'decimal:4',
    ];

    public function company(): BelongsTo  { return $this->belongsTo(Company::class); }
    public function items(): HasMany      { return $this->hasMany(PriceListItem::class); }
    public function customers(): HasMany  { return $this->hasMany(Customer::class); }

    public function scopeForCompany($query, int $companyId) { return $query->where('company_id', $companyId); }
    public function scopeActive($query)   { return $query->where('is_active', true); }

    public function getPriceForProduct(int $productId): ?float
    {
        $item = $this->items()->where('product_id', $productId)->first();
        return $item ? (float) $item->price : null;
    }
}

class PriceListItem extends Model
{
    protected $table = 'price_list_items';

    protected $fillable = [
        'price_list_id', 'product_id', 'price', 'unit_price', 'min_quantity',
        'max_quantity', 'currency', 'notes',
    ];

    protected $casts = [
        'price'        => 'decimal:4',
        'min_quantity' => 'decimal:4',
        'max_quantity' => 'decimal:4',
    ];

    public function priceList(): BelongsTo { return $this->belongsTo(PriceList::class); }
    public function product(): BelongsTo   { return $this->belongsTo(Product::class); }
}
