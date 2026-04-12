<?php $page = 'payments'; ?>
@extends('layout.mainlayout')

@section('content')
@php
    $paymentUser = auth()->user();
    $paymentPlan = strtolower((string) ($paymentUser?->subscription?->plan_name ?? $paymentUser?->subscription?->plan ?? $paymentUser?->company?->plan ?? 'basic'));
    $paymentRole = strtolower((string) ($paymentUser?->role ?? ''));
    $canManagePaymentApprovals = in_array($paymentRole, ['super_admin', 'superadmin', 'administrator', 'admin'], true)
        || str_contains($paymentPlan, 'professional')
        || str_contains($paymentPlan, 'pro')
        || str_contains($paymentPlan, 'enterprise');
@endphp
@php
    $currencyCode = $geoCurrency ?? \App\Support\GeoCurrency::currentCurrency();
    $currencyLocale = $geoCurrencyLocale ?? \App\Support\GeoCurrency::currentLocale();
@endphp
<style>
    .money-sm { font-size: 1.05rem !important; font-variant-numeric: tabular-nums; }
    .money-cell { font-size: 0.84rem; font-variant-numeric: tabular-nums; }
    .metric-count { font-size: 1.25rem !important; font-variant-numeric: tabular-nums; }
</style>
<div class="page-wrapper">
    <div class="content container-fluid">

        {{-- Header Section --}}
        @component('components.page-header')
            @slot('title') Payments & Revenue @endslot
            @slot('subtitle') Real-time income tracking and ledger postings @endslot
        @endcomponent

        {{-- Summary Cards --}}
        <div class="row mb-4">
            <div class="col-xl-4 col-sm-6 col-12">
                <div class="card shadow-sm border-0 h-100">
                    <div class="card-body">
                        <div class="d-flex align-items-center justify-content-between">
                            <div>
                                <h6 class="text-uppercase text-muted fw-semibold mb-2">Total Transactions</h6>
                                <h3 class="mb-0 metric-count">{{ number_format($payments->total()) }}</h3>
                            </div>
                            <div class="avatar avatar-lg bg-primary-light">
                                <i class="fas fa-file-invoice-dollar text-primary fs-4"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="col-xl-4 col-sm-6 col-12">
                <div class="card shadow-sm border-0 h-100">
                    <div class="card-body">
                        <div class="d-flex align-items-center justify-content-between">
                            <div>
                                <h6 class="text-uppercase text-muted fw-semibold mb-2">Net Revenue</h6>
                                <h3 class="mb-0 text-success money-sm">{{ \App\Support\GeoCurrency::format((float) $payments->sum('amount'), 'NGN', $currencyCode, $currencyLocale) }}</h3>
                            </div>
                            <div class="avatar avatar-lg bg-success-light">
                                <i class="fas fa-coins text-success fs-4"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-xl-4 col-12">
                <div class="card shadow-sm border-0 bg-primary text-white h-100">
                    <div class="card-body d-flex align-items-center justify-content-center">
                        <button type="button" class="btn btn-light btn-lg w-100 shadow-sm" data-bs-toggle="modal" data-bs-target="#add_payment">
                            <i class="fas fa-plus-circle me-2"></i> Record New Payment
                        </button>
                    </div>
                </div>
            </div>
        </div>

        {{-- Payments Table --}}
        <div class="card shadow-sm border-0">
            <div class="card-header bg-white py-3">
                <h5 class="card-title mb-0 text-dark">Recent Ledger Postings</h5>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0 datatable">
                        <thead class="bg-light">
                            <tr>
                                <th class="ps-4">Reference</th>
                                <th>Sale Ref</th>
                                <th>Destination Account</th>
                                <th>Amount</th>
                                <th>Status</th>
                                <th>Date</th>
                                <th class="text-end pe-4">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($payments as $payment)
                            <tr>
                                <td class="ps-4">
                                    <span class="fw-bold text-dark">{{ $payment->payment_id }}</span>
                                </td>
                                <td>
                                    @if($payment->sale)
                                        <span class="badge rounded-pill bg-info-light text-info">{{ $payment->sale->sale_id }}</span>
                                    @else
                                        <span class="text-muted italic">Direct Entry</span>
                                    @endif
                                </td>
                                <td>
                                    <div class="d-flex align-items-center">
                                        <div class="avatar-xs me-2">
                                            <span class="avatar-title rounded-circle bg-soft-primary text-primary">
                                                <i class="fas fa-university small"></i>
                                            </span>
                                        </div>
                                        <span>{{ $payment->method }}</span>
                                    </div>
                                </td>
                                <td>
                                    <span class="fw-bold text-success money-cell">+ {{ \App\Support\GeoCurrency::format((float) ($payment->amount ?? 0), 'NGN', $currencyCode, $currencyLocale) }}</span>
                                </td>
                                <td>
                                    @php($statusLabel = trim((string) ($payment->status ?? 'Pending')))
                                    @php($statusClass = match (strtolower($statusLabel)) {
                                        'completed' => 'success',
                                        'pending approval' => 'warning',
                                        'rejected' => 'danger',
                                        default => 'secondary',
                                    })
                                    <span class="badge bg-{{ $statusClass }}-subtle text-{{ $statusClass }} border border-{{ $statusClass }}-subtle">
                                        {{ $statusLabel }}
                                    </span>
                                </td>
                                <td>{{ $payment->created_at->format('M d, Y') }}</td>
                                <td class="text-end pe-4">
                                    <div class="dropdown">
                                        <button class="btn btn-link text-muted p-0 action-icon" data-bs-toggle="dropdown">
                                            <i class="fas fa-ellipsis-v"></i>
                                        </button>
                                        <div class="dropdown-menu dropdown-menu-end shadow-sm">
                                            <a class="dropdown-item" href="{{ route('payments.show', $payment->id) }}">
                                                <i class="fas fa-eye me-2 text-info"></i>View Details
                                            </a>
                                            <a class="dropdown-item" href="{{ route('payments.receipt', $payment->id) }}" target="_blank">
                                                <i class="fas fa-print me-2 text-primary"></i>Print Receipt
                                            </a>
                                            @if($canManagePaymentApprovals && !in_array((int) $payment->id, $pendingApprovalIds ?? [], true) && strtolower((string) ($payment->status ?? '')) !== 'pending approval')
                                                <form action="{{ route('finance.approvals.from-payment', $payment->id) }}" method="POST">
                                                    @csrf
                                                    <button type="submit" class="dropdown-item">
                                                        <i class="fas fa-user-check me-2 text-warning"></i>Submit For Approval
                                                    </button>
                                                </form>
                                            @elseif($canManagePaymentApprovals && (in_array((int) $payment->id, $pendingApprovalIds ?? [], true) || strtolower((string) ($payment->status ?? '')) === 'pending approval'))
                                                <span class="dropdown-item-text text-muted">
                                                    <i class="fas fa-clock me-2 text-warning"></i>Awaiting Approval
                                                </span>
                                            @endif
                                            <div class="dropdown-divider"></div>
                                            <form action="{{ route('payments.destroy', $payment->id) }}" method="POST">
                                                @csrf @method('DELETE')
                                                <button class="dropdown-item text-danger" onclick="return confirm('Confirm reversal of this ledger entry?')">
                                                    <i class="far fa-trash-alt me-2"></i>Delete & Reverse
                                                </button>
                                            </form>
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

{{-- Add Payment Modal --}}
<div class="modal fade" id="add_payment" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content border-0">
            <div class="modal-header bg-dark text-white p-4">
                <h5 class="modal-title"><i class="fas fa-file-invoice-dollar me-2 text-warning"></i>New Payment Entry</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form action="{{ route('payments.store') }}" method="POST" enctype="multipart/form-data">
                @csrf
                <div class="modal-body p-4">
                    @if(!empty($selectedCustomer))
                        <div class="alert alert-info d-flex align-items-center gap-2">
                            <i class="fe fe-user"></i>
                            <div>
                                Recording repayment for <strong>{{ $selectedCustomer->customer_name ?? $selectedCustomer->name }}</strong>.
                                @if(!empty($selectedCustomer->phone))
                                    <span class="text-muted">({{ $selectedCustomer->phone }})</span>
                                @endif
                            </div>
                        </div>
                    @endif
                    <div class="row g-4">
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Customer (Optional)</label>
                            @if(!empty($selectedCustomer))
                                <input type="hidden" name="customer_id" value="{{ $selectedCustomer->id }}">
                                <div class="form-control bg-light">
                                    {{ $selectedCustomer->customer_name ?? $selectedCustomer->name ?? 'Customer' }}
                                </div>
                            @else
                                <select class="form-select select" name="customer_id">
                                    <option value="">-- Choose Customer --</option>
                                    @foreach(($customers ?? collect()) as $customer)
                                        <option value="{{ $customer->id }}" data-balance="{{ (float) ($customer->balance ?? 0) }}">
                                            {{ $customer->customer_name ?? $customer->name ?? 'Customer' }}
                                        </option>
                                    @endforeach
                                </select>
                            @endif
                        </div>

                        <div class="col-md-6">
                            <label class="form-label fw-bold">Link to Sale Reference (Optional)</label>
                            <select class="form-select select" name="sale_id">
                                <option value="">-- Choose Sale --</option>
                                @forelse($sales as $sale)
                                    <option value="{{ $sale->id }}" data-balance="{{ (float) ($sale->balance ?? 0) }}" @selected(!empty($selectedSaleId) && (int) $selectedSaleId === (int) $sale->id)>
                                        {{ $sale->invoice_no ?? ('SALE-' . $sale->id) }} (Bal: {{ number_format((float) ($sale->balance ?? 0), 2) }})
                                    </option>
                                @empty
                                    <option value="" disabled>No unpaid sales available for this customer.</option>
                                @endforelse
                            </select>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label fw-bold">Deposit Account (Debit)</label>
                            <select class="form-select select" name="payment_account_id">
                                <option value="">-- Choose Ledger Account --</option>
                                @foreach($assetAccounts as $acc)
                                    <option value="{{ $acc->id }}">{{ $acc->name }} ({{ $acc->code }})</option>
                                @endforeach
                            </select>
                            @if(!empty($bankAccounts) && $bankAccounts->count())
                                <small class="text-muted d-block mt-1">Or pick a bank account below.</small>
                            @endif
                        </div>

                        <div class="col-md-6">
                            <label class="form-label fw-bold text-primary">Transaction Amount</label>
                            <div class="input-group">
                                <span class="input-group-text bg-primary text-white border-primary">$</span>
                                <input type="number" class="form-control border-primary" name="amount" id="payment-amount" step="0.01" required placeholder="0.00" value="{{ old('amount', $selectedSaleBalance ?? $outstandingBalance ?? '') }}">
                            </div>
                            @if(!empty($outstandingBalance))
                                <small class="text-muted">Outstanding for customer: ₦{{ number_format($outstandingBalance, 2) }}</small>
                            @endif
                        </div>

                        @if(!empty($bankAccounts) && $bankAccounts->count())
                            <div class="col-md-6">
                                <label class="form-label fw-bold">Bank Account</label>
                                <select class="form-select select" name="bank_id">
                                    <option value="">-- Choose Bank --</option>
                                    @foreach($bankAccounts as $bank)
                                        <option value="{{ $bank->id }}">{{ $bank->name ?? $bank->bank_name ?? 'Bank' }}</option>
                                    @endforeach
                                </select>
                            </div>
                        @endif
                        @if(($sales ?? collect())->count() > 0)
                            <div class="col-12">
                                <div class="border rounded p-3 bg-light">
                                    <div class="d-flex justify-content-between align-items-center mb-2">
                                        <strong>Outstanding Invoices</strong>
                                        <span class="text-muted small">Click a row to apply the payment amount.</span>
                                    </div>
                                    <div class="table-responsive">
                                        <table class="table table-sm align-middle mb-0">
                                            <thead>
                                                <tr>
                                                    <th>Invoice</th>
                                                    <th>Date</th>
                                                    <th class="text-end">Open Balance</th>
                                                    <th class="text-end">Total</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                @foreach($sales as $sale)
                                                    <tr class="select-sale-row" data-sale-id="{{ $sale->id }}" data-balance="{{ (float) ($sale->balance ?? 0) }}">
                                                        <td>{{ $sale->invoice_no ?? ('SALE-' . $sale->id) }}</td>
                                                        <td>{{ optional($sale->created_at)->format('d M Y') }}</td>
                                                        <td class="text-end">₦{{ number_format((float) ($sale->balance ?? 0), 2) }}</td>
                                                        <td class="text-end">₦{{ number_format((float) ($sale->total ?? 0), 2) }}</td>
                                                    </tr>
                                                @endforeach
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        @endif

                        <div class="col-md-6">
                            <label class="form-label fw-bold">Bank Reference No.</label>
                            <input type="text" class="form-control" name="reference" placeholder="e.g., CHQ-001 or WIRE-REF">
                        </div>

                        <div class="col-md-6">
                            <label class="form-label fw-bold">Method</label>
                            <select class="form-select" name="method">
                                <option value="cash">Cash</option>
                                <option value="transfer">Transfer</option>
                                <option value="pos">POS</option>
                                <option value="card">Card</option>
                            </select>
                        </div>

                        <div class="col-12">
                            <label class="form-label fw-bold">Notes / Ledger Description</label>
                            <textarea class="form-control" name="note" rows="2" placeholder="Briefly describe the purpose of this payment..."></textarea>
                        </div>

                        <div class="col-12">
                            <div class="p-3 bg-light rounded border border-dashed text-center">
                                <label class="form-label d-block mb-2 fw-bold">Supporting Document (PDF/JPG)</label>
                                <input type="file" class="form-control" name="attachment">
                            </div>
                        </div>

                        @if($canManagePaymentApprovals)
                            <div class="col-12">
                                <div class="form-check form-switch border rounded p-3 bg-light">
                                    <input class="form-check-input" type="checkbox" role="switch" id="requireApproval" name="require_approval" value="1">
                                    <label class="form-check-label fw-bold ms-2" for="requireApproval">Require approval before final ledger posting</label>
                                    <div class="text-muted small mt-1">Use this for high-value receipts or sensitive payment adjustments.</div>
                                </div>
                            </div>
                        @endif
                    </div>
                </div>
                <div class="modal-footer bg-light border-0 p-4">
                    <button type="button" class="btn btn-outline-secondary px-4" data-bs-dismiss="modal">Close</button>
                    <button type="submit" class="btn btn-primary px-5 shadow">Confirm & Post Entry</button>
                </div>
            </form>
        </div>
    </div>
</div>
@if(!empty($openPayment))
    @push('scripts')
        <script>
            document.addEventListener('DOMContentLoaded', function () {
                const modalEl = document.getElementById('add_payment');
                if (modalEl && window.bootstrap) {
                    const modal = bootstrap.Modal.getOrCreateInstance(modalEl);
                    modal.show();
                }
            });
        </script>
    @endpush
@endif

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function () {
        const saleSelect = document.querySelector('select[name="sale_id"]');
        const customerSelect = document.querySelector('select[name="customer_id"]');
        const amountInput = document.getElementById('payment-amount');
        if (!saleSelect || !amountInput) return;

        const updateAmountFromSale = () => {
            const selectedOption = saleSelect.options[saleSelect.selectedIndex];
            if (!selectedOption) return;
            const balance = parseFloat(selectedOption.getAttribute('data-balance') || '0');
            if (balance > 0) {
                amountInput.value = balance.toFixed(2);
            }
        };

        saleSelect.addEventListener('change', updateAmountFromSale);
        if (customerSelect) {
            customerSelect.addEventListener('change', () => {
                if (saleSelect.value) {
                    return;
                }
                const selectedCustomer = customerSelect.options[customerSelect.selectedIndex];
                const balance = parseFloat(selectedCustomer?.getAttribute('data-balance') || '0');
                if (balance > 0) {
                    amountInput.value = balance.toFixed(2);
                }
            });
        }
        document.querySelectorAll('.select-sale-row').forEach((row) => {
            row.addEventListener('click', () => {
                const saleId = row.getAttribute('data-sale-id');
                const balance = parseFloat(row.getAttribute('data-balance') || '0');
                if (saleId) {
                    saleSelect.value = saleId;
                    saleSelect.dispatchEvent(new Event('change'));
                }
                if (balance > 0) {
                    amountInput.value = balance.toFixed(2);
                }
            });
        });
        if (saleSelect.value) {
            updateAmountFromSale();
        }
    });
</script>
@endpush
@endsection
