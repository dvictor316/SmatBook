
@extends('layout.mainlayout')

@section('content')
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
                                    <?php $lastImportKey = 'product_import_last_' . (auth()->id() ?? 'guest'); ?>
                                    <?php if (\Illuminate\Support\Facades\Cache::has($lastImportKey)) { ?>
                                        <li>
                                            <form action="{{ route('inventory.Products.import.undo') }}" method="POST" onsubmit="return confirm('Undo the last product import? This will delete the imported items and reset their stock.');">
                                                <?php echo csrf_field(); ?>
                                                  <button type="submit" class="dropdown-item text-danger">
                                                      <i class="fa-solid fa-rotate me-2"></i>Undo Last Import
                                                  </button>
                                            </form>
                                        </li>
                                    <?php } ?>
                                </ul>
                            </div>

                            <button type="button" class="btn btn-success desktop-add-product-trigger" data-bs-toggle="modal" data-bs-target="#addProductModal">
                                <i class="fa fa-plus"></i> Add Product
                            </button>
                            <?php if (($stockTransferEnabled ?? false) && count($availableBranches ?? []) > 1) { ?>
                                <button type="button" class="btn btn-outline-dark" data-bs-toggle="modal" data-bs-target="#transferStockModal">
                                    <i class="fas fa-right-left"></i> Transfer Stock
                                </button>
                            <?php } ?>
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
                            @if($productRows->count() > 0)
                                @foreach($productRows as $product)
                                    <tr>
                                        <td>{{ $loop->iteration }}</td>
                                        <td>
                                            <div class="d-flex align-items-center">
                                                @if($product->image_url)
                                                    <img src="{{ $product->image_url }}" class="rounded me-2" width="35" height="35" alt="{{ $product->name }}">
                                                @else
                                                    <span class="product-thumb-empty"><i class="fas fa-box-open"></i></span>
                                                @endif
                                                <div>
                                                    <div class="fw-bold text-dark">{{ $product->name }}</div>
                                                    <small class="text-muted">{{ $product->sku }}</small>
                                                </div>
                                            </div>
                                        </td>
                                        <td>{{ $product->category->name ?? 'N/A' }}</td>
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
                                            @php
                                                $displayStock = (float) ($product->active_branch_stock ?? $product->stock);
                                                $hasActiveBranch = !empty($activeBranch['name'] ?? null);
                                            @endphp
                                            <span class="badge {{ $displayStock <= 5 ? 'bg-danger' : 'bg-success' }}">
                                                {{ rtrim(rtrim(number_format($displayStock, 2), '0'), '.') }}
                                            </span>
                                            @if($hasActiveBranch)
                                                <div class="small text-muted mt-1">{{ $activeBranch['name'] }}</div>
                                            @endif
                                        </td>
                                        <td>
                                            <div>{{ number_format($product->price, 2) }}</div>
                                            @if(!is_null($product->wholesale_price) || !is_null($product->special_price))
                                                <small class="d-block text-muted">Wholesale: {{ !is_null($product->wholesale_price) ? number_format($product->wholesale_price, 2) : '—' }}</small>
                                                <small class="d-block text-muted">Special: {{ !is_null($product->special_price) ? number_format($product->special_price, 2) : '—' }}</small>
                                            @endif
                                        </td>
                                        <td>{{ number_format($product->purchase_price, 2) }}</td>
                                        <td class="text-center no-print">
                                            <div class="dropdown">
                                                <a href="#" class="product-action-trigger dropdown-toggle" data-bs-toggle="dropdown" aria-expanded="false">
                                                    <i class="fas fa-bolt"></i>
                                                    <span>Manage</span>
                                                </a>
                                                <div class="dropdown-menu dropdown-menu-end product-action-menu">
                                                    <a class="dropdown-item" href="{{ route('inventory.Products.edit', $product->id) }}"><i class="far fa-edit me-2"></i>Edit</a>
                                                    <form action="{{ route('inventory.Products.destroy', $product->id) }}" method="POST" onsubmit="return confirm('Delete this product?');">
                                                        @csrf @method('DELETE')
                                                        <button type="submit" class="dropdown-item text-danger"><i class="far fa-trash-alt me-2"></i>Delete</button>
                                                    </form>
                                                </div>
                                            </div>
                                        </td>
                                    </tr>
                                @endforeach
                            @else
                                <tr>
                                    <td colspan="9" class="text-center text-muted py-4">No products found.</td>
                                </tr>
                            @endif
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

@if(($stockTransferEnabled ?? false) && count($availableBranches ?? []) > 1)
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
                            @foreach($products as $product)
                                <option value="{{ $product->id }}">{{ $product->name }} ({{ $product->sku }})</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">From Branch</label>
                            <select name="from_branch_id" class="form-select" required>
                                <option value="">Select source</option>
                                @foreach(($availableBranches ?? []) as $branch)
                                    <option value="{{ $branch['id'] }}">{{ $branch['name'] }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">To Branch</label>
                            <select name="to_branch_id" class="form-select" required>
                                <option value="">Select destination</option>
                                @foreach(($availableBranches ?? []) as $branch)
                                    <option value="{{ $branch['id'] }}">{{ $branch['name'] }}</option>
                                @endforeach
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
                        <div class="col-md-6">
                            <label class="form-label">Product Name</label>
                            <input type="text" name="name" class="form-control" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">SKU</label>
                            <input type="text" name="sku" class="form-control" placeholder="Leave blank to auto-generate">
                            <small class="text-muted">If the product does not come with a code, the system will generate a unique SKU.</small>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Barcode</label>
                            <input type="text" name="barcode" class="form-control" placeholder="Scan or type barcode">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Category</label>
                            <div class="input-group">
                                <select name="category_id" id="product_category_select" class="form-select" required>
                                    @foreach($categories as $cat)
                                        <option value="{{ $cat->id }}">{{ $cat->name }}</option>
                                    @endforeach
                                </select>
                                <button type="button" class="btn btn-outline-primary" data-bs-toggle="modal" data-bs-target="#addCategoryModal">+</button>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Base Unit (e.g. pcs)</label>
                            <input type="text" name="base_unit_name" class="form-control" value="pcs" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Default Unit Type</label>
                            <select name="unit_type" class="form-select">
                                <option value="unit">Unit</option>
                                <option value="sachet">Sachet</option>
                                <option value="roll">Roll</option>
                                <option value="carton">Carton</option>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Stock Branch</label>
                            <select name="branch_id" class="form-select">
                                <option value="">Use Active Branch</option>
                                @foreach(($availableBranches ?? []) as $branch)
                                    <option value="{{ $branch['id'] }}">{{ $branch['name'] }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Unit Total <span class="text-muted d-block small" id="quick_unit_total_hint">Total units inside one carton</span></label>
                            <input type="number" id="quick_unit_total_per_carton" class="form-control" value="0" min="0" step="0.01">
                            <small class="text-muted" id="quick_unit_total_help">Type the full number of sellable units inside one carton first.</small>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Roll Content <span class="text-muted d-block small" id="quick_roll_content_hint">Units per roll</span></label>
                            <input type="number" name="units_per_roll" min="0" class="form-control" value="0">
                            <small class="text-muted" id="quick_roll_content_help">Leave `0` if the product is sold in cartons and units only.</small>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Carton Content <span class="text-muted d-block small" id="quick_carton_content_hint">Auto-calculated rolls per carton</span></label>
                            <input type="number" name="units_per_carton" min="0" step="0.01" class="form-control" value="0">
                            <small class="text-muted" id="quick_carton_content_help">This is calculated from unit total and roll content. If rolls are not used, it matches the unit total.</small>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Retail / Default Price</label>
                            <input type="number" step="0.01" name="price" class="form-control" required>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Purchase Price</label>
                            <input type="number" step="0.01" name="purchase_price" class="form-control" required>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Wholesale Price</label>
                            <input type="number" step="0.01" name="wholesale_price" class="form-control">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Special Discount Price</label>
                            <input type="number" step="0.01" name="special_price" class="form-control">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label" id="quick_units_per_carton_label">Units Per Carton</label>
                            <input type="text" class="form-control bg-light" id="quick_units_per_carton_preview" value="0 Units" readonly>
                            <small class="text-muted">Packaging preview only. This does not increase stock until you enter opening cartons, rolls, or loose units below.</small>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Opening Roll Quantity</label>
                            <input type="number" step="0.01" name="stock_rolls" class="form-control" value="0">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Opening Carton Quantity</label>
                            <input type="number" step="0.01" name="stock_cartons" class="form-control" value="0">
                            <small class="text-muted">Cartons convert through rolls when present, or directly to pieces when rolls are not used.</small>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label" id="quick_opening_unit_label">Opening Loose Unit Quantity</label>
                            <input type="number" step="0.01" name="stock_units" class="form-control" value="0">
                            <small class="text-muted">Enter only the loose units/pieces already on hand, not the carton definition above.</small>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Calculated Total Opening Stock</label>
                            <input type="text" class="form-control bg-light" id="quick_stock_preview" value="0 Units" readonly>
                            <input type="hidden" name="stock" id="quick_final_stock_input" value="">
                            <small class="text-muted">Calculated from opening cartons + opening rolls + opening loose units only.</small>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Product Image</label>
                            <input type="file" name="image" id="quick_add_product_image" class="form-control">
                            <small class="text-muted">Any file extension can be uploaded if the browser sends it as a valid file.</small>
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
                            @foreach(($availableBranches ?? []) as $branch)
                                <option value="{{ $branch['id'] }}">{{ $branch['name'] }}</option>
                            @endforeach
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

        $('#quick_add_product_form').on('submit', function() {
            const imageInput = document.getElementById('quick_add_product_image');
            if (imageInput && (!imageInput.files || imageInput.files.length === 0)) {
                imageInput.disabled = true;
            }
        });

        function refreshQuickPackagingLabels() {
            const baseUnitName = ($('input[name="base_unit_name"]').val() || 'unit').trim();
            const unitLabel = baseUnitName.length ? baseUnitName : 'unit';
            const titleUnit = unitLabel.charAt(0).toUpperCase() + unitLabel.slice(1);

            $('#quick_unit_total_hint').text('Total ' + unitLabel + 's inside one carton');
            $('#quick_unit_total_help').text('Type the full number of sellable ' + unitLabel + 's inside one carton first.');
            $('#quick_roll_content_hint').text(unitLabel + 's per roll');
            $('#quick_roll_content_help').text('Leave `0` if the product is sold in cartons and ' + unitLabel + 's only.');
            $('#quick_carton_content_hint').text('Auto-calculated rolls per carton');
            $('#quick_carton_content_help').text('This is calculated from total ' + unitLabel + 's and ' + unitLabel + 's per roll. If rolls are not used, it matches the unit total.');
            $('#quick_units_per_carton_label').text(titleUnit + 's Per Carton');
            $('#quick_opening_unit_label').text('Opening Loose ' + titleUnit + ' Quantity');
        }

        function calculateQuickCartonContent() {
            const unitTotal = parseFloat($('#quick_unit_total_per_carton').val()) || 0;
            const unitsPerRoll = parseFloat($('#quick_add_product_form').find('input[name="units_per_roll"]').val()) || 0;
            const cartonContent = unitsPerRoll > 0 ? (unitTotal / unitsPerRoll) : unitTotal;

            $('#quick_add_product_form').find('input[name="units_per_carton"]').val(Number.isFinite(cartonContent) ? cartonContent : 0);
            $('#quick_units_per_carton_preview').val(unitTotal.toLocaleString() + ' Units');
        }

        function calculateQuickStock() {
            const cartons = parseFloat($('input[name="stock_cartons"]').val()) || 0;
            const rolls = parseFloat($('input[name="stock_rolls"]').val()) || 0;
            const sachets = parseFloat($('input[name="stock_units"]').val()) || 0;
            const rollsPerCarton = parseFloat($('input[name="units_per_carton"]').val()) || 0;
            const sachetsPerRoll = parseFloat($('input[name="units_per_roll"]').val()) || 0;

            const fromCartons = sachetsPerRoll > 0 ? cartons * rollsPerCarton * sachetsPerRoll : cartons * rollsPerCarton;
            const fromRolls = sachetsPerRoll > 0 ? rolls * sachetsPerRoll : rolls;
            const total = fromCartons + fromRolls + sachets;
            $('#quick_stock_preview').val(total.toLocaleString() + ' Units');
            $('#quick_final_stock_input').val(Math.round(total));
        }

        $('#quick_add_product_form').find('input[name="stock_cartons"], input[name="stock_rolls"], input[name="stock_units"], input[name="units_per_carton"], input[name="units_per_roll"], #quick_unit_total_per_carton').on('input', function() {
            if ($(this).attr('name') === 'units_per_roll' || this.id === 'quick_unit_total_per_carton') {
                calculateQuickCartonContent();
            }
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
            
            fetch("{{ route('categories.store') }}", {
                method: "POST",
                headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}', 'Content-Type': 'application/json', 'Accept': 'application/json' },
                body: JSON.stringify({ name: $('#new_category_name').val() })
            })
            .then(async (res) => {
                const data = await res.json();
                if (!res.ok) {
                    const msg = data?.message || Object.values(data?.errors || {})?.[0]?.[0] || 'Failed to add category.';
                    throw new Error(msg);
                }
                return data;
            })
            .then(data => {
                if(data.data) {
                    $('#product_category_select').append(new Option(data.data.name, data.data.id, true, true));
                    bootstrap.Modal.getInstance(document.getElementById('addCategoryModal')).hide();
                    form.reset();
                }
            })
            .catch((err) => {
                alert(err.message || 'Unable to add category.');
            })
            .finally(() => btn.prop('disabled', false));
        });
    });
</script>
@endpush
@endsection
