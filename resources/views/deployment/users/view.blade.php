@extends('layout.mainlayout')

@section('page-title', $user->name . ' - Profile')

@section('content')

<style>
    :root {
        --deploy-sidebar-w: 270px;
        --deploy-sidebar-collapsed: 70px;
    }

    /* 1. LAYOUT WRAPPER */
    #profile-wrapper {
        transition: margin-left 0.3s ease, width 0.3s ease;
        padding: 1.5rem;
        /* Critical: Clear fixed navbar */
        padding-top: 110px; 
        min-height: 100vh;
        background: #f8fafc;
        width: 100%;
    }

    /* DESKTOP: Default State (Sidebar Open) */
    @media (min-width: 992px) {
        #profile-wrapper { 
            margin-left: var(--deploy-sidebar-w); 
            width: calc(100% - var(--deploy-sidebar-w)); 
        }
    }

    /* DESKTOP: Toggled State (Sidebar Collapsed) */
    @media (min-width: 992px) {
        body.sidebar-icon-only #profile-wrapper { 
            margin-left: var(--deploy-sidebar-collapsed); 
            width: calc(100% - var(--deploy-sidebar-collapsed)); 
        }
        body.sidebar-collapsed #profile-wrapper,
        body.mini-sidebar #profile-wrapper {
            margin-left: var(--deploy-sidebar-collapsed);
            width: calc(100% - var(--deploy-sidebar-collapsed));
        }
    }

    /* MOBILE: Full Width */
    @media (max-width: 991.98px) {
        #profile-wrapper { 
            margin-left: 0; 
            width: 100%; 
            padding-top: 100px; 
        }
    }

    /* Card Styling */
    .dm-card {
        background: white;
        border-radius: 12px;
        box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05);
        border: 1px solid #f1f5f9;
    }
</style>

<div id="profile-wrapper">

    <div class="mb-4 d-flex justify-content-between align-items-center">
        <div>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-1 small text-muted">
                    <li class="breadcrumb-item"><a href="{{ route('deployment.dashboard') }}" class="text-decoration-none">Home</a></li>
                    <li class="breadcrumb-item"><a href="{{ route('deployment.users.index') }}" class="text-decoration-none">Users</a></li>
                    <li class="breadcrumb-item active">{{ $user->name }}</li>
                </ol>
            </nav>
            <h4 class="fw-bold text-dark mb-0">User Profile</h4>
        </div>
        <div>
            <a href="{{ route('deployment.users.index') }}" class="btn btn-white btn-sm shadow-sm border text-muted">
                <i class="fas fa-arrow-left me-1"></i> Back to List
            </a>
        </div>
    </div>

    <div class="container-fluid px-0">
        <div class="row g-4">

            <div class="col-xl-4 col-lg-5">
                <div class="dm-card h-100">
                    <div class="card-header bg-white border-0 pt-4 pb-0 text-center">
                        <div class="rounded-circle d-flex align-items-center justify-content-center mx-auto mb-3 fw-bold text-white shadow-sm" 
                             style="width:80px;height:80px;background: linear-gradient(135deg, #1e40af 0%, #3b82f6 100%);font-size:28px;">
                            {{ strtoupper(substr($user->name, 0, 1)) }}
                        </div>
                        <h5 class="fw-bold mb-1" style="color:#0f172a; letter-spacing: -0.5px;">{{ $user->name }}</h5>
                        <p class="text-muted" style="font-size:13px;">{{ $user->email }}</p>

                        <div class="d-flex justify-content-center gap-2 mb-3">
                            <span class="badge" style="background:#eef2ff;color:#1e40af;font-weight:700;font-size:11px;padding:6px 14px;border-radius:20px;">
                                <i class="fas fa-shield-alt me-1"></i>{{ ucfirst($user->role) }}
                            </span>
                            @if($user->status === 'active') 
                                <span class="badge" style="background:#f0fdf4;color:#16a34a;font-weight:700;font-size:11px;padding:6px 14px;border-radius:20px;">
                                    <i class="fas fa-check-circle me-1"></i>Active
                                </span>
                            @else 
                                <span class="badge" style="background:#fef2f2;color:#dc2626;font-weight:700;font-size:11px;padding:6px 14px;border-radius:20px;">
                                    <i class="fas fa-times-circle me-1"></i>Inactive
                                </span>
                            @endif
                        </div>
                    </div>

                    <div class="card-body px-4">
                        <div class="info-list mt-2">
                            <div class="d-flex justify-content-between py-3" style="border-bottom: 1px solid #f1f5f9;">
                                <span class="text-muted" style="font-size: 13px;">Company</span>
                                <span class="fw-semibold text-dark" style="font-size: 13px;">{{ $user->company?->name ?? '—' }}</span>
                            </div>
                            <div class="d-flex justify-content-between py-3" style="border-bottom: 1px solid #f1f5f9;">
                                <span class="text-muted" style="font-size: 13px;">Joined Date</span>
                                <span class="fw-semibold text-dark" style="font-size: 13px;">{{ $user->created_at->format('d M, Y') }}</span>
                            </div>
                            <div class="d-flex justify-content-between py-3">
                                <span class="text-muted" style="font-size: 13px;">Last Login</span>
                                <span class="fw-semibold text-dark" style="font-size: 13px;">{{ $user->last_login_at ? $user->last_login_at->diffForHumans() : 'Never' }}</span>
                            </div>
                        </div>
                    </div>

                    <div class="card-footer bg-light border-0 d-flex justify-content-center gap-2 py-3 rounded-bottom">
                        <a href="{{ route('deployment.users.edit', $user->id) }}" class="btn btn-white shadow-sm btn-sm px-3" style="border-radius:8px; font-weight: 600; color: #475569; border: 1px solid #e2e8f0;">
                            <i class="fas fa-edit me-1 text-primary"></i> Edit Profile
                        </a>
                        @if($user->status === 'active')
                            <form action="{{ route('deployment.users.deactivate', $user->id) }}" method="POST" class="d-inline">
                                @csrf
                                <button type="submit" class="btn btn-white shadow-sm btn-sm px-3" style="border-radius:8px; font-weight: 600; color: #dc2626; border: 1px solid #fee2e2;" onclick="return confirm('Suspend this user access?')">
                                    <i class="fas fa-user-slash me-1"></i> Deactivate
                                </button>
                            </form>
                        @endif
                    </div>
                </div>
            </div>

            <div class="col-xl-8 col-lg-7">
                <div class="dm-card h-100" style="min-height: 400px;">
                    <div class="p-4">
                        <h6 class="fw-bold mb-4" style="color: #1e293b;"><i class="fas fa-history me-2 text-primary"></i> Recent Activity</h6>

                        <div class="text-center py-5">
                            <div class="mb-3">
                                <div class="bg-light rounded-circle d-inline-flex align-items-center justify-content-center" style="width: 64px; height: 64px;">
                                    <i class="fas fa-clipboard-list fa-2x text-muted opacity-25"></i>
                                </div>
                            </div>
                            <h6 class="text-dark fw-bold">No Audit Logs Found</h6>
                            <p class="text-muted small" style="font-size: 13px;">There is no recent system activity recorded for this user.</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    // Handle Sidebar Toggle Resize
    document.addEventListener("DOMContentLoaded", function() {
        const toggleBtn = document.querySelector('.navbar-toggler');
        if(toggleBtn) {
            toggleBtn.addEventListener('click', () => {
                setTimeout(() => window.dispatchEvent(new Event('resize')), 300);
            });
        }
    });
</script>
@endsection
