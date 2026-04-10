<?php $page = 'trial-balance'; ?>
@extends('layout.mainlayout')

@section('content')
<style>
    :root {
        --text-dark: #1e293b;
        --text-light: #94a3b8;
        --brand-blue: #2563eb;
        --bg-alice: #f8fafc;
        --border-faint: #f1f5f9;
        --border-blue: #e0f2fe;
    }

    .report-container {
        max-width: 1280px;
        margin: 0 auto 24px auto;
        background: #fff;
        border: 1px solid #e2e8f0;
        border-radius: 22px;
        box-shadow: 0 16px 34px rgba(15, 23, 42, 0.06);
        padding: 1.25rem;
    }

    .report-header {
        border-bottom: 1px solid #dbe7f5;
        padding-bottom: 1rem;
        margin-bottom: 1.5rem;
    }

    .company-name {
        font-size: 1.2rem;
        font-weight: 800;
        text-transform: none;
        letter-spacing: -0.02em;
        margin: 0;
        color: #102a5a;
    }

    .report-title {
        font-size: 0.7rem;
        font-weight: 800;
        color: var(--brand-blue);
        text-transform: uppercase;
        letter-spacing: 0.12em;
    }

    .filter-card {
        background: #f8fbff;
        border: 1px solid #dbe7f5;
        border-radius: 16px;
        padding: 0.95rem;
        margin-bottom: 1.5rem;
    }

    .summary-grid {
        display: grid;
        grid-template-columns: repeat(3, minmax(0, 1fr));
        gap: 0.85rem;
        margin-bottom: 1.5rem;
    }

    .summary-card {
        padding: 0.9rem 1rem;
        border-radius: 16px;
        background: #ffffff;
        border: 1px solid #dbe7f5;
        box-shadow: 0 10px 24px rgba(15, 23, 42, 0.04);
        min-width: 0;
    }

    .summary-label {
        font-size: 0.66rem;
        font-weight: 700;
        color: var(--text-light);
        text-transform: uppercase;
        margin-bottom: 2px;
        display: block;
        letter-spacing: 0.08em;
    }

    .summary-amount {
        font-size: clamp(0.84rem, 1.35vw, 0.98rem);
        font-weight: 800;
        line-height: 1.2;
        font-variant-numeric: tabular-nums;
        overflow-wrap: anywhere;
        word-break: break-word;
    }

    .table-card {
        border-top: 1px solid #dbe7f5;
        border-radius: 18px;
        overflow: hidden;
        border: 1px solid #dbe7f5;
        background: #fff;
    }

    .table-scroll {
        overflow-x: auto;
        -webkit-overflow-scrolling: touch;
    }

    .table {
        margin-bottom: 0;
        min-width: 680px;
    }

    .table thead th {
        background: #f5f9ff;
        color: #102a5a;
        text-transform: uppercase;
        font-size: 0.68rem;
        font-weight: 800;
        letter-spacing: 0.08em;
        border-bottom: 1px solid #dbe7f5 !important;
        padding: 0.75rem 0.65rem;
    }

    .table tbody td {
        font-size: 0.82rem;
        padding: 0.75rem 0.65rem;
        border-bottom: 1px solid var(--border-faint);
        vertical-align: middle;
    }

    .account-code {
        font-weight: 700;
        color: var(--brand-blue);
        background: #eff6ff;
        padding: 0.25rem 0.5rem;
        border-radius: 999px;
        font-size: 0.66rem;
    }

    .total-row {
        background: transparent !important;
        border-top: 1.5px solid var(--text-dark);
    }

    .total-row td {
        font-weight: 800 !important;
        font-size: 0.86rem !important;
        border-bottom: 3.5px double var(--text-dark) !important; /* Accounting double line */
        padding: 0.85rem 0.65rem !important;
    }

    .btn-export {
        font-size: 0.7rem;
        padding: 0.55rem 0.85rem;
        font-weight: 700;
        text-transform: uppercase;
        border-radius: 999px;
        border: 1px solid #dbe7f5;
        background: white;
        transition: 0.2s;
    }
    
    .btn-export:hover { background: var(--bg-alice); }

    .status-strip {
        margin-top: 1rem;
        padding: 0.65rem 0.8rem;
        font-size: 0.62rem;
        font-weight: 800;
        text-align: center;
        border-radius: 12px;
        letter-spacing: 0.05em;
        text-transform: uppercase;
    }

    .balanced { background: #f0fdf4; color: #166534; border: 1px solid #dcfce7; }
    .unbalanced { background: #fef2f2; color: #991b1b; border: 1px solid #fee2e2; }

    .dt-buttons { display: none !important; }
    @media (max-width: 991.98px) {
        .report-container {
            padding: 1rem;
            border-radius: 18px;
        }
        .summary-grid {
            grid-template-columns: 1fr;
        }
    }
    @media (max-width: 767.98px) {
        .company-name {
            font-size: 1.05rem;
        }
        .table thead th,
        .table tbody td {
            font-size: 0.78rem !important;
        }
        .summary-amount {
            font-size: 0.82rem;
        }
        .export-actions {
            flex-wrap: wrap;
            justify-content: flex-start;
        }
        .table {
            min-width: 620px;
        }
    }
    @media print { .no-print { display: none !important; } .page-wrapper { padding-top: 0; } }
</style>

<div class="page-wrapper">
    <div class="content container-fluid">
        @php
            $reportCompany = auth()->user()?->company;
            $reportCompanyName = $reportCompany?->company_name
                ?? $reportCompany?->name
                ?? \App\Models\Setting::where('key', 'company_name')->value('value')
                ?? 'SmartProbook';
        @endphp
        <div class="page-header">
            <div class="content-page-header">
                <h5>Trial Balance</h5>
            </div>
        </div>
        @include('Reports.partials.context-strip', [
            'reportLabel' => 'Trial Balance Report',
            'periodLabel' => 'Period: ' . $startDate . ' to ' . $endDate,
        ])
        <div class="report-container">
        
        {{-- Header --}}
        <div class="report-header d-flex justify-content-between align-items-end no-print">
            <div>
                <div class="report-title">Financial Audit Report</div>
                <h1 class="company-name">{{ $reportCompanyName }}</h1>
                <div class="text-muted small mt-1" style="font-size: 0.82rem;">
                    Period: {{ $startDate }} — {{ $endDate }}
                    @if(!empty($activeBranch['name'] ?? null))
                        <span class="ms-2">· Branch: {{ $activeBranch['name'] }}</span>
                    @endif
                </div>
            </div>
            <div class="export-actions d-flex gap-1">
                <button onclick="triggerAction(2)" class="btn-export"><i class="feather-printer me-1"></i> Print</button>
                <button onclick="triggerAction(1)" class="btn-export text-danger"><i class="feather-file-text me-1"></i> PDF</button>
                <button onclick="triggerAction(0)" class="btn-export text-success"><i class="feather-download me-1"></i> Excel</button>
            </div>
        </div>

        {{-- Filters --}}
        <div class="filter-card no-print">
            <form action="" method="GET" class="row g-2 align-items-end">
                <div class="col-md-4">
                    <label class="form-label small fw-bold text-muted">Start Date</label>
                    <input type="date" name="start_date" class="form-control form-control-sm" value="{{ $startDate }}">
                </div>
                <div class="col-md-4">
                    <label class="form-label small fw-bold text-muted">End Date</label>
                    <input type="date" name="end_date" class="form-control form-control-sm" value="{{ $endDate }}">
                </div>
                <div class="col-md-2">
                    <button type="submit" class="btn btn-dark btn-sm w-100 fw-bold">Refresh</button>
                </div>
            </form>
        </div>

        {{-- Summary --}}
        <div class="summary-grid">
            <div class="summary-card">
                <span class="summary-label">Total Debits</span>
                <div class="summary-amount text-success">₦{{ number_format($totalDebits, 2) }}</div>
            </div>
            <div class="summary-card">
                <span class="summary-label">Total Credits</span>
                <div class="summary-amount text-danger">₦{{ number_format($totalCredits, 2) }}</div>
            </div>
            <div class="summary-card">
                <span class="summary-label">Balance Difference</span>
                <div class="summary-amount {{ abs($totalDebits - $totalCredits) < 0.01 ? 'text-muted' : 'text-danger' }}">
                    ₦{{ number_format(abs($totalDebits - $totalCredits), 2) }}
                </div>
            </div>
        </div>

        @if (abs(($ledgerDifference ?? 0)) >= 0.01)
            <div class="alert alert-danger mb-3">
                <div class="fw-semibold mb-1">Ledger imbalance detected</div>
                <div>Debits: ₦{{ number_format($ledgerDebits ?? 0, 2) }} · Credits: ₦{{ number_format($ledgerCredits ?? 0, 2) }} · Difference: ₦{{ number_format(abs($ledgerDifference ?? 0), 2) }}</div>
                @if (!empty($imbalancedEntries) && $imbalancedEntries->isNotEmpty())
                    <div class="mt-2 small">Top unbalanced entries:</div>
                    <ul class="mb-0 ps-3 small">
                        @foreach ($imbalancedEntries as $entry)
                            <li>
                                {{ $entry->transaction_type ?? 'Entry' }}
                                · Ref: {{ $entry->reference ?: 'N/A' }}
                                · Related: {{ $entry->related_type ?: 'N/A' }} #{{ $entry->related_id ?: 'N/A' }}
                                · Δ ₦{{ number_format(abs(((float) $entry->total_debit) - ((float) $entry->total_credit)), 2) }}
                            </li>
                        @endforeach
                    </ul>
                @endif
            </div>
        @endif

        {{-- Table --}}
        <div class="table-card">
            <div class="table-scroll">
            <table class="table align-middle" id="trialBalanceTable">
                <thead>
                    <tr>
                        <th class="ps-1">Code</th>
                        <th>Account Description</th>
                        <th>Type</th>
                        <th class="text-end">Debit (₦)</th>
                        <th class="text-end pe-1">Credit (₦)</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($accounts as $account)
                    <tr>
                        <td class="ps-1"><span class="account-code">{{ $account->code }}</span></td>
                        <td class="fw-bold" style="color: var(--text-dark)">{{ $account->name }}</td>
                        <td class="text-muted" style="font-size: 0.65rem;">{{ $account->type }}</td>
                        <td class="text-end fw-bold">{{ $account->debit_balance > 0 ? number_format($account->debit_balance, 2) : '0.00' }}</td>
                        <td class="text-end fw-bold pe-1">{{ $account->credit_balance > 0 ? number_format($account->credit_balance, 2) : '0.00' }}</td>
                    </tr>
                    @empty
                    <tr><td colspan="5" class="text-center py-4">No ledger records found.</td></tr>
                    @endforelse
                </tbody>
                <tfoot>
                    <tr class="total-row">
                        <td colspan="3" class="ps-1">TOTALS</td>
                        <td class="text-end">₦{{ number_format($totalDebits, 2) }}</td>
                        <td class="text-end pe-1">₦{{ number_format($totalCredits, 2) }}</td>
                    </tr>
                </tfoot>
            </table>
            </div>
        </div>

        @php $isBalanced = abs($totalDebits - $totalCredits) < 0.01; @endphp
        <div class="status-strip {{ $isBalanced ? 'balanced' : 'unbalanced' }}">
            {{ $isBalanced ? '✓ Trial Balance is Balanced' : '⚠ Discrepancy Detected in Trial Balance' }}
        </div>
        </div>
    </div>
</div>

{{-- Scripts remain the same for DataTables functionality --}}
<script src="https://code.jquery.com/jquery-3.7.0.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>
<script src="https://cdn.datatables.net/buttons/2.4.2/js/dataTables.buttons.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jszip/3.10.1/jszip.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.1.53/pdfmake.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.1.53/vfs_fonts.js"></script>
<script src="https://cdn.datatables.net/buttons/2.4.2/js/buttons.html5.min.js"></script>
<script src="https://cdn.datatables.net/buttons/2.4.2/js/buttons.print.min.js"></script>

<script>
$(document).ready(function() {
    window.tbTable = $('#trialBalanceTable').DataTable({
        "pageLength": -1,
        "dom": 'Bfrtip',
        "ordering": false,
        "buttons": [
            { extend: 'excelHtml5', className: 'buttons-excel', title: 'Trial Balance', footer: true },
            { 
                extend: 'pdfHtml5', 
                className: 'buttons-pdf', 
                title: 'Trial Balance', 
                footer: true,
                customize: function (doc) {
                    doc.styles.tableHeader.fillColor = '#2563eb';
                    doc.styles.tableFooter.fillColor = '#f8fafc';
                }
            },
            { extend: 'print', className: 'buttons-print', title: 'Trial Balance', footer: true }
        ]
    });
});

function triggerAction(index) {
    if (!window.tbTable) return;
    let buttonClass = index === 0 ? '.buttons-excel' : (index === 1 ? '.buttons-pdf' : '.buttons-print');
    window.tbTable.button(buttonClass).trigger();
}
</script>
@endsection
