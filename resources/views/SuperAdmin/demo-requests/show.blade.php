@extends('layout.mainlayout')

@section('content')

<div class="page-wrapper">
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
</div>
@endsection
