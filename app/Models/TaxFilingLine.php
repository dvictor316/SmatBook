<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TaxFilingLine extends Model
{
    use HasFactory;

    protected $fillable = [
        'tax_filing_id',
        'line_key',
        'label',
        'tax_type',
        'taxable_base',
        'tax_amount',
        'adjustment_amount',
        'credit_amount',
        'net_amount',
        'metadata',
    ];

    protected $casts = [
        'taxable_base' => 'decimal:2',
        'tax_amount' => 'decimal:2',
        'adjustment_amount' => 'decimal:2',
        'credit_amount' => 'decimal:2',
        'net_amount' => 'decimal:2',
        'metadata' => 'array',
    ];

    public function filing(): BelongsTo
    {
        return $this->belongsTo(TaxFiling::class, 'tax_filing_id');
    }
}
