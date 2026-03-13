<?php $page = 'balance-sheet'; ?>
@extends('layout.mainlayout')

@section('content')
<style>
    :root {
        --text-dark: #1e293b;
        --text-light: #94a3b8;
        --brand-blue: #2563eb;
        --bg-alice: #f8fafc; /* Extremely faint blue-grey tint */
        --border-faint: #f1f5f9; /* The faint line color */
        --border-blue: #e0f2fe;
    }

    .page-wrapper { background: #f8fafc; min-height: 100vh; color: var(--text-dark); }

    .report-container {
        max-width: 1280px;
        margin: 0 auto 24px auto;
        background: #fff;
        border: 1px solid #e2e8f0;
        border-radius: 14px;
        box-shadow: 0 6px 16px rgba(15, 23, 42, 0.06);
        padding: 1.5rem;
    }

    /* Header Styling */
    .report-header {
        border-bottom: 1.5px solid var(--brand-blue);
        padding-bottom: 1rem;
        margin-bottom: 2rem;
    }

    .company-name {
        font-size: 1.05rem;
        font-weight: 800;
        text-transform: uppercase;
        letter-spacing: 0.03em;
        margin: 0;
    }

    .report-title {
        font-size: 0.7rem;
        font-weight: 600;
        color: var(--brand-blue);
        text-transform: uppercase;
        letter-spacing: 0.12em;
    }

    /* Summary Snapshot */
    .summary-grid {
        display: grid;
        grid-template-columns: repeat(3, 1fr);
        gap: 0.75rem;
        margin-bottom: 2.5rem;
    }

    .summary-card {
        padding: 0.75rem 1rem;
        border-radius: 4px;
        background: var(--bg-alice);
        border: 1px solid var(--border-blue);
    }

    .summary-label {
        font-size: 0.62rem;
        font-weight: 700;
        color: var(--text-light);
        text-transform: uppercase;
        margin-bottom: 2px;
        display: block;
    }

    .summary-amount {
        font-size: 0.95rem;
        font-weight: 800;
    }

    /* Grid Structure */
    .balance-sheet-grid {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 1.5rem;
        align-items: start;
    }

    .statement-panel {
        min-width: 0;
        height: 100%;
        background: linear-gradient(180deg, #ffffff 0%, #f8fbff 100%);
        border: 1px solid #dbe7f5;
        border-radius: 18px;
        padding: 1.25rem 1.1rem;
        box-shadow: inset 0 1px 0 rgba(255, 255, 255, 0.85);
    }

    .statement-panel--assets {
        border-top: 4px solid #2563eb;
    }

    .statement-panel--liabilities {
        border-top: 4px solid #0f766e;
    }

    .section-card {
        background: #fff;
        border: 1px solid #e2e8f0;
        border-radius: 14px;
        padding: 0.9rem 1rem;
        box-shadow: 0 4px 10px rgba(15, 23, 42, 0.04);
    }

    .section-card + .section-card {
        margin-top: 1rem;
    }

    .ledger-section-title {
        font-size: 0.78rem;
        font-weight: 800;
        text-transform: uppercase;
        color: #0f172a;
        background: linear-gradient(135deg, #dbeafe, #eff6ff);
        border: 1px solid #bfdbfe;
        border-radius: 999px;
        padding: 0.45rem 0.8rem;
        margin-bottom: 1rem;
        display: inline-flex;
        align-items: center;
        gap: 0.45rem;
    }

    .group-label {
        font-size: 0.68rem;
        font-weight: 800;
        color: var(--brand-blue);
        padding: 12px 0 4px 0;
        text-transform: uppercase;
    }

    /* Faint lines between items */
    .account-row {
        display: flex;
        justify-content: space-between;
        padding: 6px 0;
        font-size: 0.72rem;
        border-bottom: 1px solid var(--border-faint); /* Faint separating line */
    }

    .account-row:last-of-type {
        border-bottom: none;
    }

    .account-name {
        font-weight: 500;
        color: #334155;
    }

    .account-val {
        font-weight: 600;
        font-variant-numeric: tabular-nums;
    }

    .txn-list {
        margin: 6px 0 10px 0;
        border: 1px dashed #e2e8f0;
        border-radius: 8px;
        background: #f8fafc;
        overflow: hidden;
        max-height: 220px;
        overflow-y: auto;
    }

    .txn-item {
        display: grid;
        grid-template-columns: 74px 80px 1fr 88px 88px;
        gap: 6px;
        align-items: center;
        padding: 5px 8px;
        font-size: 0.64rem;
        border-bottom: 1px solid #eef2f7;
    }

    .txn-item:last-child { border-bottom: none; }
    .txn-head { font-weight: 700; color: #64748b; background: #f1f5f9; }
    .txn-mono { font-variant-numeric: tabular-nums; }
    .txn-desc { color: #475569; }

    /* Accounting Totals */
    .subtotal-row {
        display: flex;
        justify-content: space-between;
        padding: 8px 0;
        margin-top: 2px;
        font-weight: 700;
        font-size: 0.72rem;
        border-top: 1px solid var(--text-dark);
        color: var(--text-dark);
    }

    .grand-total-row {
        display: flex;
        justify-content: space-between;
        padding: 10px 0;
        margin-top: 15px;
        font-weight: 800;
        font-size: 0.78rem;
        border-top: 1.5px solid var(--text-dark);
        border-bottom: 3.5px double var(--text-dark);
    }

    /* Verification strip */
    .status-strip {
        grid-column: span 2;
        margin-top: 1rem;
        padding: 0.75rem 0.9rem;
        font-size: 0.68rem;
        font-weight: 800;
        text-align: center;
        border-radius: 12px;
        letter-spacing: 0.05em;
        text-transform: uppercase;
    }

    .is-balanced { background: #f0fdf4; color: #166534; border: 1px solid #dcfce7; }
    .not-balanced { background: #fef2f2; color: #991b1b; border: 1px solid #fee2e2; }

    .btn-mini {
        font-size: 0.62rem;
        padding: 0.3rem 0.7rem;
        font-weight: 700;
        text-transform: uppercase;
        border-radius: 3px;
    }

    .empty-state-row {
        padding: 0.8rem 0;
        color: #94a3b8;
        font-size: 0.74rem;
        border-bottom: 1px solid var(--border-faint);
    }

    @media (max-width: 991.98px) {
        .summary-grid {
            grid-template-columns: 1fr;
        }

        .balance-sheet-grid {
            grid-template-columns: 1fr;
        }

        .status-strip {
            grid-column: span 1;
        }
    }

    @media print {
        .no-print { display: none !important; }
        .page-wrapper { padding-top: 0; }
        .balance-sheet-grid { gap: 2.5rem; }
        .account-row { border-bottom-color: #f1f1f1 !important; }
    }
</style>

<div class="page-wrapper">
    <div class="content container-fluid">
        <div class="page-header">
            <div class="content-page-header">
                <h5>Balance Sheet</h5>
            </div>
        </div>
        <div class="report-container">
        
        <div class="report-header d-flex justify-content-between align-items-end">
            <div>
                <div class="report-title">Statement of Financial Position</div>
                <h1 class="company-name">{{ config('company.name', 'SMAT Company') }}</h1>
                <div class="text-muted" style="font-size: 0.62rem;">Date: {{ \Carbon\Carbon::parse($reportDate ?? now())->format('d F Y') }}</div>
            </div>
            <div class="d-flex gap-1 no-print">
                <button onclick="window.print()" class="btn btn-light border btn-mini">Print</button>
                <button onclick="exportToPDF()" class="btn btn-light border btn-mini text-danger">PDF</button>
                <button onclick="exportToExcel()" class="btn btn-light border btn-mini text-success">Excel</button>
            </div>
        </div>

        <div class="summary-grid">
            <div class="summary-card">
                <span class="summary-label">Total Assets</span>
                <span class="summary-amount">₦{{ number_format($totalAssets ?? 0, 2) }}</span>
            </div>
            <div class="summary-card">
                <span class="summary-label">Total Liabilities</span>
                <span class="summary-amount">₦{{ number_format($totalLiabilities ?? 0, 2) }}</span>
            </div>
            <div class="summary-card">
                <span class="summary-label">Equity</span>
                <span class="summary-amount">₦{{ number_format($totalEquity ?? 0, 2) }}</span>
            </div>
        </div>

        

        <div class="balance-sheet-grid">
            {{-- ASSETS SIDE --}}
            <div class="statement-panel statement-panel--assets">
                <div class="ledger-section-title">01 Assets</div>
                <div class="section-card">
                <div class="group-label">Current Assets</div>
                @forelse($currentAssets ?? [] as $asset)
                <div class="account-row">
                    <span class="account-name">{{ $asset->name }}</span>
                    <span class="account-val">{{ number_format($asset->balance, 2) }}</span>
                </div>
                @if(($asset->transactions ?? collect())->isNotEmpty())
                <div class="txn-list">
                    <div class="txn-item txn-head">
                        <span>Date</span><span>Ref</span><span>Description</span><span>Debit</span><span>Credit</span>
                    </div>
                    @foreach($asset->transactions as $txn)
                    <div class="txn-item">
                        <span class="txn-mono">{{ \Carbon\Carbon::parse($txn->transaction_date)->format('d M y') }}</span>
                        <span>{{ $txn->reference ?: '-' }}</span>
                        <span class="txn-desc">{{ $txn->description ?: ($txn->transaction_type ?: 'Entry') }}</span>
                        <span class="txn-mono">{{ number_format((float) $txn->debit, 2) }}</span>
                        <span class="txn-mono">{{ number_format((float) $txn->credit, 2) }}</span>
                    </div>
                    @endforeach
                </div>
                @endif
                @empty
                <div class="empty-state-row">No current asset accounts found for the selected date.</div>
                @endforelse
                <div class="subtotal-row">
                    <span>Total Current Assets</span>
                    <span>{{ number_format($totalCurrentAssets ?? 0, 2) }}</span>
                </div>
                </div>

                <div class="section-card">
                <div class="group-label">Fixed Assets</div>
                @forelse($fixedAssets ?? [] as $asset)
                <div class="account-row">
                    <span class="account-name">{{ $asset->name }}</span>
                    <span class="account-val">{{ number_format($asset->balance, 2) }}</span>
                </div>
                @if(($asset->transactions ?? collect())->isNotEmpty())
                <div class="txn-list">
                    <div class="txn-item txn-head">
                        <span>Date</span><span>Ref</span><span>Description</span><span>Debit</span><span>Credit</span>
                    </div>
                    @foreach($asset->transactions as $txn)
                    <div class="txn-item">
                        <span class="txn-mono">{{ \Carbon\Carbon::parse($txn->transaction_date)->format('d M y') }}</span>
                        <span>{{ $txn->reference ?: '-' }}</span>
                        <span class="txn-desc">{{ $txn->description ?: ($txn->transaction_type ?: 'Entry') }}</span>
                        <span class="txn-mono">{{ number_format((float) $txn->debit, 2) }}</span>
                        <span class="txn-mono">{{ number_format((float) $txn->credit, 2) }}</span>
                    </div>
                    @endforeach
                </div>
                @endif
                @empty
                <div class="empty-state-row">No fixed asset accounts found for the selected date.</div>
                @endforelse
                <div class="subtotal-row">
                    <span>Total Fixed Assets</span>
                    <span>{{ number_format($totalFixedAssets ?? 0, 2) }}</span>
                </div>
                </div>

                <div class="grand-total-row">
                    <span>TOTAL ASSETS</span>
                    <span>₦{{ number_format($totalAssets ?? 0, 2) }}</span>
                </div>
            </div>

            {{-- LIABILITIES SIDE --}}
            <div class="statement-panel statement-panel--liabilities">
                <div class="ledger-section-title">02 Liabilities & Equity</div>
                <div class="section-card">
                <div class="group-label">Liabilities</div>
                @forelse($currentLiabilities ?? [] as $liability)
                <div class="account-row">
                    <span class="account-name">{{ $liability->name }}</span>
                    <span class="account-val">{{ number_format($liability->balance, 2) }}</span>
                </div>
                @if(($liability->transactions ?? collect())->isNotEmpty())
                <div class="txn-list">
                    <div class="txn-item txn-head">
                        <span>Date</span><span>Ref</span><span>Description</span><span>Debit</span><span>Credit</span>
                    </div>
                    @foreach($liability->transactions as $txn)
                    <div class="txn-item">
                        <span class="txn-mono">{{ \Carbon\Carbon::parse($txn->transaction_date)->format('d M y') }}</span>
                        <span>{{ $txn->reference ?: '-' }}</span>
                        <span class="txn-desc">{{ $txn->description ?: ($txn->transaction_type ?: 'Entry') }}</span>
                        <span class="txn-mono">{{ number_format((float) $txn->debit, 2) }}</span>
                        <span class="txn-mono">{{ number_format((float) $txn->credit, 2) }}</span>
                    </div>
                    @endforeach
                </div>
                @endif
                @empty
                <div class="empty-state-row">No liability accounts found for the selected date.</div>
                @endforelse
                <div class="subtotal-row">
                    <span>Total Liabilities</span>
                    <span>{{ number_format($totalLiabilities ?? 0, 2) }}</span>
                </div>
                </div>

                <div class="section-card">
                <div class="group-label">Capital & Equity</div>
                @forelse($equity ?? [] as $eq)
                <div class="account-row">
                    <span class="account-name">{{ $eq->name }}</span>
                    <span class="account-val">{{ number_format($eq->balance, 2) }}</span>
                </div>
                @if(($eq->transactions ?? collect())->isNotEmpty())
                <div class="txn-list">
                    <div class="txn-item txn-head">
                        <span>Date</span><span>Ref</span><span>Description</span><span>Debit</span><span>Credit</span>
                    </div>
                    @foreach($eq->transactions as $txn)
                    <div class="txn-item">
                        <span class="txn-mono">{{ \Carbon\Carbon::parse($txn->transaction_date)->format('d M y') }}</span>
                        <span>{{ $txn->reference ?: '-' }}</span>
                        <span class="txn-desc">{{ $txn->description ?: ($txn->transaction_type ?: 'Entry') }}</span>
                        <span class="txn-mono">{{ number_format((float) $txn->debit, 2) }}</span>
                        <span class="txn-mono">{{ number_format((float) $txn->credit, 2) }}</span>
                    </div>
                    @endforeach
                </div>
                @endif
                @empty
                <div class="empty-state-row">No equity accounts found for the selected date.</div>
                @endforelse
                <div class="account-row">
                    <span class="account-name">Retained Earnings</span>
                    <span class="account-val">{{ number_format($retainedEarnings ?? 0, 2) }}</span>
                </div>
                <div class="subtotal-row">
                    <span>Total Equity</span>
                    <span>{{ number_format($totalEquity ?? 0, 2) }}</span>
                </div>
                </div>

                <div class="grand-total-row">
                    <span>TOTAL LIABILITIES & EQUITY</span>
                    <span>₦{{ number_format(($totalLiabilities ?? 0) + ($totalEquity ?? 0), 2) }}</span>
                </div>
            </div>

            {{-- Validation --}}
            @php
                $diff = abs(($totalAssets ?? 0) - (($totalLiabilities ?? 0) + ($totalEquity ?? 0)));
                $isBalanced = $diff < 0.01;
            @endphp
            <div class="status-strip {{ $isBalanced ? 'is-balanced' : 'not-balanced' }}">
                {{ $isBalanced ? 'Verification: Statement is in Balance' : 'Discrepancy: ₦' . number_format($diff, 2) }}
            </div>
        </div>
        </div>
    </div>
</div>

<script>
function generateReport() {
    const date = document.getElementById('reportDate').value;
    window.location.href = `?date=${date}`;
}
function exportToPDF() { window.print(); }
function exportToExcel() {
    window.location.href = `{{ route("balance-sheet.export") }}?date={{ \Carbon\Carbon::parse($reportDate ?? now())->toDateString() }}`;
}
</script>
@endsection
