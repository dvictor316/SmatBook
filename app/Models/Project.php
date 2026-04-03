<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use App\Models\Traits\TenantScoped;

class Project extends Model
{
    use HasFactory, TenantScoped;

    protected $fillable = [
        'company_id',
        'created_by',
        'name',
        'client_name',
        'status',
        'priority',
        'start_date',
        'due_date',
        'budget',
        'spent',
        'progress',
        'notes',
    ];

    protected $casts = [
        'start_date' => 'date',
        'due_date' => 'date',
        'budget' => 'float',
        'spent' => 'float',
        'progress' => 'integer',
    ];

    public function tasks(): HasMany
    {
        return $this->hasMany(ProjectTask::class);
    }
}
