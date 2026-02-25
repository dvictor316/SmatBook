@extends('layout.mainlayout')

@section('content')
<div class="page-wrapper">
    <div class="content">
        @if(session('success'))
            <div class="alert alert-success">{{ session('success') }}</div>
        @endif
        @if(session('warning'))
            <div class="alert alert-warning">{{ session('warning') }}</div>
        @endif
        @if(session('error'))
            <div class="alert alert-danger">{{ session('error') }}</div>
        @endif

        <div class="d-flex justify-content-between align-items-start flex-wrap gap-3 mb-4">
            <div>
                <h4 class="mb-1">Direct Transfer Users</h4>
                <p class="text-muted mb-0">Approve, reject, or suspend users who paid via direct bank transfer (without deployment manager).</p>
            </div>
        </div>

        <div class="card mb-4">
            <div class="card-body">
                <form action="{{ route('super_admin.transfer_users.index') }}" method="GET" class="row g-2">
                    <div class="col-md-6">
                        <input type="text" name="search" class="form-control" value="{{ request('search') }}" placeholder="Search by name, email, or transfer reference">
                    </div>
                    <div class="col-md-3">
                        <select name="status" class="form-select">
                            <option value="">All statuses</option>
                            <option value="pending" {{ request('status') === 'pending' ? 'selected' : '' }}>Pending</option>
                            <option value="approved" {{ request('status') === 'approved' ? 'selected' : '' }}>Approved</option>
                            <option value="rejected" {{ request('status') === 'rejected' ? 'selected' : '' }}>Rejected</option>
                            <option value="suspended" {{ request('status') === 'suspended' ? 'selected' : '' }}>Suspended</option>
                        </select>
                    </div>
                    <div class="col-md-3 d-flex gap-2">
                        <button type="submit" class="btn btn-primary w-100">Filter</button>
                        <a href="{{ route('super_admin.transfer_users.index') }}" class="btn btn-light border">Reset</a>
                    </div>
                </form>
            </div>
        </div>

        <div class="card">
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-bordered align-middle mb-0">
                        <thead>
                            <tr>
                                <th>User</th>
                                <th>Email</th>
                                <th>Company</th>
                                <th>Plan</th>
                                <th>Amount</th>
                                <th>Reference</th>
                                <th>Submitted</th>
                                <th>Status</th>
                                <th style="min-width: 250px;">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($transferUsers as $row)
                                @php
                                    $paymentStatus = strtolower((string) ($row->payment_status ?? ''));
                                    $subStatus = strtolower((string) ($row->status ?? ''));

                                    if ($subStatus === 'suspended') {
                                        $state = 'suspended';
                                        $badgeClass = 'bg-dark';
                                    } elseif ($paymentStatus === 'failed') {
                                        $state = 'rejected';
                                        $badgeClass = 'bg-danger';
                                    } elseif ($subStatus === 'active' || $paymentStatus === 'paid') {
                                        $state = 'approved';
                                        $badgeClass = 'bg-success';
                                    } elseif ($paymentStatus === 'pending_verification') {
                                        $state = 'pending';
                                        $badgeClass = 'bg-warning text-dark';
                                    } else {
                                        $state = ucfirst($row->status ?? 'pending');
                                        $badgeClass = 'bg-secondary';
                                    }
                                @endphp
                                <tr>
                                    <td>{{ $row->customer_name }}</td>
                                    <td>{{ $row->customer_email ?: 'N/A' }}</td>
                                    <td>{{ $row->company_name ?: 'N/A' }}</td>
                                    <td>{{ $row->plan_name ?: $row->plan ?: 'N/A' }}</td>
                                    <td>₦{{ number_format((float) ($row->amount ?? 0), 2) }}</td>
                                    <td>{{ $row->transfer_reference ?: $row->transaction_reference ?: 'N/A' }}</td>
                                    <td>{{ $row->transfer_submitted_at ? \Carbon\Carbon::parse($row->transfer_submitted_at)->format('d M Y, h:i A') : 'N/A' }}</td>
                                    <td><span class="badge {{ $badgeClass }}">{{ strtoupper($state) }}</span></td>
                                    <td>
                                        <div class="d-flex gap-2 flex-wrap">
                                            <form action="{{ route('super_admin.transfer_users.approve', $row->id) }}" method="POST">
                                                @csrf
                                                <button type="submit" class="btn btn-sm btn-success" {{ $state === 'approved' ? 'disabled' : '' }}>Approve</button>
                                            </form>

                                            <form action="{{ route('super_admin.transfer_users.reject', $row->id) }}" method="POST">
                                                @csrf
                                                <button type="submit" class="btn btn-sm btn-danger" {{ $state === 'rejected' ? 'disabled' : '' }}>Reject</button>
                                            </form>

                                            <form action="{{ route('super_admin.transfer_users.suspend', $row->id) }}" method="POST">
                                                @csrf
                                                <button type="submit" class="btn btn-sm btn-dark" {{ $state === 'suspended' ? 'disabled' : '' }}>Suspend</button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="9" class="text-center text-muted py-4">No direct-transfer users found.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
            @if($transferUsers->hasPages())
                <div class="card-footer">
                    {{ $transferUsers->links() }}
                </div>
            @endif
        </div>
    </div>
</div>
@endsection
