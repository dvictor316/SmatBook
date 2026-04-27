<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProjectMilestone extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'company_id', 'project_id', 'customer_id', 'name', 'description',
        'due_date', 'completion_date', 'billing_amount', 'billing_type',
        'percentage', 'status', 'invoice_id', 'sort_order',
    ];

    protected $casts = [
        'due_date'        => 'date',
        'completion_date' => 'date',
        'billing_amount'  => 'decimal:2',
        'percentage'      => 'decimal:2',
    ];

    public function company(): BelongsTo  { return $this->belongsTo(Company::class); }
    public function customer(): BelongsTo { return $this->belongsTo(Customer::class); }
    public function invoice(): BelongsTo  { return $this->belongsTo(Invoice::class); }

    public function scopeForCompany($query, int $companyId) { return $query->where('company_id', $companyId); }
    public function scopePending($query)  { return $query->where('status', 'pending'); }
    public function scopeBillable($query) { return $query->where('status', 'completed'); }

    public function getStatusBadgeAttribute(): string
    {
        return match($this->status) {
            'completed' => 'badge-success',
            'billed'    => 'badge-info',
            'cancelled' => 'badge-secondary',
            'in_progress' => 'badge-warning',
            default     => 'badge-light',
        };
    }
}
