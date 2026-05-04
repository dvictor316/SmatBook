<?php $page = 'balance-sheet'; ?>
@extends('layout.mainlayout')

@section('content')
@php
    $currencyCode = $geoCurrency ?? \App\Support\GeoCurrency::currentCurrency();
    $currencyLocale = $geoCurrencyLocale ?? \App\Support\GeoCurrency::currentLocale();
    $reportCompany = auth()->user()?->company;
    $reportCompanyName = $reportCompany?->company_name
        ?? $reportCompany?->name
        ?? \App\Models\Setting::where('key', 'company_name')->value('value')
        ?? 'SmartProbook';
    $activeBranchName = trim((string) ($activeBranch['name'] ?? ''));
    $fmt = fn ($amount) => \App\Support\GeoCurrency::format((float) $amount, 'NGN', $currencyCode, $currencyLocale);
    $plain = fn ($amount) => number_format((float) $amount, 2);

    $currentAssetsCollection = collect($currentAssets ?? []);
    $fixedAssetsCollection = collect($fixedAssets ?? []);
    $liabilitiesCollection = collect($currentLiabilities ?? []);
    $equityCollection = collect($equity ?? []);

    $isCurrentLiability = function ($account) {
        $subType = strtolower(trim((string) ($account->sub_type ?? '')));
        if ($subType === '') { return true; }
        return str_contains($subType, 'current')
            || str_contains($subType, 'payable')
            || str_contains($subType, 'accrued')
            || str_contains($subType, 'tax')
            || str_contains($subType, 'short');
    };

    $currentLiabilityLines   = $liabilitiesCollection->filter($isCurrentLiability)->values();
    $nonCurrentLiabilityLines = $liabilitiesCollection->reject($isCurrentLiability)->values();

    $groupLines = function ($items, string $fallback) {
        return collect($items)
            ->groupBy(function ($item) use ($fallback) {
                $label = trim((string) ($item->sub_type ?? ''));
                return $label !== '' ? $label : $fallback;
            })
            ->map(fn ($group, $label) => [
                'label' => (string) $label,
                'items' => collect($group)->values(),
                'total' => collect($group)->sum(fn ($item) => (float) ($item->balance ?? 0)),
            ])
            ->values();
    };

    $currentAssetGroups        = $groupLines($currentAssetsCollection, 'Current Asset');
    $fixedAssetGroups          = $groupLines($fixedAssetsCollection, 'Fixed Asset');
    $currentLiabilityGroups    = $groupLines($currentLiabilityLines, 'Current Liability');
    $nonCurrentLiabilityGroups = $groupLines($nonCurrentLiabilityLines, 'Non-current Liability');

    $totalCurrentLiabilities    = $currentLiabilityLines->sum(fn ($item) => (float) ($item->balance ?? 0));
    $totalNonCurrentLiabilities = $nonCurrentLiabilityLines->sum(fn ($item) => (float) ($item->balance ?? 0));
    $totalLiabilitiesAndEquity  = (float) ($totalLiabilities ?? 0) + (float) ($totalEquity ?? 0);
    $difference                 = abs((float) ($totalAssets ?? 0) - $totalLiabilitiesAndEquity);
    $displayNetIncome           = (float) ($netIncome ?? $retainedEarnings ?? 0);

    // Date filter helpers
    $asOfDate   = \Carbon\Carbon::parse($reportDate ?? now());
    $asOfStr    = $asOfDate->toDateString();
    $todayStr   = now()->toDateString();
    $presets    = [
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
@endphp

<style>
/* ── Filter bar ─────────────────────────────────────────── */
.bs-filter-bar {
    background: #fff;
    border: 1px solid #dbe2ea;
    border-radius: 8px;
    padding: 14px 20px;
    margin-bottom: 18px;
    display: flex;
    flex-wrap: wrap;
    gap: 14px;
    align-items: flex-end;
}
.bs-filter-group { display: flex; flex-direction: column; gap: 4px; }
.bs-filter-label { font-size: 0.72rem; font-weight: 600; text-transform: uppercase; letter-spacing: 0.04em; color: #64748b; }
.bs-filter-bar select,
.bs-filter-bar input[type=date] {
    border: 1px solid #cbd5e1;
    border-radius: 6px;
    padding: 7px 10px;
    font-size: 0.85rem;
    color: #0f172a;
    background: #f8fafc;
    outline: none;
    min-width: 140px;
}
.bs-filter-bar select:focus,
.bs-filter-bar input[type=date]:focus { border-color: #6366f1; background: #fff; }
.bs-method-toggle { display: flex; border: 1px solid #cbd5e1; border-radius: 6px; overflow: hidden; }
.bs-method-toggle a {
    padding: 7px 16px;
    font-size: 0.83rem;
    font-weight: 600;
    color: #475569;
    text-decoration: none;
    background: #f8fafc;
}
.bs-method-toggle a.active { background: #6366f1; color: #fff; }
.bs-filter-run {
    background: #6366f1;
    color: #fff;
    border: none;
    border-radius: 6px;
    padding: 7px 20px;
    font-size: 0.83rem;
    font-weight: 700;
    cursor: pointer;
    align-self: flex-end;
}
.bs-filter-run:hover { background: #4f46e5; }

/* ── Report sheet ───────────────────────────────────────── */
.bs-page { max-width: 920px; margin: 0 auto 24px; }

.bs-toolbar {
    display: flex;
    justify-content: flex-end;
    gap: 8px;
    margin-bottom: 12px;
}
.bs-btn {
    border: 1px solid #cbd5e1;
    background: #fff;
    color: #0f172a;
    border-radius: 999px;
    padding: 8px 18px;
    font-size: 0.74rem;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.04em;
    cursor: pointer;
}
.bs-sheet {
    background: #fff;
    border: 1px solid #dbe2ea;
    padding: 30px 34px 26px;
    box-shadow: 0 16px 38px rgba(15,23,42,.06);
}
.bs-header { text-align: center; margin-bottom: 18px; }
.bs-title  { font-size: 1.45rem; font-weight: 600; color: #111827; margin: 0 0 6px; }
.bs-company { font-size: 1.1rem; font-weight: 600; text-transform: uppercase; color: #111827; margin: 0; }
.bs-date, .bs-branch { margin-top: 4px; color: #475569; font-size: 0.88rem; }

.bs-statement { width: 100%; border-collapse: collapse; font-size: 0.91rem; color: #111827; }
.bs-statement col:first-child { width: 74%; }
.bs-statement col:last-child  { width: 26%; }
.bs-statement td { padding: 3px 0; vertical-align: top; }

.bs-head-row td {
    border-top: 2px solid #111827;
    border-bottom: 1px solid #111827;
    padding: 6px 0;
    font-size: 0.76rem;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.05em;
}
.bs-head-row td:last-child, .bs-amount {
    text-align: right;
    font-variant-numeric: tabular-nums;
    white-space: nowrap;
}
.bs-section    td { padding-top: 14px; font-weight: 700; font-size: 0.93rem; color: #1e293b; }
.bs-subsection td { padding-top: 6px; font-weight: 600; padding-left: 10px; color: #334155; }
.bs-group td:first-child  { padding-left: 20px; font-weight: 600; color: #475569; font-size: 0.88rem; }
.bs-line  td:first-child  { padding-left: 36px; }
.bs-total td {
    font-weight: 700;
    border-top: 1px solid #e2e8f0;
    padding-top: 4px;
    background: #f8fafc;
    padding-left: 20px;
}
.bs-grand td {
    font-weight: 700;
    border-top: 3px double #111827;
    padding-top: 6px;
    font-size: 0.95rem;
}
.bs-spacer td { padding: 6px 0; }
.bs-note  {
    margin-top: 14px;
    padding: 9px 14px;
    border: 1px solid #e2e8f0;
    background: #f8fafc;
    color: #475569;
    font-size: 0.81rem;
    border-radius: 4px;
}
.bs-alert {
    margin-top: 12px;
    padding: 9px 14px;
    border: 1px solid #fecaca;
    background: #fef2f2;
    color: #991b1b;
    font-size: 0.81rem;
    border-radius: 4px;
}
.bs-empty td { padding-left: 36px; color: #94a3b8; font-style: italic; font-size: 0.86rem; }

@media (max-width: 767.98px) {
    .bs-sheet { padding: 20px 14px; }
    .bs-filter-bar { flex-direction: column; }
}
@media print {
    .no-print { display: none !important; }
    .bs-page { max-width: none; margin: 0; }
    .bs-sheet { box-shadow: none; border: none; padding: 0; }
    .bs-filter-bar { display: none; }
}
</style>

<div class="page-wrapper">
    <div class="content container-fluid">

        <div class="page-header no-print">
            <div class="content-page-header"><h5>Balance Sheet</h5></div>
        </div>

        @include('Reports.partials.context-strip', [
            'reportLabel' => 'Balance Sheet Report',
            'periodLabel' => 'As at ' . $asOfDate->format('d M Y'),
        ])

        {{-- ── QuickBooks-style filter bar ── --}}
        <form method="GET" action="{{ route('balance-sheet') }}" class="bs-filter-bar no-print" id="bsFilterForm">
            <div class="bs-filter-group">
                <span class="bs-filter-label">Report Period</span>
                <select id="bsPreset" onchange="applyPreset(this.value)">
                    @foreach($presets as $key => $p)
                        <option value="{{ $key }}" data-date="{{ $p['date'] }}" {{ $activePreset === $key ? 'selected' : '' }}>
                            {{ $p['label'] }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="bs-filter-group">
                <span class="bs-filter-label">As of Date</span>
                <input type="date" name="date" id="bsDate" value="{{ $asOfStr }}" onchange="document.getElementById('bsPreset').value='custom'">
            </div>
            <div class="bs-filter-group">
                <span class="bs-filter-label">Accounting Method</span>
                <div class="bs-method-toggle">
                    <a href="#" class="active">Accrual</a>
                    <a href="#">Cash</a>
                </div>
            </div>
            <button type="submit" class="bs-filter-run">Run Report</button>
        </form>

        <div class="bs-page">
            <div class="bs-toolbar no-print">
                <button type="button" onclick="window.print()" class="bs-btn">&#128438; Print</button>
                <button type="button" onclick="window.print()" class="bs-btn">&#128196; PDF</button>
                <button type="button" onclick="exportToExcel()" class="bs-btn">&#128202; Excel</button>
            </div>

            <div class="bs-sheet">
                <div class="bs-header">
                    <h1 class="bs-title">Balance Sheet</h1>
                    <div class="bs-company">{{ $reportCompanyName }}</div>
                    <div class="bs-date">As of {{ $asOfDate->format('F j, Y') }}</div>
                    @if($activeBranchName !== '')
                        <div class="bs-branch">Branch: {{ $activeBranchName }}</div>
                    @endif
                </div>

                <table class="bs-statement">
                    <colgroup><col><col></colgroup>
                    <tbody>
                        <tr class="bs-head-row">
                            <td></td>
                            <td>Total</td>
                        </tr>

                        {{-- ════ ASSETS ════ --}}
                        <tr class="bs-section"><td>Assets</td><td></td></tr>

                        <tr class="bs-subsection"><td>Current Assets</td><td></td></tr>
                        @forelse($currentAssetGroups as $group)
                            @if($loop->count > 1)
                                <tr class="bs-group">
                                    <td>{{ $group['label'] }}</td><td></td>
                                </tr>
                            @endif
                            @foreach($group['items'] as $account)
                                <tr class="bs-line">
                                    <td>{{ $account->name }}</td>
                                    <td class="bs-amount">{{ $fmt($account->balance ?? 0) }}</td>
                                </tr>
                            @endforeach
                            @if($loop->count > 1)
                                <tr class="bs-total">
                                    <td>Total {{ $group['label'] }}</td>
                                    <td class="bs-amount">{{ $fmt($group['total']) }}</td>
                                </tr>
                            @endif
                        @empty
                            <tr class="bs-empty"><td>No current asset accounts found.</td><td></td></tr>
                        @endforelse
                        <tr class="bs-total">
                            <td>Total Current Assets</td>
                            <td class="bs-amount">{{ $fmt($totalCurrentAssets ?? 0) }}</td>
                        </tr>

                        @if($fixedAssetsCollection->isNotEmpty())
                            <tr class="bs-subsection"><td>Fixed Assets</td><td></td></tr>
                            @foreach($fixedAssetGroups as $group)
                                @if($loop->count > 1)
                                    <tr class="bs-group"><td>{{ $group['label'] }}</td><td></td></tr>
                                @endif
                                @foreach($group['items'] as $account)
                                    <tr class="bs-line">
                                        <td>{{ $account->name }}</td>
                                        <td class="bs-amount">{{ $fmt($account->balance ?? 0) }}</td>
                                    </tr>
                                @endforeach
                                @if($loop->count > 1)
                                    <tr class="bs-total">
                                        <td>Total {{ $group['label'] }}</td>
                                        <td class="bs-amount">{{ $fmt($group['total']) }}</td>
                                    </tr>
                                @endif
                            @endforeach
                            <tr class="bs-total">
                                <td>Total Fixed Assets</td>
                                <td class="bs-amount">{{ $fmt($totalFixedAssets ?? 0) }}</td>
                            </tr>
                        @endif

                        <tr class="bs-grand">
                            <td>Total Assets</td>
                            <td class="bs-amount">{{ $fmt($totalAssets ?? 0) }}</td>
                        </tr>

                        <tr class="bs-spacer"><td colspan="2"></td></tr>

                        {{-- ════ LIABILITIES ════ --}}
                        <tr class="bs-section"><td>Liabilities</td><td></td></tr>

                        @if($currentLiabilityLines->isNotEmpty())
                            <tr class="bs-subsection"><td>Current Liabilities</td><td></td></tr>
                            @foreach($currentLiabilityGroups as $group)
                                @if($loop->count > 1)
                                    <tr class="bs-group"><td>{{ $group['label'] }}</td><td></td></tr>
                                @endif
                                @foreach($group['items'] as $account)
                                    <tr class="bs-line">
                                        <td>{{ $account->name }}</td>
                                        <td class="bs-amount">{{ $fmt($account->balance ?? 0) }}</td>
                                    </tr>
                                @endforeach
                                @if($loop->count > 1)
                                    <tr class="bs-total">
                                        <td>Total {{ $group['label'] }}</td>
                                        <td class="bs-amount">{{ $fmt($group['total']) }}</td>
                                    </tr>
                                @endif
                            @endforeach
                            <tr class="bs-total">
                                <td>Total Current Liabilities</td>
                                <td class="bs-amount">{{ $fmt($totalCurrentLiabilities) }}</td>
                            </tr>
                        @endif

                        @if($nonCurrentLiabilityLines->isNotEmpty())
                            <tr class="bs-subsection"><td>Non-current Liabilities</td><td></td></tr>
                            @foreach($nonCurrentLiabilityGroups as $group)
                                @if($loop->count > 1)
                                    <tr class="bs-group"><td>{{ $group['label'] }}</td><td></td></tr>
                                @endif
                                @foreach($group['items'] as $account)
                                    <tr class="bs-line">
                                        <td>{{ $account->name }}</td>
                                        <td class="bs-amount">{{ $fmt($account->balance ?? 0) }}</td>
                                    </tr>
                                @endforeach
                                @if($loop->count > 1)
                                    <tr class="bs-total">
                                        <td>Total {{ $group['label'] }}</td>
                                        <td class="bs-amount">{{ $fmt($group['total']) }}</td>
                                    </tr>
                                @endif
                            @endforeach
                            <tr class="bs-total">
                                <td>Total Non-current Liabilities</td>
                                <td class="bs-amount">{{ $fmt($totalNonCurrentLiabilities) }}</td>
                            </tr>
                        @endif

                        <tr class="bs-grand">
                            <td>Total Liabilities</td>
                            <td class="bs-amount">{{ $fmt((float)($totalLiabilities ?? 0)) }}</td>
                        </tr>

                        <tr class="bs-spacer"><td colspan="2"></td></tr>

                        {{-- ════ EQUITY ════ --}}
                        <tr class="bs-section"><td>Shareholders' Equity</td><td></td></tr>

                        @forelse($equityCollection as $account)
                            <tr class="bs-line">
                                <td>{{ $account->name }}</td>
                                <td class="bs-amount">{{ $fmt($account->balance ?? 0) }}</td>
                            </tr>
                        @empty
                            <tr class="bs-empty"><td>No equity accounts found.</td><td></td></tr>
                        @endforelse

                        <tr class="bs-line">
                            <td>Net Income</td>
                            <td class="bs-amount {{ $displayNetIncome < 0 ? 'text-danger' : '' }}">{{ $fmt($displayNetIncome) }}</td>
                        </tr>
                        <tr class="bs-total">
                            <td>Total Shareholders' Equity</td>
                            <td class="bs-amount">{{ $fmt($totalEquity ?? 0) }}</td>
                        </tr>

                        <tr class="bs-spacer"><td colspan="2"></td></tr>

                        <tr class="bs-grand">
                            <td>Total Liabilities and Shareholders' Equity</td>
                            <td class="bs-amount">{{ $fmt($totalLiabilitiesAndEquity) }}</td>
                        </tr>
                    </tbody>
                </table>

                @if (abs(($ledgerDifference ?? 0)) >= 0.01)
                    <div class="bs-alert no-print">
                        Ledger imbalance: Debits {{ $fmt($ledgerDebits ?? 0) }} &mdash; Credits {{ $fmt($ledgerCredits ?? 0) }} &mdash; Difference {{ $fmt(abs($ledgerDifference ?? 0)) }}
                    </div>
                @endif

                <div class="bs-note">
                    {{ $difference < 0.01 ? '✓ Statement is in balance.' : 'Statement difference: ' . $fmt($difference) . '. Please review your chart of accounts.' }}
                </div>
            </div>
        </div>
    </div>
</div>

<script>
const presets = @json(collect($presets)->map(fn($p) => $p['date']));

function applyPreset(key) {
    if (key !== 'custom' && presets[key]) {
        document.getElementById('bsDate').value = presets[key];
    }
}

function exportToExcel() {
    window.location.href = `{{ route('balance-sheet.export') }}?date={{ $asOfDate->toDateString() }}`;
}
</script>
@endsection

