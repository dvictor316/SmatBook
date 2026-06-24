@extends('layout.mainlayout')

@section('style')
    @include('agent.partials.styles')
@endsection

@section('content')
<div class="page-wrapper">
    <div class="content agent-page">
        <div class="agent-topline">
            <div class="agent-title">
                <h1>Earnings & Wallet</h1>
                <p>Commission wallet, payout status, and revenue generated from deployed businesses.</p>
            </div>
            <a href="{{ route('deployment.commissions.index') }}" class="agent-button"><i class="fa-solid fa-wallet"></i> Payout Center</a>
        </div>

        <section class="agent-card mb-4" style="background:linear-gradient(135deg,#062f68,#0a438d);color:#fff;">
            <div class="d-flex justify-content-between flex-wrap gap-3">
                <div>
                    <small style="color:#bdd7ff;text-transform:uppercase;font-weight:900;">Wallet Balance</small>
                    <h2 style="color:#fff;font-size:34px;">₦{{ number_format($pending) }}</h2>
                    <p class="mb-0" style="color:#d8e7ff;">Available or pending commission awaiting payout cycle.</p>
                </div>
                <span class="agent-pill" style="background:rgba(255,255,255,.16);color:#fff;">{{ number_format((float) ($managerRecord->commission_rate ?? 35), 0) }}% rate</span>
            </div>
        </section>

        <div class="agent-grid mb-4">
            <section class="agent-card span-4 agent-metric agent-tone-green"><span class="icon" style="color:var(--agent-green);background:#eafff6;"><i class="fa-solid fa-coins"></i></span><div class="label">Total Commission</div><div class="value">₦{{ number_format($total) }}</div></section>
            <section class="agent-card span-4 agent-metric agent-tone-blue"><span class="icon"><i class="fa-solid fa-check-circle"></i></span><div class="label">Paid Out</div><div class="value">₦{{ number_format($paid) }}</div></section>
            <section class="agent-card span-4 agent-metric agent-tone-amber"><span class="icon" style="color:var(--agent-amber);background:#fff7e7;"><i class="fa-solid fa-clock"></i></span><div class="label">Pending</div><div class="value">₦{{ number_format($pending) }}</div></section>
        </div>

        <div class="agent-grid">
            <section class="agent-card span-7">
                <h4>Recent Commissions</h4>
                <div class="table-responsive mt-3">
                    <table class="table align-middle">
                        <thead><tr><th>Business</th><th>Plan</th><th>Status</th><th>Amount</th></tr></thead>
                        <tbody>
                        @forelse($commissions as $commission)
                            @php $amount = $commission->commission_amount ?? $commission->amount ?? 0; @endphp
                            <tr><td><strong>{{ $commission->company_name ?? 'Business' }}</strong></td><td>{{ $commission->plan ?? '-' }}</td><td><span class="agent-pill">{{ strtoupper($commission->status ?? 'pending') }}</span></td><td><strong>₦{{ number_format($amount) }}</strong></td></tr>
                        @empty
                            <tr><td colspan="4" class="text-center agent-muted py-4">No commission records yet.</td></tr>
                        @endforelse
                        </tbody>
                    </table>
                </div>
            </section>
            <section class="agent-card span-5">
                <h4>Payout History</h4>
                @forelse($payouts as $payout)
                    <div class="agent-stat-row">
                        <span>{{ strtoupper($payout->status ?? 'processing') }}<br><small>{{ optional(\Carbon\Carbon::parse($payout->created_at))->format('d M Y') }}</small></span>
                        <strong>₦{{ number_format($payout->amount ?? 0) }}</strong>
                    </div>
                @empty
                    <p class="agent-muted mt-3">No payout history yet. Paid payouts will appear here.</p>
                @endforelse
            </section>
        </div>
    </div>
</div>
@endsection
