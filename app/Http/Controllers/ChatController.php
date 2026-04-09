<?php

namespace App\Http\Controllers;

use App\Models\Chat; 
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class ChatController extends Controller
{
    public function index($userId = null)
    {
        $currentUser = Auth::user();
        $allUsers = User::where('id', '!=', $currentUser->id)->orderBy('name')->get();
        $contactIds = $this->contactIdsForUser($currentUser->id);
        $contacts = User::whereIn('id', $contactIds)->orderBy('name')->get();

        $selectedUser = $userId ? User::find($userId) : null;
        $messages = collect();

        if ($selectedUser) {
            $messages = $this->threadMessages($currentUser->id, $selectedUser->id);
            $this->markSelectedChatAsRead($selectedUser->id);
        }

        return view('Applications.Chat.chat', [
            'contacts'     => $contacts,
            'selectedUser' => $selectedUser,
            'messages'     => $messages,
            'allUsers'     => $allUsers,
            'userId'       => $userId,
            'totalUnread'  => $this->unreadCountForUser($currentUser->id),
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'receiver_id' => 'required|exists:users,id',
            'message' => 'required|string|max:2000',
        ]);

        $this->insertChatMessage((int) auth()->id(), (int) $request->receiver_id, (string) $request->message);

        return redirect()->route('chat.index', $request->receiver_id)
            ->with('success', 'Message sent successfully.');
    }

    public function send(Request $request)
    {
        return $this->store($request);
    }

    public function getContacts()
    {
        $currentUserId = (int) Auth::id();
        $contacts = User::whereIn('id', $this->contactIdsForUser($currentUserId))
            ->orderBy('name')
            ->get()
            ->map(function (User $user) use ($currentUserId) {
                return [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'avatar_url' => $user->avatar_url,
                    'unread_count' => $this->unreadCountFromSender($currentUserId, $user->id),
                ];
            })
            ->values();

        return response()->json(['contacts' => $contacts]);
    }

    public function getMessages($userId)
    {
        $currentUserId = (int) Auth::id();
        User::findOrFail($userId);
        $this->markSelectedChatAsRead((int) $userId);

        return response()->json([
            'messages' => $this->threadMessages($currentUserId, (int) $userId)->values(),
            'unread' => $this->unreadCountForUser($currentUserId),
        ]);
    }

    public function getUnreadCount()
    {
        return response()->json([
            'count' => $this->unreadCountForUser((int) Auth::id()),
        ]);
    }

    public function markAsRead(Request $request)
    {
        $senderId = (int) ($request->input('user_id') ?? $request->input('sender_id') ?? 0);
        if ($senderId > 0) {
            $this->markSelectedChatAsRead($senderId);
        }

        return response()->json(['ok' => true]);
    }

    public function updateLastSeen()
    {
        return response()->json(['ok' => true]);
    }

    public function searchUsers(Request $request)
    {
        $search = trim((string) $request->input('q', ''));

        $users = User::query()
            ->where('id', '!=', Auth::id())
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($sub) use ($search) {
                    $sub->where('name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%");
                });
            })
            ->orderBy('name')
            ->limit(20)
            ->get(['id', 'name', 'email']);

        return response()->json(['users' => $users]);
    }

    public function deleteMessage($id)
    {
        $senderColumn = $this->senderColumn();
        $query = DB::table('chats')
            ->where('id', $id)
            ->where(function ($sub) use ($senderColumn) {
                $sub->where($senderColumn, Auth::id())
                    ->orWhere('receiver_id', Auth::id());
            });

        $this->applyChatTypeConstraint($query);
        $deleted = $query->delete();

        return response()->json(['ok' => $deleted > 0]);
    }

    private function markSelectedChatAsRead($senderId)
    {
        if (!Schema::hasTable('chats') || !Schema::hasColumn('chats', 'meta')) {
            return;
        }

        $senderColumn = $this->senderColumn();
        $query = DB::table('chats')
            ->where($senderColumn, $senderId)
            ->where('receiver_id', Auth::id());

        $this->applyChatTypeConstraint($query);

        $query->update([
            'meta->read' => true,
            'meta->read_at' => now()->toDateTimeString()
        ]);
    }

    private function contactIdsForUser(int $currentUserId)
    {
        if (!Schema::hasTable('chats')) {
            return collect();
        }

        $senderColumn = $this->senderColumn();
        $query = DB::table('chats')
            ->where(function ($builder) use ($senderColumn, $currentUserId) {
                $builder->where($senderColumn, $currentUserId)
                    ->orWhere('receiver_id', $currentUserId);
            });

        $this->applyChatTypeConstraint($query);

        return $query
            ->selectRaw("CASE WHEN {$senderColumn} = ? THEN receiver_id ELSE {$senderColumn} END as contact_id", [$currentUserId])
            ->distinct()
            ->pluck('contact_id')
            ->filter();
    }

    private function threadMessages(int $currentUserId, int $targetUserId)
    {
        if (!Schema::hasTable('chats')) {
            return collect();
        }

        $senderColumn = $this->senderColumn();
        $contentColumn = $this->contentColumn();
        $query = DB::table('chats')
            ->where(function ($builder) use ($senderColumn, $currentUserId, $targetUserId) {
                $builder->where(function ($sub) use ($senderColumn, $currentUserId, $targetUserId) {
                    $sub->where($senderColumn, $currentUserId)
                        ->where('receiver_id', $targetUserId);
                })->orWhere(function ($sub) use ($senderColumn, $currentUserId, $targetUserId) {
                    $sub->where($senderColumn, $targetUserId)
                        ->where('receiver_id', $currentUserId);
                });
            });

        $this->applyChatTypeConstraint($query);

        return $query
            ->orderBy('created_at', 'asc')
            ->get([
                'id',
                DB::raw("{$senderColumn} as user_id"),
                'receiver_id',
                DB::raw("{$contentColumn} as content"),
                'meta',
                'created_at',
                'updated_at',
            ]);
    }

    private function unreadCountForUser(int $userId): int
    {
        if (!Schema::hasTable('chats')) {
            return 0;
        }

        $query = DB::table('chats')->where('receiver_id', $userId);
        $this->applyChatTypeConstraint($query);

        if (Schema::hasColumn('chats', 'meta')) {
            $query->where(function ($builder) {
                $builder->whereNull('meta')
                    ->orWhere('meta->read', '!=', true)
                    ->orWhere('meta->read', '!=', 'true');
            });
        }

        return (int) $query->count();
    }

    private function unreadCountFromSender(int $currentUserId, int $senderId): int
    {
        if (!Schema::hasTable('chats')) {
            return 0;
        }

        $senderColumn = $this->senderColumn();
        $query = DB::table('chats')
            ->where($senderColumn, $senderId)
            ->where('receiver_id', $currentUserId);

        $this->applyChatTypeConstraint($query);

        if (Schema::hasColumn('chats', 'meta')) {
            $query->where(function ($builder) {
                $builder->whereNull('meta')
                    ->orWhere('meta->read', '!=', true)
                    ->orWhere('meta->read', '!=', 'true');
            });
        }

        return (int) $query->count();
    }

    private function insertChatMessage(int $senderId, int $receiverId, string $message): void
    {
        if (!Schema::hasTable('chats')) {
            return;
        }

        $payload = [
            $this->senderColumn() => $senderId,
            'receiver_id' => $receiverId,
            $this->contentColumn() => $message,
            'created_at' => now(),
            'updated_at' => now(),
        ];

        if (Schema::hasColumn('chats', 'type')) {
            $payload['type'] = 'chat';
        }

        if (Schema::hasColumn('chats', 'sender_name')) {
            $payload['sender_name'] = (string) (Auth::user()?->name ?? 'User');
        }

        if (Schema::hasColumn('chats', 'meta')) {
            $payload['meta'] = json_encode([
                'read' => false,
                'read_at' => null,
            ]);
        }

        DB::table('chats')->insert($payload);
    }

    private function senderColumn(): string
    {
        return Schema::hasColumn('chats', 'user_id') ? 'user_id' : 'sender_id';
    }

    private function contentColumn(): string
    {
        return Schema::hasColumn('chats', 'content') ? 'content' : 'message';
    }

    private function applyChatTypeConstraint($query): void
    {
        if (Schema::hasColumn('chats', 'type')) {
            $query->where('type', 'chat');
        }
    }
}
