<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LandingSetting extends Model
{
    protected $table = 'landing_settings';
    protected $fillable = ['hero_title', 'hero_description', 'contact_email'];
}