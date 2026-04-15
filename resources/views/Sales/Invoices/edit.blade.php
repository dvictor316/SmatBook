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

                            {{-- Invoice Items Table --}}
                            <div class="mt-4">
                                <h5 class="mb-3">Invoice Items</h5>
                                <div class="table-responsive">
                                    <table class="table table-bordered align-middle">
                                        <thead class="thead-light">
                                            <tr>
                                                <th>Item Description</th>
                                                <th class="text-center">Quantity</th>
                                                <th class="text-end">Unit Price</th>
                                                <th class="text-end">Total</th>
                                                <th class="text-center">Action</th>
                                            </tr>
                                        </thead>
                                        <tbody id="items-table-body">
                                            @forelse($invoice->items ?? [] as $idx => $item)
                                            <tr>
                                                <td>
                                                    <input type="text" name="items[{{ $idx }}][name]" class="form-control" value="{{ $item->name ?? $item->product_name }}" required>
                                                </td>
                                                <td>
                                                    <input type="number" name="items[{{ $idx }}][quantity]" class="form-control text-center" value="{{ $item->quantity }}" min="1" required>
                                                </td>
                                                <td>
                                                    <input type="number" name="items[{{ $idx }}][price]" class="form-control text-end" value="{{ $item->price }}" min="0" step="0.01" required>
                                                </td>
                                                <td class="text-end">{{ number_format($item->quantity * $item->price, 2) }}</td>
                                                <td class="text-center">
                                                    <button type="button" class="btn btn-sm btn-danger" onclick="removeItemRow(this)"><i class="fa fa-trash"></i></button>
                                                </td>
                                            </tr>
                                            @empty
                                            <tr>
                                                <td colspan="5" class="text-center text-muted">No items found for this invoice.</td>
                                            </tr>
                                            @endforelse
                                        </tbody>
                                    </table>
                                </div>
                                <button type="button" class="btn btn-outline-primary mt-2" onclick="addItemRow()"><i class="fa fa-plus"></i> Add Item</button>
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

@push('scripts')
<script>
function removeItemRow(btn) {
    const row = btn.closest('tr');
    row.parentNode.removeChild(row);
}

function addItemRow() {
    const tbody = document.getElementById('items-table-body');
    const idx = tbody.rows.length;
    const row = document.createElement('tr');
    row.innerHTML = `
        <td><input type="text" name="items[${idx}][name]" class="form-control" required></td>
        <td><input type="number" name="items[${idx}][quantity]" class="form-control text-center" min="1" value="1" required></td>
        <td><input type="number" name="items[${idx}][price]" class="form-control text-end" min="0" step="0.01" value="0.00" required></td>
        <td class="text-end">0.00</td>
        <td class="text-center"><button type="button" class="btn btn-sm btn-danger" onclick="removeItemRow(this)"><i class="fa fa-trash"></i></button></td>
    `;
    tbody.appendChild(row);
}
</script>
@endpush
@endsection
