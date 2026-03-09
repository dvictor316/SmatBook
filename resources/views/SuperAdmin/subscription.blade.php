@extends('layout.mainlayout')

@section('content') 
@php $page = 'subscription'; @endphp

<style>
/* 1. INSTITUTIONAL COLOR ARCHITECTURE & OVERRIDE */
:root {
    --brand-navy: #0f172a;
    --brand-blue: #2563eb;
    --brand-slate: #64748b;
    --brand-bg: #f4f7fa !important;  /* Sanatized Grey Background */
    --brand-card: #ffffff !important; /* Pure White Nodes */
    --brand-success: #059669;
    --brand-danger: #dc2626;
    --border-light: #e2e8f0;
}

/* Force global background parity */
body, .page-wrapper, .main-wrapper, .report-page-wrapper { 
    background-color: var(--brand-bg) !important; 
}

.report-page-wrapper { 
    margin-left: 250px; 
    padding: 25px; 
    min-height: 100vh; 
    margin-top: 8px;
    font-family: 'Inter', system-ui, sans-serif;
    transition: all 0.3s ease;
}
body.mini-sidebar .report-page-wrapper { margin-left: 80px; }

/* 2. COMMAND HEADER & TABS */
.report-header-bar {
    background: var(--brand-card);
    height: 75px;
    padding: 0 25px;
    border-radius: 12px;
    border: 1px solid var(--border-light);
    border-bottom: 4px solid var(--brand-navy);
    display: flex;
    align-items: center;
    justify-content: space-between;
    margin-bottom: 25px;
}

.inst-nav-tabs { display: flex; gap: 20px; border-bottom: 1px solid var(--border-light); margin-bottom: 25px; }
.inst-nav-link {
    padding: 10px 5px;
    font-size: 11px;
    font-weight: 800;
    text-transform: uppercase;
    color: var(--brand-slate);
    text-decoration: none;
    border-bottom: 3px solid transparent;
}
.inst-nav-link.active { color: var(--brand-blue); border-bottom-color: var(--brand-blue); }

/* 3. METRICS (EXECUTIVE MONEY FONT) */
.metric-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 20px; margin-bottom: 30px; }
.metric-node {
    background: var(--brand-card);
    border: 1px solid var(--border-light);
    border-left: 5px solid var(--brand-navy);
    padding: 20px;
    border-radius: 12px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.02);
}
.metric-label { font-size: 10px; font-weight: 800; color: var(--brand-slate); text-transform: uppercase; letter-spacing: 1px; }
.metric-value-money { font-size: 1.2rem; font-weight: 800; color: var(--brand-navy); margin-top: 5px; }
.metric-value-count { font-size: 1.5rem; font-weight: 900; color: var(--brand-navy); margin-top: 5px; }

/* 4. PLAN NODE VISIBILITY */
.plan-node-badge {
    background: #f1f5f9;
    color: var(--brand-navy);
    padding: 6px 12px;
    border-radius: 6px;
    font-weight: 800;
    font-size: 10px;
    text-transform: uppercase;
    border: 1px solid var(--brand-navy);
    letter-spacing: 0.5px;
    display: inline-block;
}

/* 5. REGISTRY TABLE */
.report-card { background: var(--brand-card); border-radius: 16px; border: 1px solid var(--border-light); overflow: hidden; box-shadow: 0 10px 25px rgba(0,0,0,0.03); }
.table thead th {
    background: #f8fafc !important;
    color: var(--brand-navy) !important;
    font-weight: 800 !important;
    text-transform: uppercase;
    font-size: 10px;
    padding: 18px 15px;
    border-bottom: 2px solid var(--brand-navy) !important;
}
.table tbody td { padding: 15px; vertical-align: middle; color: var(--brand-navy); font-weight: 600; font-size: 13px; }
.table-money { font-size: 13px; font-weight: 700; color: var(--brand-navy); }

/* 6. STATUS PILLS */
.status-pill { padding: 5px 12px; border-radius: 50px; font-size: 10px; font-weight: 800; text-transform: uppercase; border: 1px solid currentColor; }
.status-active { background: #ecfdf5; color: var(--brand-success); }
.status-expired { background: #fef2f2; color: var(--brand-danger); }
.status-pending { background: #fffbeb; color: #d97706; }

/* 7. PAGINATION & PRINT LOGIC */
.pagination-container { padding: 20px; background: #fff; border-top: 1px solid var(--border-light); }

@media print {
    .header, .sidebar, .inst-nav-tabs, .dropdown, .btn, .no-print, .pagination-container {
        display: none !important;
    }
    .report-page-wrapper { margin: 0 !important; padding: 0 !important; width: 100% !important; }
    .report-header-bar { border: none !important; box-shadow: none !important; margin-bottom: 30px !important; }
    .report-card { border: 1px solid #000 !important; box-shadow: none !important; }
    body { background-color: #fff !important; }
}

@media(max-width: 1200px) { .report-page-wrapper { margin-left: 0 !important; padding: 15px; } }
</style>

<div class="report-page-wrapper">
    
    {{-- Institutional Tabs --}}
    <div class="inst-nav-tabs no-print">
        <a href="{{ route('super_admin.packages.index') }}" class="inst-nav-link {{ $page == 'packages' ? 'active' : '' }}">Subscription Plans</a>
        <a href="{{ route('super_admin.subscriptions.index') }}" class="inst-nav-link {{ $page == 'subscription' ? 'active' : '' }}">Subscribers Registry</a>
    </div>

    {{-- Command Header --}}
    <div class="report-header-bar">
        <div class="d-flex align-items-center">
            <h5 class="fw-bold">SUBSCRIBER MANAGEMENT HUB</h5>
            <div class="vr mx-3 opacity-25" style="height: 25px;"></div>
            <span class="badge bg-light text-dark border px-3 py-2 fw-bold" style="font-size: 10px;">
                RECORDS LOADED: {{ $subscriptions->total() }}
            </span>
        </div>
        <div class="d-flex gap-2 no-print">
            <button onclick="window.print()" class="btn btn-sm btn-white border shadow-sm fw-bold px-3">
                <i class="fas fa-print me-2 text-primary"></i> PRINT AUDIT
            </button>
            <a href="{{ route('super_admin.dashboard') }}" class="btn btn-sm btn-navy text-white fw-bold px-3" style="background: var(--brand-navy);">COMMAND DASH</a>
        </div>
    </div>

    {{-- Metrics --}}
    <div class="metric-grid">
        <div class="metric-node">
            <div class="metric-label">System Liquidity</div>
            <div class="metric-value-money">₦{{ number_format($subscriptions->sum('amount'), 2) }}</div>
        </div>
        <div class="metric-node" style="border-left-color: var(--brand-blue);">
            <div class="metric-label">Total Node Users</div>
            <div class="metric-value-count">{{ $subscriptions->total() }}</div>
        </div>
        <div class="metric-node" style="border-left-color: var(--brand-success);">
            <div class="metric-label">Active Deployments</div>
            <div class="metric-value-count" style="color: var(--brand-success);">{{ $subscriptions->where('status', 'Active')->count() }}</div>
        </div>
        <div class="metric-node" style="border-left-color: var(--brand-danger);">
            <div class="metric-label">Expired Entities</div>
            <div class="metric-value-count" style="color: var(--brand-danger);">{{ $subscriptions->where('status', 'Expired')->count() }}</div>
        </div>
    </div>

    {{-- Registry Table --}}
    <div class="report-card">
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead>
                    <tr>
                        <th>Subscriber Entity</th>
                        <th class="text-center">Plan Node</th>
                        <th>Cycle</th>
                        <th class="text-end">Value</th>
                        <th class="text-center">Start Date</th>
                        <th class="text-center">Expiry</th>
                        <th class="text-center">Status</th>
                        <th class="text-center">Payment</th>
                        <th class="text-end no-print">Command</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($subscriptions as $sub)
                        <tr>
                            <td>
                                <div class="d-flex align-items-center">
                                    <div class="avatar avatar-xs me-3 no-print">
                                        <img class="avatar-img rounded-circle border" 
                                             src="{{ ($sub->user && $sub->user->image) ? asset('storage/'.$sub->user->image) : asset('assets/img/profiles/avatar-01.jpg') }}" alt="User">
                                    </div>
                                    <div>
                                        <div class="fw-bold text-dark">{{ $sub->subscriber_name }}</div>
                                        <div class="small text-primary fw-bold" style="font-family: monospace;">{{ $sub->domain_prefix }}.smatbook.com</div>
                                    </div>
                                </div>
                            </td>
                            <td class="text-center">
                                <span class="plan-node-badge">{{ $sub->plan->name ?? 'STANDARD' }}</span>
                            </td>
                            <td><span class="small fw-bold text-secondary text-uppercase">{{ $sub->billing_cycle }}</span></td>
                            <td class="text-end table-money">₦{{ number_format($sub->amount, 2) }}</td>
                            <td class="text-center small fw-bold">{{ \Carbon\Carbon::parse($sub->start_date)->format('d M Y') }}</td>
                            <td class="text-center">
                                @php $isPast = \Carbon\Carbon::parse($sub->end_date)->isPast(); @endphp
                                <span class="{{ $isPast ? 'text-danger fw-bold' : 'fw-bold' }} small">
                                    {{ \Carbon\Carbon::parse($sub->end_date)->format('d M Y') }}
                                </span>
                            </td>
                            <td class="text-center">
                                <span class="status-pill {{ match($sub->status) { 'Active' => 'status-active', 'Pending' => 'status-pending', 'Expired' => 'status-expired', default => '' } }}">
                                    {{ $sub->status }}
                                </span>
                            </td>
                            <td class="text-center">
                                @php $ps = strtolower((string)($sub->payment_status ?? 'unpaid')); @endphp
                                @if($ps === 'paid')
                                    <span class="status-pill status-active">Paid</span>
                                @elseif($ps === 'pending_verification')
                                    <span class="status-pill status-pending">Pending Verification</span>
                                @elseif($ps === 'failed')
                                    <span class="status-pill status-expired">Failed</span>
                                @else
                                    <span class="status-pill status-pending">{{ strtoupper($sub->payment_status ?? 'UNPAID') }}</span>
                                @endif
                            </td>
                            <td class="text-end no-print">
                                <div class="dropdown">
                                    <button class="btn btn-sm btn-white border shadow-sm" data-bs-toggle="dropdown">
                                        <i class="fas fa-cog"></i>
                                    </button>
                                    <div class="dropdown-menu dropdown-menu-end shadow-lg border-0">
                                        @if(strtolower((string)($sub->payment_gateway ?? '')) === 'bank_transfer' && strtolower((string)($sub->payment_status ?? '')) === 'pending_verification')
                                            <form action="{{ route('super_admin.subscriptions.transfer.approve', $sub->id) }}" method="POST" class="px-2 py-1">
                                                @csrf
                                                <button type="submit" class="dropdown-item fw-bold text-success" onclick="return confirm('Approve this bank transfer and activate subscription?')">
                                                    <i class="fe fe-check-circle me-2"></i> Approve Transfer
                                                </button>
                                            </form>
                                            <form action="{{ route('super_admin.subscriptions.transfer.reject', $sub->id) }}" method="POST" class="px-2 py-1">
                                                @csrf
                                                <input type="hidden" name="note" value="Rejected by super admin">
                                                <button type="submit" class="dropdown-item fw-bold text-danger" onclick="return confirm('Reject this bank transfer?')">
                                                    <i class="fe fe-x-circle me-2"></i> Reject Transfer
                                                </button>
                                            </form>
                                            <div class="dropdown-divider"></div>
                                        @endif
                                        <a class="dropdown-item fw-bold" href="{{ route('super_admin.subscriptions.edit', $sub->id) }}">
                                            <i class="fe fe-edit me-2 text-primary"></i> Edit Access
                                        </a>
                                        <div class="dropdown-divider"></div>
                                        <form action="{{ route('super_admin.subscriptions.destroy', $sub->id) }}" method="POST">
                                            @csrf @method('DELETE')
                                            <button type="submit" class="dropdown-item text-danger fw-bold" onclick="return confirm('Purge node?')">
                                                <i class="fe fe-trash-2 me-2"></i> Delete Node
                                            </button>
                                        </form>
                                    </div>
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        {{-- RESTORED PAGINATION --}}
        <div class="pagination-container no-print">
            <div class="d-flex justify-content-between align-items-center">
                <p class="small text-muted fw-bold mb-0">Showing {{ $subscriptions->firstItem() }} to {{ $subscriptions->lastItem() }} of {{ $subscriptions->total() }} Nodes</p>
                <div>
                    {{ $subscriptions->links() }}
                </div>
            </div>
        </div>

        {{-- Print Footer --}}
        <div class="d-none d-print-block p-4 text-center border-top">
            <p class="small text-muted fw-bold">SmartProbook Institutional Node Audit Summary • Generated: {{ date('d M Y H:i') }}</p>
        </div>
    </div>
</div>
@endsection
