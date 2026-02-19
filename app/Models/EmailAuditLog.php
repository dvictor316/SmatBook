<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EmailAuditLog extends Model
{
    protected $fillable = [
        'event_type',
        'recipient',
        'subject',
        'status',
        'details',
        'error_message',
    ];

    protected $casts = [
        'details' => 'array',
    ];
}

