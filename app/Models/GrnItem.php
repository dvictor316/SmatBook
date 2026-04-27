<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class GrnItem extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'grn_id', 'product_id', 'product_name',
        'ordered_quantity', 'received_quantity', 'rejected_quantity',
        'unit_cost', 'lot_number', 'serial_number', 'expiry_date',
    ];

    protected $casts = [
        'ordered_quantity'  => 'decimal:4',
        'received_quantity' => 'decimal:4',
        'rejected_quantity' => 'decimal:4',
        'unit_cost'         => 'decimal:2',
        'expiry_date'       => 'date',
    ];

    public function grn()
    {
        return $this->belongsTo(GoodsReceivedNote::class, 'grn_id');
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }
}
