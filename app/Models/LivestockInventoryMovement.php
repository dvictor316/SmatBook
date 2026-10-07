<?php

namespace App\Models;

use App\Models\Traits\TenantScoped;
use Illuminate\Database\Eloquent\Model;

class LivestockInventoryMovement extends Model
{
    use TenantScoped;

    protected $fillable = [
        'company_id', 'farm_id', 'flock_id', 'movement_date', 'item_type', 'movement_type',
        'quantity', 'unit', 'unit_cost', 'total_value', 'reference', 'source_type',
        'source_id', 'notes', 'created_by',
    ];

    protected $casts = [
        'movement_date' => 'date',
        'quantity' => 'decimal:3',
        'unit_cost' => 'decimal:4',
        'total_value' => 'decimal:2',
    ];

    public function flock()
    {
        return $this->belongsTo(LivestockFlock::class, 'flock_id');
    }
}
