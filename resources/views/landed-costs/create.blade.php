@extends('layout.app')

@section('title', 'Record Landed Cost')

@section('content')
<div class="page-wrapper">
<div class="content container-fluid">
    <div class="page-header"><h3 class="page-title">Record Landed Cost</h3></div>
        <div class="card">
        <div class="card-body">
            <form method="POST" action="{{ route('landed-costs.store') }}" class="row g-3">
                @csrf
                @if ($errors->any())
                    <div class="col-12">
                        <div class="alert alert-danger">
                            <ul class="mb-0 ps-3">
                                @foreach ($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    </div>
                @endif
                @if(isset($grns) && $grns->isNotEmpty())
                    <div class="col-md-4">
                        <label class="form-label">Goods Received Note</label>
                        <select name="grn_id" class="form-select">
                            <option value="">No GRN linked</option>
                            @foreach($grns as $grn)
                                <option value="{{ $grn->id }}" @selected((string) old('grn_id') === (string) $grn->id)>
                                    {{ $grn->grn_number }} - {{ $grn->received_date }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                @endif
                <div class="col-md-4">
                    <label class="form-label">Cost Type</label>
                    <input type="text" name="cost_type" class="form-control" value="{{ old('cost_type') }}" required>
                </div>
                <div class="col-md-4">
                    <label class="form-label">Description</label>
                    <input type="text" name="description" class="form-control" value="{{ old('description') }}">
                </div>
                <div class="col-md-2">
                    <label class="form-label">Amount</label>
                    <input type="number" step="0.01" min="0.01" name="amount" class="form-control" value="{{ old('amount') }}" required>
                </div>
                <div class="col-md-2">
                    <label class="form-label">Currency</label>
                    <input type="text" name="currency" class="form-control" value="{{ old('currency', 'NGN') }}">
                </div>
                <div class="col-md-4">
                    <label class="form-label">Allocation Method</label>
                    <select name="allocation_method" class="form-select" required>
                        @foreach(['by_value', 'by_weight', 'by_quantity', 'equal'] as $method)
                            <option value="{{ $method }}" @selected(old('allocation_method', 'by_value') === $method)>{{ ucfirst(str_replace('_', ' ', $method)) }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-12">
                    <label class="form-label">Notes</label>
                    <textarea name="notes" class="form-control" rows="3">{{ old('notes') }}</textarea>
                </div>
                <div class="col-12">
                    <button class="btn btn-primary">Save Landed Cost</button>
                </div>
            </form>
        </div>
    </div>
</div>
</div>
@endsection
