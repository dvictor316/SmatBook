<?php $page = 'deactive-customers'; ?>
@extends('layout.mainlayout')
@section('content')
    <!-- Page Wrapper -->
    <div class="page-wrapper">
        <div class="content container-fluid">
            <!-- Page Header -->
            @component('components.page-header')
                @slot('title')
                Deactive Customers
                @endslot
            @endcomponent
            <!-- /Page Header -->

            <!-- Search Filter -->
            @component('components.search-filter')
            @endcomponent
            <!-- /Search Filter -->

            <div class="row">
                <div class="col-sm-12">
                    <div class="card-table">
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-center table-hover datatable">
                                    <thead class="thead-light">
                                        <tr>
                                            <th>#</th>
                                            <th>Name</th>
                                            <th>Phone</th>
                                            <th>Balance </th>
                                            <th>Total Invoice </th>
                                            <th>Created</th>
                                            <th>Status</th>
                                            <th Class="no-sort">Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @php
                                            $json = file_get_contents(public_path('../public/assets/json/deactive-customers.json'));
                                            $customers = json_decode($json, true);
                                        @endphp
                                        @foreach ($customers as $customer)
                                            <tr>
                                                <td>{{ $customer['Id'] }}</td>
                                                <td>
                                                    <h2 class="table-avatar">
                                                        <a href="{{ url('profile') }}" class="avatar avatar-md me-2"><img
                                                                class="avatar-img rounded-circle"
                                                                src="{{ URL::asset('/assets/img/profiles/' . $customer['Image']) }}"
                                                                alt="User Image"></a>
                                                        <a href="{{ url('profile') }}">{{ $customer['Name'] }}
                                                            <span>{{ $customer['Email'] }}</span></a>
                                                    </h2>
                                                </td>
                                                <td>{{ $customer['Phone'] }}</td>
                                                <td>{{ $customer['Balance'] }}</td>
                                                <td>{{ $customer['TotalInvoice'] }}</td>
                                                <td>{{ $customer['Created'] }}</td>
                                                <td><span class="badge  bg-danger-light">{{ $customer['Status'] }}</span>
                                                </td>
                                                <td class="d-flex align-items-center">
                                                    <a href="{{ url('add-invoice') }}" class="btn btn-greys me-2"><i
                                                            class="fa fa-plus-circle me-1"></i> Invoice</a>
                                                    <div class="dropdown dropdown-action">
                                                        <a href="#" class=" btn-action-icon "
                                                            data-bs-toggle="dropdown" aria-expanded="false"><i
                                                                class="fas fa-ellipsis-v"></i></a>
                                                        <div class="dropdown-menu dropdown-menu-end">
                                                            <ul>
                                                                <li>
                                                                    <a class="dropdown-item"
                                                                        href="{{ url('edit-invoice') }}"><i
                                                                            class="far fa-edit me-2"></i>Edit</a>
                                                                </li>
                                                                <li>
                                                                    <a class="dropdown-item" href="javascript:void(0);"
                                                                        data-bs-toggle="modal"
                                                                        data-bs-target="#delete_modal"><i
                                                                            class="far fa-trash-alt me-2"></i>Delete</a>
                                                                </li>
                                                                <li>
                                                                    <a class="dropdown-item"
                                                                        href="{{ url('invoice-details') }}"><i
                                                                            class="far fa-eye me-2"></i>View</a>
                                                                </li>
                                                                <li>
                                                                    <a class="dropdown-item"
                                                                        href="{{ url('active-customers') }}"><i
                                                                            class="far fa-bell me-2"></i>Active</a>
                                                                </li>
                                                                <li>
                                                                    <a class="dropdown-item"
                                                                        href="{{ url('deactive-customers') }}"><i
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
        </div>
    </div>
    <!-- /Page Wrapper -->
@endsection
