<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Role extends Model
{
    use HasFactory;

    // This tells Laravel to use YOUR table, not Spatie's
    protected $table = 'roles'; 

    protected $fillable = [
        'name', 
        'description', 
        'role_group', 
        'is_system_role'
    ];

    /**
     * Relationship: A role can be assigned to many users
     */
    public function users(): HasMany
    {
        return $this->hasMany(User::class, 'role_id');
    }

    public function permissions(): BelongsToMany
    {
        return $this->belongsToMany(Permission::class, 'role_has_permissions');
    }
}
