<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Vendor extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'vendors';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'email',
        'phone',
        'address',
        'balance'
    ];

    /**
     * The attributes that should be cast to native types.
     * 
     * You might add 'email_verified_at' casting here if you add that column later.
     *
     * @var array<string, string>
     */
    protected $casts = [
        // No specific casts needed for the fields above right now
    ];
}
