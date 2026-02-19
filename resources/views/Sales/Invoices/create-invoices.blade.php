<?php $page = 'add-invoice'; ?>
@extends('layout.mainlayout')
@section('content')
<div class="page-wrapper">
    <div class="content container-fluid">
        <div class="card mb-0">
            <div class="card-body">
                <div class="page-header">
                    <div class="content-page-header">
                        <h5>Add Invoice</h5>
                    </div>
                </div>
                
                <form action="{{ route('invoices.store') }}" method="POST" enctype="multipart/form-data" id="invoice-form">
                    @csrf
                    <div class="row">
                        <div class="col-md-12">
                            <div class="form-group-item border-0 mb-0">
                                <div class="row align-item-center">
                                    {{-- 1. Customer Selection --}}
                                    <div class="col-lg-4 col-md-6 col-sm-12">
                                        <div class="input-block mb-3">
                                            <label>Customer Name <span class="text-danger">*</span></label>
                                            <ul class="form-group-plus css-equal-heights">
                                                <li>
                                                    <select class="select" name="customer_id" required>
                                                        <option value="">Choose Customer</option>
                                                        @foreach($customers as $customer)
                                                            <option value="{{ $customer->id }}" {{ (isset($selected_customer) && $selected_customer == $customer->id) ? 'selected' : '' }}>
                                                                {{ $customer->customer_name ?? $customer->name }}
                                                            </option>
                                                        @endforeach
                                                    </select>
                                                </li>
                                                <li>
                                                    <a class="btn btn-primary form-plus-btn" href="{{ url('add-customer') }}">
                                                        <i class="fe fe-plus-circle"></i>
                                                    </a>
                                                </li>
                                            </ul>
                                        </div>
                                    </div>

                                    <div class="col-lg-4 col-md-6 col-sm-12">
                                        <div class="input-block mb-3">
                                            <label>Invoice Date</label>
                                            <div class="cal-icon cal-icon-info">
                                                <input type="text" name="invoice_date" class="datetimepicker form-control" value="{{ date('d-m-Y') }}">
                                            </div>
                                        </div>
                                    </div>

                                    <div class="col-lg-4 col-md-6 col-sm-12">
                                        <div class="input-block mb-3">
                                            <label>Due Date <span class="text-danger">*</span></label>
                                            <div class="cal-icon cal-icon-info">
                                                <input type="text" name="due_date" class="datetimepicker form-control" required>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="col-lg-4 col-md-6 col-sm-12">
                                        <div class="input-block mb-3">
                                            <label>Status</label>
                                            <select class="select" name="status">
                                                <option value="Unpaid">Unpaid</option>
                                                <option value="Partially paid">Partially paid</option>
                                                <option value="Paid">Paid</option>
                                                <option value="Overdue">Overdue</option>
                                                <option value="Draft">Draft</option>
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
                                                    <th>Product / Service</th>
                                                    <th>Quantity</th>
                                                    <th>Rate (₦)</th>
                                                    <th>Discount (₦)</th>
                                                    <th>Tax (₦)</th>
                                                    <th>Amount</th>
                                                    <th class="text-end">Action</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <tr class="invoice-row">
                                                    <td><input type="text" name="items[0][name]" class="form-control" placeholder="Item Name" required></td>
                                                    <td><input type="number" name="items[0][qty]" class="form-control qty-input" value="1" min="1" oninput="calculateRow(this)"></td>
                                                    <td><input type="number" name="items[0][rate]" class="form-control rate-input" value="0.00" step="0.01" oninput="calculateRow(this)"></td>
                                                    <td><input type="number" name="items[0][discount]" class="form-control discount-input" value="0" oninput="calculateRow(this)"></td>
                                                    <td><input type="number" name="items[0][tax]" class="form-control tax-input" value="0" oninput="calculateRow(this)"></td>
                                                    <td class="fw-bold"><span class="row-total-text">₦0.00</span><input type="hidden" name="items[0][amount]" class="row-amount-hidden" value="0"></td>
                                                    <td class="text-end"><button type="button" class="btn btn-sm btn-white text-danger delete-row"><i class="fe fe-trash-2"></i></button></td>
                                                </tr>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>

                            <div class="col-lg-12">
                                <button type="button" class="btn btn-primary mb-4" id="add_row_btn"><i class="fe fe-plus-circle me-1"></i> Add New Item</button>
                            </div>

                            <div class="row mt-4">
                                <div class="col-xl-6 col-lg-12">
                                    <div class="input-block mb-3">
                                        <label>Description / Notes</label>
                                        <textarea class="form-control" name="description" rows="5" placeholder="Enter Description"></textarea>
                                    </div>
                                </div>

                                <div class="col-xl-6 col-lg-12">
                                    <div class="invoice-total-box">
                                        <div class="invoice-total-inner">
                                            <p>Sub Total <span id="display-subtotal">₦0.00</span></p>
                                            <div class="input-block mb-2 d-flex justify-content-between align-items-center">
                                                <label class="mb-0">Additional Expenses</label>
                                                <input type="number" step="0.01" name="expenses" id="input-expenses" class="form-control w-50 text-end" value="0.00" oninput="calculateGrandTotal()">
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
                                <button type="reset" class="btn btn-secondary me-2">Cancel</button>
                                <button type="submit" name="action" value="save" class="btn btn-info me-2">Save Draft</button>
                                <button type="submit" name="action" value="send" class="btn btn-primary">Save & Send</button>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>



<script>
    let rowIndex = 1;

    // Add Row Logic
    document.getElementById('add_row_btn').addEventListener('click', function() {
        const tableBody = document.querySelector('#invoice_table tbody');
        const newRow = `
            <tr class="invoice-row">
                <td><input type="text" name="items[${rowIndex}][name]" class="form-control" required></td>
                <td><input type="number" name="items[${rowIndex}][qty]" class="form-control qty-input" value="1" min="1" oninput="calculateRow(this)"></td>
                <td><input type="number" name="items[${rowIndex}][rate]" class="form-control rate-input" value="0.00" step="0.01" oninput="calculateRow(this)"></td>
                <td><input type="number" name="items[${rowIndex}][discount]" class="form-control discount-input" value="0" oninput="calculateRow(this)"></td>
                <td><input type="number" name="items[${rowIndex}][tax]" class="form-control tax-input" value="0" oninput="calculateRow(this)"></td>
                <td class="fw-bold"><span class="row-total-text">₦0.00</span><input type="hidden" name="items[${rowIndex}][amount]" class="row-amount-hidden" value="0"></td>
                <td class="text-end"><button type="button" class="btn btn-sm btn-white text-danger delete-row"><i class="fe fe-trash-2"></i></button></td>
            </tr>`;
        tableBody.insertAdjacentHTML('beforeend', newRow);
        rowIndex++;
    });

    // Delete Row Logic
    document.addEventListener('click', function(e) {
        if (e.target.closest('.delete-row')) {
            const rows = document.querySelectorAll('.invoice-row');
            if (rows.length > 1) {
                e.target.closest('.invoice-row').remove();
                calculateGrandTotal();
            }
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