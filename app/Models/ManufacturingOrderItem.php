<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ManufacturingOrderItem extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'manufacturing_order_id', 'component_product_id', 'component_name',
        'required_quantity', 'issued_quantity', 'unit_cost', 'unit',
    ];

    protected $casts = [
        'required_quantity' => 'decimal:4',
        'issued_quantity'   => 'decimal:4',
        'unit_cost'         => 'decimal:2',
    ];

    public function manufacturingOrder()
    {
        return $this->belongsTo(ManufacturingOrder::class);
    }

    public function componentProduct()
    {
        return $this->belongsTo(Product::class, 'component_product_id');
    }

    public function getRemainingQuantityAttribute(): float
    {
        return max(0, $this->required_quantity - $this->issued_quantity);
    }
}
