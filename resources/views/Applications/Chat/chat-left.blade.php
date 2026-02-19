<div class="chat-cont-left">
    <div class="chat-header">
        <span>Chats</span>
        <a href="javascript:void(0)" class="chat-compose">
            <span><i class="fe fe-plus-circle"></i></span>
        </a>
    </div>
    <form class="chat-search" wire:submit.prevent="searchUsers">
        <div class="input-group">
            <div class="input-group-prefix">
                <i class="fas fa-search"></i>
            </div>
            <input 
                type="text" 
                class="form-control" 
                placeholder="Search" 
                wire:model="searchTerm"
            >
        </div>
    </form>
    <div class="chat-users-list">
        <div class="chat-scroll">
            @if(isset($users) && $users->isNotEmpty())
                @foreach($users as $user)
                    <a 
                        href="javascript:void(0);" 
                        class="chat-block d-flex {{ $loop->first ? 'active' : '' }}"
                        wire:click="$set('selectedUserId', {{ $user->id }})"
                    >
                        <div class="media-img-wrap">
                            <div class="avatar {{ $user->status ?? 'online' }}">
                                <img 
                                    src="{{ $user->profile_photo_url ?? URL::asset('/assets/img/profiles/avatar-default.jpg') }}" 
                                    alt="User Image" 
                                    class="avatar-img rounded-circle"
                                >
                            </div>
                        </div>
                        <div class="media-body">
                            <div>
                                <div class="user-name">{{ $user->name }}</div>
                                <div class="user-last-chat">{{ $user->last_message ?? '' }}</div>
                            </div>
                            <div>
                                <div class="last-chat-time block">{{ $user->last_message_time ?? '' }}</div>
                                @if(isset($user->unread_count) && $user->unread_count > 0)
                                    <div class="badge badge-success">{{ $user->unread_count }}</div>
                                @endif
                            </div>
                        </div>
                    </a>
                @endforeach
            @else
                <p>No chats available.</p>
            @endif
        </div>
    </div>
</div>