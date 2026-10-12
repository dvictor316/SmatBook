<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OnlineStoreOrderItem extends Model
{
    protected $fillable = [
        'online_store_order_id', 'product_id', 'product_name', 'sku',
        'quantity', 'unit_price', 'line_total',
    ];

    protected $casts = ['quantity' => 'decimal:4', 'unit_price' => 'decimal:2', 'line_total' => 'decimal:2'];
}
