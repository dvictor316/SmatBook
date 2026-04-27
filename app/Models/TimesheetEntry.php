<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class TimesheetEntry extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'timesheet_id', 'work_date', 'project_name', 'task',
        'hours', 'billable', 'billable_amount', 'rate', 'notes',
    ];

    protected $casts = [
        'work_date'       => 'date',
        'hours'           => 'decimal:2',
        'billable'        => 'boolean',
        'billable_amount' => 'decimal:2',
        'rate'            => 'decimal:2',
    ];

    public function timesheet()
    {
        return $this->belongsTo(Timesheet::class);
    }
}
