<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Traits\TenantScoped;

class HotelRoomType extends Model
{
    use HasFactory, TenantScoped;

    protected $table = 'hotel_room_types';

    protected $fillable = [
        'company_id', 'property_id', 'name', 'code', 'description', 'bed_type', 'beds', 'max_adults', 'max_children', 'max_occupancy', 'base_rate', 'weekend_rate', 'extra_adult_charge', 'extra_child_charge', 'extra_bed_charge', 'is_active'
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function property()
    {
        return $this->belongsTo(HotelProperty::class, 'property_id');
    }

    public function rooms()
    {
        return $this->hasMany(HotelRoom::class, 'room_type_id');
    }
}
