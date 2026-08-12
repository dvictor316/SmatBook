<?php

namespace App\Models;

use App\Models\Traits\TenantScoped;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class HotelHousekeepingTask extends Model
{
    use HasFactory, TenantScoped;

    protected $fillable = [
        'company_id',
        'property_id',
        'room_id',
        'stay_id',
        'task_type',
        'status',
        'priority',
        'note',
        'assigned_to',
        'created_by',
        'completed_by',
        'completed_at',
    ];

    protected $casts = [
        'completed_at' => 'datetime',
    ];
}
