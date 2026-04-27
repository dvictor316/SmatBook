<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class LandedCost extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'company_id', 'branch_id', 'grn_id',
        'cost_type', 'description', 'amount', 'currency',
        'allocation_method', 'status', 'allocated_at',
        'notes', 'created_by',
    ];

    protected $casts = [
        'amount'       => 'decimal:2',
        'allocated_at' => 'datetime',
    ];

    public function grn()
    {
        return $this->belongsTo(GoodsReceivedNote::class, 'grn_id');
    }

    public function scopeForCompany($query, $companyId)
    {
        return $query->where('company_id', $companyId);
    }
}
