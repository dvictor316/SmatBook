

@extends('layout.mainlayout', [
    'hideNavbar' => true,
    'hideSidebar' => true,
    'hideFooter' => true
])

@section('content')
<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card shadow border-0">
                <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
                    <h4 class="mb-0">Complete Your Manager Profile</h4>
                    
                    {{-- Emergency Logout to prevent users getting stuck in a redirect loop --}}
                    <form action="{{ route('logout') }}" method="POST" class="m-0">
                        @csrf
                        <button type="submit" class="btn btn-sm btn-outline-light">
                            <i class="fas fa-sign-out-alt"></i> Logout
                        </button>
                    </form>
                </div>

                <div class="card-body p-4">
                    <div class="alert alert-info border-0 shadow-sm mb-4">
                        <i class="fas fa-info-circle me-2"></i>
                        Please provide your business details below. Once submitted, our SuperAdmin team will review your credentials for account activation.
                    </div>

                    <form action="{{ route('manager.submit.verification') }}" method="POST">
                        @csrf
                        
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label fw-bold">Business/Trading Name</label>
                                <input type="text" name="business_name" 
                                       class="form-control @error('business_name') is-invalid @enderror" 
                                       placeholder="e.g. Smatbook Logistics"
                                       value="{{ old('business_name', $manager->business_name ?? '') }}" required>
                                @error('business_name') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>

                            <div class="col-md-6 mb-3">
                                <label class="form-label fw-bold">Phone Number</label>
                                <input type="text" name="phone" 
                                       class="form-control @error('phone') is-invalid @enderror" 
                                       placeholder="+234..."
                                       value="{{ old('phone', $manager->phone ?? '') }}" required>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label fw-bold">Business Address</label>
                            <textarea name="address" class="form-control @error('address') is-invalid @enderror" 
                                      rows="2" placeholder="Full office or business location" 
                                      required>{{ old('address', $manager->address ?? '') }}</textarea>
                        </div>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label fw-bold">ID Type</label>
                                <select name="id_type" class="form-select @error('id_type') is-invalid @enderror" required>
                                    <option value="">Select ID Type...</option>
                                    <option value="CAC" {{ old('id_type') == 'CAC' ? 'selected' : '' }}>CAC Certificate</option>
                                    <option value="BVN" {{ old('id_type') == 'BVN' ? 'selected' : '' }}>BVN</option>
                                    <option value="NIN" {{ old('id_type') == 'NIN' ? 'selected' : '' }}>NIN</option>
                                    <option value="Passport" {{ old('id_type') == 'Passport' ? 'selected' : '' }}>International Passport</option>
                                </select>
                            </div>

                            <div class="col-md-6 mb-3">
                                <label class="form-label fw-bold">ID Number</label>
                                <input type="text" name="id_number" 
                                       class="form-control @error('id_number') is-invalid @enderror" 
                                       placeholder="Enter registration/ID number"
                                       value="{{ old('id_number', $manager->id_number ?? '') }}" required>
                            </div>
                        </div>

                        <div class="mt-4 d-grid">
                            <button type="submit" class="btn btn-primary btn-lg shadow-sm">
                                <i class="fas fa-check-circle me-1"></i> Submit for Verification
                            </button>
                            <button type="button" onclick="printPage()" class="btn btn-link btn-sm mt-2 text-muted">
                                <i class="fas fa-print"></i> Print Form Copy
                            </button>
                        </div>
                    </form>
                </div>
            </div>
            
            <p class="text-center mt-4 text-muted small">
                &copy; {{ date('Y') }} {{ env('APP_NAME', 'Smatbook') }}. All rights reserved.
            </p>
        </div>
    </div>
</div>

{{-- Standard Print Script Integration --}}
<script>
    function printPage() {
        window.print();
    }

    // Prevent session timeout while manager fills verification form.
    (function keepSessionAlive() {
        const ping = () => {
            fetch("{{ route('session.ping') }}", {
                method: 'GET',
                credentials: 'same-origin',
                cache: 'no-store',
                headers: { 'X-Requested-With': 'XMLHttpRequest' },
            }).catch(() => {});
        };
        setTimeout(ping, 2000);
        setInterval(ping, 120000);
    })();
</script>
@endsection
