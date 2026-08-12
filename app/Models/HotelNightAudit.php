<?php

namespace App\Models;

use App\Models\Traits\TenantScoped;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class HotelNightAudit extends Model
{
    use HasFactory, TenantScoped;

    protected $fillable = [
        'company_id',
        'property_id',
        'audit_date',
        'status',
        'stays_scanned',
        'charges_posted',
        'charges_skipped',
        'total_amount',
        'run_by',
        'run_at',
        'reopened_by',
        'reopened_at',
        'reopen_reason',
        'meta',
    ];

    protected $casts = [
        'audit_date' => 'date',
        'run_at' => 'datetime',
        'reopened_at' => 'datetime',
        'total_amount' => 'decimal:2',
        'meta' => 'array',
    ];
}
