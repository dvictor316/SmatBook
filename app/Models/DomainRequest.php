<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DomainRequest extends Model
{
    use HasFactory;

    protected $table = 'domain_requests'; // Laravel expects plural form

    protected $fillable = [
        'customer_name',
        'email',
        'domain',
        'employees',
        'package_name',
        'package_type',
        'status',
    ];
}
