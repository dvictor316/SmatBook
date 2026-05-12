<?php $page = 'recurring-invoice-templates'; ?>
@extends('layout.mainlayout')

@section('content')
<style>
    .ri-hero { border: 1px solid #dbeafe; border-radius: 14px; padding: 18px 20px; background: linear-gradient(135deg, #f8fbff 0%, #eef8f6 100%); box-shadow: 0 12px 30px rgba(15, 23, 42, .06); }
    .ri-stat { border: 1px solid #e5edf7; border-radius: 12px; background: #fff; box-shadow: 0 8px 20px rgba(15, 23, 42, .04); }
    .ri-stat .icon { width: 42px; height: 42px; display: inline-flex; align-items: center; justify-content: center; border-radius: 10px; }
    .ri-table thead th { font-size: 12px; color: #475569; text-transform: uppercase; letter-spacing: .03em; white-space: nowrap; }
</style>
<div class="page-wrapper">
<div class="content container-fluid">

    @component('components.page-header')
        @slot('title') Recurring Invoices @endslot
    @endcomponent

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show"><button type="button" class="btn-close" data-bs-dismiss="alert"></button>{{ session('success') }}</div>
    @endif
    @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show"><button type="button" class="btn-close" data-bs-dismiss="alert"></button>{{ session('error') }}</div>
    @endif

    <div class="ri-hero d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4">
        <div>
            <h4 class="mb-1">Recurring Invoice Automation</h4>
            <div class="text-muted">Schedule drafts, auto-send invoices, reminders, and subscription-ready billing without bypassing normal accounting.</div>
        </div>
        <a href="{{ route('sales.recurring-invoices.create') }}" class="btn btn-primary">
            <i class="fe fe-plus me-1"></i> New Template
        </a>
    </div>

    {{-- ── Stats cards ──────────────────────────────────────────────── --}}
    <div class="row g-3 mb-4">
        <div class="col-6 col-md-2">
            <div class="ri-stat h-100">
                <div class="card-body d-flex align-items-center gap-3">
                    <div class="icon flex-shrink-0 bg-success-light">
                        <i class="fe fe-repeat fs-4 text-success"></i>
                    </div>
                    <div>
                        <div class="text-muted small">Active</div>
                        <div class="fs-4 fw-bold">{{ $stats['active'] }}</div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-2">
            <div class="ri-stat h-100">
                <div class="card-body d-flex align-items-center gap-3">
                    <div class="icon flex-shrink-0 bg-warning-light">
                        <i class="fe fe-pause-circle fs-4 text-warning"></i>
                    </div>
                    <div>
                        <div class="text-muted small">Paused</div>
                        <div class="fs-4 fw-bold">{{ $stats['paused'] }}</div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-2">
            <div class="ri-stat h-100">
                <div class="card-body d-flex align-items-center gap-3">
                    <div class="icon flex-shrink-0 bg-info-light">
                        <i class="fe fe-check-circle fs-4 text-info"></i>
                    </div>
                    <div>
                        <div class="text-muted small">Generated This Month</div>
                        <div class="fs-4 fw-bold">{{ $stats['generated_this_month'] }}</div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-2">
            <div class="ri-stat h-100">
                <div class="card-body d-flex align-items-center gap-3">
                    <div class="icon flex-shrink-0 bg-danger-light">
                        <i class="fe fe-clock fs-4 text-danger"></i>
                    </div>
                    <div>
                        <div class="text-muted small">Overdue Runs</div>
                        <div class="fs-4 fw-bold">{{ $stats['overdue'] ?? 0 }}</div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-2">
            <div class="ri-stat h-100">
                <div class="card-body d-flex align-items-center gap-3">
                    <div class="icon flex-shrink-0 bg-primary-light">
                        <i class="fe fe-trending-up fs-4 text-primary"></i>
                    </div>
                    <div>
                        <div class="text-muted small">Active Forecast</div>
                        <div class="fs-6 fw-bold">{{ number_format($stats['forecast'] ?? 0, 2) }}</div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-2">
            <div class="ri-stat h-100">
                <div class="card-body d-flex align-items-center gap-3">
                    <div class="icon flex-shrink-0 bg-danger-light">
                        <i class="fe fe-alert-triangle fs-4 text-danger"></i>
                    </div>
                    <div>
                        <div class="text-muted small">Failed This Month</div>
                        <div class="fs-4 fw-bold">{{ $stats['failed'] }}</div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- ── Filters + New button ─────────────────────────────────────── --}}
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-body">
            <form method="GET" action="{{ route('sales.recurring-invoices.index') }}" class="row g-2 align-items-end">
                <div class="col-md-3">
                    <input type="text" name="q" value="{{ request('q') }}" class="form-control form-control-sm" placeholder="Search template or customer…">
                </div>
                <div class="col-md-2">
                    <select name="status" class="form-select form-select-sm">
                        <option value="">All Statuses</option>
                        @foreach(['active','paused','completed','cancelled','archived'] as $s)
                            <option value="{{ $s }}" @selected(request('status') === $s)>{{ ucfirst($s) }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <select name="frequency" class="form-select form-select-sm">
                        <option value="">All Frequencies</option>
                        @foreach(['daily'=>'Daily','weekly'=>'Weekly','biweekly'=>'Biweekly','monthly'=>'Monthly','quarterly'=>'Quarterly','semi_annual'=>'Semi-Annual','annual'=>'Annual','custom'=>'Custom'] as $k => $v)
                            <option value="{{ $k }}" @selected(request('frequency') === $k)>{{ $v }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <button type="submit" class="btn btn-sm btn-outline-primary w-100">Filter</button>
                </div>
                <div class="col-md-3 text-md-end">
                    <a href="{{ route('sales.recurring-invoices.create') }}" class="btn btn-sm btn-primary">
                        <i class="fe fe-plus me-1"></i> New Recurring Template
                    </a>
                </div>
            </form>
        </div>
    </div>

    {{-- ── Templates table ──────────────────────────────────────────── --}}
    <div class="card border-0 shadow-sm">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0 ri-table">
                    <thead class="thead-light">
                        <tr>
                            <th>Template</th>
                            <th>Customer</th>
                            <th>Frequency</th>
                            <th>Next Run</th>
                            <th>Last Run</th>
                            <th>Amount</th>
                            <th>Branch</th>
                            <th>Mode</th>
                            <th>Status</th>
                            <th class="text-end">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($templates as $t)
                        <tr>
                            <td>
                                <a href="{{ route('sales.recurring-invoices.show', $t) }}" class="fw-semibold text-dark">
                                    {{ $t->template_name }}
                                </a>
                                <div class="text-muted small">{{ $t->currency }}</div>
                            </td>
                            <td>{{ $t->display_customer_name }}</td>
                            <td>{{ $t->frequency_label }}</td>
                            <td>
                                @if($t->next_run_on)
                                    <span class="{{ $t->isDue() ? 'text-danger fw-semibold' : '' }}">
                                        {{ $t->next_run_on->format('d M Y') }}
                                    </span>
                                @else
                                    <span class="text-muted">—</span>
                                @endif
                            </td>
                            <td>{{ $t->last_run_on ? $t->last_run_on->format('d M Y') : '—' }}</td>
                            <td>{{ $t->currency }} {{ number_format($t->total, 2) }}</td>
                            <td>{{ $t->branch_name ?: ($t->branch_id ?: 'All') }}</td>
                            <td><span class="badge bg-secondary">{{ $t->automation_label }}</span></td>
                            <td><span class="badge {{ $t->status_badge }}">{{ ucfirst($t->status) }}</span></td>
                            <td class="text-end">
                                <div class="dropdown">
                                    <button class="btn btn-sm btn-light" data-bs-toggle="dropdown">
                                        <i class="fas fa-ellipsis-v"></i>
                                    </button>
                                    <ul class="dropdown-menu dropdown-menu-end">
                                        <li>
                                            <a class="dropdown-item" href="{{ route('sales.recurring-invoices.show', $t) }}">
                                                <i class="fe fe-eye me-2"></i>View
                                            </a>
                                        </li>
                                        <li>
                                            <a class="dropdown-item" href="{{ route('sales.recurring-invoices.edit', $t) }}">
                                                <i class="fe fe-edit me-2"></i>Edit
                                            </a>
                                        </li>
                                        @if($t->status === 'active')
                                        <li>
                                            <form method="POST" action="{{ route('sales.recurring-invoices.run', $t) }}">
                                                @csrf
                                                <button type="submit" class="dropdown-item text-primary">
                                                    <i class="fe fe-play me-2"></i>Run Now
                                                </button>
                                            </form>
                                        </li>
                                        <li>
                                            <form method="POST" action="{{ route('sales.recurring-invoices.pause', $t) }}">
                                                @csrf
                                                <button type="submit" class="dropdown-item text-warning">
                                                    <i class="fe fe-pause me-2"></i>Pause
                                                </button>
                                            </form>
                                        </li>
                                        @elseif($t->status === 'paused')
                                        <li>
                                            <form method="POST" action="{{ route('sales.recurring-invoices.resume', $t) }}">
                                                @csrf
                                                <button type="submit" class="dropdown-item text-success">
                                                    <i class="fe fe-play me-2"></i>Resume
                                                </button>
                                            </form>
                                        </li>
                                        @endif
                                        <li>
                                            <form method="POST" action="{{ route('sales.recurring-invoices.clone', $t) }}">
                                                @csrf
                                                <button type="submit" class="dropdown-item">
                                                    <i class="fe fe-copy me-2"></i>Clone
                                                </button>
                                            </form>
                                        </li>
                                        @if(!in_array($t->status, ['cancelled','completed']))
                                        <li><hr class="dropdown-divider"></li>
                                        <li>
                                            <form method="POST" action="{{ route('sales.recurring-invoices.cancel', $t) }}"
                                                  onsubmit="return confirm('Cancel this recurring template?')">
                                                @csrf
                                                <button type="submit" class="dropdown-item text-danger">
                                                    <i class="fe fe-x-circle me-2"></i>Cancel
                                                </button>
                                            </form>
                                        </li>
                                        @endif
                                        @if($t->status !== 'archived')
                                        <li>
                                            <form method="POST" action="{{ route('sales.recurring-invoices.archive', $t) }}"
                                                  onsubmit="return confirm('Archive this recurring template?')">
                                                @csrf
                                                <button type="submit" class="dropdown-item">
                                                    <i class="fe fe-archive me-2"></i>Archive
                                                </button>
                                            </form>
                                        </li>
                                        @endif
                                    </ul>
                                </div>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="10" class="text-center py-5 text-muted">
                                <i class="fe fe-inbox fs-1 d-block mb-2"></i>
                                No recurring templates yet.
                                <a href="{{ route('sales.recurring-invoices.create') }}">Create your first one</a>.
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            @if($templates->hasPages())
            <div class="p-3">{{ $templates->links() }}</div>
            @endif
        </div>
    </div>

</div>
</div>
@endsection
