<?php

namespace App\Http\Controllers;

use App\Models\Chat;
use Illuminate\Http\Request;
use Illuminate\Notifications\DatabaseNotification;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schema;

class NotificationController extends Controller
{
    public function index()
    {
        $user = Auth::user();

        $notifications = $user
            ? $user->notifications()->latest()->paginate(20)
            : DatabaseNotification::query()->whereRaw('1 = 0')->paginate(20);

        return view('notifications', compact('notifications'));
    }

    public function summary(Request $request)
    {
        $user = Auth::user();

        if (!$user) {
            return response()->json([
                'notification_count' => 0,
                'chat_count' => 0,
                'mail_count' => 0,
                'total_attention_count' => 0,
            ], 401);
        }

        $notificationCount = 0;
        $chatCount = 0;
        $mailCount = 0;

        if (Schema::hasTable('notifications')) {
            $notificationCount = (int) $user->unreadNotifications()->count();
        }

        if (Schema::hasTable('chats')) {
            $chatCount = (int) Chat::query()
                ->where('receiver_id', $user->id)
                ->where(function ($query) {
                    $query->whereNull('meta')
                        ->orWhere('meta->read', '!=', true)
                        ->orWhere('meta->read', '!=', 'true');
                })
                ->count();
        }

        if (Schema::hasTable('messages')) {
            $mailCount = (int) $user->unreadCount();
        }

        return response()->json([
            'notification_count' => $notificationCount,
            'chat_count' => $chatCount,
            'mail_count' => $mailCount,
            'total_attention_count' => $notificationCount + $chatCount + $mailCount,
        ]);
    }

    public function markAsRead(Request $request, string $id)
    {
        $notification = $this->findUserNotification($id);
        $notification?->markAsRead();
        $this->flushHeaderNotificationCache();

        return $this->notificationResponse($request, 'Notification marked as read.');
    }

    public function markAllAsRead(Request $request)
    {
        $user = Auth::user();
        if ($user) {
            $user->unreadNotifications->markAsRead();
        }

        $this->flushHeaderNotificationCache();

        return $this->notificationResponse($request, 'All notifications marked as read.');
    }

    public function destroy(Request $request, string $id)
    {
        $notification = $this->findUserNotification($id);
        if ($notification) {
            $notification->delete();
        }

        $this->flushHeaderNotificationCache();

        return $this->notificationResponse($request, 'Notification deleted.');
    }

    private function findUserNotification(string $id): ?DatabaseNotification
    {
        $user = Auth::user();

        if (!$user) {
            return null;
        }

        return $user->notifications()->whereKey($id)->first();
    }

    private function flushHeaderNotificationCache(): void
    {
        if (Auth::check()) {
            Cache::forget('ui:header:notifications:' . Auth::id());
            Cache::forget('ui:header:notifications:count:' . Auth::id());
        }
    }

    private function notificationResponse(Request $request, string $message)
    {
        if ($request->expectsJson()) {
            return response()->json(['ok' => true, 'message' => $message]);
        }

        return back()->with('success', $message);
    }
}
