<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class Message extends Model
{
    use HasFactory, SoftDeletes; // Added SoftDeletes for the "Trash" folder

    protected $fillable = [
        'user_id',      // Sender
        'receiver_id',  // Receiver
        'sender_id',
        'chat_id',      // Added to fix the General Error 1364
        'subject',      // Added for Inbox display
        'content',
        'read_at',      // Added to track unread status
    ];

    protected $casts = [
        'read_at' => 'datetime',
    ];

    /**
     * Boot function to handle model events.
     * This automatically assigns a chat_id if one isn't provided.
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($message) {
            if (empty($message->chat_id)) {
                // Generates a unique ID like "chat_65a7f..."
                $message->chat_id = 'chat_' . uniqid();
            }
        });
    }

    public function sender()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function receiver()
    {
        return $this->belongsTo(User::class, 'receiver_id');
    }
}