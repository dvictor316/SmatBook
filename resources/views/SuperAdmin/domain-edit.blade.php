@extends('layout.mainlayout')

@section('content')
<div class="page-wrapper">
    <div class="content container-fluid">
        @component('components.page-header')
            @slot('title') Edit Domain Request @endslot
        @endcomponent

        <div class="row mb-4">
            <div class="col-md-12">
                <div class="card shadow-sm border-0">
                    <div class="card-header bg-white">
                        <h4 class="card-title mb-0">Modify Request: {{ $domain->domain_name }}</h4>
                    </div>
                    <div class="card-body">
                        <form action="{{ route('super_admin.domain.update', $domain->id) }}" method="POST">
                            @csrf
                            @method('PUT')
                            <div class="row g-3">
                                <div class="col-md-4">
                                    <label class="form-label fw-bold">Customer Name</label>
                                    <input type="text" name="customer_name" class="form-control" value="{{ old('customer_name', $domain->customer_name) }}" required>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label fw-bold">Email</label>
                                    <input type="email" name="email" class="form-control" value="{{ old('email', $domain->email) }}" required>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label fw-bold">Domain Name</label>
                                    <input type="text" name="domain_name" class="form-control" value="{{ old('domain_name', $domain->domain_name) }}" required>
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label fw-bold">No. of Employees</label>
                                    <input type="number" name="employees" class="form-control" value="{{ old('employees', $domain->employees) }}">
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label fw-bold">Package Name</label>
                                    <input type="text" name="package_name" class="form-control" value="{{ old('package_name', $domain->package_name) }}" required>
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label fw-bold">Package Type</label>
                                    <select name="package_type" class="form-select">
                                        <option value="Monthly" {{ $domain->package_type == 'Monthly' ? 'selected' : '' }}>Monthly</option>
                                        <option value="Yearly" {{ $domain->package_type == 'Yearly' ? 'selected' : '' }}>Yearly</option>
                                    </select>
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label fw-bold">Status</label>
                                    <select name="status" class="form-select">
                                        <option value="Pending" {{ $domain->status == 'Pending' ? 'selected' : '' }}>Pending</option>
                                        <option value="Active" {{ $domain->status == 'Active' ? 'selected' : '' }}>Active</option>
                                        <option value="Rejected" {{ $domain->status == 'Rejected' ? 'selected' : '' }}>Rejected</option>
                                    </select>
                                </div>
                            </div>
                            <div class="mt-4 text-end">
                                <a href="{{ route('super_admin.domains.index') }}" class="btn btn-light me-2">Cancel</a>
                                <button type="submit" class="btn btn-primary px-5">
                                    <i class="fas fa-save me-1"></i> Update Domain Request
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection