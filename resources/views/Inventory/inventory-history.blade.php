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
                <div class="row align-items-center">
                    <div class="col">
                        <h4 class="fw-bold mb-1" style="color: #4e5d78;">Inventory History</h4>
                        <p class="text-muted mb-0" style="font-size: 12px;">Detailed log of stock movements</p>
                        <div class="mt-2">
                            <span class="badge bg-light border text-primary px-3 py-2">
                                <i class="fas fa-code-branch me-2"></i>
                                Active Branch: {{ $activeBranch['name'] ?? 'Workspace Default' }}
                            </span>
                        </div>
                    </div>
                    <div class="col-auto">
                        <div class="btn-group shadow-sm bg-white">
                            <button onclick="window.print()" class="btn btn-white border btn-sm">
                                <i class="feather-printer me-1"></i> Print
                            </button>
                            <button id="export_pdf_btn" class="btn btn-white border text-danger btn-sm">
                                <i class="feather-file-text me-1"></i> PDF
                            </button>
                            <button id="export_excel_btn" class="btn btn-white border text-success btn-sm">
                                <i class="feather-file me-1"></i> Excel
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <div class="row">
                <div class="col-sm-12">
                    <div class="card border shadow-none">
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
                                            <th class="text-end">Stock Status</th>
                                            <th class="text-end">Stock Value</th>
                                            <th class="text-end">Purchase Price</th>
                                            <th class="text-center no-print">Action</th>
                                        </tr>
                                    </thead>
                                    <tbody style="font-size: 12px;">
                                        @forelse ($inventoryHistories as $history)
                                            @php $isEditableHistoryRow = is_numeric($history->id); @endphp
                                            <tr>
                                                <td class="ps-4 text-muted">{{ $history->id }}</td>
                                                <td>{{ \Carbon\Carbon::parse($history->created_at)->format('d M Y, H:i') }}</td>
                                                
                                                {{-- FIXED: Removed ->product because of the JOIN --}}
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
                                                    {{ $isStockIn ? '+' : '-' }}{{ number_format($history->quantity) }}
                                                </td>
                                                <td class="text-end fw-semibold {{ (float) ($history->running_balance ?? 0) >= 0 ? 'text-primary' : 'text-danger' }}">
                                                    {{ number_format((float) ($history->running_balance ?? 0), 2) }}
                                                </td>
                                                <td class="text-end">
                                                    {{ \App\Support\GeoCurrency::format((float) ($history->stock_value ?? 0), 'NGN', $currencyCode, $currencyLocale) }}
                                                </td>
                                                <td class="text-end">{{ \App\Support\GeoCurrency::format((float) ($history->purchase_price ?? 0), 'NGN', $currencyCode, $currencyLocale) }}</td>
                                                
                                                <td class="text-center no-print">
                                                    <div class="dropdown dropdown-action">
                                                        <a href="#" class="btn-action-icon" data-bs-toggle="dropdown"><i class="fas fa-ellipsis-v"></i></a>
                                                        <div class="dropdown-menu dropdown-menu-right">
                                                            @if($isEditableHistoryRow)
                                                            <a class="dropdown-item edit-btn" href="javascript:void(0);"
                                                                data-id="{{ $history->id }}"
                                                                data-qty="{{ $history->quantity }}"
                                                                data-type="{{ $history->type }}"
                                                                data-bs-toggle="modal" data-bs-target="#edit_history_modal">
                                                                <i class="far fa-edit me-2 text-primary"></i>Edit
                                                            </a>
                                                            <a class="dropdown-item delete-btn" href="javascript:void(0);" 
                                                                data-id="{{ $history->id }}"
                                                                data-bs-toggle="modal" data-bs-target="#delete_history_modal">
                                                                <i class="far fa-trash-alt me-2 text-danger"></i>Delete
                                                            </a>
                                                            @else
                                                            <span class="dropdown-item-text text-muted small">System-generated entry</span>
                                                            @endif
                                                        </div>
                                                    </div>
                                                </td>
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
        .bg-light-success { background-color: #f0fdf4 !important; color: #166534 !important; border: 1px solid #dcfce7; }
        .bg-light-danger { background-color: #fef2f2 !important; color: #991b1b !important; border: 1px solid #fee2e2; }
        .btn-white { background: #fff; color: #666; }
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
