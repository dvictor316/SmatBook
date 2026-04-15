@php $page = 'purchases'; @endphp
@extends('layout.mainlayout')

@section('content')
@php
    $purchaseCollection = isset($purchases) && method_exists($purchases, 'getCollection')
        ? $purchases->getCollection()
        : collect($purchases ?? []);
    $currencyCode = $geoCurrency ?? \App\Support\GeoCurrency::currentCurrency();
    $currencyLocale = $geoCurrencyLocale ?? \App\Support\GeoCurrency::currentLocale();
    $totalPurchases = $purchaseCollection->count();
    $totalAmount = (float) $purchaseCollection->sum(fn ($purchase) => (float) ($purchase->resolved_total_amount ?? $purchase->total_amount ?? 0));
    $totalPaid = (float) $purchaseCollection->sum(fn ($purchase) => (float) ($purchase->paid_amount ?? 0));
    $totalOutstanding = max(0, $totalAmount - $totalPaid);
    $openPurchases = $purchaseCollection->filter(function ($purchase) {
        $status = strtolower((string) ($purchase->status ?? 'pending'));
        return !in_array($status, ['paid', 'completed'], true);
    })->count();
@endphp

<style>
    .purchase-report-shell .report-stamp {
        font-size: 0.78rem;
        color: #64748b;
        background: linear-gradient(135deg, #eff6ff, #f8fafc);
        border: 1px solid #dbeafe;
        border-radius: 18px;
        padding: 0.95rem 1rem;
        min-width: 280px;
    }

    .purchase-report-shell .report-stamp span,
    .purchase-report-shell .summary-label,
    .purchase-report-shell .filter-label {
        color: #334155;
        font-weight: 700;
    }

    .purchase-report-shell .summary-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
        gap: 0.85rem;
    }

    .purchase-report-shell .summary-card {
        border: 1px solid #e2e8f0;
        border-radius: 18px;
        background: #fff;
        box-shadow: 0 10px 24px rgba(15, 23, 42, 0.05);
        padding: 1rem 1.05rem;
    }

    .purchase-report-shell .summary-card strong {
        display: block;
        margin-top: 0.3rem;
        color: #0f172a;
        font-size: 1rem;
    }

    .purchase-report-shell .purchase-filter-card,
    .purchase-report-shell .purchase-table-card {
        border: 0;
        border-radius: 22px;
        box-shadow: 0 16px 34px rgba(15, 23, 42, 0.06);
    }

    .purchase-report-shell .purchase-filter-grid {
        display: grid;
        grid-template-columns: minmax(0, 2fr) repeat(2, minmax(0, 0.7fr));
        gap: 0.85rem;
        align-items: end;
    }

    .purchase-report-shell .purchase-filter-grid .form-control,
    .purchase-report-shell .purchase-filter-grid .btn {
        min-height: 46px;
        border-radius: 14px;
    }

    .purchase-report-shell .table thead th {
        background: #f8fafc;
        font-size: 0.73rem;
        text-transform: uppercase;
        letter-spacing: 0.05em;
        color: #334155;
        white-space: nowrap;
    }

    .purchase-report-shell .table tbody td {
        vertical-align: top;
        font-size: 0.9rem;
    }

    .purchase-report-shell .purchase-chip {
        display: inline-flex;
        align-items: center;
        gap: 0.45rem;
        padding: 0.4rem 0.7rem;
        border-radius: 999px;
        background: #eef2ff;
        color: #3730a3;
        font-size: 0.74rem;
        font-weight: 700;
        white-space: nowrap;
    }

    @media (max-width: 767.98px) {
        .purchase-page-actions {
            display: flex;
            flex-wrap: wrap;
            gap: 0.5rem;
            justify-content: flex-start;
        }

        .purchase-page-actions > * {
            flex: 1 1 calc(50% - 0.5rem);
            min-width: 0;
        }

        .purchase-mobile-full {
            flex-basis: 100%;
        }

        .purchase-report-shell .purchase-filter-grid {
            grid-template-columns: 1fr;
        }

        .purchase-report-shell .report-stamp {
            min-width: auto;
        }
    }

    .dt-buttons { display: none !important; }
</style>

<div class="page-wrapper">
    <div class="content container-fluid">
        <div class="page-header">
            <div class="row align-items-center">
                <div class="col">
                    <h3 class="page-title">Purchases</h3>
                    <ul class="breadcrumb">
                        <li class="breadcrumb-item"><a href="{{ url('/') }}">Dashboard</a></li>
                        <li class="breadcrumb-item active">Purchases</li>
                    </ul>
                </div>
                <div class="col-auto purchase-page-actions">
                    <a href="{{ route('purchases.create') }}" class="btn btn-success me-1">
                        <i class="fas fa-plus-circle"></i> New Purchase
                    </a>
                    <div class="dropdown purchase-mobile-full">
                        <button class="btn btn-primary dropdown-toggle w-100" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                            <i class="fas fa-download me-2"></i> Export / Print
                        </button>
                        <ul class="dropdown-menu dropdown-menu-end">
                            <li>
                                <a class="dropdown-item" href="#" id="custom_btn_print">
                                    <i class="fas fa-print me-2 text-secondary"></i> Print Report
                                </a>
                            </li>
                            <li><hr class="dropdown-divider"></li>
                            <li>
                                <a class="dropdown-item" href="#" id="custom_btn_excel">
                                    <i class="far fa-file-excel me-2 text-success"></i> Excel
                                </a>
                            </li>
                            <li>
                                <a class="dropdown-item" href="#" id="custom_btn_pdf">
                                    <i class="far fa-file-pdf me-2 text-danger"></i> PDF
                                </a>
                            </li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>

        <div class="purchase-report-shell">
            <div class="card purchase-filter-card mb-4">
                <div class="card-body">
                    <div class="d-flex flex-column flex-xl-row justify-content-between gap-3 mb-4">
                        <div>
                            <h4 class="mb-1">Supplier Purchases Overview</h4>
                            <p class="text-muted mb-0">Track supplier bills, outstanding balances, and payment status in the same clean layout as customers.</p>
                        </div>
                        <div class="report-stamp">
                            <div><span>Branch:</span> {{ $activeBranch['name'] ?? 'All Recorded Purchases' }}</div>
                            <div><span>Coverage:</span> {{ $purchases->total() ?? $totalPurchases }} purchases</div>
                            <div><span>Generated:</span> {{ now()->format('d M Y, h:i A') }}</div>
                        </div>
                    </div>

                    <div class="summary-grid mb-4">
                        <div class="summary-card">
                            <span class="summary-label"><i class="fas fa-file-invoice-dollar me-2"></i>Total Purchases</span>
                            <strong>{{ number_format($totalPurchases) }}</strong>
                        </div>
                        <div class="summary-card">
                            <span class="summary-label"><i class="fas fa-coins me-2"></i>Total Amount</span>
                            <strong>{{ \App\Support\GeoCurrency::format($totalAmount, 'NGN', $currencyCode, $currencyLocale) }}</strong>
                        </div>
                        <div class="summary-card">
                            <span class="summary-label"><i class="fas fa-check-circle me-2"></i>Total Paid</span>
                            <strong>{{ \App\Support\GeoCurrency::format($totalPaid, 'NGN', $currencyCode, $currencyLocale) }}</strong>
                        </div>
                        <div class="summary-card">
                            <span class="summary-label"><i class="fas fa-exclamation-circle me-2"></i>Outstanding</span>
                            <strong>{{ \App\Support\GeoCurrency::format($totalOutstanding, 'NGN', $currencyCode, $currencyLocale) }}</strong>
                        </div>
                        <div class="summary-card">
                            <span class="summary-label"><i class="fas fa-hourglass-half me-2"></i>Open Bills</span>
                            <strong>{{ number_format($openPurchases) }}</strong>
                        </div>
                    </div>

                    <form action="{{ route('purchase-report') }}" method="GET" class="purchase-filter-grid">
                        <div>
                            <label class="filter-label mb-2">Search Purchases</label>
                            <input type="text" name="search" value="{{ request('search') }}" class="form-control" placeholder="Search by name, SKU, or Purchase No...">
                        </div>
                        <div>
                            <button type="submit" class="btn btn-primary w-100">
                                <i class="fas fa-filter me-1"></i> Filter
                            </button>
                        </div>
                        <div>
                            <a href="{{ route('purchase-report') }}" class="btn btn-secondary w-100">
                                <i class="fas fa-undo me-1"></i> Reset
                            </a>
                        </div>
                    </form>
                </div>
            </div>

            <div class="card purchase-table-card">
                <div class="card-body">
                    <div class="table-responsive" id="printSection">
                        <table class="table table-hover align-middle" id="purchasesTable">
                            <thead>
                                <tr>
                                    <th>#</th>
                                    <th>Purchase No</th>
                                    <th>Supplier</th>
                                    <th>Total</th>
                                    <th>Paid</th>
                                    <th>Status</th>
                                    <th class="no-print text-end">Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                @isset($purchases)
                                    @forelse ($purchases as $purchase)
                                        @php
                                            $status = strtolower((string) ($purchase->status ?? 'pending'));
                                            $statusClass = $status === 'paid' || $status === 'completed'
                                                ? 'bg-success'
                                                : ($status === 'pending' ? 'bg-warning text-dark' : 'bg-secondary');
                                        @endphp
                                        <tr>
                                            <td>{{ ($purchases->currentPage() - 1) * $purchases->perPage() + $loop->iteration }}</td>
                                            <td><strong>{{ $purchase->purchase_no ?? ('PUR-' . $purchase->id) }}</strong></td>
                                            <td>{{ $purchase->supplier?->name ?? $purchase->vendor?->name ?? 'Supplier' }}</td>
                                            <td>{{ \App\Support\GeoCurrency::format((float) ($purchase->resolved_total_amount ?? $purchase->total_amount ?? 0), 'NGN', $currencyCode, $currencyLocale) }}</td>
                                            <td>{{ \App\Support\GeoCurrency::format((float) ($purchase->paid_amount ?? 0), 'NGN', $currencyCode, $currencyLocale) }}</td>
                                            <td><span class="badge {{ $statusClass }}">{{ ucfirst($status) }}</span></td>
                                            <td class="text-end no-print">
                                                <div class="d-flex justify-content-end gap-2 flex-wrap">
                                                    <a href="{{ route('purchases.show', $purchase->id) }}" class="btn btn-sm btn-info text-white d-inline-flex align-items-center gap-1">
                                                        <i class="far fa-eye"></i>
                                                        <span class="d-none d-md-inline">View</span>
                                                    </a>
                                                    <a href="{{ route('purchases.edit', $purchase->id) }}" class="btn btn-sm btn-primary d-inline-flex align-items-center gap-1">
                                                        <i class="far fa-edit"></i>
                                                        <span class="d-none d-md-inline">Edit</span>
                                                    </a>
                                                    <form action="{{ route('finance.recurring.from-purchase', $purchase->id) }}" method="POST">
                                                        @csrf
                                                        <button type="submit" class="btn btn-sm btn-outline-primary d-inline-flex align-items-center gap-1">
                                                            <i class="far fa-clock"></i>
                                                            <span class="d-none d-md-inline">Template</span>
                                                        </button>
                                                    </form>
                                                    <form action="{{ route('finance.approvals.from-purchase', $purchase->id) }}" method="POST">
                                                        @csrf
                                                        <button type="submit" class="btn btn-sm btn-outline-warning d-inline-flex align-items-center gap-1">
                                                            <i class="far fa-paper-plane"></i>
                                                            <span class="d-none d-md-inline">Approval</span>
                                                        </button>
                                                    </form>
                                                    @if (!in_array($status, ['paid', 'completed'], true))
                                                        <form action="{{ route('purchases.mark-paid', $purchase->id) }}" method="POST">
                                                            @csrf
                                                            <button type="submit" class="btn btn-sm btn-success d-inline-flex align-items-center gap-1">
                                                                <i class="fas fa-check-circle"></i>
                                                                <span class="d-none d-md-inline">Mark Paid</span>
                                                            </button>
                                                        </form>
                                                    @endif
                                                </div>
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="8" class="text-center py-4">No data matches your filter.</td>
                                        </tr>
                                    @endforelse
                                @else
                                    <tr>
                                        <td colspan="8" class="text-center py-5 text-danger">
                                            <strong>Error: Purchase data not loaded.</strong>
                                        </td>
                                    </tr>
                                @endisset
                            </tbody>
                        </table>

                        @if(isset($purchases) && method_exists($purchases, 'links'))
                            <div class="d-flex justify-content-center mt-4 no-print">
                                {{ $purchases->appends(request()->query())->links() }}
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

@endsection

@push('scripts')
{{-- 
    REQUIRED SCRIPTS FOR DATATABLES EXPORT 
    (JSZip for Excel, PDFMake for PDF) 
--}}
<script src="https://cdnjs.cloudflare.com/ajax/libs/jszip/3.10.1/jszip.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.1.53/pdfmake.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.1.53/vfs_fonts.js"></script>
<script src="https://cdn.datatables.net/buttons/2.4.2/js/dataTables.buttons.min.js"></script>
<script src="https://cdn.datatables.net/buttons/2.4.2/js/buttons.html5.min.js"></script>
<script src="https://cdn.datatables.net/buttons/2.4.2/js/buttons.print.min.js"></script>

<script>
    $(document).ready(function() {
        // Destroy existing if re-initializing
        if ($.fn.DataTable.isDataTable('#purchasesTable')) {
            $('#purchasesTable').DataTable().destroy();
        }

        // Initialize DataTable with Export Capabilities
        var table = $('#purchasesTable').DataTable({
            dom: 'Bfrtip', // Defines where buttons are placed (B = buttons)
            paging: false, // We use Laravel pagination
            searching: false, // We use custom search
            info: false,
            buttons: [
                {
                    extend: 'excelHtml5',
                    className: 'dt-btn-excel', // Hidden hook class
                    title: 'Purchases Report',
                    exportOptions: {
                        columns: ':not(.no-print)' // Exclude Action column
                    }
                },
                {
                    extend: 'pdfHtml5',
                    className: 'dt-btn-pdf', // Hidden hook class
                    title: 'Purchases Report',
                    exportOptions: {
                        columns: ':not(.no-print)'
                    }
                },
                {
                    extend: 'print',
                    className: 'dt-btn-print', // Hidden hook class
                    title: 'Purchases Report',
                    customize: function (win) {
                        $(win.document.body).css('font-size', '10pt');
                        $(win.document.body).find('table')
                            .addClass('compact')
                            .css('font-size', 'inherit');
                    },
                    exportOptions: {
                        columns: ':not(.no-print)'
                    }
                }
            ]
        });

        // --- WIRE CUSTOM BUTTONS TO DATATABLES API --- //
        
        // Excel Trigger
        $('#custom_btn_excel').on('click', function(e) {
            e.preventDefault();
            table.button('.dt-btn-excel').trigger();
        });

        // PDF Trigger
        $('#custom_btn_pdf').on('click', function(e) {
            e.preventDefault();
            table.button('.dt-btn-pdf').trigger();
        });

        // Print Trigger
        $('#custom_btn_print').on('click', function(e) {
            e.preventDefault();
            table.button('.dt-btn-print').trigger();
        });
    });
</script>
@endpush
