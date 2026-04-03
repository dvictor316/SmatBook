<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Traits\TenantScoped;

class Supplier extends Model
{
    use HasFactory, TenantScoped;

    protected $guarded = [];
}
