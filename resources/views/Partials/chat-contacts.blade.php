@if($contacts->count() > 0)
    @foreach($contacts as $contact)
        @php
            // Ensure these methods exist in your User.php model and use 'user_id'/'content'
            $lastMessage = Auth::user()->lastMessageWith($contact->id);
            $unreadCount = Auth::user()->unreadMessagesFrom($contact->id);
        @endphp
        <div class="contact-item d-flex align-items-center {{ (isset($selectedUser) && $selectedUser->id == $contact->id) ? 'active' : '' }}"
             onclick="selectContact({{ $contact->id }})"
             style="cursor: pointer; padding: 10px; border-bottom: 1px solid #f5f5f5;"
             data-user-id="{{ $contact->id }}">
            
            <div class="contact-avatar me-3" style="position: relative;">
                <img src="{{ $contact->avatar_url }}" 
                     alt="{{ $contact->name }}" 
                     class="rounded-circle" width="45" height="45">
                <span class="user-status {{ $contact->online_status }}" 
                      style="position: absolute; bottom: 0; right: 0; width: 12px; height: 12px; border-radius: 50%; border: 2px solid #fff; {{ $contact->online_status == 'online' ? 'background: #22c55e;' : 'background: #cbd5e1;' }}"></span>
            </div>

            <div class="contact-info flex-grow-1">
                <div class="d-flex justify-content-between align-items-start">
                    <h6 class="contact-name mb-1" style="font-size: 14px; font-weight: 600;">{{ $contact->name }}</h6>
                    @if($lastMessage)
                        <small class="message-time text-muted" style="font-size: 11px;">{{ $lastMessage->created_at->format('h:i A') }}</small>
                    @endif
                </div>
                @if($lastMessage)
                    <p class="last-message mb-0 text-muted" style="font-size: 13px;">
                        @if($lastMessage->user_id == Auth::id())
                            <i class="fas fa-check {{ $lastMessage->is_read ? 'text-success' : '' }} me-1"></i>
                        @endif
                        {{ Str::limit($lastMessage->content, 30) }} {{-- FIXED: Changed 'message' to 'content' --}}
                    </p>
                @else
                    <p class="last-message mb-0 text-muted" style="font-size: 13px;">No messages yet</p>
                @endif
            </div>

            @if($unreadCount > 0)
                <div class="unread-badge ms-2 bg-primary text-white rounded-circle d-flex align-items-center justify-content-center" 
                     style="width: 20px; height: 20px; font-size: 10px;">
                    {{ $unreadCount }}
                </div>
            @endif
        </div>
    @endforeach
@else
    {{-- Empty State --}}
    <div class="text-center py-5">
        <div class="mb-3">
            <i class="fas fa-users fa-3x text-muted"></i>
        </div>
        <p class="text-muted">No contacts yet</p>
        <button class="btn btn-primary btn-sm rounded-pill" 
                data-bs-toggle="modal" 
                data-bs-target="#newChatModal">
            Start a new chat
        </button>
    </div>
@endif