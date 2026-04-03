@extends('layout.mainlayout')

@section('content')
<style>
    :root {
        --sidebar-width: 250px;
        --navbar-height: 65px;
        --primary: #6366f1;
        --success: #10b981;
        --warning: #f59e0b;
        --danger: #ef4444;
        --slate: #64748b;
    }

    .master-hub-wrapper {
        margin-left: var(--sidebar-width);
        padding: 1.5rem;
        background-color: #f8fafc;
        min-height: 100vh;
        transition: all 0.3s ease;
        position: relative;
        z-index: 1002;
    }

    body.mini-sidebar .master-hub-wrapper { margin-left: 80px; }
    @media (max-width: 991px) { .master-hub-wrapper { margin-left: 0 !important; } }

    .metric-card {
        background: #fff;
        border: 1px solid #e2e8f0;
        border-radius: 12px;
        padding: 1.25rem;
        transition: transform 0.2s;
        border-bottom: 3px solid transparent;
    }
    .metric-card:hover { transform: translateY(-3px); box-shadow: 0 10px 15px -3px rgba(0,0,0,0.05); }
    .m-primary { border-bottom-color: var(--primary); }
    .m-warning { border-bottom-color: var(--warning); }
    .m-success { border-bottom-color: var(--success); }
    .m-danger { border-bottom-color: var(--danger); }
    .m-slate { border-bottom-color: var(--slate); }

    .icon-circle {
        width: 40px; height: 40px; border-radius: 10px;
        display: flex; align-items: center; justify-content: center;
    }

    .hub-table-container {
        background: #fff;
        border-radius: 12px;
        border: 1px solid #e2e8f0;
        overflow: visible;
    }
    .hub-table-container .table-responsive {
        overflow: visible !important;
    }
    .hub-table-container .dropdown-menu {
        z-index: 2005;
    }
    .pill {
        padding: 5px 10px;
        border-radius: 999px;
        font-size: 10px;
        font-weight: 600;
        letter-spacing: 0.4px;
        display: inline-block;
    }
    .pill-active { background: #ecfdf5; color: #10b981; }
    .pill-suspended { background: #fef2f2; color: #ef4444; }
    .pill-pending { background: #fffbeb; color: #f59e0b; }
    .pill-unknown { background: #f1f5f9; color: #64748b; }
    .extra-small { font-size: 10px; }
    .sticky-left { position: sticky; left: 0; background: #fff; z-index: 2; }
    .sticky-right { position: sticky; right: 0; background: #fff; z-index: 2; }
    .sticky-right .dropdown { position: static; }
    .sticky-right .dropdown-menu {
        z-index: 2000;
    }
</style>

<div class="master-hub-wrapper">
    {{-- Header --}}
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h3 class="fw-bold text-dark mb-0">Registered Users</h3>
            <p class="text-muted small">Manage users across the platform | Domain: {{ env('SESSION_DOMAIN', 'System Default') }}</p>
        </div>
        <div class="d-flex gap-2">
            <button onclick="window.print();" class="btn btn-white border px-3 btn-sm fw-bold">
                <i class="fas fa-print me-2 text-primary"></i>Export Registry
            </button>
        </div>
    </div>

    {{-- Metrics Row --}}
    <div class="row g-3 mb-4">
        @php
            $stats = [
                ['l' => 'Total Users', 'v' => $metrics['total'] ?? 0, 'i' => 'fa-users', 'c' => 'm-primary', 'icon_c' => '#eef2ff', 'txt' => '#6366f1'],
                ['l' => 'Active', 'v' => $metrics['active'] ?? 0, 'i' => 'fa-check-circle', 'c' => 'm-success', 'icon_c' => '#f0fdf4', 'txt' => '#10b981'],
                ['l' => 'Suspended', 'v' => $metrics['suspended'] ?? 0, 'i' => 'fa-ban', 'c' => 'm-danger', 'icon_c' => '#fef2f2', 'txt' => '#ef4444'],
                ['l' => 'Admins', 'v' => $metrics['admins'] ?? 0, 'i' => 'fa-user-shield', 'c' => 'm-warning', 'icon_c' => '#fffbeb', 'txt' => '#f59e0b'],
                ['l' => 'Standard Users', 'v' => $metrics['users'] ?? 0, 'i' => 'fa-user', 'c' => 'm-slate', 'icon_c' => '#f8fafc', 'txt' => '#64748b'],
            ];
        @endphp
        @foreach($stats as $s)
        <div class="col-md col-sm-6">
            <div class="metric-card {{ $s['c'] }}">
                <div class="d-flex align-items-center">
                    <div class="icon-circle me-3" style="background: {{ $s['icon_c'] }}; color: {{ $s['txt'] }};">
                        <i class="fa {{ $s['i'] }}"></i>
                    </div>
                    <div>
                        <h4 class="fw-bold mb-0 lh-1">{{ number_format($s['v']) }}</h4>
                        <span class="text-muted fw-bold text-uppercase" style="font-size: 8px; letter-spacing: 0.5px;">{{ $s['l'] }}</span>
                    </div>
                </div>
            </div>
        </div>
        @endforeach
    </div>

    {{-- Filter Bar --}}
    <div class="card border-0 shadow-sm mb-4" style="border-radius: 12px;">
        <div class="card-body p-3">
            <form action="{{ url()->current() }}" method="GET" class="row g-2 align-items-center">
                <div class="col-lg-5 col-md-4">
                    <div class="input-group">
                        <span class="input-group-text bg-light border-0"><i class="fa fa-search text-muted"></i></span>
                        <input type="text" name="search" class="form-control bg-light border-0 small" placeholder="Search by name, email or company..." value="{{ request('search') }}">
                    </div>
                </div>
                <div class="col-lg-3 col-md-4">
                    <select name="status" class="form-select bg-light border-0 small">
                        <option value="">All Statuses</option>
                        <option value="active" {{ request('status') == 'active' ? 'selected' : '' }}>Active</option>
                        <option value="suspended" {{ request('status') == 'suspended' ? 'selected' : '' }}>Suspended</option>
                    </select>
                </div>
                <div class="col-lg-2 col-md-4">
                    <select name="role" class="form-select bg-light border-0 small">
                        <option value="">All Roles</option>
                        <option value="admin" {{ request('role') == 'admin' ? 'selected' : '' }}>Admin</option>
                        <option value="manager" {{ request('role') == 'manager' ? 'selected' : '' }}>Manager</option>
                        <option value="staff" {{ request('role') == 'staff' ? 'selected' : '' }}>Staff</option>
                        <option value="user" {{ request('role') == 'user' ? 'selected' : '' }}>User</option>
                    </select>
                </div>
                <div class="col-lg-2 col-md-4 d-flex gap-2">
                    <button type="submit" class="btn btn-primary px-4 fw-bold small">Filter</button>
                    @if(request()->hasAny(['search', 'status', 'role']))
                        <a href="{{ url()->current() }}" class="btn btn-light border fw-bold small">Reset</a>
                    @endif
                </div>
            </form>
        </div>
    </div>

    {{-- Data Table --}}
    <div class="hub-table-container custom-scrollbar">
        <table class="table table-hover align-middle mb-0">
            <thead>
                <tr>
                    <th class="ps-4 sticky-left">User</th>
                    <th>Company</th>
                    <th>Email</th>
                    <th>Role</th>
                    <th>Status</th>
                    <th class="text-center sticky-right">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($users as $user)
                @php
                    $status = strtolower((string) ($user->status ?? 'active'));
                    $pillClass = match($status) {
                        'active' => 'pill-active',
                        'suspended' => 'pill-suspended',
                        default => 'pill-unknown'
                    };
                @endphp
                <tr>
                    <td class="ps-4 sticky-left bg-white">
                        <div class="d-flex align-items-center">
                            <div class="avatar-sm bg-primary text-white rounded-circle d-flex align-items-center justify-content-center me-2 fw-bold" style="width:32px; height:32px; font-size:12px;">
                                {{ strtoupper(substr($user->name ?? $user->email ?? 'U', 0, 1)) }}
                            </div>
                            <div class="lh-1">
                                <span class="d-block fw-bold text-dark small">{{ $user->name ?? 'User' }}</span>
                                <span class="text-muted extra-small">#{{ $user->id }}</span>
                            </div>
                        </div>
                    </td>
                    <td class="small">
                        <div class="text-dark">{{ $user->company?->name ?? $user->company?->company_name ?? '—' }}</div>
                        <div class="text-muted extra-small">{{ $user->company?->domain_prefix ?? '—' }}</div>
                    </td>
                    <td class="small">
                        <div class="text-dark">{{ $user->email ?? '—' }}</div>
                        <div class="text-muted extra-small">{{ $user->phone ?? '' }}</div>
                    </td>
                    <td class="small text-capitalize">{{ $user->role ?? 'user' }}</td>
                    <td><span class="pill {{ $pillClass }}">{{ strtoupper($status) }}</span></td>
                    <td class="text-center sticky-right bg-white">
                        <div class="d-flex justify-content-center gap-1">
                            <div class="dropdown">
                                <button class="btn btn-xs btn-primary dropdown-toggle" data-bs-toggle="dropdown" data-bs-boundary="viewport">Manage</button>
                                <ul class="dropdown-menu dropdown-menu-end border-0 shadow-lg">
                                    <li><h6 class="dropdown-header extra-small text-uppercase">User Control</h6></li>
                                    @if($status !== 'active')
                                    <li>
                                        <form action="{{ route('super_admin.users.activate', $user->id) }}" method="POST">
                                            @csrf
                                            <button type="submit" class="dropdown-item text-success"><i class="fas fa-check-circle me-2"></i>Activate</button>
                                        </form>
                                    </li>
                                    @endif
                                    @if($status === 'active')
                                    <li>
                                        <form action="{{ route('super_admin.users.suspend', $user->id) }}" method="POST">
                                            @csrf
                                            <button type="submit" class="dropdown-item text-warning"><i class="fas fa-pause-circle me-2"></i>Suspend</button>
                                        </form>
                                    </li>
                                    @endif
                                    <li><hr class="dropdown-divider"></li>
                                    <li><a class="dropdown-item" href="{{ route('messages.thread', ['user' => $user->id]) }}"><i class="fas fa-comments text-primary me-2"></i>Chat User</a></li>
                                    <li>
                                        <button type="button" class="dropdown-item js-email-user" data-user-id="{{ $user->id }}">
                                            <i class="fas fa-envelope text-info me-2"></i>Email User
                                        </button>
                                    </li>
                                </ul>
                            </div>
                            <form action="{{ route('super_admin.users.delete', $user->id) }}" method="POST">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn btn-xs btn-outline-danger" onclick="return confirm('Permanent delete from {{ env('SESSION_DOMAIN') }}?')">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </form>
                        </div>
                    </td>
                </tr>
                @empty
                <tr><td colspan="6" class="text-center py-5 text-muted small">No results found for your query.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    {{-- Pagination --}}
    <div class="py-3 d-flex justify-content-between align-items-center">
        <span class="small text-muted">
            Showing {{ method_exists($users, 'firstItem') ? ($users->firstItem() ?? 0) : 0 }}
            to {{ method_exists($users, 'lastItem') ? ($users->lastItem() ?? 0) : count($users) }}
            of {{ method_exists($users, 'total') ? $users->total() : count($users) }} users
        </span>
        <div>
            @if(method_exists($users, 'links'))
                {{ $users->links('pagination::bootstrap-4') }}
            @endif
        </div>
    </div>
</div>

<script>
    document.querySelectorAll('.js-email-user').forEach((btn) => {
        btn.addEventListener('click', () => {
            const userId = btn.dataset.userId;
            const subject = prompt('Email subject:', 'SmartProbook Update');
            if (subject === null) return;
            const message = prompt('Message:', 'Hello, here is an update regarding your account.');
            if (message === null) return;

            fetch(`{{ url('/superadmin/users') }}/${userId}/email`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                },
                body: JSON.stringify({ subject, message })
            })
            .then(res => res.json())
            .then(data => alert(data.message || 'Email sent.'))
            .catch(() => alert('Email failed. Please check mail settings.'));
        });
    });
</script>
@endsection
