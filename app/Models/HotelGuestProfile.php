<?php

namespace App\Models;

use App\Models\Traits\TenantScoped;
use Illuminate\Database\Eloquent\Model;

class HotelGuestProfile extends Model
{
    use TenantScoped;

    protected $fillable = [
        'company_id', 'customer_id', 'nationality', 'date_of_birth', 'gender', 'id_type', 'id_number',
        'id_issuing_country', 'id_expiry_date', 'preferences', 'allergies', 'loyalty_number', 'vip_status',
        'do_not_rent', 'do_not_rent_reason', 'updated_by',
    ];

    protected $casts = ['date_of_birth' => 'date', 'id_expiry_date' => 'date', 'do_not_rent' => 'boolean'];

    public function customer() { return $this->belongsTo(Customer::class); }
}
