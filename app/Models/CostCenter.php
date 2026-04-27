<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CostCenter extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'company_id', 'branch_id', 'name', 'code', 'type',
        'department_id', 'is_active', 'description', 'created_by',
    ];

    protected $casts = ['is_active' => 'boolean'];

    public function company(): BelongsTo    { return $this->belongsTo(Company::class); }
    public function department(): BelongsTo { return $this->belongsTo(Department::class); }

    public function scopeForCompany($query, int $companyId) { return $query->where('company_id', $companyId); }
    public function scopeActive($query)    { return $query->where('is_active', true); }
}
