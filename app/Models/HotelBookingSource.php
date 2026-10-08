<?php

namespace App\Models;

use App\Models\Traits\TenantScoped;
use Illuminate\Database\Eloquent\Model;

class HotelBookingSource extends Model
{
    use TenantScoped;

    protected $fillable = ['company_id', 'property_id', 'code', 'name', 'source_type', 'commission_type', 'commission_value', 'settlement_days', 'contact_name', 'contact_email', 'contact_phone', 'is_active', 'created_by'];
    protected $casts = ['commission_value' => 'decimal:2', 'is_active' => 'boolean'];
}
