<?php

use Illuminate\Support\Facades\Broadcast;

Broadcast::channel('presence-chat', function ($user) {
    if (auth()->check()) {
        // 1. Determine the photo path
        // We check if profile_photo exists in the DB
        $photo = $user->profile_photo;

        return [
            'id' => $user->id,
            'name' => $user->name,
            'avatar' => $photo 
                ? asset('storage/' . $photo) 
                : "https://ui-avatars.com/api/?name=" . urlencode($user->name) . "&color=7F9CF5&background=EBF4FF"
        ];
    }
});
