@extends('layout.mainlayout')

@section('page-title', 'Custom Report')

@section('content')
<div class="sb-shell page-content-wrapper">
<div class="container-fluid py-4">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h4 class="mb-0">Custom Report Builder</h4>
        <button onclick="window.print()" class="btn btn-sm btn-outline-dark"><i class="fas fa-print me-1"></i>Print</button>
    </div>

    <div class="row g-3">
        <div class="col-lg-6">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white"><h6 class="mb-0">Status Summary</h6></div>
                <div class="table-responsive">
                    <table class="table mb-0">
                        <thead><tr><th>Status</th><th>Count</th><th class="text-end">Amount</th></tr></thead>
                        <tbody>
                        @forelse(($statusSummary ?? collect()) as $row)
                            <tr>
                                <td>{{ ucfirst($row->status ?? 'N/A') }}</td>
                                <td>{{ (int)$row->total }}</td>
                                <td class="text-end">₦{{ number_format((float)$row->amount_total, 2) }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="3" class="text-center text-muted py-4">No data.</td></tr>
                        @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div class="col-lg-6">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white"><h6 class="mb-0">Billing Cycle Summary</h6></div>
                <div class="table-responsive">
                    <table class="table mb-0">
                        <thead><tr><th>Cycle</th><th>Count</th><th class="text-end">Amount</th></tr></thead>
                        <tbody>
                        @forelse(($cycleSummary ?? collect()) as $row)
                            <tr>
                                <td>{{ ucfirst($row->billing_cycle ?? 'N/A') }}</td>
                                <td>{{ (int)$row->total }}</td>
                                <td class="text-end">₦{{ number_format((float)$row->amount_total, 2) }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="3" class="text-center text-muted py-4">No data.</td></tr>
                        @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
</div>
@endsection
