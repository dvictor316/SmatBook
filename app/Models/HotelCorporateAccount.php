<?php

namespace App\Models;

use App\Models\Traits\TenantScoped;
use Illuminate\Database\Eloquent\Model;

class HotelCorporateAccount extends Model
{
    use TenantScoped;

    protected $fillable = ['company_id', 'property_id', 'customer_id', 'account_code', 'company_name', 'tax_id', 'billing_contact', 'billing_email', 'billing_phone', 'billing_address', 'credit_limit', 'payment_terms_days', 'negotiated_discount_percent', 'status', 'notes', 'created_by'];
    protected $casts = ['credit_limit' => 'decimal:2', 'negotiated_discount_percent' => 'decimal:2'];

    public function folios() { return $this->hasMany(GuestFolio::class, 'corporate_account_id'); }
    public function reservations() { return $this->hasMany(Reservation::class, 'corporate_account_id'); }
}
