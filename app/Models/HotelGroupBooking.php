<?php

namespace App\Models;

use App\Models\Traits\TenantScoped;
use Illuminate\Database\Eloquent\Model;

class HotelGroupBooking extends Model
{
    use TenantScoped;

    protected $fillable = ['company_id', 'property_id', 'corporate_account_id', 'booking_source_id', 'primary_customer_id', 'group_code', 'group_name', 'arrival_date', 'departure_date', 'rooms_requested', 'adults', 'children', 'estimated_total', 'deposit_required', 'deposit_received', 'release_date', 'status', 'notes', 'created_by'];
    protected $casts = ['arrival_date' => 'date', 'departure_date' => 'date', 'release_date' => 'date', 'estimated_total' => 'decimal:2', 'deposit_required' => 'decimal:2', 'deposit_received' => 'decimal:2'];

    public function allocations() { return $this->hasMany(HotelGroupRoomAllocation::class, 'group_booking_id'); }
    public function corporateAccount() { return $this->belongsTo(HotelCorporateAccount::class, 'corporate_account_id'); }
    public function bookingSource() { return $this->belongsTo(HotelBookingSource::class, 'booking_source_id'); }
}
