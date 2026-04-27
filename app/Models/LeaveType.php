<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class LeaveType extends Model
{
    protected $fillable = [
        'company_id', 'name', 'code', 'days_allowed_per_year',
        'is_paid', 'carry_forward', 'max_carry_forward_days',
        'requires_approval', 'is_active',
    ];

    protected $casts = [
        'is_paid'           => 'boolean',
        'carry_forward'     => 'boolean',
        'requires_approval' => 'boolean',
        'is_active'         => 'boolean',
    ];

    public function company(): BelongsTo    { return $this->belongsTo(Company::class); }
    public function requests(): HasMany     { return $this->hasMany(LeaveRequest::class); }
    public function balances(): HasMany     { return $this->hasMany(LeaveBalance::class); }

    public function scopeForCompany($query, int $companyId) { return $query->where('company_id', $companyId); }
    public function scopeActive($query)    { return $query->where('is_active', true); }
}

class LeaveRequest extends Model
{
    use \Illuminate\Database\Eloquent\SoftDeletes;

    protected $fillable = [
        'company_id', 'branch_id', 'employee_id', 'leave_type_id',
        'start_date', 'end_date', 'days_requested', 'status',
        'reason', 'rejection_reason', 'approved_by', 'approved_at', 'created_by',
    ];

    protected $casts = [
        'start_date'   => 'date',
        'end_date'     => 'date',
        'approved_at'  => 'datetime',
        'days_requested' => 'decimal:1',
    ];

    public function company(): BelongsTo    { return $this->belongsTo(Company::class); }
    public function employee(): BelongsTo   { return $this->belongsTo(Employee::class); }
    public function leaveType(): BelongsTo  { return $this->belongsTo(LeaveType::class); }
    public function approvedBy(): BelongsTo { return $this->belongsTo(User::class, 'approved_by'); }

    public function isPending(): bool   { return $this->status === 'pending'; }
    public function isApproved(): bool  { return $this->status === 'approved'; }
}

class LeaveBalance extends Model
{
    protected $table = 'leave_balances';

    protected $fillable = [
        'company_id', 'employee_id', 'leave_type_id', 'year',
        'entitled_days', 'used_days', 'pending_days',
        'remaining_days', 'carried_forward_days',
    ];

    protected $casts = [
        'entitled_days'       => 'decimal:1',
        'used_days'           => 'decimal:1',
        'pending_days'        => 'decimal:1',
        'remaining_days'      => 'decimal:1',
        'carried_forward_days' => 'decimal:1',
    ];

    public function employee(): BelongsTo  { return $this->belongsTo(Employee::class); }
    public function leaveType(): BelongsTo { return $this->belongsTo(LeaveType::class); }
}
