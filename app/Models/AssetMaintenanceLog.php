<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AssetMaintenanceLog extends Model
{
    protected $table = 'asset_maintenance_logs';

    protected $fillable = [
        'company_id', 'branch_id', 'fixed_asset_id', 'maintenance_type',
        'maintenance_date', 'next_maintenance_date', 'performed_by', 'vendor_name',
        'cost', 'status', 'description', 'findings', 'parts_replaced', 'created_by',
    ];

    protected $casts = [
        'maintenance_date'      => 'date',
        'next_maintenance_date' => 'date',
        'cost'                  => 'decimal:2',
    ];

    public function asset(): BelongsTo     { return $this->belongsTo(FixedAsset::class, 'fixed_asset_id'); }
    public function company(): BelongsTo   { return $this->belongsTo(Company::class); }
    public function createdBy(): BelongsTo { return $this->belongsTo(User::class, 'created_by'); }

    public function scopeForCompany($query, int $companyId) { return $query->where('company_id', $companyId); }

    public function getTypeBadgeAttribute(): string
    {
        return match($this->maintenance_type) {
            'corrective'  => 'badge-danger',
            'preventive'  => 'badge-success',
            'upgrade'     => 'badge-primary',
            default       => 'badge-secondary',
        };
    }
}
