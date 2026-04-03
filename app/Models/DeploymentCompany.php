<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Models\Traits\TenantScoped;

class DeploymentCompany extends Model
{
    use HasFactory, TenantScoped;

    /**
     * The table associated with the model.
     * This acts as the "Handshake" ledger between Managers and Companies.
     */
    protected $table = 'deployment_companies';

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'manager_id',      // The User ID of the Deployment Manager
        'company_id',      // The ID of the created Company
        'deployment_status',
        'manager_commission',
        'setup_config'
    ];

    /**
     * The attributes that should be cast.
     */
    protected $casts = [
        'setup_config' => 'json',
        'manager_commission' => 'decimal:2',
    ];

    // =========================================================================
    // RELATIONSHIPS
    // =========================================================================

    /**
     * Get the manager (User) who performed this deployment.
     */
    public function manager(): BelongsTo
    {
        return $this->belongsTo(User::class, 'manager_id');
    }

    /**
     * Get the company that was deployed.
     */
    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class, 'company_id');
    }
}
