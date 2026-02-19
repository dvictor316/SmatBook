<?php $page = 'tickets-closed'; ?>
@extends('layout.mainlayout')
@section('content')
    <!-- Page Wrapper -->
    <div class="page-wrapper">
        <div class="content container-fluid">

            <!-- Page Header -->
            @component('components.page-header')
                @slot('title')
                    Tickets Closed
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
            @component('components.tickets-tab')
            @endcomponent
            <!-- /All Invoice -->

            <!-- Table -->
            <div class="row">
                <div class="col-sm-12">
                    <div class="card-table">
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-stripped table-hover datatable">
                                    <thead class="thead-light">
                                        <tr>
                                            <th>Ticket ID</th>
                                            <th>Subject</th>
                                            <th>Assigned</th>
                                            <th>Assigned Date</th>
                                            <th>Created On</th>
                                            <th>Due Date</th>
                                            <th>Assignee</th>
                                            <th>Last Reply</th>
                                            <th>Priority</th>
                                            <th>Status</th>
                                            <th class="no-sort">Action</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @php
                                            $json = file_get_contents(public_path('../public/assets/json/tickets-closed.json'));
                                            $tickets = json_decode($json, true);
                                        @endphp
                                        @foreach ($tickets as $ticket)
                                            <tr>
                                                <td>
                                                    <a href="{{ url('invoice-details') }}"
                                                        class="invoice-link">{{ $ticket['TicketID'] }}</a>
                                                </td>
                                                <td>{{ $ticket['Subject'] }}</td>
                                                <td>
                                                    <h2 class="table-avatar">
                                                        <span class="avatar avatar-sm me-2"><img
                                                                class="avatar-img rounded-circle"
                                                                src="{{ URL::asset('/assets/img/profiles/' . $ticket['AssignedImage']) }}"
                                                                alt="User Image"></span>
                                                        <span>{{ $ticket['Assigned'] }}</span>
                                                    </h2>
                                                </td>
                                                <td>{{ $ticket['AssignedDate'] }}</td>
                                                <td>{{ $ticket['CreatedOn'] }}</td>
                                                <td>{{ $ticket['DueDate'] }}</td>
                                                <td>
                                                    <h2 class="table-avatar">
                                                        <a class="avatar avatar-sm me-2"><img
                                                                class="avatar-img rounded-circle"
                                                                src="{{ URL::asset('/assets/img/profiles/' . $ticket['Image']) }}"
                                                                alt="User Image"></a>
                                                        <a>{{ $ticket['Assignee'] }}<span>{{ $ticket['Phone'] }}</span></a>
                                                    </h2>
                                                </td>
                                                <td>{{ $ticket['LastReply'] }}</td>
                                                <td><span
                                                        class="{{ $ticket['ClassPriority'] }}">{{ $ticket['Priority'] }}</span>
                                                </td>
                                                <td><span class="{{ $ticket['Class'] }}">{{ $ticket['Status'] }}</span>
                                                </td>
                                                <td class="d-flex align-items-center">
                                                    <div class="dropdown dropdown-action">
                                                        <a href="#" class=" btn-action-icon "
                                                            data-bs-toggle="dropdown" aria-expanded="false"><i
                                                                class="fas fa-ellipsis-v"></i></a>
                                                        <div class="dropdown-menu dropdown-menu-right">
                                                            <ul>
                                                                <li>
                                                                    <a class="dropdown-item" href="javascript:void(0);"
                                                                        data-bs-toggle="modal"
                                                                        data-bs-target="#edit_ticket"><i
                                                                            class="far fa-edit me-2"></i>Edit</a>
                                                                </li>
                                                                <li>
                                                                    <a class="dropdown-item" href="javascript:void(0);"><i
                                                                            class="far fa-trash-alt me-2"></i>Delete</a>
                                                                </li>
                                                                <li>
                                                                    <a class="dropdown-item" href="javascript:void(0);"><i
                                                                            class="far fa-eye me-2"></i>View</a>
                                                                </li>
                                                                <li>
                                                                    <a class="dropdown-item" href="javascript:void(0);"><i
                                                                            class="far fa-bell me-2"></i>Active</a>
                                                                </li>
                                                                <li>
                                                                    <a class="dropdown-item" href="javascript:void(0);"><i
                                                                            class="far fa-bell-slash me-2"></i>Deactivate</a>
                                                                </li>
                                                            </ul>
                                                        </div>
                                                    </div>
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <!-- /Table -->

        </div>
    </div>
    <!-- /Page Wrapper -->
@endsection
