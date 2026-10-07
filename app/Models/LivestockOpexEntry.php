<?php

namespace App\Models;

use App\Models\Traits\TenantScoped;
use Illuminate\Database\Eloquent\Model;

class LivestockOpexEntry extends Model
{
    use TenantScoped;

    protected $fillable = ['company_id', 'farm_id', 'flock_id', 'expense_date', 'category', 'description', 'quantity', 'unit', 'unit_cost', 'amount', 'frequency', 'vendor', 'reference', 'created_by'];

    protected $casts = ['expense_date' => 'date', 'quantity' => 'decimal:3', 'unit_cost' => 'decimal:2', 'amount' => 'decimal:2'];

    public function flock()
    {
        return $this->belongsTo(LivestockFlock::class, 'flock_id');
    }
}
