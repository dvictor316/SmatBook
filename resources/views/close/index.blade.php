@extends('layout.mainlayout')

@section('page-title', 'Period Close')

@section('content')
<style>
    :root {
        --sidebar-w: 270px;
        --sidebar-collapsed: 80px;
    }
    #period-close-wrapper {
        margin-left: var(--sidebar-w);
        width: calc(100% - var(--sidebar-w));
        padding: 100px 1.5rem 2rem;
        min-height: 100vh;
        background: #f8fafc;
        transition: margin-left .3s, width .3s;
    }
    body.sidebar-icon-only #period-close-wrapper,
    body.mini-sidebar #period-close-wrapper {
        margin-left: var(--sidebar-collapsed);
        width: calc(100% - var(--sidebar-collapsed));
    }
    @media (max-width: 991.98px) {
        #period-close-wrapper { margin-left: 0; width: 100%; }
    }
    .close-card { border: 1px solid #e2e8f0; border-radius: 12px; }
</style>

<div id="period-close-wrapper">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <div>
            <h4 class="mb-1">Period Close</h4>
            <p class="text-muted mb-0 small">Manage accounting periods, close tasks and approvals.</p>
        </div>
    </div>

    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif
    @if(session('error'))
        <div class="alert alert-danger">{{ session('error') }}</div>
    @endif
    @if(session('info'))
        <div class="alert alert-info">{{ session('info') }}</div>
    @endif

    <div class="row g-3 mb-3">
        <div class="col-md-3">
            <div class="card close-card"><div class="card-body"><div class="text-muted small text-uppercase fw-bold">Periods</div><div class="fs-4 fw-bold">{{ $stats['total'] ?? 0 }}</div></div></div>
        </div>
        <div class="col-md-3">
            <div class="card close-card"><div class="card-body"><div class="text-muted small text-uppercase fw-bold">Open</div><div class="fs-4 fw-bold">{{ $stats['open'] ?? 0 }}</div></div></div>
        </div>
        <div class="col-md-3">
            <div class="card close-card"><div class="card-body"><div class="text-muted small text-uppercase fw-bold">Closed</div><div class="fs-4 fw-bold">{{ $stats['closed'] ?? 0 }}</div></div></div>
        </div>
        <div class="col-md-3">
            <div class="card close-card"><div class="card-body"><div class="text-muted small text-uppercase fw-bold">Pending Tasks</div><div class="fs-4 fw-bold">{{ $stats['pending_tasks'] ?? 0 }}</div></div></div>
        </div>
    </div>

    <div class="card close-card mb-3">
        <div class="card-header bg-white"><strong>Create Accounting Period</strong></div>
        <div class="card-body">
            <form method="POST" action="{{ route('close.periods.store') }}">
                @csrf
                <div class="row g-3">
                    <div class="col-md-4">
                        <label class="form-label">Name</label>
                        <input type="text" name="name" class="form-control" placeholder="2026 Q1" required>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Start Date</label>
                        <input type="date" name="start_date" class="form-control" required>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">End Date</label>
                        <input type="date" name="end_date" class="form-control" required>
                    </div>
                </div>
                <div class="mt-3">
                    <button class="btn btn-primary btn-sm text-white">Add Period</button>
                </div>
            </form>
        </div>
    </div>

    @foreach($periods as $period)
        <div class="card close-card mb-3">
            <div class="card-header bg-white d-flex justify-content-between align-items-center">
                <div>
                    <strong>{{ $period->name }}</strong>
                    <span class="text-muted ms-2">{{ $period->start_date?->format('Y-m-d') }} to {{ $period->end_date?->format('Y-m-d') }}</span>
                </div>
                <span class="badge {{ $period->status === 'closed' ? 'bg-success' : 'bg-warning text-dark' }}">{{ ucfirst($period->status) }}</span>
            </div>
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-lg-6">
                        <h6 class="mb-2">Close Tasks</h6>
                        <form method="POST" action="{{ route('close.tasks.store', $period->id) }}" class="row g-2 mb-2">
                            @csrf
                            <div class="col-md-5"><input class="form-control form-control-sm" name="title" placeholder="Task title" required></div>
                            <div class="col-md-4"><input class="form-control form-control-sm" type="date" name="due_date"></div>
                            <div class="col-md-3"><button class="btn btn-outline-primary btn-sm w-100">Add</button></div>
                            <div class="col-12"><input class="form-control form-control-sm" name="description" placeholder="Description (optional)"></div>
                        </form>
                        <div class="table-responsive">
                            <table class="table table-sm">
                                <thead><tr><th>Title</th><th>Status</th><th>Owner</th><th></th></tr></thead>
                                <tbody>
                                @forelse($period->tasks as $task)
                                    <tr>
                                        <td>{{ $task->title }}</td>
                                        <td><span class="badge {{ $task->status === 'completed' ? 'bg-success' : 'bg-secondary' }}">{{ ucfirst($task->status) }}</span></td>
                                        <td>{{ $task->owner?->name ?? 'Unassigned' }}</td>
                                        <td class="text-end">
                                            @if($task->status !== 'completed')
                                                <form method="POST" action="{{ route('close.tasks.complete', $task->id) }}">
                                                    @csrf
                                                    <button class="btn btn-outline-success btn-sm">Complete</button>
                                                </form>
                                            @endif
                                        </td>
                                    </tr>
                                @empty
                                    <tr><td colspan="4" class="text-muted text-center">No tasks yet.</td></tr>
                                @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                    <div class="col-lg-6">
                        <h6 class="mb-2">Close Approval</h6>
                        <div class="mb-2">
                            <form method="POST" action="{{ route('close.request', $period->id) }}" class="d-inline">
                                @csrf
                                <div class="mb-2">
                                    <textarea class="form-control form-control-sm" name="notes" rows="2" placeholder="Optional close request note"></textarea>
                                </div>
                                <button class="btn btn-primary btn-sm text-white" {{ $period->status === 'closed' ? 'disabled' : '' }}>
                                    Request Close Approval
                                </button>
                            </form>
                        </div>
                        <div class="table-responsive">
                            <table class="table table-sm">
                                <thead><tr><th>Status</th><th>Requested By</th><th>Approved By</th><th></th></tr></thead>
                                <tbody>
                                @forelse($period->approvals as $approval)
                                    <tr>
                                        <td><span class="badge {{ $approval->status === 'approved' ? 'bg-success' : 'bg-warning text-dark' }}">{{ ucfirst($approval->status) }}</span></td>
                                        <td>{{ $approval->requester?->name ?? '-' }}</td>
                                        <td>{{ $approval->approver?->name ?? '-' }}</td>
                                        <td class="text-end">
                                            @if($approval->status !== 'approved')
                                                <form method="POST" action="{{ route('close.approve', $approval->id) }}">
                                                    @csrf
                                                    <button class="btn btn-outline-primary btn-sm">Approve</button>
                                                </form>
                                            @endif
                                        </td>
                                    </tr>
                                @empty
                                    <tr><td colspan="4" class="text-muted text-center">No approvals yet.</td></tr>
                                @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    @endforeach

    @if(method_exists($periods, 'links'))
        <div>{{ $periods->links() }}</div>
    @endif
</div>
@endsection
