<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Traits\TenantScoped;

class HotelRatePlan extends Model
{
    use HasFactory, TenantScoped;

    protected $table = 'hotel_rate_plans';

    protected $fillable = [
        'company_id', 'property_id', 'name', 'code', 'room_type_id', 'rate', 'start_date', 'end_date', 'applicable_days', 'min_stay', 'max_stay', 'meal_plan', 'is_active'
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'start_date' => 'date',
        'end_date' => 'date',
    ];

    public function roomType()
    {
        return $this->belongsTo(HotelRoomType::class, 'room_type_id');
    }
}
