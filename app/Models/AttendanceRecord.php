<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AttendanceRecord extends Model
{
    protected $table = 'attendance_records';

    protected $fillable = [
        'company_id', 'branch_id', 'employee_id', 'attendance_date',
        'check_in_time', 'check_out_time', 'hours_worked', 'overtime_hours',
        'status', 'check_in_method', 'notes', 'created_by',
    ];

    protected $casts = [
        'attendance_date' => 'date',
        'hours_worked'    => 'decimal:2',
        'overtime_hours'  => 'decimal:2',
    ];

    public function employee(): BelongsTo  { return $this->belongsTo(Employee::class); }
    public function company(): BelongsTo   { return $this->belongsTo(Company::class); }

    public function scopeForCompany($query, int $companyId) { return $query->where('company_id', $companyId); }
    public function scopeForDate($query, string $date)      { return $query->where('attendance_date', $date); }

    public function getStatusBadgeAttribute(): string
    {
        return match($this->status) {
            'present'  => 'badge-success',
            'absent'   => 'badge-danger',
            'late'     => 'badge-warning',
            'half_day' => 'badge-info',
            default    => 'badge-secondary',
        };
    }
}
