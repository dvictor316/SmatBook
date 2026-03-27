<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Bank extends Model
{
    use HasFactory;

    // Add fillable or guarded attributes
    protected $fillable = [
        'name', 
        'company_id',
        'user_id',
        'branch', 
        'account_number', 
        'balance', 
        'account_holder_name',
        'ifsc_code',
        'swift_code',
    ];

    // Define relationships or custom methods here
}
