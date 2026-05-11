<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TaxFiling extends Model
{
    use HasFactory;

    protected $fillable = [
        'tax_jurisdiction_id',
        'company_id',
        'user_id',
        'branch_id',
        'branch_name',
        'country_code',
        'name',
        'filing_type',
        'filing_frequency',
        'currency_code',
        'branch_scope',
        'period_start',
        'period_end',
        'due_date',
        'status',
        'total_taxable',
        'total_tax',
        'tax_due',
        'tax_credit',
        'tax_refund',
        'adjustments_total',
        'credits_total',
        'reference_no',
        'submitted_by',
        'submitted_at',
        'approved_by',
        'approved_at',
        'remitted_by',
        'remitted_at',
        'remittance_reference',
        'metadata',
    ];

    protected $casts = [
        'period_start' => 'date',
        'period_end' => 'date',
        'due_date' => 'date',
        'submitted_at' => 'datetime',
        'approved_at' => 'datetime',
        'remitted_at' => 'datetime',
        'total_taxable' => 'decimal:2',
        'total_tax' => 'decimal:2',
        'tax_due' => 'decimal:2',
        'tax_credit' => 'decimal:2',
        'tax_refund' => 'decimal:2',
        'adjustments_total' => 'decimal:2',
        'credits_total' => 'decimal:2',
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

    public function lines(): HasMany
    {
        return $this->hasMany(TaxFilingLine::class, 'tax_filing_id');
    }
}
