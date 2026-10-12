<?php

namespace App\Models;

use App\Models\Traits\TenantScoped;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ReturnableAsset extends Model
{
    use TenantScoped;

    protected $fillable = [
        'company_id', 'branch_id', 'branch_name', 'created_by', 'asset_code',
        'name', 'category', 'serial_number', 'total_quantity', 'available_quantity',
        'deposit_amount', 'replacement_cost', 'status', 'notes',
    ];

    protected $casts = [
        'total_quantity' => 'integer',
        'available_quantity' => 'integer',
        'deposit_amount' => 'decimal:2',
        'replacement_cost' => 'decimal:2',
    ];

    public function movements(): HasMany
    {
        return $this->hasMany(ReturnableAssetMovement::class);
    }
}
