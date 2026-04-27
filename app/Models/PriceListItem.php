<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class PriceListItem extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'price_list_id', 'product_id', 'price',
        'min_quantity', 'currency',
    ];

    protected $casts = [
        'price'        => 'decimal:2',
        'min_quantity' => 'decimal:4',
    ];

    public function priceList()
    {
        return $this->belongsTo(PriceList::class);
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }
}
