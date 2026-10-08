<?php

namespace App\Models;

use App\Models\Traits\TenantScoped;
use Illuminate\Database\Eloquent\Model;

class HotelGroupRoomAllocation extends Model
{
    use TenantScoped;

    protected $fillable = ['company_id', 'property_id', 'group_booking_id', 'room_type_id', 'room_id', 'reservation_id', 'guest_name', 'rooms', 'nightly_rate', 'status', 'notes', 'created_by'];
    protected $casts = ['nightly_rate' => 'decimal:2'];

    public function roomType() { return $this->belongsTo(HotelRoomType::class, 'room_type_id'); }
    public function room() { return $this->belongsTo(HotelRoom::class); }
    public function reservation() { return $this->belongsTo(Reservation::class); }
}
