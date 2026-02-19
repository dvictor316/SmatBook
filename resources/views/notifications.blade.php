<?php $page = 'notifications'; ?>
@extends('layout.mainlayout')
@section('content')
    <div class="page-wrapper notifications">
        <div class="content container-fluid">
            <div class="page-header">
                <div class="content-page-header">
                    <h5>Notifications</h5>
                </div>
                <div class="noti-action-btns d-flex align-items-center justify-content-sm-end">
                    <a href="{{ route('notifications.mark-all-read') }}" class="btn btn-white btn-mark-read">
                        <i class="fa-solid fa-check me-1"></i>Mark as read
                    </a>
                    <a href="#" class="btn btn-white btn-delete-all" data-bs-toggle="modal"
                        data-bs-target="#notification-delete">
                        <i class="fe fe-trash-2 me-1"></i>Delete all
                    </a>
                </div>
            </div>
            <div class="row">
                <div class="col-xl-12 col-md-12">
                    @forelse($notifications as $notification)
                        <div class="card user-list-item {{ $notification->read_at ? '' : 'unread-notification' }}" 
                             style="{{ $notification->read_at ? '' : 'border-left: 4px solid var(--primary);' }}">
                            <div>
                                <div class="avatar avatar-online">
                                    <a href="{{ url('profile/'.$notification->data['user_id']) }}">
                                        <img src="{{ $notification->data['user_avatar'] ?? URL::asset('/assets/img/profiles/avatar-01.jpg') }}" 
                                             class="rounded-circle" alt="image">
                                    </a>
                                </div>
                            </div>
                            <div class="users-list-body">
                                <div class="name-list-user">
                                    <h6>
                                        {{ $notification->data['user_name'] }} 
                                        <span>{{ $notification->data['action_text'] }} </span>
                                        {{ $notification->data['target_item'] }}
                                    </h6>
                                    
                                    {{-- Dynamic quote/comment section if exists --}}
                                    @if(isset($notification->data['comment']))
                                        <blockquote>"{{ Str::limit($notification->data['comment'], 150) }}"</blockquote>
                                    @endif

                                    {{-- Action Buttons for specific notification types --}}
                                    @if(isset($notification->data['type']) && $notification->data['type'] == 'request')
                                        <div class="follow-btn">
                                            <a href="{{ url('action/accept/'.$notification->id) }}" class="btn btn-primary">Accept</a>
                                            <a href="{{ url('action/reject/'.$notification->id) }}" class="btn btn-outline-primary">Reject</a>
                                        </div>
                                    @endif
                                    
                                    <span class="time">{{ $notification->created_at->diffForHumans() }}</span>
                                </div>
                                <div class="chat-user-time">
                                    <span class="chats-delete">
                                        <a href="javascript:;" 
                                           onclick="deleteNotification('{{ $notification->id }}')" 
                                           data-bs-toggle="modal" 
                                           data-bs-target="#notification-delete">
                                            <i class="fe fe-trash"></i>
                                        </a>
                                    </span>
                                </div>
                            </div>
                        </div>
                    @empty
                        <div class="card p-5 text-center">
                            <div class="card-body">
                                <i class="fe fe-bell-off mb-3" style="font-size: 2rem; opacity: 0.5;"></i>
                                <p>No new notifications found.</p>
                            </div>
                        </div>
                    @endforelse

                    {{-- Dynamic Pagination --}}
                    <div class="d-flex justify-content-center mt-4">
                        {{ $notifications->links() }}
                    </div>
                </div>
            </div>
        </div>
    </div>
    {{-- Script for handling the ID in the delete modal --}}
    <script>
        function deleteNotification(id) {
            // Set the form action URL or input value in your modal dynamically
            $('#delete-notification-id').val(id);
        }
    </script>
@endsection