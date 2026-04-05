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
        'period_label',
        'amount',
        'reference_no',
        'notes',
    ];

    protected $casts = [
        'run_date' => 'date',
        'amount' => 'decimal:2',
    ];

    public function asset(): BelongsTo
    {
        return $this->belongsTo(FixedAsset::class, 'fixed_asset_id');
    }
}
