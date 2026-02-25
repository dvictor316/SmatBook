<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Payroll extends Model
{
    use HasFactory;

    protected $fillable = [
        'employee_id', 'payroll_run_id',
        'pay_period', 'basic_salary',
        'total_allowances', 'total_deductions',
        'gross_pay', 'net_pay',
        'allowances_json', 'deductions_json',
        'status', 'reference', 'paid_at',
        'business_id',
    ];

    protected $casts = [
        'pay_period'       => 'date',
        'paid_at'          => 'datetime',
        'basic_salary'     => 'decimal:2',
        'total_allowances' => 'decimal:2',
        'total_deductions' => 'decimal:2',
        'gross_pay'        => 'decimal:2',
        'net_pay'          => 'decimal:2',
    ];

    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }

    public function payrollRun()
    {
        return $this->belongsTo(PayrollRun::class);
    }

    public function getAllowanceDetailsAttribute()
    {
        return json_decode($this->allowances_json ?? '[]', true);
    }

    public function getDeductionDetailsAttribute()
    {
        return json_decode($this->deductions_json ?? '[]', true);
    }

    public function scopePaid($query)
    {
        return $query->where('status', 'paid');
    }

    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }
}

