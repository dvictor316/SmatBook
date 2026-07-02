@extends('layout.mainlayout')

@section('content')
<style>
    .demo-reset-hero {
        border: 0;
        border-radius: 18px;
        background: linear-gradient(135deg, #fff7ed 0%, #ffedd5 48%, #fee2e2 100%);
        box-shadow: 0 18px 40px rgba(194, 65, 12, 0.14);
        overflow: hidden;
    }

    .demo-reset-hero__body {
        display: flex;
        flex-wrap: wrap;
        align-items: center;
        justify-content: space-between;
        gap: 14px;
        padding: 16px 18px;
    }

    .demo-reset-hero__copy {
        flex: 1 1 420px;
        min-width: 260px;
    }

    .demo-reset-hero__eyebrow {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        padding: 6px 12px;
        border-radius: 999px;
        background: rgba(255, 255, 255, 0.7);
        color: #9a3412;
        font-size: 10px;
        font-weight: 800;
        letter-spacing: 0.06em;
        text-transform: uppercase;
        margin-bottom: 8px;
    }

    .demo-reset-hero__title {
        margin: 0 0 6px;
        color: #7c2d12;
        font-size: 20px;
        font-weight: 800;
        line-height: 1.15;
    }

    .demo-reset-hero__text {
        margin: 0;
        color: #7c2d12;
        font-size: 12px;
        line-height: 1.45;
        max-width: 760px;
    }

    .demo-reset-hero__actions {
        display: flex;
        flex: 0 1 280px;
        justify-content: flex-end;
    }

    .demo-reset-hero__form {
        width: 100%;
        max-width: 280px;
    }

    .demo-reset-hero__button {
        width: 100%;
        min-height: 48px;
        border: 0;
        border-radius: 14px;
        background: linear-gradient(135deg, #dc2626 0%, #ea580c 100%);
        color: #fff;
        font-size: 13px;
        font-weight: 800;
        box-shadow: 0 16px 28px rgba(220, 38, 38, 0.24);
    }

    .demo-reset-hero__button:hover,
    .demo-reset-hero__button:focus {
        color: #fff;
        transform: translateY(-1px);
        box-shadow: 0 18px 32px rgba(220, 38, 38, 0.28);
    }

    .demo-reset-hero__hint {
        margin-top: 8px;
        color: #9a3412;
        font-size: 10.5px;
        text-align: center;
        font-weight: 600;
    }

    @media (max-width: 767.98px) {
        .demo-reset-hero__body {
            padding: 14px;
        }

        .demo-reset-hero__title {
            font-size: 17px;
        }

        .demo-reset-hero__actions {
            flex-basis: 100%;
            justify-content: stretch;
        }

        .demo-reset-hero__form {
            max-width: none;
        }
    }
</style>

<div class="content container-fluid">

        <div class="page-header d-print-none">
            <div class="row align-items-center">
                <div class="col">
                    <h3 class="page-title">Demo Request — {{ $demoRequest->full_name }}</h3>
                    <ul class="breadcrumb">
                        <li class="breadcrumb-item"><a href="{{ route('super_admin.dashboard') }}">Dashboard</a></li>
                        <li class="breadcrumb-item"><a href="{{ route('super_admin.demo_requests.index') }}">Demo Requests</a></li>
                        <li class="breadcrumb-item active">{{ $demoRequest->full_name }}</li>
                    </ul>
                </div>
                <div class="col-auto float-right ml-auto">
                    <a href="{{ route('super_admin.demo_requests.index') }}" class="btn btn-white text-muted">
                        <i class="fas fa-arrow-left"></i> Back
                    </a>
                </div>
            </div>
        </div>

        {{-- Flash messages --}}
        @if (session('success'))
            <div class="alert alert-success alert-dismissible fade show"><span>{{ session('success') }}</span><button type="button" class="close" data-dismiss="alert">&times;</button></div>
        @endif
        @if (session('error'))
            <div class="alert alert-danger alert-dismissible fade show"><span>{{ session('error') }}</span><button type="button" class="close" data-dismiss="alert">&times;</button></div>
        @endif

        @if($demoRequest->demo_company_id && in_array($demoRequest->status, ['approved', 'expired']))
            <div class="card demo-reset-hero mb-4">
                <div class="demo-reset-hero__body">
                    <div class="demo-reset-hero__copy">
                        <div class="demo-reset-hero__eyebrow">
                            <i class="fas fa-triangle-exclamation"></i>
                            Demo Reset
                        </div>
                        <h4 class="demo-reset-hero__title">Reset This Demo Workspace</h4>
                        <p class="demo-reset-hero__text">
                            Use this when the demo already contains old records or you want a fresh environment.
                            The reset rebuilds the demo workspace using the latest clean seed rules.
                        </p>
                    </div>
                    <div class="demo-reset-hero__actions">
                        <form method="POST"
                              action="{{ route('super_admin.demo_requests.reset', $demoRequest->id) }}"
                              class="demo-reset-hero__form"
                              onsubmit="return confirm('Reset this demo workspace now and rebuild it with fresh clean data?');">
                            @csrf
                            <button type="submit" class="btn demo-reset-hero__button">
                                <i class="fas fa-rotate-right mr-2"></i> Reset Demo Workspace Now
                            </button>
                            <div class="demo-reset-hero__hint">
                                Recommended after seed-rule changes or when reports should start empty.
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        @endif

        <div class="row">

            {{-- Request details --}}
            <div class="col-lg-7">
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title mb-0">
                            Request Details
                            <span class="badge {{ $demoRequest->statusBadgeClass() }} ml-2">{{ ucfirst($demoRequest->status) }}</span>
                        </h5>
                    </div>
                    <div class="card-body">
                        <table class="table table-bordered table-sm">
                            <tbody>
                                <tr><th style="width:35%">Full Name</th><td>{{ $demoRequest->full_name }}</td></tr>
                                <tr><th>Company Name</th><td>{{ $demoRequest->company_name }}</td></tr>
                                <tr><th>Business Type</th><td>{{ $demoRequest->business_type ?? '—' }}</td></tr>
                                <tr><th>Email</th><td>{{ $demoRequest->email }}</td></tr>
                                <tr><th>Phone</th><td>{{ $demoRequest->phone }}</td></tr>
                                <tr><th>Country</th><td>{{ $demoRequest->country }}</td></tr>
                                <tr><th>Number of Users</th><td>{{ $demoRequest->number_of_users ?? '—' }}</td></tr>
                                <tr><th>Purpose</th><td style="white-space:pre-wrap">{{ $demoRequest->purpose }}</td></tr>
                                <tr><th>Submitted</th><td>{{ $demoRequest->created_at->format('d M Y H:i') }}</td></tr>
                                <tr><th>Expires</th><td>{{ $demoRequest->expires_at ? $demoRequest->expires_at->format('d M Y H:i') : '—' }}</td></tr>
                                <tr><th>Admin Note</th><td>{{ $demoRequest->admin_note ?? '—' }}</td></tr>
                                @if($demoRequest->approved_at)
                                    <tr><th>Approved At</th><td>{{ \Carbon\Carbon::parse($demoRequest->approved_at)->format('d M Y H:i') }}</td></tr>
                                    <tr><th>Approved By</th><td>{{ $demoRequest->approver?->name ?? '—' }}</td></tr>
                                @endif
                            </tbody>
                        </table>
                    </div>
                </div>

                {{-- Provisioned credentials (only if approved) --}}
                @if($demoRequest->status === 'approved' && $demoRequest->demo_company_id)
                    <div class="card border-success">
                        <div class="card-header bg-success text-white">
                            <h5 class="card-title mb-0"><i class="fas fa-server mr-2"></i>Provisioned Demo Environment</h5>
                        </div>
                        <div class="card-body">
                            <table class="table table-bordered table-sm">
                                <tbody>
                                    <tr><th style="width:35%">Demo Company ID</th><td>{{ $demoRequest->demo_company_id }}</td></tr>
                                    <tr><th>Demo User ID</th><td>{{ $demoRequest->demo_user_id }}</td></tr>
                                    @if($demoRequest->demoCompany)
                                        <tr><th>Company Name</th><td>{{ $demoRequest->demoCompany->name }}</td></tr>
                                        <tr><th>Company Status</th><td>{{ $demoRequest->demoCompany->status }}</td></tr>
                                    @endif
                                    @if($demoRequest->demoUser)
                                        <tr><th>Demo Login Email</th><td>{{ $demoRequest->demoUser->email }}</td></tr>
                                    @endif
                                    @if($demoRequest->email !== ($demoRequest->demoUser->email ?? null))
                                        <tr><th>Notification Email</th><td>{{ $demoRequest->email }}</td></tr>
                                    @endif
                                </tbody>
                            </table>
                        </div>
                    </div>
                @endif
            </div>

            {{-- Actions panel --}}
            <div class="col-lg-5">

                @if($demoRequest->status === 'pending')

                    {{-- Approve --}}
                    <div class="card border-success">
                        <div class="card-header">
                            <h5 class="card-title text-success mb-0"><i class="fas fa-check mr-2"></i>Approve Request</h5>
                        </div>
                        <div class="card-body">
                            <p class="text-muted small">Approving will provision a demo company + user and send the credentials to <strong>{{ $demoRequest->email }}</strong>.</p>
                            <form method="POST" action="{{ route('super_admin.demo_requests.approve', $demoRequest->id) }}"
                                  onsubmit="return confirm('Approve this demo request and provision an environment for {{ $demoRequest->full_name }}?');">
                                @csrf
                                <div class="form-group">
                                    <label>Admin Note (optional)</label>
                                    <textarea name="admin_note" class="form-control" rows="3" placeholder="Welcome message or internal note..."></textarea>
                                </div>
                                <button type="submit" class="btn btn-success btn-block">
                                    <i class="fas fa-check mr-1"></i> Approve & Send Credentials
                                </button>
                            </form>
                        </div>
                    </div>

                    {{-- Reject --}}
                    <div class="card border-danger">
                        <div class="card-header">
                            <h5 class="card-title text-danger mb-0"><i class="fas fa-times mr-2"></i>Reject Request</h5>
                        </div>
                        <div class="card-body">
                            <p class="text-muted small">The applicant will receive a rejection notification by email.</p>
                            <form method="POST" action="{{ route('super_admin.demo_requests.reject', $demoRequest->id) }}"
                                  onsubmit="return confirm('Reject this demo request from {{ $demoRequest->full_name }}?');">
                                @csrf
                                <div class="form-group">
                                    <label>Reason (optional — sent to applicant)</label>
                                    <textarea name="admin_note" class="form-control" rows="3" placeholder="E.g. Your business type is not currently supported..."></textarea>
                                </div>
                                <button type="submit" class="btn btn-danger btn-block">
                                    <i class="fas fa-times mr-1"></i> Reject Request
                                </button>
                            </form>
                        </div>
                    </div>

                @elseif(in_array($demoRequest->status, ['approved', 'expired']))

                    <div class="card">
                        <div class="card-body text-center py-5">
                            @if($demoRequest->status === 'approved')
                                <i class="fas fa-check-circle fa-3x text-success mb-3"></i>
                                <h5 class="text-success">Already Approved</h5>
                                <p class="text-muted">Credentials were sent to <strong>{{ $demoRequest->email }}</strong> on {{ $demoRequest->approved_at ? \Carbon\Carbon::parse($demoRequest->approved_at)->format('d M Y') : '—' }}.</p>
                            @else
                                <i class="fas fa-calendar-times fa-3x text-secondary mb-3"></i>
                                <h5 class="text-secondary">Demo Expired</h5>
                                <p class="text-muted">This demo account has expired. The environment has been deactivated.</p>
                            @endif
                        </div>
                    </div>

                    @if($demoRequest->demo_company_id)
                        <div class="card border-primary">
                            <div class="card-header">
                                <h5 class="card-title mb-0">Workspace Controls</h5>
                            </div>
                            <div class="card-body">
                                <form method="POST" action="{{ route('super_admin.demo_requests.reset', $demoRequest->id) }}" onsubmit="return confirm('Reset this demo workspace and rebuild the sample data?');">
                                    @csrf
                                    <button type="submit" class="btn btn-primary btn-block mb-3">
                                        <i class="fas fa-rotate-right mr-1"></i> Reset Demo Data
                                    </button>
                                </form>

                                <form method="POST" action="{{ route('super_admin.demo_requests.extend', $demoRequest->id) }}" class="mb-3">
                                    @csrf
                                    <div class="form-group">
                                        <label>Extend Access (hours)</label>
                                        <input type="number" name="hours" min="1" max="168" value="{{ $demoConfig['lifetime_hours'] ?? 48 }}" class="form-control">
                                    </div>
                                    <button type="submit" class="btn btn-success btn-block">
                                        <i class="fas fa-clock mr-1"></i> Extend Demo Access
                                    </button>
                                </form>

                                @if($demoRequest->status === 'approved')
                                    <form method="POST" action="{{ route('super_admin.demo_requests.expire', $demoRequest->id) }}" onsubmit="return confirm('Expire this demo workspace immediately?');">
                                        @csrf
                                        <button type="submit" class="btn btn-outline-danger btn-block">
                                            <i class="fas fa-ban mr-1"></i> Expire Demo Now
                                        </button>
                                    </form>
                                @endif
                            </div>
                        </div>
                    @endif

                @elseif($demoRequest->status === 'rejected')

                    <div class="card">
                        <div class="card-body text-center py-5">
                            <i class="fas fa-times-circle fa-3x text-danger mb-3"></i>
                            <h5 class="text-danger">Request Rejected</h5>
                            @if($demoRequest->admin_note)
                                <p class="text-muted">Reason: {{ $demoRequest->admin_note }}</p>
                            @endif
                        </div>
                    </div>

                @endif

            </div>
        </div>

</div>
@endsection
