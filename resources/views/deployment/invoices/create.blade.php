@extends('layout.mainlayout')

@section('page-title', 'Create Invoice')

@section('content')
<div class="sb-shell page-content-wrapper">
<div class="container-fluid py-4">
    <h4 class="mb-3">Create Invoice</h4>
    <div class="card border-0 shadow-sm">
        <div class="card-body">
            <form method="POST" action="{{ route('deployment.invoices.store') }}">
                @csrf
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label">Company</label>
                        <select class="form-select" name="company_id" required>
                            <option value="">Select company</option>
                            @foreach($companies as $company)
                                <option value="{{ $company->id }}">{{ $company->name ?? $company->company_name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Amount</label>
                        <input type="number" class="form-control" name="amount" min="0" step="0.01" required>
                    </div>
                </div>
                <div class="mt-4">
                    <button class="btn btn-primary" type="submit">Create</button>
                </div>
            </form>
        </div>
    </div>
</div>
</div>
@endsection
