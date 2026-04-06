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
}
