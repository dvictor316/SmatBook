<?php

namespace App\Models;

use App\Models\Traits\TenantScoped;
use Illuminate\Database\Eloquent\Model;

class LivestockDailyProduction extends Model
{
    use TenantScoped;

    protected $fillable = ['company_id', 'farm_id', 'flock_id', 'production_date', 'opening_birds', 'mortality', 'culled', 'closing_birds', 'egg_crates', 'loose_eggs', 'damaged_eggs', 'total_good_eggs', 'feed_kg', 'water_litres', 'hen_day_percent', 'medication', 'vaccination', 'notes', 'recorded_by'];

    protected $casts = ['production_date' => 'date', 'feed_kg' => 'decimal:3', 'water_litres' => 'decimal:3', 'hen_day_percent' => 'decimal:2'];

    public function flock()
    {
        return $this->belongsTo(LivestockFlock::class, 'flock_id');
    }
}
