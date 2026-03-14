@extends('layout.mainlayout')

@section('content')
<!-- Page Wrapper -->
<div class="page-wrapper">
    <div class="content container-fluid">

        <!-- Page Header -->
        <div class="page-header">
            <div class="row">
                <div class="col-sm-12">
                    <h3 class="page-title">Create Vendor</h3>
                    <ul class="breadcrumb">
                        <li class="breadcrumb-item"><a href="{{ route('vendors.index') }}">Vendors</a></li>
                        <li class="breadcrumb-item active">New Vendor</li>
                    </ul>
                </div>
            </div>
        </div>
        <!-- /Page Header -->

        <div class="row">
            <div class="col-md-12">
                <div class="card">
                    <div class="card-header">
                        <h4 class="card-title">Vendor Information</h4>
                    </div>
                    <div class="card-body">
                        
                        {{-- Add validation error display if needed --}}
                        @if ($errors->any())
                            <div class="alert alert-danger">
                                <ul>
                                    @foreach ($errors->all() as $error)
                                        <li>{{ $error }}</li>
                                    @endforeach
                                </ul>
                            </div>
                        @endif

                        {{-- Action points to the vendors.store route we defined --}}
                        <form action="{{ route('vendors.store') }}" method="POST" enctype="multipart/form-data">
                            @csrf

                            <div class="row">
                                <div class="col-md-12">
                                    <div class="form-group">
                                        <label for="logo" class="col-form-label">Vendor Image</label>
                                        <input type="file" id="logo" name="logo" class="form-control" accept="image/*">
                                        <small class="text-muted">Upload a logo or profile image to personalize the vendor record.</small>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    {{-- Name Field --}}
                                    <div class="form-group">
                                        <label for="name" class="col-form-label">Name <span class="text-danger">*</span></label>
                                        <input type="text" id="name" name="name" class="form-control" value="{{ old('name') }}" required>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    {{-- Email Field --}}
                                    <div class="form-group">
                                        <label for="email" class="col-form-label">Email</label>
                                        <input type="email" id="email" name="email" class="form-control" value="{{ old('email') }}">
                                    </div>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-6">
                                    {{-- Phone Field --}}
                                    <div class="form-group">
                                        <label for="phone" class="col-form-label">Phone</label>
                                        <input type="text" id="phone" name="phone" class="form-control" value="{{ old('phone') }}">
                                    </div>
                                </div>
                                <div class="col-md-6">
                                     {{-- Balance Field --}}
                                     <div class="form-group">
                                        <label for="balance" class="col-form-label">Starting Balance</label>
                                        <input type="number" step="0.01" id="balance" name="balance" class="form-control" value="{{ old('balance', 0.00) }}">
                                    </div>
                                </div>
                            </div>
                            
                            {{-- Address Field --}}
                            <div class="form-group">
                                <label for="address" class="col-form-label">Address</label>
                                <textarea id="address" name="address" rows="3" class="form-control">{{ old('address') }}</textarea>
                            </div>

                            {{-- Submit Button and Cancel Button --}}
                            <div class="text-end mt-4">
                                {{-- Link points back to the vendor index page --}}
                                <a href="{{ route('vendors.index') }}" class="btn btn-light me-2">Cancel</a>
                                <button type="submit" class="btn btn-primary">Create Vendor</button>
                            </div>
                        </form>

                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<!-- /Page Wrapper -->
@endsection
