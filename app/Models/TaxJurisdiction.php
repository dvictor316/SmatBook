<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TaxJurisdiction extends Model
{
    use HasFactory;

    protected $fillable = [
        'company_id',
        'user_id',
        'branch_id',
        'branch_name',
        'name',
        'country_code',
        'region',
        'currency_code',
        'filing_frequency',
        'filing_deadline_days',
        'tax_authority_name',
        'tax_authority_reference',
        'tax_authority_email',
        'tax_authority_phone',
        'portal_url',
        'registration_threshold',
        'is_default',
        'metadata',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'is_default' => 'boolean',
        'filing_deadline_days' => 'integer',
        'registration_threshold' => 'decimal:2',
        'metadata' => 'array',
    ];

    public function taxCodes(): HasMany
    {
        return $this->hasMany(TaxCode::class, 'tax_jurisdiction_id');
    }

    public function filings(): HasMany
    {
        return $this->hasMany(TaxFiling::class, 'tax_jurisdiction_id');
    }

    public function withholdingRules(): HasMany
    {
        return $this->hasMany(WithholdingRule::class, 'tax_jurisdiction_id');
    }

    public function accountMappings(): HasMany
    {
        return $this->hasMany(TaxAccountMapping::class, 'tax_jurisdiction_id');
    }
}
