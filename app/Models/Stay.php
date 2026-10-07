<?php

namespace App\Models;

use App\Models\Traits\TenantScoped;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Stay extends Model
{
    use HasFactory, TenantScoped;

    protected $fillable = [
        'company_id', 'property_id', 'reservation_id', 'customer_id', 'room_id', 'checkin_at', 'expected_checkout_at', 'actual_checkout_at', 'agreed_rate', 'adults', 'children', 'status', 'checked_in_by', 'checked_out_by',
    ];

    protected $casts = [
        'checkin_at' => 'datetime',
        'expected_checkout_at' => 'datetime',
        'actual_checkout_at' => 'datetime',
    ];

    public function reservation()
    {
        return $this->belongsTo(Reservation::class);
    }

    public function room()
    {
        return $this->belongsTo(HotelRoom::class);
    }

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    public function guestRequests()
    {
        return $this->hasMany(HotelGuestRequest::class);
    }
}
