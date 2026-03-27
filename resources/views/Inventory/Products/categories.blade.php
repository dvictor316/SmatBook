<?php $page = 'categories'; ?>
@extends('layout.mainlayout')

@section('content')
    <div class="page-wrapper">
        <div class="content container-fluid">

            {{-- Page Header --}}
            <div class="page-header no-print">
                <div class="row align-items-center">
                    <div class="col">
                        <h3 class="page-title">Product Categories</h3>
                        <ul class="breadcrumb">
                            <li class="breadcrumb-item"><a href="{{url('index')}}">Dashboard</a></li>
                            <li class="breadcrumb-item active">Inventory</li>
                        </ul>
                    </div>
                    <div class="col-auto d-flex">
                        <a href="javascript:void(0);" class="btn btn-primary me-2" data-bs-toggle="modal" data-bs-target="#addCategory">
                            <i class="fas fa-plus me-2"></i>Add New Category
                        </a>
                        {{-- Trigger for Print --}}
                        <button onclick="window.print()" class="btn btn-white text-black-50 shadow-sm">
                            <i class="fas fa-print"></i>
                        </button>
                    </div>
                </div>
            </div>

            {{-- Success Messages --}}
            @if(session('success'))
                <div class="alert alert-success alert-dismissible fade show border-0 shadow-sm" role="alert">
                    <i class="fas fa-check-circle me-2"></i>{{ session('success') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            @endif

            {{-- Table Section --}}
            <div class="row">
                <div class="col-sm-12">
                    <div class="card shadow-sm border-0">
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-center table-hover datatable" id="categoryTable">
                                    <thead class="thead-light">
                                        <tr>
                                            <th style="width: 50px;">#</th>
                                            <th>Category Name</th>
                                            <th>Description</th>
                                            <th>Items Linked</th>
                                            <th>Added Date</th>
                                            <th class="no-print">Status</th>
                                            <th class="no-sort text-end no-print">Action</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @forelse ($categories as $category)
                                            <tr>
                                                <td>{{ $loop->iteration }}</td>
                                                <td>
                                                    <span class="fw-bold text-dark">
                                                        {{ data_get($category, 'name') }}
                                                    </span>
                                                </td>
                                                <td>
                                                    <div class="description-output">
                                                        {{-- Direct property check for Object or Model --}}
                                                        @if(!empty($category->description))
                                                            {{ $category->description }}
                                                        @else
                                                            <span class="text-muted">No description provided</span>
                                                        @endif
                                                    </div>
                                                </td>
                                                <td>
                                                    <span class="badge rounded-pill bg-info-light">
                                                        {{ data_get($category, 'products_count', 0) }} Products
                                                    </span>
                                                </td>
                                                <td>
                                                    @php $date = data_get($category, 'created_at'); @endphp
                                                    {{ $date ? \Carbon\Carbon::parse($date)->format('d M Y') : 'N/A' }}
                                                </td>
                                                <td class="no-print">
                                                    <div class="status-toggle">
                                                        <input id="status_{{ data_get($category, 'id') }}" class="check" type="checkbox" {{ data_get($category, 'status') ? 'checked' : '' }}>
                                                        <label for="status_{{ data_get($category, 'id') }}" class="checktoggle checkbox-bg">checkbox</label>
                                                    </div>
                                                </td>
                                                <td class="text-end no-print">
                                                    <div class="d-flex justify-content-end">
                                                        <a class="btn btn-sm btn-white text-success me-2 shadow-sm" href="javascript:void(0);" 
                                                           data-bs-toggle="modal" data-bs-target="#editCategory{{ data_get($category, 'id') }}">
                                                            <i class="far fa-edit"></i>
                                                        </a>
                                                        <form action="{{ route('categories.clear-products', data_get($category, 'id')) }}" method="POST" onsubmit="return confirm('Delete all products in this category and reset their stock?')">
                                                            @csrf
                                                            <button type="submit" class="btn btn-sm btn-white text-warning me-2 shadow-sm">
                                                                <i class="fas fa-broom"></i>
                                                            </button>
                                                        </form>
                                                        
                                                        <form action="{{ route('categories.destroy', data_get($category, 'id')) }}" method="POST" onsubmit="return confirm('Are you sure?')">
                                                            @csrf @method('DELETE')
                                                            <button type="submit" class="btn btn-sm btn-white text-danger shadow-sm">
                                                                <i class="far fa-trash-alt"></i>
                                                            </button>
                                                        </form>
                                                    </div>
                                                </td>
                                            </tr>

                                            {{-- Edit Category Modal (Nested to ensure data consistency) --}}
                                            <div class="modal fade" id="editCategory{{ data_get($category, 'id') }}" tabindex="-1" aria-hidden="true">
                                                <div class="modal-dialog modal-dialog-centered">
                                                    <div class="modal-content border-0">
                                                        <form action="{{ route('categories.update', data_get($category, 'id')) }}" method="POST">
                                                            @csrf @method('PUT')
                                                            <div class="modal-header bg-light">
                                                                <h5 class="modal-title">Edit Category</h5>
                                                                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                                            </div>
                                                            <div class="modal-body">
                                                                <div class="mb-3">
                                                                    <label class="form-label">Category Name</label>
                                                                    <input type="text" name="name" class="form-control" value="{{ data_get($category, 'name') }}" required>
                                                                </div>
                                                                <div class="mb-3">
                                                                    <label class="form-label">Description</label>
                                                                    <textarea name="description" class="form-control" rows="3">{{ data_get($category, 'description') }}</textarea>
                                                                </div>
                                                                <div class="mb-3">
                                                                    <label class="form-label">Status</label>
                                                                    <select name="status" class="form-select">
                                                                        <option value="1" {{ data_get($category, 'status') == 1 ? 'selected' : '' }}>Active</option>
                                                                        <option value="0" {{ data_get($category, 'status') == 0 ? 'selected' : '' }}>Inactive</option>
                                                                    </select>
                                                                </div>
                                                            </div>
                                                            <div class="modal-footer">
                                                                <button type="button" class="btn btn-white" data-bs-dismiss="modal">Cancel</button>
                                                                <button type="submit" class="btn btn-primary">Save Changes</button>
                                                            </div>
                                                        </form>
                                                    </div>
                                                </div>
                                            </div>
                                        @empty
                                            <tr>
                                                <td colspan="7" class="text-center py-5 text-muted">No records found in the "smat" database.</td>
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

    {{-- Add Category Modal --}}
    <div class="modal fade" id="addCategory" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-0">
                <form action="{{ route('categories.store') }}" method="POST">
                    @csrf
                    <div class="modal-header bg-primary text-white">
                        <h5 class="modal-title">New Category</h5>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label">Category Name <span class="text-danger">*</span></label>
                            <input type="text" name="name" class="form-control" placeholder="e.g. Wood work" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Description</label>
                            <textarea name="description" class="form-control" rows="3" placeholder="Category details..."></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-white" data-bs-dismiss="modal">Close</button>
                        <button type="submit" class="btn btn-primary">Create</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    {{-- Styles for clean UI and Print formatting --}}
    <style>
        .bg-info-light { background-color: rgba(0, 209, 209, 0.1); color: #00d1d1; font-weight: 600; }
        .btn-white { background: #fff; border: 1px solid #e2e8f0; }
        
        .description-output {
            max-width: 300px;
            white-space: normal;
            word-wrap: break-word;
            font-size: 0.85rem;
            line-height: 1.4;
        }

        @media print {
            .no-print, .btn, .status-toggle, .dataTables_filter, .dataTables_info, .dataTables_paginate, .dataTables_length {
                display: none !important;
            }
            .page-wrapper { margin: 0; padding: 0; }
            .card { border: none !important; }
            .table { width: 100% !important; border: 1px solid #dee2e6 !important; }
            th { background-color: #f8f9fa !important; color: #000 !important; }
            .description-output { max-width: none; color: #000; }
        }
    </style>
@endsection
