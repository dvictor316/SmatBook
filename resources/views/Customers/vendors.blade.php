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
                    <button type="button" class="btn btn-outline-primary me-2" data-bs-toggle="modal" data-bs-target="#importVendorsModal">
                        Import Vendors
                    </button>
                    <a href="{{ route('vendors.import.template') }}" class="btn btn-outline-secondary me-2">
                        Download CSV Template
                    </a>
                    <a href="{{ route('vendors.create') }}" class="btn btn-primary">Add New Vendor</a>
                </div>
            </div>

            @component('components.search-filter')
            @endcomponent

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

    <div class="modal fade" id="importVendorsModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <form method="POST" action="{{ route('vendors.import') }}" enctype="multipart/form-data">
                    @csrf
                    <div class="modal-header">
                        <h5 class="modal-title">Import Vendors</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <p class="text-muted mb-3">Upload CSV or Excel files with vendor opening balances.</p>
                        <div class="mb-3">
                            <label class="form-label">Spreadsheet File</label>
                            <input type="file" name="import_file" class="form-control" accept=".csv,.txt,.xls,.xlsx,text/csv,application/vnd.ms-excel,application/vnd.openxmlformats-officedocument.spreadsheetml.sheet" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label d-flex align-items-center gap-2">
                                <input type="checkbox" name="update_existing" value="1">
                                <span>Update existing vendors when duplicates are found</span>
                            </label>
                            <small class="text-muted">When enabled, imports will update matching vendors instead of skipping them.</small>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-light border" data-bs-dismiss="modal">Close</button>
                        <button type="submit" class="btn btn-primary">Import Vendors</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection
