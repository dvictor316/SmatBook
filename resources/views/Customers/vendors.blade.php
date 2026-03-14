<?php $page = 'vendors'; ?>
@extends('layout.mainlayout')
@section('content')
    <div class="page-wrapper">
        <div class="content container-fluid">

            @component('components.page-header')
                @slot('title')
                    Vendors
                @endslot
            @endcomponent

            <div class="row mb-3">
                <div class="col-sm-12 text-end">
                    <a href="{{ route('vendors.create') }}" class="btn btn-primary">Add New Vendor</a>
                </div>
            </div>

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
                                            <th>Created</th>
                                            <th>Balance</th>
                                            <th Class="no-sort">Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach ($vendors as $vendor)
                                            <tr>
                                                <td>{{ $vendor->id }}</td>
                                                <td>
                                                    <h2 class="table-avatar">
                                                        <a href="{{ route('vendors.ledger', ['id' => $vendor->id]) }}" class="avatar avatar-md me-2">
                                                            <img class="avatar-img rounded-circle" src="{{ $vendor->logo_url }}" alt="{{ $vendor->name }}">
                                                        </a>
                                                        <a href="{{ route('vendors.ledger', ['id' => $vendor->id]) }}">{{ $vendor->name }}
                                                            <span>{{ $vendor->email }}</span></a>
                                                    </h2>
                                                </td>
                                                <td>{{ $vendor->phone }}</td>
                                                <td>{{ $vendor->created_at->format('M d, Y') ?? 'N/A' }}</td>
                                                <td>₦{{ number_format($vendor->current_balance ?? 0, 2) }}</td>
                                                <td class="d-flex align-items-center">
                                                    {{-- Link to the specific vendor's ledger --}}
                                                    <a href="{{ route('vendors.ledger', ['id' => $vendor->id]) }}" class="btn btn-greys me-2"><i
                                                            class="fa fa-eye me-1"></i> Ledger</a>
                                                    <a href="{{ route('vendors.transactions.create', ['id' => $vendor->id]) }}" class="btn btn-primary me-2">
                                                        <i class="fa fa-plus-circle me-1"></i> Add Txn
                                                    </a>
                                                    
                                                    <div class="dropdown dropdown-action">
                                                        <a href="#" class="btn-action-icon" data-bs-toggle="dropdown"
                                                            aria-expanded="false"><i class="fas fa-ellipsis-v"></i></a>
                                                        <div class="dropdown-menu dropdown-menu-end">
                                                            <ul>
                                                                <li>
                                                                    {{-- Link to the EDIT page (no modal required) --}}
                                                                    <a class="dropdown-item" href="{{ route('vendors.edit', $vendor->id) }}">
                                                                        <i class="far fa-edit me-2"></i>Edit
                                                                    </a>
                                                                </li>
                                                                <li>
                                                                    {{-- Form for DELETING a vendor --}}
                                                                    <form action="{{ route('vendors.destroy', $vendor->id) }}" method="POST" onsubmit="return confirm('Are you sure you want to delete this vendor? This action is permanent.');">
                                                                        @csrf
                                                                        @method('DELETE')
                                                                        <button type="submit" class="dropdown-item">
                                                                            <i class="far fa-trash-alt me-2"></i>Delete
                                                                        </button>
                                                                    </form>
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
                            <div class="mt-3">
                                {{ $vendors->links() }}
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
