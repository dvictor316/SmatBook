<?php

namespace App\Models;

use App\Models\Traits\TenantScoped;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

class LivestockInvestment extends Model
{
    use TenantScoped;

    protected $fillable = ['company_id', 'farm_id', 'flock_id', 'cost_class', 'category', 'description', 'cost_date', 'cost', 'salvage_value', 'useful_life_months', 'allocation_method', 'accumulated_allocation', 'status', 'notes', 'created_by'];

    protected $casts = ['cost_date' => 'date', 'cost' => 'decimal:2', 'salvage_value' => 'decimal:2', 'accumulated_allocation' => 'decimal:2'];

    public function farm()
    {
        return $this->belongsTo(LivestockFarm::class, 'farm_id');
    }

    public function flock()
    {
        return $this->belongsTo(LivestockFlock::class, 'flock_id');
    }

    public function allocationAsOf($date = null): float
    {
        $asOf = Carbon::parse($date ?: now())->endOfDay();
        if (! $this->cost_date || $this->cost_date->gt($asOf)) {
            return 0;
        }
        $base = max(0, (float) $this->cost - (float) $this->salvage_value);
        $life = max(1, (int) $this->useful_life_months);
        $months = min($life, max(0, $this->cost_date->startOfMonth()->diffInMonths($asOf->copy()->startOfMonth()) + 1));

        return round(min($base, ($base / $life) * $months), 2);
    }

    public function periodAllocation($from, $to): float
    {
        return max(0, round($this->allocationAsOf($to) - $this->allocationAsOf(Carbon::parse($from)->subDay()), 2));
    }
}
