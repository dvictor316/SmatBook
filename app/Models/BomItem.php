<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class BomItem extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'bom_id', 'component_product_id', 'component_name',
        'quantity', 'unit', 'unit_cost',
        'scrap_percentage', 'item_type', 'sort_order', 'notes',
    ];

    protected $casts = [
        'quantity'         => 'decimal:4',
        'unit_cost'        => 'decimal:2',
        'scrap_percentage' => 'decimal:2',
    ];

    public function bom()
    {
        return $this->belongsTo(BillOfMaterials::class, 'bom_id');
    }

    public function componentProduct()
    {
        return $this->belongsTo(Product::class, 'component_product_id');
    }

    public function getEffectiveQuantityAttribute(): float
    {
        return $this->quantity * (1 + $this->scrap_percentage / 100);
    }
}
