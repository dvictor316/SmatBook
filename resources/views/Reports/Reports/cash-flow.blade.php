@extends('layout.mainlayout')

@section('content')
<style>
    /* Professional Report Styling */
    .page-wrapper { 
        padding: 2rem; 
        padding-top: 5.5rem; 
        background: #ffffff; 
        min-height: 100vh; 
        font-family: 'Inter', sans-serif; 
        color: #1e293b;
    }
    .report-container { max-width: 1100px; margin: 0 auto; }
    
    .report-header { 
        border-bottom: 2px solid #2563eb; 
        padding-bottom: 1rem; 
        margin-bottom: 2rem; 
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
        border-radius: 8px;
        background: #f8fafc;
        border: 1px solid #e2e8f0;
        text-align: center;
    }
    .summary-label {
        font-size: 0.65rem;
        font-weight: 700;
        color: #64748b;
        text-transform: uppercase;
        display: block;
        margin-bottom: 4px;
    }
    .summary-amount { font-size: 1.1rem; font-weight: 800; }

    /* Tables & Sections */
    .section-title { 
        background: #2563eb; 
        color: #fff; 
        padding: 6px 12px; 
        font-size: 0.75rem; 
        font-weight: 800; 
        text-transform: uppercase; 
        margin-top: 2rem;
    }
    .cash-table { width: 100%; border-collapse: collapse; }
    .cash-table tr { border-bottom: 1px solid #f1f5f9; }
    .cash-table td { padding: 10px 8px; font-size: 0.75rem; }
    .amt-col { text-align: right; font-weight: 600; font-variant-numeric: tabular-nums; }

    .total-row {
        font-weight: 800;
        border-top: 1.5px solid #1e293b;
        border-bottom: 3.5px double #1e293b;
        padding: 12px 8px;
        margin-top: 2rem;
        font-size: 0.85rem;
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
                <div class="text-primary fw-bold small text-uppercase" style="letter-spacing: 0.05em;">Statement of Cash Flows</div>
                <h1 class="h3 fw-800 text-uppercase m-0">{{ $cashFlowCompanyName }}</h1>
                <div class="text-muted small">Period: <strong>{{ $start->format('d M Y') }}</strong> to <strong>{{ $end->format('d M Y') }}</strong></div>
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

        <div class="section-title" style="background: #64748b;">02. Cash Outflows (Payments)</div>
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
