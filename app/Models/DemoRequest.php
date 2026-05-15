<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DemoRequest extends Model
{
    use HasFactory;

    protected $fillable = [
        'full_name',
        'company_name',
        'business_type',
        'email',
        'phone',
        'country',
        'number_of_users',
        'purpose',
        'status',
        'admin_note',
        'approved_by',
        'approved_at',
        'expires_at',
        'demo_company_id',
        'demo_user_id',
    ];

    protected $casts = [
        'approved_at'  => 'datetime',
        'expires_at'   => 'datetime',
        'number_of_users' => 'integer',
    ];

    // ── Scopes ───────────────────────────────────────────────────────────────

    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopeApproved($query)
    {
        return $query->where('status', 'approved');
    }

    public function scopeRejected($query)
    {
        return $query->where('status', 'rejected');
    }

    public function scopeExpired($query)
    {
        return $query->where('status', 'expired');
    }

    // ── Relationships ────────────────────────────────────────────────────────

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function demoCompany(): BelongsTo
    {
        return $this->belongsTo(Company::class, 'demo_company_id');
    }

    public function demoUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'demo_user_id');
    }

    // ── Helpers ──────────────────────────────────────────────────────────────

    public function isExpired(): bool
    {
        return $this->expires_at && $this->expires_at->isPast();
    }

    public function isPending(): bool
    {
        return $this->status === 'pending';
    }

    public function isApproved(): bool
    {
        return $this->status === 'approved';
    }

    public function statusBadgeClass(): string
    {
        return match ($this->status) {
            'pending'  => 'badge-warning',
            'approved' => 'badge-success',
            'rejected' => 'badge-danger',
            'expired'  => 'badge-secondary',
            default    => 'badge-light',
        };
    }
}
