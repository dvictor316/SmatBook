<?php

namespace App\Models;

use App\Models\Traits\TenantScoped;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FixedAssetDepreciation extends Model
{
    use HasFactory, TenantScoped;

    protected $fillable = [
        'company_id',
        'branch_id',
        'branch_name',
        'fixed_asset_id',
        'created_by',
        'run_date',
        'period_start_on',
        'period_end_on',
        'period_label',
        'depreciation_frequency',
        'amount',
        'reference_no',
        'notes',
    ];

    protected $casts = [
        'run_date' => 'date',
        'period_start_on' => 'date',
        'period_end_on' => 'date',
        'amount' => 'decimal:2',
    ];

    public function asset(): BelongsTo
    {
        return $this->belongsTo(FixedAsset::class, 'fixed_asset_id');
    }
}
