<?php $page = 'debit-notes'; ?>
@extends('layout.mainlayout')
@section('content')
    <div class="page-wrapper">
        <div class="content container-fluid">

            <div class="page-header">
                <div class="content-page-header">
                    <h5>Purchase Returns / Debit Notes</h5>
                    <div class="list-btn">
                        <ul class="filter-list">
                            <li>
                                <a class="btn btn-filters w-auto popup-toggle" data-bs-toggle="tooltip" data-bs-placement="bottom" title="Filter">
                                    <span class="me-2"><img src="{{asset('assets/img/icons/filter-icon.svg')}}" alt="filter"></span>Filter 
                                </a>
                            </li>
                            <li class="dropdown">
                                <a href="javascript:void(0);" class="btn-filters" data-bs-toggle="dropdown" aria-expanded="false">
                                    <span><i class="fe fe-settings"></i></span>
                                </a>
                                <div class="dropdown-menu dropdown-menu-end shadow border-0">
                                    <div class="dropdown-header">Table Options</div>
                                    <a class="dropdown-item" href="javascript:window.print()"><i class="fe fe-printer me-2"></i> Print View</a>
                                    <a class="dropdown-item" href="{{ url('debit-notes') }}"><i class="fe fe-refresh-cw me-2"></i> Reset Table</a>
                                    <div class="dropdown-divider"></div>
                                    <a class="dropdown-item text-danger" href="#"><i class="fe fe-file-text me-2"></i> Export PDF</a>
                                </div>
                            </li>
                            <li>
                                <a class="btn btn-primary" href="{{ url('purchase-returns/create') }}">
                                    <i class="fa fa-plus-circle me-2" aria-hidden="true"></i>Add Return
                                </a>
                            </li>
                        </ul>
                    </div>
                </div>
            </div>
            <div class="card shadow-sm mb-4">
                <div class="card-body">
                    <form action="{{ url('debit-notes') }}" method="GET">
                        <div class="row align-items-center">
                            <div class="col-lg-4 col-md-6">
                                <div class="form-group mb-0">
                                    <label>Debit Note ID</label>
                                    <input type="text" name="return_no" class="form-control" placeholder="Search ID..." value="{{ request('return_no') }}">
                                </div>
                            </div>
                            <div class="col-lg-4 col-md-6">
                                <div class="form-group mb-0">
                                    <label>Vendor Name</label>
                                    <select name="vendor_id" class="form-control select">
                                        <option value="">All Vendors</option>
                                        @foreach($vendors as $vendor)
                                            <option value="{{ $vendor->id }}" {{ request('vendor_id') == $vendor->id ? 'selected' : '' }}>
                                                {{ $vendor->name }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                            <div class="col-lg-4 col-md-12 mt-3 mt-lg-0 d-flex gap-2">
                                <button type="submit" class="btn btn-primary w-100">
                                    <i class="fa fa-search me-2"></i>Search
                                </button>
                                <a href="{{ url('debit-notes') }}" class="btn btn-light w-100 border">
                                    Reset
                                </a>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
            <div class="row">
                <div class="col-sm-12">
                    <div class="card-table">
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-stripped table-hover datatable">
                                    <thead class="thead-light">
                                        <tr>
                                            <th>#</th>
                                            <th>Debit Notes ID</th>
                                            <th>Vendor</th>
                                            <th>Amount</th>
                                            <th>Created On</th>
                                            <th>Status</th>
                                            <th class="no-sort text-end">Action</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @forelse ($debit_notes as $note)
                                            <tr>
                                                <td>{{ $loop->iteration }}</td>
                                                <td>
                                                    <a href="#" class="invoice-link fw-bold text-primary">{{ $note->return_no }}</a>
                                                </td>
                                                <td>
                                                    <h2 class="table-avatar">
                                                        <a href="#" class="avatar avatar-sm me-2">
                                                            <img class="avatar-img rounded-circle" src="{{ asset('assets/img/profiles/avatar-01.jpg') }}" alt="User">
                                                        </a>
                                                        <a href="#">
                                                            {{ $note->vendor->name ?? 'Deleted Vendor' }} 
                                                            <span>{{ $note->vendor->phone ?? '' }}</span>
                                                        </a>
                                                    </h2>
                                                </td>
                                                <td class="text-dark fw-bold">
                                                    {{ number_format($note->amount, 2) }}
                                                </td>
                                                <td>{{ $note->created_at->format('d M Y') }}</td>
                                                <td>
                                                    <span class="badge bg-success-light">Active</span>
                                                </td>
                                                <td class="text-end">
                                                    <div class="dropdown dropdown-action">
                                                        <a href="#" class="btn-action-icon" data-bs-toggle="dropdown" aria-expanded="false">
                                                            <i class="fas fa-ellipsis-v"></i>
                                                        </a>
                                                        <div class="dropdown-menu dropdown-menu-right shadow border-0">
                                                            <a class="dropdown-item" href="#"><i class="far fa-edit me-2 text-info"></i>Edit</a>
                                                            <a class="dropdown-item" href="javascript:void(0);" data-bs-toggle="modal" data-bs-target="#delete_modal"><i class="far fa-trash-alt me-2 text-danger"></i>Delete</a>
                                                            <a class="dropdown-item" href="#"><i class="far fa-eye me-2 text-success"></i>View Details</a>
                                                        </div>
                                                    </div>
                                                </td>
                                            </tr>
                                        @empty
                                            <tr>
                                                <td colspan="7" class="text-center py-5 text-muted">
                                                    No Debit Notes found matching your criteria.
                                                </td>
                                            </tr>
                                        @endforelse
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            </div>
    </div>
@endsection