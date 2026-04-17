@extends('layout.mainlayout')

@section('page-title', auth()->user()->hasRole('super_admin') ? 'Deployment Managers' : 'Client Users')

@php
    /**
     * SUBDOMAIN & ROLE LOGIC
     */
    $sub = $routeParams['subdomain'] ?? request()->route('subdomain') ?? 'admin';
    $role = strtolower((string) (auth()->user()->role ?? ''));
    $isAdmin = in_array($role, ['super_admin', 'superadmin', 'administrator', 'admin'], true)
        || strtolower((string) (auth()->user()->email ?? '')) === 'donvictorlive@gmail.com';
@endphp

<style>
    .page-content-wrapper {
        margin-left: 250px; 
        transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        padding: 25px;
        min-height: calc(100vh - 70px);
        background: #f8fafc;
    }

    body.sidebar-collapsed .page-content-wrapper { margin-left: 80px; }

    @media (max-width: 991px) {
        .page-content-wrapper { margin-left: 0 !important; padding: 15px; }
    }

    .dm-card { border-radius: 12px; background: #fff; border: none; box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.075); }

    .breadcrumb-container { display: flex; align-items: center; gap: 8px; font-size: 13px; color: #64748b; margin-bottom: 20px; }
    .breadcrumb-container a { color: #1e40af; text-decoration: none; font-weight: 500; }

    .action-btn-group { display: flex; justify-content: flex-end; gap: 4px; }

    .btn-action {
        width: 32px; height: 32px; display: flex; align-items: center; justify-content: center;
        border-radius: 6px; border: 1px solid #e2e8f0; background: white; color: #64748b; transition: all 0.2s;
    }
    .btn-action:hover { background: #f8fafc; border-color: #cbd5e1; color: #1e293b; }
</style>

@section('content')
<div class="page-content-wrapper">

    <div class="breadcrumb-container d-print-none">
        <a href="{{ $isAdmin ? route('super_admin.dashboard', ['subdomain' => $sub]) : route('deployment.dashboard') }}">Home</a>
        <i class="fas fa-chevron-right" style="font-size: 10px;"></i>
        <span>{{ $isAdmin ? 'Deployment Managers' : 'Users' }}</span>
    </div>

    <div class="d-flex align-items-center justify-content-between mb-4 d-print-none">
        <div>
            <h4 class="fw-bold mb-1" style="color:#0f172a; letter-spacing: -0.5px;">
                {{ $isAdmin ? 'Deployment Managers' : 'Users' }}
            </h4>
            <div class="badge bg-white border text-muted fw-medium px-3 py-2 shadow-sm">
                <i class="fas fa-users me-2 text-primary"></i> 
                @if(method_exists($users, 'total'))
                    Showing {{ $users->firstItem() }} - {{ $users->lastItem() }} of {{ $users->total() }} Records
                @else
                    {{ $users->count() }} Records Found
                @endif
            </div>
        </div>

        @if($isAdmin)
            <a href="{{ route('super_admin.users.create', ['subdomain' => $sub]) }}" class="btn shadow-sm text-white" style="background:#1e40af; border-radius:8px; padding: 10px 20px; font-weight:600;">
                <i class="fas fa-plus-circle me-2"></i>Add Manager
            </a>
        @else
            <a href="{{ route('deployment.users.create') }}" class="btn shadow-sm text-white" style="background:#1e40af; border-radius:8px; padding: 10px 20px; font-weight:600;">
                <i class="fas fa-plus-circle me-2"></i>Add User
            </a>
        @endif
    </div>

    <div class="dm-card card shadow-sm border-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0" style="vertical-align: middle;">
                <thead class="bg-light">
                    <tr style="font-size:11px; text-transform:uppercase; letter-spacing:0.8px; color:#64748b; border-bottom: 2px solid #f1f5f9;">
                        @if($isAdmin)
                            <th class="ps-4 py-3">Business Details</th>
                            <th>Manager Name</th>
                            <th>Phone Number</th>
                            <th>ID Verification</th>
                        @else
                            <th class="ps-4 py-3">User Details</th>
                            <th>Email Address</th>
                            <th>Company</th>
                            <th>Role</th>
                        @endif
                        <th>Status</th>
                        <th class="text-end pe-4">Actions</th>
                    </tr>
                </thead>
                <tbody style="font-size: 14px;">
                    @forelse($users as $u)
                    <tr>
                        @if($isAdmin)

                            <td class="ps-4">
                                <div class="fw-bold text-dark">{{ $u->business_name ?? 'N/A' }}</div>
                                <div class="text-muted small">Ref: #{{ $u->id }}</div>
                            </td>
                            <td>{{ $u->manager_name ?? ($u->user->name ?? 'N/A') }}</td>
                            <td>{{ $u->phone ?? 'N/A' }}</td>
                            <td><span class="small text-muted">{{ $u->id_type ?? 'N/A' }}: {{ $u->id_number ?? 'N/A' }}</span></td>
                        @else

                            <td class="ps-4">
                                <div class="fw-bold text-dark">{{ $u->name }}</div>
                                <div class="text-muted small">Joined {{ $u->created_at->format('M Y') }}</div>
                            </td>
                            <td class="text-muted">{{ $u->email }}</td>
                            <td>{{ $u->company?->name ?? '—' }}</td>
                            <td><span class="badge bg-light text-primary border px-2 py-1" style="font-size: 10px;">{{ ucfirst($u->role) }}</span></td>
                        @endif

                        <td>
                            @if($u->status === 'active') 
                                <span class="badge rounded-pill" style="background:#f0fdf4; color:#16a34a; font-size:10px; border: 1px solid #dcfce7;">Active</span>
                            @elseif($u->status === 'suspended')
                                <span class="badge rounded-pill" style="background:#fff7ed; color:#ea580c; font-size:10px; border: 1px solid #ffedd5;">Suspended</span>
                            @else
                                <span class="badge rounded-pill" style="background:#fef2f2; color:#dc2626; font-size:10px; border: 1px solid #fee2e2;">{{ ucfirst($u->status ?? 'pending') }}</span>
                            @endif
                        </td>

                        <td class="text-end pe-4">
                            <div class="action-btn-group">

                                @if($isAdmin)

                                    <a href="{{ route('super_admin.users.edit', [$u->id, 'subdomain' => $sub]) }}" class="btn-action" title="View/Edit">
                                        <i class="fas fa-eye"></i>
                                    </a>
                                @else
                                    <a href="{{ route('deployment.users.view', $u->id) }}" class="btn-action" title="View">
                                        <i class="fas fa-eye"></i>
                                    </a>
                                @endif

                                @if($isAdmin)
                                    <a href="{{ route('super_admin.users.edit', [$u->id, 'subdomain' => $sub]) }}" class="btn-action" title="Edit">
                                        <i class="fas fa-pencil-alt"></i>
                                    </a>
                                @else
                                    <a href="{{ route('deployment.users.edit', $u->id) }}" class="btn-action" title="Edit">
                                        <i class="fas fa-pencil-alt"></i>
                                    </a>
                                @endif

                                @if($u->status !== 'active')
                                    @if($isAdmin)
                                        <form action="{{ route('super_admin.users.activate', [$u->id, 'subdomain' => $sub]) }}" method="POST" class="d-inline">
                                            @csrf
                                            <button type="submit" class="btn-action" title="Activate" onclick="return confirm('Activate this account?')">
                                                <i class="fas fa-check text-success"></i>
                                            </button>
                                        </form>
                                    @else
                                        <form action="{{ route('deployment.users.activate', $u->id) }}" method="POST" class="d-inline">
                                            @csrf
                                            <button type="submit" class="btn-action" title="Activate" onclick="return confirm('Activate this account?')">
                                                <i class="fas fa-check text-success"></i>
                                            </button>
                                        </form>
                                    @endif
                                @endif

                                @if($u->status === 'active')
                                    @if($isAdmin)

                                    @else
                                        <form action="{{ route('deployment.users.suspend', $u->id) }}" method="POST" class="d-inline">
                                            @csrf
                                            <button type="submit" class="btn-action" title="Suspend" onclick="return confirm('Suspend this user?')">
                                                <i class="fas fa-pause text-warning"></i>
                                            </button>
                                        </form>
                                    @endif
                                @endif

                                @if($isAdmin)
                                    <form action="{{ route('super_admin.users.deactivate', [$u->id, 'subdomain' => $sub]) }}" method="POST" class="d-inline">
                                        @csrf
                                        <button type="submit" class="btn-action" title="Deactivate" onclick="return confirm('Deactivate this record?')">
                                            <i class="fas fa-times-circle text-danger"></i>
                                        </button>
                                    </form>
                                @else
                                    <form action="{{ route('deployment.users.deactivate', $u->id) }}" method="POST" class="d-inline">
                                        @csrf
                                        <button type="submit" class="btn-action" title="Deactivate" onclick="return confirm('Deactivate this record?')">
                                            <i class="fas fa-times-circle text-danger"></i>
                                        </button>
                                    </form>
                                @endif
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr><td colspan="6" class="text-center py-5 text-muted">No records found.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if(method_exists($users, 'links'))
            <div class="card-footer bg-white border-top d-flex justify-content-between align-items-center">
                <div class="text-muted small">
                    Showing <b>{{ $users->firstItem() }}</b> to <b>{{ $users->lastItem() }}</b> of <b>{{ $users->total() }}</b> entries
                </div>
                <div class="d-print-none">
                    {{ $users->appends(request()->query())->links('pagination::bootstrap-4') }}
                </div>
            </div>
        @endif
    </div>
</div>
@endsection
