<?php $page = 'balance-sheet'; ?>
@extends('layout.mainlayout')

@section('content')
@php
use Carbon\Carbon;

/* ─────────────────────────────────────────────────────────────────
 *  CURRENCY HELPERS
 * ──────────────────────────────────────────────────────────────── */
$currencyCode   = $geoCurrency       ?? \App\Support\GeoCurrency::currentCurrency();
$currencyLocale = $geoCurrencyLocale ?? \App\Support\GeoCurrency::currentLocale();
$fmt = fn (float|int $v) => \App\Support\GeoCurrency::format((float) $v, 'NGN', $currencyCode, $currencyLocale);

/* ─────────────────────────────────────────────────────────────────
 *  REPORT META
 * ──────────────────────────────────────────────────────────────── */
$reportCompany   = auth()->user()?->company;
$companyName     = $reportCompany?->company_name
                ?? $reportCompany?->name
                ?? \App\Models\Setting::where('key', 'company_name')->value('value')
                ?? 'SmartProbook';
$activeBranchName = trim((string) ($activeBranch['name'] ?? ''));
$asOfDate        = Carbon::parse($reportDate ?? now());
$asOfStr         = $asOfDate->toDateString();

/* ─────────────────────────────────────────────────────────────────
 *  DATE FILTER PRESETS
 * ──────────────────────────────────────────────────────────────── */
$presets = [
    'today'        => ['label' => 'Today',             'date' => now()->toDateString()],
    'this_month'   => ['label' => 'This Month',        'date' => now()->endOfMonth()->toDateString()],
    'this_quarter' => ['label' => 'This Quarter',      'date' => now()->endOfQuarter()->toDateString()],
    'this_year'    => ['label' => 'This Year to Date', 'date' => now()->toDateString()],
    'last_month'   => ['label' => 'Last Month',        'date' => now()->subMonthNoOverflow()->endOfMonth()->toDateString()],
    'last_quarter' => ['label' => 'Last Quarter',      'date' => now()->subQuarter()->endOfQuarter()->toDateString()],
    'last_year'    => ['label' => 'Last Year',         'date' => now()->subYear()->endOfYear()->toDateString()],
    'custom'       => ['label' => 'Custom',            'date' => $asOfStr],
];
$activePreset = 'custom';
foreach ($presets as $key => $p) {
    if ($key !== 'custom' && $p['date'] === $asOfStr) { $activePreset = $key; break; }
}

/* ─────────────────────────────────────────────────────────────────
 *  SYSTEM / INTERNAL ACCOUNT FILTER
 *  These accounts are created for internal bookkeeping only and
 *  must NOT appear on a published financial statement.
 * ──────────────────────────────────────────────────────────────── */
$systemCodes = ['SYS-BS-RECON', 'SYS-OPENING-EQUITY', 'SYS-CUST-AR', 'SYS-SUPP-AP', 'SYS-INV'];

$isSystemAccount = function ($account) use ($systemCodes): bool {
    $name = strtolower(trim((string) ($account->name ?? '')));
    $code = strtoupper(trim((string) ($account->code ?? '')));
    if (in_array($code, $systemCodes, true)) return true;
    $patterns = [
        'opening balance equity',
        'balance sheet reconciliation',
        'bank reconciliation suspense',
        'reconciliation reserve',
        'reconciliation suspense',
    ];
    foreach ($patterns as $p) {
        if (str_contains($name, $p)) return true;
    }
    return false;
};

/* ─────────────────────────────────────────────────────────────────
 *  BANK OVERDRAFT RECLASSIFICATION
 *  A bank/cash account with a negative balance is an overdraft —
 *  it belongs under Current Liabilities, not under Assets.
 * ──────────────────────────────────────────────────────────────── */
$isBankOrCash = function ($account): bool {
    $name    = strtolower(trim((string) ($account->name    ?? '')));
    $subType = strtolower(trim((string) ($account->sub_type ?? '')));
    return str_contains($name, 'bank')
        || str_contains($name, 'cash')
        || str_contains($name, 'petty')
        || str_contains($subType, 'bank')
        || str_contains($subType, 'cash');
};

$overdraftLines = collect();

/* ─────────────────────────────────────────────────────────────────
 *  PROCESS CURRENT ASSETS
 * ──────────────────────────────────────────────────────────────── */
$processedCurrentAssets = collect($currentAssets ?? [])
    ->reject(fn ($a) => $isSystemAccount($a))
    ->filter(function ($account) use ($isBankOrCash, &$overdraftLines) {
        $bal = (float) ($account->balance ?? 0);
        if ($isBankOrCash($account) && $bal < -0.005) {
            $od            = (object) (array) $account;
            $od->balance   = abs($bal);
            $od->_overdraft = true;
            $overdraftLines->push($od);
            return false;
        }
        return true;
    })
    ->values();

/* ─────────────────────────────────────────────────────────────────
 *  PROCESS FIXED ASSETS
 * ──────────────────────────────────────────────────────────────── */
$processedFixedAssets = collect($fixedAssets ?? [])
    ->reject(fn ($a) => $isSystemAccount($a))
    ->values();

/* ─────────────────────────────────────────────────────────────────
 *  SPLIT LIABILITIES → CURRENT vs LONG-TERM
 * ──────────────────────────────────────────────────────────────── */
$isLongTermLiability = function ($account): bool {
    $subType = strtolower(trim((string) ($account->sub_type ?? '')));
    $name    = strtolower(trim((string) ($account->name    ?? '')));
    return str_contains($subType, 'long')
        || str_contains($subType, 'non-current')
        || str_contains($subType, 'non current')
        || str_contains($subType, 'term loan')
        || str_contains($subType, 'mortgage')
        || str_contains($subType, 'deferred')
        || str_contains($name, 'long-term')
        || str_contains($name, 'long term')
        || str_contains($name, 'deferred revenue')
        || str_contains($name, 'mortgage');
};

$allLiabilities         = collect($currentLiabilities ?? [])->reject(fn ($a) => $isSystemAccount($a));
$currentLiabilityLines  = $allLiabilities->reject($isLongTermLiability)->values();
$longTermLiabilityLines = $allLiabilities->filter($isLongTermLiability)->values();

if ($overdraftLines->isNotEmpty()) {
    $currentLiabilityLines = $currentLiabilityLines->concat($overdraftLines);
}

/* ─────────────────────────────────────────────────────────────────
 *  PROCESS EQUITY
 * ──────────────────────────────────────────────────────────────── */
$visibleEquity    = collect($equity ?? [])->reject(fn ($a) => $isSystemAccount($a))->values();
$displayNetIncome = (float) ($netIncome ?? $retainedEarnings ?? 0);

/* ─────────────────────────────────────────────────────────────────
 *  DISPLAY TOTALS  (after reclassifications and system-account removal)
 * ──────────────────────────────────────────────────────────────── */
$visTotalCurrentAssets  = $processedCurrentAssets->sum(fn ($a) => (float) ($a->balance ?? 0));
$visTotalFixedAssets    = $processedFixedAssets->sum(fn ($a) => (float) ($a->balance ?? 0));
$visTotalAssets         = $visTotalCurrentAssets + $visTotalFixedAssets;

$visTotalCurrentLiab    = $currentLiabilityLines->sum(fn ($a) => (float) ($a->balance ?? 0));
$visTotalLongTermLiab   = $longTermLiabilityLines->sum(fn ($a) => (float) ($a->balance ?? 0));
$visTotalLiabilities    = $visTotalCurrentLiab + $visTotalLongTermLiab;

$visTotalEquityAccounts = $visibleEquity->sum(fn ($a) => (float) ($a->balance ?? 0));
$visTotalEquity         = $visTotalEquityAccounts + $displayNetIncome;

$visTotalLiabEquity     = $visTotalLiabilities + $visTotalEquity;
$equationDiff           = round($visTotalAssets - $visTotalLiabEquity, 2);
$isBalanced             = abs($equationDiff) < 0.01;

/* ─────────────────────────────────────────────────────────────────
 *  GROUPING HELPER  (groups accounts by sub_type within a section)
 * ──────────────────────────────────────────────────────────────── */
$groupAccounts = function ($items, string $fallback) {
    return collect($items)
        ->groupBy(function ($item) use ($fallback) {
            $sub = trim((string) ($item->sub_type ?? ''));
            return $sub !== '' ? $sub : $fallback;
        })
        ->map(fn ($group, $label) => [
            'label' => (string) $label,
            'items' => collect($group)->values(),
            'total' => collect($group)->sum(fn ($a) => (float) ($a->balance ?? 0)),
        ])
        ->values();
};
@endphp

{{-- ══════════════════════════════════════════════════════════════
     STYLES
══════════════════════════════════════════════════════════════════ --}}
<style>
/* ── Filter bar ──────────────────────────────────────────── */
.bs-filter-bar {
    background: #fff;
    border: 1px solid #e2e8f0;
    border-radius: 10px;
    padding: 16px 22px;
    margin-bottom: 20px;
    display: flex;
    flex-wrap: wrap;
    gap: 16px;
    align-items: flex-end;
    box-shadow: 0 1px 4px rgba(15,23,42,.05);
}
.bs-filter-group { display: flex; flex-direction: column; gap: 5px; }
.bs-filter-label {
    font-size: 0.70rem;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.06em;
    color: #64748b;
}
.bs-filter-bar select,
.bs-filter-bar input[type=date] {
    border: 1px solid #cbd5e1;
    border-radius: 7px;
    padding: 8px 11px;
    font-size: 0.855rem;
    color: #1e293b;
    background: #f8fafc;
    min-width: 150px;
    outline: none;
    transition: border-color .15s;
}
.bs-filter-bar select:focus,
.bs-filter-bar input[type=date]:focus {
    border-color: #6366f1;
    background: #fff;
    box-shadow: 0 0 0 3px rgba(99,102,241,.12);
}
.bs-method-toggle {
    display: flex;
    border: 1px solid #cbd5e1;
    border-radius: 7px;
    overflow: hidden;
}
.bs-method-toggle a {
    padding: 8px 18px;
    font-size: 0.825rem;
    font-weight: 600;
    color: #475569;
    text-decoration: none;
    background: #f8fafc;
}
.bs-method-toggle a.active { background: #6366f1; color: #fff; }
.bs-filter-actions { align-self: flex-end; }
.bs-btn-run {
    background: #1e40af;
    color: #fff;
    border: none;
    border-radius: 7px;
    padding: 9px 24px;
    font-size: 0.845rem;
    font-weight: 700;
    cursor: pointer;
    letter-spacing: 0.02em;
}
.bs-btn-run:hover { background: #1d3fa0; }

/* ── Toolbar ─────────────────────────────────────────────── */
.bs-toolbar { display: flex; justify-content: flex-end; gap: 8px; margin-bottom: 14px; }
.bs-action-btn {
    border: 1px solid #e2e8f0;
    background: #fff;
    color: #334155;
    border-radius: 7px;
    padding: 7px 16px;
    font-size: 0.78rem;
    font-weight: 600;
    cursor: pointer;
    display: inline-flex;
    align-items: center;
    gap: 5px;
}
.bs-action-btn:hover { border-color: #94a3b8; background: #f8fafc; }
.bs-action-btn svg { width: 14px; height: 14px; }

/* ── Report wrapper ──────────────────────────────────────── */
.bs-page  { max-width: 940px; margin: 0 auto 30px; }
.bs-sheet {
    background: #fff;
    border: 1px solid #e2e8f0;
    border-radius: 10px;
    padding: 36px 44px 32px;
    box-shadow: 0 4px 24px rgba(15,23,42,.07);
}

/* ── Report header ───────────────────────────────────────── */
.bs-header {
    text-align: center;
    padding-bottom: 22px;
    border-bottom: 2px solid #1e293b;
    margin-bottom: 8px;
}
.bs-company-name {
    font-size: 1.05rem;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.05em;
    color: #111827;
    margin: 0 0 2px;
}
.bs-report-title {
    font-size: 1.55rem;
    font-weight: 700;
    color: #0f172a;
    margin: 0 0 6px;
}
.bs-report-date   { font-size: 0.92rem; color: #475569; margin: 0; }
.bs-report-branch { font-size: 0.84rem; color: #64748b; margin-top: 3px; }

/* ── Main table ──────────────────────────────────────────── */
.bs-table {
    width: 100%;
    border-collapse: collapse;
    font-size: 0.905rem;
    color: #1e293b;
    margin-top: 4px;
}
.bs-table col.col-label  { width: 70%; }
.bs-table col.col-amount { width: 30%; }
.bs-table td { padding: 0; vertical-align: middle; }

/* Column header */
.bs-col-head td {
    padding: 8px 0;
    font-size: 0.72rem;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.07em;
    color: #64748b;
    border-bottom: 1.5px solid #cbd5e1;
}
.bs-col-head td:last-child { text-align: right; padding-right: 2px; }

/* ASSETS / LIABILITIES / EQUITY label */
.bs-section-head td {
    padding: 20px 0 5px;
    font-size: 0.80rem;
    font-weight: 800;
    text-transform: uppercase;
    letter-spacing: 0.10em;
    color: #0f172a;
}

/* Sub-section (e.g. "Current Assets") */
.bs-sub-head td {
    padding: 8px 0 4px 12px;
    font-size: 0.88rem;
    font-weight: 700;
    color: #1e293b;
}

/* Sub-group header (e.g. "Accounts Receivable" parent grouping) */
.bs-group-head td:first-child {
    padding: 5px 0 3px 24px;
    font-size: 0.875rem;
    font-weight: 600;
    color: #334155;
}

/* Individual account line */
.bs-line td:first-child          { padding: 4px 0 4px 24px; font-size: 0.89rem; color: #374151; }
.bs-line-indented td:first-child { padding-left: 40px; }

/* Amount column */
.bs-amt {
    text-align: right;
    font-variant-numeric: tabular-nums;
    white-space: nowrap;
    padding-right: 2px;
    color: #1e293b;
}
.bs-amt-neg { color: #dc2626; }

/* Sub-total (e.g. "Total Current Assets") */
.bs-sub-total td {
    padding: 6px 0 6px 24px;
    font-weight: 700;
    font-size: 0.895rem;
    color: #1e293b;
    border-top: 1px solid #e2e8f0;
    background: #f8fafc;
}
.bs-sub-total td:last-child { padding-right: 2px; }

/* Section total (e.g. "Total Assets") */
.bs-section-total td {
    padding: 8px 0;
    font-size: 0.94rem;
    font-weight: 800;
    color: #0f172a;
    border-top: 2px solid #334155;
    border-bottom: 1px solid #334155;
}
.bs-section-total td:last-child { padding-right: 2px; }

/* Grand total ("Total Liabilities & Equity") */
.bs-grand-total td {
    padding: 10px 0;
    font-size: 0.97rem;
    font-weight: 800;
    color: #111827;
    border-top: 3px double #111827;
    border-bottom: 2px solid #111827;
}
.bs-grand-total td:last-child { padding-right: 2px; }

.bs-spacer td { padding: 5px 0; }

/* Overdraft badge */
.bs-overdraft-tag {
    display: inline-block;
    background: #fff7ed;
    color: #c2410c;
    border: 1px solid #fed7aa;
    border-radius: 4px;
    font-size: 0.66rem;
    font-weight: 700;
    padding: 1px 5px;
    vertical-align: middle;
    margin-left: 5px;
    text-transform: uppercase;
    letter-spacing: 0.04em;
}

/* Accounting equation result */
.bs-balanced {
    display: inline-flex;
    align-items: center;
    gap: 7px;
    background: #f0fdf4;
    border: 1px solid #bbf7d0;
    color: #15803d;
    padding: 9px 18px;
    border-radius: 8px;
    font-size: 0.83rem;
    font-weight: 700;
    margin-top: 20px;
}
.bs-imbalance {
    margin-top: 20px;
    padding: 14px 18px;
    background: #fef2f2;
    border: 1px solid #fecaca;
    border-radius: 8px;
    color: #991b1b;
    font-size: 0.84rem;
}
.bs-imbalance strong { display: block; margin-bottom: 6px; font-size: 0.90rem; }
.bs-recon-rows { width: 100%; margin-top: 8px; border-collapse: collapse; font-size: 0.82rem; }
.bs-recon-rows td { padding: 3px 8px 3px 0; }
.bs-recon-rows td:last-child {
    text-align: right;
    font-variant-numeric: tabular-nums;
    font-weight: 600;
}

/* Responsive */
@media (max-width: 767.98px) {
    .bs-sheet { padding: 20px 14px; }
    .bs-filter-bar { flex-direction: column; }
    .bs-page { margin: 0 0 20px; }
}

/* Print */
@media print {
    .no-print { display: none !important; }
    .bs-page  { max-width: none; margin: 0; }
    .bs-sheet { box-shadow: none; border: none; border-radius: 0; padding: 8px 0; }
    .bs-header { border-bottom-color: #000; }
    .bs-section-total td { border-top-color: #000; border-bottom-color: #000; }
    .bs-grand-total td   { border-top-color: #000; border-bottom-color: #000; }
    .bs-col-head td { border-bottom-color: #000; }
}
</style>

<div class="page-wrapper">
<div class="content container-fluid">

    <div class="page-header no-print">
        <div class="content-page-header"><h5>Balance Sheet</h5></div>
    </div>

    @include('Reports.partials.context-strip', [
        'reportLabel' => 'Balance Sheet',
        'periodLabel' => 'As at ' . $asOfDate->format('d M Y'),
    ])

    {{-- ─── Filter Bar ──────────────────────────────────────────── --}}
    <form method="GET" action="{{ route('balance-sheet') }}" class="bs-filter-bar no-print" id="bsFilterForm">
        <div class="bs-filter-group">
            <span class="bs-filter-label">Report Period</span>
            <select id="bsPreset" onchange="bsApplyPreset(this.value)">
                @foreach($presets as $key => $p)
                    <option value="{{ $key }}" data-date="{{ $p['date'] }}"
                        {{ $activePreset === $key ? 'selected' : '' }}>
                        {{ $p['label'] }}
                    </option>
                @endforeach
            </select>
        </div>
        <div class="bs-filter-group">
            <span class="bs-filter-label">As of Date</span>
            <input type="date" name="date" id="bsDate" value="{{ $asOfStr }}"
                   onchange="document.getElementById('bsPreset').value='custom'">
        </div>
        <div class="bs-filter-group">
            <span class="bs-filter-label">Accounting Method</span>
            <div class="bs-method-toggle">
                <a href="#" class="active" onclick="return false;">Accrual</a>
                <a href="#" onclick="return false;">Cash</a>
            </div>
        </div>
        <div class="bs-filter-actions">
            <button type="submit" class="bs-btn-run">Run Report</button>
        </div>
    </form>

    {{-- ─── Report Page ─────────────────────────────────────────── --}}
    <div class="bs-page">

        <div class="bs-toolbar no-print">
            <button type="button" class="bs-action-btn" onclick="window.print()">
                <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path d="M6 9V2h12v7M6 18H4a2 2 0 01-2-2v-5a2 2 0 012-2h16a2 2 0 012 2v5a2 2 0 01-2 2h-2m-8 0v4h8v-4H8z"/>
                </svg>
                Print
            </button>
            <button type="button" class="bs-action-btn" onclick="window.print()">
                <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path d="M14 2H6a2 2 0 00-2 2v16a2 2 0 002 2h12a2 2 0 002-2V8l-6-6z"/>
                    <path d="M14 2v6h6"/>
                </svg>
                PDF
            </button>
            <button type="button" class="bs-action-btn" onclick="bsExportExcel()">
                <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <rect x="3" y="3" width="18" height="18" rx="2"/>
                    <path d="M8 12h8M12 8v8"/>
                </svg>
                Excel
            </button>
        </div>

        <div class="bs-sheet">

            <div class="bs-header">
                <p class="bs-company-name">{{ $companyName }}</p>
                <h1 class="bs-report-title">Balance Sheet</h1>
                <p class="bs-report-date">As of {{ $asOfDate->format('F j, Y') }}</p>
                @if($activeBranchName !== '')
                    <p class="bs-report-branch">Branch: {{ $activeBranchName }}</p>
                @endif
            </div>

            <table class="bs-table">
                <colgroup>
                    <col class="col-label">
                    <col class="col-amount">
                </colgroup>
                <tbody>

                    <tr class="bs-col-head">
                        <td></td>
                        <td>TOTAL</td>
                    </tr>

                    {{-- ════════════════════════════════════════
                         ASSETS
                    ════════════════════════════════════════════ --}}
                    <tr class="bs-section-head"><td colspan="2">Assets</td></tr>

                    {{-- Current Assets --}}
                    @if($processedCurrentAssets->isNotEmpty())
                        <tr class="bs-sub-head"><td colspan="2">Current Assets</td></tr>
                        @php $caGroups = $groupAccounts($processedCurrentAssets, 'Other Current Assets'); @endphp
                        @foreach($caGroups as $group)
                            @if($caGroups->count() > 1)
                                <tr class="bs-group-head"><td>{{ $group['label'] }}</td><td></td></tr>
                            @endif
                            @foreach($group['items'] as $account)
                                <tr class="{{ $caGroups->count() > 1 ? 'bs-line bs-line-indented' : 'bs-line' }}">
                                    <td>{{ $account->name }}</td>
                                    <td class="bs-amt {{ (float)($account->balance ?? 0) < 0 ? 'bs-amt-neg' : '' }}">
                                        {{ $fmt((float)($account->balance ?? 0)) }}
                                    </td>
                                </tr>
                            @endforeach
                            @if($caGroups->count() > 1)
                                <tr class="bs-sub-total">
                                    <td>Total {{ $group['label'] }}</td>
                                    <td class="bs-amt">{{ $fmt($group['total']) }}</td>
                                </tr>
                            @endif
                        @endforeach
                        <tr class="bs-sub-total">
                            <td>Total Current Assets</td>
                            <td class="bs-amt">{{ $fmt($visTotalCurrentAssets) }}</td>
                        </tr>
                    @endif

                    {{-- Fixed / Non-Current Assets --}}
                    @if($processedFixedAssets->isNotEmpty())
                        <tr class="bs-spacer"><td colspan="2"></td></tr>
                        <tr class="bs-sub-head"><td colspan="2">Fixed Assets</td></tr>
                        @php $faGroups = $groupAccounts($processedFixedAssets, 'Fixed Assets'); @endphp
                        @foreach($faGroups as $group)
                            @if($faGroups->count() > 1)
                                <tr class="bs-group-head"><td>{{ $group['label'] }}</td><td></td></tr>
                            @endif
                            @foreach($group['items'] as $account)
                                <tr class="{{ $faGroups->count() > 1 ? 'bs-line bs-line-indented' : 'bs-line' }}">
                                    <td>{{ $account->name }}</td>
                                    <td class="bs-amt {{ (float)($account->balance ?? 0) < 0 ? 'bs-amt-neg' : '' }}">
                                        {{ $fmt((float)($account->balance ?? 0)) }}
                                    </td>
                                </tr>
                            @endforeach
                            @if($faGroups->count() > 1)
                                <tr class="bs-sub-total">
                                    <td>Total {{ $group['label'] }}</td>
                                    <td class="bs-amt">{{ $fmt($group['total']) }}</td>
                                </tr>
                            @endif
                        @endforeach
                        <tr class="bs-sub-total">
                            <td>Total Fixed Assets</td>
                            <td class="bs-amt">{{ $fmt($visTotalFixedAssets) }}</td>
                        </tr>
                    @endif

                    {{-- Total Assets --}}
                    <tr class="bs-spacer"><td colspan="2"></td></tr>
                    <tr class="bs-section-total">
                        <td>Total Assets</td>
                        <td class="bs-amt">{{ $fmt($visTotalAssets) }}</td>
                    </tr>

                    <tr class="bs-spacer"><td colspan="2"></td></tr>
                    <tr class="bs-spacer"><td colspan="2"></td></tr>

                    {{-- ════════════════════════════════════════
                         LIABILITIES
                    ════════════════════════════════════════════ --}}
                    <tr class="bs-section-head"><td colspan="2">Liabilities</td></tr>

                    {{-- Current Liabilities --}}
                    @if($currentLiabilityLines->isNotEmpty())
                        <tr class="bs-sub-head"><td colspan="2">Current Liabilities</td></tr>
                        @foreach($currentLiabilityLines as $account)
                            <tr class="bs-line">
                                <td>
                                    {{ $account->name }}
                                    @if(!empty($account->_overdraft))
                                        <span class="bs-overdraft-tag">Overdraft</span>
                                    @endif
                                </td>
                                <td class="bs-amt">{{ $fmt((float)($account->balance ?? 0)) }}</td>
                            </tr>
                        @endforeach
                        <tr class="bs-sub-total">
                            <td>Total Current Liabilities</td>
                            <td class="bs-amt">{{ $fmt($visTotalCurrentLiab) }}</td>
                        </tr>
                    @endif

                    {{-- Long-Term Liabilities --}}
                    @if($longTermLiabilityLines->isNotEmpty())
                        <tr class="bs-spacer"><td colspan="2"></td></tr>
                        <tr class="bs-sub-head"><td colspan="2">Long-Term Liabilities</td></tr>
                        @foreach($longTermLiabilityLines as $account)
                            <tr class="bs-line">
                                <td>{{ $account->name }}</td>
                                <td class="bs-amt">{{ $fmt((float)($account->balance ?? 0)) }}</td>
                            </tr>
                        @endforeach
                        <tr class="bs-sub-total">
                            <td>Total Long-Term Liabilities</td>
                            <td class="bs-amt">{{ $fmt($visTotalLongTermLiab) }}</td>
                        </tr>
                    @endif

                    {{-- Total Liabilities --}}
                    <tr class="bs-spacer"><td colspan="2"></td></tr>
                    <tr class="bs-section-total">
                        <td>Total Liabilities</td>
                        <td class="bs-amt">{{ $fmt($visTotalLiabilities) }}</td>
                    </tr>

                    <tr class="bs-spacer"><td colspan="2"></td></tr>
                    <tr class="bs-spacer"><td colspan="2"></td></tr>

                    {{-- ════════════════════════════════════════
                         EQUITY
                    ════════════════════════════════════════════ --}}
                    <tr class="bs-section-head"><td colspan="2">Equity</td></tr>

                    @foreach($visibleEquity as $account)
                        <tr class="bs-line">
                            <td>{{ $account->name }}</td>
                            <td class="bs-amt {{ (float)($account->balance ?? 0) < 0 ? 'bs-amt-neg' : '' }}">
                                {{ $fmt((float)($account->balance ?? 0)) }}
                            </td>
                        </tr>
                    @endforeach

                    <tr class="bs-line">
                        <td>Retained Earnings (Net Income)</td>
                        <td class="bs-amt {{ $displayNetIncome < 0 ? 'bs-amt-neg' : '' }}">
                            {{ $fmt($displayNetIncome) }}
                        </td>
                    </tr>

                    <tr class="bs-sub-total">
                        <td>Total Equity</td>
                        <td class="bs-amt {{ $visTotalEquity < 0 ? 'bs-amt-neg' : '' }}">
                            {{ $fmt($visTotalEquity) }}
                        </td>
                    </tr>

                    <tr class="bs-spacer"><td colspan="2"></td></tr>

                    {{-- ════════════════════════════════════════
                         GRAND TOTAL
                    ════════════════════════════════════════════ --}}
                    <tr class="bs-grand-total">
                        <td>Total Liabilities &amp; Equity</td>
                        <td class="bs-amt">{{ $fmt($visTotalLiabEquity) }}</td>
                    </tr>

                </tbody>
            </table>

            {{-- Accounting equation validation --}}
            @if($isBalanced)
                <div class="bs-balanced">
                    <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                        <path d="M5 13l4 4L19 7"/>
                    </svg>
                    Statement in balance &mdash; Assets = Liabilities + Equity
                </div>
            @else
                <div class="bs-imbalance">
                    <strong>&#9888;&nbsp; Accounting Equation Imbalance Detected</strong>
                    The balance sheet does not balance. Please review your chart of accounts and journal entries.
                    <table class="bs-recon-rows">
                        <tr><td>Total Assets</td><td>{{ $fmt($visTotalAssets) }}</td></tr>
                        <tr><td>Total Liabilities + Equity</td><td>{{ $fmt($visTotalLiabEquity) }}</td></tr>
                        <tr><td><strong>Difference</strong></td><td><strong>{{ $fmt(abs($equationDiff)) }}</strong></td></tr>
                    </table>
                    @if(isset($ledgerDifference) && abs((float)$ledgerDifference) >= 0.01)
                        <div style="margin-top:8px;font-size:0.80rem;color:#7f1d1d;">
                            Ledger &mdash;
                            Debits: {{ $fmt($ledgerDebits ?? 0) }}&nbsp;|&nbsp;
                            Credits: {{ $fmt($ledgerCredits ?? 0) }}&nbsp;|&nbsp;
                            Difference: {{ $fmt(abs((float)$ledgerDifference)) }}
                        </div>
                    @endif
                </div>
            @endif

        </div>{{-- /.bs-sheet --}}
    </div>{{-- /.bs-page --}}

</div>
</div>

<script>
const bsPresets = @json(collect($presets)->map(fn($p) => $p['date']));
function bsApplyPreset(key) {
    if (key !== 'custom' && bsPresets[key]) {
        document.getElementById('bsDate').value = bsPresets[key];
    }
}
function bsExportExcel() {
    window.location.href = '{{ route("balance-sheet.export") }}?date={{ $asOfStr }}';
}
</script>
@endsection
