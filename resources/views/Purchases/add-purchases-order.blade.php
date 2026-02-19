<?php $page = 'add-purchases-order'; ?>
@extends('layout.mainlayout')
@section('content')
<div class="page-wrapper">
    <div class="content container-fluid">
        <div class="card mb-0">
            <div class="card-body">
                <div class="content-page-header">
                    <h5>Add Purchase Order</h5>
                </div>

                <form action="{{ route('purchase-orders.store') }}" method="POST">
                    @csrf
                    <div class="row">
                        <div class="col-md-4 mb-3">
                            <label class="form-label">Purchase Order ID</label>
                            <input type="text" class="form-control" name="purchase_id" value="{{ old('purchase_id') }}" placeholder="Auto-generated if empty">
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label">Vendor</label>
                            <select class="form-control" name="vendor_id">
                                <option value="">Select Vendor</option>
                                @foreach(($vendors ?? collect()) as $vendor)
                                    <option value="{{ $vendor->id }}" {{ (string) old('vendor_id') === (string) $vendor->id ? 'selected' : '' }}>
                                        {{ $vendor->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label">Order Date</label>
                            <input type="date" class="form-control" name="purchase_date" value="{{ old('purchase_date', now()->toDateString()) }}">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Reference</label>
                            <input type="text" class="form-control" name="reference_no" value="{{ old('reference_no') }}">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Product</label>
                            <select class="form-control" name="product_id">
                                <option value="">Select Product</option>
                                @foreach(($products ?? collect()) as $product)
                                    <option value="{{ $product->id }}" {{ (string) old('product_id') === (string) $product->id ? 'selected' : '' }}>
                                        {{ $product->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-3 mb-3">
                            <label class="form-label">Quantity</label>
                            <input type="number" step="0.01" min="0" class="form-control" name="quantity" value="{{ old('quantity', 1) }}">
                        </div>
                        <div class="col-md-3 mb-3">
                            <label class="form-label">Rate</label>
                            <input type="number" step="0.01" min="0" class="form-control" name="rate" value="{{ old('rate', 0) }}">
                        </div>
                        <div class="col-md-12 mb-3">
                            <label class="form-label">Notes</label>
                            <textarea name="notes" rows="3" class="form-control">{{ old('notes') }}</textarea>
                        </div>
                    </div>
                    <div class="text-end">
                        <a href="{{ route('purchase-orders') }}" class="btn btn-primary cancel me-2">Cancel</a>
                        <button type="submit" class="btn btn-primary">Save Order</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
