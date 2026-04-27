@extends('layout.app')

@section('title', 'Leave Management')

@section('content')
<div class="content container-fluid">
    <div class="page-header">
        <div class="row align-items-center">
            <div class="col">
                <h3 class="page-title">Leave Management</h3>
                <ul class="breadcrumb">
                    <li class="breadcrumb-item"><a href="{{ route('home') }}">Dashboard</a></li>
                    <li class="breadcrumb-item active">Leave Requests</li>
                </ul>
            </div>
            <div class="col-auto">
                <a href="{{ route('hr.leave.create') }}" class="btn btn-primary btn-sm">
                    <i class="fe fe-plus me-1"></i> New Request
                </a>
                <a href="{{ route('hr.leave.types') }}" class="btn btn-outline-secondary btn-sm ms-1">
                    <i class="fe fe-settings me-1"></i> Leave Types
                </a>
            </div>
        </div>
    </div>

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show">
            {{ session('success') }}<button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    <div class="card">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Employee</th><th>Leave Type</th><th>From</th><th>To</th><th>Days</th><th>Status</th><th>Applied</th><th class="text-end">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($requests as $req)
                            <tr>
                                <td>{{ $req->employee->name ?? '—' }}</td>
                                <td>{{ $req->leaveType->name ?? '—' }}</td>
                                <td>{{ $req->start_date->format('d M Y') }}</td>
                                <td>{{ $req->end_date->format('d M Y') }}</td>
                                <td>{{ $req->days_requested }}</td>
                                <td>
                                    <span class="badge bg-{{ match($req->status) {
                                        'pending' => 'warning', 'approved' => 'success', 'rejected' => 'danger', 'cancelled' => 'secondary', default => 'secondary'
                                    } }}">{{ ucfirst($req->status) }}</span>
                                </td>
                                <td>{{ $req->created_at->format('d M Y') }}</td>
                                <td class="text-end">
                                    <a href="{{ route('hr.leave.show', $req) }}" class="btn btn-sm btn-outline-primary me-1">View</a>
                                    @if($req->status === 'pending')
                                        <form action="{{ route('hr.leave.approve', $req) }}" method="POST" class="d-inline">
                                            @csrf
                                            <button class="btn btn-sm btn-outline-success me-1">Approve</button>
                                        </form>
                                        <form action="{{ route('hr.leave.reject', $req) }}" method="POST" class="d-inline">
                                            @csrf
                                            <button class="btn btn-sm btn-outline-danger">Reject</button>
                                        </form>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="8" class="text-center py-4 text-muted">No leave requests found.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        @if($requests->hasPages())
            <div class="card-footer">{{ $requests->links() }}</div>
        @endif
    </div>
</div>
@endsection
