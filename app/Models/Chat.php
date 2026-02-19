<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Chat extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',      // Matches Migration
        'receiver_id',  // Matches Migration
        'content',      // Matches Migration (formerly message)
        'sender_name',  // Keep this only if you added it to the migration
        'meta'
    ];

    protected $casts = [
        'meta' => 'array',
        'created_at' => 'datetime',
        'updated_at' => 'datetime'
    ];

    /**
     * Relationship with sender
     */
    public function sender()
    {
        // Changed foreign key to 'user_id'
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * Relationship with receiver
     */
    public function receiver()
    {
        return $this->belongsTo(User::class, 'receiver_id');
    }

    /**
     * Scope for messages between two users
     */
    public function scopeBetweenUsers($query, $user1, $user2)
    {
        return $query->where(function($q) use ($user1, $user2) {
            $q->where('user_id', $user1) // Changed
              ->where('receiver_id', $user2);
        })->orWhere(function($q) use ($user1, $user2) {
            $q->where('user_id', $user2) // Changed
              ->where('receiver_id', $user1);
        });
    }

    // ... Keep your Avatar and Label attributes as they are ...

    /**
     * Check if message is from current user
     */
    public function isFromCurrentUser()
    {
        return $this->user_id === auth()->id(); // Changed
    }

    /**
     * Get formatted time
     */
    public function getTimeAttribute()
    {
        return $this->created_at->format('h:i A');
    }
}