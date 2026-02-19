@extends('layout.mainlayout')

@section('page-title', 'Add Vendor Transaction')

@section('content')
<div class="page-wrapper">
    <div class="content container-fluid">
        <div class="page-header">
            <div class="content-page-header d-flex align-items-center justify-content-between">
                <div>
                    <h5 class="mb-1">Add Ledger Transaction</h5>
                    <p class="text-muted mb-0">{{ $vendor->name }} ({{ $vendor->email }})</p>
                </div>
                <a href="{{ route('vendors.ledger', ['id' => $vendor->id]) }}" class="btn btn-light border">Back to Ledger</a>
            </div>
        </div>

        <div class="card border-0 shadow-sm">
            <div class="card-body">
                <form action="{{ route('vendors.transactions.store', ['id' => $vendor->id]) }}" method="POST">
                    @csrf
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Transaction Name</label>
                            <input type="text" class="form-control @error('name') is-invalid @enderror" name="name" value="{{ old('name') }}" placeholder="Invoice Payment, Refund, Adjustment" required>
                            @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Reference</label>
                            <input type="text" class="form-control @error('reference') is-invalid @enderror" name="reference" value="{{ old('reference') }}" placeholder="REF-001" required>
                            @error('reference')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Mode</label>
                            <select class="form-control @error('mode') is-invalid @enderror" name="mode" required>
                                <option value="">Select mode</option>
                                <option value="Cash" @selected(old('mode')==='Cash')>Cash</option>
                                <option value="Bank Transfer" @selected(old('mode')==='Bank Transfer')>Bank Transfer</option>
                                <option value="Card" @selected(old('mode')==='Card')>Card</option>
                                <option value="System" @selected(old('mode')==='System')>System</option>
                            </select>
                            @error('mode')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Amount</label>
                            <input type="number" step="0.01" class="form-control @error('amount') is-invalid @enderror" name="amount" value="{{ old('amount') }}" placeholder="Use negative for debit, positive for credit" required>
                            @error('amount')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                    </div>

                    <div class="text-end mt-2">
                        <a href="{{ route('vendors.index') }}" class="btn btn-secondary me-2">Cancel</a>
                        <button type="submit" class="btn btn-primary">Save Transaction</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
