<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Plan extends Model
{
    use HasFactory;

    protected $table = 'plans';

    protected $fillable = [
        'name',
        'price',
        'billing_cycle',
        'description',
        'features',
        'recommended',
        'icon',
        'status',
        'is_active',
        'expiry_date',
    ];

    protected $casts = [
        'price'       => 'decimal:2',
        'is_active'   => 'boolean',
        'expiry_date' => 'datetime',
    ];

    public function subscriptions()
    {
        return $this->hasMany(Domain::class, 'package_name', 'name');
    }
}