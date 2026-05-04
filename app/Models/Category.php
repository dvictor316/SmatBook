<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Traits\TenantScoped;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Storage;

class Category extends Model
{
    use HasFactory, TenantScoped;

    protected $fillable = [
        'name',
        'type',
        'company_id',
        'user_id',
        'branch_id',
        'branch_name',
        'description',
        'image',
        'status',
    ];

    /**
     * A category has many products
     */
    public function products(): HasMany
    {
        return $this->hasMany(Product::class);
    }

    public function getImageUrlAttribute(): ?string
    {
        $path = trim((string) ($this->image ?? ''));
        if ($path === '') {
            return null;
        }

        if (Storage::disk('public')->exists(ltrim($path, '/'))) {
            return Storage::disk('public')->url(ltrim($path, '/'));
        }

        return asset(ltrim($path, '/'));
    }
}
