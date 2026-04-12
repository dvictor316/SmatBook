@extends('layout.mainlayout')

@section('content')
<style>
    .report-container { max-width: 980px; margin: 0 auto; }
    
    .report-header { 
        border-bottom: 1px solid #dbe7f5; 
        padding-bottom: 1rem; 
        margin-bottom: 1.5rem; 
    }
    
    /* Summary Cards */
    .summary-grid {
        display: grid;
        grid-template-columns: repeat(3, 1fr);
        gap: 1rem;
        margin-bottom: 2.5rem;
    }
    .summary-card {
        padding: 1.25rem;
        border-radius: 18px;
        background: #ffffff;
        border: 1px solid #dbe7f5;
        text-align: center;
        box-shadow: 0 10px 24px rgba(15, 23, 42, 0.04);
        min-width: 0;
    }
    .summary-label {
        font-size: 0.72rem;
        font-weight: 700;
        color: #64748b;
        text-transform: uppercase;
        display: block;
        margin-bottom: 4px;
        letter-spacing: 0.08em;
    }
    .summary-amount {
        font-size: clamp(0.88rem, 1.5vw, 1rem);
        font-weight: 800;
        line-height: 1.2;
        font-variant-numeric: tabular-nums;
        overflow-wrap: anywhere;
        word-break: break-word;
    }

    .section-title { 
        background: linear-gradient(135deg, #2563eb 0%, #1d4ed8 100%);
        color: #fff; 
        padding: 0.7rem 1rem; 
        font-size: 0.78rem; 
        font-weight: 800; 
        text-transform: uppercase; 
        margin-top: 1.5rem;
        border-radius: 14px 14px 0 0;
        letter-spacing: 0.06em;
    }
    .cash-table-wrap { overflow-x: auto; -webkit-overflow-scrolling: touch; }
    .cash-table { width: 100%; min-width: 640px; border-collapse: collapse; background: #fff; border: 1px solid #dbe7f5; border-top: 0; border-radius: 0 0 18px 18px; overflow: hidden; }
    .cash-table tr { border-bottom: 1px solid #f1f5f9; }
    .cash-table td { padding: 0.75rem 0.75rem; font-size: 0.9rem; }
    .amt-col { text-align: right; font-weight: 600; font-variant-numeric: tabular-nums; }

    .total-row {
        font-weight: 800;
        border-top: 1.5px solid #1e293b;
        border-bottom: 3.5px double #1e293b;
        padding: 1rem 0.25rem;
        margin-top: 1.5rem;
        font-size: 1rem;
    }

    .cash-report-company {
        font-size: 1.35rem;
        font-weight: 800;
        color: #102a5a;
        letter-spacing: -0.02em;
    }

    .cash-report-kicker {
        letter-spacing: 0.08em !important;
        font-size: 0.74rem !important;
    }

    @media (max-width: 767.98px) {
        .summary-grid {
            grid-template-columns: 1fr;
        }

        .cash-report-company {
            font-size: 1.15rem;
        }
    }

    @media print {
        .no-print { display: none !important; }
        .page-wrapper { padding-top: 0; }
    }
</style>

<div class="page-wrapper">
    <div class="report-container">
        @php
            $cashFlowCompany = auth()->user()?->company;
            $cashFlowCompanyName = $cashFlowCompany?->company_name
                ?? $cashFlowCompany?->name
                ?? \App\Models\Setting::where('key', 'company_name')->value('value')
                ?? 'SmartProbook';
        @endphp
        @include('Reports.partials.context-strip', [
            'reportLabel' => 'Cash Flow Statement',
            'periodLabel' => 'Period: ' . $start->format('d M Y') . ' to ' . $end->format('d M Y'),
        ])
        
        <div class="report-header d-flex justify-content-between align-items-end">
            <div>
                <div class="text-primary fw-bold small text-uppercase cash-report-kicker">Statement of Cash Flows</div>
                <h1 class="cash-report-company m-0">{{ $cashFlowCompanyName }}</h1>
                <div class="text-muted small" style="font-size: 0.82rem;">Period: <strong>{{ $start->format('d M Y') }}</strong> to <strong>{{ $end->format('d M Y') }}</strong></div>
            </div>
            
            <div class="d-flex gap-2 no-print">
                <form action="" method="GET" class="d-flex gap-2 align-items-center me-3">
                    <input type="date" name="start_date" value="{{ $start->toDateString() }}" class="form-control form-control-sm">
                    <input type="date" name="end_date" value="{{ $end->toDateString() }}" class="form-control form-control-sm">
                    <button type="submit" class="btn btn-primary btn-sm">Filter</button>
                </form>
                <a href="{{ route('reports.cash-flow.export', ['start_date' => $start->toDateString(), 'end_date' => $end->toDateString()]) }}" class="btn btn-outline-success btn-sm fw-bold">
                    EXPORT CSV
                </a>
                <button onclick="window.print()" class="btn btn-outline-dark btn-sm fw-bold">PRINT</button>
            </div>
        </div>

        <div class="card border-0 bg-light mb-4 no-print">
            <div class="card-body">
                <h6 class="small fw-bold text-muted text-uppercase mb-3">Cash Trend (Last 6 Months)</h6>
                <canvas id="cashFlowChart" style="max-height: 250px;"></canvas>
            </div>
        </div>

        <div class="summary-grid">
            <div class="summary-card">
                <span class="summary-label">Opening Balance</span>
                <span class="summary-amount">₦{{ number_format($openingBalance, 2) }}</span>
            </div>
            <div class="summary-card">
                <span class="summary-label">Net Movement</span>
                <span class="summary-amount {{ $netCashFlow >= 0 ? 'text-success' : 'text-danger' }}">
                    {{ $netCashFlow >= 0 ? '+' : '' }}₦{{ number_format($netCashFlow, 2) }}
                </span>
            </div>
            <div class="summary-card" style="border-left: 4px solid #2563eb;">
                <span class="summary-label text-primary">Closing Balance</span>
                <span class="summary-amount">₦{{ number_format($closingBalance, 2) }}</span>
            </div>
        </div>

        <div class="section-title">01. Cash Inflows (Receipts)</div>
        <div class="cash-table-wrap">
        <table class="cash-table">
            @forelse($inflows as $inflow)
            <tr>
                <td>{{ \Carbon\Carbon::parse($inflow->transaction_date)->format('d M Y') }}</td>
                <td>{{ $inflow->description ?? $inflow->account->name }}</td>
                <td class="amt-col text-success">+{{ number_format($inflow->debit, 2) }}</td>
            </tr>
            @empty
            <tr><td colspan="3" class="text-center text-muted py-3">No inflows recorded for this period.</td></tr>
            @endforelse
            <tr style="background: #f8fafc; font-weight: 700;">
                <td colspan="2">Total Period Receipts</td>
                <td class="amt-col text-success">₦{{ number_format($totalInflow, 2) }}</td>
            </tr>
        </table>
        </div>
        <div class="d-flex justify-content-end mt-3">
            {{ $inflows->links() }}
        </div>

        <div class="section-title" style="background: linear-gradient(135deg, #64748b 0%, #475569 100%);">02. Cash Outflows (Payments)</div>
        <div class="cash-table-wrap">
        <table class="cash-table">
            @forelse($outflows as $outflow)
            <tr>
                <td>{{ \Carbon\Carbon::parse($outflow->transaction_date)->format('d M Y') }}</td>
                <td>{{ $outflow->description ?? $outflow->account->name }}</td>
                <td class="amt-col text-danger">-{{ number_format($outflow->credit, 2) }}</td>
            </tr>
            @empty
            <tr><td colspan="3" class="text-center text-muted py-3">No outflows recorded for this period.</td></tr>
            @endforelse
            <tr style="background: #f8fafc; font-weight: 700;">
                <td colspan="2">Total Period Payments</td>
                <td class="amt-col text-danger">(₦{{ number_format($totalOutflow, 2) }})</td>
            </tr>
        </table>
        </div>
        <div class="d-flex justify-content-end mt-3">
            {{ $outflows->links() }}
        </div>

        <div class="total-row d-flex justify-content-between">
            <span class="text-uppercase">Net Cash Position at Period End</span>
            <span>₦{{ number_format($closingBalance, 2) }}</span>
        </div>
        
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const ctx = document.getElementById('cashFlowChart').getContext('2d');
        new Chart(ctx, {
            type: 'bar',
            data: {
                labels: {!! json_encode($chartData['labels']) !!},
                datasets: [
                    { label: 'Inflows', data: {!! json_encode($chartData['inflows']) !!}, backgroundColor: '#22c55e' },
                    { label: 'Outflows', data: {!! json_encode($chartData['outflows']) !!}, backgroundColor: '#ef4444' }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: { y: { beginAtZero: true } }
            }
        });
    });
</script>
@endsection
