<?php $page = 'inventory-damages'; ?>
@extends('layout.mainlayout')

@section('content')
@php
    $currencyCode = $geoCurrency ?? \App\Support\GeoCurrency::currentCurrency();
    $currencyLocale = $geoCurrencyLocale ?? \App\Support\GeoCurrency::currentLocale();
@endphp
<div class="page-wrapper">
    <div class="content container-fluid">
        <div class="page-header mb-3 no-print">
            <div class="row align-items-center g-3">
                <div class="col">
                    <h4 class="fw-bold mb-1 report-page-title">Stock Damage Register</h4>
                    <p class="text-muted mb-0 report-page-subtitle">Track expired, spoiled, broken and written-off perishable stock.</p>
                </div>
                <div class="col-auto d-flex gap-2">
                    @if(Route::has('inventory.damage.store'))
                        <button type="button" class="btn btn-danger report-action-btn" data-bs-toggle="modal" data-bs-target="#recordDamageFromReportModal">
                            <i class="fas fa-triangle-exclamation me-1"></i> Record Damage
                        </button>
                    @endif
                    <button onclick="window.print()" class="btn btn-white border report-action-btn">
                        <i class="feather-printer me-1"></i> Print
                    </button>
                    <a href="{{ route('inventory.damages.export', array_merge(['format' => 'pdf'], request()->query())) }}" class="btn btn-outline-danger report-action-btn">
                        <i class="fas fa-file-pdf me-1"></i> PDF
                    </a>
                    <a href="{{ route('inventory.damages.export', array_merge(['format' => 'xlsx'], request()->query())) }}" class="btn btn-outline-success report-action-btn">
                        <i class="fas fa-file-excel me-1"></i> Excel
                    </a>
                </div>
            </div>
        </div>

        @include('Reports.partials.context-strip', [
            'reportLabel' => 'Stock Damage Register',
        ])

        <div class="row g-3 mb-3">
            <div class="col-md-4">
                <div class="card border shadow-none mb-0 damage-metric-card">
                    <div class="card-body p-3">
                        <p class="text-muted mb-1 fw-bold damage-metric-label">Damage Entries</p>
                        <h4 class="fw-bold text-primary mb-0">{{ number_format($damages->total()) }}</h4>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card border shadow-none mb-0 damage-metric-card">
                    <div class="card-body p-3">
                        <p class="text-muted mb-1 fw-bold damage-metric-label">Units Damaged</p>
                        <h4 class="fw-bold text-warning mb-0">{{ number_format($totalDamagedQty, 2) }}</h4>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card border-0 shadow-sm mb-0 damage-metric-card damage-metric-card--loss">
                    <div class="card-body p-3">
                        <p class="mb-1 fw-bold damage-metric-label text-white">Estimated Cost Lost</p>
                        <h4 class="fw-bold text-white mb-0">{{ \App\Support\GeoCurrency::format($totalDamageValue, 'NGN', $currencyCode, $currencyLocale) }}</h4>
                    </div>
                </div>
            </div>
        </div>

        <div class="card shadow-none border mb-3 no-print">
            <div class="card-body p-2">
                <form action="{{ route('inventory.damages') }}" method="GET">
                    <div class="row gx-2 align-items-end">
                        <div class="col-md-4">
                            <label class="damage-filter-label">Search</label>
                            <input type="text" name="q" class="form-control form-control-sm border-0 bg-light" value="{{ $search }}" placeholder="Product, SKU, reason or note">
                        </div>
                        <div class="col-md-3">
                            <label class="damage-filter-label">From Date</label>
                            <input type="date" name="from_date" class="form-control form-control-sm border-0 bg-light" value="{{ $fromDate }}">
                        </div>
                        <div class="col-md-3">
                            <label class="damage-filter-label">To Date</label>
                            <input type="date" name="to_date" class="form-control form-control-sm border-0 bg-light" value="{{ $toDate }}">
                        </div>
                        <div class="col-md-2 d-grid">
                            <button type="submit" class="btn btn-primary btn-sm fw-bold">Filter</button>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        <div class="card border shadow-none overflow-hidden">
            <div class="px-3 py-3 border-bottom bg-white">
                <h5 class="mb-1 fw-bold text-dark">Damage History</h5>
                <div class="text-muted small">Recorded damage entries for the selected criteria.</div>
            </div>
            <div class="table-responsive">
                <table class="table table-sm mb-0 damage-table">
                    <thead>
                        <tr>
                            <th class="ps-3">Date</th>
                            <th>Product</th>
                            <th>SKU</th>
                            <th>Branch</th>
                            <th>Reason</th>
                            <th>Notes</th>
                            <th class="text-end">Qty</th>
                            <th class="pe-3 text-end">Cost Value</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($damages as $damage)
                            @php
                                $lineValue = (float) ($damage->quantity ?? 0) * (float) ($damage->purchase_price ?? 0);
                                $reason = trim(str_replace('Damaged Stock -', '', (string) ($damage->reference ?? 'Damage')));
                            @endphp
                            <tr>
                                <td class="ps-3">{{ \Carbon\Carbon::parse($damage->created_at)->format('d M Y, H:i') }}</td>
                                <td class="fw-bold text-dark">{{ $damage->product_name }}</td>
                                <td class="text-muted">{{ $damage->sku ?: 'N/A' }}</td>
                                <td>{{ $damage->branch_name ?: ($activeBranch['name'] ?? 'Workspace Default') }}</td>
                                <td><span class="badge bg-warning text-dark">{{ $reason !== '' ? $reason : 'Damage' }}</span></td>
                                <td class="text-muted">{{ $damage->remarks ?: '-' }}</td>
                                <td class="text-end fw-bold text-warning">{{ number_format((float) $damage->quantity, 2) }}</td>
                                <td class="pe-3 text-end fw-bold text-danger">{{ \App\Support\GeoCurrency::format($lineValue, 'NGN', $currencyCode, $currencyLocale) }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="text-center py-5 text-muted">No damaged stock has been recorded for the selected criteria.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            @if($damages->hasPages())
                <div class="p-3 border-top no-print">
                    {{ $damages->links('pagination::bootstrap-5') }}
                </div>
            @endif
        </div>

        <div class="card border shadow-none overflow-hidden mt-3">
            <div class="px-3 py-3 border-bottom bg-white">
                <h5 class="mb-1 fw-bold text-dark">Stock Status After Damages</h5>
                <div class="text-muted small">Shows each inventory item, damaged quantity, and balance currently left in store.</div>
            </div>
            <div class="table-responsive">
                <table class="table table-sm mb-0 damage-table">
                    <thead>
                        <tr>
                            <th class="ps-3">Product</th>
                            <th>SKU</th>
                            <th class="text-end">Stock Before Damage</th>
                            <th class="text-end">Damaged Qty</th>
                            <th class="text-end">Balance In Store</th>
                            <th class="pe-3 text-end">Damage Value</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse(($stockStatusRows ?? collect()) as $row)
                            <tr>
                                <td class="ps-3 fw-bold text-dark">{{ $row->product_name }}</td>
                                <td class="text-muted">{{ $row->sku ?: 'N/A' }}</td>
                                <td class="text-end">{{ number_format((float) $row->stock_before_damage, 2) }}</td>
                                <td class="text-end fw-bold text-warning">{{ number_format((float) $row->damaged_qty, 2) }}</td>
                                <td class="text-end fw-bold text-success">{{ number_format((float) $row->current_stock, 2) }}</td>
                                <td class="pe-3 text-end fw-bold text-danger">{{ \App\Support\GeoCurrency::format((float) $row->damage_value, 'NGN', $currencyCode, $currencyLocale) }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="text-center py-5 text-muted">No inventory stock status is available for the selected criteria.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

@if(Route::has('inventory.damage.store'))
<div class="modal fade" id="recordDamageFromReportModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0">
            <form method="POST" action="{{ route('inventory.damage.store') }}">
                @csrf
                <div class="modal-header bg-light">
                    <h5 class="modal-title"><i class="fas fa-triangle-exclamation me-2 text-warning"></i>Record Damaged Stock</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body p-4">
                    @if(($activeBranch['scope'] ?? 'branch') === 'all')
                        <div class="alert alert-warning mb-3">
                            Select a specific branch before recording damaged stock.
                        </div>
                    @endif
                    <div class="mb-3">
                        <label class="form-label fw-bold">Find Product</label>
                        <input type="text" class="form-control" id="damage-product-search" placeholder="Search product name or SKU">
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold">Product *</label>
                        <select name="product_id" class="form-select" id="damage-product-select-report" required>
                            <option value="">Select product</option>
                            @foreach(($damageProducts ?? collect()) as $product)
                                @php $availableStock = (float) ($product->active_branch_stock ?? $product->stock ?? $product->stock_quantity ?? 0); @endphp
                                <option value="{{ $product->id }}"
                                    data-search="{{ strtolower(trim(($product->name ?? '') . ' ' . ($product->sku ?? ''))) }}"
                                    data-stock="{{ $availableStock }}">
                                    {{ $product->name }}{{ $product->sku ? ' (' . $product->sku . ')' : '' }} - Stock: {{ number_format($availableStock, 2) }}
                                </option>
                            @endforeach
                        </select>
                        <div class="small text-muted mt-2" id="damage-stock-note-report">Choose an item from inventory to record its damaged quantity.</div>
                    </div>
                    <div class="row">
                        <div class="col-md-4 mb-3">
                            <label class="form-label fw-bold">Damage Date *</label>
                            <input type="date" name="damage_date" class="form-control" value="{{ now()->toDateString() }}" required>
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label fw-bold">Damaged Quantity *</label>
                            <input type="number" step="0.01" min="0.01" name="quantity" class="form-control" required>
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label fw-bold">Reason *</label>
                            <select name="reason" class="form-select" required>
                                <option value="Expired">Expired</option>
                                <option value="Spoiled">Spoiled</option>
                                <option value="Broken">Broken</option>
                                <option value="Leaking">Leaking</option>
                                <option value="Contaminated">Contaminated</option>
                                <option value="Other">Other</option>
                            </select>
                        </div>
                    </div>
                    <div class="mb-0">
                        <label class="form-label fw-bold">Notes</label>
                        <textarea name="remarks" class="form-control" rows="3" placeholder="Batch, expiry date, staff note, or disposal detail"></textarea>
                    </div>
                </div>
                <div class="modal-footer bg-light">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    <button type="submit" class="btn btn-danger" @if(($activeBranch['scope'] ?? 'branch') === 'all') disabled @endif>Record Damage</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endif

<style>
    .report-page-title { color: #102a5a; font-size: 1.35rem; }
    .report-page-subtitle { font-size: 0.95rem; }
    .damage-metric-card { min-height: 100%; border-radius: 8px; }
    .damage-metric-card--loss { background: linear-gradient(135deg, #7f1d1d 0%, #dc2626 100%); }
    .damage-metric-label { font-size: 0.74rem; letter-spacing: 0.08em; text-transform: uppercase; }
    .damage-filter-label {
        display: block;
        margin-bottom: 0.45rem;
        color: #102a5a;
        font-size: 0.74rem;
        font-weight: 700;
        letter-spacing: 0.08em;
        text-transform: uppercase;
    }
    .damage-table thead th {
        background: #f8fafc;
        color: #102a5a;
        font-size: 0.74rem;
        letter-spacing: 0.08em;
        text-transform: uppercase;
        padding-top: 0.85rem;
        padding-bottom: 0.85rem;
    }
    .damage-table tbody td { vertical-align: middle; }
    @media print {
        .no-print, .header, .sidebar { display: none !important; }
        .page-wrapper { margin: 0 !important; padding: 0 !important; background: #fff !important; }
    }
</style>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const searchInput = document.getElementById('damage-product-search');
    const productSelect = document.getElementById('damage-product-select-report');
    const stockNote = document.getElementById('damage-stock-note-report');

    if (!searchInput || !productSelect) {
        return;
    }

    const options = Array.from(productSelect.options).slice(1);
    searchInput.addEventListener('input', function () {
        const term = searchInput.value.trim().toLowerCase();
        options.forEach(function (option) {
            option.hidden = term !== '' && !String(option.dataset.search || '').includes(term);
        });
    });

    productSelect.addEventListener('change', function () {
        const selected = productSelect.options[productSelect.selectedIndex];
        if (!selected || !selected.value) {
            stockNote.textContent = 'Choose an item from inventory to record its damaged quantity.';
            return;
        }

        stockNote.textContent = 'Available stock in active branch: ' + Number(selected.dataset.stock || 0).toLocaleString(undefined, {
            minimumFractionDigits: 2,
            maximumFractionDigits: 2
        });
    });
});
</script>
@endsection
