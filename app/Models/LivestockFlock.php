<?php

namespace App\Models;

use App\Models\Traits\TenantScoped;
use Illuminate\Database\Eloquent\Model;

class LivestockFlock extends Model
{
    use TenantScoped;

    protected $fillable = ['company_id', 'farm_id', 'batch_code', 'breed', 'placement_date', 'age_at_placement_weeks', 'opening_birds', 'current_birds', 'cost_per_bird', 'supplier', 'status', 'closed_on', 'notes', 'created_by'];

    protected $casts = ['placement_date' => 'date', 'closed_on' => 'date', 'cost_per_bird' => 'decimal:2'];

    public function farm()
    {
        return $this->belongsTo(LivestockFarm::class, 'farm_id');
    }

    public function productions()
    {
        return $this->hasMany(LivestockDailyProduction::class, 'flock_id');
    }
}
