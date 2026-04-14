<?php $page = 'inventory-history'; ?>
@extends('layout.mainlayout')
@section('content')
    @php
        $currencyCode = $geoCurrency ?? \App\Support\GeoCurrency::currentCurrency();
        $currencyLocale = $geoCurrencyLocale ?? \App\Support\GeoCurrency::currentLocale();
    @endphp
    <div class="page-wrapper">
        <div class="content container-fluid">

            {{-- Page Header --}}
            <div class="page-header mb-4 no-print">
                <div class="row g-3 align-items-start">
                    <div class="col-lg-8">
                        <h4 class="fw-bold mb-1" style="color: #4e5d78;">Inventory History</h4>
                        <p class="text-muted mb-0" style="font-size: 12px;">Detailed log of stock movements</p>
                        <div class="mt-2">
                            <span class="badge bg-light border text-primary px-3 py-2 me-2">
                                <i class="fas fa-code-branch me-2"></i>
                                Active Branch: {{ $activeBranch['name'] ?? 'Workspace Default' }}
                            </span>
                            <span class="badge bg-light border text-dark px-3 py-2">
                                <i class="fas fa-boxes me-2"></i>
                                {{ $product->name ?? 'Product' }} ({{ $product->sku ?? 'No SKU' }})
                            </span>
                        </div>
                    </div>
                    <div class="col-lg-4">
                        <div class="d-flex justify-content-lg-end justify-content-start">
                            <div class="btn-group shadow-sm bg-white inventory-export-group">
                                <button onclick="window.print()" class="btn btn-white border btn-sm px-3">
                                    <i class="feather-printer me-1"></i> Print
                                </button>
                                <button id="export_pdf_btn" class="btn btn-white border text-danger btn-sm px-3">
                                    <i class="feather-file-text me-1"></i> PDF
                                </button>
                                <button id="export_excel_btn" class="btn btn-white border text-success btn-sm px-3">
                                    <i class="feather-file me-1"></i> Excel
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="row g-3 mb-4">
                <div class="col-xl-4 col-md-6">
                    <div class="card border shadow-sm h-100">
                        <div class="card-body py-3 px-4 d-flex align-items-center justify-content-between">
                            <div>
                                <p class="mb-1 text-muted small text-uppercase">Current Stock</p>
                                <h5 class="mb-0">{{ number_format($currentStock, 2) }} {{ $product->unit_type ?? '' }}</h5>
                            </div>
                            <span class="badge bg-primary px-3 py-2">Live</span>
                        </div>
                    </div>
                </div>
                <div class="col-xl-4 col-md-6">
                    <div class="card border shadow-sm h-100">
                        <div class="card-body py-3 px-4 d-flex align-items-center justify-content-between">
                            <div>
                                <p class="mb-1 text-muted small text-uppercase">Total Received</p>
                                <h5 class="mb-0">{{ number_format($totalIn, 2) }}</h5>
                            </div>
                            <span class="badge bg-success px-3 py-2">In</span>
                        </div>
                    </div>
                </div>
                <div class="col-xl-4 col-md-6">
                    <div class="card border shadow-sm h-100">
                        <div class="card-body py-3 px-4 d-flex align-items-center justify-content-between">
                            <div>
                                <p class="mb-1 text-muted small text-uppercase">Total Issued</p>
                                <h5 class="mb-0">{{ number_format($totalOut, 2) }}</h5>
                            </div>
                            <span class="badge bg-danger px-3 py-2">Out</span>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card border-0 shadow-sm mb-4">
                <div class="card-body p-4">
                    <div class="row g-4">
                        <div class="col-xl-6">
                            <div class="inventory-section-card h-100">
                                <div class="d-flex align-items-center justify-content-between mb-3">
                                    <h6 class="fw-bold text-dark mb-0">Quantities In</h6>
                                    <span class="badge bg-light text-success border">Inbound</span>
                                </div>
                                <div class="row g-3">
                                    <div class="col-sm-6">
                                        <div class="inventory-metric-card">
                                            <p class="text-muted small mb-1">Total Purchase</p>
                                            <h5 class="mb-0">{{ number_format($totalIn, 2) }} <span class="text-muted metric-unit">{{ $product->unit_type ?? 'pc' }}(s)</span></h5>
                                        </div>
                                    </div>
                                    <div class="col-sm-6">
                                        <div class="inventory-metric-card">
                                            <p class="text-muted small mb-1">Stock Adjustment (In)</p>
                                            <h5 class="mb-0">0.00 <span class="text-muted metric-unit">{{ $product->unit_type ?? 'pc' }}(s)</span></h5>
                                        </div>
                                    </div>
                                    <div class="col-sm-6">
                                        <div class="inventory-metric-card">
                                            <p class="text-muted small mb-1">Reconciliation (In)</p>
                                            <h5 class="mb-0">0.00 <span class="text-muted metric-unit">{{ $product->unit_type ?? 'pc' }}(s)</span></h5>
                                        </div>
                                    </div>
                                    <div class="col-sm-6">
                                        <div class="inventory-metric-card">
                                            <p class="text-muted small mb-1">Opening Stock</p>
                                            <h5 class="mb-0">0.00 <span class="text-muted metric-unit">{{ $product->unit_type ?? 'pc' }}(s)</span></h5>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-xl-6">
                            <div class="inventory-section-card h-100">
                                <div class="d-flex align-items-center justify-content-between mb-3">
                                    <h6 class="fw-bold text-dark mb-0">Quantities Out</h6>
                                    <span class="badge bg-light text-danger border">Outbound</span>
                                </div>
                                <div class="row g-3">
                                    <div class="col-sm-6">
                                        <div class="inventory-metric-card">
                                            <p class="text-muted small mb-1">Total Sold</p>
                                            <h5 class="mb-0">{{ number_format($totalOut, 2) }} <span class="text-muted metric-unit">{{ $product->unit_type ?? 'pc' }}(s)</span></h5>
                                        </div>
                                    </div>
                                    <div class="col-sm-6">
                                        <div class="inventory-metric-card">
                                            <p class="text-muted small mb-1">Stock Adjustment (Out)</p>
                                            <h5 class="mb-0">0.00 <span class="text-muted metric-unit">{{ $product->unit_type ?? 'pc' }}(s)</span></h5>
                                        </div>
                                    </div>
                                    <div class="col-sm-6">
                                        <div class="inventory-metric-card">
                                            <p class="text-muted small mb-1">Reconciliation (Out)</p>
                                            <h5 class="mb-0">0.00 <span class="text-muted metric-unit">{{ $product->unit_type ?? 'pc' }}(s)</span></h5>
                                        </div>
                                    </div>
                                    <div class="col-sm-6">
                                        <div class="inventory-metric-card">
                                            <p class="text-muted small mb-1">Sell Returns</p>
                                            <h5 class="mb-0">0.00 <span class="text-muted metric-unit">{{ $product->unit_type ?? 'pc' }}(s)</span></h5>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="row g-3 mt-1">
                        <div class="col-xl-4 col-md-6">
                            <div class="card border bg-light h-100">
                                <div class="card-body py-3 px-4">
                                    <p class="text-muted small mb-1">Stock Value</p>
                                    <h6 class="fw-bold mb-2">Current Value</h6>
                                    <h4 class="mb-0 text-success">{{ \App\Support\GeoCurrency::format((float) ($currentStock * ($product->purchase_price ?? 0)), 'NGN', $currencyCode, $currencyLocale) }}</h4>
                                </div>
                            </div>
                        </div>
                        <div class="col-xl-4 col-md-6">
                            <div class="card border bg-light h-100">
                                <div class="card-body py-3 px-4">
                                    <p class="text-muted small mb-1">Valuation</p>
                                    <h6 class="fw-bold mb-2">Unit Value</h6>
                                    <h4 class="mb-0 text-info">{{ \App\Support\GeoCurrency::format((float) $product->purchase_price, 'NGN', $currencyCode, $currencyLocale) }}</h4>
                                </div>
                            </div>
                        </div>
                        <div class="col-xl-4 col-md-6">
                            <div class="card border bg-light h-100">
                                <div class="card-body py-3 px-4">
                                    <p class="text-muted small mb-1">Movement</p>
                                    <h6 class="fw-bold mb-2">Net Change</h6>
                                    <h4 class="mb-0 {{ $currentStock >= 0 ? 'text-success' : 'text-danger' }}">{{ $currentStock >= 0 ? '+' : '' }}{{ number_format($currentStock, 2) }}</h4>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="card border-0 shadow-sm">
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-center table-hover mb-0" id="inventoryHistoryTable">
                            <thead style="background: #f9fafb;">
                                <tr class="text-uppercase" style="font-size: 10px; letter-spacing: 0.5px;">
                                    <th class="ps-4">#</th>
                                    <th>Date</th>
                                    <th>Item</th>
                                    <th>Code</th>
                                    <th class="text-center">Type</th>
                                    <th>Reference</th>
                                    <th class="text-end">Quantity</th>
                                    <th class="text-center no-print">Action</th>
                                    <th class="text-end">Stock Status</th>
                                    <th class="text-end">Stock Value</th>
                                    <th class="text-end">Purchase Price</th>
                                </tr>
                            </thead>
                            <tbody style="font-size: 12px;">
                                @forelse ($inventoryHistories as $history)
                                    @php $isEditableHistoryRow = is_numeric($history->id); @endphp
                                    <tr>
                                        <td class="ps-4 text-muted">{{ $history->id }}</td>
                                        <td>{{ \Carbon\Carbon::parse($history->created_at)->format('d M Y, H:i') }}</td>
                                        <td class="fw-bold text-dark">{{ $history->name }}</td>
                                        <td class="text-muted">{{ $history->sku }}</td>
                                        <td class="text-center">
                                            @php $isStockIn = in_array(strtolower($history->type), ['in', 'stock in']); @endphp
                                            <span class="badge {{ $isStockIn ? 'bg-light-success text-success' : 'bg-light-danger text-danger' }} px-2">
                                                {{ $isStockIn ? 'Stock In' : 'Stock Out' }}
                                            </span>
                                        </td>
                                        <td>{{ $history->reference ?? 'System Movement' }}</td>
                                        <td class="text-end fw-bold {{ $isStockIn ? 'text-success' : 'text-danger' }}">
                                            @php
                                                $movementUnitType = strtolower(trim((string) ($history->unit_type ?? 'unit')));
                                                $movementUnitLabel = match ($movementUnitType) {
                                                    'carton' => 'ctn',
                                                    'roll' => 'roll',
                                                    'unit', 'pcs', 'piece', 'pieces', 'sachet' => 'pcs',
                                                    default => $movementUnitType !== '' ? $movementUnitType : 'pcs',
                                                };
                                                $movementQuantity = rtrim(rtrim(number_format((float) ($history->quantity ?? 0), 2), '0'), '.');
                                            @endphp
                                            {{ $isStockIn ? '+' : '-' }}{{ $movementQuantity }}
                                            <span class="text-muted text-uppercase ms-1">{{ $movementUnitLabel }}</span>
                                        </td>
                                        <td class="text-center no-print inventory-action-cell">
                                            @if($isEditableHistoryRow)
                                                <div class="d-inline-flex align-items-center gap-2 flex-wrap justify-content-center">
                                                    <button type="button"
                                                        class="btn btn-sm btn-outline-primary edit-btn inventory-action-btn"
                                                        data-id="{{ $history->id }}"
                                                        data-qty="{{ $history->quantity }}"
                                                        data-type="{{ $history->type }}"
                                                        data-bs-toggle="modal"
                                                        data-bs-target="#edit_history_modal">
                                                        <i class="far fa-edit me-1"></i>Edit
                                                    </button>
                                                    <button type="button"
                                                        class="btn btn-sm btn-outline-danger delete-btn inventory-action-btn"
                                                        data-id="{{ $history->id }}"
                                                        data-bs-toggle="modal"
                                                        data-bs-target="#delete_history_modal">
                                                        <i class="far fa-trash-alt me-1"></i>Delete
                                                    </button>
                                                </div>
                                            @else
                                                <span class="text-muted small">System-generated entry</span>
                                            @endif
                                        </td>
                                        <td class="text-end fw-semibold {{ (float) ($history->running_balance ?? 0) >= 0 ? 'text-primary' : 'text-danger' }}">
                                            {{ number_format((float) ($history->running_balance ?? 0), 2) }}
                                        </td>
                                        <td class="text-end">
                                            {{ \App\Support\GeoCurrency::format((float) ($history->stock_value ?? 0), 'NGN', $currencyCode, $currencyLocale) }}
                                        </td>
                                        <td class="text-end">{{ \App\Support\GeoCurrency::format((float) ($history->purchase_price ?? 0), 'NGN', $currencyCode, $currencyLocale) }}</td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="11" class="text-center p-5 text-muted">
                                            No movement history found for this item.
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Edit Modal --}}
    <div class="modal fade" id="edit_history_modal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Edit Movement Record</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form action="{{ route('inventory.history.update') }}" method="POST">
                    @csrf
                    <div class="modal-body">
                        <input type="hidden" name="id" id="modal_edit_id">
                        <div class="mb-3">
                            <label class="form-label">Adjustment Quantity</label>
                            <input type="number" step="0.01" min="0.01" name="quantity" id="modal_edit_qty" class="form-control" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Movement Type</label>
                            <select name="type" id="modal_edit_type" class="form-control">
                                <option value="in">Stock In</option>
                                <option value="out">Stock Out</option>
                            </select>
                        </div>
                        <small class="text-muted">This updates the history record only. It does not recalculate current stock.</small>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">Update Record</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    {{-- Delete Modal --}}
    <div class="modal fade" id="delete_history_modal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-sm">
            <div class="modal-content text-center p-4">
                <div class="modal-body">
                    <i class="far fa-trash-alt text-danger mb-3" style="font-size: 40px;"></i>
                    <h5>Are you sure?</h5>
                    <p class="text-muted small">This only removes the log entry. It does not change current stock.</p>
                    <form action="{{ route('inventory.history.delete') }}" method="POST">
                        @csrf
                        <input type="hidden" name="id" id="modal_delete_id">
                        <div class="d-grid gap-2 mt-4">
                            <button type="submit" class="btn btn-danger">Delete Log</button>
                            <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <style>


        .button-debug { display: none !important; }
        .bg-light-success { background-color: #f0fdf4 !important; color: #166534 !important; border: 1px solid #dcfce7; }
        .bg-light-danger { background-color: #fef2f2 !important; color: #991b1b !important; border: 1px solid #fee2e2; }
        .btn-white { background: #fff; color: #666; }
        .inventory-export-group .btn { min-width: 110px; }
        .inventory-section-card {
            height: 100%;
            padding: 1.25rem;
            border: 1px solid #e9edf4;
            border-radius: 8px;
            background: #fff;
        }
        .inventory-metric-card {
            height: 100%;
            padding: 1rem 1.1rem;
            border: 1px solid #edf1f7;
            border-radius: 8px;
            background: #f8fafc;
        }
        .metric-unit { font-size: 12px; }
        .inventory-action-cell {
            min-width: 180px;
        }
        .inventory-action-btn {
            min-width: 78px;
            border-radius: 8px;
        }
        .table-responsive { overflow-x: auto; overflow-y: visible; }
        .table { position: relative; }
        tr { position: relative; } 
        .dropdown-action { position: relative; z-index: 100; }
        .dropdown-menu { position: absolute !important; z-index: 1050 !important; min-width: 180px; }
        @media (max-width: 991.98px) {
            .inventory-export-group {
                width: 100%;
            }
            .inventory-export-group .btn {
                flex: 1 1 0;
                min-width: 0;
            }
        }
        @media print { .no-print { display: none !important; } .card { border: none !important; } }
    </style>
@endsection

@push('scripts')
<script src="https://cdnjs.cloudflare.com/ajax/libs/xlsx/0.17.0/xlsx.full.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf-autotable/3.5.23/jspdf.plugin.autotable.min.js"></script>

<script>
    $(document).ready(function() {
        $('.edit-btn').on('click', function() {
            $('#modal_edit_id').val($(this).data('id'));
            $('#modal_edit_qty').val($(this).data('qty'));
            $('#modal_edit_type').val($(this).data('type'));
        });

        $('.delete-btn').on('click', function() {
            $('#modal_delete_id').val($(this).data('id'));
        });

        // PDF Export
        $('#export_pdf_btn').on('click', function() {
            const { jsPDF } = window.jspdf;
            const doc = new jsPDF('l', 'pt', 'a4');
            doc.text("Inventory Movement History", 40, 40);
            doc.autoTable({ 
                html: '#inventoryHistoryTable', 
                startY: 60,
                didParseCell: function(data) {
                    if (data.column.index === 7) { data.cell.styles.fontSize = 0; } // Hide Actions
                }
            });
            doc.save("Inventory_History.pdf");
        });

        // Excel Export
        $('#export_excel_btn').on('click', function() {
            let table = document.getElementById("inventoryHistoryTable");
            let wb = XLSX.utils.table_to_book(table);
            XLSX.writeFile(wb, "Inventory_History.xlsx");
        });
    });
</script>
@endpush
