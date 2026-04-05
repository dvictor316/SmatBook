<?php

namespace App\Models;

use App\Models\Traits\TenantScoped;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RecurringTransaction extends Model
{
    use HasFactory, TenantScoped;

    protected $fillable = [
        'company_id',
        'branch_id',
        'branch_name',
        'created_by',
        'updated_by',
        'source_type',
        'source_id',
        'name',
        'frequency',
        'interval_value',
        'starts_on',
        'next_run_on',
        'last_run_on',
        'ends_on',
        'status',
        'auto_post',
        'approval_required',
        'notes',
        'payload',
    ];

    protected $casts = [
        'starts_on' => 'date',
        'next_run_on' => 'date',
        'last_run_on' => 'datetime',
        'ends_on' => 'date',
        'auto_post' => 'boolean',
        'approval_required' => 'boolean',
        'payload' => 'array',
    ];

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
