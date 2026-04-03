<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Traits\TenantScoped;

class Bank extends Model
{
    use HasFactory, TenantScoped;

    // Add fillable or guarded attributes
    protected $fillable = [
        'name', 
        'company_id',
        'user_id',
        'branch_id',
        'branch_name',
        'branch', 
        'account_number', 
        'balance', 
        'account_holder_name',
        'ifsc_code',
        'swift_code',
    ];

    // Define relationships or custom methods here
}
