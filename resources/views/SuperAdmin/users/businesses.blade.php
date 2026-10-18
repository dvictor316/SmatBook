@extends('layout.mainlayout')

@section('content')
<style>
    :root {
        --sidebar-width: 250px;
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
    }

    body.mini-sidebar .master-hub-wrapper { margin-left: 80px; }
    @media (max-width: 991px) { .master-hub-wrapper { margin-left: 0 !important; } }

    .metric-card {
        background: #fff;
        border: 1px solid #e2e8f0;
        border-radius: 12px;
        padding: 1.25rem;
        border-bottom: 3px solid transparent;
    }

    .m-primary { border-bottom-color: var(--primary); }
    .m-success { border-bottom-color: var(--success); }
    .m-danger { border-bottom-color: var(--danger); }
    .m-warning { border-bottom-color: var(--warning); }
    .m-slate { border-bottom-color: var(--slate); }

    .hub-table-container {
        background: #fff;
        border-radius: 12px;
        border: 1px solid #e2e8f0;
        overflow: hidden;
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
    .pill-inactive { background: #fef2f2; color: #ef4444; }
    .pill-neutral { background: #f1f5f9; color: #64748b; }
</style>

<div class="master-hub-wrapper">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h3 class="fw-bold text-dark mb-0">{{ $paymentGroup === 'paid' ? 'Paid Businesses' : ($paymentGroup === 'pending' ? 'Pending Payment Businesses' : 'Registered Businesses') }}</h3>
            <p class="text-muted small">View registered businesses by payment status.</p>
        </div>
        <div class="d-flex gap-2 align-items-center">
            <select class="form-select form-select-sm" aria-label="Business payment status" onchange="if (this.value) window.location.href = this.value;">
                <option value="{{ route('super_admin.users.index', ['category' => 'registered_businesses', 'payment_group' => 'pending']) }}" @selected($paymentGroup === 'pending')>Pending Businesses</option>
                <option value="{{ route('super_admin.users.index', ['category' => 'registered_businesses', 'payment_group' => 'paid']) }}" @selected($paymentGroup === 'paid')>Paid Businesses</option>
            </select>
            <button onclick="window.print();" class="btn btn-white border px-3 btn-sm fw-bold">
                <i class="fas fa-print me-2 text-primary"></i>Export Registry
            </button>
        </div>
    </div>

    <div class="row g-3 mb-4">
        @php
            $stats = [
                ['l' => 'Total Businesses', 'v' => $metrics['total'] ?? 0, 'c' => 'm-primary'],
                ['l' => 'Active', 'v' => $metrics['active'] ?? 0, 'c' => 'm-success'],
                ['l' => 'Inactive', 'v' => $metrics['inactive'] ?? 0, 'c' => 'm-danger'],
                ['l' => 'Connected Domains', 'v' => $metrics['with_domains'] ?? 0, 'c' => 'm-warning'],
                ['l' => 'Total Revenue', 'v' => '₦' . number_format($metrics['revenue'] ?? 0, 2), 'c' => 'm-slate'],
            ];
        @endphp
        @foreach($stats as $s)
            <div class="col-md col-sm-6">
                <div class="metric-card {{ $s['c'] }}">
                    <div class="small text-muted text-uppercase fw-bold mb-1">{{ $s['l'] }}</div>
                    <div class="fw-bold fs-4 text-dark">{{ is_numeric($s['v']) ? number_format($s['v']) : $s['v'] }}</div>
                </div>
            </div>
        @endforeach
    </div>

    <div class="card border-0 shadow-sm mb-4" style="border-radius: 12px;">
        <div class="card-body p-3">
            <form action="{{ url()->current() }}" method="GET" class="row g-2 align-items-center">
                <input type="hidden" name="category" value="registered_businesses">
                <input type="hidden" name="payment_group" value="{{ $paymentGroup }}">
                <div class="col-lg-6 col-md-5">
                    <div class="input-group">
                        <span class="input-group-text bg-light border-0"><i class="fa fa-search text-muted"></i></span>
                        <input type="text" name="search" class="form-control bg-light border-0 small" placeholder="Search business, owner, email or domain..." value="{{ request('search') }}">
                    </div>
                </div>
                <div class="col-lg-3 col-md-3">
                    <select name="status" class="form-select bg-light border-0 small">
                        <option value="">All Statuses</option>
                        <option value="active" {{ request('status') == 'active' ? 'selected' : '' }}>Active</option>
                        <option value="trial" {{ request('status') == 'trial' ? 'selected' : '' }}>Trial</option>
                        <option value="pending" {{ request('status') == 'pending' ? 'selected' : '' }}>Pending</option>
                        <option value="suspended" {{ request('status') == 'suspended' ? 'selected' : '' }}>Suspended</option>
                        <option value="inactive" {{ request('status') == 'inactive' ? 'selected' : '' }}>Inactive</option>
                    </select>
                </div>
                <div class="col-lg-3 col-md-4 d-flex gap-2">
                    <button type="submit" class="btn btn-primary px-4 fw-bold small">Filter</button>
                    @if(request()->hasAny(['search', 'status']))
                        <a href="{{ route('super_admin.users.index', array_filter(['category' => 'registered_businesses', 'payment_group' => $paymentGroup])) }}" class="btn btn-light border fw-bold small">Reset</a>
                    @endif
                </div>
            </form>
        </div>
    </div>

    @if($paymentGroup === 'pending')
        <form id="bulk-delete-pending-businesses" method="POST" action="{{ route('super_admin.users.businesses.bulk-delete') }}" class="mb-3" onsubmit="return document.querySelectorAll('.pending-business-checkbox:checked').length > 0 && confirm('Remove all selected unpaid businesses and disable their users? Financial records will be retained.');">
            @csrf
            @method('DELETE')
            <button type="submit" class="btn btn-outline-danger btn-sm fw-bold">
                <i class="fas fa-trash me-1"></i> Delete Selected
            </button>
        </form>
    @endif

    <div class="hub-table-container">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        @if($paymentGroup === 'pending')<th class="ps-3"><input type="checkbox" id="select-all-pending-businesses" aria-label="Select all pending businesses"></th>@endif
                        <th class="ps-4">Owner</th>
                        <th>Business</th>
                        <th>Domain</th>
                        <th>Total Paid</th>
                        <th>Last Payment</th>
                        <th>Status</th>
                        @if($paymentGroup === 'pending')<th class="text-end pe-4">Actions</th>@endif
                    </tr>
                </thead>
                <tbody>
                    @forelse($businesses as $business)
                        @php
                            $status = strtolower((string) ($business->status ?? 'active'));
                            $pillClass = in_array($status, ['active', 'trial', 'enabled'], true)
                                ? 'pill-active'
                                : (in_array($status, ['inactive', 'suspended', 'disabled', 'expired'], true) ? 'pill-inactive' : 'pill-neutral');
                        @endphp
                        <tr>
                            @if($paymentGroup === 'pending')
                                <td class="ps-3"><input class="pending-business-checkbox" type="checkbox" name="business_ids[]" value="{{ $business->id }}" form="bulk-delete-pending-businesses" aria-label="Select {{ $business->company_name }}"></td>
                            @endif
                            <td class="ps-4">
                                <div class="fw-bold text-dark">{{ $business->name ?? 'User' }}</div>
                                <div class="text-muted small">{{ $business->email ?? '—' }}</div>
                            </td>
                            <td>
                                <div class="text-dark">{{ $business->company_name ?: 'No business name yet' }}</div>
                                <div class="text-muted small">#{{ $business->id }}</div>
                            </td>
                            <td>
                                <div class="text-dark">{{ $business->company_domain_prefix ?: '—' }}</div>
                                <div class="text-muted small">{{ $business->company_plan ?: 'No plan label' }}</div>
                            </td>
                            <td class="fw-bold text-success">₦{{ number_format((float) ($business->total_paid ?? 0), 2) }}</td>
                            <td class="text-muted small">
                                {{ $business->last_paid_at ? \Illuminate\Support\Carbon::parse($business->last_paid_at)->format('d M Y') : '—' }}
                            </td>
                            <td><span class="pill {{ $pillClass }}">{{ strtoupper($status ?: 'unknown') }}</span></td>
                            @if($paymentGroup === 'pending')
                                <td class="text-end pe-4">
                                    <button type="button" class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#edit-business-{{ $business->id }}" title="Edit business"><i class="fas fa-pen"></i></button>
                                    <form class="d-inline" method="POST" action="{{ route('super_admin.users.businesses.delete', $business->id) }}" onsubmit="return confirm('Remove this business from the registry and disable its users? Financial records will be retained.');">
                                        @csrf @method('DELETE')
                                        <button type="submit" class="btn btn-sm btn-outline-danger" title="Delete business"><i class="fas fa-trash"></i></button>
                                    </form>
                                </td>
                            @endif
                        </tr>
                    @empty
                        <tr>
                            <td colspan="{{ $paymentGroup === 'pending' ? 8 : 7 }}" class="text-center py-5 text-muted small">No registered businesses matched your filters.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    @if($paymentGroup === 'pending')
    @foreach($businesses as $business)
    <div class="modal fade" id="edit-business-{{ $business->id }}" tabindex="-1" aria-labelledby="edit-business-title-{{ $business->id }}" aria-hidden="true">
        <div class="modal-dialog"><div class="modal-content">
            <form method="POST" action="{{ route('super_admin.users.businesses.update', $business->id) }}">
                @csrf @method('PUT')
                <input type="hidden" name="payment_group" value="{{ $paymentGroup }}">
                <div class="modal-header"><h5 class="modal-title" id="edit-business-title-{{ $business->id }}">Edit {{ $business->company_name }}</h5><button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button></div>
                <div class="modal-body">
                    <div class="mb-3"><label class="form-label" for="business-name-{{ $business->id }}">Business name</label><input id="business-name-{{ $business->id }}" name="name" class="form-control" value="{{ $business->company_name }}" required maxlength="191"></div>
                    <div class="mb-3"><label class="form-label" for="business-email-{{ $business->id }}">Business email</label><input id="business-email-{{ $business->id }}" name="email" type="email" class="form-control" value="{{ $business->company_email ?? '' }}"></div>
                    <div class="mb-3"><label class="form-label" for="business-phone-{{ $business->id }}">Phone</label><input id="business-phone-{{ $business->id }}" name="phone" class="form-control" value="{{ $business->company_phone ?? '' }}"></div>
                    <div class="mb-3"><label class="form-label" for="business-status-{{ $business->id }}">Status</label><select id="business-status-{{ $business->id }}" name="status" class="form-select">@foreach(['active' => 'Active', 'trial' => 'Trial', 'pending' => 'Pending', 'pending_payment' => 'Pending Payment', 'inactive' => 'Inactive', 'suspended' => 'Suspended'] as $value => $label)<option value="{{ $value }}" @selected($business->status === $value)>{{ $label }}</option>@endforeach</select></div>
                </div>
                <div class="modal-footer"><button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button><button type="submit" class="btn btn-primary">Save</button></div>
            </form>
        </div></div>
    </div>
    @endforeach
    @endif

    <div class="py-3 d-flex justify-content-between align-items-center">
        <span class="small text-muted">
            Showing {{ method_exists($businesses, 'firstItem') ? ($businesses->firstItem() ?? 0) : 0 }}
            to {{ method_exists($businesses, 'lastItem') ? ($businesses->lastItem() ?? 0) : count($businesses) }}
            of {{ method_exists($businesses, 'total') ? $businesses->total() : count($businesses) }} businesses
        </span>
        <div>
            @if(method_exists($businesses, 'links'))
                {{ $businesses->links('pagination::bootstrap-4') }}
            @endif
        </div>
    </div>
</div>
@if($paymentGroup === 'pending')
    <script>
        document.getElementById('select-all-pending-businesses')?.addEventListener('change', function () {
            document.querySelectorAll('.pending-business-checkbox').forEach(function (checkbox) {
                checkbox.checked = this.checked;
            }, this);
        });
    </script>
@endif
@endsection
