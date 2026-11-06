<?php

namespace App\Models;

use App\Models\Traits\TenantScoped;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StockTransferAudit extends Model
{
    use HasFactory, TenantScoped;

    protected $fillable = [
        'transfer_id',
        'reversal_of_audit_id',
        'company_id',
        'product_id',
        'from_branch_id',
        'from_branch_name',
        'to_branch_id',
        'to_branch_name',
        'quantity',
        'initiated_by',
        'notes',
    ];

    protected $casts = [
        'quantity' => 'float',
    ];

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function initiator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'initiated_by');
    }

    public function transfer(): BelongsTo
    {
        return $this->belongsTo(StockTransfer::class, 'transfer_id');
    }

    public function reversals(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(self::class, 'reversal_of_audit_id');
    }
}
