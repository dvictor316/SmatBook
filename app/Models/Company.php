<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Company extends Model
{
    use HasFactory;

    /**
     * Attributes that can be mass-assigned.
     */
    protected $fillable = [
        'user_id',            // Main owner user ID
        'owner_id',           // Legacy or fallback owner ID
        'deployed_by',        // Added to match DB describe
        'name',
        'company_name',       // Added to match DB describe
        'email',
        'phone',
        'address',
        'status',             // e.g., active, pending_payment, suspended
        'country',
        'currency_code',
        'currency_symbol',
        'subdomain',          // Existing slug
        'domain_prefix',      // Primary routing prefix
        'domain',             // Full domain or primary slug
        'plan',               // e.g., Basic, Pro, Enterprise
        'industry',
        'logo',
        'subscription_start',
        'subscription_end',
    ];

    /**
     * Attributes cast to specific data types.
     */
    protected $casts = [
        'subscription_start' => 'datetime',
        'subscription_end'   => 'datetime',
        'created_at'         => 'datetime',
        'updated_at'         => 'datetime',
        'deleted_at'         => 'datetime',
    ];

    // =========================================================================
    // Relationships
    // =========================================================================

    /**
     * Get all users associated with the company.
     */
    public function users(): HasMany
    {
        return $this->hasMany(User::class, 'company_id');
    }

    /**
     * Get the owner user of the company.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * Get the subscription details for the company.
     */
    public function subscription(): HasOne
    {
        return $this->hasOne(Subscription::class, 'company_id')->latestOfMany('id');
    }

    /**
     * Get the deployment link for the company.
     */
    public function deploymentLink(): HasOne
    {
        return $this->hasOne(DeploymentCompany::class, 'company_id');
    }

    /**
     * Get all domains linked to the company.
     */
    public function domains(): HasMany
    {
        return $this->hasMany(Domain::class);
    }

    /**
     * Get all purchase transactions related to the company.
     */
    public function purchaseTransactions(): HasMany
    {
        return $this->hasMany(PurchaseTransaction::class);
    }

    // =========================================================================
    // Accessors & Helpers
    // =========================================================================

    /**
     * Get the currency symbol, defaulting based on country if not set.
     */
    public function getCurrencySymbolAttribute($value): string
    {
        if (!empty($value)) {
            return $value;
        }
        return ($this->country ?? 'Nigeria') === 'Nigeria' ? '₦' : '$';
    }

    /**
     * Generate the full URL for the company.
     */
    public function getFullUrlAttribute(): string
    {
        $baseDomain = env('SESSION_DOMAIN', 'smatbook.com');
        $cleanBase = ltrim($baseDomain, '.');
        $slug = $this->domain_prefix ?? $this->subdomain ?? $this->domain ?? 'app';

        return "https://$slug.$cleanBase";
    }
}
