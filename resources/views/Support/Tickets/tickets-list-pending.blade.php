<?php $page = 'tickets-list-pending'; ?>
@extends('layout.mainlayout')
@section('content')
    <!-- Page Wrapper -->
    <div class="page-wrapper">
        <div class="content container-fluid">

            <!-- Page Header -->
            @component('components.page-header')
                @slot('title')
                    Tickets
                @endslot
            @endcomponent
            <!-- /Page Header -->

            <!-- Search Filter -->
            @component('components.search-filter')
            @endcomponent
            <!-- /Search Filter -->

            <!-- Inovices card -->
            @component('components.tickets-card')
            @endcomponent
            <!-- /Inovices card -->

            <!-- All Invoice -->
            @component('components.tickets-kanban-tab')
            @endcomponent
            <!-- /All Invoice -->

            <!-- Ticket list -->
            <div class="comments">
                <!-- card -->
                @php
                    $json = file_get_contents(public_path('../public/assets/json/tickets-list-pending.json'));
                    $tickets = json_decode($json, true);
                @endphp
                @foreach ($tickets as $ticket)
                    <div class="card">
                        <div class="card-body card-support">
                            <div class="comments-details d-flex align-items-center justify-content-between">
                                <div class="d-flex align-items-center describe-btn mt-0">
                                    <h6 class="fw-bolder text-gray-dark">{{ $ticket['title'] }}</h6><span
                                        class="{{ $ticket['class'] }}">{{ $ticket['status'] }}</span>
                                </div>
                                <a href="#" class="reply-comment d-flex text-gray-light"><span><i
                                            class="fe fe-clock text-gray-light fw-normal me-2"></i></span><span
                                        class="text-gray-light fw-normal">{{ $ticket['timestamp'] }}</span></a>
                            </div>
                            <div class="card-describe">
                                <p>{{ $ticket['description'] }}</p>
                            </div>
                            <div class="describe-btn">
                                <span class="{{ $ticket['priortiyclass'] }}">{{ $ticket['priority'] }}</span>
                                <span class="badge badge-gray-outline me-2">{{ $ticket['ticket_number'] }}</span>
                                <span><i
                                        class="fe fe-message-square text-gray-light fw-normal me-2"></i>{{ $ticket['message_count'] }}</span>
                            </div>
                        </div>
                    </div>
                @endforeach
                <!-- /card -->
            </div>
            <!-- /Ticket list -->
        </div>
    </div>
    <!-- /Page Wrapper -->
@endsection
