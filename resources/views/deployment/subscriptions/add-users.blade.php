@extends('layout.mainlayout')

@section('page-title', $pageTitle ?? 'Add Users')

@section('content')
@php
    $selectedSubscription = $selectedSubscription ?? null;
    $fmt = fn ($value) => number_format((float) $value, 2);
@endphp

<div class="sb-shell">
    <div class="d-flex justify-content-between align-items-start flex-wrap gap-3 mb-4">
        <div>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-1" style="font-size:12.5px;">
                    <li class="breadcrumb-item">
                        <a href="{{ route($dashboardRoute ?? 'deployment.dashboard') }}" class="text-decoration-none text-muted">Dashboard</a>
                    </li>
                    <li class="breadcrumb-item active text-dark fw-semibold">Add Users</li>
                </ol>
            </nav>
            <h4 class="fw-bold mb-1">{{ $pageTitle ?? 'Add Users to Business' }}</h4>
            <p class="text-muted small mb-0">{{ $pageSubtitle ?? 'Increase a business user limit.' }}</p>
        </div>
        <a href="{{ route($dashboardRoute ?? 'deployment.dashboard') }}" class="btn btn-sm btn-white border shadow-sm text-muted">
            <i class="fas fa-arrow-left me-1"></i> Back
        </a>
    </div>

    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif
    @if(session('error'))
        <div class="alert alert-danger">{{ session('error') }}</div>
    @endif
    @if($errors->any())
        <div class="alert alert-danger">{{ $errors->first() }}</div>
    @endif

    <div class="row g-4">
        <div class="col-xl-5">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white border-0 pb-0">
                    <h5 class="fw-bold mb-1">Seat Upgrade</h5>
                    <p class="text-muted small mb-0">Choose the business and enter the new total user limit. Seats are activated after checkout payment.</p>
                </div>
                <div class="card-body">
                    <form action="{{ route($formActionRoute ?? 'deployment.subscription.add-users.store') }}" method="POST">
                        @csrf
                        <div class="mb-3">
                            <label class="form-label fw-semibold">Registered Business</label>
                            <select name="subscription_id" id="subscriptionSelect" class="form-select" required>
                                <option value="">Select business...</option>
                                @foreach($subscriptions as $subscription)
                                    @php
                                        $companyName = $subscription->company?->name ?? $subscription->company?->company_name ?? $subscription->subscriber_name ?? 'Business';
                                        $selected = (int) old('subscription_id', $selectedSubscription?->id) === (int) $subscription->id;
                                    @endphp
                                    <option value="{{ $subscription->id }}"
                                        data-limit="{{ (int) $subscription->seatUpgrade->current_limit }}"
                                        data-users="{{ (int) $subscription->seatUpgrade->current_users }}"
                                        data-unit="{{ (float) $subscription->seatUpgrade->unit_amount }}"
                                        {{ $selected ? 'selected' : '' }}>
                                        {{ $companyName }} - {{ $subscription->plan_name ?? $subscription->plan }} - {{ (int) $subscription->seatUpgrade->current_limit }} users
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div class="row g-3 mb-3">
                            <div class="col-6">
                                <div class="p-3 rounded border bg-light">
                                    <div class="small text-muted">Current Limit</div>
                                    <div class="fw-bold" id="currentLimit">{{ $selectedSubscription?->seatUpgrade?->current_limit ?? '-' }}</div>
                                </div>
                            </div>
                            <div class="col-6">
                                <div class="p-3 rounded border bg-light">
                                    <div class="small text-muted">Users Created</div>
                                    <div class="fw-bold" id="currentUsers">{{ $selectedSubscription?->seatUpgrade?->current_users ?? '-' }}</div>
                                </div>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label fw-semibold">New Total User Limit</label>
                            <input type="number" name="new_user_limit" id="newUserLimit" class="form-control"
                                min="{{ (int) (($selectedSubscription?->seatUpgrade?->current_limit ?? 0) + 1) }}"
                                value="{{ old('new_user_limit', $selectedSubscription ? ((int) $selectedSubscription->seatUpgrade->current_limit + 1) : '') }}"
                                placeholder="Example: 10" required>
                            <small class="text-muted">This must be higher than the current limit.</small>
                        </div>

                        <div class="p-3 rounded border mb-3" style="background:#f8fbff;">
                            <div class="d-flex justify-content-between small mb-2">
                                <span class="text-muted">Price per added user</span>
                                <strong>₦<span id="unitPrice">{{ $selectedSubscription ? $selectedSubscription->seatUpgrade->unit_amount_label : '0.00' }}</span></strong>
                            </div>
                            <div class="d-flex justify-content-between small mb-2">
                                <span class="text-muted">Additional users</span>
                                <strong id="extraUsers">0</strong>
                            </div>
                            <div class="d-flex justify-content-between border-top pt-2">
                                <span class="fw-bold">Upgrade Amount</span>
                                <strong>₦<span id="upgradeAmount">0.00</span></strong>
                            </div>
                        </div>

                        <button type="submit" class="btn btn-primary w-100 fw-bold">
                            <i class="fas fa-credit-card me-1"></i> Continue to Checkout
                        </button>
                    </form>
                </div>
            </div>
        </div>

        <div class="col-xl-7">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white border-0 pb-0">
                    <h5 class="fw-bold mb-1">Registered Businesses</h5>
                    <p class="text-muted small mb-0">Only active paid or free subscriptions are available for adding users.</p>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle">
                            <thead>
                                <tr>
                                    <th>Business</th>
                                    <th>Plan</th>
                                    <th class="text-center">Users</th>
                                    <th class="text-end">Seat Price</th>
                                    <th></th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($subscriptions as $subscription)
                                    @php
                                        $companyName = $subscription->company?->name ?? $subscription->company?->company_name ?? $subscription->subscriber_name ?? 'Business';
                                    @endphp
                                    <tr>
                                        <td>
                                            <div class="fw-semibold">{{ $companyName }}</div>
                                            <div class="small text-muted">{{ $subscription->user?->email }}</div>
                                        </td>
                                        <td>
                                            <span class="badge bg-light text-dark border">{{ $subscription->plan_name ?? $subscription->plan }}</span>
                                            <div class="small text-muted">{{ ucfirst((string) $subscription->billing_cycle) }}</div>
                                        </td>
                                        <td class="text-center">
                                            <strong>{{ (int) $subscription->seatUpgrade->current_users }}</strong>
                                            <span class="text-muted">/ {{ (int) $subscription->seatUpgrade->current_limit }}</span>
                                        </td>
                                        <td class="text-end">₦{{ $subscription->seatUpgrade->unit_amount_label }}</td>
                                        <td class="text-end">
                                            <a class="btn btn-sm btn-outline-primary" href="{{ request()->fullUrlWithQuery(['subscription_id' => $subscription->id]) }}">
                                                Select
                                            </a>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="5" class="text-center text-muted py-4">No active registered businesses found.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    {{ $subscriptions->links() }}
                </div>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const select = document.getElementById('subscriptionSelect');
    const newLimit = document.getElementById('newUserLimit');
    const currentLimit = document.getElementById('currentLimit');
    const currentUsers = document.getElementById('currentUsers');
    const unitPrice = document.getElementById('unitPrice');
    const extraUsers = document.getElementById('extraUsers');
    const upgradeAmount = document.getElementById('upgradeAmount');
    const money = new Intl.NumberFormat('en-NG', { minimumFractionDigits: 2, maximumFractionDigits: 2 });

    function recalc() {
        const option = select.options[select.selectedIndex];
        const limit = Number(option?.dataset?.limit || 0);
        const users = Number(option?.dataset?.users || 0);
        const unit = Number(option?.dataset?.unit || 0);
        const next = Number(newLimit.value || 0);
        const extra = Math.max(0, next - limit);

        currentLimit.textContent = limit || '-';
        currentUsers.textContent = users || '-';
        unitPrice.textContent = money.format(unit);
        extraUsers.textContent = extra;
        upgradeAmount.textContent = money.format(extra * unit);

        if (limit > 0) {
            newLimit.min = String(limit + 1);
        }
    }

    select?.addEventListener('change', function () {
        const option = select.options[select.selectedIndex];
        const limit = Number(option?.dataset?.limit || 0);
        if (limit > 0) {
            newLimit.value = String(limit + 1);
        }
        recalc();
    });
    newLimit?.addEventListener('input', recalc);
    recalc();
});
</script>
@endpush
@endsection
