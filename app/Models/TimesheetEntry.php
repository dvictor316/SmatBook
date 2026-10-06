<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TimesheetEntry extends Model
{
    protected $fillable = [
        'timesheet_id', 'entry_date', 'project_id', 'task_id',
        'activity_description', 'hours', 'is_billable', 'hourly_rate', 'line_total',
    ];

    protected $casts = [
        'entry_date' => 'date',
        'hours' => 'decimal:2',
        'hourly_rate' => 'decimal:4',
        'line_total' => 'decimal:2',
        'is_billable' => 'boolean',
    ];

    public function timesheet(): BelongsTo
    {
        return $this->belongsTo(Timesheet::class);
    }
}
