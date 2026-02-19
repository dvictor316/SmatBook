<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Auth;
use Illuminate\Notifications\DatabaseNotification;

class LayoutController extends Controller
{
    public static function getNotifications()
    {
        try {
            if (!Auth::check()) {
                return collect(); // return empty if user not logged in
            }

            return DatabaseNotification::where('notifiable_id', Auth::id())
                ->where('notifiable_type', 'App\\Models\\User')
                ->latest()
                ->take(5)
                ->get();
        } catch (\Exception $e) {
            return collect(); // fallback to empty if notifications table missing
        }
    }
}
