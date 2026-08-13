
@extends('layout.mainlayout')

@section('content')
@php
    $products = $products ?? collect();
    $productRows = $productRows ?? collect();
    $hasProductRows = isset($hasProductRows) ? (bool) $hasProductRows : ($productRows->count() > 0);
    $categories = $categories ?? collect();
    $availableBranches = $availableBranches ?? [];
    $activeBranch = $activeBranch ?? [];
    $stockTransferEnabled = $stockTransferEnabled ?? false;
    $branchOptions = $availableBranches;
    $showStockTransferModal = $stockTransferEnabled && count($branchOptions) > 1;
@endphp
<style>
    /* Hide default DataTables buttons as we trigger them via our custom dropdown */
    .dt-buttons { display: none !important; }

    .product-action-trigger {
        display: inline-flex;
        align-items: center;
        gap: 0.45rem;
        padding: 0.55rem 0.95rem;
        border: 1px solid rgba(13, 110, 253, 0.25);
        border-radius: 999px;
        background: #eef4ff;
        color: #0d4fd6;
        font-size: 0.875rem;
        font-weight: 700;
        text-decoration: none;
        box-shadow: 0 8px 20px rgba(13, 110, 253, 0.12);
        transition: all 0.2s ease;
    }

    .product-action-trigger:hover,
    .product-action-trigger:focus {
        background: #0d6efd;
        color: #fff;
        border-color: #0d6efd;
    }

    .product-action-menu {
        min-width: 11rem;
        border: 0;
        border-radius: 1rem;
        box-shadow: 0 18px 40px rgba(15, 23, 42, 0.14);
        overflow: hidden;
    }

    .product-action-menu .dropdown-item {
        display: flex;
        align-items: center;
        gap: 0.65rem;
        padding: 0.8rem 1rem;
        font-weight: 600;
    }

    .product-action-menu .dropdown-item.text-danger {
        background: #fff7f7;
    }

    @media (max-width: 767.98px) {
        .product-action-trigger {
            width: 100%;
            justify-content: center;
            padding: 0.7rem 0.95rem;
        }
    }

    .product-thumb-empty {
        width: 35px;
        height: 35px;
        border-radius: 10px;
        background: #eef2ff;
        color: #4f46e5;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        margin-right: 0.5rem;
        font-size: 0.9rem;
    }

    .product-thumb-img {
        width: 35px;
        height: 35px;
        object-fit: cover;
        background: #eef2ff;
    }


    .inventory-toolbar {
        display: grid;
        grid-template-columns: repeat(3, minmax(0, 1fr));
        gap: 0.8rem 1rem;
        justify-content: stretch;
        align-items: center;
        max-width: 100%;
    }

    .inventory-page-title {
        margin: 0 0 0.65rem;
    }

    .inventory-page-title h4 {
        font-size: 1.08rem;
        white-space: nowrap;
    }

    .inventory-search-form {
        min-width: 0;
        width: 100%;
        max-width: none;
    }

    .inventory-search-form .form-control {
        min-height: 44px;
        font-size: 0.94rem;
    }

    .inventory-search-form .btn {
        min-width: 54px;
        padding-left: 0.85rem;
        padding-right: 0.85rem;
        border-radius: 999px;
        margin-left: -1px;
    }

    .inventory-toolbar .dropdown {
        position: relative;
    }

    .inventory-toolbar .dropdown-menu {
        z-index: 1085;
        min-width: 230px;
        box-shadow: 0 14px 34px rgba(15, 23, 42, 0.16);
    }

    .inventory-tool-btn {
        min-height: 46px;
        font-weight: 800;
        border-radius: 999px;
        padding-left: 0.85rem;
        padding-right: 0.85rem;
        width: 100%;
        justify-content: center;
        white-space: nowrap;
        font-size: 0.96rem;
    }

    .inventory-toolbar .dropdown-toggle.inventory-tool-btn {
        padding-right: 1.15rem;
    }

    .inventory-toolbar .dropdown > .inventory-tool-btn,
    .desktop-add-product-trigger.inventory-tool-btn {
        display: inline-flex;
        align-items: center;
        gap: 0.35rem;
    }

    .inventory-toolbar-primary {
        grid-column: 1 / 2;
        grid-row: 1;
    }

    .inventory-toolbar-print {
        grid-column: 2 / 3;
        grid-row: 1;
    }

    .inventory-toolbar-export {
        grid-column: 3 / 4;
        grid-row: 1;
    }

    .inventory-toolbar-import {
        grid-column: 3 / 4;
        grid-row: 2;
    }

    .inventory-toolbar-add {
        grid-column: 2 / 3;
        grid-row: 2;
    }

    .inventory-toolbar-transfer {
        grid-column: 3 / 4;
        grid-row: 2;
    }

    .inventory-toolbar-clear {
        grid-column: 1 / -1;
        grid-row: 3;
    }

    .inventory-bulk-bar {
        display: none;
        align-items: center;
        gap: 0.75rem;
        padding: 0.75rem 1rem;
        border: 1px solid #fecaca;
        border-radius: 12px;
        background: #fff7f7;
        color: #991b1b;
        margin-bottom: 1rem;
    }

    .inventory-bulk-bar.is-visible {
        display: flex;
    }

    .inventory-select-cell {
        width: 44px;
        text-align: center;
    }

    .inventory-select-cell .form-check-input {
        cursor: pointer;
        width: 1.05rem;
        height: 1.05rem;
    }

    .inventory-table-shell {
        max-height: none;
        min-height: 0;
        overflow: auto;
    }

    .inventory-table-shell thead th {
        position: sticky;
        top: 0;
        z-index: 5;
        background: #f8fafc;
        box-shadow: inset 0 -1px 0 #e5e7eb;
    }

    #products-table_wrapper .dataTables_scrollBody {
        border: 0;
        max-height: calc(100vh - 95px) !important;
    }

    #products-table_wrapper .dataTables_paginate,
    #products-table_wrapper .dataTables_info {
        padding-top: 0.2rem;
        padding-bottom: 0;
        font-size: 0.78rem;
    }

    .inventory-table-card-body {
        padding: 0.65rem 0.65rem 0.35rem;
    }

    #products-table_wrapper .dataTables_paginate {
        text-align: center !important;
    }

    #products-table_wrapper .dataTables_paginate .pagination {
        display: inline-flex;
        width: auto;
        margin: 0.1rem auto 0 !important;
        gap: 0.25rem;
        justify-content: center;
        padding: 0.2rem;
        border: 1px solid #e5e7eb;
        border-radius: 12px;
        background: #fff;
        box-shadow: 0 8px 18px rgba(15, 23, 42, 0.06);
    }

    #products-table_wrapper .dataTables_paginate .page-link {
        min-height: 30px;
        padding: 0.28rem 0.62rem;
        border-radius: 8px;
    }

    .inventory-page-header,
    .inventory-page-header .card-body,
    .inventory-page-header .inventory-toolbar {
        overflow: visible;
    }

    .mobile-add-product-trigger {
        display: none;
    }
    
    @media print {
        .no-print, .dt-buttons, .main-header, .sidebar { display: none !important; }
    }

    @media (max-width: 767.98px) {
        .inventory-page-title {
            margin-bottom: 0.85rem;
        }

        .inventory-toolbar {
            grid-template-columns: 1fr 1fr;
            width: 100%;
        }

        .inventory-toolbar > * {
            min-width: 0;
        }

        .inventory-search-form {
            grid-column: 1 / -1;
            min-width: 100%;
        }

        .inventory-toolbar-print,
        .inventory-toolbar-export,
        .inventory-toolbar-import,
        .inventory-toolbar-add,
        .inventory-toolbar-clear,
        .inventory-toolbar-transfer {
            grid-column: auto;
            grid-row: auto;
        }

        .inventory-toolbar .btn,
        .inventory-toolbar .dropdown,
        .inventory-toolbar .dropdown > .btn {
            width: 100%;
        }

        .desktop-add-product-trigger {
            display: none !important;
        }

        .mobile-add-product-trigger {
            position: fixed;
            right: 16px;
            bottom: 88px;
            z-index: 1040;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 0.45rem;
            padding: 0.9rem 1rem;
            border: 0;
            border-radius: 999px;
            background: linear-gradient(135deg, #198754 0%, #0f9d58 100%);
            color: #fff;
            box-shadow: 0 16px 36px rgba(15, 157, 88, 0.32);
            font-weight: 800;
        }
    }
</style>

<div class="page-wrapper" id="main-content-wrapper">
    <div class="content container-fluid">

        {{-- INLINE HEADER & CONTROLS --}}
        <div class="inventory-page-title no-print">
            <h4 class="mb-0 text-primary"><i class="fas fa-boxes me-2"></i>Inventory Management</h4>
        </div>
        <div class="card shadow-sm mb-3 no-print">
            <div class="card-body">
                <div class="inventory-toolbar">
                    <form method="GET" action="{{ route('product-list') }}" class="d-flex inventory-search-form inventory-toolbar-primary" id="inventory-toolbar-search-form">
                        <div class="input-group">
                            <input type="text" name="search" value="{{ $search ?? '' }}" class="form-control" id="inventory-toolbar-search-input" placeholder="Search SKU or Name...">
                            <button type="submit" class="btn btn-primary"><i class="fas fa-search"></i><span class="visually-hidden">Filter</span></button>
                        </div>
                    </form>

                    @if(!empty($search))
                        <a href="{{ route('product-list') }}" class="btn btn-outline-secondary inventory-tool-btn inventory-toolbar-clear">
                            <i class="fas fa-filter-circle-xmark me-1"></i> Clear Filter
                        </a>
                    @endif

                    <button type="button" class="btn btn-outline-secondary inventory-tool-btn inventory-toolbar-print" id="inventory_print_btn">
                        <i class="fas fa-print me-1"></i> Print
                    </button>

                    <div class="dropdown inventory-toolbar-export">
                        <button class="btn btn-outline-primary dropdown-toggle inventory-tool-btn" type="button" data-bs-toggle="dropdown">
                            <i class="fas fa-upload me-1"></i> Stock Export
                        </button>
                        <ul class="dropdown-menu">
                            <li><a class="dropdown-item" href="{{ route('inventory.Products.export', ['format' => 'xls', 'search' => $search ?? null]) }}"><i class="far fa-file-excel me-2 text-success"></i>Export Stock Excel</a></li>
                            <li><a class="dropdown-item" href="{{ route('inventory.Products.export', ['format' => 'csv', 'search' => $search ?? null]) }}"><i class="fas fa-file-csv me-2 text-primary"></i>Export Stock CSV</a></li>
                            <li><a class="dropdown-item" href="#" id="export_pdf"><i class="far fa-file-pdf me-2 text-danger"></i>Export Stock PDF</a></li>
                        </ul>
                    </div>

                    <div class="dropdown inventory-toolbar-import">
                        <button class="btn btn-outline-success dropdown-toggle inventory-tool-btn" type="button" data-bs-toggle="dropdown">
                            <i class="fas fa-download me-1"></i> Bulk Stock Import
                        </button>
                        <ul class="dropdown-menu dropdown-menu-end">
                            <li><a class="dropdown-item" href="{{ route('inventory.Products.import.template') }}"><i class="far fa-file-lines me-2 text-primary"></i>Download Stock Template</a></li>
                            <li><button type="button" class="dropdown-item" data-bs-toggle="modal" data-bs-target="#importProductsModal"><i class="fas fa-file-upload me-2 text-success"></i>Upload Stock Spreadsheet</button></li>
                            @php($lastImportKey = 'product_import_last_' . (auth()->id() ?? 'guest'))
                            @if (\Illuminate\Support\Facades\Cache::has($lastImportKey))
                                <li>
                                    <form action="{{ route('inventory.Products.import.undo') }}" method="POST" onsubmit="return confirm('Undo the last product import? This will delete the imported items and reset their stock.');">
                                        @csrf
                                          <button type="submit" class="dropdown-item text-danger">
                                              <i class="fa-solid fa-rotate me-2"></i>Undo Last Import
                                          </button>
                                    </form>
                                </li>
                            @endif
                        </ul>
                    </div>

                    <a href="{{ route('add-products') }}" class="btn btn-success desktop-add-product-trigger inventory-tool-btn inventory-toolbar-add">
                        <i class="fa fa-plus"></i> Add Product
                    </a>
                    @if($showStockTransferModal)
                        <button type="button" class="btn btn-outline-dark inventory-tool-btn inventory-toolbar-transfer" data-bs-toggle="modal" data-bs-target="#transferStockModal">
                            <i class="fas fa-right-left"></i> Transfer Stock
                        </button>
                    @endif
                </div>
            </div>
        </div>

        <div class="card shadow-sm">
            <div class="card-body inventory-table-card-body">
                <form id="bulk-delete-products-form" method="POST" action="{{ route('inventory.Products.bulk-destroy') }}" class="inventory-bulk-bar no-print">
                    @csrf
                    @method('DELETE')
                    <strong><span id="bulk-selected-count">0</span> selected</strong>
                    <span class="text-muted">Delete selected stock items from inventory.</span>
                    <button type="submit" class="btn btn-danger btn-sm ms-auto">
                        <i class="far fa-trash-alt me-1"></i> Delete Selected
                    </button>
                </form>
                <div class="table-responsive inventory-table-shell">
                    <table class="table table-hover" id="products-table">
                        <thead class="thead-light">
                            <tr>
                                <th class="inventory-select-cell no-print">
                                    <input type="checkbox" class="form-check-input" id="select-all-products" aria-label="Select all visible stock items">
                                </th>
                                <th>#</th>
                                <th>Item / SKU</th>
                                <th>Category</th>
                                <th>Base Unit</th>
                                <th>Packaging</th>
                                <th>Stock</th>
                                <th>S. Price</th>
                                <th>P. Price</th>
                                <th class="text-center no-print">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if ($hasProductRows): ?>
                                <?php $productIndex = method_exists($products, 'firstItem') ? ($products->firstItem() ?? 1) : 1; foreach ($productRows as $product): ?>
                                    <tr>
                                        <td class="inventory-select-cell no-print">
                                            <input type="checkbox" class="form-check-input product-select-checkbox" value="{{ $product->id }}" aria-label="Select {{ $product->name }}">
                                        </td>
                                        <td>{{ $productIndex }}</td>
                                        <td>
                                            <div class="d-flex align-items-center">
                                                @if($product->image_url)
                                                    <img src="{{ $product->image_url }}" class="rounded me-2 product-thumb-img" alt="{{ $product->name }}" loading="lazy" onerror="this.classList.add('d-none'); if (this.nextElementSibling) this.nextElementSibling.classList.remove('d-none');">
                                                    <span class="product-thumb-empty d-none"><i class="fas fa-box-open"></i></span>
                                                @else
                                                    <span class="product-thumb-empty"><i class="fas fa-box-open"></i></span>
                                                @endif
                                                <div>
                                                    <div class="fw-bold text-dark">{{ $product->name }}</div>
                                                    <small class="text-muted">{{ $product->sku }}</small>
                                                </div>
                                            </div>
                                        </td>
                                        <td>{{ $product->category_name ?? 'N/A' }}</td>
                                        <td><span class="badge bg-soft-info text-info">{{ $product->base_unit_name }}</span></td>
                                        <td>
                                                @if((int) ($product->units_per_roll ?? 0) > 0)
                                                    <small class="d-block text-nowrap">Rolls / Carton: <strong>{{ $product->units_per_carton }}</strong></small>
                                                    <small class="d-block text-nowrap">Sachets / Roll: <strong>{{ $product->units_per_roll }}</strong></small>
                                                @else
                                                    <small class="d-block text-nowrap">Pieces / Carton: <strong>{{ $product->units_per_carton }}</strong></small>
                                                    <small class="d-block text-nowrap">Roll Layer: <strong>Not used</strong></small>
                                                @endif
                                        </td>
                                        <td>
                                            <?php $displayStock = (float) ($product->active_branch_stock ?? $product->stock); ?>
                                            <?php $hasActiveBranch = !empty($activeBranch['name'] ?? null); ?>
                                            <span class="badge {{ $displayStock <= 5 ? 'bg-danger' : 'bg-success' }}">
                                                {{ rtrim(rtrim(number_format((float) $displayStock, 2), '0'), '.') }}
                                            </span>
                                            @if($hasActiveBranch)
                                                <div class="small text-muted mt-1">{{ $activeBranch['name'] }}</div>
                                            @endif
                                        </td>
                                        <td>
                                            <div>{{ number_format((float) $product->price, 2) }}</div>
                                            @if(!is_null($product->wholesale_price) || !is_null($product->special_price))
                                                <small class="d-block text-muted">Wholesale: {{ !is_null($product->wholesale_price) ? number_format((float) $product->wholesale_price, 2) : '—' }}</small>
                                                <small class="d-block text-muted">Special: {{ !is_null($product->special_price) ? number_format((float) $product->special_price, 2) : '—' }}</small>
                                            @endif
                                        </td>
                                        <td>{{ number_format((float) $product->purchase_price, 2) }}</td>
                                        <td class="text-center no-print">
                                            <div class="dropdown">
                                                <a href="#" class="product-action-trigger dropdown-toggle" data-bs-toggle="dropdown" aria-expanded="false">
                                                    <i class="fas fa-bolt"></i>
                                                    <span>Manage</span>
                                                </a>
                                                <div class="dropdown-menu dropdown-menu-end product-action-menu">
                                                    <a class="dropdown-item" href="{{ route('inventory.history', $product->id) }}"><i class="fas fa-chart-line me-2"></i>Run Report</a>
                                                    <a class="dropdown-item" href="{{ route('inventory.Products.edit', $product->id) }}"><i class="far fa-edit me-2"></i>Edit</a>
                                                    <form action="{{ route('inventory.Products.destroy', $product->id) }}" method="POST" onsubmit="return confirm('Delete this product?');">
                                                        @csrf @method('DELETE')
                                                        <button type="submit" class="dropdown-item text-danger"><i class="far fa-trash-alt me-2"></i>Delete</button>
                                                    </form>
                                                </div>
                                            </div>
                                        </td>
                                    </tr>
                                <?php $productIndex++; endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="10" class="text-center text-muted py-4">No products found.</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<a href="{{ route('add-products') }}" class="mobile-add-product-trigger no-print" aria-label="Add product">
    <i class="fas fa-plus"></i>
    <span>Add Product</span>
</a>

@if($showStockTransferModal)
<div class="modal fade" id="transferStockModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST" action="{{ route('inventory.transfer') }}">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title">Transfer Stock Between Branches</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p class="text-muted mb-3">Use this on higher plans to move stock from one branch to another without changing total company stock.</p>
                    <div class="mb-3">
                        <label class="form-label">Product</label>
                        <select name="product_id" class="form-select" required>
                            <option value="">Select product</option>
                            <?php foreach ($products as $product): ?>
                                <option value="{{ $product->id }}">{{ $product->name }} ({{ $product->sku }})</option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">From Branch</label>
                            <select name="from_branch_id" class="form-select" required>
                                <option value="">Select source</option>
                                <?php foreach ($branchOptions as $branch): ?>
                                    <option value="{{ $branch['id'] }}">{{ $branch['name'] }}</option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">To Branch</label>
                            <select name="to_branch_id" class="form-select" required>
                                <option value="">Select destination</option>
                                <?php foreach ($branchOptions as $branch): ?>
                                    <option value="{{ $branch['id'] }}">{{ $branch['name'] }}</option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    <div class="mb-0">
                        <label class="form-label">Quantity</label>
                        <input type="number" step="0.01" min="0.01" name="quantity" class="form-control" required>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    <button type="submit" class="btn btn-primary">Transfer Stock</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endif

<div class="modal fade" id="importProductsModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST" action="{{ route('inventory.Products.import') }}" enctype="multipart/form-data">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title">Bulk Stock Import</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p class="text-muted mb-3">Use the stock spreadsheet template to import many products, prices, packaging, and opening stock quantities at once. Missing SKU values will be generated automatically.</p>
                    <div class="alert alert-info small mb-3">
                        <strong>Prokip-style import guide:</strong>
                        <ul class="mb-0 ps-3">
                            <li>Only the product name is required. Other columns can be left blank and will default safely.</li>
                            <li>Use existing column names where possible: name, sku, barcode, category, unit, unit_type, stock, retail_price, wholesale_price, special_price, purchase_price.</li>
                            <li>For measured products, put KG or LITRE in unit_type or unit. The importer will map them to kg or litre units.</li>
                            <li>Packaging fields such as units_per_carton, units_per_roll, stock_cartons, stock_rolls, and stock_units must be numbers. Text values are treated as 0.</li>
                            <li>Leave SKU blank if you want the system to generate one automatically.</li>
                        </ul>
                    </div>
                    <div class="mb-3">
                        <a href="{{ route('inventory.Products.import.template') }}" class="btn btn-light border w-100">
                            <i class="far fa-file-lines me-2"></i>Download Stock CSV Template
                        </a>
                    </div>
                    <div>
                        <label class="form-label">Spreadsheet File</label>
                        <input type="file" name="import_file" class="form-control" accept=".csv,.txt,.xls,.xlsx,text/csv,application/vnd.ms-excel,application/vnd.openxmlformats-officedocument.spreadsheetml.sheet" required>
                    </div>
                    <div class="mt-3">
                        <label class="form-label d-flex align-items-center gap-2">
                            <input type="checkbox" name="update_existing" value="1">
                            <span>Update existing products when duplicates are found</span>
                        </label>
                        <small class="text-muted">When enabled, imports will update matching items instead of skipping them.</small>
                    </div>
                    <div>
                        <label class="form-label">Apply Opening Stock To Branch</label>
                        <select name="branch_id" class="form-select">
                            <option value="">Use Active Branch</option>
                            <?php foreach ($branchOptions as $branch): ?>
                                <option value="{{ $branch['id'] }}">{{ $branch['name'] }}</option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    <button type="submit" class="btn btn-primary">Import Stock Spreadsheet</button>
                </div>
            </form>
        </div>
    </div>
</div>

@push('scripts')
{{-- Required DataTables Buttons Assets --}}
<script src="https://cdnjs.cloudflare.com/ajax/libs/jszip/3.10.1/jszip.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.1.53/pdfmake.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.1.53/vfs_fonts.js"></script>
<script src="https://cdn.datatables.net/buttons/2.4.2/js/dataTables.buttons.min.js"></script>
<script src="https://cdn.datatables.net/buttons/2.4.2/js/buttons.html5.min.js"></script>

<script>
    $(document).ready(function() {
        var selectedProductIds = new Set();
        var bulkForm = $('#bulk-delete-products-form');
        var bulkCount = $('#bulk-selected-count');
        var selectAllProducts = $('#select-all-products');
        var toolbarSearchInput = $('#inventory-toolbar-search-input');

        function syncBulkDeleteForm() {
            bulkForm.find('input[name="product_ids[]"]').remove();
            selectedProductIds.forEach(function(id) {
                $('<input>', {
                    type: 'hidden',
                    name: 'product_ids[]',
                    value: id
                }).appendTo(bulkForm);
            });

            bulkCount.text(selectedProductIds.size);
            bulkForm.toggleClass('is-visible', selectedProductIds.size > 0);
        }

        function currentPageProductChecks() {
            if (typeof table !== 'undefined' && table) {
                return $(table.rows({ page: 'current', search: 'applied' }).nodes()).find('.product-select-checkbox');
            }

            return $('.product-select-checkbox');
        }

        function syncVisibleProductChecks() {
            $('.product-select-checkbox').each(function() {
                this.checked = selectedProductIds.has(String(this.value));
            });

            var pageChecks = currentPageProductChecks();
            var checkedOnPage = pageChecks.filter(':checked').length;
            selectAllProducts.prop('checked', pageChecks.length > 0 && checkedOnPage === pageChecks.length);
            selectAllProducts.prop('indeterminate', checkedOnPage > 0 && checkedOnPage < pageChecks.length);
        }

        function htmlEscape(value) {
            return String(value == null ? '' : value).replace(/[&<>"']/g, function(match) {
                return {
                    '&': '&amp;',
                    '<': '&lt;',
                    '>': '&gt;',
                    '"': '&quot;',
                    "'": '&#039;'
                }[match];
            });
        }

        function printableCellText(cell) {
            return $(cell)
                .clone()
                .find('img, button, .dropdown, input, .product-thumb-empty')
                .remove()
                .end()
                .text()
                .replace(/\s+/g, ' ')
                .trim();
        }

        function printInventoryTable() {
            var printableColumns = [];
            $('#products-table thead th').each(function(index) {
                if (!$(this).hasClass('no-print')) {
                    printableColumns.push({
                        index: index,
                        label: $(this).text().replace(/\s+/g, ' ').trim()
                    });
                }
            });

            var rows = table.rows({ search: 'applied', page: 'all' }).nodes().toArray();
            var bodyRows = rows.map(function(row) {
                var cells = $(row).children('td');
                var cellHtml = printableColumns.map(function(column) {
                    return '<td>' + htmlEscape(printableCellText(cells[column.index])) + '</td>';
                }).join('');

                return '<tr>' + cellHtml + '</tr>';
            }).join('');

            var printedAt = new Date().toLocaleString();
            var headerHtml = printableColumns.map(function(column) {
                return '<th>' + htmlEscape(column.label) + '</th>';
            }).join('');
            var columnWidths = ['5%', '25%', '12%', '9%', '19%', '10%', '10%', '10%'];
            var colgroupHtml = printableColumns.map(function(column, index) {
                return '<col style="width:' + (columnWidths[index] || 'auto') + '">';
            }).join('');

            $('#inventory-print-frame').remove();
            var printFrame = $('<iframe>', {
                id: 'inventory-print-frame',
                title: 'Product Inventory Print'
            }).css({
                position: 'fixed',
                right: '0',
                bottom: '0',
                width: '0',
                height: '0',
                border: '0',
                opacity: '0',
                pointerEvents: 'none'
            }).appendTo('body')[0];

            var printDoc = printFrame.contentDocument || printFrame.contentWindow.document;
            printDoc.open();
            printDoc.write(`<!doctype html>
                <html>
                <head>
                    <title>Product Inventory</title>
                    <style>
                        @page { size: A4 landscape; margin: 5mm; }
                        * { box-sizing: border-box; }
                        body { margin: 0; font-family: Arial, sans-serif; color: #111827; }
                        h1 { margin: 0 0 3px; font-size: 16px; }
                        .meta { margin: 0 0 7px; color: #475569; font-size: 10px; }
                        table { width: 100%; border-collapse: collapse; table-layout: fixed; font-size: 8px; }
                        th, td { border: 1px solid #d1d5db; padding: 3px 4px; text-align: left; vertical-align: top; overflow-wrap: anywhere; word-break: break-word; }
                        th { background: #eef2ff; color: #0f172a; font-weight: 700; }
                        td:nth-child(1), th:nth-child(1),
                        td:nth-child(6), th:nth-child(6),
                        td:nth-child(7), th:nth-child(7),
                        td:nth-child(8), th:nth-child(8) { text-align: right; }
                        tr:nth-child(even) td { background: #f8fafc; }
                    </style>
                </head>
                <body>
                    <h1>Product Inventory</h1>
                    <p class="meta">Printed ${htmlEscape(printedAt)} &middot; ${rows.length} item(s)</p>
                    <table>
                        <colgroup>${colgroupHtml}</colgroup>
                        <thead><tr>${headerHtml}</tr></thead>
                        <tbody>${bodyRows || '<tr><td colspan="' + printableColumns.length + '">No products found.</td></tr>'}</tbody>
                    </table>
                </body>
                </html>`);
            printDoc.close();

            var cleanupPrintFrame = function() {
                setTimeout(function() {
                    $('#inventory-print-frame').remove();
                }, 250);
            };

            printFrame.contentWindow.onafterprint = cleanupPrintFrame;
            setTimeout(function() {
                printFrame.contentWindow.focus();
                printFrame.contentWindow.print();
                setTimeout(cleanupPrintFrame, 30000);
            }, 150);
        }

        // PREVENT RE-INITIALIZATION ERROR
        if ($.fn.DataTable.isDataTable('#products-table')) {
            $('#products-table').DataTable().destroy();
        }

        var table = $('#products-table').DataTable({
            dom: 'Brtip',
            buttons: [
                { extend: 'excelHtml5', className: 'dt-excel d-none', title: 'Product_Inventory_List', exportOptions: { columns: ':not(.no-print)', modifier: { page: 'all', search: 'applied' } } },
                { extend: 'csvHtml5', className: 'dt-csv d-none', title: 'Product_Inventory_List', exportOptions: { columns: ':not(.no-print)', modifier: { page: 'all', search: 'applied' } } },
                { extend: 'pdfHtml5', className: 'dt-pdf d-none', title: 'Product Inventory List', orientation: 'landscape', pageSize: 'A4', exportOptions: { columns: ':not(.no-print)', modifier: { page: 'all', search: 'applied' } } }
            ],
            pageLength: 500,
            displayLength: 500,
            iDisplayLength: 500,
            lengthChange: false,
            stateSave: false,
            stateLoadCallback: function() { return null; },
            deferRender: true,
            scrollY: 'calc(100vh - 255px)',
            scrollCollapse: false,
            paging: true,
            lengthMenu: [[500, -1], [500, 'All']],
            language: {
                search: "",
                searchPlaceholder: "Search...",
                info: "Showing _START_ to _END_ of _TOTAL_ stock items",
                paginate: {
                    previous: "Previous 500",
                    next: "Next 500"
                }
            }
        });

        // Trigger Exports from Custom Dropdown
        $('#export_pdf').on('click', function(e) { e.preventDefault(); table.button('.dt-pdf').trigger(); });
        $('#inventory_print_btn').on('click', function(e) {
            e.preventDefault();
            printInventoryTable();
        });

        table.page.len(500).draw(false);
        table.on('draw', syncVisibleProductChecks);
        syncVisibleProductChecks();

        var searchDebounce;
        toolbarSearchInput.on('input', function() {
            var query = this.value;
            clearTimeout(searchDebounce);
            searchDebounce = setTimeout(function() {
                table.search(query).draw();
            }, 120);
        });

        $('#products-table').on('change', '.product-select-checkbox', function() {
            var id = String(this.value);
            if (this.checked) {
                selectedProductIds.add(id);
            } else {
                selectedProductIds.delete(id);
            }
            syncBulkDeleteForm();
            syncVisibleProductChecks();
        });

        selectAllProducts.on('click', function(e) {
            e.preventDefault();
            var pageChecks = currentPageProductChecks();
            var checkedOnPage = pageChecks.filter(':checked').length;
            var checked = pageChecks.length > 0 && checkedOnPage < pageChecks.length;

            pageChecks.each(function() {
                var id = String(this.value);
                this.checked = checked;
                if (checked) {
                    selectedProductIds.add(id);
                } else {
                    selectedProductIds.delete(id);
                }
            });
            syncBulkDeleteForm();
            syncVisibleProductChecks();
        });

        bulkForm.on('submit', function(e) {
            if (selectedProductIds.size < 1) {
                e.preventDefault();
                alert('Select at least one stock item to delete.');
                return;
            }

            if (!confirm('Delete ' + selectedProductIds.size + ' selected stock item(s)? This cannot be undone.')) {
                e.preventDefault();
            }
        });

    });
</script>
@endpush
@endsection
