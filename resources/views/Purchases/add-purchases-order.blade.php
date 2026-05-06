<?php $page = 'add-purchases-order'; ?>
@extends('layout.mainlayout')

@section('content')
@php
    $orderItems = old('items', [['product_id' => '', 'description' => '', 'quantity' => 1, 'rate' => 0]]);
@endphp

<div class="page-wrapper">
    <div class="content container-fluid">
        <div class="page-header">
            <div class="row align-items-center">
                <div class="col-lg-8">
                    <h3 class="page-title">New Purchase Order</h3>
                    <p class="text-muted mb-0">Use this only when goods are not fully received yet, or when the business wants to follow the full order to receipt to payment supply chain.</p>
                </div>
                <div class="col-lg-4 text-lg-end mt-3 mt-lg-0">
                    <a href="{{ route('purchases.create') }}" class="btn btn-outline-primary">
                        <i class="fe fe-file-text me-1"></i> Goods Already Received? Use Direct Purchase
                    </a>
                </div>
            </div>
        </div>

        @if($errors->any())
            <div class="alert alert-danger">
                <ul class="mb-0">
                    @foreach($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form action="{{ route('purchase-orders.store') }}" method="POST" id="purchase-order-form">
            @csrf
            <div class="row">
                <div class="col-xl-8">
                    <div class="card border-0 shadow-sm">
                        <div class="card-body">
                            <div class="row g-3">
                                <div class="col-md-4">
                                    <label class="form-label">Purchase Order ID</label>
                                    <input type="text" class="form-control" name="purchase_id" value="{{ old('purchase_id') }}" placeholder="Auto-generated if empty">
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label">Supplier <span class="text-danger">*</span></label>
                                    <select class="form-select" name="supplier_id" required>
                                        <option value="">Select supplier</option>
                                        @foreach(($suppliers ?? collect()) as $supplier)
                                            <option value="{{ $supplier->id }}" @selected((string) old('supplier_id') === (string) $supplier->id)>
                                                {{ $supplier->name }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label">Order Date <span class="text-danger">*</span></label>
                                    <input type="date" class="form-control" name="purchase_date" value="{{ old('purchase_date', now()->toDateString()) }}" required>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Reference</label>
                                    <input type="text" class="form-control" name="reference_no" value="{{ old('reference_no') }}" placeholder="Supplier quote, email, or internal reference">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Legacy Vendor Link</label>
                                    <select class="form-select" name="vendor_id">
                                        <option value="">Optional vendor mapping</option>
                                        @foreach(($vendors ?? collect()) as $vendor)
                                            <option value="{{ $vendor->id }}" @selected((string) old('vendor_id') === (string) $vendor->id)>
                                                {{ $vendor->name }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-12">
                                    <label class="form-label">Notes</label>
                                    <textarea name="notes" rows="3" class="form-control" placeholder="Delivery expectations, supplier commitments, or internal instructions">{{ old('notes') }}</textarea>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="card border-0 shadow-sm">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <div>
                                    <h5 class="mb-1">Order Items</h5>
                                    <p class="text-muted mb-0">These lines stay outstanding until received through a Goods Received Note.</p>
                                </div>
                                <button type="button" class="btn btn-outline-primary btn-sm" id="add-item-row">
                                    <i class="fe fe-plus me-1"></i> Add Item
                                </button>
                            </div>

                            <div class="table-responsive">
                                <table class="table align-middle" id="po-items-table">
                                    <thead class="table-light">
                                        <tr>
                                            <th style="min-width: 220px;">Product</th>
                                            <th style="min-width: 220px;">Description</th>
                                            <th class="text-end" style="min-width: 120px;">Qty</th>
                                            <th class="text-end" style="min-width: 140px;">Rate</th>
                                            <th class="text-end" style="min-width: 150px;">Line Total</th>
                                            <th class="text-center" style="width: 70px;">Remove</th>
                                        </tr>
                                    </thead>
                                    <tbody id="po-items-body">
                                        @foreach($orderItems as $index => $item)
                                            <tr class="po-item-row">
                                                <td>
                                                    <select class="form-select product-select" name="items[{{ $index }}][product_id]" required>
                                                        <option value="">Select product</option>
                                                        @foreach(($products ?? collect()) as $product)
                                                            <option value="{{ $product->id }}" data-name="{{ $product->name }}" @selected((string) ($item['product_id'] ?? '') === (string) $product->id)>
                                                                {{ $product->name }}
                                                            </option>
                                                        @endforeach
                                                    </select>
                                                </td>
                                                <td>
                                                    <input type="text" class="form-control description-input" name="items[{{ $index }}][description]" value="{{ $item['description'] ?? '' }}" placeholder="Optional line note">
                                                </td>
                                                <td>
                                                    <input type="number" step="0.01" min="0.01" class="form-control text-end quantity-input" name="items[{{ $index }}][quantity]" value="{{ $item['quantity'] ?? 1 }}" required>
                                                </td>
                                                <td>
                                                    <input type="number" step="0.01" min="0" class="form-control text-end rate-input" name="items[{{ $index }}][rate]" value="{{ $item['rate'] ?? 0 }}" required>
                                                </td>
                                                <td class="text-end fw-semibold line-total-cell">0.00</td>
                                                <td class="text-center">
                                                    <button type="button" class="btn btn-sm btn-outline-danger remove-item-row">&times;</button>
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-xl-4">
                    <div class="card border-0 shadow-sm">
                        <div class="card-body">
                            <h5 class="mb-3">Order Summary</h5>
                            <div class="border rounded-3 p-3 mb-3 bg-light">
                                <div class="d-flex justify-content-between mb-2">
                                    <span class="text-muted">Line items</span>
                                    <strong id="summary-lines">0</strong>
                                </div>
                                <div class="d-flex justify-content-between mb-2">
                                    <span class="text-muted">Ordered quantity</span>
                                    <strong id="summary-qty">0.00</strong>
                                </div>
                                <div class="d-flex justify-content-between">
                                    <span class="text-muted">Order value</span>
                                    <strong id="summary-total">0.00</strong>
                                </div>
                            </div>

                            <div class="alert alert-info mb-3">
                                Inventory will stay unchanged until these items are received from the supplier through the GRN flow.
                            </div>

                            <div class="alert alert-warning mb-0">
                                If the supplier delivers only part of the order, the remaining quantity stays open on this purchase order and can be received later.
                            </div>
                        </div>
                    </div>

                    <div class="d-grid gap-2">
                        <a href="{{ route('purchase-orders') }}" class="btn btn-outline-secondary">Cancel</a>
                        <button type="submit" class="btn btn-primary">Save Purchase Order</button>
                    </div>
                </div>
            </div>
        </form>
    </div>
</div>

@push('scripts')
<script>
(function () {
    const products = @json(($products ?? collect())->map(fn ($product) => ['id' => $product->id, 'name' => $product->name])->values());
    const tbody = document.getElementById('po-items-body');
    const addButton = document.getElementById('add-item-row');
    let rowIndex = {{ count($orderItems) }};

    function productOptions(selectedId = '') {
        let options = '<option value="">Select product</option>';
        products.forEach((product) => {
            const selected = String(product.id) === String(selectedId) ? 'selected' : '';
            options += `<option value="${product.id}" data-name="${product.name}" ${selected}>${product.name}</option>`;
        });
        return options;
    }

    function buildRow(index) {
        return `<tr class="po-item-row">
            <td>
                <select class="form-select product-select" name="items[${index}][product_id]" required>
                    ${productOptions()}
                </select>
            </td>
            <td><input type="text" class="form-control description-input" name="items[${index}][description]" placeholder="Optional line note"></td>
            <td><input type="number" step="0.01" min="0.01" class="form-control text-end quantity-input" name="items[${index}][quantity]" value="1" required></td>
            <td><input type="number" step="0.01" min="0" class="form-control text-end rate-input" name="items[${index}][rate]" value="0" required></td>
            <td class="text-end fw-semibold line-total-cell">0.00</td>
            <td class="text-center"><button type="button" class="btn btn-sm btn-outline-danger remove-item-row">&times;</button></td>
        </tr>`;
    }

    function calculateTotals() {
        let lineCount = 0;
        let totalQty = 0;
        let grandTotal = 0;

        tbody.querySelectorAll('.po-item-row').forEach((row) => {
            const qty = parseFloat(row.querySelector('.quantity-input')?.value || 0);
            const rate = parseFloat(row.querySelector('.rate-input')?.value || 0);
            const lineTotal = qty * rate;

            row.querySelector('.line-total-cell').textContent = lineTotal.toFixed(2);

            if (row.querySelector('.product-select')?.value) {
                lineCount += 1;
            }
            totalQty += qty;
            grandTotal += lineTotal;
        });

        document.getElementById('summary-lines').textContent = String(lineCount);
        document.getElementById('summary-qty').textContent = totalQty.toFixed(2);
        document.getElementById('summary-total').textContent = grandTotal.toFixed(2);
    }

    addButton.addEventListener('click', function () {
        tbody.insertAdjacentHTML('beforeend', buildRow(rowIndex++));
        calculateTotals();
    });

    tbody.addEventListener('click', function (event) {
        if (!event.target.classList.contains('remove-item-row')) {
            return;
        }

        if (tbody.querySelectorAll('.po-item-row').length === 1) {
            return;
        }

        event.target.closest('tr').remove();
        calculateTotals();
    });

    tbody.addEventListener('change', function (event) {
        if (event.target.classList.contains('product-select')) {
            const selectedOption = event.target.options[event.target.selectedIndex];
            const row = event.target.closest('tr');
            const description = row.querySelector('.description-input');
            if (selectedOption?.dataset?.name && !description.value.trim()) {
                description.value = selectedOption.dataset.name;
            }
        }

        calculateTotals();
    });

    tbody.addEventListener('input', calculateTotals);
    calculateTotals();
})();
</script>
@endpush
@endsection
