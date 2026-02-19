<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Activity extends Model
{
    use HasFactory;

    // Allow these fields to be filled by the database
    protected $fillable = [
        'sale_id',
        'user_id',
        'description'
    ];


    public function activities()
    {
        return $this->hasMany(Activity::class)->latest();
    }
    
    /**
     * It is also helpful to have the user who owns the sale
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Link activity back to the Sale
     */
    public function sale()
    {
        return $this->belongsTo(Sale::class);
    }
}