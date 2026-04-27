<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Timesheet extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'company_id', 'branch_id', 'employee_id', 'user_id', 'project_id',
        'customer_id', 'week_start_date', 'status', 'total_hours', 'billable_hours',
        'hourly_rate', 'billable_amount', 'notes', 'approved_by', 'approved_at', 'invoice_id',
    ];

    protected $casts = [
        'week_start_date' => 'date',
        'approved_at'     => 'datetime',
        'total_hours'     => 'decimal:2',
        'billable_hours'  => 'decimal:2',
        'hourly_rate'     => 'decimal:4',
        'billable_amount' => 'decimal:2',
    ];

    public function company(): BelongsTo    { return $this->belongsTo(Company::class); }
    public function employee(): BelongsTo   { return $this->belongsTo(Employee::class); }
    public function customer(): BelongsTo   { return $this->belongsTo(Customer::class); }
    public function approvedBy(): BelongsTo { return $this->belongsTo(User::class, 'approved_by'); }
    public function invoice(): BelongsTo    { return $this->belongsTo(Invoice::class); }
    public function entries(): HasMany      { return $this->hasMany(TimesheetEntry::class); }

    public function scopeForCompany($query, int $companyId) { return $query->where('company_id', $companyId); }
    public function scopePending($query)    { return $query->where('status', 'submitted'); }
    public function scopeApproved($query)   { return $query->where('status', 'approved'); }
}

class TimesheetEntry extends Model
{
    protected $table = 'timesheet_entries';

    protected $fillable = [
        'timesheet_id', 'entry_date', 'project_id', 'task_id',
        'activity_description', 'hours', 'is_billable', 'hourly_rate', 'line_total',
    ];

    protected $casts = [
        'entry_date'   => 'date',
        'hours'        => 'decimal:2',
        'hourly_rate'  => 'decimal:4',
        'line_total'   => 'decimal:2',
        'is_billable'  => 'boolean',
    ];

    public function timesheet(): BelongsTo { return $this->belongsTo(Timesheet::class); }
}
