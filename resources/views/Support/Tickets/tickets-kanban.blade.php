<?php $page = 'tickets-kanban'; ?>
@extends('layout.mainlayout')
@section('content')
    
    <div class="page-wrapper">
        <div class="content container-fluid">

            
            @component('components.page-header')
                @slot('title')
                    Tickets
                @endslot
            @endcomponent
            

            
            @component('components.search-filter')
            @endcomponent
            

            
            @component('components.tickets-card')
            @endcomponent
            

            
            @component('components.tickets-kanban-tab')
            @endcomponent
            

            
            <div class="row">
                <div class="col-sm-12">
                    <div class="kanban-ticket-main d-flex">

                        <ul class="kanban-ticket-list">
                            <li class="kanban-ticket-grid">
                                <div class="kanban-ticket d-flex justify-content-between align-items-center">
                                    <div class="kanban-head css-equal-heights">
                                        <h6 class="me-2">Open</h6>
                                        <span
                                            class="inovices-widget-icon rounded-circle bg-warning-light text-warning-light">5</span>
                                    </div>
                                    <a href="#"><span><i class="fe fe-plus"></i></span></a>
                                </div>
                            </li>
                            @php
                                $json = file_get_contents(public_path('../public/assets/json/tickets-kanban-open.json'));
                                $tickets = json_decode($json, true);
                            @endphp
                            @foreach ($tickets as $ticket)
                                
                                <li class="kanban-ticket-grid">
                                    <div class="card-kanban">
                                        <div class="kanban-box">
                                            <div class="kanban-name d-flex justify-content-between align-items-center">
                                                <span
                                                    class="{{ $ticket['priorityclass'] }}">{{ $ticket['priority'] }}</span>
                                                <div class="dropdown dropdown-action">
                                                    <a href="#" class="action-icon dropdown-toggle"
                                                        data-bs-toggle="dropdown" aria-expanded="false"><i
                                                            class="fas fa-ellipsis-h"></i></a>
                                                    <div class="dropdown-menu dropdown-menu-end">
                                                        <a class="dropdown-item" href="javascript:void(0);"
                                                            data-bs-toggle="modal" data-bs-target="#edit_ticket"><i
                                                                class="far fa-edit me-2"></i>Edit</a>
                                                        <a class="dropdown-item" href="javascript:void(0);"><i
                                                                class="far fa-eye me-2"></i>View</a>
                                                        <a class="dropdown-item" href="javascript:void(0);"><i
                                                                class="far fa-trash-alt me-2"></i>Delete</a>
                                                    </div>
                                                </div>
                                            </div>
                                            <h6>{{ $ticket['title'] }}</h6>
                                            <div class="ticket-due d-flex">
                                                <div class="ticket-due-id me-2"><span
                                                        class="badge badge-gray-outline">{{ $ticket['ticket_number'] }}</span>
                                                </div>
                                                <div class="ticket-due-status"><span
                                                        class="{{ $ticket['due_status_class'] }}">{{ $ticket['due_status'] }}</span>
                                                </div>
                                            </div>
                                            <div class="d-flex align-items-center justify-content-between">
                                                <ul class="kanban-ticket-img d-flex align-items-center">
                                                    <li>
                                                        <a href="#" data-bs-toggle="tooltip" data-bs-placement="top"
                                                            title="" data-bs-original-title="Member 1">
                                                            <img class="avatar-img rounded-circle"
                                                                src="{{ URL::asset('/assets/img/profiles/avatar-01.jpg') }}"
                                                                alt="img">
                                                        </a>
                                                    </li>
                                                    <li>
                                                        <a href="#" data-bs-toggle="tooltip"
                                                            data-bs-placement="top" title=""
                                                            data-bs-original-title="Member 2"><img
                                                                class="avatar-img rounded-circle"
                                                                src="{{ URL::asset('/assets/img/profiles/avatar-02.jpg') }}"
                                                                alt="img"></a>
                                                    </li>
                                                    <li class="more-set">
                                                        <a href="#"><span
                                                                class="d-flex justify-content-center align-items-center"><i
                                                                    class="fe fe-plus"></i></span></a>
                                                    </li>
                                                </ul>
                                                <div class="progressset">
                                                    <p><span><i class="far fa-calendar me-2"></i></span>
                                                        {{ $ticket['progress_date'] }}</p>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </li>
                                
                            @endforeach
                        </ul>

                        <ul class="kanban-ticket-list">
                            <li class="kanban-ticket-grid">
                                <div class="kanban-ticket d-flex justify-content-between align-items-center">
                                    <div class="kanban-head css-equal-heights">
                                        <h6 class="me-2">In Progress</h6>
                                        <span
                                            class="inovices-widget-icon rounded-circle bg-warning-light text-warning-light">8</span>
                                    </div>
                                    <a href="#"><span><i class="fe fe-plus"></i></span></a>
                                </div>
                            </li>
                            @php
                                $json = file_get_contents(public_path('../public/assets/json/tickets-kanban-inprogress.json'));
                                $tickets = json_decode($json, true);
                            @endphp
                            @foreach ($tickets as $ticket)
                                
                                <li class="kanban-ticket-grid">
                                    <div class="card-kanban">
                                        <div class="kanban-box">
                                            <div class="kanban-name d-flex justify-content-between align-items-center">
                                                <span
                                                    class="{{ $ticket['priorityclass'] }}">{{ $ticket['priority'] }}</span>
                                                <div class="dropdown dropdown-action">
                                                    <a href="#" class="action-icon dropdown-toggle"
                                                        data-bs-toggle="dropdown" aria-expanded="false"><i
                                                            class="fas fa-ellipsis-h"></i></a>
                                                    <div class="dropdown-menu dropdown-menu-end">
                                                        <a class="dropdown-item" href="javascript:void(0);"
                                                            data-bs-toggle="modal" data-bs-target="#edit_ticket"><i
                                                                class="far fa-edit me-2"></i>Edit</a>
                                                        <a class="dropdown-item" href="javascript:void(0);"><i
                                                                class="far fa-eye me-2"></i>View</a>
                                                        <a class="dropdown-item" href="javascript:void(0);"><i
                                                                class="far fa-trash-alt me-2"></i>Delete</a>
                                                    </div>
                                                </div>
                                            </div>
                                            <h6>{{ $ticket['title'] }}</h6>
                                            <div class="ticket-due d-flex">
                                                <div class="ticket-due-id me-2"><span
                                                        class="badge badge-gray-outline">{{ $ticket['ticket_number'] }}</span>
                                                </div>
                                                <div class="ticket-due-status"><span
                                                        class="{{ $ticket['due_status_class'] }}">{{ $ticket['due_status'] }}</span>
                                                </div>
                                            </div>
                                            <div class="d-flex align-items-center justify-content-between">
                                                <ul class="kanban-ticket-img d-flex align-items-center">
                                                    <li>
                                                        <a href="#" data-bs-toggle="tooltip"
                                                            data-bs-placement="top" title=""
                                                            data-bs-original-title="Member 1">
                                                            <img class="avatar-img rounded-circle"
                                                                src="{{ URL::asset('/assets/img/profiles/avatar-01.jpg') }}"
                                                                alt="img">
                                                        </a>
                                                    </li>
                                                    <li>
                                                        <a href="#" data-bs-toggle="tooltip"
                                                            data-bs-placement="top" title=""
                                                            data-bs-original-title="Member 2"><img
                                                                class="avatar-img rounded-circle"
                                                                src="{{ URL::asset('/assets/img/profiles/avatar-02.jpg') }}"
                                                                alt="img"></a>
                                                    </li>
                                                    <li class="more-set">
                                                        <a href="#"><span
                                                                class="d-flex justify-content-center align-items-center"><i
                                                                    class="fe fe-plus"></i></span></a>
                                                    </li>
                                                </ul>
                                                <div class="progressset">
                                                    <p><span><i class="far fa-calendar me-2"></i></span> Jan 2023</p>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </li>
                                
                            @endforeach
                        </ul>

                        <ul class="kanban-ticket-list">
                            <li class="kanban-ticket-grid">
                                <div class="kanban-ticket d-flex justify-content-between align-items-center">
                                    <div class="kanban-head css-equal-heights">
                                        <h6 class="me-2">Hold</h6>
                                        <span
                                            class="inovices-widget-icon rounded-circle bg-warning-light text-warning-light">6</span>
                                    </div>
                                    <a href="#"><span><i class="fe fe-plus"></i></span></a>
                                </div>
                            </li>
                            @php
                                $json = file_get_contents(public_path('../public/assets/json/tickets-kanban-hold.json'));
                                $tickets = json_decode($json, true);
                            @endphp
                            @foreach ($tickets as $ticket)
                                
                                <li class="kanban-ticket-grid">
                                    <div class="card-kanban">
                                        <div class="kanban-box">
                                            <div class="kanban-name d-flex justify-content-between align-items-center">
                                                <span
                                                    class="{{ $ticket['priorityclass'] }}">{{ $ticket['priority'] }}</span>
                                                <div class="dropdown dropdown-action">
                                                    <a href="#" class="action-icon dropdown-toggle"
                                                        data-bs-toggle="dropdown" aria-expanded="false"><i
                                                            class="fas fa-ellipsis-h"></i></a>
                                                    <div class="dropdown-menu dropdown-menu-end">
                                                        <a class="dropdown-item" href="javascript:void(0);"
                                                            data-bs-toggle="modal" data-bs-target="#edit_ticket"><i
                                                                class="far fa-edit me-2"></i>Edit</a>
                                                        <a class="dropdown-item" href="javascript:void(0);"><i
                                                                class="far fa-eye me-2"></i>View</a>
                                                        <a class="dropdown-item" href="javascript:void(0);"><i
                                                                class="far fa-trash-alt me-2"></i>Delete</a>
                                                    </div>
                                                </div>
                                            </div>
                                            <h6>{{ $ticket['title'] }}</h6>
                                            <div class="ticket-due d-flex">
                                                <div class="ticket-due-id me-2"><span
                                                        class="badge badge-gray-outline">{{ $ticket['ticket_number'] }}</span>
                                                </div>
                                                <div class="ticket-due-status"><span
                                                        class="{{ $ticket['due_status_class'] }}">{{ $ticket['due_status'] }}</span>
                                                </div>
                                            </div>
                                            <div class="d-flex align-items-center justify-content-between">
                                                <ul class="kanban-ticket-img d-flex align-items-center">
                                                    <li>
                                                        <a href="#" data-bs-toggle="tooltip"
                                                            data-bs-placement="top" title=""
                                                            data-bs-original-title="Member 1">
                                                            <img class="avatar-img rounded-circle"
                                                                src="{{ URL::asset('/assets/img/profiles/avatar-01.jpg') }}"
                                                                alt="img">
                                                        </a>
                                                    </li>
                                                    <li>
                                                        <a href="#" data-bs-toggle="tooltip"
                                                            data-bs-placement="top" title=""
                                                            data-bs-original-title="Member 2"><img
                                                                class="avatar-img rounded-circle"
                                                                src="{{ URL::asset('/assets/img/profiles/avatar-02.jpg') }}"
                                                                alt="img"></a>
                                                    </li>
                                                    <li class="more-set">
                                                        <a href="#"><span
                                                                class="d-flex justify-content-center align-items-center"><i
                                                                    class="fe fe-plus"></i></span></a>
                                                    </li>
                                                </ul>
                                                <div class="progressset">
                                                    <p><span><i class="far fa-calendar me-2"></i></span> Jan 2023</p>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </li>
                                
                            @endforeach
                        </ul>

                        <ul class="kanban-ticket-list">
                            <li class="kanban-ticket-grid">
                                <div class="kanban-ticket d-flex justify-content-between align-items-center">
                                    <div class="kanban-head css-equal-heights">
                                        <h6 class="me-2">Unassigned</h6>
                                        <span
                                            class="inovices-widget-icon rounded-circle bg-warning-light text-warning-light">5</span>
                                    </div>
                                    <a href="#"><span><i class="fe fe-plus"></i></span></a>
                                </div>
                            </li>
                            @php
                                $json = file_get_contents(public_path('../public/assets/json/tickets-kanban-unassigned.json'));
                                $tickets = json_decode($json, true);
                            @endphp
                            @foreach ($tickets as $ticket)
                                
                                <li class="kanban-ticket-grid">
                                    <div class="card-kanban">
                                        <div class="kanban-box">
                                            <div class="kanban-name d-flex justify-content-between align-items-center">
                                                <span
                                                    class="{{ $ticket['priorityclass'] }}">{{ $ticket['priority'] }}</span>
                                                <div class="dropdown dropdown-action">
                                                    <a href="#" class="action-icon dropdown-toggle"
                                                        data-bs-toggle="dropdown" aria-expanded="false"><i
                                                            class="fas fa-ellipsis-h"></i></a>
                                                    <div class="dropdown-menu dropdown-menu-end">
                                                        <a class="dropdown-item" href="javascript:void(0);"
                                                            data-bs-toggle="modal" data-bs-target="#edit_ticket"><i
                                                                class="far fa-edit me-2"></i>Edit</a>
                                                        <a class="dropdown-item" href="javascript:void(0);"><i
                                                                class="far fa-eye me-2"></i>View</a>
                                                        <a class="dropdown-item" href="javascript:void(0);"><i
                                                                class="far fa-trash-alt me-2"></i>Delete</a>
                                                    </div>
                                                </div>
                                            </div>
                                            <h6>{{ $ticket['title'] }}</h6>
                                            <div class="ticket-due d-flex">
                                                <div class="ticket-due-id me-2"><span
                                                        class="badge badge-gray-outline">{{ $ticket['ticket_number'] }}</span>
                                                </div>
                                                <div class="ticket-due-status"><span
                                                        class="{{ $ticket['due_status_class'] }}">{{ $ticket['due_status'] }}</span>
                                                </div>
                                            </div>
                                            <div class="d-flex align-items-center justify-content-between">
                                                <ul class="kanban-ticket-img d-flex align-items-center">
                                                    <li>
                                                        <a href="#" data-bs-toggle="tooltip"
                                                            data-bs-placement="top" title=""
                                                            data-bs-original-title="Member 1">
                                                            <img class="avatar-img rounded-circle"
                                                                src="{{ URL::asset('/assets/img/profiles/avatar-01.jpg') }}"
                                                                alt="img">
                                                        </a>
                                                    </li>
                                                    <li>
                                                        <a href="#" data-bs-toggle="tooltip"
                                                            data-bs-placement="top" title=""
                                                            data-bs-original-title="Member 2"><img
                                                                class="avatar-img rounded-circle"
                                                                src="{{ URL::asset('/assets/img/profiles/avatar-02.jpg') }}"
                                                                alt="img"></a>
                                                    </li>
                                                    <li class="more-set">
                                                        <a href="#"><span
                                                                class="d-flex justify-content-center align-items-center"><i
                                                                    class="fe fe-plus"></i></span></a>
                                                    </li>
                                                </ul>
                                                <div class="progressset">
                                                    <p><span><i class="far fa-calendar me-2"></i></span> Jan 2023</p>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </li>
                                
                            @endforeach
                        </ul>

                        <ul class="kanban-ticket-list">
                            <li class="kanban-ticket-grid">
                                <div class="kanban-ticket d-flex justify-content-between align-items-center">
                                    <div class="kanban-head css-equal-heights">
                                        <h6 class="me-2">Solved</h6>
                                        <span
                                            class="inovices-widget-icon rounded-circle bg-warning-light text-warning-light">10</span>
                                    </div>
                                    <a href="#"><span><i class="fe fe-plus"></i></span></a>
                                </div>
                            </li>
                            @php
                                $json = file_get_contents(public_path('../public/assets/json/tickets-kanban-solved.json'));
                                $tickets = json_decode($json, true);
                            @endphp
                            @foreach ($tickets as $ticket)
                                
                                <li class="kanban-ticket-grid">
                                    <div class="card-kanban">
                                        <div class="kanban-box">
                                            <div class="kanban-name d-flex justify-content-between align-items-center">
                                                <span
                                                    class="{{ $ticket['priorityclass'] }}">{{ $ticket['priority'] }}</span>
                                                <div class="dropdown dropdown-action">
                                                    <a href="#" class="action-icon dropdown-toggle"
                                                        data-bs-toggle="dropdown" aria-expanded="false"><i
                                                            class="fas fa-ellipsis-h"></i></a>
                                                    <div class="dropdown-menu dropdown-menu-end">
                                                        <a class="dropdown-item" href="javascript:void(0);"
                                                            data-bs-toggle="modal" data-bs-target="#edit_ticket"><i
                                                                class="far fa-edit me-2"></i>Edit</a>
                                                        <a class="dropdown-item" href="javascript:void(0);"><i
                                                                class="far fa-eye me-2"></i>View</a>
                                                        <a class="dropdown-item" href="javascript:void(0);"><i
                                                                class="far fa-trash-alt me-2"></i>Delete</a>
                                                    </div>
                                                </div>
                                            </div>
                                            <h6>{{ $ticket['title'] }}</h6>
                                            <div class="ticket-due d-flex">
                                                <div class="ticket-due-id me-2"><span
                                                        class="badge badge-gray-outline">{{ $ticket['ticket_number'] }}</span>
                                                </div>
                                                <div class="ticket-due-status"><span
                                                        class="{{ $ticket['due_status_class'] }}">{{ $ticket['due_status'] }}</span>
                                                </div>
                                            </div>
                                            <div class="d-flex align-items-center justify-content-between">
                                                <ul class="kanban-ticket-img d-flex align-items-center">
                                                    <li>
                                                        <a href="#" data-bs-toggle="tooltip"
                                                            data-bs-placement="top" title=""
                                                            data-bs-original-title="Member 1">
                                                            <img class="avatar-img rounded-circle"
                                                                src="{{ URL::asset('/assets/img/profiles/avatar-01.jpg') }}"
                                                                alt="img">
                                                        </a>
                                                    </li>
                                                    <li>
                                                        <a href="#" data-bs-toggle="tooltip"
                                                            data-bs-placement="top" title=""
                                                            data-bs-original-title="Member 2"><img
                                                                class="avatar-img rounded-circle"
                                                                src="{{ URL::asset('/assets/img/profiles/avatar-02.jpg') }}"
                                                                alt="img"></a>
                                                    </li>
                                                    <li class="more-set">
                                                        <a href="#"><span
                                                                class="d-flex justify-content-center align-items-center"><i
                                                                    class="fe fe-plus"></i></span></a>
                                                    </li>
                                                </ul>
                                                <div class="progressset">
                                                    <p><span><i class="far fa-calendar me-2"></i></span> Jan 2023</p>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </li>
                                
                            @endforeach
                        </ul>

                    </div>
                </div>
            </div>
            

        </div>
    </div>
    
@endsection
