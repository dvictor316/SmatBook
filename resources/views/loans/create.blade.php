@extends('layout.app')

@section('title', 'New Loan / Overdraft')

@section('content')
<div class="content container-fluid">
    <div class="page-header">
        <div class="row align-items-center">
            <div class="col">
                <h3 class="page-title">New Loan / Overdraft</h3>
                <ul class="breadcrumb">
                    <li class="breadcrumb-item"><a href="{{ route('home') }}">Dashboard</a></li>
                    <li class="breadcrumb-item"><a href="{{ route('loans.index') }}">Loans</a></li>
                    <li class="breadcrumb-item active">New</li>
                </ul>
            </div>
        </div>
    </div>

    <div class="row justify-content-center">
        <div class="col-md-9">
            <div class="card">
                <div class="card-body">
                    @if($errors->any())
                        <div class="alert alert-danger">
                            <ul class="mb-0">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
                        </div>
                    @endif

                    <form action="{{ route('loans.store') }}" method="POST">
                        @csrf
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label fw-semibold">Loan Reference Number <span class="text-danger">*</span></label>
                                <input type="text" name="loan_number" class="form-control @error('loan_number') is-invalid @enderror"
                                       value="{{ old('loan_number') }}" required>
                                @error('loan_number')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-semibold">Type <span class="text-danger">*</span></label>
                                <select name="type" class="form-select @error('type') is-invalid @enderror" required>
                                    <option value="">-- Select Type --</option>
                                    <option value="loan" @selected(old('type') === 'loan')>Loan</option>
                                    <option value="overdraft" @selected(old('type') === 'overdraft')>Overdraft</option>
                                    <option value="credit_line" @selected(old('type') === 'credit_line')>Credit Line</option>
                                </select>
                                @error('type')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-semibold">Lender Name <span class="text-danger">*</span></label>
                                <input type="text" name="lender_name" class="form-control @error('lender_name') is-invalid @enderror"
                                       value="{{ old('lender_name') }}" required>
                                @error('lender_name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-semibold">Associated Bank Account</label>
                                <select name="bank_id" class="form-select @error('bank_id') is-invalid @enderror">
                                    <option value="">-- None --</option>
                                    @foreach($banks as $bank)
                                        <option value="{{ $bank->id }}" @selected(old('bank_id') == $bank->id)>{{ $bank->name }}</option>
                                    @endforeach
                                </select>
                                @error('bank_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                            <div class="col-md-4">
                                <label class="form-label fw-semibold">Principal Amount <span class="text-danger">*</span></label>
                                <input type="number" name="principal_amount" class="form-control @error('principal_amount') is-invalid @enderror"
                                       value="{{ old('principal_amount') }}" step="0.01" min="0.01" required>
                                @error('principal_amount')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                            <div class="col-md-4">
                                <label class="form-label fw-semibold">Interest Rate (%) <span class="text-danger">*</span></label>
                                <input type="number" name="interest_rate" class="form-control @error('interest_rate') is-invalid @enderror"
                                       value="{{ old('interest_rate') }}" step="0.01" min="0" required>
                                @error('interest_rate')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                            <div class="col-md-4">
                                <label class="form-label fw-semibold">Interest Type <span class="text-danger">*</span></label>
                                <select name="interest_type" class="form-select @error('interest_type') is-invalid @enderror" required>
                                    <option value="fixed" @selected(old('interest_type') === 'fixed')>Fixed</option>
                                    <option value="floating" @selected(old('interest_type') === 'floating')>Floating</option>
                                </select>
                                @error('interest_type')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                            <div class="col-md-4">
                                <label class="form-label fw-semibold">Disbursement Date <span class="text-danger">*</span></label>
                                <input type="date" name="disbursement_date" class="form-control @error('disbursement_date') is-invalid @enderror"
                                       value="{{ old('disbursement_date', now()->toDateString()) }}" required>
                                @error('disbursement_date')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                            <div class="col-md-4">
                                <label class="form-label fw-semibold">Maturity Date <span class="text-danger">*</span></label>
                                <input type="date" name="maturity_date" class="form-control @error('maturity_date') is-invalid @enderror"
                                       value="{{ old('maturity_date') }}" required>
                                @error('maturity_date')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                            <div class="col-md-4">
                                <label class="form-label fw-semibold">Tenure (Months)</label>
                                <input type="number" name="tenure_months" class="form-control @error('tenure_months') is-invalid @enderror"
                                       value="{{ old('tenure_months') }}" min="1">
                                @error('tenure_months')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-semibold">Repayment Frequency <span class="text-danger">*</span></label>
                                <select name="repayment_frequency" class="form-select @error('repayment_frequency') is-invalid @enderror" required>
                                    <option value="monthly" @selected(old('repayment_frequency') === 'monthly')>Monthly</option>
                                    <option value="quarterly" @selected(old('repayment_frequency') === 'quarterly')>Quarterly</option>
                                    <option value="annual" @selected(old('repayment_frequency') === 'annual')>Annual</option>
                                    <option value="bullet" @selected(old('repayment_frequency') === 'bullet')>Bullet (Lump Sum)</option>
                                </select>
                                @error('repayment_frequency')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                            <div class="col-12">
                                <label class="form-label fw-semibold">Notes</label>
                                <textarea name="notes" class="form-control" rows="3">{{ old('notes') }}</textarea>
                            </div>
                        </div>

                        <div class="mt-4 d-flex gap-2">
                            <button type="submit" class="btn btn-primary">Save Loan</button>
                            <a href="{{ route('loans.index') }}" class="btn btn-outline-secondary">Cancel</a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
