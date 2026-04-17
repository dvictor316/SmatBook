<div 
    class="flex flex-col h-full bg-white rounded-xl shadow-2xl overflow-hidden" 
    x-data="{
        init() {
            this.scrollToBottom();
            Livewire.on('chat-updated', () => {
                this.scrollToBottom();
            });
        },
        scrollToBottom() {
            const chatScroll = this.$refs.chatScroll;
            if (chatScroll) {
                chatScroll.scrollTop = chatScroll.scrollHeight;
            }
        }
    }" 
    x-init="init()"
>
    
    <div class="p-4 border-b bg-gray-100 text-lg font-semibold text-gray-700 flex justify-between items-center">
        <span>Live Chat (Chat ID: {{ $chat->id ?? 'N/A' }})</span>
        
        <button wire:click="loadMessages" class="text-sm p-1 bg-gray-200 rounded hover:bg-gray-300">Refresh</button>
    </div>

    
    <div 
        class="flex-grow p-6 overflow-y-auto space-y-4" 
        x-ref="chatScroll"
        wire:poll.3s
    >
        <ul class="list-none p-0 m-0">
            @php
                $currentUserId = Auth::id();
            @endphp
            @foreach ($messages as $message)
                @if (isset($message->is_date_divider))
                    <li class="text-center text-sm text-gray-400 my-4">{{ $message->date_text }}</li>
                @elseif (isset($message->is_typing_indicator))

                @else
                    @php
                        $isSent = ($message->sender_id ?? null) == $currentUserId;
                        $alignmentClass = $isSent ? 'justify-end' : 'justify-start';
                        $bubbleClasses = $isSent 
                            ? 'bg-blue-600 text-white rounded-tr-none' 
                            : 'bg-gray-100 text-gray-800 rounded-tl-none';
                    @endphp

                    <li class="flex {{ $alignmentClass }} mb-4">
                        @if (!$isSent)
                            <div class="w-10 h-10 flex-shrink-0 mr-3">
                                <img src="{{ $message->sender->avatar_url ?? 'https://placehold.co/40x40' }}" alt="User Avatar" class="w-full h-full rounded-full object-cover">
                            </div>
                        @endif
                        
                        <div class="max-w-xs sm:max-w-md">
                            <div class="p-3 rounded-xl shadow-md {{ $bubbleClasses }} whitespace-pre-wrap">
                                @foreach (explode("\n", $message->content) as $paragraph)
                                    <p class="mb-1 last:mb-0">{{ $paragraph }}</p>
                                @endforeach

                                @php
                                    $attachments = isset($message->attachments) ? $message->attachments : [];
                                    $attachmentsCount = is_array($attachments) ? count($attachments) : 0;
                                @endphp
                                @if ($attachmentsCount > 0)
                                    <div class="grid grid-cols-2 gap-2 mt-2">
                                        @foreach ($attachments as $attachment)
                                            <div class="border border-gray-300 p-2 rounded-lg bg-white/20 text-center">
                                                <img src="{{ $attachment->url }}" alt="Attachment" class="w-full h-auto rounded mb-1" />
                                                <div class="text-xs truncate">{{ $attachment->name }}</div>
                                                <a href="{{ $attachment->url }}" class="block text-xs text-blue-200 hover:text-blue-100 mt-1" target="_blank" rel="noopener noreferrer">Download</a>
                                            </div>
                                        @endforeach
                                    </div>
                                @endif

                                <div class="flex items-center mt-1 text-xs opacity-80 {{ $isSent ? 'justify-end text-blue-200' : 'justify-start text-gray-500' }}">
                                    <span>{{ \Illuminate\Support\Carbon::parse($message->created_at)->format('h:i A') }}</span>
                                    @if ($isSent)
                                        <div x-data="{ open: false }" @click.away="open = false" class="relative ml-2 cursor-pointer">
                                            <button @click="open = !open" class="w-4 h-4 hover:text-white focus:outline-none">
                                                <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg">
                                                    <path d="M6 10a2 2 0 11-4 0 2 2 0 014 0zm6 0a2 2 0 11-4 0 2 2 0 014 0zm6 0a2 2 0 11-4 0 2 2 0 014 0z"></path>
                                                </svg>
                                            </button>
                                            <div x-show="open" x-cloak class="absolute right-0 bottom-full mb-1 w-32 bg-white rounded-lg shadow-xl z-10 text-gray-800 overflow-hidden">
                                                <a wire:click.prevent="deleteMessage({{ $message->id }})" class="block px-3 py-2 text-sm hover:bg-gray-100 cursor-pointer">Delete</a>
                                            </div>
                                        </div>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </li>
                @endif
            @endforeach
        </ul>
    </div>

    
    <div class="p-4 border-t bg-white">
        <form wire:submit.prevent="sendMessage" class="flex items-center space-x-3">
            <input 
                type="text" 
                wire:model.defer="newMessageText" 
                placeholder="Type your message..."
                class="flex-grow p-3 border border-gray-300 rounded-full focus:outline-none focus:ring-2 focus:ring-blue-500"
            />
            <button 
                type="submit" 
                class="p-3 bg-blue-600 text-white rounded-full hover:bg-blue-700 disabled:opacity-50 transition duration-150 shadow-lg"
                @if (empty($newMessageText)) disabled @endif
            >
                Send
            </button>
        </form>
    </div>
</div>