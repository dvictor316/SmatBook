<?php

namespace App\Models;

use App\Models\Traits\TenantScoped;
use Illuminate\Database\Eloquent\Model;

class HotelRateRestriction extends Model
{
    use TenantScoped;

    protected $fillable = ['company_id', 'property_id', 'rate_plan_id', 'start_date', 'end_date', 'applicable_days', 'min_stay', 'max_stay', 'closed_to_arrival', 'closed_to_departure', 'stop_sell', 'notes', 'created_by'];
    protected $casts = ['start_date' => 'date', 'end_date' => 'date', 'closed_to_arrival' => 'boolean', 'closed_to_departure' => 'boolean', 'stop_sell' => 'boolean'];

    public function ratePlan() { return $this->belongsTo(HotelRatePlan::class); }
}
