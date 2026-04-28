<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Traits\TenantScoped;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Schema;

class Employee extends Model
{
    use HasFactory, TenantScoped;

    protected $fillable = [
        'company_id',
        'branch_id',
        'user_id',
        'name', 'employee_id', 'department', 'job_title',
        'email', 'phone', 'employment_date',
        'bank_name', 'account_number', 'tax_id',
        'basic_salary', 'total_allowances', 'total_deductions',
        'gross_pay', 'net_pay',
        'allowances', 'deductions', 'status',
        'business_id',
    ];

    protected $casts = [
        'allowances'       => 'array',
        'deductions'       => 'array',
        'basic_salary'     => 'decimal:2',
        'total_allowances' => 'decimal:2',
        'total_deductions' => 'decimal:2',
        'gross_pay'        => 'decimal:2',
        'net_pay'          => 'decimal:2',
        'employment_date'  => 'date',
    ];

    public function payrolls()
    {
        return $this->hasMany(Payroll::class);
    }

    public function latestPayroll()
    {
        return $this->hasOne(Payroll::class)->latestOfMany('pay_period');
    }

    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    public function scopeForWorkspaceCompany(Builder $query, int $companyId): Builder
    {
        $table = $this->getTable();

        if (Schema::hasColumn($table, 'company_id')) {
            return $query->where($table . '.company_id', $companyId);
        }

        if (Schema::hasColumn($table, 'business_id')) {
            return $query->where($table . '.business_id', $companyId);
        }

        return $query;
    }
}
