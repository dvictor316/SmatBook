<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class PriceListItem extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'price_list_id', 'product_id', 'price', 'unit_price',
        'min_quantity', 'max_quantity', 'currency', 'notes',
    ];

    protected $casts = [
        'price'        => 'decimal:2',
        'unit_price'   => 'decimal:2',
        'min_quantity' => 'decimal:4',
        'max_quantity' => 'decimal:4',
    ];

    public function priceList()
    {
        return $this->belongsTo(PriceList::class);
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function getPriceAttribute($value)
    {
        return $value ?? ($this->attributes['unit_price'] ?? null);
    }
}
