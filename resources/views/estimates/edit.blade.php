@extends('layout.mainlayout')

@section('page-title', 'Edit Estimate')

@section('content')
@php
    $currencySymbol = config('app.currency_symbol', '₦');
    $statusOptions = ['Draft', 'Sent', 'Accepted', 'Declined', 'Expired'];
    $subtotal = (float) old('subtotal', $estimate->subtotal ?? 0);
    $tax = (float) old('tax', $estimate->tax ?? 0);
    $discount = (float) old('discount', $estimate->discount ?? 0);
    $totalAmount = (float) old('total_amount', $estimate->total_amount ?? 0);
    $issueDate = old('issue_date', optional($estimate->issue_date)->format('Y-m-d'));
    $expiryDate = old('expiry_date', optional($estimate->expiry_date)->format('Y-m-d'));
    $statusValue = old('status', $estimate->status ?? 'Draft');
@endphp

<div class="page-wrapper">
    <div class="content container-fluid">
        <div class="page-header">
            <div class="content-page-header d-flex flex-wrap align-items-center justify-content-between gap-2">
                <div>
                    <h5 class="mb-1">Edit Estimate</h5>
                    <div class="text-muted small">Update totals, dates, and customer information before sending.</div>
                </div>
                <div class="d-flex gap-2">
                    <a href="{{ route('estimates.show', $estimate->id) }}" class="btn btn-light">
                        <i class="fa-solid fa-eye me-2"></i>View
                    </a>
                    <a href="{{ route('estimates.index') }}" class="btn btn-outline-secondary">Back to Estimates</a>
                </div>
            </div>
        </div>

        <form action="{{ route('estimates.update', $estimate->id) }}" method="POST">
            @csrf
            @method('PUT')

            <div class="row g-4">
                <div class="col-lg-8">
                    <div class="card border-0 shadow-sm">
                        <div class="card-header bg-white border-0">
                            <h5 class="mb-1">Estimate Details</h5>
                            <div class="text-muted small">Adjust the commercial details and keep everything aligned.</div>
                        </div>
                        <div class="card-body">
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label class="form-label">Estimate Number</label>
                                    <input type="text" name="estimate_number" class="form-control" value="{{ old('estimate_number', $estimate->estimate_number) }}" required>
                                    @error('estimate_number') <div class="text-danger small">{{ $message }}</div> @enderror
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label d-flex align-items-center justify-content-between gap-2">
                                        <span>Customer</span>
                                        <a href="{{ route('customers.add') }}" class="btn btn-sm btn-outline-primary">
                                            <i class="fa-solid fa-plus me-1"></i>Add Customer
                                        </a>
                                    </label>
                                    <select name="customer_id" class="form-select" required>
                                        <option value="">Select customer</option>
                                        @foreach($customers ?? [] as $customer)
                                            <option value="{{ $customer->id }}" @selected(old('customer_id', $estimate->customer_id) == $customer->id)>
                                                {{ $customer->customer_name ?? $customer->name ?? ('Customer #' . $customer->id) }}
                                            </option>
                                        @endforeach
                                    </select>
                                    @error('customer_id') <div class="text-danger small">{{ $message }}</div> @enderror
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Issue Date</label>
                                    <input type="date" name="issue_date" class="form-control" value="{{ $issueDate }}" required>
                                    @error('issue_date') <div class="text-danger small">{{ $message }}</div> @enderror
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Expiry Date</label>
                                    <input type="date" name="expiry_date" class="form-control" value="{{ $expiryDate }}" required>
                                    @error('expiry_date') <div class="text-danger small">{{ $message }}</div> @enderror
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label">Subtotal ({{ $currencySymbol }})</label>
                                    <input type="number" step="0.01" name="subtotal" class="form-control" value="{{ old('subtotal', $estimate->subtotal ?? 0) }}" required>
                                    @error('subtotal') <div class="text-danger small">{{ $message }}</div> @enderror
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label">Tax ({{ $currencySymbol }})</label>
                                    <input type="number" step="0.01" name="tax" class="form-control" value="{{ old('tax', $estimate->tax ?? 0) }}" required>
                                    @error('tax') <div class="text-danger small">{{ $message }}</div> @enderror
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label">Discount ({{ $currencySymbol }})</label>
                                    <input type="number" step="0.01" name="discount" class="form-control" value="{{ old('discount', $estimate->discount ?? 0) }}">
                                    @error('discount') <div class="text-danger small">{{ $message }}</div> @enderror
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Total Amount ({{ $currencySymbol }})</label>
                                    <input type="number" step="0.01" name="total_amount" class="form-control" value="{{ old('total_amount', $estimate->total_amount ?? 0) }}" required>
                                    @error('total_amount') <div class="text-danger small">{{ $message }}</div> @enderror
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Status</label>
                                    <select name="status" class="form-select" required>
                                        @foreach($statusOptions as $status)
                                            <option value="{{ $status }}" @selected($statusValue === $status)>{{ $status }}</option>
                                        @endforeach
                                    </select>
                                    @error('status') <div class="text-danger small">{{ $message }}</div> @enderror
                                </div>
                                <div class="col-12">
                                    <label class="form-label">Notes</label>
                                    <textarea name="notes" rows="3" class="form-control" placeholder="Add terms or internal notes...">{{ old('notes', $estimate->notes) }}</textarea>
                                    @error('notes') <div class="text-danger small">{{ $message }}</div> @enderror
                                </div>
                            </div>
                        </div>
                        <div class="card-footer bg-white border-0 d-flex justify-content-end gap-2">
                            <a href="{{ route('estimates.show', $estimate->id) }}" class="btn btn-light">Cancel</a>
                            <button type="submit" class="btn btn-primary">
                                <i class="fa-solid fa-floppy-disk me-2"></i>Save Changes
                            </button>
                        </div>
                    </div>
                </div>

                <div class="col-lg-4">
                    <div class="card border-0 shadow-sm mb-4">
                        <div class="card-header bg-white border-0">
                            <h6 class="mb-1">Estimate Summary</h6>
                            <div class="text-muted small">Quick snapshot for approvals.</div>
                        </div>
                        <div class="card-body">
                            <div class="mb-3">
                                <div class="text-muted small">Estimate</div>
                                <div class="fw-semibold">{{ $estimate->estimate_number ?? ('EST-' . str_pad($estimate->id, 5, '0', STR_PAD_LEFT)) }}</div>
                            </div>
                            <div class="mb-3">
                                <div class="text-muted small">Status</div>
                                <span class="badge bg-soft-primary text-primary">{{ $statusValue }}</span>
                            </div>
                            <div class="d-flex justify-content-between mb-2">
                                <span class="text-muted">Subtotal</span>
                                <span class="fw-semibold">{{ $currencySymbol }}{{ number_format($subtotal, 2) }}</span>
                            </div>
                            <div class="d-flex justify-content-between mb-2">
                                <span class="text-muted">Tax</span>
                                <span class="fw-semibold">{{ $currencySymbol }}{{ number_format($tax, 2) }}</span>
                            </div>
                            <div class="d-flex justify-content-between mb-2">
                                <span class="text-muted">Discount</span>
                                <span class="fw-semibold">-{{ $currencySymbol }}{{ number_format($discount, 2) }}</span>
                            </div>
                            <div class="d-flex justify-content-between border-top pt-2">
                                <span class="fw-semibold">Total</span>
                                <span class="fw-bold">{{ $currencySymbol }}{{ number_format($totalAmount, 2) }}</span>
                            </div>
                            <div class="mt-3 text-muted small">
                                Updated {{ optional($estimate->updated_at)->diffForHumans() ?? 'recently' }}.
                            </div>
                        </div>
                    </div>

                    <div class="card border-0 shadow-sm">
                        <div class="card-header bg-white border-0">
                            <h6 class="mb-1">Checklist</h6>
                            <div class="text-muted small">Final review before sending.</div>
                        </div>
                        <div class="card-body">
                            <ul class="text-muted small mb-0">
                                <li>Confirm totals match the customer quote.</li>
                                <li>Align expiry date with commercial policy.</li>
                                <li>Keep notes short and client-ready.</li>
                                <li>Save changes before sending.</li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
        </form>
    </div>
</div>
@endsection
