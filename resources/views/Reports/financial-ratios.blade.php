@extends('layout.app')

@section('title', 'Financial Ratios')

@section('content')
<div class="content container-fluid">
    <div class="page-header">
        <div class="row align-items-center">
            <div class="col">
                <h3 class="page-title">Financial Ratios</h3>
                <ul class="breadcrumb">
                    <li class="breadcrumb-item"><a href="{{ route('home') }}">Dashboard</a></li>
                    <li class="breadcrumb-item">Reports</li>
                    <li class="breadcrumb-item active">Financial Ratios</li>
                </ul>
            </div>
            <div class="col-auto">
                <form method="GET" class="d-flex gap-2">
                    <select name="year" class="form-select form-select-sm">
                        @for($y = date('Y'); $y >= date('Y') - 4; $y--)
                            <option value="{{ $y }}" @selected(($year ?? date('Y')) == $y)>{{ $y }}</option>
                        @endfor
                    </select>
                    <select name="period" class="form-select form-select-sm">
                        <option value="annual" @selected(($period ?? 'annual') === 'annual')>Annual</option>
                        @foreach(['Q1','Q2','Q3','Q4'] as $q)
                            <option value="{{ strtolower($q) }}" @selected(($period ?? '') === strtolower($q))>{{ $q }}</option>
                        @endforeach
                    </select>
                    <button class="btn btn-primary btn-sm">Apply</button>
                </form>
            </div>
        </div>
    </div>

    <div class="row">
        {{-- Liquidity Ratios --}}
        <div class="col-12 mb-3">
            <h5 class="text-muted fw-semibold">Liquidity Ratios</h5>
        </div>
        <div class="col-md-3">
            <div class="card text-center">
                <div class="card-body">
                    <h6 class="card-subtitle text-muted mb-2">Current Ratio</h6>
                    <h3 class="mb-0">{{ number_format($ratios['current_ratio'] ?? 0, 2) }}</h3>
                    <small class="text-muted">Current Assets / Current Liabilities</small>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card text-center">
                <div class="card-body">
                    <h6 class="card-subtitle text-muted mb-2">Quick Ratio</h6>
                    <h3 class="mb-0">{{ number_format($ratios['quick_ratio'] ?? 0, 2) }}</h3>
                    <small class="text-muted">(Current Assets − Inventory) / Current Liabilities</small>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card text-center">
                <div class="card-body">
                    <h6 class="card-subtitle text-muted mb-2">Cash Ratio</h6>
                    <h3 class="mb-0">{{ number_format($ratios['cash_ratio'] ?? 0, 2) }}</h3>
                    <small class="text-muted">Cash &amp; Equivalents / Current Liabilities</small>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card text-center">
                <div class="card-body">
                    <h6 class="card-subtitle text-muted mb-2">Working Capital</h6>
                    <h3 class="mb-0">{{ number_format($ratios['working_capital'] ?? 0, 2) }}</h3>
                    <small class="text-muted">Current Assets − Current Liabilities</small>
                </div>
            </div>
        </div>

        {{-- Profitability Ratios --}}
        <div class="col-12 mt-4 mb-3">
            <h5 class="text-muted fw-semibold">Profitability Ratios</h5>
        </div>
        <div class="col-md-3">
            <div class="card text-center">
                <div class="card-body">
                    <h6 class="card-subtitle text-muted mb-2">Gross Profit Margin</h6>
                    <h3 class="mb-0">{{ number_format($ratios['gross_profit_margin'] ?? 0, 1) }}%</h3>
                    <small class="text-muted">Gross Profit / Revenue</small>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card text-center">
                <div class="card-body">
                    <h6 class="card-subtitle text-muted mb-2">Net Profit Margin</h6>
                    <h3 class="mb-0">{{ number_format($ratios['net_profit_margin'] ?? 0, 1) }}%</h3>
                    <small class="text-muted">Net Income / Revenue</small>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card text-center">
                <div class="card-body">
                    <h6 class="card-subtitle text-muted mb-2">Return on Assets</h6>
                    <h3 class="mb-0">{{ number_format($ratios['return_on_assets'] ?? 0, 1) }}%</h3>
                    <small class="text-muted">Net Income / Total Assets</small>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card text-center">
                <div class="card-body">
                    <h6 class="card-subtitle text-muted mb-2">Return on Equity</h6>
                    <h3 class="mb-0">{{ number_format($ratios['return_on_equity'] ?? 0, 1) }}%</h3>
                    <small class="text-muted">Net Income / Shareholders' Equity</small>
                </div>
            </div>
        </div>

        {{-- Leverage / Solvency Ratios --}}
        <div class="col-12 mt-4 mb-3">
            <h5 class="text-muted fw-semibold">Leverage Ratios</h5>
        </div>
        <div class="col-md-3">
            <div class="card text-center">
                <div class="card-body">
                    <h6 class="card-subtitle text-muted mb-2">Debt to Equity</h6>
                    <h3 class="mb-0">{{ number_format($ratios['debt_to_equity'] ?? 0, 2) }}</h3>
                    <small class="text-muted">Total Debt / Equity</small>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card text-center">
                <div class="card-body">
                    <h6 class="card-subtitle text-muted mb-2">Debt Ratio</h6>
                    <h3 class="mb-0">{{ number_format($ratios['debt_ratio'] ?? 0, 2) }}</h3>
                    <small class="text-muted">Total Liabilities / Total Assets</small>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card text-center">
                <div class="card-body">
                    <h6 class="card-subtitle text-muted mb-2">Interest Coverage</h6>
                    <h3 class="mb-0">{{ number_format($ratios['interest_coverage'] ?? 0, 2) }}</h3>
                    <small class="text-muted">EBIT / Interest Expense</small>
                </div>
            </div>
        </div>

        {{-- Efficiency Ratios --}}
        <div class="col-12 mt-4 mb-3">
            <h5 class="text-muted fw-semibold">Efficiency Ratios</h5>
        </div>
        <div class="col-md-3">
            <div class="card text-center">
                <div class="card-body">
                    <h6 class="card-subtitle text-muted mb-2">Inventory Turnover</h6>
                    <h3 class="mb-0">{{ number_format($ratios['inventory_turnover'] ?? 0, 2) }}</h3>
                    <small class="text-muted">COGS / Avg Inventory</small>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card text-center">
                <div class="card-body">
                    <h6 class="card-subtitle text-muted mb-2">Receivables Turnover</h6>
                    <h3 class="mb-0">{{ number_format($ratios['receivables_turnover'] ?? 0, 2) }}</h3>
                    <small class="text-muted">Revenue / Avg Receivables</small>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card text-center">
                <div class="card-body">
                    <h6 class="card-subtitle text-muted mb-2">Days Sales Outstanding</h6>
                    <h3 class="mb-0">{{ number_format($ratios['days_sales_outstanding'] ?? 0, 0) }} days</h3>
                    <small class="text-muted">365 / Receivables Turnover</small>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card text-center">
                <div class="card-body">
                    <h6 class="card-subtitle text-muted mb-2">Asset Turnover</h6>
                    <h3 class="mb-0">{{ number_format($ratios['asset_turnover'] ?? 0, 2) }}</h3>
                    <small class="text-muted">Revenue / Total Assets</small>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
