<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TaxAccountMapping extends Model
{
    use HasFactory;

    protected $fillable = [
        'company_id',
        'user_id',
        'branch_id',
        'branch_name',
        'tax_jurisdiction_id',
        'tax_code_id',
        'country_code',
        'tax_type',
        'role',
        'account_code',
        'account_name',
        'is_required',
        'effective_from',
        'effective_to',
        'metadata',
    ];

    protected $casts = [
        'is_required' => 'boolean',
        'effective_from' => 'date',
        'effective_to' => 'date',
        'metadata' => 'array',
    ];

    public function jurisdiction(): BelongsTo
    {
        return $this->belongsTo(TaxJurisdiction::class, 'tax_jurisdiction_id');
    }

    public function taxCode(): BelongsTo
    {
        return $this->belongsTo(TaxCode::class, 'tax_code_id');
    }
}
