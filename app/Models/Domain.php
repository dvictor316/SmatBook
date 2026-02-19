<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Domain extends Model
{
    use HasFactory;

    /**
     * REWRITTEN: DOMAIN MODEL
     * Aligning with Multi-Tenant SaaS Architecture
     */
    protected $fillable = [
        'tenant_id',       // Foreign key to User/Company
        'subscription_id', // Link to the specific payment record
        'customer_name', 
        'email',
        'domain_name',     // e.g., 'prefix.smatbook.com'
        'employees',       // Organization scale
        'package_name',
        'package_type',
        'price',
        'status',          // Pending, Active, Suspended, Expired
        'expiry_date',
        'setup_completed_at',
        'approved_at'
    ];

    /**
     * Dates to be treated as Carbon instances
     */
    protected $dates = [
        'expiry_date',
        'setup_completed_at',
        'approved_at',
        'created_at',
        'updated_at'
    ];

    /**
     * RELATIONSHIP: Subscription
     * Each domain belongs to a specific subscription purchase.
     */
    public function subscription()
    {
        return $this->belongsTo(Subscription::class);
    }

    /**
     * RELATIONSHIP: User (Owner)
     * The tenant/customer who owns this domain.
     */
    public function owner()
    {
        return $this->belongsTo(User::class, 'tenant_id');
    }

    /**
     * RELATIONSHIP: Plan
     * Connects to the Plan model via the package name.
     */
    public function plan()
    {
        return $this->belongsTo(Plan::class, 'package_name', 'name');
    }

    /**
     * SCOPE: Active Domains
     */
    public function scopeActive($query)
    {
        return $query->where('status', 'Active');
    }

    /**
     * HELPER: Check if expired
     */
    public function isExpired()
    {
        return $this->expiry_date && $this->expiry_date->isPast();
    }
}