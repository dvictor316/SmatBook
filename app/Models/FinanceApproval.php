<?php

namespace App\Models;

use App\Models\Traits\TenantScoped;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class FinanceApproval extends Model
{
    use HasFactory, TenantScoped;

    protected $fillable = [
        'company_id',
        'branch_id',
        'branch_name',
        'requested_by',
        'approved_by',
        'approval_type',
        'approvable_type',
        'approvable_id',
        'reference_no',
        'title',
        'amount',
        'status',
        'submitted_at',
        'acted_at',
        'decision_notes',
        'snapshot',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'submitted_at' => 'datetime',
        'acted_at' => 'datetime',
        'snapshot' => 'array',
    ];

    public function approvable(): MorphTo
    {
        return $this->morphTo();
    }

    public function requester(): BelongsTo
    {
        return $this->belongsTo(User::class, 'requested_by');
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }
}
