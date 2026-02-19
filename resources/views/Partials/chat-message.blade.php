@if(isset($currentDate) && $message->date_label != $currentDate)
    <div class="message-date text-center my-3">
        <span class="badge bg-light text-muted p-2 rounded-pill shadow-sm" style="font-size: 11px;">
            {{ $message->date_label }}
        </span>
    </div>
    @php $currentDate = $message->date_label; @endphp
@endif

<div class="message-wrapper mb-3 d-flex {{ $message->isFromCurrentUser() ? 'justify-content-end' : 'justify-content-start' }}">
    <div class="message-bubble {{ $message->isFromCurrentUser() ? 'sent' : 'received' }}" 
         style="max-width: 75%; padding: 10px 15px; border-radius: 15px; {{ $message->isFromCurrentUser() ? 'background: #664dc9; color: #fff; border-bottom-right-radius: 2px;' : 'background: #e9ecef; color: #333; border-bottom-left-radius: 2px;' }}">
        
        <div class="message-text">
            {{ $message->content }} {{-- Changed from $message->message to $message->content --}}
            
            @if($message->type_emoji)
                <span class="ms-1">{{ $message->type_emoji }}</span>
            @endif
        </div>

        <div class="message-time mt-1 d-flex align-items-center {{ $message->isFromCurrentUser() ? 'justify-content-end' : 'justify-content-start' }}" style="font-size: 10px; opacity: 0.8;">
            {{ $message->time }}
            
            @if($message->isFromCurrentUser())
                <span class="ms-1">
                    @if($message->is_read)
                        <i class="fas fa-check-double text-success"></i>
                    @else
                        <i class="fas fa-check"></i>
                    @endif
                </span>
            @endif
        </div>
    </div>
</div>