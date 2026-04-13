
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

    .inventory-toolbar {
        display: flex;
        flex-wrap: wrap;
        gap: 0.75rem;
        justify-content: flex-end;
        align-items: center;
    }

    .inventory-search-form {
        min-width: 240px;
    }

    .mobile-add-product-trigger {
        display: none;
    }

    .product-form-sheet {
        border: 1px solid #edf2f7;
        border-radius: 14px;
        background: #fbfdff;
        padding: 1rem;
    }

    .product-form-sheet h6 {
        margin-bottom: 0.25rem;
        font-weight: 800;
        color: #1f2937;
    }

    #addProductModal .modal-content,
    #addProductModal .modal-body,
    #addProductModal .product-form-sheet,
    #addProductModal .form-control,
    #addProductModal .form-select,
    #addProductModal .input-group .btn {
        color: #1f2937;
    }

    #addProductModal .form-control,
    #addProductModal .form-select {
        background-color: #ffffff;
        border-color: #dbe3f0;
    }

    #addProductModal .form-select option,
    #addProductModal .form-control option {
        background-color: #ffffff;
        color: #111827;
    }

    #addProductModal .form-select:focus,
    #addProductModal .form-control:focus {
        border-color: #60a5fa;
        box-shadow: 0 0 0 0.2rem rgba(96, 165, 250, 0.18);
    }

    #addProductModal .select2-container {
        width: 100% !important;
    }

    #addProductModal .select2-container--default .select2-selection--single {
        height: 49px;
        border-radius: 12px;
        border: 1px solid #dbe3f0;
        background: #ffffff;
        display: flex;
        align-items: center;
    }

    #addProductModal .select2-container--default .select2-selection--single .select2-selection__rendered {
        color: #111827;
        line-height: 47px;
        padding-left: 14px;
        padding-right: 36px;
    }

    #addProductModal .select2-container--default .select2-selection--single .select2-selection__placeholder {
        color: #6b7280;
    }

    .select2-container--open .select2-dropdown.quick-category-dropdown {
        z-index: 2000;
        border-color: #dbe3f0;
        border-radius: 12px;
        overflow: hidden;
        background: #ffffff;
    }

    .select2-container--open .select2-dropdown.quick-category-dropdown .select2-results__option {
        color: #111827;
        background: #ffffff;
        padding: 10px 14px;
    }

    .select2-container--open .select2-dropdown.quick-category-dropdown .select2-results__option--highlighted[aria-selected] {
        background: #eef4ff;
        color: #1d4ed8;
    }

    .product-form-muted {
        color: #6b7280;
        font-size: 0.9rem;
        margin-bottom: 0.9rem;
    }

    .product-flow-banner {
        display: flex;
        flex-wrap: wrap;
        gap: 0.75rem;
        padding: 0.9rem 1rem;
        border-radius: 12px;
        background: linear-gradient(135deg, #f4f7ff 0%, #f8fbff 100%);
        border: 1px solid #dbe7ff;
    }

    .product-flow-step {
        flex: 1 1 180px;
        min-width: 0;
    }

    .product-flow-step strong {
        display: block;
        color: #1d4ed8;
        font-size: 0.86rem;
        margin-bottom: 0.15rem;
    }

    .product-flow-step span {
        color: #475569;
        font-size: 0.82rem;
        line-height: 1.45;
    }

    .quick-summary-pills {
        display: flex;
        flex-wrap: wrap;
        gap: 0.75rem;
    }

    .quick-summary-pill {
        flex: 1 1 180px;
        min-width: 0;
        border: 1px solid #e5e7eb;
        border-radius: 12px;
        background: #fff;
        padding: 0.75rem 0.9rem;
    }

    .quick-summary-pill span {
        display: block;
        font-size: 0.78rem;
        color: #64748b;
        margin-bottom: 0.2rem;
        text-transform: uppercase;
        letter-spacing: 0.04em;
    }

    .quick-summary-pill strong {
        font-size: 1rem;
        color: #0f172a;
        font-weight: 800;
    }

    .product-collapse-toggle {
        display: inline-flex;
        align-items: center;
        gap: 0.45rem;
        font-weight: 700;
    }
    
    @media print {
        .no-print, .dt-buttons, .main-header, .sidebar { display: none !important; }
        .card { border: none !important; box-shadow: none !important; }
        .page-wrapper { margin: 0 !important; padding: 0 !important; }
        table { width: 100% !important; border-collapse: collapse; }
        th, td { border: 1px solid #dee2e6 !important; padding: 8px !important; }
    }

    @media (max-width: 767.98px) {
        .inventory-page-header > [class*="col-"] {
            width: 100%;
        }

        .inventory-page-title {
            margin-bottom: 0.85rem;
        }

        .inventory-toolbar {
            justify-content: stretch;
            width: 100%;
        }

        .inventory-toolbar > * {
            flex: 1 1 calc(50% - 0.375rem);
            min-width: 0;
        }

        .inventory-search-form {
            flex: 1 1 100%;
            min-width: 100%;
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
        <div class="card shadow-sm mb-3 no-print">
            <div class="card-body">
                <div class="row align-items-center inventory-page-header">
                    <div class="col-md-4 inventory-page-title">
                        <h4 class="mb-0 text-primary"><i class="fas fa-boxes me-2"></i>Inventory Management</h4>
                    </div>
                    <div class="col-md-8 text-end">
                        <div class="inventory-toolbar">
                            <form method="GET" action="{{ route('product-list') }}" class="d-flex inventory-search-form">
                                <div class="input-group">
                                    <input type="text" name="search" value="{{ $search ?? '' }}" class="form-control" placeholder="Search SKU or Name...">
                                    <button type="submit" class="btn btn-primary"><i class="fas fa-search"></i></button>
                                </div>
                            </form>
                            
                            <div class="dropdown">
                                <button class="btn btn-outline-secondary dropdown-toggle" type="button" data-bs-toggle="dropdown">
                                    <i class="fas fa-download me-1"></i> Export
                                </button>
                                <ul class="dropdown-menu">
                                    <li><a class="dropdown-item" href="#" id="export_print"><i class="fas fa-print me-2 text-secondary"></i>Print</a></li>
                                    <li><a class="dropdown-item" href="#" id="export_excel"><i class="far fa-file-excel me-2 text-success"></i>Excel</a></li>
                                    <li><a class="dropdown-item" href="#" id="export_pdf"><i class="far fa-file-pdf me-2 text-danger"></i>PDF</a></li>
                                </ul>
                            </div>

                            <div class="dropdown">
                                <button class="btn btn-outline-primary dropdown-toggle" type="button" data-bs-toggle="dropdown">
                                    <i class="fas fa-upload me-1"></i> Import
                                </button>
                                <ul class="dropdown-menu dropdown-menu-end">
                                    <li><a class="dropdown-item" href="{{ route('inventory.Products.import.template') }}"><i class="far fa-file-lines me-2 text-primary"></i>Download Spreadsheet Template</a></li>
                                    <li><button type="button" class="dropdown-item" data-bs-toggle="modal" data-bs-target="#importProductsModal"><i class="fas fa-file-upload me-2 text-success"></i>Import Products</button></li>
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

                            <button type="button" class="btn btn-success desktop-add-product-trigger" data-bs-toggle="modal" data-bs-target="#addProductModal">
                                <i class="fa fa-plus"></i> Add Product
                            </button>
                            @if($showStockTransferModal)
                                <button type="button" class="btn btn-outline-dark" data-bs-toggle="modal" data-bs-target="#transferStockModal">
                                    <i class="fas fa-right-left"></i> Transfer Stock
                                </button>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="card shadow-sm">
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover" id="products-table">
                        <thead class="thead-light">
                            <tr>
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
                                <?php $productIndex = 1; foreach ($productRows as $product): ?>
                                    <tr>
                                        <td>{{ $productIndex }}</td>
                                        <td>
                                            <div class="d-flex align-items-center">
                                                @if($product->image_url)
                                                    <img src="{{ $product->image_url }}" class="rounded me-2" width="35" height="35" alt="{{ $product->name }}" onerror="this.style.display='none'; this.nextElementSibling.style.display='inline-flex';">
                                                    <span class="product-thumb-empty" style="display:none;"><i class="fas fa-box-open"></i></span>
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
                                            <?php
                                                $stockStatusLabel = $displayStock <= 0 ? 'Out of stock' : ($displayStock <= 5 ? 'Low stock' : 'In stock');
                                                $stockStatusClass = $displayStock <= 0 ? 'text-danger' : ($displayStock <= 5 ? 'text-warning' : 'text-success');
                                            ?>
                                            <?php $hasActiveBranch = !empty($activeBranch['name'] ?? null); ?>
                                            <span class="badge {{ $displayStock <= 5 ? 'bg-danger' : 'bg-success' }}">
                                                {{ rtrim(rtrim(number_format((float) $displayStock, 2), '0'), '.') }}
                                            </span>
                                            <div class="small fw-semibold {{ $stockStatusClass }} mt-1">{{ $stockStatusLabel }}</div>
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
                                    <td colspan="9" class="text-center text-muted py-4">No products found.</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<button type="button" class="mobile-add-product-trigger no-print" data-bs-toggle="modal" data-bs-target="#addProductModal" aria-label="Add product">
    <i class="fas fa-plus"></i>
    <span>Add Product</span>
</button>

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

{{-- MODAL: ADD PRODUCT --}}
<div class="modal fade" id="addProductModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <form method="POST" action="{{ route('inventory.Products.store') }}" enctype="multipart/form-data" id="quick_add_product_form">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title">Add New Product</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="row g-3">
                        <div class="col-12">
                            <div class="product-flow-banner">
                                <div class="product-flow-step">
                                    <strong>1. Product Details</strong>
                                    <span>Name, category, branch, prices, and image.</span>
                                </div>
                                <div class="product-flow-step">
                                    <strong>2. Packaging Setup</strong>
                                    <span>Tell the system how many pcs make one roll and one carton.</span>
                                </div>
                                <div class="product-flow-step">
                                    <strong>3. Opening Stock</strong>
                                    <span>Type only your current ctn, roll, and pcs. Total stock updates automatically.</span>
                                </div>
                            </div>
                        </div>

                        <div class="col-12">
                            <div class="product-form-sheet">
                                <h6>Product Details</h6>
                                <p class="product-form-muted">Enter the basic information first. This is all most products need.</p>
                                <div class="row g-3">
                                    <div class="col-md-6">
                                        <label class="form-label">Product Name</label>
                                        <input type="text" name="name" class="form-control" placeholder="e.g. Big Bull Rice 50kg" required>
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label">Category</label>
                                        <div class="input-group">
                                            <select name="category_id" id="product_category_select" class="form-select quick-category-select" required>
                                                <option value="">Select Category</option>
                                                <?php foreach ($categories as $cat): ?>
                                                    <option value="{{ $cat->id }}">{{ $cat->name }}</option>
                                                <?php endforeach; ?>
                                            </select>
                                            <button type="button" class="btn btn-outline-primary" data-bs-toggle="modal" data-bs-target="#addCategoryModal" title="Quick add category">+</button>
                                        </div>
                                    </div>
                                    <div class="col-md-2">
                                        <label class="form-label">Base Unit</label>
                                        <input type="text" name="base_unit_name" class="form-control" value="pcs" required>
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label">Retail / Default Price</label>
                                        <input type="number" step="0.01" name="price" class="form-control" placeholder="0.00" required>
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label">Purchase Price</label>
                                        <input type="number" step="0.01" name="purchase_price" class="form-control" placeholder="0.00" required>
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label">Stock Branch</label>
                                        <select name="branch_id" class="form-select">
                                            <option value="">Use Active Branch</option>
                                            <?php foreach ($branchOptions as $branch): ?>
                                                <option value="{{ $branch['id'] }}">{{ $branch['name'] }}</option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label">Product Image</label>
                                        <input type="file" name="image" id="quick_add_product_image" class="form-control">
                                        <small class="text-muted">Optional.</small>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="col-12">
                            <div class="product-form-sheet">
                                <h6>Packaging Setup</h6>
                                <p class="product-form-muted">Enter any two values below and the third one fills automatically.</p>
                                <div class="row g-3">
                                    <div class="col-md-4">
                                        <label class="form-label">Rolls Per Ctn</label>
                                        <input type="number" id="quick_rolls_per_carton_helper" min="0" step="0.01" class="form-control" value="0">
                                        <small class="text-muted">How many rolls are inside one carton.</small>
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label">Pcs Per Roll</label>
                                        <input type="number" id="quick_pcs_per_roll_helper" min="0" step="0.01" class="form-control" value="0">
                                        <small class="text-muted">How many pcs are inside one roll.</small>
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label">Pcs Per Ctn</label>
                                        <input type="number" id="quick_pcs_per_carton_helper" min="0" step="0.01" class="form-control" value="0">
                                        <small class="text-muted">Enter any two fields and this last one will calculate.</small>
                                    </div>
                                    <input type="hidden" name="units_per_roll" id="quick_units_per_roll_input" value="0">
                                    <input type="hidden" name="units_per_carton" id="quick_units_per_carton_input" value="0">
                                </div>
                            </div>
                        </div>

                        <div class="col-12">
                            <div class="product-form-sheet">
                                <h6>Opening Stock</h6>
                                <p class="product-form-muted">Type the quantity you currently have. Total stock appears automatically. If you do not have stock yet, leave all three fields at `0` and save the product first.</p>
                                <div class="row g-3">
                                    <div class="col-md-4">
                                        <label class="form-label">Opening Ctn</label>
                                        <input type="number" step="0.01" name="stock_cartons" class="form-control" value="0">
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label">Opening Roll</label>
                                        <input type="number" step="0.01" name="stock_rolls" class="form-control" value="0">
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label" id="quick_opening_unit_label">Opening Pcs</label>
                                        <input type="number" step="0.01" name="stock_units" class="form-control" value="0">
                                    </div>
                                    <div class="col-12">
                                        <div class="quick-summary-pills">
                                            <div class="quick-summary-pill">
                                                <span>Pcs Per Ctn</span>
                                                <strong id="quick_units_per_carton_preview_text">0 pcs</strong>
                                            </div>
                                            <div class="quick-summary-pill">
                                                <span>Total Opening Stock</span>
                                                <strong id="quick_stock_preview_text">0 pcs</strong>
                                            </div>
                                            <div class="quick-summary-pill">
                                                <span>Estimated Opening Value</span>
                                                <strong id="quick_stock_value_preview">0.00</strong>
                                            </div>
                                        </div>
                                        <input type="hidden" name="stock" id="quick_final_stock_input" value="">
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="col-12">
                            <button class="btn btn-light border product-collapse-toggle" type="button" data-bs-toggle="collapse" data-bs-target="#advancedProductFields" aria-expanded="false" aria-controls="advancedProductFields">
                                <i class="fas fa-sliders-h"></i>
                                <span>Advanced Fields</span>
                            </button>
                        </div>

                        <div class="col-12 collapse" id="advancedProductFields">
                            <div class="product-form-sheet">
                                <h6>Advanced Options</h6>
                                <p class="product-form-muted">Only open this when the product needs a SKU, barcode, or extra price levels.</p>
                                <div class="row g-3">
                                    <div class="col-md-6">
                                        <label class="form-label">SKU</label>
                                        <input type="text" name="sku" class="form-control" placeholder="Leave blank to auto-generate">
                                        <small class="text-muted">If there is no product code yet, the system creates one automatically.</small>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">Barcode</label>
                                        <input type="text" name="barcode" class="form-control" placeholder="Scan or type barcode">
                                    </div>
                                    <input type="hidden" name="unit_type" id="quick_unit_type_input" value="unit">
                                    <div class="col-md-4">
                                        <label class="form-label">Wholesale Price</label>
                                        <input type="number" step="0.01" name="wholesale_price" class="form-control" placeholder="Optional">
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label">Special Discount Price</label>
                                        <input type="number" step="0.01" name="special_price" class="form-control" placeholder="Optional">
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    <button type="submit" class="btn btn-primary">Save Product</button>
                </div>
            </form>
        </div>
    </div>
</div>

<div class="modal fade" id="importProductsModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST" action="{{ route('inventory.Products.import') }}" enctype="multipart/form-data">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title">Bulk Import Products</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p class="text-muted mb-3">Use the CSV template for large catalogs. Missing SKU values will be generated automatically during import.</p>
                    <div class="mb-3">
                        <a href="{{ route('inventory.Products.import.template') }}" class="btn btn-light border w-100">
                            <i class="far fa-file-lines me-2"></i>Download CSV Template
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
                    <button type="submit" class="btn btn-primary">Import Products</button>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- MODAL: QUICK ADD CATEGORY --}}
<div class="modal fade" id="addCategoryModal" tabindex="-1" style="z-index: 1060;">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <form id="ajaxAddCategoryForm">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title">Quick Category</h5>
                </div>
                <div class="modal-body">
                    <div id="quick_category_error_message" class="alert alert-danger d-none" role="alert"></div>
                    <input type="text" name="name" id="new_category_name" class="form-control" placeholder="Category Name" required>
                </div>
                <div class="modal-footer">
                    <button type="submit" class="btn btn-primary">Add</button>
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
<script src="https://cdn.datatables.net/buttons/2.4.2/js/buttons.print.min.js"></script>

<script>
    $(document).ready(function() {
        const categoryIndexUrl = @json(url('/categories'));
        const categoryStoreUrl = @json(url('/categories'));
        const quickCategoryError = $('#quick_category_error_message');

        function showQuickCategoryError(message) {
            quickCategoryError.removeClass('d-none').text(message || 'Unable to complete category request.');
        }

        function clearQuickCategoryError() {
            quickCategoryError.addClass('d-none').text('');
        }

        async function parseJsonResponse(response, fallbackMessage) {
            const raw = await response.text();
            try {
                return JSON.parse(raw);
            } catch (error) {
                throw new Error(raw && raw.trim().startsWith('<')
                    ? fallbackMessage
                    : (raw || fallbackMessage));
            }
        }

        async function reloadQuickCategoryOptions(selectedValue = '') {
            const categorySelect = document.getElementById('product_category_select');
            if (!categorySelect) {
                return;
            }

            try {
                const response = await fetch(categoryIndexUrl, {
                    headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
                });
                const payload = await parseJsonResponse(response, 'Category list returned HTML instead of JSON.');
                const categories = Array.isArray(payload?.data) ? payload.data : [];

                categorySelect.innerHTML = '';
                categorySelect.add(new Option('Select Category', '', false, false));

                categories.forEach((category) => {
                    if (!category?.id || !category?.name) {
                        return;
                    }

                    const isSelected = selectedValue !== '' && String(category.id) === String(selectedValue);
                    categorySelect.add(new Option(category.name, category.id, isSelected, isSelected));
                });

                if (selectedValue !== '') {
                    categorySelect.value = String(selectedValue);
                }
            } catch (error) {
                console.error('Unable to reload categories', error);
                showQuickCategoryError(error.message || 'Unable to load categories.');
            }
        }

        function initializeQuickCategorySelect() {
            const categorySelect = $('#product_category_select');
            if (!categorySelect.length || !$.fn.select2) {
                return;
            }

            if (!categorySelect.find('option[value=""]').length) {
                categorySelect.prepend(new Option('Select Category', '', false, false));
            }

            if (categorySelect.hasClass('select2-hidden-accessible')) {
                categorySelect.select2('destroy');
            }

            categorySelect.select2({
                dropdownParent: $('#addProductModal'),
                width: '100%',
                placeholder: 'Select Category',
                dropdownCssClass: 'quick-category-dropdown',
                minimumResultsForSearch: Infinity
            });
        }

        function upsertCategoryOption(selectSelector, category) {
            if (!category || !category.id || !category.name) {
                return;
            }

            const select = document.querySelector(selectSelector);
            if (!select) {
                return;
            }

            const optionValue = String(category.id);
            let existingOption = Array.from(select.options).find((option) => option.value === optionValue);

            if (!existingOption) {
                existingOption = new Option(category.name, category.id, true, true);
                select.add(existingOption);
            } else {
                existingOption.text = category.name;
                existingOption.selected = true;
            }

            select.value = optionValue;
            $(select).trigger('change');
            $(select).trigger('change.select2');
        }

        // PREVENT RE-INITIALIZATION ERROR
        if ($.fn.DataTable.isDataTable('#products-table')) {
            $('#products-table').DataTable().destroy();
        }

        var table = $('#products-table').DataTable({
            dom: 'Bfrtip',
            buttons: [
                { extend: 'excelHtml5', className: 'dt-excel d-none', title: 'Product_Inventory_List', exportOptions: { columns: ':not(.no-print)' } },
                { extend: 'pdfHtml5', className: 'dt-pdf d-none', title: 'Product Inventory List', exportOptions: { columns: ':not(.no-print)' } },
                { extend: 'print', className: 'dt-print d-none', title: 'Product Inventory', exportOptions: { columns: ':not(.no-print)' } }
            ],
            pageLength: 25,
            language: { search: "" , searchPlaceholder: "Search..." }
        });

        // Trigger Exports from Custom Dropdown
        $('#export_excel').on('click', function(e) { e.preventDefault(); table.button('.dt-excel').trigger(); });
        $('#export_pdf').on('click', function(e) { e.preventDefault(); table.button('.dt-pdf').trigger(); });
        $('#export_print').on('click', function(e) { e.preventDefault(); table.button('.dt-print').trigger(); });
        reloadQuickCategoryOptions().finally(() => {
            initializeQuickCategorySelect();
        });

        $('#addProductModal').on('shown.bs.modal', function() {
            reloadQuickCategoryOptions($('#product_category_select').val() || '').finally(() => {
                initializeQuickCategorySelect();
            });
        });

        $('#quick_add_product_form').on('submit', function() {
            const imageInput = document.getElementById('quick_add_product_image');
            if (imageInput && (!imageInput.files || imageInput.files.length === 0)) {
                imageInput.disabled = true;
            }
        });

        function refreshQuickPackagingLabels() {
            const baseUnitName = ($('input[name="base_unit_name"]').val() || 'pcs').trim();
            const unitLabel = baseUnitName.length ? baseUnitName : 'pcs';
            const titleUnit = unitLabel.charAt(0).toUpperCase() + unitLabel.slice(1);

            $('#quick_opening_unit_label').text('Opening ' + titleUnit);
        }

        let lastPackagingFieldEdited = null;

        function packagingValue(selector) {
            return parseFloat($(selector).val()) || 0;
        }

        function setPackagingValue(selector, value) {
            $(selector).val(value > 0 ? value : 0);
        }

        function syncPackagingHiddenFields() {
            const rollsPerCtn = packagingValue('#quick_rolls_per_carton_helper');
            const pcsPerRoll = packagingValue('#quick_pcs_per_roll_helper');
            const pcsPerCtn = packagingValue('#quick_pcs_per_carton_helper');

            $('#quick_units_per_roll_input').val(pcsPerRoll > 0 ? pcsPerRoll : 0);
            $('#quick_units_per_carton_input').val(rollsPerCtn > 0 ? rollsPerCtn : pcsPerCtn);
            $('#quick_units_per_carton_preview_text').text((pcsPerCtn > 0 ? pcsPerCtn : 0).toLocaleString() + ' pcs');
        }

        function syncQuickUnitType() {
            const pcsPerRoll = packagingValue('#quick_pcs_per_roll_helper');
            const rollsPerCtn = packagingValue('#quick_rolls_per_carton_helper');
            let unitType = 'unit';

            if (rollsPerCtn > 0) {
                unitType = 'carton';
            } else if (pcsPerRoll > 0) {
                unitType = 'roll';
            }

            $('#quick_unit_type_input').val(unitType);
        }

        function calculateQuickCartonContent() {
            let rollsPerCtn = packagingValue('#quick_rolls_per_carton_helper');
            let pcsPerRoll = packagingValue('#quick_pcs_per_roll_helper');
            let pcsPerCtn = packagingValue('#quick_pcs_per_carton_helper');

            const filledCount = [rollsPerCtn, pcsPerRoll, pcsPerCtn].filter(value => value > 0).length;

            if (filledCount >= 2) {
                if ((lastPackagingFieldEdited === 'rolls' || lastPackagingFieldEdited === 'pcs_per_roll') && rollsPerCtn > 0 && pcsPerRoll > 0) {
                    pcsPerCtn = rollsPerCtn * pcsPerRoll;
                    setPackagingValue('#quick_pcs_per_carton_helper', pcsPerCtn);
                } else if (lastPackagingFieldEdited === 'pcs_per_ctn' && rollsPerCtn > 0 && pcsPerCtn > 0) {
                    pcsPerRoll = pcsPerCtn / rollsPerCtn;
                    setPackagingValue('#quick_pcs_per_roll_helper', pcsPerRoll);
                } else if (lastPackagingFieldEdited === 'pcs_per_ctn' && pcsPerRoll > 0 && pcsPerCtn > 0) {
                    rollsPerCtn = pcsPerCtn / pcsPerRoll;
                    setPackagingValue('#quick_rolls_per_carton_helper', rollsPerCtn);
                } else if (rollsPerCtn > 0 && pcsPerRoll > 0 && pcsPerCtn <= 0) {
                    pcsPerCtn = rollsPerCtn * pcsPerRoll;
                    setPackagingValue('#quick_pcs_per_carton_helper', pcsPerCtn);
                } else if (rollsPerCtn > 0 && pcsPerCtn > 0 && pcsPerRoll <= 0) {
                    pcsPerRoll = pcsPerCtn / rollsPerCtn;
                    setPackagingValue('#quick_pcs_per_roll_helper', pcsPerRoll);
                } else if (pcsPerRoll > 0 && pcsPerCtn > 0 && rollsPerCtn <= 0) {
                    rollsPerCtn = pcsPerCtn / pcsPerRoll;
                    setPackagingValue('#quick_rolls_per_carton_helper', rollsPerCtn);
                }
            }

            syncPackagingHiddenFields();
            syncQuickUnitType();
        }

        function calculateQuickStock() {
            const cartons = parseFloat($('input[name="stock_cartons"]').val()) || 0;
            const rolls = parseFloat($('input[name="stock_rolls"]').val()) || 0;
            const pieces = parseFloat($('input[name="stock_units"]').val()) || 0;
            const rollsPerCarton = parseFloat($('#quick_units_per_carton_input').val()) || 0;
            const piecesPerRoll = parseFloat($('#quick_units_per_roll_input').val()) || 0;
            const piecesPerCarton = packagingValue('#quick_pcs_per_carton_helper');
            const purchasePrice = parseFloat($('input[name="purchase_price"]').val()) || 0;

            const fromCartons = piecesPerCarton > 0
                ? cartons * piecesPerCarton
                : (piecesPerRoll > 0 ? cartons * rollsPerCarton * piecesPerRoll : cartons * rollsPerCarton);
            const fromRolls = piecesPerRoll > 0 ? rolls * piecesPerRoll : rolls;
            let total = fromCartons + fromRolls + pieces;

            // If the user defined pack size but left opening stock empty,
            // treat the entered carton piece count as the starting quantity.
            if (total <= 0 && piecesPerCarton > 0) {
                total = piecesPerCarton;
            }

            const stockValue = total * purchasePrice;

            $('#quick_stock_preview_text').text(total.toLocaleString() + ' pcs');
            $('#quick_stock_value_preview').text(stockValue.toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 }));
            $('#quick_final_stock_input').val(Math.round(total));
        }

        $('#quick_rolls_per_carton_helper, #quick_pcs_per_roll_helper, #quick_pcs_per_carton_helper').on('input', function() {
            lastPackagingFieldEdited = $(this).attr('id') === 'quick_rolls_per_carton_helper'
                ? 'rolls'
                : ($(this).attr('id') === 'quick_pcs_per_roll_helper' ? 'pcs_per_roll' : 'pcs_per_ctn');
            calculateQuickCartonContent();
            calculateQuickStock();
        });

        $('#quick_add_product_form').find('input[name="stock_cartons"], input[name="stock_rolls"], input[name="stock_units"], input[name="purchase_price"]').on('input', function() {
            calculateQuickStock();
        });

        $('#quick_add_product_form').find('input[name="base_unit_name"]').on('input', function() {
            refreshQuickPackagingLabels();
        });

        refreshQuickPackagingLabels();
        calculateQuickCartonContent();
        calculateQuickStock();

        // AJAX Category Store
        $('#ajaxAddCategoryForm').on('submit', function(e) {
            e.preventDefault();
            const form = this;
            const btn = $(this).find('button[type="submit"]');
            btn.prop('disabled', true);
            clearQuickCategoryError();
            
            fetch(categoryStoreUrl, {
                method: "POST",
                headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}', 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                body: JSON.stringify({ name: $('#new_category_name').val() })
            })
            .then(async (res) => {
                const data = await parseJsonResponse(res, 'Category save returned HTML instead of JSON.');
                if (!res.ok) {
                    const msg = data?.message || Object.values(data?.errors || {})?.[0]?.[0] || 'Failed to add category.';
                    throw new Error(msg);
                }
                return data;
            })
            .then(data => {
                if(data.data) {
                    upsertCategoryOption('#product_category_select', data.data);
                    reloadQuickCategoryOptions(String(data.data.id)).finally(() => {
                        clearQuickCategoryError();
                        initializeQuickCategorySelect();
                        bootstrap.Modal.getInstance(document.getElementById('addCategoryModal')).hide();
                        form.reset();
                    });
                }
            })
            .catch((err) => {
                showQuickCategoryError(err.message || 'Unable to add category.');
            })
            .finally(() => btn.prop('disabled', false));
        });
    });
</script>
@endpush
@endsection
