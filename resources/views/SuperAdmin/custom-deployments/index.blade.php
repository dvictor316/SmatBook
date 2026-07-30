@extends('layout.mainlayout')

@section('content')
<style>
    .custom-deploy-wrapper { margin-left: 250px; padding: 1.5rem; background: #f8fafc; min-height: 100vh; }
    body.mini-sidebar .custom-deploy-wrapper { margin-left: 80px; }
    @media (max-width: 991px) { .custom-deploy-wrapper { margin-left: 0 !important; } }
    .cd-card { background: #fff; border: 1px solid #e2e8f0; border-radius: 16px; box-shadow: 0 10px 28px rgba(15,23,42,.06); }
    .cd-metric { padding: 18px; border-bottom: 4px solid #2563eb; }
    .cd-metric.success { border-color: #10b981; }
    .cd-metric.warn { border-color: #f59e0b; }
    .cd-metric.danger { border-color: #ef4444; }
    .cd-pill { display: inline-flex; padding: 5px 10px; border-radius: 999px; font-size: 11px; font-weight: 800; }
    .cd-pill.active { background: #ecfdf5; color: #059669; }
    .cd-pill.suspended { background: #fef2f2; color: #dc2626; }
    .cd-pill.other { background: #f1f5f9; color: #64748b; }
</style>

<div class="custom-deploy-wrapper">
    <div class="d-flex justify-content-between align-items-start flex-wrap gap-3 mb-4">
        <div>
            <h3 class="fw-bold text-dark mb-1">Custom Unlimited Deployments</h3>
            <p class="text-muted mb-0">Manage superadmin-created free/custom workspaces only. Paid deployment flow is separate.</p>
        </div>
        <a href="{{ route('super_admin.custom_deployments.create') }}" class="btn btn-primary fw-bold">
            <i class="fas fa-plus me-2"></i>Create Custom Deployment
        </a>
    </div>

    <div class="row g-3 mb-4">
        @foreach([
            ['label' => 'Total Custom', 'value' => $metrics['total'] ?? 0, 'class' => ''],
            ['label' => 'Active', 'value' => $metrics['active'] ?? 0, 'class' => 'success'],
            ['label' => 'Suspended', 'value' => $metrics['suspended'] ?? 0, 'class' => 'danger'],
            ['label' => 'Unlimited Access', 'value' => $metrics['unlimited'] ?? 0, 'class' => 'warn'],
        ] as $metric)
            <div class="col-lg-3 col-sm-6">
                <div class="cd-card cd-metric {{ $metric['class'] }}">
                    <div class="text-muted small fw-bold text-uppercase">{{ $metric['label'] }}</div>
                    <div class="h3 fw-bold mb-0">{{ number_format($metric['value']) }}</div>
                </div>
            </div>
        @endforeach
    </div>

    <div class="cd-card mb-4">
        <div class="card-body">
            <form method="GET" class="row g-2 align-items-center">
                <div class="col-md-6">
                    <input type="text" name="search" class="form-control" value="{{ request('search') }}" placeholder="Search business, owner, email, domain, plan...">
                </div>
                <div class="col-md-3">
                    <select name="status" class="form-select">
                        <option value="">All Statuses</option>
                        <option value="active" @selected(request('status') === 'active')>Active</option>
                        <option value="suspended" @selected(request('status') === 'suspended')>Suspended</option>
                    </select>
                </div>
                <div class="col-md-3 d-flex gap-2">
                    <button class="btn btn-primary fw-bold" type="submit">Filter</button>
                    <a href="{{ route('super_admin.custom_deployments.index') }}" class="btn btn-light border fw-bold">Reset</a>
                </div>
            </form>
        </div>
    </div>

    <div class="cd-card table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead>
                <tr>
                    <th class="ps-4">Business</th>
                    <th>Owner</th>
                    <th>Plan</th>
                    <th>User Access</th>
                    <th>Domain</th>
                    <th>Status</th>
                    <th class="text-end pe-4">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($deployments as $deployment)
                    @php
                        $status = strtolower((string) ($deployment->status ?? 'active'));
                        if (in_array($status, ['cancelled', 'expired'], true) || strtolower((string) ($deployment->user?->status ?? '')) === 'suspended') {
                            $status = 'suspended';
                        }
                        $company = $deployment->company;
                        $owner = $deployment->user;
                        $isUnlimited = strtolower((string) ($deployment->payment_status ?? '')) === 'free'
                            && (int) ($deployment->user_limit ?? 0) >= 100000;
                        $planTitle = $isUnlimited ? 'Custom Unlimited' : ($deployment->plan_name ?? $deployment->plan ?? 'Custom');
                        $licenseSummary = $isUnlimited
                            ? 'Unlimited · Free license'
                            : (ucfirst($deployment->billing_cycle ?? 'monthly') . ' · ₦' . number_format((float) ($deployment->amount ?? 0), 2));
                        $accessLabel = $isUnlimited ? 'Unlimited users' : number_format((int) ($deployment->user_limit ?? 0)) . ' users';
                    @endphp
                    <tr>
                        <td class="ps-4">
                            <div class="fw-bold">{{ $company?->name ?? $company?->company_name ?? $deployment->subscriber_name ?? 'Custom workspace' }}</div>
                            <div class="text-muted small">#{{ $deployment->id }} · Free custom deployment</div>
                        </td>
                        <td>
                            <div class="fw-semibold">{{ $owner?->name ?? $deployment->subscriber_name ?? '—' }}</div>
                            <div class="text-muted small">{{ $owner?->email ?? '—' }}</div>
                        </td>
                        <td>
                            <div>{{ $planTitle }}</div>
                            <div class="text-muted small">{{ $licenseSummary }}</div>
                        </td>
                        <td>{{ $accessLabel }}</td>
                        <td>
                            <div>{{ $company?->domain ?? (($company?->domain_prefix ?? $deployment->domain_prefix) ? (($company?->domain_prefix ?? $deployment->domain_prefix) . '.' . config('session.domain', 'smartprobook.com')) : '—') }}</div>
                        </td>
                        <td><span class="cd-pill {{ $status === 'active' ? 'active' : ($status === 'suspended' ? 'suspended' : 'other') }}">{{ strtoupper($status) }}</span></td>
                        <td class="text-end pe-4">
                            <div class="d-inline-flex gap-1 flex-wrap justify-content-end">
                                <a href="{{ route('super_admin.custom_deployments.edit', $deployment->id) }}" class="btn btn-sm btn-outline-primary">Edit</a>
                                @if($status === 'active')
                                    <form method="POST" action="{{ route('super_admin.custom_deployments.suspend', $deployment->id) }}">
                                        @csrf
                                        <button class="btn btn-sm btn-outline-warning" onclick="return confirm('Suspend this custom deployment?')">Suspend</button>
                                    </form>
                                @else
                                    <form method="POST" action="{{ route('super_admin.custom_deployments.activate', $deployment->id) }}">
                                        @csrf
                                        <button class="btn btn-sm btn-outline-success">Activate</button>
                                    </form>
                                @endif
                                <form method="POST" action="{{ route('super_admin.custom_deployments.destroy', $deployment->id) }}">
                                    @csrf
                                    @method('DELETE')
                                    <button class="btn btn-sm btn-outline-danger" onclick="return confirm('Remove this custom deployment from the active list?')">Delete</button>
                                </form>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="7" class="text-center text-muted py-5">No custom deployments found.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="py-3">
        {{ $deployments->links('pagination::bootstrap-4') }}
    </div>
</div>
@endsection
