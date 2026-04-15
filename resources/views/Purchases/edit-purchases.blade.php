<?php $page = 'edit-purchases'; ?>
@extends('layout.mainlayout')

@section('content')
<div class="page-wrapper">
    <div class="content container-fluid">
    <div class="container-fluid py-4">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h1>Edit Purchase</h1>
            <a href="{{ route('purchases.index') }}" class="btn btn-secondary">
                <i class="fas fa-arrow-left"></i> Back to List
            </a>
        </div>

        @if(session('success'))
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                {{ session('success') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        @endif

        @if(session('error'))
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                {{ session('error') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        @endif

        @if($errors->any())
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <div class="fw-semibold mb-2">Please fix the highlighted purchase errors.</div>
                <ul class="mb-0">
                    @foreach($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        @endif

        @php
            $existingItems = $purchase->items->map(fn($item) => [
                'product_id'   => $item->product_id,
                'product_name' => $item->product?->name ?? $item->product_name ?? '',
                'quantity'     => (float) ($item->qty ?? $item->quantity ?? 1),
                'unit'         => $item->unit ?? 'pcs',
                'rate'         => (float) ($item->unit_price ?? $item->rate ?? 0),
                'discount'     => (float) ($item->discount ?? 0),
                'tax_id'       => $item->tax_id ?? '',
            ]);
        @endphp

        <form action="{{ route('purchases.update', $purchase->id) }}" method="POST" enctype="multipart/form-data" id="purchaseForm" novalidate>
            @csrf
            @method('PUT')

            <!-- Purchase Details Section -->
            <div class="row mb-4">
                <div class="col-12">
                    <div class="card p-3">
                        <h5 class="card-title">Purchase Details</h5>
                        <div class="row g-3">
                            <!-- Purchase ID (read-only) -->
                            <div class="col-md-4">
                                <label for="purchase_id" class="form-label">Purchase ID</label>
                                <input type="text" id="purchase_id" name="purchase_id"
                                       value="{{ $purchase->purchase_no ?? $purchase->id }}"
                                       class="form-control" readonly>
                            </div>

                            <!-- Select Supplier -->
                            <div class="col-md-4">
                                <label for="supplier_id" class="form-label">Select Supplier *</label>
                                <div class="input-group">
                                    <select id="supplier_id" name="supplier_id" class="form-select @error('supplier_id') is-invalid @enderror" required>
                                        <option value="">Choose Supplier</option>
                                        @foreach($vendors as $vendor)
                                            <option value="{{ $vendor->id }}"
                                                {{ (int)($purchase->supplier_id ?? $purchase->vendor_id) === (int)$vendor->id ? 'selected' : '' }}>
                                                {{ $vendor->name ?? $vendor->supplier_name ?? $vendor->company_name ?? 'Supplier' }}
                                            </option>
                                        @endforeach
                                    </select>
                                    <a class="btn btn-primary" href="{{ route('suppliers.create') }}" title="Add Supplier">
                                        <i class="fas fa-plus-circle"></i>
                                    </a>
                                </div>
                                @error('supplier_id')
                                    <div class="invalid-feedback d-block">{{ $message }}</div>
                                @enderror
                            </div>

                            <!-- Purchase Date -->
                            <div class="col-md-4">
                                <label for="purchase_date" class="form-label">Purchase Date *</label>
                                <input type="date" id="purchase_date" name="purchase_date"
                                       value="{{ old('purchase_date', $purchase->purchase_date ?? $purchase->created_at?->format('Y-m-d') ?? date('Y-m-d')) }}"
                                       class="form-control @error('purchase_date') is-invalid @enderror" required>
                                @error('purchase_date')
                                    <div class="invalid-feedback d-block">{{ $message }}</div>
                                @enderror
                            </div>

                            <!-- Due Date -->
                            <div class="col-md-4">
                                <label for="due_date" class="form-label">Due Date</label>
                                <input type="date" id="due_date" name="due_date"
                                       value="{{ old('due_date', $purchase->due_date ?? '') }}"
                                       class="form-control @error('due_date') is-invalid @enderror">
                                @error('due_date')
                                    <div class="invalid-feedback d-block">{{ $message }}</div>
                                @enderror
                            </div>

                            <!-- Reference No -->
                            <div class="col-md-4">
                                <label for="reference_no" class="form-label">Reference No</label>
                                <input type="text" id="reference_no" name="reference_no"
                                       value="{{ old('reference_no', $purchase->reference_no ?? '') }}"
                                       class="form-control @error('reference_no') is-invalid @enderror"
                                       placeholder="Reference number">
                                @error('reference_no')
                                    <div class="invalid-feedback d-block">{{ $message }}</div>
                                @enderror
                            </div>

                            <!-- Supplier Invoice Serial No -->
                            <div class="col-md-4">
                                <label for="invoice_serial_no" class="form-label">Supplier Invoice Serial No</label>
                                <input type="text" id="invoice_serial_no" name="invoice_serial_no"
                                       value="{{ old('invoice_serial_no', $purchase->invoice_serial_no ?? '') }}"
                                       class="form-control @error('invoice_serial_no') is-invalid @enderror"
                                       placeholder="Supplier invoice serial number">
                                @error('invoice_serial_no')
                                    <div class="invalid-feedback d-block">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Products Selection & Table -->
            <div class="row mb-4">
                <div class="col-12">
                    <div class="card">
                        <div class="card-header d-flex justify-content-between align-items-center">
                            <h5 class="mb-0">Products</h5>
                            <button type="button" class="btn btn-sm btn-primary" id="addProductBtn">
                                <i class="fas fa-plus"></i> Add Product
                            </button>
                        </div>
                        <div class="card-body p-0">
                            <div class="table-responsive">
                                <table class="table table-hover mb-0" id="productsTable">
                                    <thead class="table-light">
                                        <tr>
                                            <th>Product / Service *</th>
                                            <th>Quantity *</th>
                                            <th>Unit</th>
                                            <th>Rate *</th>
                                            <th>Discount</th>
                                            <th>Tax</th>
                                            <th>Amount</th>
                                            <th>Action</th>
                                        </tr>
                                    </thead>
                                    <tbody id="productsTableBody"></tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Discount & Tax -->
            <div class="row mb-4">
                <div class="col-12">
                    <div class="card p-3">
                        <h5 class="card-title">Discount & Tax</h5>
                        <div class="row g-3 align-items-center">
                            <!-- Discount Type -->
                            <div class="col-md-4">
                                <label for="discount_type" class="form-label">Discount Type</label>
                                <select id="discount_type" name="discount_type" class="form-select">
                                    <option value="percentage" {{ old('discount_type', $purchase->discount_type ?? '') == 'percentage' ? 'selected' : '' }}>Percentage(%)</option>
                                    <option value="fixed" {{ old('discount_type', $purchase->discount_type ?? '') == 'fixed' ? 'selected' : '' }}>Fixed</option>
                                </select>
                            </div>

                            <!-- Discount Value -->
                            <div class="col-md-4">
                                <label for="discount_value" class="form-label">Discount</label>
                                <input type="number" id="discount_value" name="discount_value"
                                       value="{{ old('discount_value', $purchase->discount_value ?? 0) }}"
                                       class="form-control" step="0.01" min="0">
                            </div>

                            <!-- Tax Selection -->
                            <div class="col-md-4">
                                <label for="tax_id" class="form-label">Tax</label>
                                <select id="tax_id" name="tax_id" class="form-select">
                                    <option value="">Select Tax</option>
                                    @forelse($taxOptions as $tax)
                                        <option value="{{ $tax->id }}"
                                            {{ old('tax_id', $purchase->tax_id ?? '') == $tax->id ? 'selected' : '' }}>
                                            {{ $tax->description ?? $tax->code ?? 'Tax' }}@if(isset($tax->rate)) ({{ rtrim(rtrim(number_format((float) $tax->rate, 4, '.', ''), '0'), '.') }}%)@endif
                                        </option>
                                    @empty
                                        <option value="" disabled>No tax codes configured</option>
                                    @endforelse
                                </select>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Bank Details & Totals -->
            <div class="row mb-4">
                <div class="col-12">
                    <div class="card p-3">
                        <div class="row g-3">
                            <!-- Bank & Notes -->
                            <div class="col-md-6">
                                <h5>Bank Details</h5>

                                <!-- Select Bank -->
                                <div class="mb-3">
                                    <label for="bank_id" class="form-label">Select Bank</label>
                                    <div class="input-group">
                                        <select id="bank_id" name="bank_id" class="form-select">
                                            <option value="">Select Bank</option>
                                            @foreach($banks as $bank)
                                                <option value="{{ $bank->id }}"
                                                    {{ old('bank_id', $purchase->bank_id ?? '') == $bank->id ? 'selected' : '' }}>
                                                    {{ $bank->name }}
                                                </option>
                                            @endforeach
                                        </select>
                                        <a class="btn btn-primary" href="{{ route('bank-account') }}" title="Add Bank">
                                            <i class="fas fa-plus-circle"></i>
                                        </a>
                                    </div>
                                </div>

                                <!-- Notes -->
                                <div class="mb-3">
                                    <label for="notes" class="form-label">Notes</label>
                                    <textarea id="notes" name="notes" class="form-control" rows="3"
                                              placeholder="Enter Notes">{{ old('notes', $purchase->notes ?? '') }}</textarea>
                                </div>

                                <!-- Terms & Conditions -->
                                <div class="mb-3">
                                    <label for="terms_conditions" class="form-label">Terms & Conditions</label>
                                    <textarea id="terms_conditions" name="terms_conditions" class="form-control" rows="3"
                                              placeholder="Enter Terms and Conditions">{{ old('terms_conditions', $purchase->terms_conditions ?? '') }}</textarea>
                                </div>
                            </div>

                            <!-- Totals & Signature -->
                            <div class="col-md-6">
                                <h5>Totals & Signature</h5>
                                <div class="mb-3">
                                    <p>Taxable Amount: <strong id="taxableAmount">0.00</strong></p>
                                    <p>Discount: <strong id="totalDiscount">0.00</strong></p>
                                    <p>VAT: <strong id="vatAmount">0.00</strong></p>
                                    <div class="form-check form-switch align-items-center mb-2">
                                        <input type="hidden" name="round_off" value="0">
                                        <input class="form-check-input" type="checkbox" id="round_off" name="round_off"
                                               value="1"
                                               {{ old('round_off', $purchase->round_off ?? false) ? 'checked' : '' }}>
                                        <label class="form-check-label" for="round_off">Round Off</label>
                                        <span class="ms-2" id="roundOffAmount">0.00</span>
                                    </div>
                                    <hr>
                                    <h4>Total Amount: <span id="totalAmount">0.00</span></h4>
                                </div>

                                <!-- Signature Name -->
                                <div class="mb-3">
                                    <label for="signature_name" class="form-label">Signature Name</label>
                                    <input type="text" id="signature_name" name="signature_name"
                                           value="{{ old('signature_name', $purchase->signature_name ?? '') }}"
                                           class="form-control" placeholder="Enter Signature Name">
                                </div>

                                <!-- Signature Upload -->
                                <div class="mb-3">
                                    <label for="signature_image" class="form-label">Upload Signature</label>
                                    @if(!empty($purchase->signature_image))
                                        <div class="mb-2">
                                            <img src="{{ asset('storage/' . $purchase->signature_image) }}" alt="Current Signature" style="max-height:60px;">
                                            <small class="d-block text-muted">Upload a new file to replace the current signature.</small>
                                        </div>
                                    @endif
                                    <input type="file" id="signature_image" name="signature_image"
                                           class="form-control" accept="image/*">
                                    @error('signature_image')
                                        <div class="invalid-feedback d-block">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Submit Buttons -->
            <div class="row">
                <div class="col-12 d-flex justify-content-end gap-2">
                    <a href="{{ route('purchases.index') }}" class="btn btn-secondary">Cancel</a>
                    <button type="submit" class="btn btn-primary">Update Purchase</button>
                </div>
            </div>
        </form>
    </div>
    </div>
</div>

<script>
        document.addEventListener('DOMContentLoaded', function() {
            const products = @json($products);
            const initialRows = @json($existingItems);
            const currencySymbol = @json(\App\Support\GeoCurrency::currentSymbol());
            let productCounter = 0;

            const tableBody = document.getElementById('productsTableBody');
            const addBtn = document.getElementById('addProductBtn');

            function buildProductOptions() {
                const options = ['<option value="">Select Product</option>'];
                products.forEach((product) => {
                    const unit = product.unit ?? 'pcs';
                    const price = product.price ?? 0;
                    options.push(
                        `<option value="${product.id}" data-name="${escapeHtml(product.name)}" data-unit="${escapeHtml(unit)}" data-price="${price}">${escapeHtml(product.name)} - ${currencySymbol}${Number(price).toFixed(2)}</option>`
                    );
                });
                return options.join('');
            }

            function escapeHtml(value) {
                return String(value ?? '')
                    .replace(/&/g, '&amp;')
                    .replace(/</g, '&lt;')
                    .replace(/>/g, '&gt;')
                    .replace(/"/g, '&quot;')
                    .replace(/'/g, '&#39;');
            }

            function updateProductAmount(rowIndex) {
                const quantityField = document.querySelector(`input[name="products[${rowIndex}][quantity]"]`);
                const rateField = document.querySelector(`input[name="products[${rowIndex}][rate]"]`);
                const discountField = document.querySelector(`input[name="products[${rowIndex}][discount]"]`);
                const amountCell = document.getElementById(`amount_${rowIndex}`);

                if (!quantityField || !rateField || !discountField || !amountCell) {
                    return;
                }

                const quantity = parseFloat(quantityField.value) || 0;
                const rate = parseFloat(rateField.value) || 0;
                const discount = parseFloat(discountField.value) || 0;
                const amount = Math.max(0, (quantity * rate) - discount);

                amountCell.textContent = amount.toFixed(2);
                updateTotals();
            }

            function removeProductRow(rowId) {
                const row = document.getElementById(rowId);
                if (row) {
                    row.remove();
                }

                if (tableBody.children.length === 0) {
                    createEmptyRow();
                }

                updateTotals();
            }

            function createEmptyRow(seed = {}) {
                const rowId = `productRow_${productCounter}`;
                const rowIndex = productCounter;
                const normalizedSeed = {
                    product_id: seed.product_id ? String(seed.product_id) : '',
                    product_name: seed.product_name ?? '',
                    quantity: Number(seed.quantity ?? 1),
                    unit: seed.unit ?? '',
                    rate: Number(seed.rate ?? 0),
                    discount: Number(seed.discount ?? 0),
                    tax_id: seed.tax_id ? String(seed.tax_id) : '',
                };
                const row = document.createElement('tr');
                row.id = rowId;
                row.innerHTML = `
                    <td>
                        <select name="products[${rowIndex}][product_id]" class="form-select product-select" data-row="${rowIndex}">
                            ${buildProductOptions()}
                        </select>
                    </td>
                    <td>
                        <input type="number" name="products[${rowIndex}][quantity]" value="${normalizedSeed.quantity > 0 ? normalizedSeed.quantity : 1}" class="form-control quantity-input" min="0.01" step="0.01"
                               data-row="${rowIndex}" onchange="updateProductAmount(${rowIndex})" oninput="updateProductAmount(${rowIndex})">
                    </td>
                    <td>
                        <input type="text" name="products[${rowIndex}][unit]" value="${escapeHtml(normalizedSeed.unit)}" class="form-control unit-input" data-row="${rowIndex}">
                    </td>
                    <td>
                        <input type="number" name="products[${rowIndex}][rate]" value="${normalizedSeed.rate}" class="form-control rate-input" step="0.01" min="0"
                               data-row="${rowIndex}" onchange="updateProductAmount(${rowIndex})" oninput="updateProductAmount(${rowIndex})">
                    </td>
                    <td>
                        <input type="number" name="products[${rowIndex}][discount]" value="${normalizedSeed.discount}" class="form-control discount-input" step="0.01" min="0"
                               data-row="${rowIndex}" onchange="updateProductAmount(${rowIndex})" oninput="updateProductAmount(${rowIndex})">
                    </td>
                    <td>
                        <select name="products[${rowIndex}][tax_id]" class="form-select tax-select" data-row="${rowIndex}" onchange="updateProductAmount(${rowIndex})">
                            <option value="">No Tax</option>
                            @foreach($taxOptions as $tax)
                                <option value="{{ $tax->id }}">{{ $tax->description ?? $tax->code ?? 'Tax' }}@if(isset($tax->rate)) ({{ rtrim(rtrim(number_format((float) $tax->rate, 4, '.', ''), '0'), '.') }}%)@endif</option>
                            @endforeach
                        </select>
                    </td>
                    <td class="amount-cell" id="amount_${rowIndex}">0.00</td>
                    <td>
                        <button type="button" class="btn btn-sm btn-danger" onclick="removeProductRow('${rowId}')">Remove</button>
                    </td>
                `;

                tableBody.appendChild(row);
                productCounter += 1;
                bindRowEvents(row);

                const productSelect = row.querySelector('.product-select');
                const taxSelect = row.querySelector('.tax-select');

                if (productSelect && normalizedSeed.product_id) {
                    productSelect.value = normalizedSeed.product_id;
                }

                if (taxSelect && normalizedSeed.tax_id) {
                    taxSelect.value = normalizedSeed.tax_id;
                }

                if (normalizedSeed.product_id) {
                    syncSelectedProductDetails(row, normalizedSeed.product_name);
                } else {
                    updateProductAmount(rowIndex);
                }

                return row;
            }

            function syncSelectedProductDetails(row, seededProductName = '') {
                const productSelect = row.querySelector('.product-select');
                if (!productSelect) {
                    return;
                }

                const selectedOption = productSelect.options[productSelect.selectedIndex];
                const rowIndex = productSelect.getAttribute('data-row');
                let nameField = document.querySelector(`input[name="products[${rowIndex}][product_name]"]`);
                const unitInput = row.querySelector('.unit-input');
                const rateInput = row.querySelector('.rate-input');

                const unit = selectedOption?.getAttribute('data-unit') || '';
                const price = Number(selectedOption?.getAttribute('data-price') || 0);
                const productName = selectedOption?.getAttribute('data-name') || seededProductName || '';

                if (!nameField && productName) {
                    nameField = document.createElement('input');
                    nameField.type = 'hidden';
                    nameField.name = `products[${rowIndex}][product_name]`;
                    row.querySelector('td').appendChild(nameField);
                }

                if (nameField) {
                    nameField.value = productName;
                }

                if (unitInput && !String(unitInput.value || '').trim()) {
                    unitInput.value = unit;
                }

                if (rateInput && Number(rateInput.value || 0) <= 0) {
                    rateInput.value = price;
                }

                updateProductAmount(rowIndex);
            }

            function bindRowEvents(row) {
                const productSelect = row.querySelector('.product-select');
                if (!productSelect) {
                    return;
                }

                productSelect.addEventListener('change', function() {
                    syncSelectedProductDetails(row);

                    if (isLastRowFilled()) {
                        createEmptyRow();
                    }
                });
            }

            function isLastRowFilled() {
                const rows = Array.from(tableBody.querySelectorAll('tr'));
                if (rows.length === 0) {
                    return false;
                }
                const lastRow = rows[rows.length - 1];
                const productSelect = lastRow.querySelector('.product-select');
                return productSelect && productSelect.value;
            }

            addBtn.addEventListener('click', function() {
                createEmptyRow();
            });

            if (Array.isArray(initialRows) && initialRows.length > 0) {
                initialRows.forEach((row) => createEmptyRow(row));
                if (isLastRowFilled()) {
                    createEmptyRow();
                }
            } else {
                createEmptyRow();
            }

            updateTotals();

            // Expose helpers for inline handlers in the dynamic row template
            window.updateProductAmount = updateProductAmount;
            window.removeProductRow = removeProductRow;

            function updateTotals() {
                let taxableAmount = 0;
                let totalDiscount = 0;

                const quantityInputs = document.querySelectorAll('.quantity-input');
                const rateInputs = document.querySelectorAll('.rate-input');
                const discountInputs = document.querySelectorAll('.discount-input');

                for (let i = 0; i < quantityInputs.length; i++) {
                    const quantity = parseFloat(quantityInputs[i].value) || 0;
                    const rate = parseFloat(rateInputs[i].value) || 0;
                    const discount = parseFloat(discountInputs[i].value) || 0;

                    taxableAmount += quantity * rate;
                    totalDiscount += discount;
                }

                const discountType = document.getElementById('discount_type').value;
                const discountValue = parseFloat(document.getElementById('discount_value').value) || 0;
                let globalDiscount = 0;

                if (discountType === 'percentage') {
                    globalDiscount = (taxableAmount * discountValue) / 100;
                } else {
                    globalDiscount = discountValue;
                }

                const taxId = document.getElementById('tax_id').value;
                let taxRate = 0;
                @foreach($taxOptions as $tax)
                    if ("{{ $tax->id }}" === taxId) {
                        taxRate = {{ $tax->rate ?? 0 }};
                    }
                @endforeach

                const vatAmount = ((taxableAmount - totalDiscount - globalDiscount) * taxRate) / 100;

                const subtotal = taxableAmount - totalDiscount - globalDiscount;
                const roundOff = document.getElementById('round_off').checked;
                let roundOffAmount = 0;
                let totalAmount = subtotal + vatAmount;

                if (roundOff) {
                    totalAmount = Math.round(totalAmount);
                    roundOffAmount = totalAmount - (subtotal + vatAmount);
                }

                document.getElementById('taxableAmount').textContent = taxableAmount.toFixed(2);
                document.getElementById('totalDiscount').textContent = (totalDiscount + globalDiscount).toFixed(2);
                document.getElementById('vatAmount').textContent = vatAmount.toFixed(2);
                document.getElementById('roundOffAmount').textContent = roundOffAmount.toFixed(2);
                document.getElementById('totalAmount').textContent = totalAmount.toFixed(2);
            }

            document.getElementById('discount_type').addEventListener('change', updateTotals);
            document.getElementById('discount_value').addEventListener('input', updateTotals);
            document.getElementById('tax_id').addEventListener('change', updateTotals);
            document.getElementById('round_off').addEventListener('change', updateTotals);
        });
</script>
@endsection
