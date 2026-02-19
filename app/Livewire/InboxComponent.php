<?php

namespace App\Livewire;

use Livewire\Component;
use App\Models\Message;
use Illuminate\Support\Facades\Auth;
use Livewire\WithPagination;

class InboxComponent extends Component
{
    use WithPagination;

    public $folder = 'inbox';

    // This allows the component to react when you click "Sent" or "Trash" in your main view
    protected $listeners = ['setFolder'];

    public function setFolder($name) {
        $this->folder = $name;
        $this->resetPage(); 
    }

    public function render()
    {
        // 1. Filter by 'email' type as seen in your DB (type='email')
        $query = Message::where('type', 'email');

        if ($this->folder === 'sent') {
            // 2. Your DB uses 'sender_id' for the person who sent it
            $query->where('sender_id', Auth::id());
        } elseif ($this->folder === 'trash') {
            // 3. Show soft-deleted messages
            $query->onlyTrashed()->where(function($q) {
                $q->where('sender_id', Auth::id())
                  ->orWhere('receiver_id', Auth::id());
            });
        } else {
            // 4. Inbox: Messages sent TO you that are NOT deleted
            $query->where('receiver_id', Auth::id());
        }

        return view('livewire.inbox-component', [
            'messages' => $query->with(['sender', 'receiver'])->latest()->paginate(10)
        ]);
    }

    // Add this method inside your InboxComponent class
public function deleteMessage($id)
{
    $message = Message::withTrashed()->findOrFail($id);

    if ($message->trashed()) {
        // If it's already in trash, delete it forever
        $message->forceDelete();
        $this->dispatch('alert', ['type' => 'success', 'message' => 'Message permanently deleted.']);
    } else {
        // Otherwise, move to trash (soft delete)
        $message->delete();
        $this->dispatch('alert', ['type' => 'warning', 'message' => 'Message moved to trash.']);
    }

    // Refresh the view
    $this->resetPage();
}
}