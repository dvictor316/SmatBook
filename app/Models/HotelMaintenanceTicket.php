<?php

namespace App\Models;

use App\Models\Traits\TenantScoped;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class HotelMaintenanceTicket extends Model
{
    use HasFactory, TenantScoped;

    protected $fillable = [
        'company_id',
        'property_id',
        'room_id',
        'ticket_no',
        'status',
        'severity',
        'title',
        'description',
        'reported_by',
        'assigned_to',
        'resolved_by',
        'resolved_at',
        'resolution_note',
    ];

    protected $casts = [
        'resolved_at' => 'datetime',
    ];
}
