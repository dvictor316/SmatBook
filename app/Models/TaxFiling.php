<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TaxFiling extends Model
{
    use HasFactory;

    protected $fillable = [
        'tax_jurisdiction_id',
        'name',
        'period_start',
        'period_end',
        'due_date',
        'status',
        'total_taxable',
        'total_tax',
        'reference_no',
        'submitted_by',
        'submitted_at',
        'metadata',
    ];

    protected $casts = [
        'period_start' => 'date',
        'period_end' => 'date',
        'due_date' => 'date',
        'submitted_at' => 'datetime',
        'total_taxable' => 'decimal:2',
        'total_tax' => 'decimal:2',
        'metadata' => 'array',
    ];

    public function jurisdiction(): BelongsTo
    {
        return $this->belongsTo(TaxJurisdiction::class, 'tax_jurisdiction_id');
    }

    public function submitter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'submitted_by');
    }
}

