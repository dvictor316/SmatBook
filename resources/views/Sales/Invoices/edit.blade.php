@extends('layout.mainlayout')

@section('content')
<div class="page-wrapper">
    <div class="content container-fluid">
        <div class="row justify-content-center">
            <div class="col-xl-8">
                {{-- Display Validation Errors to see why redirect fails --}}
                @if ($errors->any())
                    <div class="alert alert-danger">
                        <ul>
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                <div class="card shadow-sm border-0">
                    <div class="card-header bg-white py-3">
                        <h4 class="card-title mb-0">Edit Invoice: {{ $invoice->invoice_no }}</h4>
                    </div>
                    <div class="card-body">
                        {{-- Ensure the route name 'invoices.update' is correct in your web.php --}}
                        <form action="{{ route('invoices.update', $invoice->id) }}" method="POST">
                            @csrf
                            @method('PUT')

                            <div class="row">
                                <div class="col-md-12 mb-3">
                                    <label class="form-label fw-bold">Customer Name</label>
                                    <input type="text" name="customer_name" class="form-control" value="{{ old('customer_name', $invoice->customer_name) }}" required>
                                </div>

                                <div class="col-md-6 mb-3">
                                    <label class="form-label fw-bold">Grand Total ($)</label>
                                    <input type="number" step="0.01" name="total" id="total" class="form-control" value="{{ old('total', $invoice->total) }}" required>
                                    <small class="text-muted">This is the final amount (Subtotal + Tax)</small>
                                </div>

                                <div class="col-md-6 mb-3">
                                    <label class="form-label fw-bold">Tax Amount ($)</label>
                                    <input type="number" step="0.01" name="tax" class="form-control" value="{{ old('tax', $invoice->tax) }}">
                                </div>

                                <div class="col-md-6 mb-3">
                                    <label class="form-label fw-bold">Amount Already Paid ($)</label>
                                    <input type="number" step="0.01" name="amount_paid" class="form-control" value="{{ old('amount_paid', $invoice->effective_paid ?? $invoice->amount_paid) }}">
                                    <small class="text-muted">If fully paid, this should equal the total. Due amount will become zero automatically.</small>
                                </div>

                                <div class="col-md-6 mb-3">
                                    <label class="form-label fw-bold">Payment Status</label>
                                    <select name="payment_status" class="form-control" required>
                                        <option value="paid" {{ ($invoice->effective_payment_status ?? $invoice->payment_status) == 'paid' ? 'selected' : '' }}>Paid</option>
                                        <option value="unpaid" {{ ($invoice->effective_payment_status ?? $invoice->payment_status) == 'unpaid' ? 'selected' : '' }}>Unpaid</option>
                                        <option value="partial" {{ ($invoice->effective_payment_status ?? $invoice->payment_status) == 'partial' ? 'selected' : '' }}>Partial</option>
                                    </select>
                                </div>

                                <div class="col-md-6 mb-3">
                                    <label class="form-label fw-bold">Payment Method</label>
                                    <select name="payment_method" class="form-control">
                                        <option value="cash" {{ $invoice->payment_method == 'cash' ? 'selected' : '' }}>Cash</option>
                                        <option value="card" {{ $invoice->payment_method == 'card' ? 'selected' : '' }}>Card</option>
                                        <option value="transfer" {{ $invoice->payment_method == 'transfer' ? 'selected' : '' }}>Bank Transfer</option>
                                    </select>
                                </div>
                            </div>

                            <div class="text-end mt-4">
                                <a href="{{ route('invoices.index') }}" class="btn btn-light border me-2">Cancel</a>
                                <button type="submit" class="btn btn-primary px-4">Save Changes</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
