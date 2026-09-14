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
                <div class="label">Available Commission</div>
                <div class="value">₦{{ number_format($stats['available_commissions'] ?? $stats['pending_commissions']) }}</div>
            </section>
            <section class="agent-card span-4 agent-metric">
                <span class="icon"><i class="fa-solid fa-money-bill-trend-up"></i></span>
                <div class="label">Processing Payout</div>
                <div class="value">₦{{ number_format($stats['processing_commissions'] ?? 0) }}</div>
            </section>

            <section class="agent-card span-12">
                <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
                    <div>
                        <h4>Payout Setup</h4>
                        <small class="agent-muted">Add verified bank details, enable automatic payout, or request your available balance.</small>
                    </div>
                    <span class="agent-pill">Status: {{ str_replace('_', ' ', optional($manager)->payout_status ?? 'not_configured') }}</span>
                </div>

                @if(session('success'))
                    <div class="alert alert-success py-2">{{ session('success') }}</div>
                @endif
                @if(session('error'))
                    <div class="alert alert-danger py-2">{{ session('error') }}</div>
                @endif
                @if(session('info'))
                    <div class="alert alert-info py-2">{{ session('info') }}</div>
                @endif

                <form method="POST" action="{{ route('agent.earnings.payout-profile') }}" class="row g-3" id="agent-payout-profile-form">
                    @csrf
                    @php
                        $selectedBankCode = old('payout_bank_code', optional($manager)->payout_bank_code ?? '');
                        $selectedBankName = old('payout_bank_name', optional($manager)->payout_bank_name ?? '');
                        $hasRetryablePayout = !empty($retryablePayout);
                        $canRequestPayout = (($stats['available_commissions'] ?? 0) > 0) || $hasRetryablePayout;
                    @endphp
                    <div class="col-md-4">
                        <label class="form-label fw-semibold">Bank</label>
                        <select name="payout_bank_code" id="agent_payout_bank_code" class="form-control @error('payout_bank_code') is-invalid @enderror" required>
                            <option value="">Select bank</option>
                            @foreach(($paystackBanks ?? collect()) as $bank)
                                <option value="{{ $bank['code'] }}" data-bank-name="{{ $bank['name'] }}" {{ (string) $selectedBankCode === (string) $bank['code'] ? 'selected' : '' }}>
                                    {{ $bank['name'] }}
                                </option>
                            @endforeach
                        </select>
                        <input type="hidden" name="payout_bank_name" id="agent_payout_bank_name" value="{{ $selectedBankName }}">
                        @error('payout_bank_code')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-md-4">
                        <label class="form-label fw-semibold">Account Name</label>
                        <input type="text" name="payout_account_name" class="form-control" value="{{ old('payout_account_name', optional($manager)->payout_account_name ?? '') }}" required>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label fw-semibold">Account Number</label>
                        <input type="text" name="payout_account_number" class="form-control" value="{{ old('payout_account_number', optional($manager)->payout_account_number ?? '') }}" required>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label fw-semibold">Provider</label>
                        <select name="payout_provider" class="form-control" required>
                            <option value="paystack" {{ old('payout_provider', optional($manager)->payout_provider ?? 'paystack') === 'paystack' ? 'selected' : '' }}>Paystack</option>
                            <option value="flutterwave" {{ old('payout_provider', optional($manager)->payout_provider ?? '') === 'flutterwave' ? 'selected' : '' }}>Flutterwave</option>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label fw-semibold">Minimum Payout</label>
                        <input type="number" step="0.01" min="0" name="minimum_payout_amount" class="form-control" value="{{ old('minimum_payout_amount', optional($manager)->minimum_payout_amount ?? 5000) }}">
                    </div>
                    <div class="col-md-4 d-flex align-items-end">
                        <div class="form-check form-switch mb-2">
                            <input class="form-check-input" type="checkbox" id="agent_auto_payout_enabled" name="auto_payout_enabled" value="1" {{ old('auto_payout_enabled', !empty(optional($manager)->auto_payout_enabled)) ? 'checked' : '' }}>
                            <label class="form-check-label fw-semibold" for="agent_auto_payout_enabled">Enable automatic payout</label>
                        </div>
                    </div>
                    <div class="col-12 d-flex gap-2 flex-wrap">
                        <button type="submit" class="agent-button"><i class="fa-solid fa-floppy-disk"></i> Save Payout Profile</button>
                        <button type="submit" form="agent-request-payout-form" class="agent-button soft" {{ !$canRequestPayout ? 'disabled' : '' }}>
                            <i class="fa-solid fa-money-bill-transfer"></i> {{ $hasRetryablePayout ? 'Resume Payout' : 'Request Payout' }}
                        </button>
                    </div>
                </form>
                <form method="POST" action="{{ route('agent.earnings.request-payout') }}" id="agent-request-payout-form">
                    @csrf
                </form>
            </section>

            <section class="agent-card span-12">
                <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
                    <h4>Payout History</h4>
                    <span class="agent-pill">Failed/review: ₦{{ number_format($stats['failed_payouts'] ?? 0) }}</span>
                </div>
                <div class="table-responsive">
                    <table class="table align-middle">
                        <thead>
                            <tr><th>Reference</th><th>Provider</th><th>Amount</th><th>Status</th><th>Date</th></tr>
                        </thead>
                        <tbody>
                        @forelse($recentPayouts ?? [] as $payout)
                            <tr>
                                <td><code>{{ $payout->payout_reference }}</code></td>
                                <td>{{ ucfirst($payout->gateway ?: 'n/a') }}</td>
                                <td><strong>₦{{ number_format((float) $payout->amount, 2) }}</strong></td>
                                <td><span class="agent-pill">{{ str_replace('_', ' ', ucfirst($payout->status)) }}</span></td>
                                <td>{{ optional($payout->processed_at ?? $payout->created_at)->format('M j, Y') }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="5" class="text-center agent-muted py-4">No payout history yet.</td></tr>
                        @endforelse
                        </tbody>
                    </table>
                </div>
            </section>

            <section class="agent-card span-12">
                <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
                    <h4>Commission History</h4>
                    <span class="agent-pill">Sales volume: ₦{{ number_format($stats['sales_volume']) }}</span>
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
                                <td><strong>₦{{ number_format((float) ($commission->commission_amount ?? $commission->amount ?? 0), 2) }}</strong></td>
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
<script>
document.addEventListener('DOMContentLoaded', function () {
    const bankSelect = document.getElementById('agent_payout_bank_code');
    const bankNameInput = document.getElementById('agent_payout_bank_name');
    const syncBankName = () => {
        if (!bankSelect || !bankNameInput) return;
        const selectedOption = bankSelect.options[bankSelect.selectedIndex];
        bankNameInput.value = selectedOption?.dataset?.bankName || selectedOption?.textContent?.trim() || '';
    };

    bankSelect?.addEventListener('change', syncBankName);
    syncBankName();
});
</script>
@endsection
