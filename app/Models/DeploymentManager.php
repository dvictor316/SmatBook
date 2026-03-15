<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;

class DeploymentManager extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     * domain => env('SESSION_DOMAIN', null)
     */
    protected $fillable = [
        'user_id',
        'business_name',
        'phone',
        'address',
        'id_type',
        'id_number',
        'status', // active, pending, suspended
        'deployment_limit',
        'commission_rate',
        'payout_bank_name',
        'payout_bank_code',
        'payout_account_name',
        'payout_account_number',
        'payout_provider',
        'payout_recipient_code',
        'payout_status',
        'auto_payout_enabled',
        'minimum_payout_amount',
    ];

    protected $casts = [
        'commission_rate' => 'decimal:2',
        'auto_payout_enabled' => 'boolean',
        'minimum_payout_amount' => 'decimal:2',
    ];

    // =========================================================================
    // RELATIONSHIPS
    // =========================================================================

    /**
     * Get the core user profile that owns this manager identity.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * Get the companies linked to this manager via the handshake table.
     * Rewritten to align with the 'deployment_companies' table logic.
     */
    public function companies(): HasManyThrough
    {
        return $this->hasManyThrough(
            Company::class,
            DeploymentCompany::class,
            'manager_id', // Foreign key on deployment_companies table (User ID)
            'id',         // Foreign key on companies table
            'user_id',    // Local key on deployment_managers table
            'company_id'  // Local key on deployment_companies table
        );
    }

    /**
     * Direct access to the handshake records for commission/status tracking.
     */
    public function deploymentRecords(): HasMany
    {
        return $this->hasMany(DeploymentCompany::class, 'manager_id', 'user_id');
    }

    // =========================================================================
    // HELPERS
    // =========================================================================

    /**
     * Check if the manager has reached their deployment cap.
     */
    public function hasReachedLimit(): bool
    {
        if ($this->deployment_limit === 0) return false; // 0 = unlimited
        return $this->companies()->count() >= $this->deployment_limit;
    }
}
