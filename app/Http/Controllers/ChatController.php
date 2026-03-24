<?php

namespace App\Http\Controllers;

use App\Models\Chat; 
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ChatController extends Controller
{
    public function index($userId = null)
    {
        $currentUser = Auth::user();
        $allUsers = User::where('id', '!=', $currentUser->id)->orderBy('name')->get();

        // 1. Get unique contact IDs (FILTER BY CHAT TYPE)
        $contactIds = Chat::where(function($q) use ($currentUser) {
                $q->where('user_id', $currentUser->id)
                  ->orWhere('receiver_id', $currentUser->id);
            })
            ->where('type', 'chat') // Only show people you've chatted with, not emailed
            ->selectRaw("CASE WHEN user_id = ? THEN receiver_id ELSE user_id END as contact_id", [$currentUser->id])
            ->distinct()
            ->pluck('contact_id')
            ->filter();

        $contacts = User::whereIn('id', $contactIds)->get();

        $selectedUser = $userId ? User::find($userId) : null;
        $messages = collect();

        if ($selectedUser) {
            // 2. FETCH HISTORY (FILTER BY CHAT TYPE)
            $messages = Chat::where('type', 'chat') // Ensure emails don't show up here
                ->where(function($q) use ($currentUser, $selectedUser) {
                    $q->where(function($sub) use ($currentUser, $selectedUser) {
                        $sub->where('user_id', $currentUser->id)
                            ->where('receiver_id', $selectedUser->id);
                    })->orWhere(function($sub) use ($currentUser, $selectedUser) {
                        $sub->where('user_id', $selectedUser->id)
                            ->where('receiver_id', $currentUser->id);
                    });
                })
                ->orderBy('created_at', 'asc')
                ->get();

            $this->markSelectedChatAsRead($selectedUser->id);
        }

        return view('Applications.Chat.chat', [
            'contacts'     => $contacts,
            'selectedUser' => $selectedUser,
            'messages'     => $messages,
            'allUsers'     => $allUsers,
            'userId'       => $userId
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'receiver_id' => 'required|exists:users,id',
            'message' => 'required|string|max:2000',
        ]);

        // 3. TAG AS CHAT TYPE
        Chat::create([
            'user_id'     => auth()->id(),
            'receiver_id' => $request->receiver_id,
            'content'     => $request->message, 
            'type'        => 'chat', // Distinguishes it from 'email'
        ]);

        return redirect()->route('chat.index', $request->receiver_id)
            ->with('success', 'Message sent successfully.');
    }

    private function markSelectedChatAsRead($senderId)
    {
        Chat::where('type', 'chat')
            ->where('user_id', $senderId)
            ->where('receiver_id', Auth::id())
            ->update([
                'meta->read' => true,
                'meta->read_at' => now()->toDateTimeString()
            ]);
    }
}
