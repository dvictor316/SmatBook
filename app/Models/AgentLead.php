<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class AgentLead extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'agent_id',
        'state_manager_id',
        'company_id',
        'first_name',
        'last_name',
        'business_name',
        'business_category',
        'phone',
        'email',
        'address',
        'status',
        'source',
        'lead_type',
        'priority',
        'notes',
        'latitude',
        'longitude',
        'next_follow_up_at',
        'converted_at',
        'invoice_count',
        'last_activity_at',
    ];

    protected $casts = [
        'latitude' => 'decimal:7',
        'longitude' => 'decimal:7',
        'next_follow_up_at' => 'datetime',
        'converted_at' => 'datetime',
        'last_activity_at' => 'datetime',
    ];

    public function agent(): BelongsTo
    {
        return $this->belongsTo(User::class, 'agent_id');
    }

    public function stateManager(): BelongsTo
    {
        return $this->belongsTo(User::class, 'state_manager_id');
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function activities(): HasMany
    {
        return $this->hasMany(AgentActivity::class);
    }
}
