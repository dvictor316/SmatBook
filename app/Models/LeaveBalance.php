<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LeaveBalance extends Model
{
    protected $fillable = [
        'company_id', 'employee_id', 'leave_type_id',
        'year', 'entitled_days', 'used_days',
        'carried_forward_days', 'remaining_days',
    ];

    protected $casts = [
        'entitled_days'        => 'decimal:1',
        'used_days'            => 'decimal:1',
        'carried_forward_days' => 'decimal:1',
        'remaining_days'       => 'decimal:1',
    ];

    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }

    public function leaveType()
    {
        return $this->belongsTo(LeaveType::class);
    }

    public function scopeForCompany($query, $companyId)
    {
        return $query->where('company_id', $companyId);
    }
}
