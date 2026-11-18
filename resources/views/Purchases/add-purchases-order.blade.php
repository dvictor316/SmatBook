<?php $page = 'add-purchases-order'; ?>
@extends('layout.mainlayout')

@section('content')
@php
    $orderItems = old('items', isset($order)
        ? $order->items->map(fn ($item) => [
            'product_id' => $item->product_id,
            'description' => $item->description ?? '',
            'quantity' => $item->qty,
            'rate' => $item->unit_price,
        ])->all()
        : [['product_id' => '', 'description' => '', 'quantity' => 1, 'rate' => 0]]);
    $productsData = ($products ?? collect())->map(fn ($p) => [
        'id'   => (int) $p->id,
        'name' => (string) ($p->name ?? ''),
        'rate' => (float) ($p->purchase_rate ?? $p->purchase_price ?? $p->price ?? 0),
    ])->values();
@endphp

<style>
    .po-workspace { display: grid; grid-template-columns: minmax(0, 1fr) 320px; gap: 18px; align-items: start; }
    .po-panel { background: #fff; border: 1px solid #e5edf7; border-radius: 12px; box-shadow: 0 10px 26px rgba(15,23,42,.05); overflow: hidden; }
    .po-panel-head { padding: 16px 18px; border-bottom: 1px solid #edf2f7; background: linear-gradient(180deg,#ffffff 0%,#f8fbff 100%); }
    .po-panel-body { padding: 18px; }
    .po-items-table th { font-size: 12px; color: #475569; text-transform: uppercase; letter-spacing: .03em; white-space: nowrap; }
    .po-items-table td { min-width: 130px; vertical-align: middle; }
    .po-items-table td.product-cell { min-width: 240px; }
    .po-total-row { display: flex; justify-content: space-between; gap: 12px; padding: 10px 0; border-bottom: 1px solid #eef2f7; }
    .po-total-row.total { border-bottom: 0; font-size: 18px; font-weight: 800; color: #0f172a; }
    @media (max-width: 1100px) { .po-workspace { grid-template-columns: 1fr; } }
</style>

<div class="page-wrapper">
    <div class="content container-fluid">
        <div class="page-header">
            <div class="row align-items-center">
                <div class="col-lg-8">
                    <h3 class="page-title">{{ isset($order) ? 'Edit Purchase Order' : 'New Purchase Order' }}</h3>
                    <p class="text-muted mb-0">Use this when goods are not yet received. Inventory stays unchanged until a Goods Received Note is created against this order.</p>
                </div>
                <div class="col-lg-4 text-lg-end mt-3 mt-lg-0">
                    <a href="{{ route('purchases.create') }}" class="btn btn-outline-primary">
                        <i class="fe fe-file-text me-1"></i> Goods Already Received? Direct Purchase
                    </a>
                </div>
            </div>
        </div>

        @if($errors->any())
            <div class="alert alert-danger alert-dismissible fade show">
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                <ul class="mb-0">
                    @foreach($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form action="{{ isset($order) ? route('purchase-orders.update', $order->id) : route('purchase-orders.store') }}" method="POST" id="po-form">
            @csrf
            @if(isset($order))
                @method('PUT')
            @endif
            <div class="po-workspace">

                {{-- LEFT: main form --}}
                <div>
                    <div class="po-panel mb-3">
                        <div class="po-panel-head">
                            <h5 class="mb-1">Order Details</h5>
                            <div class="text-muted small">Supplier, dates, and reference for this purchase order.</div>
                        </div>
                        <div class="po-panel-body">
                            <div class="row g-3">
                                <div class="col-md-4">
                                    <label class="form-label">Purchase Order ID</label>
                                    <input type="text" class="form-control" name="purchase_id" value="{{ old('purchase_id', $order->purchase_no ?? '') }}" placeholder="Auto-generated if empty">
                                    @error('purchase_id') <div class="text-danger small mt-1">{{ $message }}</div> @enderror
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label">Supplier <span class="text-danger">*</span></label>
                                    <div class="input-group">
                                        <select class="form-select" name="supplier_id" id="po-supplier-id" required>
                                            <option value="">Select supplier</option>
                                            @foreach(($suppliers ?? collect()) as $supplier)
                                                <option value="{{ $supplier->id }}" @selected((string) old('supplier_id', $order->supplier_id ?? '') === (string) $supplier->id)>
                                                    {{ $supplier->name ?: ($supplier->supplier_name ?: ($supplier->company_name ?: 'Supplier #' . $supplier->id)) }}
                                                </option>
                                            @endforeach
                                        </select>
                                        <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#poSupplierModal" title="Add supplier" aria-label="Add supplier without leaving this purchase order">
                                            <i class="fas fa-plus-circle" aria-hidden="true"></i>
                                        </button>
                                    </div>
                                    @error('supplier_id') <div class="text-danger small mt-1">{{ $message }}</div> @enderror
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label">Order Date <span class="text-danger">*</span></label>
                                    <input type="date" class="form-control" name="purchase_date" value="{{ old('purchase_date', isset($order) && $order->purchase_date ? \Carbon\Carbon::parse($order->purchase_date)->toDateString() : now()->toDateString()) }}" required>
                                    @error('purchase_date') <div class="text-danger small mt-1">{{ $message }}</div> @enderror
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Reference</label>
                                    <input type="text" class="form-control" name="reference_no" value="{{ old('reference_no', $order->reference_no ?? '') }}" placeholder="Supplier quote, email, or internal reference">
                                    @error('reference_no') <div class="text-danger small mt-1">{{ $message }}</div> @enderror
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Legacy Vendor Link</label>
                                    <select class="form-select" name="vendor_id">
                                        <option value="">Optional vendor mapping</option>
                                        @foreach(($vendors ?? collect()) as $vendor)
                                            <option value="{{ $vendor->id }}" @selected((string) old('vendor_id', $order->vendor_id ?? '') === (string) $vendor->id)>
                                                {{ $vendor->name }}
                                            </option>
                                        @endforeach
                                    </select>
                                    @error('vendor_id') <div class="text-danger small mt-1">{{ $message }}</div> @enderror
                                </div>
                                <div class="col-12">
                                    <label class="form-label">Notes</label>
                                    <textarea name="notes" rows="3" class="form-control" placeholder="Delivery expectations, supplier commitments, or internal instructions">{{ old('notes', $order->notes ?? '') }}</textarea>
                                    @error('notes') <div class="text-danger small mt-1">{{ $message }}</div> @enderror
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- Items table --}}
                    <div class="po-panel">
                        <div class="po-panel-head d-flex justify-content-between align-items-center">
                            <div>
                                <h5 class="mb-1">Order Items</h5>
                                <div class="text-muted small">Lines stay outstanding until received through a Goods Received Note.</div>
                            </div>
                            <button type="button" class="btn btn-outline-primary btn-sm" id="po-add-row">
                                <i class="fe fe-plus me-1"></i> Add Item
                            </button>
                        </div>
                        <div class="p-0">
                            <div class="table-responsive">
                                <table class="table align-middle po-items-table mb-0" id="po-items-table">
                                    <thead class="table-light">
                                        <tr>
                                            <th class="product-cell ps-3">Product</th>
                                            <th>Description</th>
                                            <th class="text-end">Qty</th>
                                            <th class="text-end">Cost Price</th>
                                            <th class="text-end pe-3">Line Total</th>
                                            <th class="text-center" style="width:56px;"></th>
                                        </tr>
                                    </thead>
                                    <tbody id="po-items-body">
                                        @foreach($orderItems as $index => $item)
                                            <tr class="po-item-row">
                                                <td class="product-cell ps-3">
                                                    <select class="form-select product-select" name="items[{{ $index }}][product_id]" required>
                                                        <option value="">Select product</option>
                                                        @foreach(($products ?? collect()) as $product)
                                                            <option value="{{ $product->id }}"
                                                                data-name="{{ $product->name }}"
                                                                data-rate="{{ (float) ($product->purchase_rate ?? $product->purchase_price ?? $product->price ?? 0) }}"
                                                                @selected((string) ($item['product_id'] ?? '') === (string) $product->id)>
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
                                                <td class="text-end fw-semibold line-total-cell pe-3">0.00</td>
                                                <td class="text-center">
                                                    <button type="button" class="btn btn-sm btn-outline-danger po-remove-row" aria-label="Remove item" title="Remove item"><i class="fe fe-trash-2" aria-hidden="true"></i></button>
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- RIGHT: summary sidebar --}}
                <aside>
                    <div class="po-panel mb-3">
                        <div class="po-panel-head">
                            <h6 class="mb-1">Order Summary</h6>
                            <div class="text-muted small">Calculated from item rows.</div>
                        </div>
                        <div class="po-panel-body">
                            <div class="po-total-row">
                                <span class="text-muted">Line items</span>
                                <strong><span id="summary-lines">0</span></strong>
                            </div>
                            <div class="po-total-row">
                                <span class="text-muted">Ordered qty</span>
                                <strong><span id="summary-qty">0.00</span></strong>
                            </div>
                            <div class="po-total-row total">
                                <span>Order value</span>
                                <span id="summary-total">0.00</span>
                            </div>
                        </div>
                    </div>

                    <div class="po-panel mb-3">
                        <div class="po-panel-body">
                            <div class="alert alert-info mb-2 py-2 small">
                                Inventory stays unchanged until items are received via the GRN flow.
                            </div>
                            <div class="alert alert-warning mb-0 py-2 small">
                                Partial deliveries leave remaining quantities open on this order for later receipt.
                            </div>
                        </div>
                    </div>

                    <div class="d-grid gap-2">
                        <button type="submit" class="btn btn-primary">
                            <i class="fe fe-check me-1"></i> {{ isset($order) ? 'Update Purchase Order' : 'Save Purchase Order' }}
                        </button>
                        <a href="{{ route('purchase-orders') }}" class="btn btn-outline-secondary">Cancel</a>
                    </div>
                </aside>

            </div>
        </form>
    </div>
</div>

<div class="modal fade" id="poSupplierModal" tabindex="-1" aria-labelledby="poSupplierModalTitle" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered"><div class="modal-content">
        <form id="poSupplierForm" action="{{ route('suppliers.store') }}" method="POST">
            @csrf
            <div class="modal-header">
                <h5 class="modal-title" id="poSupplierModalTitle">Add supplier</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p class="text-muted small">The purchase order and its items will stay open.</p>
                <div id="poSupplierErrors" class="alert alert-danger" role="alert" hidden></div>
                <div class="mb-3"><label for="poSupplierName" class="form-label">Supplier / business name *</label><input id="poSupplierName" name="name" class="form-control" required maxlength="191"></div>
                <div class="mb-3"><label for="poSupplierPhone" class="form-label">Phone</label><input id="poSupplierPhone" name="phone" type="tel" class="form-control" maxlength="191"></div>
                <div class="mb-3"><label for="poSupplierEmail" class="form-label">Email</label><input id="poSupplierEmail" name="email" type="email" class="form-control" maxlength="191"></div>
                <div><label for="poSupplierAddress" class="form-label">Address</label><textarea id="poSupplierAddress" name="address" class="form-control" maxlength="255" rows="2"></textarea></div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="submit" class="btn btn-primary">Save supplier &amp; continue order</button>
            </div>
        </form>
    </div></div>
</div>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const supplierForm = document.getElementById('poSupplierForm');
    const supplierModal = document.getElementById('poSupplierModal');
    const supplierSelect = document.getElementById('po-supplier-id');
    const supplierErrors = document.getElementById('poSupplierErrors');

    supplierForm?.addEventListener('submit', async function (event) {
        event.preventDefault();
        if (!supplierForm.reportValidity()) return;
        const submitButton = supplierForm.querySelector('[type="submit"]');
        submitButton.disabled = true;
        supplierErrors.hidden = true;

        try {
            const response = await fetch(supplierForm.action, {
                method: 'POST',
                body: new FormData(supplierForm),
                headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
            });
            const data = await response.json().catch(() => ({}));
            if (!response.ok) {
                throw new Error(Object.values(data.errors || {}).flat().join(' ') || data.message || 'Could not save supplier.');
            }
            if (!data.supplier?.id || !data.supplier?.name) {
                throw new Error('Could not confirm the new supplier. Please try again.');
            }

            supplierSelect.add(new Option(data.supplier.name, data.supplier.id, true, true));
            supplierSelect.dispatchEvent(new Event('change', { bubbles: true }));
            bootstrap.Modal.getOrCreateInstance(supplierModal).hide();
            supplierForm.reset();
        } catch (error) {
            supplierErrors.textContent = error.message || 'Could not save supplier. Your purchase order is still open.';
            supplierErrors.hidden = false;
        } finally {
            submitButton.disabled = false;
        }
    });

    const products = @json($productsData);
    const tbody    = document.getElementById('po-items-body');
    const addBtn   = document.getElementById('po-add-row');
    let rowIndex   = {{ count($orderItems) }};

    function initializeProductSelect(select) {
        if (!select || !window.jQuery || !window.jQuery.fn || !window.jQuery.fn.select2) { return; }
        const $select = window.jQuery(select);
        if ($select.hasClass('select2-hidden-accessible')) { return; }
        $select.select2({
            width: '100%',
            placeholder: 'Type product name...',
            allowClear: true,
            minimumResultsForSearch: 0,
            dropdownParent: window.jQuery(document.body)
        });
    }

    function initializeProductSelects(root) {
        (root || tbody).querySelectorAll('.product-select').forEach(initializeProductSelect);
    }

    function escapeHtml(value) {
        return String(value == null ? '' : value).replace(/[&<>"']/g, function (character) {
            return {'&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#039;'}[character];
        });
    }

    function productOptions(selectedId) {
        selectedId = selectedId || '';
        let html = '<option value="">Search product by name...</option>';
        products.forEach(function (p) {
            const sel = String(p.id) === String(selectedId) ? ' selected' : '';
            const name = escapeHtml(p.name);
            const rate = Number(p.rate || 0);
            html += '<option value="' + escapeHtml(p.id) + '" data-name="' + name + '" data-rate="' + escapeHtml(rate) + '"' + sel + '>' + name + ' — ₦' + rate.toLocaleString(undefined, {minimumFractionDigits: 2, maximumFractionDigits: 2}) + '</option>';
        });
        return html;
    }

    function buildRow(index) {
        return '<tr class="po-item-row">'
            + '<td class="product-cell ps-3"><select class="form-select product-select" name="items[' + index + '][product_id]" required>' + productOptions() + '</select></td>'
            + '<td><input type="text" class="form-control description-input" name="items[' + index + '][description]" placeholder="Optional line note"></td>'
            + '<td><input type="number" step="0.01" min="0.01" class="form-control text-end quantity-input" name="items[' + index + '][quantity]" value="1" required></td>'
            + '<td><input type="number" step="0.01" min="0" class="form-control text-end rate-input" name="items[' + index + '][rate]" value="0" required></td>'
            + '<td class="text-end fw-semibold line-total-cell pe-3">0.00</td>'
            + '<td class="text-center"><button type="button" class="btn btn-sm btn-outline-danger po-remove-row" aria-label="Remove item" title="Remove item"><i class="fe fe-trash-2" aria-hidden="true"></i></button></td>'
            + '</tr>';
    }

    function applyProductDefaults(row, forceRate) {
        var sel  = row.querySelector('.product-select');
        var opt  = sel ? sel.options[sel.selectedIndex] : null;
        var desc = row.querySelector('.description-input');
        var rate = row.querySelector('.rate-input');

        if (opt && opt.dataset.name && desc && !desc.value.trim()) {
            desc.value = opt.dataset.name;
        }
        if (opt && rate && (forceRate || !rate.value || parseFloat(rate.value) === 0)) {
            rate.value = parseFloat(opt.dataset.rate || 0).toFixed(2);
        }
    }

    function recalculate() {
        var lineCount = 0, totalQty = 0, grandTotal = 0;
        tbody.querySelectorAll('.po-item-row').forEach(function (row) {
            var qty  = parseFloat(row.querySelector('.quantity-input') ? row.querySelector('.quantity-input').value : 0) || 0;
            var rate = parseFloat(row.querySelector('.rate-input') ? row.querySelector('.rate-input').value : 0) || 0;
            var line = qty * rate;
            row.querySelector('.line-total-cell').textContent = window.formatCompactAmount(line);
            if (row.querySelector('.product-select') && row.querySelector('.product-select').value) { lineCount++; }
            totalQty   += qty;
            grandTotal += line;
        });
        document.getElementById('summary-lines').textContent = String(lineCount);
        document.getElementById('summary-qty').textContent   = totalQty.toFixed(2);
        document.getElementById('summary-total').textContent = window.formatCompactAmount(grandTotal);
    }

    addBtn.addEventListener('click', function () {
        tbody.insertAdjacentHTML('beforeend', buildRow(rowIndex++));
        initializeProductSelect(tbody.lastElementChild.querySelector('.product-select'));
        recalculate();
    });

    tbody.addEventListener('click', function (e) {
        const removeButton = e.target.closest('.po-remove-row');
        if (!removeButton || !tbody.contains(removeButton)) { return; }
        const row = removeButton.closest('.po-item-row');
        if (!row) { return; }

        const select = row.querySelector('.product-select');
        if (select && window.jQuery && window.jQuery.fn && window.jQuery.fn.select2 && window.jQuery(select).hasClass('select2-hidden-accessible')) {
            window.jQuery(select).select2('destroy');
        }
        row.remove();
        // Keep one editable row so a user can continue when they remove the last item.
        if (!tbody.querySelector('.po-item-row')) {
            tbody.insertAdjacentHTML('beforeend', buildRow(rowIndex++));
            initializeProductSelect(tbody.lastElementChild.querySelector('.product-select'));
        }
        recalculate();
    });

    tbody.addEventListener('change', function (e) {
        var row = e.target.closest('tr');
        if (!row) { return; }
        if (e.target.classList.contains('product-select')) { applyProductDefaults(row, true); }
        recalculate();
    });

    tbody.addEventListener('input', recalculate);

    tbody.querySelectorAll('.po-item-row').forEach(function (row) { applyProductDefaults(row, false); });
    initializeProductSelects(tbody);
    recalculate();
});
</script>
@endpush
@endsection
