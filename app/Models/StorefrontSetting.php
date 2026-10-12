<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StorefrontSetting extends Model
{
    protected $fillable = [
        'company_id', 'slug', 'custom_domain', 'store_name', 'description',
        'contact_phone', 'contact_email', 'fulfilment_note', 'is_enabled',
    ];

    protected $casts = ['is_enabled' => 'boolean'];

    public function company()
    {
        return $this->belongsTo(Company::class);
    }
}
