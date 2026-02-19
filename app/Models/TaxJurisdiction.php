<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TaxJurisdiction extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'country_code',
        'region',
        'currency_code',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
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
}

