<?php

/*
|--------------------------------------------------------------------------
| SUBSCRIPTION MODEL: app/Models/Subscription.php
|--------------------------------------------------------------------------
| REWRITE: Added missing plan_name column support to match Controller needs
| domain => env('SESSION_DOMAIN', null)
*/

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Carbon\Carbon;
use App\Support\GeoCurrency;

class Subscription extends Model
{
    use HasFactory;

    /**
     * REWRITTEN: SUBSCRIPTION MODEL
     * Modified to include 'plan_name' and 'plan' in fillable to prevent 
     * MassAssignment and SQL Handshake errors.
     */
    protected $fillable = [
        'user_id',
        'company_id',
        'plan_id',
        'plan',                // Alias to support various controller versions
        'plan_name',           // Key column for identifying plans in UI
        'subscriber_name',
        'domain_prefix',
        'employee_size',
        'amount',
        'billing_cycle',
        'start_date',
        'end_date',
        'status',              // Active, Pending, Expired, Suspended, Awaiting Payment, trial
        'payment_status',      // paid, unpaid, free
        'payment_gateway',     // paystack, flutterwave, opay
        'payment_reference',   // transaction ID from gateway
        'transfer_bank_id',
        'transfer_reference',
        'transfer_payer_name',
        'transfer_proof',
        'transfer_submitted_at',
        'transfer_validated_by',
        'transfer_validated_at',
        'transfer_validation_note',
        'transaction_reference', 
        'deployed_by',
        'activated_at',
        'initialized_at',
        'paid_at',             
        'payment_date',        
    ];

    /**
     * Casting attributes for Carbon and Currency logic.
     */
    protected $casts = [
        'start_date'   => 'date',
        'end_date'     => 'date',
        'paid_at'      => 'datetime',
        'payment_date' => 'datetime',
        'transfer_submitted_at' => 'datetime',
        'transfer_validated_at' => 'datetime',
        'amount'       => 'decimal:2'
    ];

    // =========================================================================
    // RELATIONSHIPS
    // =========================================================================

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class, 'company_id');
    }

    /**
     * RELATIONSHIP: Tenant
     * domain => env('SESSION_DOMAIN', null)
     */
    public function tenant(): BelongsTo
    {
        // Using user_id as the foreign key to link tenants to owners
        return $this->belongsTo(Tenant::class, 'user_id', 'user_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function plan_relationship(): BelongsTo
    {
        return $this->belongsTo(Plan::class, 'plan_id');
    }

    public function domain(): HasOne
    {
        return $this->hasOne(Domain::class);
    }

    // =========================================================================
    // SCOPES & UI HELPERS
    // =========================================================================

    public function scopeActive($query)
    {
        return $query->where('status', 'Active');
    }

    public function hasDomain(): bool
    {
        return !empty($this->domain_prefix) || ($this->company && !empty($this->company->subdomain));
    }

    /**
     * Generates workspace URL: {prefix}.{SESSION_DOMAIN}
     */
    public function getWorkspaceUrlAttribute(): string
    {
        $base = env('SESSION_DOMAIN', 'smatbook.com');
        $prefix = $this->domain_prefix ?? ($this->company ? $this->company->subdomain : null);
        
        return $prefix ? "https://{$prefix}." . ltrim($base, '.') : '#';
    }

    public function getFormattedAmountAttribute(): string
    {
        return GeoCurrency::format((float) $this->amount, 'NGN');
    }

    public function getStatusBadgeAttribute(): string
    {
        return match (strtolower($this->status)) {
            'active'           => 'badge-success',
            'trial'            => 'badge-primary',
            'pending', 'awaiting payment' => 'badge-warning',
            'expired'          => 'badge-danger',
            'suspended'        => 'badge-dark',
            default            => 'badge-secondary',
        };
    }

    // =========================================================================
    // CORE LOGIC
    // =========================================================================

    public function isExpired(): bool
    {
        if ($this->status === 'Expired') return true;

        if ($this->end_date && $this->end_date->isPast()) {
            $this->updateQuietly(['status' => 'Expired']);
            return true;
        }

        return false;
    }

    public function isValid(): bool
    {
        $activeStatuses = ['active', 'trial'];
        return in_array(strtolower($this->status), $activeStatuses) 
               && !$this->isExpired() 
               && ($this->payment_status === 'paid' || $this->payment_status === 'free');
    }

    public function daysRemaining(): int
    {
        if ($this->isExpired() || !$this->end_date) return 0;
        return (int) now()->diffInDays($this->end_date, false);
    }

    public function expiryMessage(): string
    {
        if (in_array(strtolower($this->status), ['pending', 'awaiting payment'])) {
            return 'Awaiting payment activation';
        }
        
        if ($this->isExpired()) {
            return 'Expired on ' . ($this->end_date ? $this->end_date->format('M d, Y') : 'N/A');
        }

        $days = $this->daysRemaining();
        if ($days <= 0) return 'Expires today';
        return "Expires in $days days (" . ($this->end_date ? $this->end_date->format('M d, Y') : 'N/A') . ")";
    }

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($subscription) {
            $subscription->status = $subscription->status ?? 'Pending';
            $subscription->payment_status = $subscription->payment_status ?? 'unpaid';
        });

        static::retrieved(function ($subscription) {
            if (in_array(strtolower($subscription->status), ['active', 'trial'])) {
                $subscription->isExpired();
            }
        });
    }
}
