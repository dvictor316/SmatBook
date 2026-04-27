<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PurchaseRequisition extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'company_id', 'branch_id', 'requisition_number', 'request_date', 'required_date',
        'priority', 'status', 'requested_by', 'approved_by', 'approved_at',
        'department_id', 'cost_center_id', 'justification', 'rejection_reason',
    ];

    protected $casts = [
        'request_date'  => 'date',
        'required_date' => 'date',
        'approved_at'   => 'datetime',
    ];

    public function company(): BelongsTo    { return $this->belongsTo(Company::class); }
    public function requestedBy(): BelongsTo { return $this->belongsTo(User::class, 'requested_by'); }
    public function approvedBy(): BelongsTo  { return $this->belongsTo(User::class, 'approved_by'); }
    public function department(): BelongsTo  { return $this->belongsTo(Department::class); }
    public function costCenter(): BelongsTo  { return $this->belongsTo(CostCenter::class); }
    public function items(): HasMany         { return $this->hasMany(PurchaseRequisitionItem::class); }

    public function scopeForCompany($query, int $companyId) { return $query->where('company_id', $companyId); }
    public function scopePending($query)   { return $query->where('status', 'submitted'); }
    public function scopeApproved($query)  { return $query->where('status', 'approved'); }

    public function getEstimatedTotalAttribute(): float
    {
        return (float) $this->items()->sum('estimated_total');
    }
}

class PurchaseRequisitionItem extends Model
{
    protected $table = 'purchase_requisition_items';

    protected $fillable = [
        'purchase_requisition_id', 'product_id', 'product_name', 'unit',
        'quantity', 'estimated_unit_price', 'estimated_total', 'specification',
    ];

    protected $casts = [
        'quantity'              => 'decimal:4',
        'estimated_unit_price'  => 'decimal:2',
        'estimated_total'       => 'decimal:2',
    ];

    public function requisition(): BelongsTo { return $this->belongsTo(PurchaseRequisition::class, 'purchase_requisition_id'); }
    public function product(): BelongsTo     { return $this->belongsTo(Product::class); }
}
