<?php

namespace App\Models;

use App\Models\Traits\TenantScoped;
use Illuminate\Database\Eloquent\Model;

class LivestockFarm extends Model
{
    use TenantScoped;

    protected $fillable = ['company_id', 'branch_id', 'branch_name', 'name', 'code', 'farm_type', 'bird_capacity', 'eggs_per_crate', 'location', 'is_active', 'created_by'];

    protected $casts = ['is_active' => 'boolean'];

    public function flocks()
    {
        return $this->hasMany(LivestockFlock::class, 'farm_id');
    }

    public function investments()
    {
        return $this->hasMany(LivestockInvestment::class, 'farm_id');
    }

    public function productions()
    {
        return $this->hasMany(LivestockDailyProduction::class, 'farm_id');
    }
}
