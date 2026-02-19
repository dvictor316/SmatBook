<?php

namespace App\Livewire;

use Livewire\Component;
use App\Models\Message;
use App\Models\Chat;

class ChatComponent extends Component
{
    public $chat; // Chat model instance
    public $messages = [];
    public $newMessageText = '';
    public $chatId; // Current chat ID
    public $currentUserId; // Logged-in user ID

    // Initialize component with chat ID
    public function mount($chatId)
    {
        $this->chatId = $chatId;
        $this->currentUserId = auth()->id();

        // Load chat info
        $this->chat = Chat::findOrFail($chatId);

        // Load existing messages
        $this->loadMessages();
    }

    // Load messages for the chat
    public function loadMessages()
    {
        $this->messages = Message::where('chat_id', $this->chatId)
            ->orderBy('created_at', 'asc')
            ->get();

        // Emit event for front-end to scroll
        $this->emit('chat-updated');
    }

    // Public method for polling to refresh messages
    public function refreshMessages()
    {
        $this->loadMessages();
    }

    // Send a new message
    public function sendMessage()
    {
        $trimmedMessage = trim($this->newMessageText);
        if (empty($trimmedMessage)) {
            return; // Prevent empty messages
        }

        Message::create([
            'chat_id' => $this->chatId,
            'sender_id' => $this->currentUserId,
            'content' => $this->newMessageText,
        ]);

        // Reload messages to include the new one
        $this->loadMessages();

        // Clear input
        $this->newMessageText = '';
    }

    // Delete a message (if permissions allow)
    public function deleteMessage($messageId)
    {
        Message::where('id', $messageId)
            ->where('sender_id', $this->currentUserId)
            ->delete();

        $this->loadMessages();
    }

    public function render()
    {
        return view('livewire.chat', [
            'messages' => $this->messages,
            'chat' => $this->chat,
        ]);
    }
}