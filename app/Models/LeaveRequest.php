<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class LeaveRequest extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'company_id', 'branch_id', 'employee_id', 'leave_type_id',
        'start_date', 'end_date', 'days_requested',
        'reason', 'status', 'approved_by', 'approved_at',
        'rejection_reason', 'created_by',
    ];

    protected $casts = [
        'start_date'  => 'date',
        'end_date'    => 'date',
        'approved_at' => 'datetime',
    ];

    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }

    public function leaveType()
    {
        return $this->belongsTo(LeaveType::class);
    }

    public function approvedBy()
    {
        return $this->belongsTo(\App\Models\User::class, 'approved_by');
    }

    public function isPending(): bool
    {
        return $this->status === 'pending';
    }

    public function scopeForCompany($query, $companyId)
    {
        return $query->where('company_id', $companyId);
    }
}
