@extends('layout.mainlayout')

@section('page-title', 'Create Estimate')

@section('content')
@php
    $currencySymbol = config('app.currency_symbol', '₦');
    $statusOptions = ['Draft', 'Sent', 'Accepted', 'Declined', 'Expired'];
@endphp

<div class="page-wrapper">
    <div class="content container-fluid">
        @component('components.page-header')
            @slot('title')
                Create Estimate
            @endslot
        @endcomponent

        <form action="{{ route('estimates.store') }}" method="POST">
            @csrf

            <div class="row g-4">
                <div class="col-lg-8">
                    <div class="card border-0 shadow-sm">
                        <div class="card-header bg-white border-0">
                            <h5 class="mb-1">Estimate Details</h5>
                            <div class="text-muted small">Capture the customer, dates, and commercial terms.</div>
                        </div>
                        <div class="card-body">
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label class="form-label">Estimate Number</label>
                                    <input type="text" name="estimate_number" class="form-control" value="{{ old('estimate_number') }}" required>
                                    @error('estimate_number') <div class="text-danger small">{{ $message }}</div> @enderror
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Customer</label>
                                    <select name="customer_id" class="form-select" required>
                                        <option value="">Select customer</option>
                                        @foreach($customers ?? [] as $customer)
                                            <option value="{{ $customer->id }}" @selected(old('customer_id') == $customer->id)>
                                                {{ $customer->name ?? ('Customer #' . $customer->id) }}
                                            </option>
                                        @endforeach
                                    </select>
                                    @error('customer_id') <div class="text-danger small">{{ $message }}</div> @enderror
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Issue Date</label>
                                    <input type="date" name="issue_date" class="form-control" value="{{ old('issue_date') }}" required>
                                    @error('issue_date') <div class="text-danger small">{{ $message }}</div> @enderror
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Expiry Date</label>
                                    <input type="date" name="expiry_date" class="form-control" value="{{ old('expiry_date') }}" required>
                                    @error('expiry_date') <div class="text-danger small">{{ $message }}</div> @enderror
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label">Subtotal ({{ $currencySymbol }})</label>
                                    <input type="number" step="0.01" name="subtotal" class="form-control" value="{{ old('subtotal', 0) }}" required>
                                    @error('subtotal') <div class="text-danger small">{{ $message }}</div> @enderror
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label">Tax ({{ $currencySymbol }})</label>
                                    <input type="number" step="0.01" name="tax" class="form-control" value="{{ old('tax', 0) }}" required>
                                    @error('tax') <div class="text-danger small">{{ $message }}</div> @enderror
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label">Discount ({{ $currencySymbol }})</label>
                                    <input type="number" step="0.01" name="discount" class="form-control" value="{{ old('discount', 0) }}">
                                    @error('discount') <div class="text-danger small">{{ $message }}</div> @enderror
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Total Amount ({{ $currencySymbol }})</label>
                                    <input type="number" step="0.01" name="total_amount" class="form-control" value="{{ old('total_amount', 0) }}" required>
                                    @error('total_amount') <div class="text-danger small">{{ $message }}</div> @enderror
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Status</label>
                                    <select name="status" class="form-select" required>
                                        @foreach($statusOptions as $status)
                                            <option value="{{ $status }}" @selected(old('status', 'Draft') === $status)>{{ $status }}</option>
                                        @endforeach
                                    </select>
                                    @error('status') <div class="text-danger small">{{ $message }}</div> @enderror
                                </div>
                                <div class="col-12">
                                    <label class="form-label">Notes</label>
                                    <textarea name="notes" rows="3" class="form-control" placeholder="Add terms or internal notes...">{{ old('notes') }}</textarea>
                                    @error('notes') <div class="text-danger small">{{ $message }}</div> @enderror
                                </div>
                            </div>
                        </div>
                        <div class="card-footer bg-white border-0 d-flex justify-content-end gap-2">
                            <a href="{{ route('estimates.index') }}" class="btn btn-light">Cancel</a>
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save me-2"></i>Save Estimate
                            </button>
                        </div>
                    </div>
                </div>

                <div class="col-lg-4">
                    <div class="card border-0 shadow-sm">
                        <div class="card-header bg-white border-0">
                            <h6 class="mb-1">Quick Tips</h6>
                            <div class="text-muted small">Keep estimates accurate and on time.</div>
                        </div>
                        <div class="card-body">
                            <ul class="text-muted small mb-0">
                                <li>Use consistent estimate numbers for tracking.</li>
                                <li>Set expiry dates to drive faster approvals.</li>
                                <li>Add discounts as separate line items when needed.</li>
                                <li>Double‑check totals before sending.</li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
        </form>
    </div>
</div>
@endsection
