<?php $page = 'inventory'; ?>
@extends('layout.mainlayout')
@section('content')
    <div class="page-wrapper">
        <div class="content container-fluid">

            <div class="page-header mb-4">
                <div class="row align-items-center">
                    <div class="col">
                        <h4 class="fw-bold mb-1">Inventory Management</h4>
                        <p class="text-muted mb-0">Monitor and manage your current stock levels</p>
                    </div>
                    <div class="col-auto d-flex">

                        <button class="btn btn-primary me-2 shadow-sm" data-bs-toggle="modal" data-bs-target="#stock_adjustment_modal">
                            <i class="feather-plus-circle me-1"></i> Quick Adjustment
                        </button>

                        <a href="{{ url('add-products') }}" class="btn btn-outline-primary shadow-sm">
                            <i class="feather-plus me-1"></i> Add New Product
                        </a>
                    </div>
                </div>
            </div>

            <div class="row">
                <div class="col-sm-12">
                    <div class="card border-0 shadow-sm">
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-center table-hover datatable" id="inventoryTable">
                                    <thead class="thead-light">
                                        <tr>
                                            <th>#</th>
                                            <th>Item</th>
                                            <th>Code (SKU)</th>
                                            <th>Units</th>
                                            <th>Stock Level</th>
                                            <th>Selling Price</th>
                                            <th>Purchase Price</th>
                                            <th class="text-end">Action</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach ($products as $product)
                                            <tr>
                                                <td>{{ $product->id }}</td>
                                                <td class="fw-bold text-dark">{{ $product->name }}</td>
                                                <td class="text-muted">{{ $product->sku }}</td>
                                                <td>{{ $product->unit_type }}</td>
                                                <td>
                                                    <span class="badge {{ $product->stock <= 10 ? 'bg-danger-light text-danger' : 'bg-success-light text-success' }}">
                                                        {{ $product->stock }} {{ $product->unit_type }}
                                                    </span>
                                                </td>
                                                <td>{{ number_format($product->price, 2) }}</td>
                                                <td>{{ number_format($product->purchase_price, 2) }}</td>
                                                <td class="text-end">
                                                    <div class="d-flex justify-content-end align-items-center">
                                                        <a href="{{ route('inventory.history', $product->id) }}" class="btn btn-sm btn-white border me-2" title="View stock history">
                                                            <i class="far fa-eye me-1"></i> History
                                                        </a>
                                                        <div class="dropdown dropdown-action">
                                                            <a href="#" class="btn-action-icon" data-bs-toggle="dropdown"><i class="fas fa-ellipsis-v"></i></a>
                                                            <div class="dropdown-menu dropdown-menu-right">
                                                                <a class="dropdown-item" href="{{ url('edit-products/'.$product->id) }}">
                                                                    <i class="far fa-edit me-2"></i>Edit
                                                                </a>
                                                                <a class="dropdown-item text-danger" href="javascript:void(0);">
                                                                    <i class="far fa-trash-alt me-2"></i>Delete
                                                                </a>
                                                            </div>
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

    <div class="modal fade" id="stock_adjustment_modal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-0 shadow">
                <div class="modal-header bg-light">
                    <h5 class="modal-title">Manual Stock Adjustment</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form action="{{ url('inventory/adjust') }}" method="POST">
                    @csrf
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label fw-semibold">Select Product</label>
                            <select name="product_id" class="form-control" required>
                                <option value="">-- Choose Product --</option>
                                @foreach($products as $product)
                                    <option value="{{ $product->id }}">{{ $product->name }} (Current Stock: {{ $product->stock }})</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label fw-semibold">Action Type</label>
                                <select name="type" class="form-control" required>
                                    <option value="in">Stock In (+)</option>
                                    <option value="out">Stock Out (-)</option>
                                </select>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label fw-semibold">Quantity</label>
                                <input type="number" name="quantity" class="form-control" min="1" placeholder="e.g. 10" required>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer bg-light">
                        <button type="button" class="btn btn-secondary px-4" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary px-4">Save Movement</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <style>
        .bg-danger-light { background-color: #fee2e2; border: 1px solid #fecaca; }
        .bg-success-light { background-color: #dcfce7; border: 1px solid #bbf7d0; }
        .btn-white { background-color: #fff; color: #334155; }
        .table thead th { font-size: 11px; text-transform: uppercase; color: #64748b; font-weight: 600; }
    </style>
@endsection