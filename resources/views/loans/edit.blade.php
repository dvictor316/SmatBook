@extends('layout.app')

@section('title', 'Edit Loan #' . $loan->loan_number)

@section('content')
<div class="content container-fluid">
    <div class="page-header">
        <div class="row align-items-center">
            <div class="col">
                <h3 class="page-title">Edit Loan #{{ $loan->loan_number }}</h3>
                <ul class="breadcrumb">
                    <li class="breadcrumb-item"><a href="{{ route('home') }}">Dashboard</a></li>
                    <li class="breadcrumb-item"><a href="{{ route('loans.index') }}">Loans</a></li>
                    <li class="breadcrumb-item active">Edit</li>
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

                    <form action="{{ route('loans.update', $loan) }}" method="POST">
                        @csrf
                        @method('PUT')
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label fw-semibold">Loan Reference</label>
                                <input type="text" class="form-control bg-light" value="{{ $loan->loan_number }}" disabled>
                                <small class="text-muted">Loan number cannot be changed</small>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-semibold">Status <span class="text-danger">*</span></label>
                                <select name="status" class="form-select @error('status') is-invalid @enderror" required>
                                    <option value="active" @selected(old('status', $loan->status) === 'active')>Active</option>
                                    <option value="closed" @selected(old('status', $loan->status) === 'closed')>Closed</option>
                                    <option value="defaulted" @selected(old('status', $loan->status) === 'defaulted')>Defaulted</option>
                                </select>
                                @error('status')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-semibold">Lender Name <span class="text-danger">*</span></label>
                                <input type="text" name="lender_name" class="form-control @error('lender_name') is-invalid @enderror"
                                       value="{{ old('lender_name', $loan->lender_name) }}" required>
                                @error('lender_name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-semibold">Interest Rate (%)</label>
                                <input type="number" name="interest_rate" class="form-control @error('interest_rate') is-invalid @enderror"
                                       value="{{ old('interest_rate', $loan->interest_rate) }}" step="0.01" min="0">
                                @error('interest_rate')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-semibold">Maturity Date</label>
                                <input type="date" name="maturity_date" class="form-control @error('maturity_date') is-invalid @enderror"
                                       value="{{ old('maturity_date', $loan->maturity_date?->toDateString()) }}">
                                @error('maturity_date')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                            <div class="col-12">
                                <label class="form-label fw-semibold">Notes</label>
                                <textarea name="notes" class="form-control" rows="3">{{ old('notes', $loan->notes) }}</textarea>
                            </div>
                        </div>

                        <div class="mt-4 d-flex gap-2">
                            <button type="submit" class="btn btn-primary">Update Loan</button>
                            <a href="{{ route('loans.show', $loan) }}" class="btn btn-outline-secondary">Cancel</a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
