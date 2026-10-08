<?php

namespace App\Models;

use App\Models\Traits\TenantScoped;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Reservation extends Model
{
    use HasFactory, TenantScoped;

    protected $fillable = [
        'company_id', 'property_id', 'reservation_number', 'customer_id', 'room_type_id', 'room_id',
        'arrival_date', 'arrival_time', 'departure_date', 'departure_time', 'nights', 'adults', 'children',
        'rate_plan_id', 'nightly_rate', 'subtotal', 'discount', 'tax', 'service_charge', 'other_charges', 'total',
        'deposit_required', 'deposit_received', 'balance', 'status', 'source', 'special_requests', 'internal_notes',
        'created_by', 'confirmed_by', 'cancelled_by', 'cancellation_reason', 'cancelled_at', 'no_show_at',
        'booking_source_id', 'corporate_account_id', 'group_booking_id',
    ];

    protected $casts = [
        'arrival_date' => 'date',
        'departure_date' => 'date',
        'cancelled_at' => 'datetime',
        'no_show_at' => 'datetime',
    ];

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    public function room()
    {
        return $this->belongsTo(HotelRoom::class);
    }

    public function roomType()
    {
        return $this->belongsTo(HotelRoomType::class, 'room_type_id');
    }

    public function stay()
    {
        return $this->hasOne(Stay::class);
    }

    public function bookingSource() { return $this->belongsTo(HotelBookingSource::class, 'booking_source_id'); }
    public function corporateAccount() { return $this->belongsTo(HotelCorporateAccount::class, 'corporate_account_id'); }
    public function groupBooking() { return $this->belongsTo(HotelGroupBooking::class, 'group_booking_id'); }
}
