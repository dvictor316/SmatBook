<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ManufacturingOrder extends Model
{
    use SoftDeletes;

    protected $table = 'manufacturing_orders';

    protected $fillable = [
        'company_id', 'branch_id', 'mo_number', 'bom_id', 'product_id',
        'planned_quantity', 'produced_quantity', 'planned_start_date', 'planned_end_date',
        'actual_start_date', 'actual_end_date', 'status', 'cost_center_id',
        'total_material_cost', 'total_labour_cost', 'total_overhead_cost',
        'notes', 'created_by',
    ];

    protected $casts = [
        'planned_start_date'  => 'date',
        'planned_end_date'    => 'date',
        'actual_start_date'   => 'date',
        'actual_end_date'     => 'date',
        'planned_quantity'    => 'decimal:4',
        'produced_quantity'   => 'decimal:4',
        'total_material_cost' => 'decimal:2',
        'total_labour_cost'   => 'decimal:2',
        'total_overhead_cost' => 'decimal:2',
    ];

    public function company(): BelongsTo    { return $this->belongsTo(Company::class); }
    public function bom(): BelongsTo        { return $this->belongsTo(BillOfMaterials::class, 'bom_id'); }
    public function product(): BelongsTo    { return $this->belongsTo(Product::class); }
    public function costCenter(): BelongsTo { return $this->belongsTo(CostCenter::class); }
    public function items(): HasMany        { return $this->hasMany(ManufacturingOrderItem::class); }

    public function scopeForCompany($query, int $companyId) { return $query->where('company_id', $companyId); }
    public function scopeActive($query)     { return $query->whereIn('status', ['confirmed', 'in_progress']); }

    public function getTotalCostAttribute(): float
    {
        return (float) ($this->total_material_cost + $this->total_labour_cost + $this->total_overhead_cost);
    }

    public function getProgressPercentageAttribute(): float
    {
        if ((float) $this->planned_quantity <= 0) return 0;
        return min(100, round(($this->produced_quantity / $this->planned_quantity) * 100, 1));
    }
}

class ManufacturingOrderItem extends Model
{
    protected $table = 'manufacturing_order_items';

    protected $fillable = [
        'manufacturing_order_id', 'product_id', 'product_name',
        'required_quantity', 'consumed_quantity', 'unit_cost', 'unit', 'lot_number',
    ];

    protected $casts = [
        'required_quantity'  => 'decimal:4',
        'consumed_quantity'  => 'decimal:4',
        'unit_cost'          => 'decimal:4',
    ];

    public function order(): BelongsTo   { return $this->belongsTo(ManufacturingOrder::class, 'manufacturing_order_id'); }
    public function product(): BelongsTo { return $this->belongsTo(Product::class); }
}
