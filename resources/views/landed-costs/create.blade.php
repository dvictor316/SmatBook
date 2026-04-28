@extends('layout.app')

@section('title', 'Record Landed Cost')

@section('content')
<div class="content container-fluid">
    <div class="page-header"><h3 class="page-title">Record Landed Cost</h3></div>
    <div class="card">
        <div class="card-body">
            <form method="POST" action="{{ route('landed-costs.store') }}" class="row g-3">
                @csrf
                <div class="col-md-4">
                    <label class="form-label">Cost Type</label>
                    <input type="text" name="cost_type" class="form-control" required>
                </div>
                <div class="col-md-4">
                    <label class="form-label">Description</label>
                    <input type="text" name="description" class="form-control">
                </div>
                <div class="col-md-2">
                    <label class="form-label">Amount</label>
                    <input type="number" step="0.01" min="0.01" name="amount" class="form-control" required>
                </div>
                <div class="col-md-2">
                    <label class="form-label">Currency</label>
                    <input type="text" name="currency" class="form-control" value="NGN">
                </div>
                <div class="col-md-4">
                    <label class="form-label">Allocation Method</label>
                    <select name="allocation_method" class="form-select" required>
                        @foreach(['by_value', 'by_weight', 'by_quantity', 'equal'] as $method)
                            <option value="{{ $method }}">{{ ucfirst(str_replace('_', ' ', $method)) }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-12">
                    <label class="form-label">Notes</label>
                    <textarea name="notes" class="form-control" rows="3"></textarea>
                </div>
                <div class="col-12">
                    <button class="btn btn-primary">Save Landed Cost</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
