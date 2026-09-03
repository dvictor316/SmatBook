<?php

namespace App\Models;

use App\Models\Traits\TenantScoped;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class HotelRoomImage extends Model
{
    use HasFactory, TenantScoped;

    protected $fillable = [
        'company_id',
        'property_id',
        'room_id',
        'path',
        'caption',
        'is_cover',
        'is_panorama',
        'sort_order',
        'uploaded_by',
    ];

    protected $casts = [
        'is_cover' => 'boolean',
        'is_panorama' => 'boolean',
        'sort_order' => 'integer',
    ];

    public function room()
    {
        return $this->belongsTo(HotelRoom::class, 'room_id');
    }
}
