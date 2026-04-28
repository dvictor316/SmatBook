@extends('layout.app')

@section('title', 'Leave Requests')

@section('content')
<div class="content container-fluid">
    <div class="page-header">
        <div class="row align-items-center">
            <div class="col"><h3 class="page-title">Leave Requests</h3></div>
            <div class="col-auto">
                <a href="{{ route('hr.leave.create') }}" class="btn btn-primary btn-sm">New Request</a>
                <a href="{{ route('hr.leave.types') }}" class="btn btn-outline-secondary btn-sm ms-1">Leave Types</a>
            </div>
        </div>
    </div>
    <div class="card">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table mb-0">
                    <thead class="table-light"><tr><th>Employee</th><th>Type</th><th>From</th><th>To</th><th>Days</th><th>Status</th><th class="text-end">Actions</th></tr></thead>
                    <tbody>
                        @forelse($requests as $req)
                            <tr>
                                <td>{{ $req->employee->name ?? '—' }}</td>
                                <td>{{ $req->leaveType->name ?? '—' }}</td>
                                <td>{{ $req->start_date->format('d M Y') }}</td>
                                <td>{{ $req->end_date->format('d M Y') }}</td>
                                <td>{{ number_format((float) $req->days_requested, 1) }}</td>
                                <td>{{ ucfirst($req->status) }}</td>
                                <td class="text-end">
                                    @if($req->status === 'pending')
                                        <form action="{{ route('hr.leave.approve', $req) }}" method="POST" class="d-inline">@csrf<button class="btn btn-sm btn-outline-success me-1">Approve</button></form>
                                        <form action="{{ route('hr.leave.reject', $req) }}" method="POST" class="d-inline">
                                            @csrf
                                            <input type="hidden" name="rejection_reason" value="Rejected from leave request queue.">
                                            <button class="btn btn-sm btn-outline-danger">Reject</button>
                                        </form>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="7" class="text-center py-4 text-muted">No leave requests found.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection
