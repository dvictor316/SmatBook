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
                <p>Track paid commissions, pending payouts, invoices, and sales performance.</p>
            </div>
        </div>

        <div class="agent-grid">
            <section class="agent-card span-4 agent-metric">
                <span class="icon" style="color:var(--agent-green);background:#eafff6;"><i class="fa-solid fa-circle-check"></i></span>
                <div class="label">Paid Commission</div>
                <div class="value">₦{{ number_format($stats['paid_commissions']) }}</div>
            </section>
            <section class="agent-card span-4 agent-metric">
                <span class="icon" style="color:var(--agent-amber);background:#fff8e8;"><i class="fa-solid fa-clock"></i></span>
                <div class="label">Pending Commission</div>
                <div class="value">₦{{ number_format($stats['pending_commissions']) }}</div>
            </section>
            <section class="agent-card span-4 agent-metric">
                <span class="icon"><i class="fa-solid fa-money-bill-trend-up"></i></span>
                <div class="label">Sales Volume</div>
                <div class="value">₦{{ number_format($stats['sales_volume']) }}</div>
            </section>

            <section class="agent-card span-12">
                <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
                    <h4>Commission History</h4>
                    <span class="agent-pill">Total processed this week: ₦{{ number_format($stats['paid_commissions']) }}</span>
                </div>
                <div class="table-responsive">
                    <table class="table align-middle">
                        <thead>
                            <tr><th>Company</th><th>Status</th><th>Amount</th><th>Date</th></tr>
                        </thead>
                        <tbody>
                        @forelse($commissions as $commission)
                            <tr>
                                <td>#{{ $commission->company_id }}</td>
                                <td><span class="agent-pill">{{ ucfirst($commission->status) }}</span></td>
                                <td><strong>₦{{ number_format($commission->amount) }}</strong></td>
                                <td>{{ \Carbon\Carbon::parse($commission->created_at)->format('M j, Y') }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="4" class="text-center agent-muted py-5">No commission history yet.</td></tr>
                        @endforelse
                        </tbody>
                    </table>
                </div>
                @if(method_exists($commissions, 'links'))
                    {{ $commissions->links() }}
                @endif
            </section>
        </div>
    </div>
</div>
@endsection
