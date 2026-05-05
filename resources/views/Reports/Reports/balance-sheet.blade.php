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
$accountingMethod = $method ?? 'accrual';
$activeCompareTo  = $compareTo ?? 'none';

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

// Consolidation state (passed from controller when all-branches mode)
$consolidate   = (bool) ($consolidate   ?? false);
$isAllBranches = (bool) ($isAllBranches ?? (($activeBranch['scope'] ?? '') === 'all'));

/* ─────────────────────────────────────────────────────────────────
 *  DISPLAY COLLECTIONS
 * ──────────────────────────────────────────────────────────────── */
$processedCurrentAssets = collect($currentAssets ?? [])
    ->reject(fn ($a) => abs((float)($a->balance ?? 0)) < 0.005)
    ->values();
$processedFixedAssets = collect($fixedAssets ?? [])
    ->reject(fn ($a) => abs((float)($a->balance ?? 0)) < 0.005)
    ->values();
$currentLiabilityLines  = collect($currentLiabilities ?? [])->reject(fn ($a) => abs((float)($a->balance ?? 0)) < 0.005)->values();
$longTermLiabilityLines = collect($longTermLiabilities ?? [])->reject(fn ($a) => abs((float)($a->balance ?? 0)) < 0.005)->values();
$equityCapitalLines     = collect($equityCapital ?? [])->reject(fn ($a) => abs((float)($a->balance ?? 0)) < 0.005)->values();
$equityRetainedLines    = collect($equityRetained ?? [])->reject(fn ($a) => abs((float)($a->balance ?? 0)) < 0.005)->values();
$equityReserveLines     = collect($equityReserves ?? [])->reject(fn ($a) => abs((float)($a->balance ?? 0)) < 0.005)->values();
$retainedEarningsLines  = collect($retainedEarningsLines ?? [])->reject(fn ($a) => abs((float)($a->balance ?? 0)) < 0.005)->values();

/* ─────────────────────────────────────────────────────────────────
 *  DISPLAY TOTALS  (after reclassifications and system-account removal)
 * ──────────────────────────────────────────────────────────────── */
$visTotalCurrentAssets  = $processedCurrentAssets->sum(fn ($a) => (float) ($a->balance ?? 0));
$visTotalFixedAssets    = $processedFixedAssets->sum(fn ($a) => (float) ($a->balance ?? 0));
$visTotalAssets         = $visTotalCurrentAssets + $visTotalFixedAssets;

$visTotalCurrentLiab    = $currentLiabilityLines->sum(fn ($a) => (float) ($a->balance ?? 0));
$visTotalLongTermLiab   = $longTermLiabilityLines->sum(fn ($a) => (float) ($a->balance ?? 0));
$visTotalLiabilities    = $visTotalCurrentLiab + $visTotalLongTermLiab;

$visTotalEquityCapital  = $equityCapitalLines->sum(fn ($a) => (float) ($a->balance ?? 0));
$visTotalEquityRetained = $equityRetainedLines->sum(fn ($a) => (float) ($a->balance ?? 0));
$visTotalEquityReserves = $equityReserveLines->sum(fn ($a) => (float) ($a->balance ?? 0));
$visTotalCurrentEarnings = $retainedEarningsLines->sum(fn ($a) => (float) ($a->balance ?? 0));
$visTotalEquity         = $visTotalEquityCapital + $visTotalEquityRetained + $visTotalEquityReserves + $visTotalCurrentEarnings;

$visTotalLiabEquity     = $visTotalLiabilities + $visTotalEquity;
$equationDiff           = round((float) ($statementDifference ?? ($visTotalAssets - $visTotalLiabEquity)), 2);
$balanceTolerance       = (float) ($balanceTolerance ?? 0.005);
$isBalanced             = abs($equationDiff) <= $balanceTolerance;
$showBalanceDiagnostics = (bool) ($showBalanceDiagnostics ?? !$isBalanced);
$showInlineDiagnostics  = $showBalanceDiagnostics || (bool) ($isAdminDiagnosticMode ?? false);

/* ─────────────────────────────────────────────────────────────────
 *  COMPARISON PERIOD DATA
 * ──────────────────────────────────────────────────────────────── */
$hasCmp  = !empty($compareData);
$colCount = $hasCmp ? 4 : 2;

$cmpTotalCurrentAssets = 0.0;
$cmpTotalFixedAssets   = 0.0;
$cmpTotalAssets        = 0.0;
$cmpTotalCurrentLiab   = 0.0;
$cmpTotalLongTermLiab  = 0.0;
$cmpTotalLiabilities   = 0.0;
$cmpTotalEquity        = 0.0;
$cmpTotalLiabEquity    = 0.0;
$cmpLookup             = collect();

if ($hasCmp) {
    $cmpCurrentAssetsVis = collect($compareData['currentAssets'] ?? [])->values();
    $cmpFixedAssetsVis = collect($compareData['fixedAssets'] ?? [])->values();
    $cmpCurrentLiabVis = collect($compareData['currentLiabilities'] ?? [])->values();
    $cmpLongTermLiabVis = collect($compareData['longTermLiabilities'] ?? [])->values();
    $cmpEquityCapitalVis = collect($compareData['equityCapital'] ?? [])->values();
    $cmpEquityRetainedVis = collect($compareData['equityRetained'] ?? [])->values();
    $cmpEquityReserveVis = collect($compareData['equityReserves'] ?? [])->values();
    $cmpRetainedEarningsVis = collect($compareData['retainedEarningsLines'] ?? [])->values();

    $cmpTotalCurrentAssets = $cmpCurrentAssetsVis->sum(fn ($a) => (float) ($a->balance ?? 0));
    $cmpTotalFixedAssets   = $cmpFixedAssetsVis->sum(fn ($a) => (float) ($a->balance ?? 0));
    $cmpTotalAssets        = $cmpTotalCurrentAssets + $cmpTotalFixedAssets;
    $cmpTotalCurrentLiab   = $cmpCurrentLiabVis->sum(fn ($a) => (float) ($a->balance ?? 0));
    $cmpTotalLongTermLiab  = $cmpLongTermLiabVis->sum(fn ($a) => (float) ($a->balance ?? 0));
    $cmpTotalLiabilities   = $cmpTotalCurrentLiab + $cmpTotalLongTermLiab;
    $cmpTotalEquity        = $cmpEquityCapitalVis->sum(fn ($a) => (float) ($a->balance ?? 0))
                            + $cmpEquityRetainedVis->sum(fn ($a) => (float) ($a->balance ?? 0))
                            + $cmpEquityReserveVis->sum(fn ($a) => (float) ($a->balance ?? 0))
                            + $cmpRetainedEarningsVis->sum(fn ($a) => (float) ($a->balance ?? 0));
    $cmpTotalLiabEquity    = $cmpTotalLiabilities + $cmpTotalEquity;

    // Name-keyed lookup for per-account comparison amounts
    $cmpLookup = collect()
        ->concat($compareData['currentAssets'] ?? [])
        ->concat($compareData['fixedAssets']   ?? [])
        ->concat($compareData['currentLiabilities'] ?? [])
        ->concat($compareData['longTermLiabilities'] ?? [])
        ->concat($compareData['equityCapital'] ?? [])
        ->concat($compareData['equityRetained'] ?? [])
        ->concat($compareData['equityReserves'] ?? [])
        ->concat($compareData['retainedEarningsLines'] ?? [])
        ->keyBy(fn ($a) => strtolower(trim((string) ($a->name ?? ''))));
}

/* ─────────────────────────────────────────────────────────────────
 *  GROUPING HELPER
 * ──────────────────────────────────────────────────────────────── */
$groupAccounts = function ($items, string $fallback) {
    return collect($items)
        ->groupBy(function ($item) use ($fallback) {
            $forced = trim((string) ($item->_bs_group ?? ''));
            if ($forced !== '') {
                return $forced;
            }
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

// Helper: comparison amount for a single account by name
$cmpAmt = fn ($account) => isset($cmpLookup[strtolower(trim((string) ($account->name ?? '')))])
    ? (float) ($cmpLookup[strtolower(trim((string) ($account->name ?? '')))]->balance ?? 0)
    : null;

// Helper: render change column — $ change and optional % for comparison
$changeCell = function (float $current, ?float $compare) use ($hasCmp): string {
    if (!$hasCmp || $compare === null) return '';
    $delta = $current - $compare;
    $cls   = $delta < 0 ? 'bs-amt-neg' : ($delta > 0 ? 'bs-amt-pos' : '');
    $prefix = $delta > 0 ? '+' : '';
    $pct   = '';
    if (abs($compare) >= 0.01) {
        $pctVal = round(($delta / abs($compare)) * 100, 1);
        $pct = ' <span class="bs-change-pct">(' . ($pctVal > 0 ? '+' : '') . $pctVal . '%)</span>';
    }
    return '<td class="bs-chg-amt ' . $cls . '">' . $prefix . number_format($delta, 2) . $pct . '</td>';
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
    user-select: none;
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
.bs-page  { max-width: 960px; margin: 0 auto 30px; }
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
.bs-cash-badge {
    display: inline-block;
    background: #fef9c3;
    color: #854d0e;
    border: 1px solid #fde68a;
    border-radius: 4px;
    font-size: 0.72rem;
    font-weight: 700;
    padding: 2px 7px;
    margin-top: 5px;
    letter-spacing: 0.04em;
    text-transform: uppercase;
}

/* ── Main table ──────────────────────────────────────────── */
.bs-table {
    width: 100%;
    border-collapse: collapse;
    font-size: 0.905rem;
    color: #1e293b;
    margin-top: 4px;
}
.bs-table col.col-label  { width: 60%; }
.bs-table col.col-amount { width: 20%; }
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
.bs-col-head td:not(:first-child) { text-align: right; padding-right: 2px; }
.bs-col-head td.col-cmp { color: #94a3b8; }

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

/* Sub-group header */
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
.bs-amt-neg  { color: #dc2626; }
.bs-amt-dash { color: #94a3b8; text-align: right; padding-right: 2px; font-size: 0.85rem; }

/* Comparison column */
.bs-cmp-amt {
    text-align: right;
    font-variant-numeric: tabular-nums;
    white-space: nowrap;
    padding-right: 2px;
    color: #64748b;
    font-size: 0.875rem;
}
.bs-cmp-amt-neg { color: #f87171; }

/* Change / variance column */
.bs-chg-amt {
    text-align: right;
    font-variant-numeric: tabular-nums;
    white-space: nowrap;
    padding-right: 2px;
    font-size: 0.82rem;
    color: #475569;
    font-weight: 600;
}
.bs-chg-amt.bs-amt-pos { color: #16a34a; }
.bs-chg-amt.bs-amt-neg { color: #dc2626; }
.bs-change-pct {
    font-size: 0.73rem;
    font-weight: 500;
    opacity: 0.80;
}

/* Sub-total */
.bs-sub-total td {
    padding: 6px 0 6px 24px;
    font-weight: 700;
    font-size: 0.895rem;
    color: #1e293b;
    border-top: 1px solid #e2e8f0;
    background: #f8fafc;
}
.bs-sub-total td:not(:first-child) { padding-right: 2px; }

/* Section total */
.bs-section-total td {
    padding: 8px 0;
    font-size: 0.94rem;
    font-weight: 800;
    color: #0f172a;
    border-top: 2px solid #334155;
    border-bottom: 1px solid #334155;
}
.bs-section-total td:not(:first-child) { padding-right: 2px; }

/* Grand total */
.bs-grand-total td {
    padding: 10px 0;
    font-size: 0.97rem;
    font-weight: 800;
    color: #111827;
    border-top: 3px double #111827;
    border-bottom: 2px solid #111827;
}
.bs-grand-total td:not(:first-child) { padding-right: 2px; }

.bs-spacer td { padding: 5px 0; }

/* Deficit / accumulated loss badge */
.bs-deficit-tag {
    display: inline-block;
    background: #fef2f2;
    color: #b91c1c;
    border: 1px solid #fecaca;
    border-radius: 4px;
    font-size: 0.66rem;
    font-weight: 700;
    padding: 1px 5px;
    vertical-align: middle;
    margin-left: 5px;
    text-transform: uppercase;
    letter-spacing: 0.04em;
}

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
/* Vendor credit badge (AP with debit balance reclassified to current assets) */
.bs-vendor-credit-tag {
    display: inline-block;
    background: #f0f9ff;
    color: #0369a1;
    border: 1px solid #bae6fd;
    border-radius: 4px;
    font-size: 0.66rem;
    font-weight: 700;
    padding: 1px 5px;
    vertical-align: middle;
    margin-left: 5px;
    text-transform: uppercase;
    letter-spacing: 0.04em;
}
/* Hidden-equity debug panel (?debug=1) */
.bs-hidden-debug {
    margin-top: 12px;
    padding: 12px 16px;
    background: #fefce8;
    border: 1px solid #fde68a;
    border-radius: 8px;
    font-size: 0.79rem;
    color: #713f12;
}
.bs-hidden-debug summary {
    cursor: pointer;
    font-weight: 700;
    color: #92400e;
}
.bs-hidden-debug table { width: 100%; margin-top: 8px; border-collapse: collapse; }
.bs-hidden-debug td { padding: 3px 6px; border-bottom: 1px solid #fde68a; }
.bs-hidden-debug td:last-child { text-align: right; font-variant-numeric: tabular-nums; }

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
            <input type="hidden" name="accounting_method" id="bsMethod" value="{{ $accountingMethod }}">
            <div class="bs-method-toggle">
                <a href="#" id="bsMethodAccrual"
                   onclick="bsSetMethod('accrual'); return false;"
                   class="{{ $accountingMethod === 'accrual' ? 'active' : '' }}">Accrual</a>
                <a href="#" id="bsMethodCash"
                   onclick="bsSetMethod('cash'); return false;"
                   class="{{ $accountingMethod === 'cash' ? 'active' : '' }}">Cash</a>
            </div>
        </div>
        <div class="bs-filter-group">
            <span class="bs-filter-label">Compare to</span>
            <select name="compare_to">
                <option value="none"            {{ $activeCompareTo === 'none'            ? 'selected' : '' }}>None</option>
                <option value="previous_period" {{ $activeCompareTo === 'previous_period' ? 'selected' : '' }}>Previous Period</option>
                <option value="previous_year"   {{ $activeCompareTo === 'previous_year'   ? 'selected' : '' }}>Previous Year</option>
            </select>
        </div>
        @if(isset($allBranches) && $allBranches->count() > 1)
        <div class="bs-filter-group">
            <span class="bs-filter-label">Branch</span>
            <select name="branch_id" onchange="this.form.submit()">
                <option value="all" {{ ($activeBranch['scope'] ?? '') === 'all' ? 'selected' : '' }}>All Branches</option>
                @foreach($allBranches as $br)
                    <option value="{{ $br['id'] }}" {{ ($activeBranch['id'] ?? '') == $br['id'] ? 'selected' : '' }}>
                        {{ $br['name'] }}
                    </option>
                @endforeach
            </select>
        </div>
        @endif
        @if($isAllBranches)
        <div class="bs-filter-group">
            <span class="bs-filter-label">Account View</span>
            <input type="hidden" name="consolidate" id="bsConsolidate" value="{{ $consolidate ? '1' : '0' }}">
            <div class="bs-method-toggle">
                <a href="#" data-val="0" onclick="bsSetConsolidate(0); return false;"
                   class="{{ !$consolidate ? 'active' : '' }}">By Branch</a>
                <a href="#" data-val="1" onclick="bsSetConsolidate(1); return false;"
                   class="{{ $consolidate ? 'active' : '' }}">Consolidated</a>
            </div>
        </div>
        @endif
        <div class="bs-filter-actions">
            <button type="submit" class="bs-btn-run">Run Report</button>
        </div>
    </form>

    {{-- ─── Report Page ─────────────────────────────────────────── --}}
    <div class="bs-page">

        {{-- Unassigned-branch notice: excluded from the all-branches statement --}}
        @if($showInlineDiagnostics && $isAllBranches && ($unassignedTxnCount ?? 0) > 0)
        <div class="no-print" style="
            background:#fffbeb;
            border:1px solid #fcd34d;
            border-left:4px solid #f59e0b;
            border-radius:6px;
            padding:12px 16px;
            margin-bottom:18px;
            font-size:0.875rem;
            color:#78350f;
            display:flex;
            align-items:flex-start;
            gap:10px;
        ">
            <svg style="flex-shrink:0;margin-top:1px" width="18" height="18" fill="none" stroke="#f59e0b" stroke-width="2" viewBox="0 0 24 24">
                <circle cx="12" cy="12" r="10"/><path d="M12 8v4m0 4h.01"/>
            </svg>
            <div>
                <strong>Unassigned legacy transactions were excluded</strong><br>
                <span style="font-size:0.82rem;">
                    {{ number_format($unassignedTxnCount) }} transaction{{ $unassignedTxnCount == 1 ? '' : 's' }}
                    with <em>no branch assigned</em> were left out of the all-branches statement so
                    this report reflects only branch-owned activity. Review and reassign those
                    legacy entries if they should appear in branch reporting.
                </span>
            </div>
        </div>
        @endif

        <div class="bs-toolbar no-print">
            <button type="button" class="bs-action-btn" onclick="window.print()">
                <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path d="M6 9V2h12v7M6 18H4a2 2 0 01-2-2v-5a2 2 0 012-2h16a2 2 0 012 2v5a2 2 0 01-2 2h-2m-8 0v4h8v-4H8z"/>
                </svg>
                Print / PDF
            </button>
            <button type="button" class="bs-action-btn" onclick="bsExportExcel()">
                <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <rect x="3" y="3" width="18" height="18" rx="2"/>
                    <path d="M8 12h8M12 8v8"/>
                </svg>
                Export Excel
            </button>
        </div>

        <div class="bs-sheet">

            <div class="bs-header">
                <p class="bs-company-name">{{ $companyName }}</p>
                <h1 class="bs-report-title">Balance Sheet</h1>
                <p class="bs-report-date">As of {{ $asOfDate->format('F j, Y') }}</p>
                @if($accountingMethod === 'cash')
                    <span class="bs-cash-badge">Cash Basis</span>
                @endif
                @php
                    $headerBranchLabel = '';
                    if ($isAllBranches) {
                        $headerBranchLabel = $consolidate
                            ? 'Consolidated — All Branches'
                            : 'All Branches — Branch Detail View';
                    } elseif ($activeBranchName !== '') {
                        $headerBranchLabel = 'Branch: ' . $activeBranchName;
                    }
                @endphp
                @if($headerBranchLabel !== '')
                    <p class="bs-report-branch">{{ $headerBranchLabel }}</p>
                @endif
            </div>

            <table class="bs-table">
                <colgroup>
                    <col class="col-label">
                    <col class="col-amount">
                    @if($hasCmp)<col class="col-amount"><col style="width:18%">@endif
                </colgroup>
                <tbody>

                    <tr class="bs-col-head">
                        <td></td>
                        <td>{{ $asOfDate->format('M j, Y') }}</td>
                        @if($hasCmp)
                            <td class="col-cmp">{{ $comparePeriodLabel }}</td>
                            <td class="col-cmp" style="text-align:right;padding-right:2px;">Change ($)</td>
                        @endif
                    </tr>

                    {{-- ════════════════════════════════════════
                         ASSETS
                    ════════════════════════════════════════════ --}}
                    <tr class="bs-section-head"><td colspan="{{ $colCount }}">Assets</td></tr>

                    {{-- Current Assets --}}
                    @if($processedCurrentAssets->isNotEmpty())
                        <tr class="bs-sub-head"><td colspan="{{ $colCount }}">Current Assets</td></tr>
                        @php $caGroups = $groupAccounts($processedCurrentAssets, 'Other Current Assets'); @endphp
                        @foreach($caGroups as $group)
                            @php
                            $trivialCaLabels = ['current assets', 'current asset', 'other current assets', 'current', 'assets', 'asset'];
                            $showCaGroupHead = $caGroups->count() > 1 && !in_array(strtolower(trim($group['label'])), $trivialCaLabels, true);
                            @endphp
                            @if($showCaGroupHead)
                                <tr class="bs-group-head">
                                    <td>{{ $group['label'] }}</td>
                                    <td></td>
                                    @if($hasCmp)<td></td><td></td>@endif
                                </tr>
                            @endif
                            @foreach($group['items'] as $account)
                                @php $cv = $cmpAmt($account); $bal = (float)($account->balance ?? 0); @endphp
                                <tr class="{{ $showCaGroupHead ? 'bs-line bs-line-indented' : 'bs-line' }}">
                                    <td>
                                        {{ !empty($account->_vendor_credit) ? ($account->_display_name ?? 'Supplier Advance') : $account->name }}
                                        @if(!empty($account->_vendor_credit))<span class="bs-vendor-credit-tag">Prepaid</span>@endif
                                    </td>
                                    <td class="bs-amt {{ $bal < 0 ? 'bs-amt-neg' : '' }}">
                                        {{ $fmt($bal) }}
                                    </td>
                                    @if($hasCmp)
                                        @if($cv !== null)
                                            <td class="bs-cmp-amt {{ $cv < 0 ? 'bs-cmp-amt-neg' : '' }}">{{ $fmt($cv) }}</td>
                                        @else
                                            <td class="bs-amt-dash">—</td>
                                        @endif
                                        {!! $changeCell($bal, $cv) !!}
                                    @endif
                                </tr>
                            @endforeach
                            @if($showCaGroupHead)
                                <tr class="bs-sub-total">
                                    <td>Total {{ $group['label'] }}</td>
                                    <td class="bs-amt">{{ $fmt($group['total']) }}</td>
                                    @if($hasCmp)<td></td><td></td>@endif
                                </tr>
                            @endif
                        @endforeach
                        <tr class="bs-sub-total">
                            <td>Total Current Assets</td>
                            <td class="bs-amt">{{ $fmt($visTotalCurrentAssets) }}</td>
                            @if($hasCmp)
                                <td class="bs-cmp-amt">{{ $fmt($cmpTotalCurrentAssets) }}</td>
                                {!! $changeCell($visTotalCurrentAssets, $cmpTotalCurrentAssets) !!}
                            @endif
                        </tr>
                    @endif

                    {{-- Fixed / Non-Current Assets --}}
                    @if($processedFixedAssets->isNotEmpty())
                        <tr class="bs-spacer"><td colspan="{{ $colCount }}"></td></tr>
                        <tr class="bs-sub-head"><td colspan="{{ $colCount }}">Fixed Assets</td></tr>
                        @php $faGroups = $groupAccounts($processedFixedAssets, 'Fixed Assets'); @endphp
                        @foreach($faGroups as $group)
                            @php
                            $trivialFaLabels = ['fixed assets', 'fixed asset', 'non-current assets', 'non-current asset', 'property plant and equipment', 'ppe'];
                            $showFaGroupHead = $faGroups->count() > 1 && !in_array(strtolower(trim($group['label'])), $trivialFaLabels, true);
                            @endphp
                            @if($showFaGroupHead)
                                <tr class="bs-group-head">
                                    <td>{{ $group['label'] }}</td>
                                    <td></td>
                                    @if($hasCmp)<td></td><td></td>@endif
                                </tr>
                            @endif
                            @foreach($group['items'] as $account)
                                @php $cv = $cmpAmt($account); $bal = (float)($account->balance ?? 0); @endphp
                                <tr class="{{ $showFaGroupHead ? 'bs-line bs-line-indented' : 'bs-line' }}">
                                    <td>{{ $account->name }}</td>
                                    <td class="bs-amt {{ $bal < 0 ? 'bs-amt-neg' : '' }}">
                                        {{ $fmt($bal) }}
                                    </td>
                                    @if($hasCmp)
                                        @if($cv !== null)
                                            <td class="bs-cmp-amt {{ $cv < 0 ? 'bs-cmp-amt-neg' : '' }}">{{ $fmt($cv) }}</td>
                                        @else
                                            <td class="bs-amt-dash">—</td>
                                        @endif
                                        {!! $changeCell($bal, $cv) !!}
                                    @endif
                                </tr>
                            @endforeach
                            @if($showFaGroupHead)
                                <tr class="bs-sub-total">
                                    <td>Total {{ $group['label'] }}</td>
                                    <td class="bs-amt">{{ $fmt($group['total']) }}</td>
                                    @if($hasCmp)<td></td><td></td>@endif
                                </tr>
                            @endif
                        @endforeach
                        <tr class="bs-sub-total">
                            <td>Total Fixed Assets</td>
                            <td class="bs-amt">{{ $fmt($visTotalFixedAssets) }}</td>
                            @if($hasCmp)
                                <td class="bs-cmp-amt">{{ $fmt($cmpTotalFixedAssets) }}</td>
                                {!! $changeCell($visTotalFixedAssets, $cmpTotalFixedAssets) !!}
                            @endif
                        </tr>
                    @endif

                    {{-- Total Assets --}}
                    <tr class="bs-spacer"><td colspan="{{ $colCount }}"></td></tr>
                    <tr class="bs-section-total">
                        <td>Total Assets</td>
                        <td class="bs-amt">{{ $fmt($visTotalAssets) }}</td>
                        @if($hasCmp)
                            <td class="bs-cmp-amt">{{ $fmt($cmpTotalAssets) }}</td>
                            {!! $changeCell($visTotalAssets, $cmpTotalAssets) !!}
                        @endif
                    </tr>

                    <tr class="bs-spacer"><td colspan="{{ $colCount }}"></td></tr>
                    <tr class="bs-spacer"><td colspan="{{ $colCount }}"></td></tr>

                    {{-- ════════════════════════════════════════
                         LIABILITIES
                    ════════════════════════════════════════════ --}}
                    <tr class="bs-section-head"><td colspan="{{ $colCount }}">Liabilities</td></tr>

                    {{-- Current Liabilities --}}
                    @if($currentLiabilityLines->isNotEmpty())
                        <tr class="bs-sub-head"><td colspan="{{ $colCount }}">Current Liabilities</td></tr>
                        @foreach($currentLiabilityLines as $account)
                            @php $cv = $cmpAmt($account); $bal = (float)($account->balance ?? 0); @endphp
                            <tr class="bs-line">
                                <td>
                                    {{ $account->name }}
                                    @if(!empty($account->_overdraft))
                                        <span class="bs-overdraft-tag">Overdraft</span>
                                    @endif
                                </td>
                                <td class="bs-amt">{{ $fmt($bal) }}</td>
                                @if($hasCmp)
                                    @if($cv !== null)
                                        <td class="bs-cmp-amt {{ $cv < 0 ? 'bs-cmp-amt-neg' : '' }}">{{ $fmt($cv) }}</td>
                                    @else
                                        <td class="bs-amt-dash">—</td>
                                    @endif
                                    {!! $changeCell($bal, $cv) !!}
                                @endif
                            </tr>
                        @endforeach
                        <tr class="bs-sub-total">
                            <td>Total Current Liabilities</td>
                            <td class="bs-amt">{{ $fmt($visTotalCurrentLiab) }}</td>
                            @if($hasCmp)
                                <td class="bs-cmp-amt">{{ $fmt($cmpTotalCurrentLiab) }}</td>
                                {!! $changeCell($visTotalCurrentLiab, $cmpTotalCurrentLiab) !!}
                            @endif
                        </tr>
                    @endif

                    {{-- Long-Term Liabilities --}}
                    @if($longTermLiabilityLines->isNotEmpty())
                        <tr class="bs-spacer"><td colspan="{{ $colCount }}"></td></tr>
                        <tr class="bs-sub-head"><td colspan="{{ $colCount }}">Long-Term Liabilities</td></tr>
                        @foreach($longTermLiabilityLines as $account)
                            @php $cv = $cmpAmt($account); $bal = (float)($account->balance ?? 0); @endphp
                            <tr class="bs-line">
                                <td>{{ $account->name }}</td>
                                <td class="bs-amt">{{ $fmt($bal) }}</td>
                                @if($hasCmp)
                                    @if($cv !== null)
                                        <td class="bs-cmp-amt {{ $cv < 0 ? 'bs-cmp-amt-neg' : '' }}">{{ $fmt($cv) }}</td>
                                    @else
                                        <td class="bs-amt-dash">—</td>
                                    @endif
                                    {!! $changeCell($bal, $cv) !!}
                                @endif
                            </tr>
                        @endforeach
                        <tr class="bs-sub-total">
                            <td>Total Long-Term Liabilities</td>
                            <td class="bs-amt">{{ $fmt($visTotalLongTermLiab) }}</td>
                            @if($hasCmp)
                                <td class="bs-cmp-amt">{{ $fmt($cmpTotalLongTermLiab) }}</td>
                                {!! $changeCell($visTotalLongTermLiab, $cmpTotalLongTermLiab) !!}
                            @endif
                        </tr>
                    @endif

                    {{-- Total Liabilities --}}
                    <tr class="bs-spacer"><td colspan="{{ $colCount }}"></td></tr>
                    <tr class="bs-section-total">
                        <td>Total Liabilities</td>
                        <td class="bs-amt">{{ $fmt($visTotalLiabilities) }}</td>
                        @if($hasCmp)
                            <td class="bs-cmp-amt">{{ $fmt($cmpTotalLiabilities) }}</td>
                            {!! $changeCell($visTotalLiabilities, $cmpTotalLiabilities) !!}
                        @endif
                    </tr>

                    <tr class="bs-spacer"><td colspan="{{ $colCount }}"></td></tr>
                    <tr class="bs-spacer"><td colspan="{{ $colCount }}"></td></tr>

                    {{-- ════════════════════════════════════════
                         EQUITY
                    ════════════════════════════════════════════ --}}
                    <tr class="bs-section-head"><td colspan="{{ $colCount }}">Equity</td></tr>

                    @if($equityCapitalLines->isNotEmpty())
                        <tr class="bs-sub-head"><td colspan="{{ $colCount }}">Capital</td></tr>
                        @foreach($equityCapitalLines as $account)
                            @php $cv = $cmpAmt($account); $bal = (float)($account->balance ?? 0); @endphp
                            <tr class="bs-line">
                                <td>{{ $account->name }}</td>
                                <td class="bs-amt {{ $bal < 0 ? 'bs-amt-neg' : '' }}">{{ $fmt($bal) }}</td>
                                @if($hasCmp)
                                    @if($cv !== null)
                                        <td class="bs-cmp-amt {{ $cv < 0 ? 'bs-cmp-amt-neg' : '' }}">{{ $fmt($cv) }}</td>
                                    @else
                                        <td class="bs-amt-dash">&mdash;</td>
                                    @endif
                                    {!! $changeCell($bal, $cv) !!}
                                @endif
                            </tr>
                        @endforeach
                        <tr class="bs-sub-total">
                            <td>Total Capital</td>
                            <td class="bs-amt {{ $visTotalEquityCapital < 0 ? 'bs-amt-neg' : '' }}">{{ $fmt($visTotalEquityCapital) }}</td>
                            @if($hasCmp)
                                @php $cmpCapTotal = $cmpEquityCapitalVis->sum(fn ($a) => (float)($a->balance ?? 0)); @endphp
                                <td class="bs-cmp-amt {{ $cmpCapTotal < 0 ? 'bs-cmp-amt-neg' : '' }}">{{ $fmt($cmpCapTotal) }}</td>
                                {!! $changeCell($visTotalEquityCapital, $cmpCapTotal) !!}
                            @endif
                        </tr>
                    @endif

                    @if($equityRetainedLines->isNotEmpty() || $retainedEarningsLines->isNotEmpty())
                        <tr class="bs-spacer"><td colspan="{{ $colCount }}"></td></tr>
                        <tr class="bs-sub-head"><td colspan="{{ $colCount }}">Retained Earnings</td></tr>
                        @foreach($equityRetainedLines as $account)
                            @php $cv = $cmpAmt($account); $bal = (float)($account->balance ?? 0); @endphp
                            <tr class="bs-line">
                                <td>{{ $account->name }}</td>
                                <td class="bs-amt {{ $bal < 0 ? 'bs-amt-neg' : '' }}">{{ $fmt($bal) }}</td>
                                @if($hasCmp)
                                    @if($cv !== null)
                                        <td class="bs-cmp-amt {{ $cv < 0 ? 'bs-cmp-amt-neg' : '' }}">{{ $fmt($cv) }}</td>
                                    @else
                                        <td class="bs-amt-dash">&mdash;</td>
                                    @endif
                                    {!! $changeCell($bal, $cv) !!}
                                @endif
                            </tr>
                        @endforeach
                        @foreach($retainedEarningsLines as $account)
                            @php $cv = $cmpAmt($account); $bal = (float)($account->balance ?? 0); @endphp
                            <tr class="bs-line">
                                <td>
                                    {{ $account->name }}
                                    @if(!empty($account->_deficit))<span class="bs-deficit-tag">Deficit</span>@endif
                                </td>
                                <td class="bs-amt {{ $bal < 0 ? 'bs-amt-neg' : '' }}">{{ $fmt($bal) }}</td>
                                @if($hasCmp)
                                    @if($cv !== null)
                                        <td class="bs-cmp-amt {{ $cv < 0 ? 'bs-cmp-amt-neg' : '' }}">{{ $fmt($cv) }}</td>
                                    @else
                                        <td class="bs-amt-dash">&mdash;</td>
                                    @endif
                                    {!! $changeCell($bal, $cv) !!}
                                @endif
                            </tr>
                        @endforeach
                        @php $visTotalRE = $visTotalEquityRetained + $visTotalCurrentEarnings; @endphp
                        <tr class="bs-sub-total">
                            <td>Total Retained Earnings</td>
                            <td class="bs-amt {{ $visTotalRE < 0 ? 'bs-amt-neg' : '' }}">{{ $fmt($visTotalRE) }}</td>
                            @if($hasCmp)
                                @php $cmpRETotal = $cmpEquityRetainedVis->sum(fn ($a) => (float)($a->balance ?? 0))
                                                + $cmpRetainedEarningsVis->sum(fn ($a) => (float)($a->balance ?? 0)); @endphp
                                <td class="bs-cmp-amt {{ $cmpRETotal < 0 ? 'bs-cmp-amt-neg' : '' }}">{{ $fmt($cmpRETotal) }}</td>
                                {!! $changeCell($visTotalRE, $cmpRETotal) !!}
                            @endif
                        </tr>
                    @endif

                    @if($equityReserveLines->isNotEmpty())
                        <tr class="bs-spacer"><td colspan="{{ $colCount }}"></td></tr>
                        <tr class="bs-sub-head"><td colspan="{{ $colCount }}">Reserves</td></tr>
                        @foreach($equityReserveLines as $account)
                            @php $cv = $cmpAmt($account); $bal = (float)($account->balance ?? 0); @endphp
                            <tr class="bs-line">
                                <td>{{ $account->name }}</td>
                                <td class="bs-amt {{ $bal < 0 ? 'bs-amt-neg' : '' }}">{{ $fmt($bal) }}</td>
                                @if($hasCmp)
                                    @if($cv !== null)
                                        <td class="bs-cmp-amt {{ $cv < 0 ? 'bs-cmp-amt-neg' : '' }}">{{ $fmt($cv) }}</td>
                                    @else
                                        <td class="bs-amt-dash">&mdash;</td>
                                    @endif
                                    {!! $changeCell($bal, $cv) !!}
                                @endif
                            </tr>
                        @endforeach
                        <tr class="bs-sub-total">
                            <td>Total Reserves</td>
                            <td class="bs-amt {{ $visTotalEquityReserves < 0 ? 'bs-amt-neg' : '' }}">{{ $fmt($visTotalEquityReserves) }}</td>
                            @if($hasCmp)
                                @php $cmpResTotal = $cmpEquityReserveVis->sum(fn ($a) => (float)($a->balance ?? 0)); @endphp
                                <td class="bs-cmp-amt {{ $cmpResTotal < 0 ? 'bs-cmp-amt-neg' : '' }}">{{ $fmt($cmpResTotal) }}</td>
                                {!! $changeCell($visTotalEquityReserves, $cmpResTotal) !!}
                            @endif
                        </tr>
                    @endif

                    <tr class="bs-spacer"><td colspan="{{ $colCount }}"></td></tr>
                    <tr class="bs-section-total">
                        <td>Total Equity</td>
                        <td class="bs-amt {{ $visTotalEquity < 0 ? 'bs-amt-neg' : '' }}">
                            {{ $fmt($visTotalEquity) }}
                        </td>
                        @if($hasCmp)
                            <td class="bs-cmp-amt {{ $cmpTotalEquity < 0 ? 'bs-cmp-amt-neg' : '' }}">
                                {{ $fmt($cmpTotalEquity) }}
                            </td>
                            {!! $changeCell($visTotalEquity, $cmpTotalEquity) !!}
                        @endif
                    </tr>

                    <tr class="bs-spacer"><td colspan="{{ $colCount }}"></td></tr>

                    {{-- ════════════════════════════════════════
                         GRAND TOTAL
                    ════════════════════════════════════════════ --}}
                    <tr class="bs-grand-total">
                        <td>Total Liabilities &amp; Equity</td>
                        <td class="bs-amt">{{ $fmt($visTotalLiabEquity) }}</td>
                        @if($hasCmp)
                            <td class="bs-cmp-amt">{{ $fmt($cmpTotalLiabEquity) }}</td>
                            {!! $changeCell($visTotalLiabEquity, $cmpTotalLiabEquity) !!}
                        @endif
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
            @elseif($showInlineDiagnostics)
                <div class="bs-imbalance">
                    <strong>&#9888;&nbsp; Statement Review Required</strong>
                    The accounting equation is currently out of balance. Review the breakdown below and expand the panels to investigate.
                    <table class="bs-recon-rows">
                        <tr><td>Total Assets</td><td>{{ $fmt($visTotalAssets) }}</td></tr>
                        <tr><td>Total Liabilities + Equity</td><td>{{ $fmt($visTotalLiabEquity) }}</td></tr>
                        <tr><td><strong>Statement Gap</strong></td><td><strong style="color:#dc2626;">{{ $fmt(abs($equationDiff)) }}</strong></td></tr>
                    </table>
                    @php
                        $ldDiff = (float) ($ledgerDifference ?? 0);
                        $ldDr   = (float) ($ledgerDebits   ?? 0);
                        $ldCr   = (float) ($ledgerCredits  ?? 0);
                    @endphp
                    <table class="bs-recon-rows" style="margin-top:8px;">
                        <tr>
                            <td colspan="2" style="font-weight:700;color:#374151;padding-bottom:2px;">Ledger Double-Entry Check</td>
                        </tr>
                        <tr><td>Total Debits Posted</td><td>{{ $fmt($ldDr) }}</td></tr>
                        <tr><td>Total Credits Posted</td><td>{{ $fmt($ldCr) }}</td></tr>
                        <tr>
                            <td><strong>Ledger Imbalance</strong></td>
                            <td>
                                @if(abs($ldDiff) < 0.01)
                                    <span style="color:#16a34a;font-weight:700;">Balanced ✓</span>
                                @else
                                    <strong style="color:#dc2626;">{{ $fmt(abs($ldDiff)) }}
                                        ({{ $ldDiff > 0 ? 'excess Debits' : 'excess Credits' }})</strong>
                                @endif
                            </td>
                        </tr>
                    </table>
                    @if(abs($ldDiff) < 0.01 && abs($equationDiff) >= 0.01)
                        <div style="margin-top:8px;font-size:0.82rem;color:#92400e;background:#fef3c7;padding:6px 10px;border-radius:4px;">
                            <strong>Tip:</strong> Your journal entries are balanced (Debits = Credits), but the statement still disagrees.
                            This usually means some accounts have an unrecognised <em>type</em> and are missing from the face of the report.
                            Expand <em>Unclassified Accounts</em> below to see which accounts are affected.
                        </div>
                    @elseif(abs($ldDiff) >= 0.01)
                        <div style="margin-top:8px;font-size:0.82rem;color:#7f1d1d;background:#fee2e2;padding:6px 10px;border-radius:4px;">
                            <strong>Tip:</strong> Your journal entries are themselves out of balance by {{ $fmt(abs($ldDiff)) }}.
                            Expand <em>Imbalanced Journal Entries</em> below and fix those entries first.
                        </div>
                    @endif
                </div>
            @endif

            @if($showInlineDiagnostics && abs((float) ($reconciliationReserveDiagnostic ?? 0)) >= $balanceTolerance)
                <div class="bs-hidden-debug">
                    <strong>Diagnostic Reconciliation Gap</strong><br>
                    A temporary balancing amount of {{ $fmt((float) ($reconciliationReserveDiagnostic ?? 0)) }}
                    would be required to force agreement, but no reserve entry has been posted automatically.
                    @if(!empty($reconciliationReserveNeedsReview))
                        <div style="margin-top:6px;color:#991b1b;font-weight:700;">
                            Review required: the diagnostic gap exceeds {{ $fmt((float) ($reconciliationReserveThreshold ?? 0)) }}.
                        </div>
                    @endif
                </div>
            @endif

            @php
                $openingBalanceValidation = $openingBalanceValidation ?? [];
                $duplicateCustomerRefs = collect($openingBalanceValidation['duplicate_customer_refs'] ?? []);
                $duplicateSupplierRefs = collect($openingBalanceValidation['duplicate_supplier_refs'] ?? []);
                $imbalancedCustomerRefs = collect($openingBalanceValidation['imbalanced_customer_refs'] ?? []);
                $imbalancedSupplierRefs = collect($openingBalanceValidation['imbalanced_supplier_refs'] ?? []);
                $reserveSuspenseDiagnostics = collect($reserveSuspenseDiagnostics ?? []);
            @endphp

            @if($showInlineDiagnostics && (
                abs((float) ($openingBalanceValidation['unposted_customer_opening_balance'] ?? 0)) >= 0.01 ||
                abs((float) ($openingBalanceValidation['unposted_supplier_opening_balance'] ?? 0)) >= 0.01 ||
                abs((float) ($openingBalanceValidation['legacy_inventory_bridge'] ?? 0)) >= 0.01 ||
                $duplicateCustomerRefs->isNotEmpty() ||
                $duplicateSupplierRefs->isNotEmpty() ||
                $imbalancedCustomerRefs->isNotEmpty() ||
                $imbalancedSupplierRefs->isNotEmpty() ||
                $reserveSuspenseDiagnostics->isNotEmpty()
            ))
                <details class="bs-hidden-debug no-print" style="margin-top:16px;">
                    <summary>Validation & Diagnostics</summary>
                    <table>
                        <tbody>
                            <tr>
                                <td>Unposted Customer Opening Balances</td>
                                <td>{{ $fmt((float) ($openingBalanceValidation['unposted_customer_opening_balance'] ?? 0)) }}</td>
                            </tr>
                            <tr>
                                <td>Unposted Supplier Opening Balances</td>
                                <td>{{ $fmt((float) ($openingBalanceValidation['unposted_supplier_opening_balance'] ?? 0)) }}</td>
                            </tr>
                            <tr>
                                <td>Legacy Inventory Bridge Still Outside Ledger</td>
                                <td>{{ $fmt((float) ($openingBalanceValidation['legacy_inventory_bridge'] ?? 0)) }}</td>
                            </tr>
                            <tr>
                                <td>Duplicate Customer Opening Refs</td>
                                <td>{{ number_format($duplicateCustomerRefs->count()) }}</td>
                            </tr>
                            <tr>
                                <td>Duplicate Supplier Opening Refs</td>
                                <td>{{ number_format($duplicateSupplierRefs->count()) }}</td>
                            </tr>
                            <tr>
                                <td>Reserve / Suspense Ledger Rows</td>
                                <td>{{ number_format($reserveSuspenseDiagnostics->count()) }}</td>
                            </tr>
                        </tbody>
                    </table>

                    @if($duplicateCustomerRefs->isNotEmpty() || $duplicateSupplierRefs->isNotEmpty() || $imbalancedCustomerRefs->isNotEmpty() || $imbalancedSupplierRefs->isNotEmpty())
                        <div style="margin-top:12px;font-weight:700;">Opening Balance Reference Exceptions</div>
                        <table style="width:100%;margin-top:8px;border-collapse:collapse;font-size:0.79rem;">
                            <thead>
                                <tr style="background:#fef9c3;">
                                    <td style="padding:4px 6px;">Type</td>
                                    <td style="padding:4px 6px;">Reference</td>
                                    <td style="padding:4px 6px;">Related ID</td>
                                    <td style="padding:4px 6px;text-align:right;">Entries</td>
                                    <td style="padding:4px 6px;text-align:right;">Debit</td>
                                    <td style="padding:4px 6px;text-align:right;">Credit</td>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($duplicateCustomerRefs->concat($duplicateSupplierRefs)->concat($imbalancedCustomerRefs)->concat($imbalancedSupplierRefs) as $row)
                                    <tr>
                                        <td style="padding:4px 6px;">
                                            {{ str_starts_with((string) ($row->reference ?? ''), 'CUST-OB-') ? 'Customer OB' : 'Supplier OB' }}
                                        </td>
                                        <td style="padding:4px 6px;">{{ $row->reference ?? '—' }}</td>
                                        <td style="padding:4px 6px;">{{ $row->related_id ?? '—' }}</td>
                                        <td style="padding:4px 6px;text-align:right;">{{ (int) ($row->entry_count ?? 0) }}</td>
                                        <td style="padding:4px 6px;text-align:right;">{{ $fmt((float) ($row->total_debit ?? 0)) }}</td>
                                        <td style="padding:4px 6px;text-align:right;">{{ $fmt((float) ($row->total_credit ?? 0)) }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    @endif

                    @if($reserveSuspenseDiagnostics->isNotEmpty())
                        <div style="margin-top:12px;font-weight:700;">Reserve / Suspense Source Transactions</div>
                        <table style="width:100%;margin-top:8px;border-collapse:collapse;font-size:0.79rem;">
                            <thead>
                                <tr style="background:#fef9c3;">
                                    <td style="padding:4px 6px;">Date</td>
                                    <td style="padding:4px 6px;">Account</td>
                                    <td style="padding:4px 6px;">Reference</td>
                                    <td style="padding:4px 6px;">Type</td>
                                    <td style="padding:4px 6px;">Branch</td>
                                    <td style="padding:4px 6px;text-align:right;">Debit</td>
                                    <td style="padding:4px 6px;text-align:right;">Credit</td>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($reserveSuspenseDiagnostics as $row)
                                    <tr>
                                        <td style="padding:4px 6px;">{{ \Carbon\Carbon::parse($row->transaction_date)->format('d M Y') }}</td>
                                        <td style="padding:4px 6px;">{{ $row->account_name }}{{ !empty($row->account_code) ? ' (' . $row->account_code . ')' : '' }}</td>
                                        <td style="padding:4px 6px;">{{ $row->reference ?? '—' }}</td>
                                        <td style="padding:4px 6px;">{{ $row->transaction_type ?? '—' }}</td>
                                        <td style="padding:4px 6px;">{{ $row->branch_name ?: ($row->branch_id ?: 'Shared') }}</td>
                                        <td style="padding:4px 6px;text-align:right;">{{ $fmt((float) ($row->debit ?? 0)) }}</td>
                                        <td style="padding:4px 6px;text-align:right;">{{ $fmt((float) ($row->credit ?? 0)) }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    @endif
                </details>
            @endif

            {{-- Detailed imbalance entries (auto-expanded when unbalanced) --}}
            @if($showInlineDiagnostics && isset($imbalancedEntries) && $imbalancedEntries->isNotEmpty() && !$isBalanced)
                <details style="margin-top:16px;" open>
                    <summary style="cursor:pointer;font-size:0.80rem;color:#64748b;font-weight:600;">
                        &#9888; Imbalanced Journal Entries ({{ $imbalancedEntries->count() }}) — fix these first
                    </summary>
                    <p style="font-size:0.79rem;color:#64748b;margin:6px 0 8px;">
                        Each row below shows a transaction group where Debits ≠ Credits.
                        Navigate to the original transaction and add the missing leg to correct it.
                    </p>
                    <table style="width:100%;margin-top:8px;font-size:0.79rem;border-collapse:collapse;">
                        <thead>
                            <tr style="background:#f1f5f9;color:#475569;">
                                <th style="padding:5px 8px;text-align:left;font-weight:700;">Type</th>
                                <th style="padding:5px 8px;text-align:left;font-weight:700;">Reference</th>
                                <th style="padding:5px 8px;text-align:right;font-weight:700;">Debit</th>
                                <th style="padding:5px 8px;text-align:right;font-weight:700;">Credit</th>
                                <th style="padding:5px 8px;text-align:right;font-weight:700;">Gap</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($imbalancedEntries as $entry)
                                <tr style="border-top:1px solid #e2e8f0;">
                                    <td style="padding:4px 8px;">{{ $entry->transaction_type ?? '—' }}</td>
                                    <td style="padding:4px 8px;">{{ $entry->reference ?? ($entry->related_type . '#' . $entry->related_id) }}</td>
                                    <td style="padding:4px 8px;text-align:right;font-variant-numeric:tabular-nums;">{{ $fmt((float)$entry->total_debit) }}</td>
                                    <td style="padding:4px 8px;text-align:right;font-variant-numeric:tabular-nums;">{{ $fmt((float)$entry->total_credit) }}</td>
                                    <td style="padding:4px 8px;text-align:right;font-variant-numeric:tabular-nums;color:#dc2626;font-weight:700;">
                                        {{ $fmt(abs((float)$entry->total_debit - (float)$entry->total_credit)) }}
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </details>
            @endif

            {{-- Unclassified / orphaned accounts panel --}}
            @if($showInlineDiagnostics && isset($unplacedAccounts) && $unplacedAccounts->isNotEmpty())
                <details style="margin-top:16px;" {{ !$isBalanced ? 'open' : '' }}>
                    <summary style="cursor:pointer;font-size:0.80rem;color:#92400e;font-weight:600;">
                        &#9888; Unclassified Accounts ({{ $unplacedAccounts->count() }}) — not appearing on the balance sheet
                    </summary>
                    <p style="font-size:0.79rem;color:#64748b;margin:6px 0 8px;">
                        These accounts have posted transactions but their <strong>Account Type</strong> is not
                        recognised as Asset, Liability, or Equity, so they are excluded from the statement.
                        Open each account in your Chart of Accounts and set the correct type.
                    </p>
                    <table style="width:100%;font-size:0.79rem;border-collapse:collapse;">
                        <thead>
                            <tr style="background:#fef9c3;color:#78350f;">
                                <th style="padding:5px 8px;text-align:left;font-weight:700;">#</th>
                                <th style="padding:5px 8px;text-align:left;font-weight:700;">Account Name</th>
                                <th style="padding:5px 8px;text-align:left;font-weight:700;">Current Type</th>
                                <th style="padding:5px 8px;text-align:left;font-weight:700;">Sub-type</th>
                                <th style="padding:5px 8px;text-align:right;font-weight:700;">Balance</th>
                                <th style="padding:5px 8px;text-align:left;font-weight:700;">Suggested Fix</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($unplacedAccounts as $uaIdx => $ua)
                                @php
                                    $uaType     = strtolower(trim((string) ($ua->type ?? '')));
                                    $uaName     = strtolower(trim((string) ($ua->name ?? '')));
                                    $uaReason   = $ua->_unplaced_reason ?? 'unrecognized_type';

                                    if ($uaReason === 'system_reserve') {
                                        // Account type IS recognised (e.g. Equity) but it is a
                                        // system-generated reconciliation/suspense account excluded
                                        // from the face of the statement by design.
                                        $uaSuggest  = 'System reconciliation account — type is correct. Balance represents an outstanding bank-rec difference.';
                                        $uaSugColor = '#6b7280';
                                    } elseif (str_contains($uaType, 'income') || str_contains($uaType, 'revenue') || str_contains($uaName, 'income') || str_contains($uaName, 'revenue') || str_contains($uaName, 'sales')) {
                                        $uaSuggest  = 'Set type → Revenue / Income';
                                        $uaSugColor = '#047857';
                                    } elseif (str_contains($uaType, 'expense') || str_contains($uaType, 'cost') || str_contains($uaName, 'expense') || str_contains($uaName, 'cost')) {
                                        $uaSuggest  = 'Set type → Expense';
                                        $uaSugColor = '#047857';
                                    } elseif (str_contains($uaType, 'loan') || str_contains($uaType, 'payable') || str_contains($uaType, 'liability') || str_contains($uaName, 'loan') || str_contains($uaName, 'payable') || str_contains($uaName, 'liability')) {
                                        $uaSuggest  = 'Set type → Liability';
                                        $uaSugColor = '#047857';
                                    } elseif (str_contains($uaType, 'equity') || str_contains($uaType, 'capital') || str_contains($uaName, 'capital') || str_contains($uaName, 'equity')) {
                                        $uaSuggest  = 'Set type → Equity';
                                        $uaSugColor = '#047857';
                                    } elseif (str_contains($uaType, 'bank') || str_contains($uaType, 'cash') || str_contains($uaName, 'bank') || str_contains($uaName, 'cash')) {
                                        $uaSuggest  = 'Set type → Asset (Bank/Cash)';
                                        $uaSugColor = '#047857';
                                    } elseif (str_contains($uaType, 'receivable') || str_contains($uaType, 'debtor') || str_contains($uaName, 'receivable') || str_contains($uaName, 'debtor')) {
                                        $uaSuggest  = 'Set type → Asset (Receivable)';
                                        $uaSugColor = '#047857';
                                    } else {
                                        $uaSuggest  = 'Open Chart of Accounts and set the correct type';
                                        $uaSugColor = '#b45309';
                                    }
                                @endphp
                                <tr style="border-top:1px solid #e2e8f0;{{ abs((float)($ua->balance ?? 0)) > 100000 ? 'background:#fffbeb;' : '' }}">
                                    <td style="padding:4px 8px;color:#9ca3af;">{{ $uaIdx + 1 }}</td>
                                    <td style="padding:4px 8px;font-weight:600;">{{ $ua->name ?? '—' }}</td>
                                    <td style="padding:4px 8px;{{ $uaReason === 'system_reserve' ? 'color:#6b7280;' : 'color:#dc2626;font-weight:700;' }}">
                                        {{ $ua->type ?? '(none)' }}
                                        @if($uaReason === 'system_reserve')
                                            <span style="font-size:0.73rem;color:#16a34a;">✓ recognised</span>
                                        @endif
                                    </td>
                                    <td style="padding:4px 8px;color:#6b7280;">{{ $ua->sub_type ?? '—' }}</td>
                                    <td style="padding:4px 8px;text-align:right;font-variant-numeric:tabular-nums;font-weight:600;">
                                        {{ $fmt((float) ($ua->balance ?? 0)) }}
                                    </td>
                                    <td style="padding:4px 8px;color:{{ $uaSugColor }};font-size:0.77rem;">{{ $uaSuggest }}</td>
                                </tr>
                            @endforeach
                            @php
                                $unplacedTotal   = $unplacedAccounts->sum(fn($a) => (float)($a->balance ?? 0));
                                $unexplainedGap  = round(abs($equationDiff) - abs($unplacedTotal), 2);
                            @endphp
                            <tr style="background:#fef9c3;font-weight:700;border-top:2px solid #d97706;">
                                <td colspan="4" style="padding:5px 8px;">Total unclassified balance</td>
                                <td style="padding:5px 8px;text-align:right;font-variant-numeric:tabular-nums;">
                                    {{ $fmt($unplacedTotal) }}
                                </td>
                                <td></td>
                            </tr>
                        </tbody>
                    </table>

                    @if($unexplainedGap >= 1)
                        <div style="margin-top:10px;padding:10px 14px;background:#fef3c7;border-left:4px solid #f59e0b;border-radius:4px;font-size:0.81rem;line-height:1.55;">
                            <strong style="color:#92400e;">&#9888; Unexplained gap after accounting for unclassified accounts: {{ $fmt($unexplainedGap) }}</strong><br>
                            <span style="color:#78350f;">
                                The unclassified accounts above explain only {{ $fmt(abs($unplacedTotal)) }} of the {{ $fmt(abs($equationDiff)) }} gap.
                                The remaining <strong>{{ $fmt($unexplainedGap) }}</strong> is most likely caused by one of the following:
                            </span>
                            <ul style="margin:6px 0 0 18px;color:#78350f;padding:0;">
                                <li><strong>Owner's capital or share capital</strong> was never posted as a journal entry (e.g., Dr Bank / Cr Capital).</li>
                                <li><strong>Opening retained earnings</strong> from periods before this system was set up were not migrated into an equity account.</li>
                                <li><strong>Opening balances</strong> for assets (receivables, inventory) were entered without a corresponding credit to equity.</li>
                            </ul>
                            <div style="margin-top:6px;color:#92400e;">
                                <strong>Action:</strong> Create an <em>Owner's Capital</em> (or <em>Retained Earnings</em>) account with type <em>Equity</em>,
                                then post a journal entry:
                                <code style="background:#fde68a;padding:1px 5px;border-radius:3px;">Dr Opening Balance Clearing &nbsp;{{ $fmt($unexplainedGap) }} / Cr Owner's Capital &nbsp;{{ $fmt($unexplainedGap) }}</code>
                            </div>
                        </div>
                    @endif
                </details>
            @endif

            {{-- ── Full account classification diagnostic ────────────────────────────── --}}
            @if($showInlineDiagnostics && !empty($fullLedgerBreakdown) && $fullLedgerBreakdown->isNotEmpty())
            @php
                // Equity gap: amount of missing opening equity (data gap, not a code bug).
                // Positive = assets exceed liabilities + all recorded equity + RE.
                $diagEquityGap = $openingEquityGap ?? 0.0;
            @endphp
            <details style="margin-top:14px;" id="bsFullLedger">
                <summary style="cursor:pointer;font-weight:700;font-size:0.88rem;color:#1e40af;padding:4px 0;">
                    &#128202; Full Account Classification — {{ $fullLedgerBreakdown->count() }} account(s) with ledger activity
                </summary>
                <div style="overflow-x:auto;margin-top:8px;">
                    <table style="width:100%;border-collapse:collapse;font-size:0.78rem;">
                        <thead>
                            <tr style="background:#eff6ff;font-size:0.75rem;text-transform:uppercase;letter-spacing:.04em;color:#1e40af;">
                                <th style="padding:5px 7px;text-align:left;">#</th>
                                <th style="padding:5px 7px;text-align:left;">Account</th>
                                <th style="padding:5px 7px;text-align:left;">DB&nbsp;Type</th>
                                <th style="padding:5px 7px;text-align:left;">Normalised</th>
                                <th style="padding:5px 7px;text-align:left;">Sub-type</th>
                                <th style="padding:5px 7px;text-align:right;">Dr&nbsp;Total</th>
                                <th style="padding:5px 7px;text-align:right;">Cr&nbsp;Total</th>
                                <th style="padding:5px 7px;text-align:right;">Balance</th>
                                <th style="padding:5px 7px;text-align:left;">BS&nbsp;Bucket</th>
                            </tr>
                        </thead>
                        <tbody>
                            @php $diagIdx = 0; $lastBucket = null; @endphp
                            @foreach($fullLedgerBreakdown as $da)
                                @php
                                    $daBucket   = $da->_bucket   ?? '?';
                                    $daSide     = $da->_side     ?? 'unclassified';
                                    $daNorm     = $da->_normType ?? '?';
                                    $daDr       = (float)($da->total_debit  ?? 0);
                                    $daCr       = (float)($da->total_credit ?? 0);
                                    $daBal      = (float)($da->balance      ?? 0);
                                    $daIsUnknown= ($daSide === 'unclassified');
                                    $daRowBg    = match($daSide) {
                                        'asset'         => 'background:#f0fdf4;',
                                        'liability'     => 'background:#fff7ed;',
                                        'equity'        => 'background:#eff6ff;',
                                        'revenue'       => 'background:#fdf4ff;',
                                        'expense'       => 'background:#fefce8;',
                                        default         => 'background:#fef2f2;',
                                    };
                                    $diagIdx++;
                                @endphp
                                @if($daBucket !== $lastBucket)
                                    @php $lastBucket = $daBucket; @endphp
                                    <tr>
                                        <td colspan="9" style="padding:4px 7px 2px;font-weight:700;font-size:0.73rem;letter-spacing:.05em;text-transform:uppercase;color:#374151;border-top:2px solid #d1d5db;background:#f9fafb;">
                                            {{ $daBucket }}
                                        </td>
                                    </tr>
                                @endif
                                <tr style="{{ $daRowBg }}{{ $daIsUnknown ? 'border-left:3px solid #dc2626;' : '' }}">
                                    <td style="padding:3px 7px;color:#9ca3af;">{{ $diagIdx }}</td>
                                    <td style="padding:3px 7px;font-weight:600;">{{ $da->name ?? '—' }}</td>
                                    <td style="padding:3px 7px;color:{{ $daIsUnknown ? '#dc2626' : '#374151' }};font-family:monospace;font-size:0.75rem;">
                                        {{ $da->type ?? '(none)' }}
                                    </td>
                                    <td style="padding:3px 7px;font-family:monospace;font-size:0.75rem;color:{{ $daNorm==='other'?'#dc2626':'#374151' }};">
                                        {{ $daNorm }}
                                    </td>
                                    <td style="padding:3px 7px;color:#6b7280;font-size:0.74rem;">{{ $da->sub_type ?? '—' }}</td>
                                    <td style="padding:3px 7px;text-align:right;font-variant-numeric:tabular-nums;color:#374151;">
                                        {{ $daDr > 0.005 ? $fmt($daDr) : '—' }}
                                    </td>
                                    <td style="padding:3px 7px;text-align:right;font-variant-numeric:tabular-nums;color:#374151;">
                                        {{ $daCr > 0.005 ? $fmt($daCr) : '—' }}
                                    </td>
                                    <td style="padding:3px 7px;text-align:right;font-variant-numeric:tabular-nums;font-weight:700;color:{{ $daBal < 0 ? '#dc2626' : '#059669' }};">
                                        {{ $fmt($daBal) }}
                                    </td>
                                    <td style="padding:3px 7px;font-size:0.74rem;color:{{ $daIsUnknown?'#dc2626':'#374151' }};">{{ $daBucket }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                {{-- Equity gap analysis ------------------------------------------------- --}}
                @if(abs($diagEquityGap) >= 1)
                <div style="margin-top:12px;padding:12px 16px;background:#fef3c7;border-left:4px solid #d97706;border-radius:4px;font-size:0.81rem;line-height:1.6;">
                    <strong style="color:#92400e;font-size:0.86rem;">&#9888; Opening Equity Gap Detected: {{ $fmt(abs($diagEquityGap)) }}</strong>
                    <table style="margin-top:8px;border-collapse:collapse;width:100%;max-width:520px;font-size:0.79rem;">
                        <tr>
                            <td style="padding:2px 6px;color:#78350f;">Total Assets</td>
                            <td style="padding:2px 6px;text-align:right;font-variant-numeric:tabular-nums;font-weight:700;">{{ $fmt($visTotalAssets) }}</td>
                        </tr>
                        <tr>
                            <td style="padding:2px 6px;color:#78350f;">Total Liabilities</td>
                            <td style="padding:2px 6px;text-align:right;font-variant-numeric:tabular-nums;font-weight:700;">({{ $fmt($visTotalLiabilities) }})</td>
                        </tr>
                        <tr>
                            <td style="padding:2px 6px;color:#78350f;">Real Equity Accounts (Capital / Reserves / Retained)</td>
                            <td style="padding:2px 6px;text-align:right;font-variant-numeric:tabular-nums;font-weight:700;">({{ $fmt($visTotalEquityCapital + $visTotalEquityRetained + $visTotalEquityReserves) }})</td>
                        </tr>
                        <tr>
                            <td style="padding:2px 6px;color:#78350f;">Current Year Net Income (Earnings / Deficit)</td>
                            <td style="padding:2px 6px;text-align:right;font-variant-numeric:tabular-nums;font-weight:700;">({{ $fmt($visTotalCurrentEarnings) }})</td>
                        </tr>
                        <tr style="border-top:2px solid #d97706;">
                            <td style="padding:4px 6px;color:#92400e;font-weight:700;">Opening Equity Gap (unrecorded capital)</td>
                            <td style="padding:4px 6px;text-align:right;font-variant-numeric:tabular-nums;font-weight:700;color:#b45309;">{{ $fmt(abs($diagEquityGap)) }}</td>
                        </tr>
                    </table>
                    <p style="margin:8px 0 4px;color:#78350f;">
                        The ledger is <strong>balanced</strong> (debits = credits), so no journal entries are missing or duplicated.
                        However, <strong>{{ $fmt(abs($diagEquityGap)) }}</strong> of assets were funded without a corresponding
                        credit to an equity account (owner's capital injection or opening retained earnings).
                    </p>
                    <ul style="margin:4px 0 0 18px;color:#78350f;padding:0;">
                        <li>Create an <em>Owner's Capital</em> account (type: Equity) if one does not exist.</li>
                        <li>Post a journal entry: <code style="background:#fde68a;padding:1px 4px;border-radius:3px;">Dr Opening Balance Clearing &nbsp;{{ $fmt(abs($diagEquityGap)) }}&nbsp;/&nbsp;Cr Owner's Capital &nbsp;{{ $fmt(abs($diagEquityGap)) }}</code></li>
                        <li>After posting, refresh this report — the gap should be zero.</li>
                    </ul>
                </div>
                @endif
            </details>
            @endif

            {{-- ══════════════════════════════════════════════════════════════════════ --}}
            {{-- Opening Balance & Equity Migration Audit                               --}}
            {{-- ══════════════════════════════════════════════════════════════════════ --}}
            @if($showInlineDiagnostics && !empty($openingBalanceAudit) && ($openingBalanceAudit['available'] ?? false))
            @php
                $oba           = $openingBalanceAudit;
                $obaHasData    = $oba['has_opening_journals'] ?? false;
                $obaAsset      = (float) ($oba['opening_asset_total']        ?? 0);
                $obaLiab       = (float) ($oba['opening_liability_total']    ?? 0);
                $obaEquity     = (float) ($oba['opening_equity_total']       ?? 0);
                $obaNetAssets  = (float) ($oba['opening_net_assets']         ?? 0);
                $obaReqAdj     = (float) ($oba['required_equity_adjustment'] ?? 0);
                $obaFlagged    = $oba['flagged_refs']   ?? collect();
                $obaByRef      = $oba['by_reference']   ?? collect();
                $obaTypeTotals = $oba['type_totals']    ?? collect();
                $obaHasGap     = abs($obaReqAdj) >= 1;
                $obaFlagCount  = $obaFlagged instanceof \Illuminate\Support\Collection ? $obaFlagged->count() : count($obaFlagged);
            @endphp
            <details style="margin-top:14px;" id="bsOpeningAudit" {{ (!$obaHasData || $obaHasGap) && !$isBalanced ? 'open' : '' }}>
                <summary style="cursor:pointer;font-weight:700;font-size:0.88rem;color:#7c3aed;padding:4px 0;">
                    &#128269; Opening Balance &amp; Equity Migration Audit
                    @if(!$obaHasData)
                        <span style="font-weight:400;color:#6b7280;font-size:0.8rem;"> — no Opening Balance journals found for this branch</span>
                    @elseif($obaHasGap)
                        <span style="font-weight:400;color:#b91c1c;font-size:0.8rem;"> — &#9888; equity shortfall {{ $fmt(abs($obaReqAdj)) }} detected</span>
                    @else
                        <span style="font-weight:400;color:#059669;font-size:0.8rem;"> — &#10003; opening equity symmetric</span>
                    @endif
                </summary>

                @if(!$obaHasData)
                    <div style="padding:10px 12px;background:#f3f4f6;border-radius:4px;font-size:0.82rem;color:#6b7280;margin-top:8px;">
                        No transactions with type <strong>"Opening Balance"</strong> were found for this branch
                        up to {{ $reportDate->format('j M Y') }}.<br>
                        If opening balances exist but were posted with a different transaction type (e.g. "Journal Entry"),
                        they will not appear here. Check the <em>Full Account Classification</em> panel above for those ledger movements.
                    </div>
                @else

                    {{-- ── Task 1–3: Net assets vs equity summary ── --}}
                    <div style="margin-top:10px;overflow-x:auto;">
                        <table style="border-collapse:collapse;font-size:0.8rem;min-width:420px;max-width:600px;">
                            <caption style="text-align:left;font-weight:700;color:#374151;padding:0 0 6px;font-size:0.82rem;">
                                Opening Balance Type Summary (up to {{ $reportDate->format('j M Y') }})
                            </caption>
                            <thead>
                                <tr style="background:#ede9fe;font-size:0.73rem;text-transform:uppercase;letter-spacing:.04em;color:#4c1d95;">
                                    <th style="padding:5px 10px;text-align:left;">Account Type</th>
                                    <th style="padding:5px 10px;text-align:right;">Dr Total</th>
                                    <th style="padding:5px 10px;text-align:right;">Cr Total</th>
                                    <th style="padding:5px 10px;text-align:right;">Net Effect</th>
                                    <th style="padding:5px 10px;text-align:right;">Entries</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($obaTypeTotals as $obaType => $obaRow)
                                @php
                                    $obaRowBg = match($obaType) {
                                        'asset'     => '#f0fdf4',
                                        'liability' => '#fff7ed',
                                        'equity'    => '#eff6ff',
                                        'revenue'   => '#fdf4ff',
                                        'expense'   => '#fefce8',
                                        default     => '#fef2f2',
                                    };
                                    $obaNetBal = (float)($obaRow['net_balance'] ?? 0);
                                @endphp
                                <tr style="background:{{ $obaRowBg }};border-top:1px solid #e5e7eb;">
                                    <td style="padding:4px 10px;font-weight:600;text-transform:capitalize;">{{ $obaType }}</td>
                                    <td style="padding:4px 10px;text-align:right;font-variant-numeric:tabular-nums;">{{ $fmt((float)($obaRow['total_debit'] ?? 0)) }}</td>
                                    <td style="padding:4px 10px;text-align:right;font-variant-numeric:tabular-nums;">{{ $fmt((float)($obaRow['total_credit'] ?? 0)) }}</td>
                                    <td style="padding:4px 10px;text-align:right;font-variant-numeric:tabular-nums;font-weight:700;color:{{ $obaNetBal < 0 ? '#dc2626' : '#059669' }};">
                                        {{ $fmt($obaNetBal) }}
                                    </td>
                                    <td style="padding:4px 10px;text-align:right;color:#6b7280;">{{ $obaRow['count'] ?? 0 }}</td>
                                </tr>
                                @endforeach
                                {{-- Derived rows ──────────────────────────────────── --}}
                                <tr style="border-top:2px solid #a78bfa;background:#f5f3ff;">
                                    <td colspan="3" style="padding:5px 10px;font-weight:700;color:#4c1d95;">Net Assets via Opening Balances (Assets − Liabilities)</td>
                                    <td style="padding:5px 10px;text-align:right;font-variant-numeric:tabular-nums;font-weight:700;color:{{ $obaNetAssets < 0 ? '#dc2626' : '#374151' }};">
                                        {{ $fmt($obaNetAssets) }}
                                    </td>
                                    <td></td>
                                </tr>
                                <tr style="background:#f5f3ff;">
                                    <td colspan="3" style="padding:3px 10px;color:#4c1d95;">Equity posted via Opening Balances</td>
                                    <td style="padding:3px 10px;text-align:right;font-variant-numeric:tabular-nums;color:#374151;">{{ $fmt($obaEquity) }}</td>
                                    <td></td>
                                </tr>
                                <tr style="border-top:2px solid {{ $obaHasGap ? '#dc2626' : '#059669' }};background:{{ $obaHasGap ? '#fef2f2' : '#f0fdf4' }};">
                                    <td colspan="3" style="padding:6px 10px;font-weight:700;color:{{ $obaHasGap ? '#b91c1c' : '#059669' }};">
                                        Required Equity Adjustment (Task 7)
                                        @if(!$obaHasGap)
                                            &nbsp;&#10003;
                                        @endif
                                    </td>
                                    <td style="padding:6px 10px;text-align:right;font-variant-numeric:tabular-nums;font-weight:700;font-size:1.05em;color:{{ $obaHasGap ? '#b91c1c' : '#059669' }};">
                                        {{ $fmt(abs($obaReqAdj)) }}
                                        @if($obaHasGap) &nbsp;&#9650; MISSING @endif
                                    </td>
                                    <td></td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    @if($obaHasGap)
                    <div style="margin-top:10px;padding:10px 14px;background:#fef2f2;border-left:4px solid #dc2626;border-radius:4px;font-size:0.81rem;line-height:1.6;">
                        <strong style="color:#991b1b;">&#9888; Equity shortfall confirmed: {{ $fmt(abs($obaReqAdj)) }}</strong><br>
                        <span style="color:#7f1d1d;">
                            Opening balance journals introduced <strong>{{ $fmt($obaAsset) }}</strong> of assets
                            and <strong>{{ $fmt($obaLiab) }}</strong> of liabilities (net {{ $fmt($obaNetAssets) }}),
                            but only <strong>{{ $fmt($obaEquity) }}</strong> was posted to equity accounts.
                            The <strong>{{ $fmt(abs($obaReqAdj)) }}</strong> difference must be covered by a
                            journal entry to <em>Owner's Capital</em> (or similar equity account).
                        </span>
                        <div style="margin-top:6px;">
                            <strong style="color:#991b1b;">Missing journal entry:</strong><br>
                            <code style="background:#fee2e2;padding:2px 6px;border-radius:3px;display:inline-block;margin-top:3px;">
                                Dr &nbsp;Opening Balance Clearing &nbsp;{{ $fmt(abs($obaReqAdj)) }}&nbsp;&nbsp;/&nbsp;&nbsp;Cr &nbsp;Owner's Capital &nbsp;{{ $fmt(abs($obaReqAdj)) }}
                            </code>
                        </div>
                    </div>
                    @endif

                    {{-- ── Tasks 5–6: Per-journal-reference audit (flagged first) ── --}}
                    <details style="margin-top:14px;" {{ $obaFlagCount > 0 ? 'open' : '' }}>
                        <summary style="cursor:pointer;font-size:0.83rem;font-weight:700;color:#374151;padding:3px 0;">
                            Journal-by-Journal Audit
                            @if($obaFlagCount > 0)
                                &nbsp;<span style="background:#dc2626;color:#fff;border-radius:9px;padding:1px 8px;font-size:0.75rem;">{{ $obaFlagCount }} flagged</span>
                            @else
                                &nbsp;<span style="background:#059669;color:#fff;border-radius:9px;padding:1px 8px;font-size:0.75rem;">all symmetric</span>
                            @endif
                        </summary>
                        <div style="overflow-x:auto;margin-top:8px;">
                            @foreach($obaByRef as $obaJournal)
                            @php
                                $jFlagged   = $obaJournal->flag;
                                $jImbal     = $obaJournal->is_imbalanced;
                                $jRef       = $obaJournal->reference ?? '—';
                                $jDate      = $obaJournal->date instanceof \Carbon\Carbon
                                    ? $obaJournal->date->format('j M Y')
                                    : (string) $obaJournal->date;
                            @endphp
                            <div style="margin-bottom:10px;border:1px solid {{ $jFlagged ? '#fca5a5' : ($jImbal ? '#fcd34d' : '#d1d5db') }};border-radius:5px;overflow:hidden;">
                                <div style="padding:5px 10px;background:{{ $jFlagged ? '#fef2f2' : ($jImbal ? '#fffbeb' : '#f9fafb') }};display:flex;gap:12px;align-items:center;flex-wrap:wrap;">
                                    <span style="font-weight:700;font-size:0.8rem;color:#374151;">{{ $jRef }}</span>
                                    <span style="color:#6b7280;font-size:0.77rem;">{{ $jDate }}</span>
                                    @if($jFlagged)
                                        <span style="background:#dc2626;color:#fff;border-radius:9px;padding:0 7px;font-size:0.72rem;">&#9888; no equity leg — missing {{ $fmt(abs($obaJournal->missing_equity)) }}</span>
                                    @elseif($jImbal)
                                        <span style="background:#d97706;color:#fff;border-radius:9px;padding:0 7px;font-size:0.72rem;">&#9888; imbalanced Dr/Cr</span>
                                    @else
                                        <span style="background:#059669;color:#fff;border-radius:9px;padding:0 7px;font-size:0.72rem;">&#10003; equity matched</span>
                                    @endif
                                    @if($jFlagged)
                                    <span style="font-size:0.76rem;color:#6b7280;margin-left:auto;">
                                        Asset net: {{ $fmt($obaJournal->asset_net) }} &nbsp;|&nbsp;
                                        Liab net: {{ $fmt($obaJournal->liab_net) }} &nbsp;|&nbsp;
                                        Equity posted: {{ $fmt($obaJournal->equity_net) }}
                                    </span>
                                    @endif
                                </div>
                                <table style="width:100%;border-collapse:collapse;font-size:0.77rem;">
                                    <thead>
                                        <tr style="background:#f3f4f6;color:#4b5563;font-size:0.72rem;text-transform:uppercase;letter-spacing:.04em;">
                                            <th style="padding:3px 8px;text-align:left;">Account</th>
                                            <th style="padding:3px 8px;text-align:left;">Type</th>
                                            <th style="padding:3px 8px;text-align:left;">Norm.</th>
                                            <th style="padding:3px 8px;text-align:right;">Debit</th>
                                            <th style="padding:3px 8px;text-align:right;">Credit</th>
                                            <th style="padding:3px 8px;text-align:left;">Branch</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($obaJournal->legs as $obaLeg)
                                        @php
                                            $legNorm = $obaLeg->_norm_type ?? 'other';
                                            $legBg   = match($legNorm) {
                                                'asset'     => '#f0fdf4',
                                                'liability' => '#fff7ed',
                                                'equity'    => '#eff6ff',
                                                default     => '#fef9f9',
                                            };
                                        @endphp
                                        <tr style="border-top:1px solid #f3f4f6;background:{{ $legBg }};">
                                            <td style="padding:3px 8px;font-weight:600;">{{ $obaLeg->account_name ?? '—' }}</td>
                                            <td style="padding:3px 8px;color:#374151;font-family:monospace;font-size:0.73rem;">{{ $obaLeg->account_type ?? '(none)' }}</td>
                                            <td style="padding:3px 8px;font-family:monospace;font-size:0.73rem;color:{{ $legNorm === 'other' ? '#dc2626' : '#374151' }};">{{ $legNorm }}</td>
                                            <td style="padding:3px 8px;text-align:right;font-variant-numeric:tabular-nums;">
                                                {{ (float)($obaLeg->debit ?? 0) > 0.005 ? $fmt((float)$obaLeg->debit) : '—' }}
                                            </td>
                                            <td style="padding:3px 8px;text-align:right;font-variant-numeric:tabular-nums;">
                                                {{ (float)($obaLeg->credit ?? 0) > 0.005 ? $fmt((float)$obaLeg->credit) : '—' }}
                                            </td>
                                            <td style="padding:3px 8px;color:#9ca3af;font-size:0.72rem;">
                                                {{ $obaLeg->branch_name ?? ($obaLeg->branch_id ? 'ID:'.$obaLeg->branch_id : 'global') }}
                                            </td>
                                        </tr>
                                        @endforeach
                                        <tr style="background:#f9fafb;font-weight:700;border-top:1px solid #d1d5db;">
                                            <td colspan="3" style="padding:3px 8px;color:#374151;">Totals</td>
                                            <td style="padding:3px 8px;text-align:right;font-variant-numeric:tabular-nums;">{{ $fmt($obaJournal->total_debit) }}</td>
                                            <td style="padding:3px 8px;text-align:right;font-variant-numeric:tabular-nums;">{{ $fmt($obaJournal->total_credit) }}</td>
                                            <td></td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                            @endforeach
                        </div>
                    </details>

                @endif {{-- has_opening_journals --}}
            </details>
            @endif {{-- openingBalanceAudit available --}}

        </div>{{-- /.bs-sheet --}}
    </div>{{-- /.bs-page --}}

</div>{{-- /.content --}}
</div>{{-- /.page-wrapper --}}

<script>
function bsApplyPreset(key) {
    const opts = document.querySelectorAll('#bsPreset option');
    opts.forEach(opt => {
        if (opt.value === key) {
            document.getElementById('bsDate').value = opt.dataset.date;
        }
    });
}

function bsSetMethod(val) {
    document.getElementById('bsMethod').value = val;
    document.getElementById('bsMethodAccrual').classList.toggle('active', val === 'accrual');
    document.getElementById('bsMethodCash').classList.toggle('active', val === 'cash');
}

function bsSetConsolidate(val) {
    var el = document.getElementById('bsConsolidate');
    if (el) el.value = val;
    document.querySelectorAll('[data-val="0"],[data-val="1"]').forEach(function(a) {
        if (a.closest('.bs-method-toggle')) {
            a.classList.toggle('active', parseInt(a.dataset.val) === val);
        }
    });
}

function bsExportExcel() {
    const date = document.getElementById('bsDate').value;
    window.location.href = '{{ route("balance-sheet.export") }}?date=' + date;
}
</script>
@endsection
