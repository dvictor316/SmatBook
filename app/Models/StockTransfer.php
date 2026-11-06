<?php

namespace App\Models;

use App\Models\Traits\TenantScoped;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class StockTransfer extends Model
{
    use TenantScoped;

    protected $fillable = [
        'company_id',
        'reference',
        'transfer_date',
        'from_branch_id',
        'from_branch_name',
        'to_branch_id',
        'to_branch_name',
        'status',
        'notes',
        'initiated_by',
    ];

    protected $casts = [
        'transfer_date' => 'datetime',
    ];

    public function items(): HasMany
    {
        return $this->hasMany(StockTransferAudit::class, 'transfer_id');
    }

    public function initiator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'initiated_by');
    }

    public function includesCompanyWideRecords(): bool
    {
        return true;
    }
}
