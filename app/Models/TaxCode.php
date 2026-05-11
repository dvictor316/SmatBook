<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TaxCode extends Model
{
    use HasFactory;

    protected $fillable = [
        'tax_jurisdiction_id',
        'company_id',
        'user_id',
        'branch_id',
        'branch_name',
        'country_code',
        'code',
        'name',
        'description',
        'rate',
        'type',
        'category',
        'calculation_method',
        'is_compound',
        'compound_order',
        'effective_from',
        'effective_to',
        'filing_frequency',
        'filing_deadline_days',
        'report_template',
        'ledger_output_account_code',
        'ledger_input_account_code',
        'ledger_payable_account_code',
        'ledger_receivable_account_code',
        'ledger_expense_account_code',
        'registration_threshold',
        'supports_reverse_charge',
        'is_zero_rated',
        'is_exempt',
        'recoverability_rate',
        'applies_to',
        'metadata',
        'is_active',
    ];

    protected $casts = [
        'rate' => 'decimal:4',
        'is_compound' => 'boolean',
        'supports_reverse_charge' => 'boolean',
        'is_zero_rated' => 'boolean',
        'is_exempt' => 'boolean',
        'effective_from' => 'date',
        'effective_to' => 'date',
        'filing_deadline_days' => 'integer',
        'registration_threshold' => 'decimal:2',
        'recoverability_rate' => 'decimal:4',
        'applies_to' => 'array',
        'metadata' => 'array',
        'is_active' => 'boolean',
    ];

    public function jurisdiction(): BelongsTo
    {
        return $this->belongsTo(TaxJurisdiction::class, 'tax_jurisdiction_id');
    }

    public function accountMappings(): HasMany
    {
        return $this->hasMany(TaxAccountMapping::class, 'tax_code_id');
    }
}
