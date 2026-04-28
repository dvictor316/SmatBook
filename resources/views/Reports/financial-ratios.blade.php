@extends('layout.mainlayout')

@section('content')
    <style>
        .ratio-summary-card {
            border: 0;
            border-radius: 20px;
            box-shadow: 0 12px 28px rgba(15, 23, 42, 0.06);
        }

        .ratio-summary-card .card-body {
            padding: 1.15rem 1.2rem;
        }

        .ratio-section-title {
            font-size: 0.82rem;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: 0.08em;
            color: #64748b;
            margin-bottom: 0.9rem;
        }

        .ratio-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            gap: 1rem;
            flex-wrap: wrap;
        }

        .ratio-filter-form {
            display: flex;
            gap: 0.75rem;
            flex-wrap: wrap;
            align-items: end;
        }

        .ratio-filter-form .form-group {
            min-width: 140px;
        }

        @media (max-width: 767.98px) {
            .ratio-filter-form,
            .ratio-filter-form .form-group,
            .ratio-filter-form .btn {
                width: 100%;
            }
        }
    </style>

    <div class="page-wrapper">
        <div class="content container-fluid">
            <div class="page-header">
                <div class="content-page-header ratio-header">
                    <div>
                        <h5>Financial Ratios</h5>
                        <p class="text-muted mb-0">Monitor liquidity, profitability, leverage, and efficiency for the selected reporting period.</p>
                    </div>
                    <form method="GET" action="{{ route('reports.financial-ratios') }}" class="ratio-filter-form">
                        <div class="form-group">
                            <label class="form-label">Period</label>
                            <select name="period" class="form-select">
                                <option value="ytd" @selected(($period ?? 'ytd') === 'ytd')>Year to Date</option>
                                <option value="monthly" @selected(($period ?? '') === 'monthly')>This Month</option>
                                <option value="quarterly" @selected(($period ?? '') === 'quarterly')>This Quarter</option>
                                <option value="annual" @selected(($period ?? '') === 'annual')>Annual</option>
                            </select>
                        </div>
                        <button type="submit" class="btn btn-primary">Apply</button>
                    </form>
                </div>
            </div>

            <div class="row">
                <div class="col-12">
                    <div class="ratio-section-title">Liquidity Ratios</div>
                </div>
                <div class="col-xl-3 col-md-6">
                    <div class="card ratio-summary-card text-center">
                        <div class="card-body">
                            <h6 class="card-subtitle text-muted mb-2">Current Ratio</h6>
                            <h3 class="mb-1">{{ number_format((float) ($ratios['current_ratio'] ?? 0), 2) }}</h3>
                            <small class="text-muted">Current Assets / Current Liabilities</small>
                        </div>
                    </div>
                </div>
                <div class="col-xl-3 col-md-6">
                    <div class="card ratio-summary-card text-center">
                        <div class="card-body">
                            <h6 class="card-subtitle text-muted mb-2">Quick Ratio</h6>
                            <h3 class="mb-1">{{ number_format((float) ($ratios['quick_ratio'] ?? 0), 2) }}</h3>
                            <small class="text-muted">(Assets - Inventory) / Current Liabilities</small>
                        </div>
                    </div>
                </div>
                <div class="col-xl-3 col-md-6">
                    <div class="card ratio-summary-card text-center">
                        <div class="card-body">
                            <h6 class="card-subtitle text-muted mb-2">Cash Ratio</h6>
                            <h3 class="mb-1">{{ number_format((float) ($ratios['cash_ratio'] ?? 0), 2) }}</h3>
                            <small class="text-muted">Cash / Current Liabilities</small>
                        </div>
                    </div>
                </div>
                <div class="col-xl-3 col-md-6">
                    <div class="card ratio-summary-card text-center">
                        <div class="card-body">
                            <h6 class="card-subtitle text-muted mb-2">Working Capital</h6>
                            <h3 class="mb-1">{{ number_format((float) ($ratios['working_capital'] ?? 0), 2) }}</h3>
                            <small class="text-muted">Current Assets - Current Liabilities</small>
                        </div>
                    </div>
                </div>

                <div class="col-12 mt-4">
                    <div class="ratio-section-title">Profitability Ratios</div>
                </div>
                <div class="col-xl-3 col-md-6">
                    <div class="card ratio-summary-card text-center">
                        <div class="card-body">
                            <h6 class="card-subtitle text-muted mb-2">Gross Margin</h6>
                            <h3 class="mb-1">{{ number_format((float) ($ratios['gross_margin'] ?? 0), 1) }}%</h3>
                            <small class="text-muted">Gross Profit / Revenue</small>
                        </div>
                    </div>
                </div>
                <div class="col-xl-3 col-md-6">
                    <div class="card ratio-summary-card text-center">
                        <div class="card-body">
                            <h6 class="card-subtitle text-muted mb-2">Net Margin</h6>
                            <h3 class="mb-1">{{ number_format((float) ($ratios['net_margin'] ?? 0), 1) }}%</h3>
                            <small class="text-muted">Net Income / Revenue</small>
                        </div>
                    </div>
                </div>
                <div class="col-xl-3 col-md-6">
                    <div class="card ratio-summary-card text-center">
                        <div class="card-body">
                            <h6 class="card-subtitle text-muted mb-2">ROA</h6>
                            <h3 class="mb-1">{{ number_format((float) ($ratios['roa'] ?? 0), 1) }}%</h3>
                            <small class="text-muted">Return on Assets</small>
                        </div>
                    </div>
                </div>
                <div class="col-xl-3 col-md-6">
                    <div class="card ratio-summary-card text-center">
                        <div class="card-body">
                            <h6 class="card-subtitle text-muted mb-2">ROE</h6>
                            <h3 class="mb-1">{{ number_format((float) ($ratios['roe'] ?? 0), 1) }}%</h3>
                            <small class="text-muted">Return on Equity</small>
                        </div>
                    </div>
                </div>

                <div class="col-12 mt-4">
                    <div class="ratio-section-title">Leverage Ratios</div>
                </div>
                <div class="col-xl-3 col-md-6">
                    <div class="card ratio-summary-card text-center">
                        <div class="card-body">
                            <h6 class="card-subtitle text-muted mb-2">Debt to Equity</h6>
                            <h3 class="mb-1">{{ number_format((float) ($ratios['debt_to_equity'] ?? 0), 2) }}</h3>
                            <small class="text-muted">Debt / Equity</small>
                        </div>
                    </div>
                </div>
                <div class="col-xl-3 col-md-6">
                    <div class="card ratio-summary-card text-center">
                        <div class="card-body">
                            <h6 class="card-subtitle text-muted mb-2">Debt Ratio</h6>
                            <h3 class="mb-1">{{ number_format((float) ($ratios['debt_ratio'] ?? 0), 2) }}</h3>
                            <small class="text-muted">Liabilities / Assets</small>
                        </div>
                    </div>
                </div>
                <div class="col-xl-3 col-md-6">
                    <div class="card ratio-summary-card text-center">
                        <div class="card-body">
                            <h6 class="card-subtitle text-muted mb-2">Interest Coverage</h6>
                            <h3 class="mb-1">{{ number_format((float) ($ratios['interest_coverage'] ?? 0), 2) }}</h3>
                            <small class="text-muted">EBIT / Interest Expense</small>
                        </div>
                    </div>
                </div>
                <div class="col-xl-3 col-md-6">
                    <div class="card ratio-summary-card text-center">
                        <div class="card-body">
                            <h6 class="card-subtitle text-muted mb-2">Equity Multiplier</h6>
                            <h3 class="mb-1">{{ number_format((float) ($ratios['equity_multiplier'] ?? 0), 2) }}</h3>
                            <small class="text-muted">Assets / Equity</small>
                        </div>
                    </div>
                </div>

                <div class="col-12 mt-4">
                    <div class="ratio-section-title">Efficiency Ratios</div>
                </div>
                <div class="col-xl-3 col-md-6">
                    <div class="card ratio-summary-card text-center">
                        <div class="card-body">
                            <h6 class="card-subtitle text-muted mb-2">Inventory Turnover</h6>
                            <h3 class="mb-1">{{ number_format((float) ($ratios['inventory_turnover'] ?? 0), 2) }}</h3>
                            <small class="text-muted">COGS / Inventory</small>
                        </div>
                    </div>
                </div>
                <div class="col-xl-3 col-md-6">
                    <div class="card ratio-summary-card text-center">
                        <div class="card-body">
                            <h6 class="card-subtitle text-muted mb-2">DSO</h6>
                            <h3 class="mb-1">{{ number_format((float) ($ratios['dso'] ?? 0), 1) }} days</h3>
                            <small class="text-muted">Days Sales Outstanding</small>
                        </div>
                    </div>
                </div>
                <div class="col-xl-3 col-md-6">
                    <div class="card ratio-summary-card text-center">
                        <div class="card-body">
                            <h6 class="card-subtitle text-muted mb-2">A/P Days</h6>
                            <h3 class="mb-1">{{ number_format((float) ($ratios['ap_days'] ?? 0), 1) }} days</h3>
                            <small class="text-muted">Average Payables Days</small>
                        </div>
                    </div>
                </div>
                <div class="col-xl-3 col-md-6">
                    <div class="card ratio-summary-card text-center">
                        <div class="card-body">
                            <h6 class="card-subtitle text-muted mb-2">Asset Turnover</h6>
                            <h3 class="mb-1">{{ number_format((float) ($ratios['asset_turnover'] ?? 0), 2) }}</h3>
                            <small class="text-muted">Revenue / Total Assets</small>
                        </div>
                    </div>
                </div>

                <div class="col-12 mt-4">
                    <div class="card ratio-summary-card">
                        <div class="card-body">
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <h6 class="mb-1">Revenue</h6>
                                    <div class="text-muted">{{ number_format((float) ($revenue ?? 0), 2) }}</div>
                                </div>
                                <div class="col-md-6">
                                    <h6 class="mb-1">Net Income</h6>
                                    <div class="text-muted">{{ number_format((float) ($netIncome ?? 0), 2) }}</div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
