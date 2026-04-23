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
        if ($subType === '') {
            return true;
        }

        return str_contains($subType, 'current')
            || str_contains($subType, 'payable')
            || str_contains($subType, 'accrued')
            || str_contains($subType, 'tax')
            || str_contains($subType, 'short');
    };

    $currentLiabilityLines = $liabilitiesCollection->filter($isCurrentLiability)->values();
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

    $currentAssetGroups = $groupLines($currentAssetsCollection, 'Current Assets');
    $fixedAssetGroups = $groupLines($fixedAssetsCollection, 'Fixed Assets');
    $currentLiabilityGroups = $groupLines($currentLiabilityLines, 'Current Liabilities');
    $nonCurrentLiabilityGroups = $groupLines($nonCurrentLiabilityLines, 'Non-current Liabilities');

    $totalCurrentLiabilities = $currentLiabilityLines->sum(fn ($item) => (float) ($item->balance ?? 0));
    $totalNonCurrentLiabilities = $nonCurrentLiabilityLines->sum(fn ($item) => (float) ($item->balance ?? 0));
    $totalLiabilitiesAndEquity = (float) ($totalLiabilities ?? 0) + (float) ($totalEquity ?? 0);
    $difference = abs((float) ($totalAssets ?? 0) - $totalLiabilitiesAndEquity);
    $displayNetIncome = (float) ($netIncome ?? $retainedEarnings ?? 0);
@endphp

<style>
    .bs-page {
        max-width: 980px;
        margin: 0 auto 24px;
    }

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
        padding: 10px 16px;
        font-size: 0.74rem;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.04em;
    }

    .bs-sheet {
        background: #fff;
        border: 1px solid #dbe2ea;
        padding: 30px 34px 26px;
        box-shadow: 0 16px 38px rgba(15, 23, 42, 0.06);
    }

    .bs-header {
        text-align: center;
        margin-bottom: 18px;
    }

    .bs-title {
        font-size: 1.55rem;
        font-weight: 500;
        color: #111827;
        margin: 0 0 10px;
    }

    .bs-company {
        font-size: 1.15rem;
        font-weight: 500;
        text-transform: uppercase;
        color: #111827;
        margin: 0;
    }

    .bs-date,
    .bs-branch {
        margin-top: 6px;
        color: #475569;
        font-size: 0.92rem;
    }

    .bs-statement {
        width: 100%;
        border-collapse: collapse;
        font-size: 0.92rem;
        color: #111827;
    }

    .bs-statement col:first-child { width: 74%; }
    .bs-statement col:last-child { width: 26%; }

    .bs-statement td {
        padding: 3px 0;
        vertical-align: top;
    }

    .bs-head-row td {
        border-top: 2px solid #111827;
        border-bottom: 1px solid #111827;
        padding-top: 6px;
        padding-bottom: 6px;
        font-size: 0.76rem;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.05em;
    }

    .bs-head-row td:last-child,
    .bs-amount {
        text-align: right;
        font-variant-numeric: tabular-nums;
        white-space: nowrap;
    }

    .bs-section td {
        padding-top: 12px;
        font-weight: 500;
    }

    .bs-subsection td {
        padding-top: 4px;
        font-weight: 500;
    }

    .bs-group td:first-child {
        padding-left: 16px;
    }

    .bs-line td:first-child {
        padding-left: 32px;
    }

    .bs-total td {
        font-weight: 700;
        border-top: 1px solid #cbd5e1;
        padding-top: 4px;
    }

    .bs-grand td {
        font-weight: 700;
        border-top: 3px double #111827;
        padding-top: 5px;
    }

    .bs-spacer td {
        padding: 4px 0;
    }

    .bs-note {
        margin-top: 16px;
        padding: 10px 14px;
        border: 1px solid #e2e8f0;
        background: #f8fafc;
        color: #475569;
        font-size: 0.82rem;
    }

    .bs-alert {
        margin-top: 14px;
        padding: 10px 14px;
        border: 1px solid #fecaca;
        background: #fef2f2;
        color: #991b1b;
        font-size: 0.82rem;
    }

    .bs-empty td:first-child {
        padding-left: 16px;
        color: #64748b;
        font-style: italic;
    }

    @media (max-width: 767.98px) {
        .bs-sheet {
            padding: 20px 16px;
        }

        .bs-title {
            font-size: 1.3rem;
        }

        .bs-company {
            font-size: 1rem;
        }
    }

    @media print {
        .no-print {
            display: none !important;
        }

        .bs-page {
            max-width: none;
            margin: 0;
        }

        .bs-sheet {
            box-shadow: none;
            border: none;
            padding: 0;
        }
    }
</style>

<div class="page-wrapper">
    <div class="content container-fluid">
        <div class="page-header no-print">
            <div class="content-page-header">
                <h5>Balance Sheet</h5>
            </div>
        </div>

        @include('Reports.partials.context-strip', [
            'reportLabel' => 'Balance Sheet Report',
            'periodLabel' => 'As at ' . \Carbon\Carbon::parse($reportDate ?? now())->format('d M Y'),
        ])

        <div class="bs-page">
            <div class="bs-toolbar no-print">
                <button type="button" onclick="window.print()" class="bs-btn">Print</button>
                <button type="button" onclick="exportToPDF()" class="bs-btn">PDF</button>
                <button type="button" onclick="exportToExcel()" class="bs-btn">Excel</button>
            </div>

            <div class="bs-sheet">
                <div class="bs-header">
                    <h1 class="bs-title">Balance Sheet</h1>
                    <div class="bs-company">{{ $reportCompanyName }}</div>
                    <div class="bs-date">As of {{ \Carbon\Carbon::parse($reportDate ?? now())->format('d M, Y') }}</div>
                    @if($activeBranchName !== '')
                        <div class="bs-branch">Branch: {{ $activeBranchName }}</div>
                    @endif
                </div>

                <table class="bs-statement">
                    <colgroup>
                        <col>
                        <col>
                    </colgroup>
                    <tbody>
                        <tr class="bs-head-row">
                            <td></td>
                            <td>Total</td>
                        </tr>

                        <tr class="bs-section">
                            <td>Assets</td>
                            <td></td>
                        </tr>

                        <tr class="bs-subsection">
                            <td>Current Assets</td>
                            <td></td>
                        </tr>
                        @forelse($currentAssetGroups as $group)
                            <tr class="bs-group">
                                <td>{{ $group['label'] }}</td>
                                <td></td>
                            </tr>
                            @foreach($group['items'] as $account)
                                <tr class="bs-line">
                                    <td>{{ $account->name }}</td>
                                    <td class="bs-amount">{{ $fmt($account->balance ?? 0) }}</td>
                                </tr>
                            @endforeach
                            <tr class="bs-total">
                                <td>Total for {{ $group['label'] }}</td>
                                <td class="bs-amount">{{ $fmt($group['total']) }}</td>
                            </tr>
                        @empty
                            <tr class="bs-empty">
                                <td>No current asset accounts found for the selected date.</td>
                                <td></td>
                            </tr>
                        @endforelse
                        <tr class="bs-total">
                            <td>Total for Current Assets</td>
                            <td class="bs-amount">{{ $fmt($totalCurrentAssets ?? 0) }}</td>
                        </tr>

                        @if($fixedAssetsCollection->isNotEmpty())
                            <tr class="bs-subsection">
                                <td>Fixed Assets</td>
                                <td></td>
                            </tr>
                            @foreach($fixedAssetGroups as $group)
                                <tr class="bs-group">
                                    <td>{{ $group['label'] }}</td>
                                    <td></td>
                                </tr>
                                @foreach($group['items'] as $account)
                                    <tr class="bs-line">
                                        <td>{{ $account->name }}</td>
                                        <td class="bs-amount">{{ $fmt($account->balance ?? 0) }}</td>
                                    </tr>
                                @endforeach
                                <tr class="bs-total">
                                    <td>Total for {{ $group['label'] }}</td>
                                    <td class="bs-amount">{{ $fmt($group['total']) }}</td>
                                </tr>
                            @endforeach
                            <tr class="bs-total">
                                <td>Total for Fixed Assets</td>
                                <td class="bs-amount">{{ $fmt($totalFixedAssets ?? 0) }}</td>
                            </tr>
                        @endif

                        <tr class="bs-grand">
                            <td>Total for Assets</td>
                            <td class="bs-amount">{{ $fmt($totalAssets ?? 0) }}</td>
                        </tr>

                        <tr class="bs-spacer"><td colspan="2"></td></tr>

                        <tr class="bs-section">
                            <td>Liabilities and Shareholder's Equity</td>
                            <td></td>
                        </tr>

                        <tr class="bs-subsection">
                            <td>Current Liabilities</td>
                            <td></td>
                        </tr>
                        @forelse($currentLiabilityGroups as $group)
                            <tr class="bs-group">
                                <td>{{ $group['label'] }}</td>
                                <td></td>
                            </tr>
                            @foreach($group['items'] as $account)
                                <tr class="bs-line">
                                    <td>{{ $account->name }}</td>
                                    <td class="bs-amount">{{ $fmt($account->balance ?? 0) }}</td>
                                </tr>
                            @endforeach
                            <tr class="bs-total">
                                <td>Total for {{ $group['label'] }}</td>
                                <td class="bs-amount">{{ $fmt($group['total']) }}</td>
                            </tr>
                        @empty
                            <tr class="bs-empty">
                                <td>No current liability accounts found for the selected date.</td>
                                <td></td>
                            </tr>
                        @endforelse
                        <tr class="bs-total">
                            <td>Total for Current Liabilities</td>
                            <td class="bs-amount">{{ $fmt($totalCurrentLiabilities) }}</td>
                        </tr>

                        <tr class="bs-subsection">
                            <td>Non-current Liabilities</td>
                            <td></td>
                        </tr>
                        @forelse($nonCurrentLiabilityGroups as $group)
                            <tr class="bs-group">
                                <td>{{ $group['label'] }}</td>
                                <td></td>
                            </tr>
                            @foreach($group['items'] as $account)
                                <tr class="bs-line">
                                    <td>{{ $account->name }}</td>
                                    <td class="bs-amount">{{ $fmt($account->balance ?? 0) }}</td>
                                </tr>
                            @endforeach
                            <tr class="bs-total">
                                <td>Total for {{ $group['label'] }}</td>
                                <td class="bs-amount">{{ $fmt($group['total']) }}</td>
                            </tr>
                        @empty
                            <tr class="bs-empty">
                                <td>No non-current liability accounts found for the selected date.</td>
                                <td></td>
                            </tr>
                        @endforelse
                        <tr class="bs-total">
                            <td>Total for Non-current Liabilities</td>
                            <td class="bs-amount">{{ $fmt($totalNonCurrentLiabilities) }}</td>
                        </tr>

                        <tr class="bs-subsection">
                            <td>Shareholders' Equity</td>
                            <td></td>
                        </tr>
                        @forelse($equityCollection as $account)
                            <tr class="bs-line">
                                <td>{{ $account->name }}</td>
                                <td class="bs-amount">{{ $fmt($account->balance ?? 0) }}</td>
                            </tr>
                        @empty
                            <tr class="bs-empty">
                                <td>No equity accounts found for the selected date.</td>
                                <td></td>
                            </tr>
                        @endforelse
                        <tr class="bs-line">
                            <td>Net Income</td>
                            <td class="bs-amount">{{ $fmt($displayNetIncome) }}</td>
                        </tr>
                        <tr class="bs-total">
                            <td>Total for Shareholders' Equity</td>
                            <td class="bs-amount">{{ $fmt($totalEquity ?? 0) }}</td>
                        </tr>

                        <tr class="bs-grand">
                            <td>Total for Liabilities and Shareholder's Equity</td>
                            <td class="bs-amount">{{ $fmt($totalLiabilitiesAndEquity) }}</td>
                        </tr>
                    </tbody>
                </table>

                @if (abs(($ledgerDifference ?? 0)) >= 0.01)
                    <div class="bs-alert no-print">
                        Ledger imbalance detected. Debits {{ $fmt($ledgerDebits ?? 0) }}, Credits {{ $fmt($ledgerCredits ?? 0) }}, Difference {{ $fmt(abs($ledgerDifference ?? 0)) }}.
                    </div>
                @endif

                <div class="bs-note">
                    {{ $difference < 0.01 ? 'Statement is in balance.' : 'Statement difference: ' . $fmt($difference) . '.' }}
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function exportToPDF() { window.print(); }
function exportToExcel() {
    window.location.href = `{{ route("balance-sheet.export") }}?date={{ \Carbon\Carbon::parse($reportDate ?? now())->toDateString() }}`;
}
</script>
@endsection
