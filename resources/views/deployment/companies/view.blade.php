@extends('layout.mainlayout')

@section('page-title', 'Company Details')

@section('content')
<div class="sb-shell page-content-wrapper">
<div class="container-fluid py-4">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h4 class="mb-0">Company Details</h4>
        <a href="{{ route('deployment.companies.index') }}" class="btn btn-light border">Back</a>
    </div>

    <div class="card border-0 shadow-sm">
        <div class="card-body">
            <div class="row g-3">
                <div class="col-md-6"><strong>Name:</strong> {{ $company->name ?? $company->company_name ?? 'N/A' }}</div>
                <div class="col-md-6"><strong>Email:</strong> {{ $company->email ?? 'N/A' }}</div>
                <div class="col-md-6"><strong>Phone:</strong> {{ $company->phone ?? 'N/A' }}</div>
                <div class="col-md-6"><strong>Status:</strong> {{ ucfirst($company->status ?? 'unknown') }}</div>
                <div class="col-md-6"><strong>Plan:</strong> {{ $company->plan ?? 'N/A' }}</div>
                <div class="col-md-6"><strong>Subdomain:</strong> {{ $company->domain_prefix ?? $company->subdomain ?? 'N/A' }}</div>
            </div>

            <div class="mt-4 d-flex gap-2">
                <a href="{{ route('deployment.companies.edit', $company->id) }}" class="btn btn-primary">Edit</a>
                <form action="{{ route('deployment.companies.suspend', $company->id) }}" method="POST">
                    @csrf
                    <button class="btn btn-warning" type="submit">Suspend</button>
                </form>
                <form action="{{ route('deployment.companies.activate', $company->id) }}" method="POST">
                    @csrf
                    <button class="btn btn-success" type="submit">Activate</button>
                </form>
            </div>
        </div>
    </div>
</div>
</div>
@endsection
