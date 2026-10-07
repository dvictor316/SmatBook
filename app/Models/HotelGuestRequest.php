<?php

namespace App\Models;

use App\Models\Traits\TenantScoped;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class HotelGuestRequest extends Model
{
    use HasFactory, TenantScoped;

    protected $fillable = [
        'company_id', 'property_id', 'reservation_id', 'stay_id', 'customer_id', 'room_id',
        'request_number', 'category', 'department', 'priority', 'status', 'subject', 'details',
        'requested_at', 'due_at', 'acknowledged_at', 'resolved_at', 'resolution_note',
        'assigned_to', 'created_by', 'resolved_by',
    ];

    protected $casts = [
        'requested_at' => 'datetime',
        'due_at' => 'datetime',
        'acknowledged_at' => 'datetime',
        'resolved_at' => 'datetime',
    ];

    public function reservation()
    {
        return $this->belongsTo(Reservation::class);
    }

    public function stay()
    {
        return $this->belongsTo(Stay::class);
    }

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    public function room()
    {
        return $this->belongsTo(HotelRoom::class);
    }

    public function assignee()
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }
}
