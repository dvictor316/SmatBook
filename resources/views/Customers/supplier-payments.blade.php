<?php $page = 'suppliers'; ?>
@extends('layout.mainlayout')

@section('content')
<div class="page-wrapper">
    <div class="content container-fluid">
        <div class="page-header">
            <div class="row align-items-center">
                <div class="col">
                    <h3 class="page-title">Pay Supplier</h3>
                    <ul class="breadcrumb">
                        <li class="breadcrumb-item"><a href="{{ route('suppliers.index') }}">Suppliers</a></li>
                        <li class="breadcrumb-item"><a href="{{ route('suppliers.show', $supplier->id) }}">{{ $supplier->name ?? $supplier->supplier_name ?? $supplier->company_name ?? 'Supplier' }}</a></li>
                        <li class="breadcrumb-item active">Pay</li>
                    </ul>
                </div>
                <div class="col-auto">
                    <a href="{{ route('suppliers.show', $supplier->id) }}" class="btn btn-outline-primary">
                        <i class="fas fa-arrow-left me-1"></i> Back to Supplier
                    </a>
                </div>
            </div>
        </div>

        @if(session('success'))
            <div class="alert alert-success border-0 shadow-sm">{{ session('success') }}</div>
        @endif

        @if(session('error'))
            <div class="alert alert-danger border-0 shadow-sm">{{ session('error') }}</div>
        @endif

        @if($errors->any())
            <div class="alert alert-danger border-0 shadow-sm">
                <ul class="mb-0 ps-3">
                    @foreach($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <div class="row g-3 mb-4">
            <div class="col-md-3">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-body">
                        <p class="text-muted mb-1">Supplier</p>
                        <h4 class="mb-0">{{ $supplier->name ?? $supplier->supplier_name ?? $supplier->company_name ?? 'Supplier' }}</h4>
                        <small class="text-muted">{{ $supplier->phone ?: ($supplier->email ?: 'No contact saved') }}</small>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-body">
                        <p class="text-muted mb-1">Outstanding Bills</p>
                        <h4 class="mb-0">₦{{ number_format((float) $summary['outstanding_payables'], 2) }}</h4>
                        <small class="text-muted">{{ $summary['open_bills'] }} unpaid purchase(s)</small>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-body">
                        <p class="text-muted mb-1">Payment History Total</p>
                        <h4 class="mb-0">₦{{ number_format((float) $summary['total_paid'], 2) }}</h4>
                        <small class="text-muted">Total paid through supplier history</small>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card border-0 shadow-sm h-100 bg-dark text-white">
                    <div class="card-body">
                        <p class="mb-1 text-white-50">Open Purchase Count</p>
                        <h3 class="mb-0">{{ $summary['open_bills'] }}</h3>
                        <small class="text-white-50">Pay in full or partially across multiple bills</small>
                    </div>
                </div>
            </div>
        </div>

        <div class="row g-3 mb-4">
            <div class="col-md-4">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-body">
                        <p class="text-muted mb-1">Selected Bank Available Balance</p>
                        <h4 class="mb-0">₦<span data-selected-bank-balance>0.00</span></h4>
                        <small class="text-muted">Choose a bank account to verify available funds before saving payment.</small>
                    </div>
                </div>
            </div>
        </div>

        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white">
                <h5 class="card-title mb-1">Make Supplier Payment</h5>
                <p class="text-muted mb-0">Allocate the amount you want to pay against one or more outstanding purchases.</p>
            </div>
            <div class="card-body">
                <form method="POST" action="{{ route('suppliers.store-payment', $supplier->id) }}" id="supplierPaymentForm">
                    @csrf
                    <div class="row g-3 mb-4">
                        <div class="col-md-3">
                            <label class="form-label">Payment Date</label>
                            <input type="date" name="payment_date" class="form-control" value="{{ old('payment_date', now()->toDateString()) }}" required>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Bank / Account</label>
                            <select name="bank_id" class="form-select">
                                <option value="">Select bank</option>
                                @foreach($banks as $bank)
                                    <option value="{{ $bank->id }}" data-balance="{{ number_format((float) ($bank->balance ?? 0), 2) }}" @selected((string) old('bank_id') === (string) $bank->id)>
                                        {{ $bank->name }} — ₦{{ number_format((float) ($bank->balance ?? 0), 2) }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Payment Method</label>
                            <select name="method" class="form-select">
                                @foreach(['Bank Transfer', 'Cash', 'Cheque', 'POS', 'Other'] as $method)
                                    <option value="{{ $method }}" @selected(old('method', 'Bank Transfer') === $method)>{{ $method }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Reference No.</label>
                            <input type="text" name="reference" class="form-control" value="{{ old('reference') }}" placeholder="Optional reference">
                        </div>
                        <div class="col-12">
                            <label class="form-label">Note</label>
                            <textarea name="note" rows="2" class="form-control" placeholder="Optional note for this supplier payment">{{ old('note') }}</textarea>
                        </div>
                        <div class="col-12">
                            <div class="alert alert-warning d-none" role="alert" data-bank-warning></div>
                        </div>
                    </div>

                    <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
                        <div>
                            <h6 class="mb-1">Outstanding Purchases</h6>
                            <small class="text-muted">Use “Full” for quick settlement or type a partial payment for any bill.</small>
                        </div>
                        <div class="text-end">
                            <div class="small text-muted">Amount being paid</div>
                            <div class="fs-4 fw-bold text-success">₦<span data-allocation-total>0.00</span></div>
                        </div>
                    </div>

                    <div class="table-responsive">
                        <table class="table table-hover align-middle">
                            <thead class="table-light">
                                <tr>
                                    <th>Purchase</th>
                                    <th>Date</th>
                                    <th class="text-end">Total</th>
                                    <th class="text-end">Paid</th>
                                    <th class="text-end">Outstanding</th>
                                    <th style="width: 220px;">Payment</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($outstandingPurchases as $purchase)
                                    <tr>
                                        <td>
                                            <div class="fw-semibold">{{ $purchase->purchase_no ?: ('Purchase #' . $purchase->id) }}</div>
                                            <small class="text-muted">{{ $purchase->reference_no ?? $purchase->invoice_serial_no ?? 'Outstanding purchase bill' }}</small>
                                        </td>
                                        <td>{{ optional($purchase->purchase_date ?? $purchase->created_at)->format('d M Y') ?: optional($purchase->created_at)->format('d M Y') }}</td>
                                        <td class="text-end">₦{{ number_format((float) ($purchase->total_amount ?? 0), 2) }}</td>
                                        <td class="text-end">₦{{ number_format((float) ($purchase->paid_amount ?? 0), 2) }}</td>
                                        <td class="text-end fw-semibold">₦{{ number_format((float) $purchase->outstanding_balance, 2) }}</td>
                                        <td>
                                            <div class="input-group">
                                                <input type="number" step="0.01" min="0" max="{{ $purchase->outstanding_balance }}" name="allocations[{{ $purchase->id }}]" class="form-control allocation-input" value="{{ old('allocations.' . $purchase->id) }}" data-max="{{ $purchase->outstanding_balance }}">
                                                <button class="btn btn-outline-secondary fill-allocation" type="button" data-full="{{ $purchase->outstanding_balance }}">Full</button>
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="6" class="text-center text-muted py-4">No outstanding purchases found for this supplier.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    <div class="d-flex justify-content-end mt-4">
                        <button type="submit" class="btn btn-success px-4">Save Supplier Payment</button>
                    </div>
                </form>
            </div>
        </div>

        <div class="card border-0 shadow-sm mt-4">
            <div class="card-header bg-white">
                <h5 class="card-title mb-1">Supplier Payment History</h5>
                <p class="text-muted mb-0">Every payment already recorded for this supplier.</p>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Date</th>
                                <th>Reference</th>
                                <th>Purchase</th>
                                <th>Bank</th>
                                <th>Method</th>
                                <th class="text-end">Amount</th>
                                <th>Note</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($supplierPayments as $payment)
                                <tr>
                                    <td>{{ optional($payment->payment_date)->format('d M Y') ?: optional($payment->created_at)->format('d M Y') }}</td>
                                    <td>{{ $payment->reference ?: ($payment->payment_group ?: '-') }}</td>
                                    <td>{{ $payment->purchase?->purchase_no ?: 'Manual supplier payment' }}</td>
                                    <td>{{ $payment->bank?->name ?: 'Not specified' }}</td>
                                    <td>{{ $payment->method ?: '-' }}</td>
                                    <td class="text-end fw-semibold">₦{{ number_format((float) $payment->amount, 2) }}</td>
                                    <td>{{ $payment->note ?: '-' }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="7" class="text-center text-muted py-4">No supplier payment history recorded yet.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const inputs = Array.from(document.querySelectorAll('.allocation-input'));
    const totalEl = document.querySelector('[data-allocation-total]');
    const bankSelect = document.querySelector('select[name="bank_id"]');
    const bankBalanceEl = document.querySelector('[data-selected-bank-balance]');
    const bankWarningEl = document.querySelector('[data-bank-warning]');
    const paymentForm = document.querySelector('#supplierPaymentForm');

    function formatCurrency(value) {
        return value.toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 });
    }

    function getSelectedBankBalance() {
        if (!bankSelect) {
            return 0;
        }
        const selectedOption = bankSelect.selectedOptions[0];
        if (!selectedOption || !selectedOption.dataset.balance) {
            return 0;
        }
        return parseFloat(selectedOption.dataset.balance.replace(/,/g, '')) || 0;
    }

    function updateBankBalance() {
        if (!bankBalanceEl) {
            return;
        }

        const balance = getSelectedBankBalance();
        bankBalanceEl.textContent = formatCurrency(balance);

        const currentTotal = parseFloat((totalEl?.textContent || '0').replace(/,/g, '')) || 0;
        if (bankSelect?.value && currentTotal > balance && bankWarningEl) {
            bankWarningEl.textContent = 'Selected bank balance is not sufficient for the current payment total of ₦' + formatCurrency(currentTotal) + '. Please choose another account or reduce the payment amount.';
            bankWarningEl.classList.remove('d-none');
        } else if (bankWarningEl) {
            bankWarningEl.classList.add('d-none');
        }
    }

    function updateTotal() {
        const total = inputs.reduce((sum, input) => {
            const max = parseFloat(input.dataset.max || '0');
            let value = parseFloat(input.value || '0');
            if (value < 0 || Number.isNaN(value)) {
                value = 0;
            }
            if (max > 0 && value > max) {
                value = max;
                input.value = max.toFixed(2);
            }
            return sum + value;
        }, 0);

        if (totalEl) {
            totalEl.textContent = formatCurrency(total);
        }
        updateBankBalance();
    }

    document.querySelectorAll('.fill-allocation').forEach(function (button) {
        button.addEventListener('click', function () {
            const input = button.closest('.input-group').querySelector('.allocation-input');
            if (!input) {
                return;
            }
            input.value = parseFloat(button.dataset.full || '0').toFixed(2);
            updateTotal();
        });
    });

    inputs.forEach(function (input) {
        input.addEventListener('input', updateTotal);
    });

    if (bankSelect) {
        bankSelect.addEventListener('change', updateBankBalance);
    }

    if (paymentForm) {
        paymentForm.addEventListener('submit', function (event) {
            const balance = getSelectedBankBalance();
            const total = parseFloat((totalEl?.textContent || '0').replace(/,/g, '')) || 0;
            if (bankSelect?.value && total > balance) {
                event.preventDefault();
                if (bankWarningEl) {
                    bankWarningEl.textContent = 'Cannot save payment because the selected bank does not have enough funds to cover ₦' + formatCurrency(total) + '.';
                    bankWarningEl.classList.remove('d-none');
                    bankWarningEl.scrollIntoView({ behavior: 'smooth', block: 'center' });
                }
            }
        });
    }

    updateTotal();
});
</script>
@endsection
