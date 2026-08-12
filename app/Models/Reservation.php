<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Reservation extends Model
{
    use HasFactory;

    protected $fillable = [
        'company_id','property_id','reservation_number','customer_id','room_type_id','room_id',
        'arrival_date','arrival_time','departure_date','departure_time','nights','adults','children',
        'rate_plan_id','nightly_rate','subtotal','discount','tax','service_charge','other_charges','total',
        'deposit_required','deposit_received','balance','status','source','special_requests','internal_notes'
    ];

    protected $casts = [
        'arrival_date' => 'date',
        'departure_date' => 'date',
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

}
