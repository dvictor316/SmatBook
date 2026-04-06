@extends('layout.mainlayout')

@section('content')
<div class="page-wrapper">
    <div class="content container-fluid">
        <div class="page-header">
            <div class="row align-items-center">
                <div class="col">
                    <h3 class="page-title">Activity Log</h3>
                    <ul class="breadcrumb">
                        <li class="breadcrumb-item"><a href="{{ url('/') }}">Dashboard</a></li>
                        <li class="breadcrumb-item active">Activity Log</li>
                    </ul>
                </div>
                <div class="col-auto">
                    @if(Route::has('activity-log.export'))
                        <a href="{{ route('activity-log.export', request()->query()) }}" class="btn btn-outline-primary">
                            <i class="fa-solid fa-file-export me-1"></i>Export CSV
                        </a>
                    @endif
                </div>
            </div>
        </div>

        <div class="row g-3 mb-4">
            <div class="col-md-3">
                <div class="card border-0 shadow-sm h-100"><div class="card-body"><div class="text-muted small text-uppercase fw-bold">Events</div><div class="fs-3 fw-bold">{{ $stats['total'] ?? 0 }}</div></div></div>
            </div>
            <div class="col-md-3">
                <div class="card border-0 shadow-sm h-100"><div class="card-body"><div class="text-muted small text-uppercase fw-bold">Today</div><div class="fs-3 fw-bold">{{ $stats['today'] ?? 0 }}</div></div></div>
            </div>
            <div class="col-md-3">
                <div class="card border-0 shadow-sm h-100"><div class="card-body"><div class="text-muted small text-uppercase fw-bold">Users</div><div class="fs-3 fw-bold">{{ $stats['users'] ?? 0 }}</div></div></div>
            </div>
            <div class="col-md-3">
                <div class="card border-0 shadow-sm h-100"><div class="card-body"><div class="text-muted small text-uppercase fw-bold">Modules</div><div class="fs-3 fw-bold">{{ $stats['modules'] ?? 0 }}</div></div></div>
            </div>
        </div>

        <div class="card border-0 shadow-sm mb-4">
            <div class="card-body">
                <form method="GET" action="{{ route(Route::currentRouteName()) }}">
                    <div class="row g-3 align-items-end">
                        <div class="col-md-6">
                            <label class="form-label">Search</label>
                            <input type="text" name="search" value="{{ $search ?? '' }}" class="form-control" placeholder="Search description, user, module, or IP">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Module</label>
                            <select name="module" class="form-select">
                                <option value="">All modules</option>
                                @foreach(($modules ?? collect()) as $moduleOption)
                                    <option value="{{ $moduleOption }}" @selected(($module ?? '') === $moduleOption)>
                                        {{ ucfirst($moduleOption) }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-2">
                            <label class="form-label">From</label>
                            <input type="date" name="from_date" value="{{ request('from_date') }}" class="form-control">
                        </div>
                        <div class="col-md-2">
                            <label class="form-label">To</label>
                            <input type="date" name="to_date" value="{{ request('to_date') }}" class="form-control">
                        </div>
                        <div class="col-md-3 d-flex gap-2">
                            <button type="submit" class="btn btn-primary flex-grow-1">
                                <i class="fa-solid fa-filter me-1"></i>Apply
                            </button>
                            <a href="{{ route(Route::currentRouteName()) }}" class="btn btn-outline-secondary flex-grow-1">
                                <i class="fa-solid fa-rotate me-1"></i>Reset
                            </a>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        <div class="card border-0 shadow-sm">
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead class="thead-light">
                        <tr>
                            <th>Date</th>
                            <th>User</th>
                            <th>Module</th>
                            <th>Action</th>
                            <th>Description</th>
                            <th>IP</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse(($logs ?? collect()) as $log)
                            <tr>
                                <td>{{ optional($log->created_at)->format('Y-m-d H:i') }}</td>
                                <td>
                                    <div class="fw-semibold">{{ $log->user?->name ?? 'System' }}</div>
                                    <div class="text-muted small">{{ $log->user?->email ?? 'system@smartprobook.com' }}</div>
                                </td>
                                <td>{{ ucfirst($log->module ?? 'general') }}</td>
                                <td>{{ $log->action ?? 'action' }}</td>
                                <td>{{ $log->description }}</td>
                                <td>{{ $log->ip_address ?? '—' }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="text-center text-muted py-4">No activity logs found.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            @if(isset($logs) && method_exists($logs, 'links'))
                <div class="card-footer bg-white">
                    {{ $logs->links() }}
                </div>
            @endif
        </div>
    </div>
</div>
@endsection
