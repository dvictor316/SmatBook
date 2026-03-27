<?php $page = 'active-customers'; ?>
@extends('layout.mainlayout')

@section('content')
<!-- Page Wrapper -->
<div class="page-wrapper">
    <div class="content container-fluid">

        <!-- Page Header -->
        @component('components.page-header')
            @slot('title')
                Active Customers
            @endslot
        @endcomponent
        <!-- /Page Header -->

        <!-- Search Filter -->
        @component('components.search-filter')
            <!-- Optional: Add dynamic filters here -->
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
                                        <th>Balance</th>
                                        <th>Total Invoices</th>
                                        <th>Created</th>
                                        <th>Status</th>
                                        <th class="no-sort">Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse ($customers as $customer)
                                    <tr>
                                        <td>{{ $loop->iteration }}</td>
                                        <td>
                                            <h2 class="table-avatar">
                                                <a href="{{ route('customers.show', $customer->id) }}" class="avatar avatar-md me-2">
                                                    <img class="avatar-img rounded-circle" 
                                                         src="{{ $customer->image ? asset('assets/img/profiles/' . $customer->image) : asset('assets/img/profiles/default.png') }}" 
                                                         alt="User Image">
                                                </a>
                                                <a href="{{ route('customers.show', $customer->id) }}">
                                                    {{ $customer->name }}
                                                    <span>{{ $customer->email }}</span>
                                                </a>
                                            </h2>
                                        </td>
                                        <td>{{ $customer->phone }}</td>
                                        <td>{{ number_format($customer->computed_balance ?? $customer->balance, 2) }}</td>
                                        <td>{{ $customer->invoices->count() }}</td>
                                        <td>{{ $customer->created_at->format('d M Y') }}</td>
                                        <td>
                                            <span class="badge {{ $customer->status == 'active' ? 'bg-success-light' : 'bg-danger-light' }}">
                                                {{ ucfirst($customer->status) }}
                                            </span>
                                        </td>
                                        <td class="d-flex align-items-center">
                                            <a href="{{ route('add-invoice', ['customer_id' => $customer->id]) }}" class="btn btn-greys me-2">
                                                <i class="fa fa-plus-circle me-1"></i> Invoice
                                            </a>
                                            <div class="dropdown dropdown-action">
                                                <a href="#" class="btn-action-icon" data-bs-toggle="dropdown">
                                                    <i class="fas fa-ellipsis-v"></i>
                                                </a>
                                                <div class="dropdown-menu dropdown-menu-end">
                                                    <a class="dropdown-item" href="{{ route('customers.edit', $customer->id) }}">
                                                        <i class="far fa-edit me-2"></i>Edit
                                                    </a>
                                                    <a class="dropdown-item" href="#" data-bs-toggle="modal" data-bs-target="#delete_modal" data-id="{{ $customer->id }}">
                                                        <i class="far fa-trash-alt me-2"></i>Delete
                                                    </a>
                                                    <a class="dropdown-item" href="{{ route('customers.show', $customer->id) }}">
                                                        <i class="far fa-eye me-2"></i>View
                                                    </a>
                                                    <a class="dropdown-item" href="{{ route('active-customers') }}">
                                                        <i class="far fa-bell me-2"></i>Active
                                                    </a>
                                                    <a class="dropdown-item" href="{{ route('deactive-customers') }}">
                                                        <i class="far fa-bell-slash me-2"></i>Deactivate
                                                    </a>
                                                </div>
                                            </div>
                                        </td>
                                    </tr>
                                    @empty
                                    <tr>
                                        <td colspan="8" class="text-center">No customers found.</td>
                                    </tr>
                                    @endforelse
                                </tbody>
                            </table>
                            @if($customers->isEmpty())
                                <p class="text-center mt-3">No active customers found.</p>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </div>

    </div>
</div>
<!-- /Page Wrapper -->
@endsection
