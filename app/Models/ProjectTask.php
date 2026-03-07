<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProjectTask extends Model
{
    use HasFactory;

    protected $fillable = [
        'project_id',
        'title',
        'assignee',
        'status',
        'due_date',
        'estimated_hours',
        'actual_hours',
        'completed_at',
    ];

    protected $casts = [
        'due_date' => 'date',
        'estimated_hours' => 'float',
        'actual_hours' => 'float',
        'completed_at' => 'datetime',
    ];

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }
}
