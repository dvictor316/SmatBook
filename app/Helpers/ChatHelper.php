<?php
// app/Helpers/ChatHelper.php

namespace App\Helpers;

use App\Models\Chat;
use App\Models\User;

class ChatHelper
{
    public static function getTotalUnreadMessages($userId)
    {
        return Chat::where('receiver_id', $userId)
            ->where(function($query) {
                $query->whereNull('meta')
                      ->orWhereRaw("JSON_UNQUOTE(JSON_EXTRACT(meta, '$.read')) != 'true'");
            })
            ->count();
    }
    
    public static function getContacts($userId)
    {
        $contactIds = Chat::where('sender_id', $userId)
            ->orWhere('receiver_id', $userId)
            ->selectRaw('CASE 
                WHEN sender_id = ? THEN receiver_id 
                WHEN receiver_id = ? THEN sender_id 
                END as contact_id', [$userId, $userId])
            ->distinct()
            ->pluck('contact_id')
            ->filter()
            ->unique()
            ->toArray();
            
        return User::whereIn('id', $contactIds)->get();
    }

    private function getCountryHeatMap($user) {
    // Only query this if the user is Enterprise or Custom
    $plan = strtolower($user->subscription->plan_name ?? '');
    if (!in_array($plan, ['enterprise', 'custom']) && $user->email !== 'donvictorlive@gmail.com') {
        return []; // Return empty array to save server resources and prevent cheating
    }

    return Company::selectRaw('country, COUNT(*) as count')
        ->groupBy('country')
        ->pluck('count', 'country')
        ->toArray();
}
}