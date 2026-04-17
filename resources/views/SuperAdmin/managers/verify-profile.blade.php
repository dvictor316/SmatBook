@extends('layout.master')

@section('title', 'Partner Verification')

@section('content')
<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card shadow-lg border-0">
                <div class="card-header bg-primary text-white p-4">
                    <h4 class="mb-1"><i class="fas fa-user-check me-2"></i> Complete Partner Profile</h4>
                    <p class="mb-0 opacity-75">Please provide your business details to activate your Deployment Manager dashboard.</p>
                </div>
                <div class="card-body p-4">
                    <form action="{{ route('manager.submit.verification') }}" method="POST">
                        @csrf

                        <div class="row">
                            <div class="col-md-12 mb-4">
                                <h5 class="text-muted border-bottom pb-2">Business Information</h5>
                            </div>

                            <div class="col-md-12 mb-3">
                                <label class="form-label">Registered Business Name</label>
                                <input type="text" name="business_name" class="form-control @error('business_name') is-invalid @enderror" value="{{ old('business_name') }}" placeholder="Enter legal business name" required>
                                @error('business_name') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>

                            <div class="col-md-6 mb-3">
                                <label class="form-label">Phone Number</label>
                                <input type="text" name="phone" class="form-control @error('phone') is-invalid @enderror" value="{{ old('phone') }}" placeholder="+234..." required>
                                @error('phone') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>

                            <div class="col-md-6 mb-3">
                                <label class="form-label">Contact Email</label>
                                <input type="email" class="form-control" value="{{ $user->email }}" disabled>
                                <small class="text-muted">Linked to your login account.</small>
                            </div>

                            <div class="col-md-12 mb-3">
                                <label class="form-label">Business Address</label>
                                <textarea name="address" class="form-control @error('address') is-invalid @enderror" rows="2" placeholder="Full office or residential address" required>{{ old('address') }}</textarea>
                                @error('address') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>

                            <div class="col-md-12 mt-4 mb-4">
                                <h5 class="text-muted border-bottom pb-2">Identity Verification</h5>
                            </div>

                            <div class="col-md-6 mb-3">
                                <label class="form-label">ID Type</label>
                                <select name="id_type" class="form-select @error('id_type') is-invalid @enderror" required>
                                    <option value="">Select ID Type</option>
                                    <option value="NIN">National ID (NIN)</option>
                                    <option value="BVN">BVN Verification</option>
                                    <option value="Passport">International Passport</option>
                                    <option value="CAC">CAC Registration Number</option>
                                </select>
                                @error('id_type') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>

                            <div class="col-md-6 mb-3">
                                <label class="form-label">ID Number / Registration No.</label>
                                <input type="text" name="id_number" class="form-control @error('id_number') is-invalid @enderror" value="{{ old('id_number') }}" placeholder="Enter number" required>
                                @error('id_number') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>
                        </div>

                        <div class="d-grid mt-4">
                            <button type="submit" class="btn btn-primary btn-lg">
                                <i class="fas fa-paper-plane me-2"></i> Submit for Activation
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <div class="text-center mt-4">
                <p class="text-muted small">By submitting, you agree to our Partner Terms of Service and Deployment Guidelines.</p>
            </div>
        </div>
    </div>
</div>
@endsection