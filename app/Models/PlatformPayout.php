<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class PlatformPayout extends Model
{
    use HasFactory;

    protected $table = 'platform_payouts';

    protected $fillable = [
        'recipient_name',
        'recipient_type',
        'recipient_user_id',
        'amount',
        'payout_type',
        'description',
        'notes',
        'recorded_by',
        'paid_at',
    ];

    protected $casts = [
        'amount'   => 'decimal:2',
        'paid_at'  => 'datetime',
    ];

    public function recorder()
    {
        return $this->belongsTo(User::class, 'recorded_by');
    }

    public function recipient()
    {
        return $this->belongsTo(User::class, 'recipient_user_id');
    }

    public function getRecipientTypeLabelAttribute(): string
    {
        return match ($this->recipient_type) {
            'state_manager' => 'State Manager',
            'agent' => 'Agent',
            'app_user' => 'App User',
            default => 'External',
        };
    }
}
