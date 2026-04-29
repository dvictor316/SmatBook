
@extends('layout.mainlayout', [
    'hideNavbar' => true,
    'hideSidebar' => true,
    'hideFooter' => true
])

@section('content')
<style>
    .manager-onboarding {
        min-height: 100vh;
        background:
            radial-gradient(circle at top left, rgba(95, 93, 255, 0.10), transparent 26%),
            radial-gradient(circle at bottom right, rgba(14, 165, 233, 0.08), transparent 22%),
            linear-gradient(180deg, #f7f9ff 0%, #eef4ff 100%);
    }
    .manager-card {
        border: 1px solid rgba(63, 94, 251, 0.10);
        border-radius: 28px;
        overflow: hidden;
        box-shadow: 0 24px 60px rgba(17, 24, 39, 0.10);
        background: #ffffff;
    }
    .manager-card__header {
        padding: 32px 36px;
        background: linear-gradient(135deg, #1d4ed8 0%, #2563eb 38%, #7c3aed 100%);
        color: #fff;
    }
    .manager-card__title {
        margin: 0;
        font-size: 2.1rem;
        font-weight: 800;
        line-height: 1.1;
        color: #fff;
    }
    .manager-card__subtitle {
        margin: 10px 0 0;
        max-width: 640px;
        color: rgba(255,255,255,.86);
        font-size: 1rem;
    }
    .manager-card__body {
        padding: 36px;
    }
    .manager-pill {
        display: inline-flex;
        align-items: center;
        gap: 10px;
        padding: 12px 18px;
        border-radius: 999px;
        border: 1px solid rgba(255,255,255,.28);
        background: rgba(255,255,255,.08);
        color: #fff;
        font-weight: 700;
        text-decoration: none;
    }
    .manager-alert {
        border: 0;
        border-radius: 22px;
        padding: 20px 22px;
        background: linear-gradient(135deg, rgba(37,99,235,.09), rgba(124,58,237,.09));
        color: #173a7a;
        box-shadow: inset 0 0 0 1px rgba(37,99,235,.10);
    }
    .manager-alert strong {
        color: #142a63;
    }
    .manager-card .form-label {
        font-weight: 800;
        color: #334155;
        margin-bottom: 10px;
    }
    .manager-card .form-control,
    .manager-card .form-select {
        min-height: 56px;
        border-radius: 16px;
        border-color: #dbe3f3;
        padding-inline: 18px;
        font-size: 1.05rem;
        box-shadow: none;
    }
    .manager-card textarea.form-control {
        min-height: 108px;
        padding-top: 16px;
    }
    .manager-card .form-control:focus,
    .manager-card .form-select:focus {
        border-color: #4f46e5;
        box-shadow: 0 0 0 4px rgba(79, 70, 229, 0.12);
    }
    .manager-submit {
        min-height: 60px;
        border: 0;
        border-radius: 18px;
        background: linear-gradient(135deg, #2563eb 0%, #4f46e5 45%, #7c3aed 100%);
        font-weight: 800;
        letter-spacing: 0.01em;
        box-shadow: 0 20px 35px rgba(79, 70, 229, 0.22);
    }
    .manager-submit:hover,
    .manager-submit:focus {
        background: linear-gradient(135deg, #1d4ed8 0%, #4338ca 45%, #6d28d9 100%);
    }
    .manager-helper {
        color: #64748b;
        font-size: 0.92rem;
    }
    @media (max-width: 767.98px) {
        .manager-card__header,
        .manager-card__body {
            padding: 24px 20px;
        }
        .manager-card__title {
            font-size: 1.7rem;
        }
    }
</style>

<div class="container py-5 manager-onboarding">
    <div class="row justify-content-center">
        <div class="col-xl-9 col-lg-10">
            <div class="manager-card">
                <div class="manager-card__header d-flex justify-content-between align-items-start flex-wrap gap-3">
                    <div>
                        <h4 class="manager-card__title">Complete Your Manager Profile</h4>
                        <p class="manager-card__subtitle">Submit your business identity details for internal review. Once approved by a super admin, your deployment workspace will be activated.</p>
                    </div>

                    <form action="{{ route('logout') }}" method="POST" class="m-0">
                        @csrf
                        <button type="submit" class="manager-pill">
                            <i class="fas fa-sign-out-alt"></i> Logout
                        </button>
                    </form>
                </div>

                <div class="manager-card__body">
                    <div class="manager-alert mb-4">
                        <i class="fas fa-info-circle me-2"></i>
                        <strong>Approval flow:</strong> please provide accurate business details below. We validate the ID details automatically, then hold the account for super admin approval before dashboard access is granted.
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
                                    @error('phone') <div class="invalid-feedback">{{ $message }}</div> @enderror
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
                                @error('id_type') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>

                            <div class="col-md-6 mb-3">
                                <label class="form-label fw-bold">ID Number</label>
                                <input type="text" name="id_number" 
                                       class="form-control @error('id_number') is-invalid @enderror" 
                                       placeholder="Enter registration/ID number"
                                       value="{{ old('id_number', $manager->id_number ?? '') }}" required>
                                @error('id_number') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>
                        </div>

                        <div class="mt-4 d-grid">
                            <button type="submit" class="btn btn-primary btn-lg shadow-sm manager-submit">
                                <i class="fas fa-paper-plane me-1"></i> Submit for Approval
                            </button>
                            <div class="manager-helper text-center mt-3">Your access stays pending until a super admin approves this profile.</div>
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
