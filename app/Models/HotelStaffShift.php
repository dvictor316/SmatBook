<?php

namespace App\Models;

use App\Models\Traits\TenantScoped;
use Illuminate\Database\Eloquent\Model;

class HotelStaffShift extends Model
{
    use TenantScoped;

    protected $fillable = ['company_id', 'property_id', 'user_id', 'shift_date', 'department', 'shift_name', 'starts_at', 'ends_at', 'status', 'opening_float', 'closing_cash', 'handover_note', 'created_by'];
    protected $casts = ['shift_date' => 'date', 'opening_float' => 'decimal:2', 'closing_cash' => 'decimal:2'];

    public function user() { return $this->belongsTo(User::class); }
    public function property() { return $this->belongsTo(HotelProperty::class); }
}
