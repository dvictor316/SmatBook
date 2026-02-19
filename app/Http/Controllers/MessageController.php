<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Message;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class MessageController extends Controller
{
    /**
     * Display a listing of messages.
     * Rewritten to support central and tenant indexing.
     */
    public function index()
    {
        $userId = Auth::id();
        $messages = Message::where('user_id', $userId)
            ->orWhere('receiver_id', $userId)
            ->with(['sender', 'receiver'])
            ->orderBy('created_at', 'desc')
            ->get();

        return view('messages.index', compact('messages'));
    }

    /**
     * Display the specified message.
     * Resolves: Method App\Http\Controllers\MessageController::show does not exist.
     */
    public function show($id)
    {
        $message = Message::where(function($q) {
            $q->where('user_id', Auth::id())
              ->orWhere('receiver_id', Auth::id());
        })->with(['sender', 'receiver'])->findOrFail($id);

        // Mark as read if the current user is the recipient
        if ($message->receiver_id === Auth::id() && is_null($message->read_at)) {
            $message->update(['read_at' => now()]);
        }

        return view('messages.show', compact('message'));
    }

    /**
     * Store a newly created message (Chat and Email logic).
     */
    public function store(Request $request)
    {
        $request->validate([
            'receiver_id' => 'required|exists:users,id',
            'subject'     => 'required|string|max:255',
            'content'     => 'required|string',
        ]);

        $senderId = Auth::id();
        $receiverId = $request->receiver_id;

        // Find or Create a Chat ID to group these messages (Chat logic)
        $chatId = Message::where(function($q) use ($senderId, $receiverId) {
                $q->where('user_id', $senderId)->where('receiver_id', $receiverId);
            })->orWhere(function($q) use ($senderId, $receiverId) {
                $q->where('user_id', $receiverId)->where('receiver_id', $senderId);
            })->value('chat_id');

        if (!$chatId) {
            $chatId = (string) Str::uuid(); 
        }

        Message::create([
            'user_id'     => $senderId, 
            'sender_id'   => $senderId,
            'receiver_id' => $receiverId,
            'chat_id'     => $chatId,
            'subject'     => $request->subject,
            'content'     => $request->content,
            'read_at'     => null,
        ]);

        return redirect()->back()->with('success', 'Message sent successfully!');
    }

    /**
     * Move a message to trash (Soft Delete).
     */
    public function destroy($id)
    {
        $message = Message::where(function($q) {
            $q->where('user_id', Auth::id())
              ->orWhere('receiver_id', Auth::id());
        })->findOrFail($id);

        $message->delete(); 

        return redirect()->back()->with('info', 'Message moved to trash.');
    }

    /**
     * Standard Printing Script for Chat/Email pages
     * Integrated per requirement: [2025-12-30]
     */
    public function printScript()
    {
        return "
        <script>
            function printContent(el) {
                var restorepage = document.body.innerHTML;
                var printcontent = document.getElementById(el).innerHTML;
                document.body.innerHTML = printcontent;
                window.print();
                document.body.innerHTML = restorepage;
                location.reload();
            }
        </script>";
    }
}
