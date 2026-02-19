@extends('layout.mainlayout')

@section('content')
<!-- Page Wrapper -->
<div class="page-wrapper">
    <div class="content container-fluid">

        <!-- Page Header -->
        <div class="page-header">
            <div class="row">
                <div class="col-sm-12">
                    <h3 class="page-title">Edit Vendor</h3>
                    <ul class="breadcrumb">
                        <li class="breadcrumb-item"><a href="{{ route('vendors.index') }}">Vendors</a></li>
                        <li class="breadcrumb-item active">Edit Vendor</li>
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
                        
                        @if ($errors->any())
                            <div class="alert alert-danger">
                                <ul>
                                    @foreach ($errors->all() as $error)
                                        <li>{{ $error }}</li>
                                    @endforeach
                                </ul>
                            </div>
                        @endif

                        <form action="{{ route('vendors.update', $vendor->id) }}" method="POST">
                            @csrf
                            @method('PUT')

                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="name" class="col-form-label">Name <span class="text-danger">*</span></label>
                                        <input type="text" id="name" name="name" class="form-control" value="{{ old('name', $vendor->name) }}" required>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="email" class="col-form-label">Email</label>
                                        <input type="email" id="email" name="email" class="form-control" value="{{ old('email', $vendor->email) }}">
                                    </div>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="phone" class="col-form-label">Phone</label>
                                        <input type="text" id="phone" name="phone" class="form-control" value="{{ old('phone', $vendor->phone) }}">
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    {{-- Displaying the dynamically calculated balance from the controller --}}
                                    <div class="form-group">
                                        <label for="current_balance" class="col-form-label">Current Balance (Read Only)</label>
                                        <input type="text" id="current_balance" class="form-control" 
                                               value="${{ number_format($vendor->current_balance ?? 0, 2) }}" readonly disabled>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="form-group">
                                <label for="address" class="col-form-label">Address</label>
                                <textarea id="address" name="address" rows="3" class="form-control">{{ old('address', $vendor->address) }}</textarea>
                            </div>

                            <div class="text-end mt-4">
                                <a href="{{ route('vendors.index') }}" class="btn btn-light me-2">Cancel</a>
                                <button type="submit" class="btn btn-primary">Update Vendor Information</button>
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
