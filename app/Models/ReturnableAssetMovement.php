<?php

namespace App\Models;

use App\Models\Traits\TenantScoped;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ReturnableAssetMovement extends Model
{
    use TenantScoped;

    protected $fillable = [
        'company_id', 'branch_id', 'branch_name', 'returnable_asset_id',
        'parent_movement_id', 'customer_id', 'created_by', 'movement_type',
        'custodian_name', 'custodian_phone', 'quantity', 'returned_quantity',
        'issued_on', 'due_on', 'returned_on', 'condition', 'deposit_held',
        'reference', 'notes',
    ];

    protected $casts = [
        'quantity' => 'integer',
        'returned_quantity' => 'integer',
        'issued_on' => 'date',
        'due_on' => 'date',
        'returned_on' => 'date',
        'deposit_held' => 'decimal:2',
    ];

    public function asset(): BelongsTo
    {
        return $this->belongsTo(ReturnableAsset::class, 'returnable_asset_id');
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function parentMovement(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_movement_id');
    }

    public function getOutstandingQuantityAttribute(): int
    {
        return max(0, (int) $this->quantity - (int) $this->returned_quantity);
    }
}
