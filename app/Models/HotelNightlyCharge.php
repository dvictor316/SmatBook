<?php

namespace App\Models;

use App\Models\Traits\TenantScoped;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class HotelNightlyCharge extends Model
{
    use HasFactory, TenantScoped;

    protected $fillable = [
        'company_id',
        'property_id',
        'stay_id',
        'folio_id',
        'room_id',
        'charge_date',
        'amount',
        'status',
        'folio_item_id',
        'night_audit_id',
        'posted_by',
        'posted_at',
        'note',
    ];

    protected $casts = [
        'charge_date' => 'date',
        'posted_at' => 'datetime',
        'amount' => 'decimal:2',
    ];
}
