<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WithholdingRule extends Model
{
    use HasFactory;

    protected $fillable = [
        'tax_jurisdiction_id',
        'name',
        'counterparty_type',
        'rate',
        'threshold_amount',
        'account_code',
        'is_active',
    ];

    protected $casts = [
        'rate' => 'decimal:4',
        'threshold_amount' => 'decimal:2',
        'is_active' => 'boolean',
    ];

    public function jurisdiction(): BelongsTo
    {
        return $this->belongsTo(TaxJurisdiction::class, 'tax_jurisdiction_id');
    }
}

