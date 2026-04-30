<?php $page = 'chart-of-accounts-report'; ?>
@extends('layout.mainlayout')

@section('content')
@php
    $currencyCode = $geoCurrency ?? \App\Support\GeoCurrency::currentCurrency();
    $currencyLocale = $geoCurrencyLocale ?? \App\Support\GeoCurrency::currentLocale();
    $currencySymbol = $geoCurrencySymbol ?? \App\Support\GeoCurrency::currentSymbol();
    $reportCompany = auth()->user()?->company;
    $reportCompanyName = $reportCompany?->company_name
        ?? $reportCompany?->name
        ?? \App\Models\Setting::where('key', 'company_name')->value('value')
        ?? 'SmartProbook';
@endphp

<style>
    .coa-report-shell { max-width: 1180px; margin: 0 auto 24px; }
    .coa-report-header { display: flex; justify-content: space-between; gap: 16px; align-items: flex-end; margin-bottom: 16px; }
    .coa-report-title { font-size: 1.4rem; font-weight: 800; color: #0f172a; margin: 0; }
    .coa-report-meta { color: #64748b; font-size: 0.85rem; }
    .coa-export-btn { border: 1px solid #d1d5db; background: #fff; border-radius: 999px; padding: 8px 14px; font-size: 0.78rem; font-weight: 700; }
    .coa-summary-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); gap: 12px; margin-bottom: 16px; }
    .coa-summary-card { background: #fff; border: 1px solid #e5e7eb; border-radius: 14px; padding: 14px 16px; }
    .coa-summary-label { display: block; font-size: 0.72rem; color: #64748b; text-transform: uppercase; letter-spacing: 0.06em; margin-bottom: 6px; }
    .coa-summary-value { font-size: 1rem; font-weight: 800; color: #0f172a; }
    .coa-filter-card, .coa-table-card { background: #fff; border: 1px solid #e5e7eb; border-radius: 16px; box-shadow: 0 8px 24px rgba(15, 23, 42, 0.04); }
    .coa-filter-card { margin-bottom: 16px; }
    .coa-table-card { overflow: hidden; }
    .coa-table-card .table { margin-bottom: 0; }
    .coa-table-card thead th { background: #f8fafc; font-size: 0.74rem; text-transform: uppercase; letter-spacing: 0.06em; color: #475569; }
    .coa-code { font-family: 'Courier New', monospace; font-size: 0.78rem; background: #f1f5f9; color: #334155; padding: 3px 8px; border-radius: 6px; }
    .coa-status { display: inline-flex; align-items: center; border-radius: 999px; padding: 4px 10px; font-size: 0.72rem; font-weight: 700; }
    .coa-status-active { background: #dcfce7; color: #166534; }
    .coa-status-inactive { background: #fee2e2; color: #991b1b; }
    @media print { .no-print { display: none !important; } .page-wrapper { padding-top: 0; } .coa-filter-card, .coa-table-card, .coa-summary-card { box-shadow: none; } }
</style>

<div class="page-wrapper">
    <div class="content container-fluid">
        <div class="page-header no-print">
            <div class="content-page-header">
                <h5>Chart of Accounts</h5>
            </div>
        </div>

        @include('Reports.partials.context-strip', [
            'reportLabel' => 'Chart of Accounts Report',
            'periodLabel' => request('start_date') && request('end_date')
                ? 'Period: ' . request('start_date') . ' to ' . request('end_date')
                : 'Current account register',
        ])

        <div class="coa-report-shell">
            <div class="coa-report-header no-print">
                <div>
                    <h1 class="coa-report-title">Chart of Accounts Report</h1>
                    <div class="coa-report-meta">{{ $reportCompanyName }} @if(!empty($activeBranch['name'] ?? null)) · Branch: {{ $activeBranch['name'] }} @endif</div>
                </div>
                <div class="d-flex gap-2">
                    <button type="button" onclick="window.print()" class="coa-export-btn">Print</button>
                    <button type="button" onclick="coaTable.button('.buttons-excel').trigger()" class="coa-export-btn">Excel</button>
                </div>
            </div>

            <div class="coa-summary-grid">
                <div class="coa-summary-card">
                    <span class="coa-summary-label">Total Accounts</span>
                    <div class="coa-summary-value">{{ number_format($summary['total_accounts'] ?? 0) }}</div>
                </div>
                <div class="coa-summary-card">
                    <span class="coa-summary-label">Active Accounts</span>
                    <div class="coa-summary-value">{{ number_format($summary['active_accounts'] ?? 0) }}</div>
                </div>
                <div class="coa-summary-card">
                    <span class="coa-summary-label">Inactive Accounts</span>
                    <div class="coa-summary-value">{{ number_format($summary['inactive_accounts'] ?? 0) }}</div>
                </div>
                <div class="coa-summary-card">
                    <span class="coa-summary-label">Combined Balance</span>
                    <div class="coa-summary-value">{{ \App\Support\GeoCurrency::format($summary['total_balance'] ?? 0, 'NGN', $currencyCode, $currencyLocale) }}</div>
                </div>
            </div>

            <div class="coa-filter-card no-print">
                <div class="card-body">
                    <form method="GET" action="{{ route('reports.chart-of-accounts') }}" class="row g-3 align-items-end">
                        <div class="col-md-3">
                            <label class="form-label small fw-bold text-muted">Search</label>
                            <input type="text" name="search" class="form-control form-control-sm" value="{{ request('search') }}" placeholder="Code, name, subtype...">
                        </div>
                        <div class="col-md-2">
                            <label class="form-label small fw-bold text-muted">Type</label>
                            <select name="type" class="form-select form-select-sm">
                                <option value="">All Types</option>
                                @foreach($accountTypes as $type)
                                    <option value="{{ $type }}" @selected(request('type') === $type)>{{ $type }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-2">
                            <label class="form-label small fw-bold text-muted">Status</label>
                            <select name="status" class="form-select form-select-sm">
                                <option value="">All Status</option>
                                <option value="active" @selected(request('status') === 'active')>Active</option>
                                <option value="inactive" @selected(request('status') === 'inactive')>Inactive</option>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <label class="form-label small fw-bold text-muted">Start Date</label>
                            <input type="date" name="start_date" class="form-control form-control-sm" value="{{ request('start_date') }}">
                        </div>
                        <div class="col-md-2">
                            <label class="form-label small fw-bold text-muted">End Date</label>
                            <input type="date" name="end_date" class="form-control form-control-sm" value="{{ request('end_date') }}">
                        </div>
                        <div class="col-md-1 d-flex gap-2">
                            <button type="submit" class="btn btn-dark btn-sm w-100">Run</button>
                        </div>
                    </form>
                </div>
            </div>

            <div class="coa-table-card">
                <div class="table-responsive">
                    <table class="table align-middle" id="chartAccountsReportTable">
                        <thead>
                            <tr>
                                <th>Code</th>
                                <th>Account</th>
                                <th>Type</th>
                                <th>Subtype</th>
                                <th>Status</th>
                                <th class="text-end">Opening ({{ $currencySymbol }})</th>
                                <th class="text-end">Current ({{ $currencySymbol }})</th>
                                <th class="text-end">Transactions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($accounts as $account)
                                <tr>
                                    <td><span class="coa-code">{{ $account->code ?: '—' }}</span></td>
                                    <td>
                                        <div class="fw-semibold text-dark">{{ $account->name }}</div>
                                        @if(!empty($account->description))
                                            <div class="text-muted small">{{ $account->description }}</div>
                                        @endif
                                    </td>
                                    <td>{{ $account->type }}</td>
                                    <td>{{ $account->sub_type ?: '—' }}</td>
                                    <td>
                                        @php $isActive = (bool) ($account->is_active ?? true); @endphp
                                        <span class="coa-status {{ $isActive ? 'coa-status-active' : 'coa-status-inactive' }}">{{ $isActive ? 'Active' : 'Inactive' }}</span>
                                    </td>
                                    <td class="text-end">{{ number_format((float) ($account->opening_balance ?? 0), 2) }}</td>
                                    <td class="text-end fw-semibold">{{ number_format((float) ($account->current_balance ?? 0), 2) }}</td>
                                    <td class="text-end">{{ number_format((int) ($account->transactions_count ?? 0)) }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="8" class="text-center py-4 text-muted">No chart of accounts records matched the selected filters.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.7.0.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>
<script src="https://cdn.datatables.net/buttons/2.4.2/js/dataTables.buttons.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jszip/3.10.1/jszip.min.js"></script>
<script src="https://cdn.datatables.net/buttons/2.4.2/js/buttons.html5.min.js"></script>

<script>
let coaTable;
$(document).ready(function() {
    coaTable = $('#chartAccountsReportTable').DataTable({
        pageLength: -1,
        ordering: false,
        dom: 'Bfrtip',
        buttons: [
            { extend: 'excelHtml5', className: 'buttons-excel', title: 'Chart of Accounts Report' }
        ]
    });
});
</script>
@endsection