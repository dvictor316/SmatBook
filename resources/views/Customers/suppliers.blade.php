<?php $page = 'suppliers'; ?>
@extends('layout.mainlayout')
@section('content')
    <div class="page-wrapper">
        <div class="content container-fluid">
            @component('components.page-header')
                @slot('title')
                    Suppliers
                @endslot
            @endcomponent

            <div class="row mb-3">
                <div class="col-sm-12 text-end">
                    <button type="button" class="btn btn-outline-primary me-2" data-bs-toggle="modal" data-bs-target="#importSuppliersModal">
                        Import Suppliers
                    </button>
                    <a href="{{ route('suppliers.import.template') }}" class="btn btn-outline-secondary me-2">
                        Download CSV Template
                    </a>
                    <a href="{{ route('suppliers.create') }}" class="btn btn-primary">Add New Supplier</a>
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
                                            <th class="no-sort">Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach ($suppliers as $supplier)
                                            <tr>
                                                <td>{{ $supplier->id }}</td>
                                                <td>
                                                    <h2 class="table-avatar">
                                                        <span class="avatar avatar-md me-2 bg-light text-dark rounded-circle d-inline-flex align-items-center justify-content-center">
                                                            {{ strtoupper(substr($supplier->name ?? $supplier->supplier_name ?? $supplier->company_name ?? 'S', 0, 1)) }}
                                                        </span>
                                                        <span>{{ $supplier->name ?? $supplier->supplier_name ?? $supplier->company_name }}</span>
                                                        @if(!empty($supplier->email))
                                                            <span>{{ $supplier->email }}</span>
                                                        @endif
                                                    </h2>
                                                </td>
                                                <td>{{ $supplier->phone ?? '-' }}</td>
                                                <td>{{ optional($supplier->created_at)->format('M d, Y') ?? 'N/A' }}</td>
                                                <td class="d-flex align-items-center">
                                                    <a href="{{ route('suppliers.edit', $supplier->id) }}" class="btn btn-greys me-2">
                                                        <i class="far fa-edit me-1"></i> Edit
                                                    </a>
                                                    <form action="{{ route('suppliers.destroy', $supplier->id) }}" method="POST" onsubmit="return confirm('Are you sure you want to delete this supplier?');">
                                                        @csrf
                                                        @method('DELETE')
                                                        <button type="submit" class="btn btn-danger">
                                                            <i class="far fa-trash-alt me-1"></i> Delete
                                                        </button>
                                                    </form>
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                            <div class="mt-3">
                                {{ $suppliers->links() }}
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="importSuppliersModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <form method="POST" action="{{ route('suppliers.import') }}" enctype="multipart/form-data">
                    @csrf
                    <div class="modal-header">
                        <h5 class="modal-title">Import Suppliers</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <p class="text-muted mb-3">Upload CSV or Excel files with supplier information.</p>
                        <div class="mb-3">
                            <label class="form-label">Spreadsheet File</label>
                            <input type="file" name="import_file" class="form-control" accept=".csv,.txt,.xls,.xlsx,text/csv,application/vnd.ms-excel,application/vnd.openxmlformats-officedocument.spreadsheetml.sheet" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label d-flex align-items-center gap-2">
                                <input type="checkbox" name="update_existing" value="1">
                                <span>Update existing suppliers when duplicates are found</span>
                            </label>
                            <small class="text-muted">When enabled, imports will update matching suppliers instead of skipping them.</small>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-light border" data-bs-dismiss="modal">Close</button>
                        <button type="submit" class="btn btn-primary">Import Suppliers</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection
