<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Models\Traits\TenantScoped;

class DeploymentManagerPayout extends Model
{
    use HasFactory, TenantScoped;

    protected $fillable = [
        'manager_id',
        'payout_reference',
        'gateway',
        'status',
        'amount',
        'currency',
        'bank_name',
        'bank_code',
        'account_name',
        'account_number',
        'recipient_reference',
        'transfer_reference',
        'failure_reason',
        'approved_by',
        'approved_at',
        'processed_at',
        'paid_at',
        'is_automatic',
        'meta',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'approved_at' => 'datetime',
        'processed_at' => 'datetime',
        'paid_at' => 'datetime',
        'is_automatic' => 'boolean',
        'meta' => 'array',
    ];

    public function manager(): BelongsTo
    {
        return $this->belongsTo(User::class, 'manager_id');
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }
}
