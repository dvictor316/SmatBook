<?php

namespace App\Models;

use App\Models\Traits\TenantScoped;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class HotelOperationalEvent extends Model
{
    use HasFactory, TenantScoped;

    protected $fillable = [
        'company_id',
        'property_id',
        'reservation_id',
        'stay_id',
        'customer_id',
        'room_id',
        'event_type',
        'title',
        'description',
        'meta',
        'created_by',
    ];

    protected $casts = [
        'meta' => 'array',
    ];
}
