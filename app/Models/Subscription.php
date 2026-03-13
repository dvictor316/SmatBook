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
use App\Models\Plan;
use Illuminate\Support\Facades\Schema;

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
        $base = env('SESSION_DOMAIN', 'smartprobook.com');
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
        if (strtolower((string) $this->status) === 'expired') {
            return true;
        }

        if ($this->end_date && $this->end_date->copy()->endOfDay()->isPast()) {
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

    public function isExpiringSoon(int $days = 7): bool
    {
        if ($this->isExpired() || !$this->end_date) {
            return false;
        }

        return $this->daysRemaining() <= $days;
    }

    public function planLabel(): string
    {
        return (string) ($this->plan_name ?: $this->plan ?: optional($this->company)->plan ?: 'Basic');
    }

    public function resolvedUserLimit(): ?int
    {
        if ($this->relationLoaded('plan_relationship') && $this->plan_relationship) {
            return $this->plan_relationship->resolvedUserLimit();
        }

        if ($this->plan_id && $plan = Plan::find($this->plan_id)) {
            return $plan->resolvedUserLimit();
        }

        return Plan::defaultUserLimitForName($this->planLabel());
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

    public static function expireDueSubscriptions(): int
    {
        if (! Schema::hasTable('subscriptions') || ! Schema::hasColumn('subscriptions', 'end_date')) {
            return 0;
        }

        return static::query()
            ->whereIn(\DB::raw("LOWER(COALESCE(status, ''))"), ['active', 'trial'])
            ->whereNotNull('end_date')
            ->whereDate('end_date', '<', now()->toDateString())
            ->update([
                'status' => 'Expired',
                'updated_at' => now(),
            ]);
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

    public static function resolveCurrentForUser($user): ?self
    {
        if (!$user) {
            return null;
        }

        $subscription = static::query()
            ->with('plan_relationship')
            ->where(function ($query) use ($user) {
                if (!empty($user->company_id)) {
                    $query->where('company_id', $user->company_id);
                }

                $query->orWhere('user_id', $user->id);
            })
            ->orderByRaw("
                CASE
                    WHEN LOWER(COALESCE(status, '')) IN ('active', 'trial') THEN 0
                    WHEN LOWER(COALESCE(status, '')) = 'expired' THEN 1
                    ELSE 2
                END
            ")
            ->orderByDesc('end_date')
            ->orderByDesc('id')
            ->first();

        if ($subscription && in_array(strtolower((string) $subscription->status), ['active', 'trial'], true)) {
            $subscription->isExpired();
            $subscription->refresh();
        }

        return $subscription;
    }
}
