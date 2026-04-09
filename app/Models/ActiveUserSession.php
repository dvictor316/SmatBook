<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ActiveUserSession extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'company_id',
        'session_id',
        'device_fingerprint',
        'ip_address',
        'user_agent',
        'authenticated_at',
        'last_seen_at',
    ];

    protected $casts = [
        'authenticated_at' => 'datetime',
        'last_seen_at' => 'datetime',
    ];
}
