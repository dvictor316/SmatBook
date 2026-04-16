<?php $page = 'add-invoice'; ?>
@extends('layout.mainlayout')
@section('content')
@php
    $isEditMode = ($formMode ?? 'create') === 'edit';
    $quotationPrefill = $quotationPrefill ?? session('quotation_prefill') ?? [];
    $invoiceFormDefaults = $invoiceFormDefaults ?? [];
    $invoiceItems = old('items');
    if (!is_array($invoiceItems)) {
        $invoiceItems = $invoiceFormDefaults['items'] ?? $quotationPrefill['items'] ?? [[
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
    if (empty($invoiceItems)) {
        $invoiceItems = [[
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
    $selectedCustomer = old('customer_id', $invoiceFormDefaults['customer_id'] ?? $quotationPrefill['customer_id'] ?? ($selected_customer ?? ''));
    $invoiceDate = old('invoice_date', $invoiceFormDefaults['invoice_date'] ?? $quotationPrefill['invoice_date'] ?? date('d-m-Y'));
    $dueDate = old('due_date', $invoiceFormDefaults['due_date'] ?? $quotationPrefill['due_date'] ?? '');
    $selectedStatus = old('status', $invoiceFormDefaults['status'] ?? 'Unpaid');
    $invoiceDescription = old('description', $invoiceFormDefaults['description'] ?? $quotationPrefill['description'] ?? '');
    $invoiceExpenses = old('expenses', $invoiceFormDefaults['expenses'] ?? '0.00');
    $formAction = $isEditMode && isset($invoice) ? route('invoices.update', $invoice->id) : route('invoices.store');
@endphp
<div class="page-wrapper">
    <div class="content container-fluid">
        <div class="card mb-0">
            <div class="card-body">
                <div class="page-header">
                    <div class="content-page-header">
                        <h5>{{ $isEditMode ? 'Edit Invoice' : 'Add Invoice' }}</h5>
                    </div>
                </div>

                @if(session('info'))
                    <div class="alert alert-info border-0 shadow-sm">{{ session('info') }}</div>
                @endif

                @if(session('error'))
                    <div class="alert alert-danger border-0 shadow-sm">{{ session('error') }}</div>
                @endif

                @if ($errors->any())
                    <div class="alert alert-danger border-0 shadow-sm">
                        <ul class="mb-0 ps-3">
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif
                
                <form action="{{ $formAction }}" method="POST" enctype="multipart/form-data" id="invoice-form">
                    @csrf
                    @if($isEditMode)
                        @method('PUT')
                    @endif
                    <div class="row">
                        <div class="col-md-12">
                            <div class="form-group-item border-0 mb-0">
                                <div class="row align-item-center">
                                    {{-- 1. Customer Selection --}}
                                    <div class="col-lg-4 col-md-6 col-sm-12">
                                        <div class="input-block mb-3">
                                            <label>Customer Name <span class="text-danger">*</span></label>
                                            <input type="text" id="invoice-customer-search" class="form-control mb-2" placeholder="Search customer name...">
                                            <ul class="form-group-plus css-equal-heights">
                                                <li>
                                                    <select class="form-control customer-select select2" name="customer_id" required>
                                                        <option value="">Choose Customer</option>
                                                        @foreach($customers as $customer)
                                                            <option value="{{ $customer->id }}" {{ (string) $selectedCustomer === (string) $customer->id ? 'selected' : '' }}>
                                                                {{ $customer->customer_name ?? $customer->name }}
                                                            </option>
                                                        @endforeach
                                                    </select>
                                                </li>
                                                <li>
                                                    <a class="btn btn-primary form-plus-btn" href="{{ url('add-customer') }}">
                                                        <i class="fas fa-plus-circle"></i>
                                                    </a>
                                                </li>
                                            </ul>
                                        </div>
                                    </div>

                                    <div class="col-lg-4 col-md-6 col-sm-12">
                                        <div class="input-block mb-3">
                                            <label>Invoice Date</label>
                                            <div class="cal-icon cal-icon-info">
                                                <input type="text" name="invoice_date" class="datetimepicker form-control" value="{{ $invoiceDate }}">
                                            </div>
                                        </div>
                                    </div>

                                    <div class="col-lg-4 col-md-6 col-sm-12">
                                        <div class="input-block mb-3">
                                            <label>Due Date <span class="text-danger">*</span></label>
                                            <div class="cal-icon cal-icon-info">
                                                <input type="text" name="due_date" class="datetimepicker form-control" value="{{ $dueDate }}" required>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="col-lg-4 col-md-6 col-sm-12">
                                        <div class="input-block mb-3">
                                            <label>Status</label>
                                            <select class="select" name="status">
                                                <option value="Unpaid" {{ $selectedStatus === 'Unpaid' ? 'selected' : '' }}>Unpaid</option>
                                                <option value="Partially paid" {{ $selectedStatus === 'Partially paid' ? 'selected' : '' }}>Partially paid</option>
                                                <option value="Paid" {{ $selectedStatus === 'Paid' ? 'selected' : '' }}>Paid</option>
                                                <option value="Overdue" {{ $selectedStatus === 'Overdue' ? 'selected' : '' }}>Overdue</option>
                                                <option value="Draft" {{ $selectedStatus === 'Draft' ? 'selected' : '' }}>Draft</option>
                                            </select>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            {{-- 2. Dynamic Product Table --}}
                            <div class="form-group-item mt-4">
                                <div class="card-table">
                                    <div class="table-responsive">
                                        <table class="table table-center table-hover" id="invoice_table">
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
                                                @foreach($invoiceItems as $index => $item)
                                                    <tr class="invoice-row">
                                                        <td>
                                                            <select name="items[{{ $index }}][product_id]" class="form-control product-select select2" onchange="syncInvoiceProduct(this)">
                                                                <option value="">Custom item</option>
                                                                @foreach($products as $product)
                                                                    <option value="{{ $product->id }}"
                                                                        data-name="{{ $product->name }}"
                                                                        data-retail="{{ $product->retail_price ?? $product->price ?? 0 }}"
                                                                        data-wholesale="{{ $product->wholesale_price ?? 0 }}"
                                                                        data-special="{{ $product->special_price ?? 0 }}"
                                                                        {{ (string) ($item['product_id'] ?? '') === (string) $product->id ? 'selected' : '' }}>
                                                                        {{ $product->name }}
                                                                    </option>
                                                                @endforeach
                                                            </select>
                                                        </td>
                                                        <td><input type="text" name="items[{{ $index }}][name]" class="form-control" placeholder="Item Name" value="{{ $item['name'] ?? '' }}" required></td>
                                                        <td>
                                                            @php $priceLevel = $item['price_level'] ?? 'retail'; @endphp
                                                            <select name="items[{{ $index }}][price_level]" class="form-control price-level-select" onchange="syncInvoiceProduct(this)">
                                                                <option value="retail" {{ $priceLevel === 'retail' ? 'selected' : '' }}>Retail / Default</option>
                                                                <option value="wholesale" {{ $priceLevel === 'wholesale' ? 'selected' : '' }}>Wholesale</option>
                                                                <option value="special" {{ $priceLevel === 'special' ? 'selected' : '' }}>Special Discount</option>
                                                            </select>
                                                        </td>
                                                        <td><input type="number" name="items[{{ $index }}][qty]" class="form-control qty-input" value="{{ $item['qty'] ?? 1 }}" min="1" oninput="calculateRow(this)"></td>
                                                        <td><input type="number" name="items[{{ $index }}][rate]" class="form-control rate-input" value="{{ $item['rate'] ?? '0.00' }}" step="0.01" oninput="calculateRow(this)"></td>
                                                        <td><input type="number" name="items[{{ $index }}][discount]" class="form-control discount-input" value="{{ $item['discount'] ?? 0 }}" oninput="calculateRow(this)"></td>
                                                        <td><input type="number" name="items[{{ $index }}][tax]" class="form-control tax-input" value="{{ $item['tax'] ?? 0 }}" oninput="calculateRow(this)"></td>
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
                                <button type="button" class="btn btn-primary mb-4" id="add_row_btn"><i class="fas fa-plus-circle me-1"></i> Add New Item</button>
                            </div>

                            <div class="row mt-4">
                                <div class="col-xl-6 col-lg-12">
                                    <div class="input-block mb-3">
                                        <label>Description / Notes</label>
                                        <textarea class="form-control" name="description" rows="5" placeholder="Enter Description">{{ $invoiceDescription }}</textarea>
                                    </div>
                                </div>

                                <div class="col-xl-6 col-lg-12">
                                    <div class="invoice-total-box">
                                        <div class="invoice-total-inner">
                                            <p>Sub Total <span id="display-subtotal">₦0.00</span></p>
                                            <div class="input-block mb-2 d-flex justify-content-between align-items-center">
                                                <label class="mb-0">Additional Expenses</label>
                                                <input type="number" step="0.01" name="expenses" id="input-expenses" class="form-control w-50 text-end" value="{{ $invoiceExpenses }}" oninput="calculateGrandTotal()">
                                            </div>
                                        </div>
                                        <div class="invoice-total-footer">
                                            <h4>Total Amount <span id="display-grandtotal">₦0.00</span></h4>
                                            <input type="hidden" name="total_amount" id="hidden-total-amount" value="0.00">
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="add-customer-btns text-end mt-4">
                                <a href="{{ route('invoices.index') }}" class="btn btn-secondary me-2">Cancel</a>
                                <button type="submit" name="action" value="save" class="btn btn-info me-2">{{ $isEditMode ? 'Save Changes' : 'Save Draft' }}</button>
                                <button type="submit" name="action" value="send" class="btn btn-primary">{{ $isEditMode ? 'Update & Send' : 'Save & Send' }}</button>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>



<script>
    let rowIndex = {{ count($invoiceItems) }};
    let customerOptionSnapshots = [];

    function initInvoiceSelect2(scope) {
        if (typeof window.jQuery === 'undefined' || typeof jQuery.fn.select2 === 'undefined') {
            return;
        }

        const $scope = scope ? jQuery(scope) : jQuery(document);
        $scope.find('.customer-select').not('.select2-hidden-accessible').select2({
            width: '100%',
            placeholder: 'Search customer name...',
            allowClear: true
        });
        $scope.find('.product-select').not('.select2-hidden-accessible').select2({
            width: '100%',
            placeholder: 'Search product',
            allowClear: true
        });
    }

    function snapshotCustomerOptions() {
        const customerSelect = document.querySelector('.customer-select');
        if (!customerSelect) {
            customerOptionSnapshots = [];
            return;
        }

        customerOptionSnapshots = Array.from(customerSelect.options).map(function(option) {
            return {
                value: option.value,
                label: option.textContent || '',
            };
        });
    }

    function filterInvoiceCustomerOptions(keyword) {
        const customerSelect = document.querySelector('.customer-select');
        if (!customerSelect) {
            return;
        }

        const query = String(keyword || '').toLowerCase().trim();
        const selectedValue = customerSelect.value;
        const filteredOptions = customerOptionSnapshots.filter(function(option) {
            if (option.value === '') {
                return true;
            }

            return query === '' || option.label.toLowerCase().includes(query);
        });

        customerSelect.innerHTML = '';
        filteredOptions.forEach(function(option) {
            const node = document.createElement('option');
            node.value = option.value;
            node.textContent = option.label;
            if (option.value === selectedValue) {
                node.selected = true;
            }
            customerSelect.appendChild(node);
        });

        if (selectedValue && !filteredOptions.some(function(option) { return option.value === selectedValue; })) {
            customerSelect.value = '';
        }

        if (typeof window.jQuery !== 'undefined' && typeof jQuery.fn.select2 !== 'undefined') {
            jQuery(customerSelect).trigger('change.select2');
        }
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', function() {
            snapshotCustomerOptions();
            initInvoiceSelect2(document);
            document.querySelectorAll('.invoice-row').forEach(function(row) {
                const rateInput = row.querySelector('.rate-input');
                if (rateInput) {
                    calculateRow(rateInput);
                }
            });

            const invoiceCustomerSearch = document.getElementById('invoice-customer-search');
            if (invoiceCustomerSearch) {
                invoiceCustomerSearch.addEventListener('input', function() {
                    filterInvoiceCustomerOptions(invoiceCustomerSearch.value);
                });
            }
        });
    } else {
        snapshotCustomerOptions();
        initInvoiceSelect2(document);
        document.querySelectorAll('.invoice-row').forEach(function(row) {
            const rateInput = row.querySelector('.rate-input');
            if (rateInput) {
                calculateRow(rateInput);
            }
        });

        const invoiceCustomerSearch = document.getElementById('invoice-customer-search');
        if (invoiceCustomerSearch) {
            invoiceCustomerSearch.addEventListener('input', function() {
                filterInvoiceCustomerOptions(invoiceCustomerSearch.value);
            });
        }
    }

    // Add Row Logic
    document.getElementById('add_row_btn').addEventListener('click', function() {
        const tableBody = document.querySelector('#invoice_table tbody');
        const newRow = `
            <tr class="invoice-row">
                <td>
                    <select name="items[${rowIndex}][product_id]" class="form-control product-select select2" onchange="syncInvoiceProduct(this)">
                        <option value="">Custom item</option>
                        @foreach($products as $product)
                            <option value="{{ $product->id }}"
                                data-name="{{ $product->name }}"
                                data-retail="{{ $product->retail_price ?? $product->price ?? 0 }}"
                                data-wholesale="{{ $product->wholesale_price ?? 0 }}"
                                data-special="{{ $product->special_price ?? 0 }}">
                                {{ $product->name }}
                            </option>
                        @endforeach
                    </select>
                </td>
                <td><input type="text" name="items[${rowIndex}][name]" class="form-control" required></td>
                <td>
                    <select name="items[${rowIndex}][price_level]" class="form-control price-level-select" onchange="syncInvoiceProduct(this)">
                        <option value="retail">Retail / Default</option>
                        <option value="wholesale">Wholesale</option>
                        <option value="special">Special Discount</option>
                    </select>
                </td>
                <td><input type="number" name="items[${rowIndex}][qty]" class="form-control qty-input" value="1" min="1" oninput="calculateRow(this)"></td>
                <td><input type="number" name="items[${rowIndex}][rate]" class="form-control rate-input" value="0.00" step="0.01" oninput="calculateRow(this)"></td>
                <td><input type="number" name="items[${rowIndex}][discount]" class="form-control discount-input" value="0" oninput="calculateRow(this)"></td>
                <td><input type="number" name="items[${rowIndex}][tax]" class="form-control tax-input" value="0" oninput="calculateRow(this)"></td>
                <td class="fw-bold"><span class="row-total-text">₦0.00</span><input type="hidden" name="items[${rowIndex}][amount]" class="row-amount-hidden" value="0"></td>
                <td class="text-end"><button type="button" class="btn btn-sm btn-white text-danger delete-row"><i class="fas fa-trash-alt"></i></button></td>
            </tr>`;
        tableBody.insertAdjacentHTML('beforeend', newRow);
        const insertedRow = tableBody.lastElementChild;
        if (insertedRow) {
            initInvoiceSelect2(insertedRow);
        }
        rowIndex++;
    });

    function syncInvoiceProduct(element) {
        const row = element.closest('.invoice-row');
        if (!row) return;

        const productSelect = row.querySelector('.product-select');
        const priceLevelSelect = row.querySelector('.price-level-select');
        if (!productSelect || !priceLevelSelect) return;

        const selectedOption = productSelect.options[productSelect.selectedIndex];
        if (!selectedOption || !selectedOption.value) {
            return;
        }

        const productName = selectedOption.getAttribute('data-name') || '';
        const retailPrice = parseFloat(selectedOption.getAttribute('data-retail')) || 0;
        const wholesalePrice = parseFloat(selectedOption.getAttribute('data-wholesale')) || 0;
        const specialPrice = parseFloat(selectedOption.getAttribute('data-special')) || 0;
        const level = priceLevelSelect.value || 'retail';

        let rate = retailPrice;
        if (level === 'wholesale' && wholesalePrice > 0) {
            rate = wholesalePrice;
        } else if (level === 'special' && specialPrice > 0) {
            rate = specialPrice;
        }

        row.querySelector('input[name$="[name]"]').value = productName;
        row.querySelector('.rate-input').value = rate.toFixed(2);
        calculateRow(row.querySelector('.rate-input'));
    }

    document.addEventListener('change', function(e) {
        if (e.target.matches('.product-select, .price-level-select')) {
            syncInvoiceProduct(e.target);
        }
    });

    // Delete Row Logic
    document.addEventListener('click', function(e) {
        if (e.target.closest('.delete-row')) {
            const row = e.target.closest('.invoice-row');
            const rows = document.querySelectorAll('.invoice-row');
            if (!row) {
                return;
            }

            if (rows.length > 1) {
                row.remove();
                calculateGrandTotal();
                return;
            }

            const productSelect = row.querySelector('.product-select');
            const nameInput = row.querySelector('input[name$="[name]"]');
            const priceLevelSelect = row.querySelector('.price-level-select');
            const qtyInput = row.querySelector('.qty-input');
            const rateInput = row.querySelector('.rate-input');
            const discountInput = row.querySelector('.discount-input');
            const taxInput = row.querySelector('.tax-input');

            if (productSelect) {
                productSelect.value = '';
                if (typeof window.jQuery !== 'undefined' && typeof jQuery.fn.select2 !== 'undefined') {
                    jQuery(productSelect).trigger('change.select2');
                }
            }

            if (nameInput) nameInput.value = '';
            if (priceLevelSelect) priceLevelSelect.value = 'retail';
            if (qtyInput) qtyInput.value = 1;
            if (rateInput) rateInput.value = '0.00';
            if (discountInput) discountInput.value = 0;
            if (taxInput) taxInput.value = 0;

            calculateRow(rateInput || row);
        }
    });

    // Calculate Individual Row
    function calculateRow(element) {
        const row = element.closest('.invoice-row');
        const qty = parseFloat(row.querySelector('.qty-input').value) || 0;
        const rate = parseFloat(row.querySelector('.rate-input').value) || 0;
        const disc = parseFloat(row.querySelector('.discount-input').value) || 0;
        const tax = parseFloat(row.querySelector('.tax-input').value) || 0;

        const amount = (qty * rate) - disc + tax;
        row.querySelector('.row-total-text').innerText = '₦' + amount.toLocaleString();
        row.querySelector('.row-amount-hidden').value = amount.toFixed(2);
        calculateGrandTotal();
    }

    // Calculate Grand Total
    function calculateGrandTotal() {
        let subtotal = 0;
        document.querySelectorAll('.row-amount-hidden').forEach(input => {
            subtotal += parseFloat(input.value) || 0;
        });
        const expenses = parseFloat(document.getElementById('input-expenses').value) || 0;
        const grandTotal = subtotal + expenses;

        document.getElementById('display-subtotal').innerText = '₦' + subtotal.toLocaleString();
        document.getElementById('display-grandtotal').innerText = '₦' + grandTotal.toLocaleString();
        document.getElementById('hidden-total-amount').value = grandTotal.toFixed(2);
    }
</script>
@endsection
