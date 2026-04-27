<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class BillOfMaterials extends Model
{
    use SoftDeletes;

    protected $table = 'bill_of_materials';

    protected $fillable = [
        'company_id', 'branch_id', 'bom_number', 'product_id', 'output_quantity',
        'unit', 'bom_type', 'status', 'standard_cost', 'instructions', 'created_by',
    ];

    protected $casts = [
        'output_quantity' => 'decimal:4',
        'standard_cost'   => 'decimal:4',
    ];

    public function company(): BelongsTo  { return $this->belongsTo(Company::class); }
    public function product(): BelongsTo  { return $this->belongsTo(Product::class); }
    public function items(): HasMany      { return $this->hasMany(BomItem::class, 'bom_id'); }
    public function orders(): HasMany     { return $this->hasMany(ManufacturingOrder::class, 'bom_id'); }

    public function scopeForCompany($query, int $companyId) { return $query->where('company_id', $companyId); }
    public function scopeActive($query)   { return $query->where('status', 'active'); }

    public function calculateStandardCost(): float
    {
        return (float) $this->items->sum(function ($item) {
            return $item->quantity * $item->unit_cost * (1 + ($item->scrap_percentage / 100));
        });
    }
}

class BomItem extends Model
{
    protected $table = 'bom_items';

    protected $fillable = [
        'bom_id', 'component_product_id', 'component_name',
        'quantity', 'unit', 'unit_cost', 'scrap_percentage', 'item_type', 'sort_order',
    ];

    protected $casts = [
        'quantity'         => 'decimal:4',
        'unit_cost'        => 'decimal:4',
        'scrap_percentage' => 'decimal:4',
    ];

    public function bom(): BelongsTo             { return $this->belongsTo(BillOfMaterials::class, 'bom_id'); }
    public function componentProduct(): BelongsTo { return $this->belongsTo(Product::class, 'component_product_id'); }
}
