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
$equationDiff           = round($visTotalAssets - $visTotalLiabEquity, 2);
$isBalanced             = abs($equationDiff) < 0.01;

/* ─────────────────────────────────────────────────────────────────
 *  COMPARISON PERIOD DATA
 * ──────────────────────────────────────────────────────────────── */
$hasCmp  = !empty($compareData);
$colCount = $hasCmp ? 3 : 2;

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

        {{-- Unassigned-branch notice: shown when consolidated view has pre-branch data --}}
        @if($isAllBranches && ($unassignedTxnCount ?? 0) > 0)
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
                <strong>Consolidated total includes pre-branch data</strong><br>
                <span style="font-size:0.82rem;">
                    {{ number_format($unassignedTxnCount) }} transaction{{ $unassignedTxnCount == 1 ? '' : 's' }}
                    in this report have <em>no branch assigned</em> — they were recorded before
                    branch tracking was set up. These appear here in the consolidated view but are
                    <strong>not visible</strong> when you view any individual branch, which is why
                    the consolidated total is higher than the sum of your individual branch totals.
                    To resolve: reassign those transactions to the correct branch.
                </span>
            </div>
        </div>
        @endif

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
                    @if($hasCmp)<col class="col-amount">@endif
                </colgroup>
                <tbody>

                    <tr class="bs-col-head">
                        <td></td>
                        <td>{{ $asOfDate->format('M j, Y') }}</td>
                        @if($hasCmp)<td class="col-cmp">{{ $comparePeriodLabel }}</td>@endif
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
                                    @if($hasCmp)<td></td>@endif
                                </tr>
                            @endif
                            @foreach($group['items'] as $account)
                                @php $cv = $cmpAmt($account); @endphp
                                <tr class="{{ $showCaGroupHead ? 'bs-line bs-line-indented' : 'bs-line' }}">
                                    <td>
                                        {{ !empty($account->_vendor_credit) ? ($account->_display_name ?? 'Supplier Advance') : $account->name }}
                                    </td>
                                    <td class="bs-amt {{ (float)($account->balance ?? 0) < 0 ? 'bs-amt-neg' : '' }}">
                                        {{ $fmt((float)($account->balance ?? 0)) }}
                                    </td>
                                    @if($hasCmp)
                                        @if($cv !== null)
                                            <td class="bs-cmp-amt {{ $cv < 0 ? 'bs-cmp-amt-neg' : '' }}">{{ $fmt($cv) }}</td>
                                        @else
                                            <td class="bs-amt-dash">—</td>
                                        @endif
                                    @endif
                                </tr>
                            @endforeach
                            @if($showCaGroupHead)
                                <tr class="bs-sub-total">
                                    <td>Total {{ $group['label'] }}</td>
                                    <td class="bs-amt">{{ $fmt($group['total']) }}</td>
                                    @if($hasCmp)<td></td>@endif
                                </tr>
                            @endif
                        @endforeach
                        <tr class="bs-sub-total">
                            <td>Total Current Assets</td>
                            <td class="bs-amt">{{ $fmt($visTotalCurrentAssets) }}</td>
                            @if($hasCmp)<td class="bs-cmp-amt">{{ $fmt($cmpTotalCurrentAssets) }}</td>@endif
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
                                    @if($hasCmp)<td></td>@endif
                                </tr>
                            @endif
                            @foreach($group['items'] as $account)
                                @php $cv = $cmpAmt($account); @endphp
                                <tr class="{{ $showFaGroupHead ? 'bs-line bs-line-indented' : 'bs-line' }}">
                                    <td>{{ $account->name }}</td>
                                    <td class="bs-amt {{ (float)($account->balance ?? 0) < 0 ? 'bs-amt-neg' : '' }}">
                                        {{ $fmt((float)($account->balance ?? 0)) }}
                                    </td>
                                    @if($hasCmp)
                                        @if($cv !== null)
                                            <td class="bs-cmp-amt {{ $cv < 0 ? 'bs-cmp-amt-neg' : '' }}">{{ $fmt($cv) }}</td>
                                        @else
                                            <td class="bs-amt-dash">—</td>
                                        @endif
                                    @endif
                                </tr>
                            @endforeach
                            @if($showFaGroupHead)
                                <tr class="bs-sub-total">
                                    <td>Total {{ $group['label'] }}</td>
                                    <td class="bs-amt">{{ $fmt($group['total']) }}</td>
                                    @if($hasCmp)<td></td>@endif
                                </tr>
                            @endif
                        @endforeach
                        <tr class="bs-sub-total">
                            <td>Total Fixed Assets</td>
                            <td class="bs-amt">{{ $fmt($visTotalFixedAssets) }}</td>
                            @if($hasCmp)<td class="bs-cmp-amt">{{ $fmt($cmpTotalFixedAssets) }}</td>@endif
                        </tr>
                    @endif

                    {{-- Total Assets --}}
                    <tr class="bs-spacer"><td colspan="{{ $colCount }}"></td></tr>
                    <tr class="bs-section-total">
                        <td>Total Assets</td>
                        <td class="bs-amt">{{ $fmt($visTotalAssets) }}</td>
                        @if($hasCmp)<td class="bs-cmp-amt">{{ $fmt($cmpTotalAssets) }}</td>@endif
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
                            @php $cv = $cmpAmt($account); @endphp
                            <tr class="bs-line">
                                <td>
                                    {{ $account->name }}
                                    @if(!empty($account->_overdraft))
                                        <span class="bs-overdraft-tag">Overdraft</span>
                                    @endif
                                </td>
                                <td class="bs-amt">{{ $fmt((float)($account->balance ?? 0)) }}</td>
                                @if($hasCmp)
                                    @if($cv !== null)
                                        <td class="bs-cmp-amt {{ $cv < 0 ? 'bs-cmp-amt-neg' : '' }}">{{ $fmt($cv) }}</td>
                                    @else
                                        <td class="bs-amt-dash">—</td>
                                    @endif
                                @endif
                            </tr>
                        @endforeach
                        <tr class="bs-sub-total">
                            <td>Total Current Liabilities</td>
                            <td class="bs-amt">{{ $fmt($visTotalCurrentLiab) }}</td>
                            @if($hasCmp)<td class="bs-cmp-amt">{{ $fmt($cmpTotalCurrentLiab) }}</td>@endif
                        </tr>
                    @endif

                    {{-- Long-Term Liabilities --}}
                    @if($longTermLiabilityLines->isNotEmpty())
                        <tr class="bs-spacer"><td colspan="{{ $colCount }}"></td></tr>
                        <tr class="bs-sub-head"><td colspan="{{ $colCount }}">Long-Term Liabilities</td></tr>
                        @foreach($longTermLiabilityLines as $account)
                            @php $cv = $cmpAmt($account); @endphp
                            <tr class="bs-line">
                                <td>{{ $account->name }}</td>
                                <td class="bs-amt">{{ $fmt((float)($account->balance ?? 0)) }}</td>
                                @if($hasCmp)
                                    @if($cv !== null)
                                        <td class="bs-cmp-amt {{ $cv < 0 ? 'bs-cmp-amt-neg' : '' }}">{{ $fmt($cv) }}</td>
                                    @else
                                        <td class="bs-amt-dash">—</td>
                                    @endif
                                @endif
                            </tr>
                        @endforeach
                        <tr class="bs-sub-total">
                            <td>Total Long-Term Liabilities</td>
                            <td class="bs-amt">{{ $fmt($visTotalLongTermLiab) }}</td>
                            @if($hasCmp)<td class="bs-cmp-amt">{{ $fmt($cmpTotalLongTermLiab) }}</td>@endif
                        </tr>
                    @endif

                    {{-- Total Liabilities --}}
                    <tr class="bs-spacer"><td colspan="{{ $colCount }}"></td></tr>
                    <tr class="bs-section-total">
                        <td>Total Liabilities</td>
                        <td class="bs-amt">{{ $fmt($visTotalLiabilities) }}</td>
                        @if($hasCmp)<td class="bs-cmp-amt">{{ $fmt($cmpTotalLiabilities) }}</td>@endif
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
                            @php $cv = $cmpAmt($account); @endphp
                            <tr class="bs-line">
                                <td>{{ $account->name }}</td>
                                <td class="bs-amt {{ (float)($account->balance ?? 0) < 0 ? 'bs-amt-neg' : '' }}">
                                    {{ $fmt((float)($account->balance ?? 0)) }}
                                </td>
                                @if($hasCmp)
                                    @if($cv !== null)
                                        <td class="bs-cmp-amt {{ $cv < 0 ? 'bs-cmp-amt-neg' : '' }}">{{ $fmt($cv) }}</td>
                                    @else
                                        <td class="bs-amt-dash">&mdash;</td>
                                    @endif
                                @endif
                            </tr>
                        @endforeach
                    @endif

                    @if($equityRetainedLines->isNotEmpty() || $retainedEarningsLines->isNotEmpty())
                        <tr class="bs-spacer"><td colspan="{{ $colCount }}"></td></tr>
                        <tr class="bs-sub-head"><td colspan="{{ $colCount }}">Retained Earnings</td></tr>
                        @foreach($equityRetainedLines as $account)
                            @php $cv = $cmpAmt($account); @endphp
                            <tr class="bs-line">
                                <td>{{ $account->name }}</td>
                                <td class="bs-amt {{ (float)($account->balance ?? 0) < 0 ? 'bs-amt-neg' : '' }}">
                                    {{ $fmt((float)($account->balance ?? 0)) }}
                                </td>
                                @if($hasCmp)
                                    @if($cv !== null)
                                        <td class="bs-cmp-amt {{ $cv < 0 ? 'bs-cmp-amt-neg' : '' }}">{{ $fmt($cv) }}</td>
                                    @else
                                        <td class="bs-amt-dash">&mdash;</td>
                                    @endif
                                @endif
                            </tr>
                        @endforeach
                        @foreach($retainedEarningsLines as $account)
                            @php $cv = $cmpAmt($account); @endphp
                            <tr class="bs-line">
                                <td>
                                    {{ $account->name }}
                                    @if(!empty($account->_deficit))
                                        <span class="bs-deficit-tag">Accumulated Loss</span>
                                    @endif
                                </td>
                                <td class="bs-amt {{ (float)($account->balance ?? 0) < 0 ? 'bs-amt-neg' : '' }}">
                                    {{ $fmt((float)($account->balance ?? 0)) }}
                                </td>
                                @if($hasCmp)
                                    @if($cv !== null)
                                        <td class="bs-cmp-amt {{ $cv < 0 ? 'bs-cmp-amt-neg' : '' }}">{{ $fmt($cv) }}</td>
                                    @else
                                        <td class="bs-amt-dash">&mdash;</td>
                                    @endif
                                @endif
                            </tr>
                        @endforeach
                    @endif

                    @if($equityReserveLines->isNotEmpty())
                        <tr class="bs-spacer"><td colspan="{{ $colCount }}"></td></tr>
                        <tr class="bs-sub-head"><td colspan="{{ $colCount }}">Reserves</td></tr>
                        @foreach($equityReserveLines as $account)
                            @php $cv = $cmpAmt($account); @endphp
                            <tr class="bs-line">
                                <td>{{ $account->name }}</td>
                                <td class="bs-amt {{ (float)($account->balance ?? 0) < 0 ? 'bs-amt-neg' : '' }}">
                                    {{ $fmt((float)($account->balance ?? 0)) }}
                                </td>
                                @if($hasCmp)
                                    @if($cv !== null)
                                        <td class="bs-cmp-amt {{ $cv < 0 ? 'bs-cmp-amt-neg' : '' }}">{{ $fmt($cv) }}</td>
                                    @else
                                        <td class="bs-amt-dash">&mdash;</td>
                                    @endif
                                @endif
                            </tr>
                        @endforeach
                    @endif

                    <tr class="bs-sub-total">
                        <td>Total Equity</td>
                        <td class="bs-amt {{ $visTotalEquity < 0 ? 'bs-amt-neg' : '' }}">
                            {{ $fmt($visTotalEquity) }}
                        </td>
                        @if($hasCmp)
                            <td class="bs-cmp-amt {{ $cmpTotalEquity < 0 ? 'bs-cmp-amt-neg' : '' }}">
                                {{ $fmt($cmpTotalEquity) }}
                            </td>
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
            @else
                <div class="bs-imbalance">
                    <strong>&#9888;&nbsp; Accounting Equation Imbalance Detected</strong>
                    The balance sheet does not balance. Please review your chart of accounts and journal entries.
                    <table class="bs-recon-rows">
                        <tr><td>Total Assets</td><td>{{ $fmt($visTotalAssets) }}</td></tr>
                        <tr><td>Total Liabilities + Equity</td><td>{{ $fmt($visTotalLiabEquity) }}</td></tr>
                        <tr><td><strong>Difference</strong></td><td><strong>{{ $fmt(abs($equationDiff)) }}</strong></td></tr>
                    </table>
                </div>
            @endif

            @if(abs((float) ($reconciliationReserveDiagnostic ?? 0)) >= 0.01)
                <div class="bs-hidden-debug">
                    <strong>Reconciliation diagnostic only</strong><br>
                    A temporary reconciliation reserve of {{ $fmt((float) ($reconciliationReserveDiagnostic ?? 0)) }}
                    would be needed to force balance, but it has not been posted into Equity.
                    @if(!empty($reconciliationReserveNeedsReview))
                        <div style="margin-top:6px;color:#991b1b;font-weight:700;">
                            Review required: diagnostic exceeds threshold of {{ $fmt((float) ($reconciliationReserveThreshold ?? 0)) }}.
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

            @if(
                abs((float) ($openingBalanceValidation['unposted_customer_opening_balance'] ?? 0)) >= 0.01 ||
                abs((float) ($openingBalanceValidation['unposted_supplier_opening_balance'] ?? 0)) >= 0.01 ||
                abs((float) ($openingBalanceValidation['legacy_inventory_bridge'] ?? 0)) >= 0.01 ||
                $duplicateCustomerRefs->isNotEmpty() ||
                $duplicateSupplierRefs->isNotEmpty() ||
                $imbalancedCustomerRefs->isNotEmpty() ||
                $imbalancedSupplierRefs->isNotEmpty() ||
                $reserveSuspenseDiagnostics->isNotEmpty()
            )
                <details class="bs-hidden-debug no-print" style="margin-top:16px;">
                    <summary>Validation Report</summary>
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

            {{-- Detailed imbalance entries (debug panel) --}}
            @if(isset($imbalancedEntries) && $imbalancedEntries->isNotEmpty() && !$isBalanced)
                <details style="margin-top:16px;">
                    <summary style="cursor:pointer;font-size:0.80rem;color:#64748b;font-weight:600;">
                        Show Imbalanced Journal Entries ({{ $imbalancedEntries->count() }})
                    </summary>
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
    document.querySelectorAll('[data-val]').forEach(function(a) {
        if (a.closest && a.closest('.bs-method-toggle')) {
            a.classList.toggle('active', parseInt(a.dataset.val) === val);
        }
    });
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
