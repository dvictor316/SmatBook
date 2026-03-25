// session domain logic
// domain => env('SESSION_DOMAIN', null)

@extends('layout.mainlayout')

@section('content')
<div class="page-wrapper">
    <div class="content container-fluid">

        <div class="page-header">
            <div class="row">
                <div class="col-sm-12">
                    <h3 class="page-title">Create New Company</h3>
                    <ul class="breadcrumb">
                        <li class="breadcrumb-item"><a href="{{ url('/superadmin/companies') }}">Companies</a></li>
                        <li class="breadcrumb-item active">New Company</li>
                    </ul>
                </div>
            </div>
        </div>
        <div class="row">
            <div class="col-md-12">
                <div class="card shadow-sm">
                    <div class="card-header bg-white">
                        <h4 class="card-title mb-0">Full Company Registration</h4>
                    </div>
                    <div class="card-body">
                        <form action="{{ url('/superadmin/companies') }}" method="POST">
                            @csrf

                            {{-- Display Validation Errors --}}
                            @if ($errors->any())
                                <div class="alert alert-danger alert-dismissible fade show" role="alert">
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
                                    <h5 class="text-primary mb-3">Identity & Domain</h5>
                                    <div class="form-group mb-3">
                                        <label>Company Name <span class="text-danger">*</span></label>
                                        <input type="text" name="name" class="form-control" value="{{ old('name') }}" placeholder="e.g. SmartProbook Ltd" required>
                                    </div>
                                    <div class="form-group mb-3">
                                        <label>Subdomain (Tenant Prefix)</label>
                                        <div class="input-group">
                                            <input type="text" name="subdomain" class="form-control" value="{{ old('subdomain') }}" placeholder="companyname">
                                            <span class="input-group-text">.{{ config('session.domain', env('SESSION_DOMAIN', 'smartprobook.com')) }}</span>
                                        </div>
                                    </div>
                                    <div class="form-group mb-3">
                                        <label>Custom Domain (Optional)</label>
                                        <input type="text" name="domain" class="form-control" value="{{ old('domain') }}" placeholder="e.g. www.company.com">
                                    </div>
                                    <div class="form-group mb-3">
                                        <label>Subscription Plan</label>
                                        <select name="plan" class="form-control">
                                            <option value="basic" {{ old('plan') == 'basic' ? 'selected' : '' }}>Basic</option>
                                            <option value="premium" {{ old('plan') == 'premium' ? 'selected' : '' }}>Premium</option>
                                            <option value="enterprise" {{ old('plan') == 'enterprise' ? 'selected' : '' }}>Enterprise</option>
                                        </select>
                                    </div>
                                </div>

                                <div class="col-md-6">
                                    <h5 class="text-primary mb-3">Contact & Localisation</h5>
                                    <div class="form-group mb-3">
                                        <label>Email Address</label>
                                        <input type="email" name="email" class="form-control" value="{{ old('email') }}" placeholder="contact@company.com">
                                    </div>
                                    <div class="form-group mb-3">
                                        <label>Phone Number</label>
                                        <input type="text" name="phone" class="form-control" value="{{ old('phone') }}" placeholder="+234...">
                                    </div>
                                    <div class="row">
                                        <div class="col-md-6">
                                            <div class="form-group mb-3">
                                                <label>Currency Symbol</label>
                                                <input type="text" name="currency_symbol" class="form-control" value="{{ old('currency_symbol', '₦') }}">
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="form-group mb-3">
                                                <label>Currency Code</label>
                                                <input type="text" name="currency_code" class="form-control" value="{{ old('currency_code', 'NGN') }}">
                                            </div>
                                        </div>
                                    </div>
                                    <div class="form-group mb-3">
                                        <label>Country</label>
                                        <input type="text" name="country" class="form-control" value="{{ old('country', 'Nigeria') }}">
                                    </div>
                                </div>

                                <div class="col-md-12 mt-3">
                                    <h5 class="text-primary mb-3">Full Address & Status</h5>
                                    <div class="form-group mb-3">
                                        <label>Physical Address</label>
                                        <textarea name="address" class="form-control" rows="3">{{ old('address') }}</textarea>
                                    </div>
                                    <div class="row">
                                        <div class="col-md-6">
                                            <div class="form-group mb-3">
                                                <label>Assign to User (User ID)</label>
                                                <input type="number" name="user_id" class="form-control" value="{{ old('user_id') }}" placeholder="Owner User ID">
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="form-group mb-3">
                                                <label>System Status</label>
                                                <select name="status" class="form-control">
                                                    <option value="active" {{ old('status') == 'active' ? 'selected' : '' }}>Active</option>
                                                    <option value="inactive" {{ old('status') == 'inactive' ? 'selected' : '' }}>Inactive</option>
                                                    <option value="suspended" {{ old('status') == 'suspended' ? 'selected' : '' }}>Suspended</option>
                                                </select>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <hr>
                            <div class="text-end pb-3">
                                <button type="reset" class="btn btn-secondary me-2">Reset Fields</button>
                                <button type="submit" class="btn btn-primary btn-lg px-5">Save Company</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>

    </div>
</div>

<script>
    // Universal printing logic for the creation form context
    window.onbeforeprint = function() {
        console.log("Printing New Company Registration Form for: {{ env('SESSION_DOMAIN', 'null') }}");
    };
</script>
@endsection
