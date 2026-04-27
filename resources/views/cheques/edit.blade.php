@extends('layout.app')

@section('title', 'Edit Cheque #' . $cheque->cheque_number)

@section('content')
<div class="content container-fluid">
    <div class="page-header">
        <div class="row align-items-center">
            <div class="col">
                <h3 class="page-title">Edit Cheque #{{ $cheque->cheque_number }}</h3>
                <ul class="breadcrumb">
                    <li class="breadcrumb-item"><a href="{{ route('home') }}">Dashboard</a></li>
                    <li class="breadcrumb-item"><a href="{{ route('cheques.index') }}">Cheques</a></li>
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

                    <form action="{{ route('cheques.update', $cheque) }}" method="POST">
                        @csrf
                        @method('PUT')
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label fw-semibold">Cheque Number <span class="text-danger">*</span></label>
                                <input type="text" name="cheque_number" class="form-control @error('cheque_number') is-invalid @enderror"
                                       value="{{ old('cheque_number', $cheque->cheque_number) }}" required>
                                @error('cheque_number')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-semibold">Type <span class="text-danger">*</span></label>
                                <select name="type" class="form-select @error('type') is-invalid @enderror" required id="chequeType" onchange="toggleParty()">
                                    <option value="issue" @selected(old('type', $cheque->type) === 'issue')>Issue</option>
                                    <option value="receive" @selected(old('type', $cheque->type) === 'receive')>Receive</option>
                                </select>
                                @error('type')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-semibold">Status <span class="text-danger">*</span></label>
                                <select name="status" class="form-select @error('status') is-invalid @enderror">
                                    @foreach(['pending','cleared','bounced','cancelled','voided','deposited'] as $s)
                                        <option value="{{ $s }}" @selected(old('status', $cheque->status) === $s)>{{ ucfirst($s) }}</option>
                                    @endforeach
                                </select>
                                @error('status')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-semibold">Bank <span class="text-danger">*</span></label>
                                <select name="bank_id" class="form-select @error('bank_id') is-invalid @enderror" required>
                                    <option value="">-- Select Bank --</option>
                                    @foreach($banks as $bank)
                                        <option value="{{ $bank->id }}" @selected(old('bank_id', $cheque->bank_id) == $bank->id)>{{ $bank->name }}</option>
                                    @endforeach
                                </select>
                                @error('bank_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-semibold">Payee / Payer Name <span class="text-danger">*</span></label>
                                <input type="text" name="payee_name" class="form-control @error('payee_name') is-invalid @enderror"
                                       value="{{ old('payee_name', $cheque->payee_name) }}" required>
                                @error('payee_name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                            <div class="col-md-4">
                                <label class="form-label fw-semibold">Amount <span class="text-danger">*</span></label>
                                <input type="number" name="amount" class="form-control @error('amount') is-invalid @enderror"
                                       value="{{ old('amount', $cheque->amount) }}" step="0.01" min="0.01" required>
                                @error('amount')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                            <div class="col-md-4">
                                <label class="form-label fw-semibold">Cheque Date <span class="text-danger">*</span></label>
                                <input type="date" name="cheque_date" class="form-control @error('cheque_date') is-invalid @enderror"
                                       value="{{ old('cheque_date', $cheque->cheque_date?->toDateString()) }}" required>
                                @error('cheque_date')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                            <div class="col-md-4">
                                <label class="form-label fw-semibold">Due / Clearing Date</label>
                                <input type="date" name="due_date" class="form-control @error('due_date') is-invalid @enderror"
                                       value="{{ old('due_date', $cheque->due_date?->toDateString()) }}">
                                @error('due_date')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>

                            <div class="col-md-6" id="supplierField">
                                <label class="form-label fw-semibold">Supplier (optional)</label>
                                <select name="supplier_id" class="form-select">
                                    <option value="">-- None --</option>
                                    @foreach($suppliers as $s)
                                        <option value="{{ $s->id }}" @selected(old('supplier_id', $cheque->supplier_id) == $s->id)>{{ $s->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-6" id="customerField">
                                <label class="form-label fw-semibold">Customer (optional)</label>
                                <select name="customer_id" class="form-select">
                                    <option value="">-- None --</option>
                                    @foreach($customers as $c)
                                        <option value="{{ $c->id }}" @selected(old('customer_id', $cheque->customer_id) == $c->id)>{{ $c->name }}</option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="col-12">
                                <label class="form-label fw-semibold">Notes</label>
                                <textarea name="notes" class="form-control" rows="3">{{ old('notes', $cheque->notes) }}</textarea>
                            </div>
                        </div>

                        <div class="mt-4 d-flex gap-2">
                            <button type="submit" class="btn btn-primary">Update Cheque</button>
                            <a href="{{ route('cheques.show', $cheque) }}" class="btn btn-outline-secondary">Cancel</a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
    function toggleParty() {
        const type = document.getElementById('chequeType').value;
        document.getElementById('supplierField').style.display = (type === 'issue' || type === '') ? '' : 'none';
        document.getElementById('customerField').style.display = (type === 'receive' || type === '') ? '' : 'none';
    }
    toggleParty();
</script>
@endpush
@endsection
