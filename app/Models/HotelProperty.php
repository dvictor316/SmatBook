<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Traits\TenantScoped;

class HotelProperty extends Model
{
    use HasFactory, TenantScoped;

    protected $table = 'hotel_properties';

    protected $fillable = [
        'company_id', 'branch_id', 'name', 'code', 'address', 'city', 'state', 'country', 'phone', 'email', 'currency_code', 'timezone', 'default_checkin_time', 'default_checkout_time', 'is_active'
    ];

    protected $casts = [
        'default_checkin_time' => 'datetime:H:i',
        'default_checkout_time' => 'datetime:H:i',
        'is_active' => 'boolean',
    ];

    public function rooms()
    {
        return $this->hasMany(HotelRoom::class, 'property_id');
    }

    public function roomTypes()
    {
        return $this->hasMany(HotelRoomType::class, 'property_id');
    }
}
