<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Traits\TenantScoped;

class HotelRoom extends Model
{
    use HasFactory, TenantScoped;

    protected $table = 'hotel_rooms';

    protected $fillable = [
        'company_id', 'property_id', 'room_type_id', 'room_number', 'floor', 'wing', 'base_rate_override', 'operational_status', 'housekeeping_status', 'is_active', 'notes'
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function property()
    {
        return $this->belongsTo(HotelProperty::class, 'property_id');
    }

    public function type()
    {
        return $this->belongsTo(HotelRoomType::class, 'room_type_id');
    }
}
