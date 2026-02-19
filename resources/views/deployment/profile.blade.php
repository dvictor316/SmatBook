
@extends('layout.mainlayout')

@section('content')
<style>
    /* Prevent Infringement: Match the 250px sidebar */
    #profile-content {
        width: 100%;
        min-height: 100vh;
        background-color: #f4f7f6;
        transition: all 0.3s ease;
    }

    .profile-header {
        background: linear-gradient(to right, #4e73df, #224abe);
        height: 150px;
        border-radius: 0 0 15px 15px;
    }

    .profile-img-container {
        margin-top: -75px;
    }

    .profile-img {
        width: 150px;
        height: 150px;
        border: 5px solid #fff;
        object-fit: cover;
    }

    @media print {
        #profile-content { margin-left: 0 !important; }
        .no-print { display: none !important; }
    }
</style>

<div id="layout-wrapper">
    <div id="profile-content">
        <div class="profile-header no-print"></div>
        
        <div class="container-fluid">
            <div class="row justify-content-center">
                <div class="col-lg-8">
                    <div class="card border-0 shadow-sm profile-img-container mb-4">
                        <div class="card-body text-center">
                            <img src="https://ui-avatars.com/api/?name={{ urlencode(auth()->user()->name) }}&background=random&size=150" 
                                 class="rounded-circle profile-img mb-3" alt="Profile">
                            
                            <h3 class="fw-bold mb-1">{{ auth()->user()->name }}</h3>
                            <p class="text-muted small">
                                <i class="fas fa-shield-alt me-1"></i> 
                                Super Admin Manager @ {{ env('SESSION_DOMAIN', 'System') }}
                            </p>
                            
                            <div class="d-flex justify-content-center gap-2 no-print">
                                <button onclick="window.print()" class="btn btn-outline-secondary btn-sm">
                                    <i class="fas fa-print me-1"></i> Print Profile
                                </button>
                                <a href="{{ route('settings.index') }}" class="btn btn-primary btn-sm">
                                    <i class="fas fa-edit me-1"></i> Edit Settings
                                </a>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6">
                            <div class="card border-0 shadow-sm mb-4">
                                <div class="card-header bg-white py-3">
                                    <h6 class="mb-0 fw-bold">Account Details</h6>
                                </div>
                                <div class="card-body">
                                    <div class="mb-3">
                                        <label class="small text-muted d-block">Email Address</label>
                                        <span class="fw-medium">{{ auth()->user()->email }}</span>
                                    </div>
                                    <div class="mb-3">
                                        <label class="small text-muted d-block">Role Permissions</label>
                                        <span class="badge bg-soft-primary text-primary">Full Access</span>
                                    </div>
                                    <div>
                                        <label class="small text-muted d-block">Member Since</label>
                                        <span class="fw-medium">{{ auth()->user()->created_at->format('M d, Y') }}</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="col-md-6">
                            <div class="card border-0 shadow-sm mb-4">
                                <div class="card-header bg-white py-3">
                                    <h6 class="mb-0 fw-bold">Domain Environment</h6>
                                </div>
                                <div class="card-body text-center py-4">
                                    <i class="fas fa-globe fa-3x text-light mb-3"></i>
                                    <p class="mb-0">Current Session Domain:</p>
                                    <code class="d-block h5 text-primary mt-2">{{ env('SESSION_DOMAIN', 'localhost') }}</code>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    // Custom print handling for your pages
    window.onbeforeprint = function() {
        console.log("Printing profile for user on {{ env('SESSION_DOMAIN') }}");
    };
</script>
@endsection
