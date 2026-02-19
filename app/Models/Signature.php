<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class Signature extends Model
{
    use HasFactory;

    protected $fillable = ['name', 'image_path', 'status'];

    /**
     * Accessor to get the full URL for the signature image.
     * Usage: $signature->image_url
     */
    public function getImageUrlAttribute()
    {
        if ($this->image_path && Storage::disk('public')->exists($this->image_path)) {
            return asset('storage/' . $this->image_path);
        }
        return asset('assets/img/signature-placeholder.png');
    }
}