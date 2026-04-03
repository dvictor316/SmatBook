<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\Traits\TenantScoped;

class EmailAuditLog extends Model
{
    use TenantScoped;
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
