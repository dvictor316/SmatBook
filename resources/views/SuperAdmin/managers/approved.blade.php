@extends('layout.mainlayout')

@section('content')
@php
    $currentManagerRoute = request()->route()?->getName();
    $pageTitle = match ($currentManagerRoute) {
        'super_admin.managers.pending' => 'Pending Deployment Managers',
        'super_admin.managers.suspended' => 'Suspended Deployment Managers',
        'super_admin.managers.approved' => 'Approved Deployment Managers',
        default => 'Deployment Managers List',
    };

    $pageSubtitle = match ($currentManagerRoute) {
        'super_admin.managers.pending' => 'Review partners awaiting approval and activation.',
        'super_admin.managers.suspended' => 'Monitor suspended partners and restore access when necessary.',
        'super_admin.managers.approved' => 'Manage approved deployment partners across the platform.',
        default => 'Centralized registry for all deployment partners across the platform.',
    };
@endphp
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
        margin-top: var(--navbar-height);
        padding: 1.5rem;
        background-color: #f8fafc;
        min-height: calc(100vh - var(--navbar-height));
        transition: all 0.3s ease;
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
        max-height: 600px;
        overflow: auto;
        position: relative;
        border-radius: 12px;
        background: white;
        border: 1px solid #e2e8f0;
    }

    thead th {
        position: sticky; top: 0; z-index: 20;
        background: #f1f5f9 !important;
        font-size: 11px; text-transform: uppercase; font-weight: 700;
        color: #475569; border-bottom: 2px solid #e2e8f0 !important;
    }

    @media (min-width: 992px) {
        .sticky-left { position: sticky; left: 0; z-index: 15; background: white; box-shadow: 2px 0 5px rgba(0,0,0,0.03); }
        .sticky-right { position: sticky; right: 0; z-index: 15; background: white; box-shadow: -2px 0 5px rgba(0,0,0,0.03); }
    }

    .pill {
        padding: 4px 10px; border-radius: 20px; font-size: 10px; font-weight: 800; text-transform: uppercase;
    }
    .pill-active { background: #dcfce7; color: #15803d; }
    .pill-pending { background: #fef3c7; color: #b45309; }
    .pill-suspended { background: #fee2e2; color: #b91c1c; }
    .pill-rejected { background: #f1f5f9; color: #475569; }

    .dropdown-menu { 
        z-index: 9999 !important; 
        border: 0; 
        box-shadow: 0 10px 25px rgba(0,0,0,0.1); 
        padding: 8px;
    }
    .dropdown-item { border-radius: 6px; font-size: 13px; margin-bottom: 2px; }

    .custom-scrollbar::-webkit-scrollbar { width: 5px; height: 5px; }
    .custom-scrollbar::-webkit-scrollbar-thumb { background: #cbd5e0; border-radius: 10px; }
</style>

<div class="master-hub-wrapper">
    
    {{-- THE FIX: Feedback Alerts --}}
    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show border-0 shadow-sm mb-4" role="alert">
            <i class="fas fa-check-circle me-2"></i> {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show border-0 shadow-sm mb-4" role="alert">
            <i class="fas fa-exclamation-triangle me-2"></i> {{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    {{-- Header --}}
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h3 class="fw-bold text-dark mb-0">{{ $pageTitle }}</h3>
            <p class="text-muted small">{{ $pageSubtitle }} | Domain: {{ env('SESSION_DOMAIN', 'System Default') }}</p>
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
                ['l' => 'Total Partners', 'v' => \DB::table('deployment_managers')->count(), 'i' => 'fa-users', 'c' => 'm-primary', 'icon_c' => '#eef2ff', 'txt' => '#6366f1'],
                ['l' => 'Pending Review', 'v' => \DB::table('deployment_managers')->whereIn('status', ['pending', 'pending_info'])->count(), 'i' => 'fa-clock', 'c' => 'm-warning', 'icon_c' => '#fffbeb', 'txt' => '#f59e0b'],
                ['l' => 'Active Status', 'v' => \DB::table('deployment_managers')->where('status', 'active')->count(), 'i' => 'fa-check-circle', 'c' => 'm-success', 'icon_c' => '#f0fdf4', 'txt' => '#10b981'],
                ['l' => 'Suspended', 'v' => \DB::table('deployment_managers')->where('status', 'suspended')->count(), 'i' => 'fa-ban', 'c' => 'm-danger', 'icon_c' => '#fef2f2', 'txt' => '#ef4444'],
                ['l' => 'Rejected Hub', 'v' => \DB::table('deployment_managers')->where('status', 'rejected')->count(), 'i' => 'fa-times-circle', 'c' => 'm-slate', 'icon_c' => '#f8fafc', 'txt' => '#64748b'],
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
                        <input type="text" name="search" class="form-control bg-light border-0 small" placeholder="Search by name, email or business..." value="{{ request('search') }}">
                    </div>
                </div>
                <div class="col-lg-3 col-md-4">
                    <select name="status" class="form-select bg-light border-0 small">
                        <option value="">All Statuses</option>
                        <option value="active" {{ request('status') == 'active' ? 'selected' : '' }}>Active</option>
                        <option value="pending" {{ request('status') == 'pending' ? 'selected' : '' }}>Pending</option>
                        <option value="suspended" {{ request('status') == 'suspended' ? 'selected' : '' }}>Suspended</option>
                        <option value="rejected" {{ request('status') == 'rejected' ? 'selected' : '' }}>Rejected</option>
                    </select>
                </div>
                <div class="col-lg-4 col-md-4 d-flex gap-2">
                    <button type="submit" class="btn btn-primary px-4 fw-bold small">Filter Results</button>
                    @if(request()->hasAny(['search', 'status']))
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
                    <th class="ps-4 sticky-left">Partner Profile</th>
                    <th>Business Entity</th>
                    <th>Contact Info</th>
                    <th>Balance (₦)</th>
                    <th>Status</th>
                    <th class="text-center sticky-right">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($managers as $manager)
                <tr style="{{ in_array($manager->status, ['pending', 'pending_info']) ? 'background: #fffbeb;' : '' }}">
                    <td class="ps-4 sticky-left bg-white">
                        <div class="d-flex align-items-center">
                            <div class="avatar-sm bg-primary text-white rounded-circle d-flex align-items-center justify-content-center me-2 fw-bold" style="width:32px; height:32px; font-size:12px;">
                                {{ substr($manager->manager_name, 0, 1) }}
                            </div>
                            <div class="lh-1">
                                <span class="d-block fw-bold text-dark small">{{ $manager->manager_name }}</span>
                                <span class="text-muted extra-small">#{{ $manager->user_id }}</span>
                            </div>
                        </div>
                    </td>
                    <td>
                        <span class="fw-bold text-primary small d-block mb-0">{{ $manager->business_name }}</span>
                        <span class="text-muted extra-small">{{ $manager->id_type }}: {{ $manager->id_number }}</span>
                    </td>
                    <td class="small">
                        <div class="text-dark">{{ $manager->email }}</div>
                        <div class="text-muted extra-small">{{ $manager->phone }}</div>
                    </td>
                    <td class="fw-bold text-success small">₦{{ number_format($manager->commission_due ?? ($manager->commission ?? 0), 2) }}</td>
                    <td>
                        @php
                            $pillClass = match($manager->status) {
                                'active' => 'pill-active',
                                'pending', 'pending_info' => 'pill-pending',
                                'suspended' => 'pill-suspended',
                                default => 'pill-rejected'
                            };
                        @endphp
                        <span class="pill {{ $pillClass }}">{{ strtoupper(str_replace('_', ' ', $manager->status)) }}</span>
                    </td>
                    <td class="text-center sticky-right bg-white">
                        <div class="d-flex justify-content-center gap-1">
                            <div class="dropdown">
                                <button class="btn btn-xs btn-primary dropdown-toggle" data-bs-toggle="dropdown" data-bs-boundary="viewport">Manage</button>
                                <ul class="dropdown-menu dropdown-menu-end border-0 shadow-lg">
                                    <li><h6 class="dropdown-header extra-small text-uppercase">Partner Control</h6></li>
                                    
                                    @if(in_array($manager->status, ['pending', 'pending_info', 'rejected', 'suspended']))
                                    <li>
                                        <form action="{{ route('super_admin.managers.approve', $manager->id) }}" method="POST" onsubmit="return confirm('Approve this partner for {{ env('SESSION_DOMAIN') }}?')">
                                            @csrf
                                            <button type="submit" class="dropdown-item text-success"><i class="fas fa-check-circle me-2"></i>Approve Partner</button>
                                        </form>
                                    </li>
                                    @endif

                                    @if($manager->status == 'active')
                                    <li>
                                        <form action="{{ route('super_admin.managers.suspend', $manager->id) }}" method="POST">
                                            @csrf
                                            <button type="submit" class="dropdown-item text-warning"><i class="fas fa-pause-circle me-2"></i>Suspend Partner</button>
                                        </form>
                                    </li>
                                    @endif

                                    @if($manager->status != 'rejected')
                                    <li>
                                        <form action="{{ route('super_admin.managers.reject', $manager->id) }}" method="POST">
                                            @csrf
                                            <button type="submit" class="dropdown-item text-danger"><i class="fas fa-times-circle me-2"></i>Reject Application</button>
                                        </form>
                                    </li>
                                    @endif

                                    <li><hr class="dropdown-divider"></li>
                                    <li><a class="dropdown-item" href="{{ route('messages.chat.show', ['id' => $manager->user_id]) }}"><i class="fas fa-comments text-primary me-2"></i>Chat Partner</a></li>
                                    <li><a class="dropdown-item" href="mailto:{{ $manager->email }}"><i class="fas fa-envelope text-info me-2"></i>Email Partner</a></li>
                                </ul>
                            </div>
                            <form action="{{ route('super_admin.managers.delete', $manager->id) }}" method="POST">
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
            Showing {{ method_exists($managers, 'firstItem') ? ($managers->firstItem() ?? 0) : 0 }}
            to {{ method_exists($managers, 'lastItem') ? ($managers->lastItem() ?? 0) : count($managers) }}
            of {{ method_exists($managers, 'total') ? $managers->total() : count($managers) }} partners
        </span>
        <div>
            @if(method_exists($managers, 'links'))
                {{ $managers->links('pagination::bootstrap-4') }}
            @endif
        </div>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        const dropdowns = document.querySelectorAll('.dropdown');
        dropdowns.forEach(el => {
            el.addEventListener('show.bs.dropdown', function () {
                this.closest('tr').style.zIndex = '9999';
                this.closest('td').style.zIndex = '9999';
            });
            el.addEventListener('hide.bs.dropdown', function () {
                this.closest('tr').style.zIndex = '';
                this.closest('td').style.zIndex = '';
            });
        });
    });
</script>
@endsection
