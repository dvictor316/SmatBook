<?php $page = 'edit-quotations'; ?>
@extends('layout.mainlayout')
@section('content')
@php
    $quotationItems = old('items');
    $issueDateValue = old('issue_date', !empty($quotation->issue_date) ? \Carbon\Carbon::parse($quotation->issue_date)->format('d-m-Y') : '');
    $expiryDateValue = old('expiry_date', !empty($quotation->expiry_date) ? \Carbon\Carbon::parse($quotation->expiry_date)->format('d-m-Y') : '');
    if (!is_array($quotationItems)) {
        $quotationItems = json_decode((string) ($quotation->items_json ?? '[]'), true);
    }
    if (!is_array($quotationItems) || empty($quotationItems)) {
        $quotationItems = [[
            'product_id' => '',
            'name' => '',
            'price_level' => 'retail',
            'qty' => 1,
            'rate' => '0.00',
            'discount' => 0,
            'tax' => 0,
            'amount' => 0,
        ]];
    }
@endphp
<div class="page-wrapper">
    <div class="content container-fluid">
        <div class="card mb-0">
            <div class="card-body">
                <div class="page-header">
                    <div class="content-page-header">
                        <h5>Edit Quotation</h5>
                    </div>
                </div>
                @if($errors->any())
                    <div class="alert alert-danger border-0 shadow-sm">
                        {{ $errors->first() }}
                    </div>
                @endif

                @if(empty($quotation))
                    <div class="alert alert-warning">No quotation found to edit.</div>
                @else
                    <form action="{{ route('quotations.update', $quotation->id) }}" method="POST" id="quotation-edit-form">
                        @csrf
                        @method('PUT')
                        <div class="row align-item-center">
                            <div class="col-lg-3 col-md-6 col-sm-12">
                                <div class="input-block mb-3">
                                    <label>Quotation ID</label>
                                    <input type="text" name="quotation_id" class="form-control" value="{{ old('quotation_id', $quotation->quotation_id) }}" required>
                                </div>
                            </div>

                            <div class="col-lg-3 col-md-6 col-sm-12">
                                <div class="input-block mb-3">
                                    <label>Customer</label>
                                    <select class="form-control customer-select select2" name="customer_id">
                                        <option value="">Walk-in Customer</option>
                                        @foreach(($customers ?? collect()) as $customer)
                                            <option value="{{ $customer->id }}" {{ (string) old('customer_id', $quotation->customer_id) === (string) $customer->id ? 'selected' : '' }}>
                                                {{ $customer->customer_name ?? $customer->name }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>

                            <div class="col-lg-3 col-md-6 col-sm-12">
                                <div class="input-block mb-3">
                                    <label>Issue Date</label>
                                    <div class="cal-icon cal-icon-info">
                                        <input type="text" name="issue_date" class="datetimepicker form-control" value="{{ $issueDateValue }}">
                                    </div>
                                </div>
                            </div>

                            <div class="col-lg-3 col-md-6 col-sm-12">
                                <div class="input-block mb-3">
                                    <label>Valid Until</label>
                                    <div class="cal-icon cal-icon-info">
                                        <input type="text" name="expiry_date" class="datetimepicker form-control" value="{{ $expiryDateValue }}">
                                    </div>
                                </div>
                            </div>

                            <div class="col-lg-4 col-md-6 col-sm-12">
                                <div class="input-block mb-3">
                                    <label>Status</label>
                                    @php $status = old('status', $quotation->status ?? 'Pending'); @endphp
                                    <select name="status" class="select">
                                        <option value="Pending" {{ $status === 'Pending' ? 'selected' : '' }}>Pending</option>
                                        <option value="Sent" {{ $status === 'Sent' ? 'selected' : '' }}>Sent</option>
                                        <option value="Approved" {{ $status === 'Approved' ? 'selected' : '' }}>Approved</option>
                                        <option value="Rejected" {{ $status === 'Rejected' ? 'selected' : '' }}>Rejected</option>
                                    </select>
                                </div>
                            </div>
                        </div>

                        <div class="form-group-item mt-4">
                            <div class="card-table">
                                <div class="table-responsive">
                                    <table class="table table-center table-hover" id="quotation_table">
                                        <thead class="thead-light">
                                            <tr>
                                                <th style="min-width: 170px;">Catalog Product</th>
                                                <th>Product / Service</th>
                                                <th style="min-width: 150px;">Price Level</th>
                                                <th>Quantity</th>
                                                <th>Rate (₦)</th>
                                                <th>Discount (₦)</th>
                                                <th>Tax (₦)</th>
                                                <th>Amount</th>
                                                <th class="text-end">Action</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach($quotationItems as $index => $item)
                                                <tr class="quotation-row">
                                                    <td>
                                                        <select name="items[{{ $index }}][product_id]" class="form-control product-select select2" onchange="syncQuotationProduct(this)">
                                                            <option value="">Custom item</option>
                                                            @foreach(($products ?? collect()) as $product)
                                                                <option value="{{ $product->id }}"
                                                                    data-name="{{ $product->name }}"
                                                                    data-retail="{{ $product->retail_price ?? $product->price ?? $product->product_price ?? 0 }}"
                                                                    data-wholesale="{{ $product->wholesale_price ?? 0 }}"
                                                                    data-special="{{ $product->special_price ?? 0 }}"
                                                                    {{ (string) ($item['product_id'] ?? '') === (string) $product->id ? 'selected' : '' }}>
                                                                    {{ $product->name }}
                                                                </option>
                                                            @endforeach
                                                        </select>
                                                    </td>
                                                    <td><input type="text" name="items[{{ $index }}][name]" class="form-control" value="{{ $item['name'] ?? '' }}" required></td>
                                                    <td>
                                                        <select name="items[{{ $index }}][price_level]" class="form-control price-level-select" onchange="syncQuotationProduct(this)">
                                                            @php $level = $item['price_level'] ?? 'retail'; @endphp
                                                            <option value="retail" {{ $level === 'retail' ? 'selected' : '' }}>Retail / Default</option>
                                                            <option value="wholesale" {{ $level === 'wholesale' ? 'selected' : '' }}>Wholesale</option>
                                                            <option value="special" {{ $level === 'special' ? 'selected' : '' }}>Special Discount</option>
                                                        </select>
                                                    </td>
                                                    <td><input type="number" name="items[{{ $index }}][qty]" class="form-control qty-input" value="{{ $item['qty'] ?? 1 }}" min="1" oninput="calculateQuotationRow(this)"></td>
                                                    <td><input type="number" name="items[{{ $index }}][rate]" class="form-control rate-input" value="{{ $item['rate'] ?? '0.00' }}" step="0.01" oninput="calculateQuotationRow(this)"></td>
                                                    <td><input type="number" name="items[{{ $index }}][discount]" class="form-control discount-input" value="{{ $item['discount'] ?? 0 }}" oninput="calculateQuotationRow(this)"></td>
                                                    <td><input type="number" name="items[{{ $index }}][tax]" class="form-control tax-input" value="{{ $item['tax'] ?? 0 }}" oninput="calculateQuotationRow(this)"></td>
                                                    <td class="fw-bold"><span class="row-total-text">₦{{ number_format((float) ($item['amount'] ?? 0), 2) }}</span><input type="hidden" name="items[{{ $index }}][amount]" class="row-amount-hidden" value="{{ (float) ($item['amount'] ?? 0) }}"></td>
                                                    <td class="text-end"><button type="button" class="btn btn-sm btn-white text-danger delete-row"><i class="fas fa-trash-alt"></i></button></td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>

                        <div class="col-lg-12">
                            <button type="button" class="btn btn-primary mb-4" id="add_quotation_row_btn"><i class="fas fa-plus-circle me-1"></i> Add New Item</button>
                        </div>

                        <div class="row mt-4">
                            <div class="col-xl-6 col-lg-12">
                                <div class="input-block mb-3">
                                    <label>Description / Notes</label>
                                    <textarea class="form-control" name="description" rows="5" placeholder="Enter Description">{{ old('description', $quotation->description ?? $quotation->note) }}</textarea>
                                </div>
                            </div>

                            <div class="col-xl-6 col-lg-12">
                                <div class="invoice-total-box">
                                    <div class="invoice-total-inner">
                                        <p>Sub Total <span id="quotation-display-subtotal">₦0.00</span></p>
                                        <p>Discount <span id="quotation-display-discount">₦0.00</span></p>
                                        <p>Tax <span id="quotation-display-tax">₦0.00</span></p>
                                    </div>
                                    <div class="invoice-total-footer">
                                        <h4>Total Amount <span id="quotation-display-grandtotal">₦0.00</span></h4>
                                        <input type="hidden" name="total" id="quotation-hidden-total" value="{{ old('total', (float) $quotation->total) }}">
                                        <input type="hidden" name="note" id="quotation-hidden-note" value="{{ old('note', $quotation->note) }}">
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="text-end mt-4">
                            <a href="{{ route('quotations') }}" class="btn btn-secondary me-2">Cancel</a>
                            <button type="submit" class="btn btn-primary">Update Quotation</button>
                        </div>
                    </form>
                @endif
            </div>
        </div>
    </div>
</div>
<script>
    let quotationRowIndex = {{ count($quotationItems) }};

    function initQuotationSelect2(scope) {
        if (typeof window.jQuery === 'undefined' || typeof jQuery.fn.select2 === 'undefined') {
            return;
        }

        const $scope = scope ? jQuery(scope) : jQuery(document);
        $scope.find('.customer-select').not('.select2-hidden-accessible').select2({
            width: '100%',
            placeholder: 'Choose Customer',
            allowClear: true
        });
        $scope.find('.product-select').not('.select2-hidden-accessible').select2({
            width: '100%',
            placeholder: 'Search product',
            allowClear: true
        });
    }

    function syncQuotationProduct(element) {
        const row = element.closest('.quotation-row');
        if (!row) return;

        const productSelect = row.querySelector('.product-select');
        const priceLevelSelect = row.querySelector('.price-level-select');
        const nameInput = row.querySelector('input[name*="[name]"]');
        const rateInput = row.querySelector('.rate-input');
        if (!productSelect || !priceLevelSelect || !nameInput || !rateInput) return;

        const selectedOption = productSelect.options[productSelect.selectedIndex];
        if (!selectedOption || !selectedOption.value) return;

        const productName = selectedOption.getAttribute('data-name') || '';
        const priceLevel = priceLevelSelect.value || 'retail';
        const retail = parseFloat(selectedOption.getAttribute('data-retail') || '0') || 0;
        const wholesale = parseFloat(selectedOption.getAttribute('data-wholesale') || '0') || 0;
        const special = parseFloat(selectedOption.getAttribute('data-special') || '0') || 0;

        let rate = retail;
        if (priceLevel === 'wholesale' && wholesale > 0) {
            rate = wholesale;
        } else if (priceLevel === 'special' && special > 0) {
            rate = special;
        }

        nameInput.value = productName;
        rateInput.value = rate.toFixed(2);
        calculateQuotationRow(rateInput);
    }

    function calculateQuotationRow(element) {
        const row = element.closest('.quotation-row');
        if (!row) return;

        const qty = parseFloat(row.querySelector('.qty-input')?.value || '0') || 0;
        const rate = parseFloat(row.querySelector('.rate-input')?.value || '0') || 0;
        const discount = parseFloat(row.querySelector('.discount-input')?.value || '0') || 0;
        const tax = parseFloat(row.querySelector('.tax-input')?.value || '0') || 0;

        const subtotal = qty * rate;
        const total = Math.max(0, subtotal - discount + tax);

        const totalText = row.querySelector('.row-total-text');
        const amountHidden = row.querySelector('.row-amount-hidden');

        if (totalText) totalText.textContent = `₦${total.toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 })}`;
        if (amountHidden) amountHidden.value = total.toFixed(2);

        calculateQuotationGrandTotal();
    }

    function calculateQuotationGrandTotal() {
        const rows = Array.from(document.querySelectorAll('.quotation-row'));
        let subtotal = 0;
        let discount = 0;
        let tax = 0;
        let total = 0;

        rows.forEach((row) => {
            const qty = parseFloat(row.querySelector('.qty-input')?.value || '0') || 0;
            const rate = parseFloat(row.querySelector('.rate-input')?.value || '0') || 0;
            const lineDiscount = parseFloat(row.querySelector('.discount-input')?.value || '0') || 0;
            const lineTax = parseFloat(row.querySelector('.tax-input')?.value || '0') || 0;
            subtotal += qty * rate;
            discount += lineDiscount;
            tax += lineTax;
            total += Math.max(0, (qty * rate) - lineDiscount + lineTax);
        });

        const fmt = (value) => `₦${value.toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 })}`;
        const subtotalEl = document.getElementById('quotation-display-subtotal');
        const discountEl = document.getElementById('quotation-display-discount');
        const taxEl = document.getElementById('quotation-display-tax');
        const totalEl = document.getElementById('quotation-display-grandtotal');
        const totalHidden = document.getElementById('quotation-hidden-total');
        const noteHidden = document.getElementById('quotation-hidden-note');
        const description = document.querySelector('textarea[name="description"]');

        if (subtotalEl) subtotalEl.textContent = fmt(subtotal);
        if (discountEl) discountEl.textContent = fmt(discount);
        if (taxEl) taxEl.textContent = fmt(tax);
        if (totalEl) totalEl.textContent = fmt(total);
        if (totalHidden) totalHidden.value = total.toFixed(2);
        if (noteHidden && description) noteHidden.value = description.value;
    }

    document.addEventListener('DOMContentLoaded', function () {
        initQuotationSelect2(document);
        document.querySelectorAll('.quotation-row').forEach((row) => {
            calculateQuotationRow(row.querySelector('.rate-input'));
        });

        document.getElementById('add_quotation_row_btn')?.addEventListener('click', function () {
            const tableBody = document.querySelector('#quotation_table tbody');
            const newRow = `
                <tr class="quotation-row">
                    <td>
                        <select name="items[${quotationRowIndex}][product_id]" class="form-control product-select select2" onchange="syncQuotationProduct(this)">
                            <option value="">Custom item</option>
                            @foreach(($products ?? collect()) as $product)
                                <option value="{{ $product->id }}"
                                    data-name="{{ $product->name }}"
                                    data-retail="{{ $product->retail_price ?? $product->price ?? $product->product_price ?? 0 }}"
                                    data-wholesale="{{ $product->wholesale_price ?? 0 }}"
                                    data-special="{{ $product->special_price ?? 0 }}">
                                    {{ $product->name }}
                                </option>
                            @endforeach
                        </select>
                    </td>
                    <td><input type="text" name="items[${quotationRowIndex}][name]" class="form-control" required></td>
                    <td>
                        <select name="items[${quotationRowIndex}][price_level]" class="form-control price-level-select" onchange="syncQuotationProduct(this)">
                            <option value="retail">Retail / Default</option>
                            <option value="wholesale">Wholesale</option>
                            <option value="special">Special Discount</option>
                        </select>
                    </td>
                    <td><input type="number" name="items[${quotationRowIndex}][qty]" class="form-control qty-input" value="1" min="1" oninput="calculateQuotationRow(this)"></td>
                    <td><input type="number" name="items[${quotationRowIndex}][rate]" class="form-control rate-input" value="0.00" step="0.01" oninput="calculateQuotationRow(this)"></td>
                    <td><input type="number" name="items[${quotationRowIndex}][discount]" class="form-control discount-input" value="0" oninput="calculateQuotationRow(this)"></td>
                    <td><input type="number" name="items[${quotationRowIndex}][tax]" class="form-control tax-input" value="0" oninput="calculateQuotationRow(this)"></td>
                    <td class="fw-bold"><span class="row-total-text">₦0.00</span><input type="hidden" name="items[${quotationRowIndex}][amount]" class="row-amount-hidden" value="0"></td>
                    <td class="text-end"><button type="button" class="btn btn-sm btn-white text-danger delete-row"><i class="fas fa-trash-alt"></i></button></td>
                </tr>`;
            tableBody.insertAdjacentHTML('beforeend', newRow);
            const insertedRow = tableBody.lastElementChild;
            if (insertedRow) {
                initQuotationSelect2(insertedRow);
            }
            quotationRowIndex++;
        });

        document.addEventListener('click', function (event) {
            if (!event.target.closest('.delete-row')) return;
            const rows = document.querySelectorAll('.quotation-row');
            if (rows.length <= 1) return;
            event.target.closest('.quotation-row')?.remove();
            calculateQuotationGrandTotal();
        });

        document.querySelector('textarea[name="description"]')?.addEventListener('input', calculateQuotationGrandTotal);
    });
</script>
@endsection
