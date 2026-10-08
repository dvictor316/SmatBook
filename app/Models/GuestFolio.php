<?php

namespace App\Models;

use App\Models\Traits\TenantScoped;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class GuestFolio extends Model
{
    use HasFactory, TenantScoped;

    protected $fillable = [
        'company_id', 'property_id', 'stay_id', 'reservation_id', 'customer_id', 'folio_number', 'opening_deposit', 'total_charges', 'total_payments', 'balance', 'status', 'corporate_account_id', 'group_booking_id', 'due_date', 'folio_label',
    ];

    protected $casts = [
        'opening_deposit' => 'decimal:2',
        'total_charges' => 'decimal:2',
        'total_payments' => 'decimal:2',
        'balance' => 'decimal:2',
        'due_date' => 'date',
    ];

    public function stay()
    {
        return $this->belongsTo(Stay::class);
    }

    public function reservation()
    {
        return $this->belongsTo(Reservation::class);
    }

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    public function items()
    {
        return $this->hasMany(FolioItem::class, 'folio_id');
    }

    public function guestRequests()
    {
        return $this->hasMany(HotelGuestRequest::class, 'stay_id', 'stay_id');
    }

    public function corporateAccount()
    {
        return $this->belongsTo(HotelCorporateAccount::class, 'corporate_account_id');
    }

    public function groupBooking()
    {
        return $this->belongsTo(HotelGroupBooking::class, 'group_booking_id');
    }
}
