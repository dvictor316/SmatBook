// session domain logic
// domain => env('SESSION_DOMAIN', null)

@extends('layout.mainlayout')

@section('content')
<div class="page-wrapper">
    <div class="content container-fluid">

        <div class="page-header">
            <div class="row align-items-center">
                <div class="col-sm-12">
                    <h3 class="page-title">Edit Company: {{ $company->name }}</h3>
                    <ul class="breadcrumb">
                        <li class="breadcrumb-item"><a href="{{ url('/superadmin/companies') }}">Companies</a></li>
                        <li class="breadcrumb-item active">Edit Details</li>
                    </ul>
                </div>
            </div>
        </div>
        <div class="row">
            <div class="col-md-12">
                <div class="card shadow-sm">
                    <div class="card-header bg-white">
                        <h4 class="card-title mb-0">Update Company Profiles & Infrastructure</h4>
                    </div>
                    <div class="card-body">
                        {{-- Explicitly using the update URL to match your controller fix --}}
                        <form action="{{ url('/superadmin/companies/' . $company->id) }}" method="POST" enctype="multipart/form-data">
                            @csrf
                            @method('PUT') 

                            {{-- Display Validation Errors --}}
                            @if ($errors->any())
                                <div class="alert alert-danger alert-dismissible fade show">
                                    <ul class="mb-0">
                                        @foreach ($errors->all() as $error)
                                            <li>{{ $error }}</li>
                                        @endforeach
                                    </ul>
                                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                                </div>
                            @endif
                            
                            <div class="row">
                                <div class="col-md-6">
                                    <h5 class="text-primary mb-3"><i class="fas fa-id-card me-2"></i>Identity & Domain</h5>
                                    <div class="form-group mb-3">
                                        <label>Company Name <span class="text-danger">*</span></label>
                                        <input type="text" name="name" class="form-control" value="{{ old('name', $company->name) }}" required>
                                    </div>
                                    <div class="form-group mb-3">
                                        <label>Subdomain (Tenant Prefix)</label>
                                        <div class="input-group">
                                            <input type="text" name="subdomain" class="form-control" value="{{ old('subdomain', $company->subdomain) }}">
                                            @php
                                                $domainSuffix = ltrim(config('session.domain', env('SESSION_DOMAIN', 'smartprobook.com')), '.');
                                            @endphp
                                            <span class="input-group-text bg-light">.{{ $domainSuffix }}</span>
                                        </div>
                                    </div>
                                    <div class="form-group mb-3">
                                        <label>Custom Domain</label>
                                        <input type="text" name="domain" class="form-control" value="{{ old('domain', $company->domain) }}" placeholder="www.example.com">
                                    </div>
                                    <div class="form-group mb-3">
                                        <label>Subscription Plan</label>
                                        <select name="plan" class="form-control select">
                                            <option value="basic" {{ old('plan', $company->plan) == 'basic' ? 'selected' : '' }}>Basic</option>
                                            <option value="premium" {{ old('plan', $company->plan) == 'premium' ? 'selected' : '' }}>Premium</option>
                                            <option value="enterprise" {{ old('plan', $company->plan) == 'enterprise' ? 'selected' : '' }}>Enterprise</option>
                                        </select>
                                    </div>
                                </div>

                                <div class="col-md-6">
                                    <h5 class="text-primary mb-3"><i class="fas fa-map-marked-alt me-2"></i>Contact & Localisation</h5>
                                    <div class="form-group mb-3">
                                        <label>Email Address</label>
                                        <input type="email" name="email" class="form-control" value="{{ old('email', $company->email) }}">
                                    </div>
                                    <div class="form-group mb-3">
                                        <label>Phone Number</label>
                                        <input type="text" name="phone" class="form-control" value="{{ old('phone', $company->phone) }}">
                                    </div>
                                    <div class="row">
                                        <div class="col-md-6">
                                            <div class="form-group mb-3">
                                                <label>Currency Symbol</label>
                                                <input type="text" name="currency_symbol" class="form-control" value="{{ old('currency_symbol', $company->currency_symbol) }}">
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="form-group mb-3">
                                                <label>Currency Code</label>
                                                <input type="text" name="currency_code" class="form-control" value="{{ old('currency_code', $company->currency_code) }}">
                                            </div>
                                        </div>
                                    </div>
                                    <div class="form-group mb-3">
                                        <label>Country</label>
                                        <input type="text" name="country" class="form-control" value="{{ old('country', $company->country) }}">
                                    </div>
                                </div>

                                <div class="col-md-12">
                                    <hr>
                                    <h5 class="text-primary mb-3"><i class="fas fa-cog me-2"></i>System Status & Ownership</h5>
                                    <div class="row">
                                        <div class="col-md-4">
                                            <div class="form-group mb-3">
                                                <label>User ID (Account Owner)</label>
                                                <input type="number" name="user_id" class="form-control" value="{{ old('user_id', $company->user_id) }}">
                                            </div>
                                        </div>
                                        <div class="col-md-4">
                                            <div class="form-group mb-3">
                                                <label>Owner ID (Reference)</label>
                                                <input type="number" name="owner_id" class="form-control" value="{{ old('owner_id', $company->owner_id) }}">
                                            </div>
                                        </div>
                                        <div class="col-md-4">
                                            <div class="form-group mb-3">
                                                <label>Company Status</label>
                                                <select name="status" class="form-control select">
                                                    <option value="active" {{ old('status', $company->status) == 'active' ? 'selected' : '' }}>Active</option>
                                                    <option value="inactive" {{ old('status', $company->status) == 'inactive' ? 'selected' : '' }}>Inactive</option>
                                                    <option value="suspended" {{ old('status', $company->status) == 'suspended' ? 'selected' : '' }}>Suspended</option>
                                                </select>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="form-group mb-3">
                                        <label>Physical Address</label>
                                        <textarea name="address" class="form-control" rows="3">{{ old('address', $company->address) }}</textarea>
                                    </div>
                                </div>
                            </div>

                            <div class="text-end mt-4">
                                <a href="{{ url('/superadmin/companies') }}" class="btn btn-secondary me-2">Cancel</a>
                                <button type="submit" class="btn btn-primary px-5">Update Company</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>

    </div>
</div>

<script>
    // Universal print script integrated into the edit view context
    window.onbeforeprint = function() {
        console.log("Preparing print for company record: {{ $company->id }} under domain {{ env('SESSION_DOMAIN', 'null') }}");
    };
</script>
@endsection
