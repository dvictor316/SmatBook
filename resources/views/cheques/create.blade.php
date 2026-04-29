@extends('layout.mainlayout')

@section('title', 'New Cheque')

@section('content')
<div class="page-wrapper">
<div class="content container-fluid">
    <div class="page-header">
        <div class="row align-items-center">
            <div class="col">
                <h3 class="page-title">New Cheque</h3>
                <ul class="breadcrumb">
                    <li class="breadcrumb-item"><a href="{{ route('home') }}">Dashboard</a></li>
                    <li class="breadcrumb-item"><a href="{{ route('cheques.index') }}">Cheques</a></li>
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

                    <form action="{{ route('cheques.store') }}" method="POST">
                        @csrf
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label fw-semibold">Cheque Number <span class="text-danger">*</span></label>
                                <input type="text" name="cheque_number" class="form-control @error('cheque_number') is-invalid @enderror"
                                       value="{{ old('cheque_number') }}" required>
                                @error('cheque_number')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-semibold">Type <span class="text-danger">*</span></label>
                                <select name="type" class="form-select @error('type') is-invalid @enderror" required id="chequeType" onchange="toggleParty()">
                                    <option value="">-- Select Type --</option>
                                    <option value="issue" @selected(old('type') === 'issue')>Issue (we write a cheque)</option>
                                    <option value="receive" @selected(old('type') === 'receive')>Receive (we receive a cheque)</option>
                                </select>
                                @error('type')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-semibold">Bank <span class="text-danger">*</span></label>
                                <select name="bank_id" class="form-select @error('bank_id') is-invalid @enderror" required>
                                    <option value="">-- Select Bank --</option>
                                    @foreach($banks as $bank)
                                        <option value="{{ $bank->id }}" @selected(old('bank_id') == $bank->id)>{{ $bank->name }}</option>
                                    @endforeach
                                </select>
                                @error('bank_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-semibold">Payee / Payer Name <span class="text-danger">*</span></label>
                                <input type="text" name="payee_name" class="form-control @error('payee_name') is-invalid @enderror"
                                       value="{{ old('payee_name') }}" required>
                                @error('payee_name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                            <div class="col-md-4">
                                <label class="form-label fw-semibold">Amount <span class="text-danger">*</span></label>
                                <input type="number" name="amount" class="form-control @error('amount') is-invalid @enderror"
                                       value="{{ old('amount') }}" step="0.01" min="0.01" required>
                                @error('amount')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                            <div class="col-md-4">
                                <label class="form-label fw-semibold">Cheque Date <span class="text-danger">*</span></label>
                                <input type="date" name="cheque_date" class="form-control @error('cheque_date') is-invalid @enderror"
                                       value="{{ old('cheque_date', now()->toDateString()) }}" required>
                                @error('cheque_date')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                            <div class="col-md-4">
                                <label class="form-label fw-semibold">Due / Clearing Date</label>
                                <input type="date" name="due_date" class="form-control @error('due_date') is-invalid @enderror"
                                       value="{{ old('due_date') }}">
                                @error('due_date')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>

                            {{-- Supplier (visible when type = issue) --}}
                            <div class="col-md-6" id="supplierField">
                                <label class="form-label fw-semibold">Supplier (optional)</label>
                                <select name="supplier_id" class="form-select @error('supplier_id') is-invalid @enderror">
                                    <option value="">-- None --</option>
                                    @foreach($suppliers as $s)
                                        <option value="{{ $s->id }}" @selected(old('supplier_id') == $s->id)>{{ $s->name }}</option>
                                    @endforeach
                                </select>
                                @error('supplier_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>

                            {{-- Customer (visible when type = receive) --}}
                            <div class="col-md-6" id="customerField">
                                <label class="form-label fw-semibold">Customer (optional)</label>
                                <select name="customer_id" class="form-select @error('customer_id') is-invalid @enderror">
                                    <option value="">-- None --</option>
                                    @foreach($customers as $c)
                                        <option value="{{ $c->id }}" @selected(old('customer_id') == $c->id)>{{ $c->customer_name }}</option>
                                    @endforeach
                                </select>
                                @error('customer_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>

                            <div class="col-12">
                                <label class="form-label fw-semibold">Notes</label>
                                <textarea name="notes" class="form-control" rows="3">{{ old('notes') }}</textarea>
                            </div>
                        </div>

                        <div class="mt-4 d-flex gap-2">
                            <button type="submit" class="btn btn-primary">Save Cheque</button>
                            <a href="{{ route('cheques.index') }}" class="btn btn-outline-secondary">Cancel</a>
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
    function toggleParty() {
        const type = document.getElementById('chequeType').value;
        document.getElementById('supplierField').style.display = (type === 'issue' || type === '') ? '' : 'none';
        document.getElementById('customerField').style.display = (type === 'receive' || type === '') ? '' : 'none';
    }
    toggleParty();
</script>
@endpush
@endsection
