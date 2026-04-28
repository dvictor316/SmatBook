@extends('layout.app')

@section('title', 'Loan #' . $loan->loan_number)

@section('content')
<div class="page-wrapper">
<div class="content container-fluid">
    <div class="page-header">
        <div class="row align-items-center">
            <div class="col">
                <h3 class="page-title">Loan #{{ $loan->loan_number }}</h3>
                <ul class="breadcrumb">
                    <li class="breadcrumb-item"><a href="{{ route('home') }}">Dashboard</a></li>
                    <li class="breadcrumb-item"><a href="{{ route('loans.index') }}">Loans</a></li>
                    <li class="breadcrumb-item active">#{{ $loan->loan_number }}</li>
                </ul>
            </div>
            <div class="col-auto d-flex gap-2">
                <a href="{{ route('loans.edit', $loan) }}" class="btn btn-sm btn-outline-primary">Edit</a>
                <form action="{{ route('loans.destroy', $loan) }}" method="POST" onsubmit="return confirm('Delete this loan?')">
                    @csrf @method('DELETE')
                    <button class="btn btn-sm btn-outline-danger">Delete</button>
                </form>
            </div>
        </div>
    </div>

    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    <div class="row">
        {{-- Loan Details --}}
        <div class="col-md-6">
            <div class="card">
                <div class="card-header"><h5 class="card-title mb-0">Loan Details</h5></div>
                <div class="card-body">
                    <table class="table table-borderless table-sm">
                        <tr><th width="45%">Reference</th><td>{{ $loan->loan_number }}</td></tr>
                        <tr>
                            <th>Type</th>
                            <td><span class="badge bg-info text-dark">{{ ucwords(str_replace('_', ' ', $loan->type)) }}</span></td>
                        </tr>
                        <tr>
                            <th>Status</th>
                            <td>
                                @php
                                    $sc = match($loan->status) { 'active' => 'success', 'closed' => 'secondary', 'defaulted' => 'danger', default => 'warning' };
                                @endphp
                                <span class="badge bg-{{ $sc }}">{{ ucfirst($loan->status) }}</span>
                            </td>
                        </tr>
                        <tr><th>Lender</th><td>{{ $loan->lender_name }}</td></tr>
                        <tr><th>Bank Account</th><td>{{ $loan->bank?->name ?? '—' }}</td></tr>
                        <tr><th>Principal</th><td><strong>{{ number_format($loan->principal_amount, 2) }}</strong></td></tr>
                        <tr><th>Interest Rate</th><td>{{ $loan->interest_rate }}% ({{ ucfirst($loan->interest_type) }})</td></tr>
                        <tr><th>Repayment</th><td>{{ ucfirst(str_replace('_', ' ', $loan->repayment_frequency)) }}</td></tr>
                        <tr><th>Disbursement</th><td>{{ $loan->disbursement_date?->format('d M Y') ?? '—' }}</td></tr>
                        <tr><th>Maturity</th><td>{{ $loan->maturity_date?->format('d M Y') ?? '—' }}</td></tr>
                        <tr><th>Tenure</th><td>{{ $loan->tenure_months ? $loan->tenure_months . ' months' : '—' }}</td></tr>
                        @if($loan->notes)
                        <tr><th>Notes</th><td>{{ $loan->notes }}</td></tr>
                        @endif
                    </table>
                </div>
            </div>
        </div>

        {{-- Balances & Add Repayment --}}
        <div class="col-md-6">
            <div class="card mb-3">
                <div class="card-header"><h5 class="card-title mb-0">Balance Summary</h5></div>
                <div class="card-body">
                    <div class="row text-center">
                        <div class="col-4">
                            <div class="text-muted small">Principal</div>
                            <div class="fs-5 fw-bold">{{ number_format($loan->principal_amount, 2) }}</div>
                        </div>
                        <div class="col-4">
                            <div class="text-muted small">Total Paid</div>
                            <div class="fs-5 fw-bold text-success">{{ number_format($loan->repayments->sum(fn($r) => $r->principal_paid + $r->interest_paid), 2) }}</div>
                        </div>
                        <div class="col-4">
                            <div class="text-muted small">Outstanding</div>
                            <div class="fs-5 fw-bold text-danger">{{ number_format($loan->outstanding_balance, 2) }}</div>
                        </div>
                    </div>
                </div>
            </div>

            @if($loan->status === 'active')
            <div class="card">
                <div class="card-header"><h5 class="card-title mb-0">Record Repayment</h5></div>
                <div class="card-body">
                    <form action="{{ route('loans.repayments.store', $loan) }}" method="POST">
                        @csrf
                        <div class="row g-2">
                            <div class="col-6">
                                <label class="form-label fw-semibold">Payment Date <span class="text-danger">*</span></label>
                                <input type="date" name="payment_date" class="form-control @error('payment_date') is-invalid @enderror"
                                       value="{{ old('payment_date', now()->toDateString()) }}" required>
                                @error('payment_date')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                            <div class="col-6">
                                <label class="form-label fw-semibold">Method <span class="text-danger">*</span></label>
                                <select name="payment_method" class="form-select @error('payment_method') is-invalid @enderror" required>
                                    <option value="cash">Cash</option>
                                    <option value="transfer">Transfer</option>
                                    <option value="cheque">Cheque</option>
                                </select>
                            </div>
                            <div class="col-6">
                                <label class="form-label fw-semibold">Principal Paid <span class="text-danger">*</span></label>
                                <input type="number" name="principal_paid" class="form-control @error('principal_paid') is-invalid @enderror"
                                       value="{{ old('principal_paid', 0) }}" step="0.01" min="0" required>
                                @error('principal_paid')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                            <div class="col-6">
                                <label class="form-label fw-semibold">Interest Paid</label>
                                <input type="number" name="interest_paid" class="form-control"
                                       value="{{ old('interest_paid', 0) }}" step="0.01" min="0">
                            </div>
                            <div class="col-12">
                                <label class="form-label fw-semibold">Reference</label>
                                <input type="text" name="reference" class="form-control" value="{{ old('reference') }}" placeholder="Transaction ref / cheque no.">
                            </div>
                            <div class="col-12">
                                <label class="form-label fw-semibold">Notes</label>
                                <textarea name="notes" class="form-control" rows="2">{{ old('notes') }}</textarea>
                            </div>
                        </div>
                        <button type="submit" class="btn btn-primary mt-3 w-100">Record Payment</button>
                    </form>
                </div>
            </div>
            @endif
        </div>
    </div>

    {{-- Repayments History --}}
    <div class="card mt-3">
        <div class="card-header"><h5 class="card-title mb-0">Repayment History</h5></div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Date</th>
                            <th>Principal Paid</th>
                            <th>Interest Paid</th>
                            <th>Total</th>
                            <th>Method</th>
                            <th>Reference</th>
                            <th>Notes</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($loan->repayments->sortByDesc('payment_date') as $rep)
                        <tr>
                            <td>{{ $rep->payment_date?->format('d M Y') }}</td>
                            <td>{{ number_format($rep->principal_paid, 2) }}</td>
                            <td>{{ number_format($rep->interest_paid, 2) }}</td>
                            <td><strong>{{ number_format($rep->principal_paid + $rep->interest_paid, 2) }}</strong></td>
                            <td>{{ ucfirst($rep->payment_method) }}</td>
                            <td>{{ $rep->reference ?? '—' }}</td>
                            <td>{{ $rep->notes ?? '—' }}</td>
                        </tr>
                        @empty
                        <tr><td colspan="7" class="text-center text-muted py-3">No repayments recorded yet.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
</div>
@endsection
