<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use App\Models\Traits\TenantScoped;

class AccountingPeriod extends Model
{
    use HasFactory, TenantScoped;

    protected $fillable = [
        'name',
        'start_date',
        'end_date',
        'status',
        'closed_at',
        'closed_by',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'closed_at' => 'datetime',
    ];

    public function tasks(): HasMany
    {
        return $this->hasMany(CloseTask::class, 'accounting_period_id');
    }

    public function approvals(): HasMany
    {
        return $this->hasMany(CloseApproval::class, 'accounting_period_id');
    }
}
