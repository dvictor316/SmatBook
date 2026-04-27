<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ReportSchedule extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'company_id', 'name', 'report_type', 'frequency', 'format',
        'recipients', 'parameters', 'cron_expression',
        'next_run_at', 'last_run_at', 'is_active', 'created_by',
    ];

    protected $casts = [
        'recipients'   => 'array',
        'parameters'   => 'array',
        'is_active'    => 'boolean',
        'next_run_at'  => 'datetime',
        'last_run_at'  => 'datetime',
    ];

    public function company(): BelongsTo   { return $this->belongsTo(Company::class); }
    public function createdBy(): BelongsTo { return $this->belongsTo(User::class, 'created_by'); }

    public function scopeForCompany($query, int $companyId) { return $query->where('company_id', $companyId); }
    public function scopeActive($query) { return $query->where('is_active', true); }
    public function scopeDue($query)    { return $query->where('next_run_at', '<=', now())->where('is_active', true); }
}
