@extends('layout.mainlayout')

@section('content')
<style>
    :root {
        --chat-primary: #7e3af2;
        --chat-bg: #f9fafb;
        --online-color: #059669;
        --glass-bg: rgba(255, 255, 255, 0.95);
    }

    .chat-wrapper {
        background: var(--chat-bg);
        height: calc(100vh - 180px);
        min-height: 600px;
        border-radius: 20px;
        overflow: hidden;
        box-shadow: 0 15px 35px rgba(0,0,0,0.1);
        display: flex;
        border: 1px solid rgba(255,255,255,0.3);
    }

    /* Sidebar Styling */
    .chat-sidebar {
        width: 350px;
        background: #fff;
        border-right: 1px solid #edf2f7;
        display: flex;
        flex-direction: column;
    }

    /* Online "Stories" Bar */
    .online-status-wrapper {
        padding: 20px 15px;
        background: #fff;
        border-bottom: 1px solid #f3f4f6;
    }

    #onlineUsersBar {
        display: flex;
        gap: 15px;
        overflow-x: auto;
        padding-bottom: 10px;
        scrollbar-width: none;
    }
    #onlineUsersBar::-webkit-scrollbar { display: none; }

    .online-user-card {
        display: flex;
        flex-direction: column;
        align-items: center;
        text-decoration: none !important;
        min-width: 65px;
        transition: transform 0.2s;
    }
    .online-user-card:hover { transform: translateY(-3px); }

    .avatar-ring {
        padding: 2px;
        border: 2px solid var(--online-color);
        border-radius: 50%;
        margin-bottom: 5px;
    }

    /* Contact List */
    .contact-item {
        padding: 15px;
        display: flex;
        align-items: center;
        border-bottom: 1px solid #f8fafc;
        transition: all 0.2s;
        text-decoration: none !important;
        color: inherit;
    }
    .contact-item:hover { background: #f1f5f9; }
    .contact-item.active { 
        background: #f5f3ff; 
        border-left: 4px solid var(--chat-primary);
    }

    /* Messages Area */
    .chat-main {
        flex: 1;
        display: flex;
        flex-direction: column;
        background: #fff;
        position: relative;
    }

    .messages-container {
        flex: 1;
        padding: 25px;
        overflow-y: auto;
        background-color: #f8fafc;
        background-image: radial-gradient(#e2e8f0 0.5px, transparent 0.5px);
        background-size: 20px 20px;
    }

    .message-bubble {
        max-width: 75%;
        padding: 12px 18px;
        border-radius: 18px;
        margin-bottom: 15px;
        font-size: 14px;
        line-height: 1.5;
        box-shadow: 0 2px 5px rgba(0,0,0,0.02);
    }

    .message-wrapper.sent { display: flex; justify-content: flex-end; }
    .message-wrapper.sent .message-bubble {
        background: var(--chat-primary);
        color: #fff;
        border-bottom-right-radius: 2px;
    }

    .message-wrapper.received { display: flex; justify-content: flex-start; }
    .message-wrapper.received .message-bubble {
        background: #fff;
        color: #334155;
        border-bottom-left-radius: 2px;
        border: 1px solid #e2e8f0;
    }

    /* Modal Styling */
    .user-select-item:hover {
        background-color: #f8fafc;
        border-radius: 10px;
    }
</style>

<div class="page-wrapper">
    <div class="content container-fluid">

        <div class="page-header">
            <div class="row align-items-center">
                <div class="col">
                    <h3 class="page-title">💬 Messages</h3>
                </div>
                <div class="col-auto">
                    <button class="btn btn-outline-secondary btn-print me-2 shadow-sm">
                        <i class="fas fa-print me-1"></i> Print
                    </button>
                    <span class="badge bg-soft-primary text-primary rounded-pill px-3">
                        <span id="unreadCounter">{{ $totalUnread ?? 0 }}</span> Unread
                    </span>
                </div>
            </div>
        </div>

        <div class="chat-wrapper">
            <div class="chat-sidebar">
                <div class="p-4 border-bottom d-flex align-items-center justify-content-between">
                    <div class="d-flex align-items-center">
                        <img src="{{ Auth::user()->avatar_url ?? 'https://ui-avatars.com/api/?name='.urlencode(Auth::user()->name) }}" class="rounded-circle me-2 shadow-sm" width="42">
                        <div>
                            <h6 class="mb-0 fw-bold">{{ Auth::user()->name }}</h6>
                            <small class="text-success"><i class="fas fa-circle" style="font-size: 8px;"></i> Online</small>
                        </div>
                    </div>
                    <button class="btn btn-light rounded-circle shadow-sm" data-bs-toggle="modal" data-bs-target="#newChatModal">
                        <i class="fas fa-pen-nib text-primary"></i>
                    </button>
                </div>

                <div class="online-status-wrapper">
                    <small class="text-uppercase text-muted fw-bold mb-3 d-block" style="font-size: 10px; letter-spacing: 0.5px;">Active Friends</small>
                    <div id="onlineUsersBar">

                        @foreach($allUsers as $u)
                            @if($u->id !== Auth::id())
                                <a href="{{ route('chat.show', $u->id) }}" class="online-user-card">
                                    <div class="avatar-ring" style="border-color: {{ $u->is_online ? 'var(--online-color)' : '#e2e8f0' }}">
                                        <img src="{{ $u->avatar_url ?? 'https://ui-avatars.com/api/?name='.urlencode($u->name) }}" width="45" height="45" class="rounded-circle">
                                    </div>
                                    <small>{{ Str::before($u->name, ' ') }}</small>
                                </a>
                            @endif
                        @endforeach
                    </div>
                </div>

                <div class="contacts-list overflow-auto">
                    @forelse($contacts as $contact)
                        <a href="{{ route('chat.show', $contact->id) }}" class="contact-item {{ (isset($selectedUser) && $selectedUser->id == $contact->id) ? 'active' : '' }}">
                            <img src="{{ $contact->avatar_url ?? 'https://ui-avatars.com/api/?name='.urlencode($contact->name) }}" class="rounded-circle me-3" width="48">
                            <div class="flex-grow-1">
                                <div class="d-flex justify-content-between align-items-center">
                                    <h6 class="mb-0 fw-bold" style="font-size: 14px;">{{ $contact->name }}</h6>
                                </div>
                                <p class="mb-0 text-muted" style="font-size: 12px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; max-width: 180px;">
                                    Click to view conversation
                                </p>
                            </div>
                        </a>
                    @empty
                        <div class="p-5 text-center text-muted">
                            <i class="fas fa-comment-slash d-block mb-2 opacity-25" style="font-size: 30px;"></i>
                            <small>No recent chats</small>
                        </div>
                    @endforelse
                </div>
            </div>

            <div class="chat-main">
                @if(isset($selectedUser))
                    <div class="p-3 border-bottom d-flex align-items-center justify-content-between bg-white shadow-sm" style="z-index: 10;">
                        <div class="d-flex align-items-center">
                            <img src="{{ $selectedUser->avatar_url ?? 'https://ui-avatars.com/api/?name='.urlencode($selectedUser->name) }}" class="rounded-circle me-3" width="45">
                            <div>
                                <h6 class="mb-0 fw-bold">{{ $selectedUser->name }}</h6>
                                <small class="text-success">Active Now</small>
                            </div>
                        </div>
                    </div>

                    <div class="messages-container" id="messagesContainer">

                        @foreach($messages as $message)
                            <div class="message-wrapper {{ $message->user_id == Auth::id() ? 'sent' : 'received' }}">
                                <div class="message-bubble shadow-sm">
                                    {{ $message->content }}
                                    <span class="message-time d-block mt-1 text-end" style="font-size: 9px; opacity: 0.7;">
                                        {{ $message->created_at->format('H:i') }}
                                    </span>
                                </div>
                            </div>
                        @endforeach
                    </div>

                    <div class="p-4 bg-white border-top">
                        <form action="{{ route('chat.store') }}" method="POST">
                            @csrf
                            <input type="hidden" name="receiver_id" value="{{ $selectedUser->id }}">
                            <div class="input-group shadow-sm rounded-pill overflow-hidden border">
                                <input type="text" name="message" class="form-control border-0 px-4 py-2" placeholder="Type your message..." required autocomplete="off">
                                <button type="submit" class="btn btn-primary px-4">
                                    <i class="fas fa-paper-plane"></i>
                                </button>
                            </div>
                        </form>
                    </div>
                @else
                    <div class="h-100 d-flex flex-column align-items-center justify-content-center text-center p-5 opacity-50">
                        <i class="fas fa-paper-plane mb-4 text-primary" style="font-size: 60px;"></i>
                        <h4 class="fw-bold">Your Messages</h4>
                        <p>Select a friend from the sidebar or click the pen to start something new.</p>
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="newChatModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg" style="border-radius: 20px;">
            <div class="modal-header border-0 pb-0">
                <h5 class="modal-title fw-bold">New Conversation</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-4">
                <div class="input-group mb-4 bg-light rounded-pill px-3 py-1">
                    <span class="input-group-text bg-transparent border-0 text-muted"><i class="fas fa-search"></i></span>
                    <input type="text" id="modalUserSearch" class="form-control bg-transparent border-0" placeholder="Search friends...">
                </div>
                <div id="modalUserList" style="max-height: 350px; overflow-y: auto;">
                    @foreach($allUsers as $user)
                        @if($user->id !== Auth::id())
                            <a href="{{ route('chat.show', $user->id) }}" class="user-select-item d-flex align-items-center p-3 mb-1 text-decoration-none text-dark">
                                <img src="{{ $user->avatar_url ?? 'https://ui-avatars.com/api/?name='.urlencode($user->name) }}" class="rounded-circle me-3" width="40">
                                <div class="user-data">
                                    <h6 class="mb-0 fw-bold user-name">{{ $user->name }}</h6>
                                    <small class="text-muted">{{ $user->email }}</small>
                                </div>
                            </a>
                        @endif
                    @endforeach
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        const container = document.getElementById('messagesContainer');
        if (container) container.scrollTop = container.scrollHeight;

        const searchInput = document.getElementById('modalUserSearch');
        const userList = document.getElementById('modalUserList');

        if (searchInput) {
            searchInput.addEventListener('keyup', function() {
                const term = this.value.toLowerCase();
                const users = userList.getElementsByClassName('user-select-item');

                Array.from(users).forEach(user => {
                    const name = user.querySelector('.user-name').textContent.toLowerCase();
                    user.style.display = name.includes(term) ? 'flex' : 'none';
                });
            });
        }

        document.addEventListener('click', function(e) {
            if (e.target.classList.contains('fa-print') || e.target.closest('.btn-print')) {
                window.print();
            }
        });
    });
</script>
@endsection