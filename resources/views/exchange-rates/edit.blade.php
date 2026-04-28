@extends('layout.app')

@section('title', 'Edit Exchange Rate')

@section('content')
<div class="page-wrapper">
<div class="content container-fluid">
    <div class="page-header">
        <div class="row align-items-center">
            <div class="col">
                <h3 class="page-title">Edit Exchange Rate</h3>
                <ul class="breadcrumb">
                    <li class="breadcrumb-item"><a href="{{ route('home') }}">Dashboard</a></li>
                    <li class="breadcrumb-item"><a href="{{ route('exchange-rates.index') }}">Exchange Rates</a></li>
                    <li class="breadcrumb-item active">Edit</li>
                </ul>
            </div>
        </div>
    </div>

    <div class="row justify-content-center">
        <div class="col-md-7">
            <div class="card">
                <div class="card-body">
                    @if($errors->any())
                        <div class="alert alert-danger">
                            <ul class="mb-0">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
                        </div>
                    @endif

                    <form action="{{ route('exchange-rates.update', $exchangeRate) }}" method="POST">
                        @csrf
                        @method('PUT')
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label fw-semibold">Base Currency <span class="text-danger">*</span></label>
                                <input type="text" name="base_currency" class="form-control text-uppercase @error('base_currency') is-invalid @enderror"
                                       value="{{ old('base_currency', $exchangeRate->base_currency) }}" maxlength="3" required>
                                @error('base_currency')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-semibold">Target Currency <span class="text-danger">*</span></label>
                                <input type="text" name="target_currency" class="form-control text-uppercase @error('target_currency') is-invalid @enderror"
                                       value="{{ old('target_currency', $exchangeRate->target_currency) }}" maxlength="3" required>
                                @error('target_currency')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-semibold">Rate <span class="text-danger">*</span></label>
                                <input type="number" name="rate" class="form-control @error('rate') is-invalid @enderror"
                                       value="{{ old('rate', $exchangeRate->rate) }}" step="0.000001" min="0.000001" required>
                                <div class="form-text">1 Base = ? Target</div>
                                @error('rate')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-semibold">Effective Date <span class="text-danger">*</span></label>
                                <input type="date" name="effective_date" class="form-control @error('effective_date') is-invalid @enderror"
                                       value="{{ old('effective_date', $exchangeRate->effective_date?->toDateString()) }}" required>
                                @error('effective_date')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-semibold">Source</label>
                                <select name="source" class="form-select">
                                    <option value="manual" @selected(old('source', $exchangeRate->source) === 'manual')>Manual Entry</option>
                                    <option value="central_bank" @selected(old('source', $exchangeRate->source) === 'central_bank')>Central Bank</option>
                                    <option value="api" @selected(old('source', $exchangeRate->source) === 'api')>API / Auto</option>
                                </select>
                            </div>
                            <div class="col-md-6 d-flex align-items-end">
                                <div class="form-check form-switch">
                                    <input class="form-check-input" type="checkbox" name="is_active" value="1" id="isActive"
                                           {{ old('is_active', $exchangeRate->is_active) ? 'checked' : '' }}>
                                    <label class="form-check-label fw-semibold" for="isActive">Active</label>
                                </div>
                            </div>
                        </div>

                        <div class="mt-4 d-flex gap-2">
                            <button type="submit" class="btn btn-primary">Update Rate</button>
                            <a href="{{ route('exchange-rates.index') }}" class="btn btn-outline-secondary">Cancel</a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
</div>
@endsection
