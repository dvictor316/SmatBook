<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Department extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'company_id', 'branch_id', 'name', 'code', 'parent_id',
        'head_employee_id', 'is_active', 'description',
    ];

    protected $casts = ['is_active' => 'boolean'];

    public function company(): BelongsTo     { return $this->belongsTo(Company::class); }
    public function parent(): BelongsTo      { return $this->belongsTo(Department::class, 'parent_id'); }
    public function children(): HasMany      { return $this->hasMany(Department::class, 'parent_id'); }
    public function head(): BelongsTo        { return $this->belongsTo(Employee::class, 'head_employee_id'); }
    public function employees(): HasMany     { return $this->hasMany(Employee::class, 'department', 'name'); }
    public function costCenters(): HasMany   { return $this->hasMany(CostCenter::class); }

    public function scopeForCompany($query, int $companyId) { return $query->where('company_id', $companyId); }
    public function scopeActive($query)     { return $query->where('is_active', true); }
}
