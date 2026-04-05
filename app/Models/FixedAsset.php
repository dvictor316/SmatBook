<?php

namespace App\Models;

use App\Models\Traits\TenantScoped;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class FixedAsset extends Model
{
    use HasFactory, TenantScoped;

    protected $fillable = [
        'company_id',
        'branch_id',
        'branch_name',
        'created_by',
        'asset_code',
        'name',
        'account_id',
        'depreciation_account_id',
        'expense_account_id',
        'acquired_on',
        'cost',
        'salvage_value',
        'useful_life_months',
        'depreciation_method',
        'status',
        'accumulated_depreciation',
        'book_value',
        'last_depreciated_on',
        'notes',
    ];

    protected $casts = [
        'acquired_on' => 'date',
        'cost' => 'decimal:2',
        'salvage_value' => 'decimal:2',
        'accumulated_depreciation' => 'decimal:2',
        'book_value' => 'decimal:2',
        'last_depreciated_on' => 'date',
    ];

    public function assetAccount(): BelongsTo
    {
        return $this->belongsTo(Account::class, 'account_id');
    }

    public function depreciationAccount(): BelongsTo
    {
        return $this->belongsTo(Account::class, 'depreciation_account_id');
    }

    public function expenseAccount(): BelongsTo
    {
        return $this->belongsTo(Account::class, 'expense_account_id');
    }

    public function depreciations(): HasMany
    {
        return $this->hasMany(FixedAssetDepreciation::class);
    }
}
