<?php

namespace App\Models;

use App\Models\Traits\TenantScoped;
use Illuminate\Database\Eloquent\Model;

class HotelDepositTransaction extends Model
{
    use TenantScoped;

    protected $fillable = ['company_id', 'property_id', 'reservation_id', 'group_booking_id', 'corporate_account_id', 'folio_id', 'account_id', 'transaction_date', 'transaction_type', 'payment_method', 'amount', 'reference', 'status', 'notes', 'recorded_by'];
    protected $casts = ['transaction_date' => 'date', 'amount' => 'decimal:2'];
}
