<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PosSuspendedSale extends Model
{
    protected $fillable = [
        'company_id',
        'branch_id',
        'branch_name',
        'created_by',
        'customer_id',
        'customer_name',
        'items',
        'item_count',
        'total',
        'claim_token',
        'claimed_by',
        'claimed_at',
    ];

    protected $casts = [
        'items' => 'array',
        'total' => 'decimal:2',
        'claimed_at' => 'datetime',
    ];

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function claimant(): BelongsTo
    {
        return $this->belongsTo(User::class, 'claimed_by');
    }
}
