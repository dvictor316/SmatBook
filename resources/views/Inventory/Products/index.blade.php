
@extends('layout.mainlayout')

@section('content')
@php
    $products = $products ?? collect();
    $productRows = $productRows ?? collect();
    $transferProducts = $transferProducts ?? $productRows;
    $hasProductRows = isset($hasProductRows) ? (bool) $hasProductRows : ($productRows->count() > 0);
    $categories = $categories ?? collect();
    $availableBranches = $availableBranches ?? [];
    $activeBranch = $activeBranch ?? [];
    $stockTransferEnabled = $stockTransferEnabled ?? false;
    $branchOptions = $availableBranches;
    $showStockTransferModal = $stockTransferEnabled && count($branchOptions) > 1;
    $productRowsForView = collect($productRows)->values();
    $transferProductsForView = collect($transferProducts)->values();
    $branchOptionsForView = array_values($branchOptions);
    $piecesPerCarton = 0;
    $piecesPerRoll = 0;
    $rollsPerCarton = 0;
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
        grid-column: 1 / 2;
        grid-row: 3;
    }

    .inventory-toolbar-damages {
        grid-column: 1 / 2;
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
        grid-row: 4;
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

    .import-guide-modal .modal-content {
        border: 0;
        border-radius: 18px;
        overflow: hidden;
        box-shadow: 0 28px 70px rgba(15, 23, 42, 0.24);
    }

    .import-guide-hero {
        background: linear-gradient(135deg, #0f3a8a 0%, #2563eb 68%, #d7a928 100%);
        color: #fff;
        padding: 1.4rem 1.5rem;
    }

    .import-guide-hero h5 {
        color: #fff;
        font-size: 1.35rem;
        font-weight: 900;
    }

    .import-guide-hero p {
        color: rgba(255, 255, 255, 0.86);
        margin-bottom: 0;
    }

    .import-guide-grid {
        display: grid;
        grid-template-columns: repeat(2, minmax(0, 1fr));
        gap: 1rem;
    }

    .import-guide-panel {
        border: 1px solid #e2e8f0;
        border-radius: 12px;
        padding: 1rem;
        background: #fff;
    }

    .import-guide-panel h6,
    .import-guide-examples h6 {
        color: #0f172a;
        font-weight: 900;
        margin-bottom: 0.75rem;
    }

    .import-guide-list {
        margin: 0;
        padding-left: 1.15rem;
        color: #475569;
        font-size: 0.88rem;
    }

    .import-guide-list li + li {
        margin-top: 0.4rem;
    }

    .import-guide-examples {
        border: 1px solid #bfdbfe;
        border-radius: 12px;
        padding: 1rem;
        background: #f8fbff;
    }

    .import-example-table {
        margin-bottom: 0;
        font-size: 0.82rem;
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
        .inventory-toolbar-transfer,
        .inventory-toolbar-damages {
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

        .import-guide-grid {
            grid-template-columns: 1fr;
        }
    }
</style>

<div class="page-wrapper" id="main-content-wrapper">
    <div class="content container-fluid">

        <!-- Inline header and controls -->
        <div class="inventory-page-title no-print">
            <h4 class="mb-0 text-primary"><i class="fas fa-boxes me-2"></i>Inventory Management</h4>
        </div>
        <div class="card shadow-sm mb-3 no-print">
            <div class="card-body">
                <div class="inventory-toolbar">
                    <form method="GET" action="<?php echo e(route('product-list')); ?>" class="d-flex inventory-search-form inventory-toolbar-primary" id="inventory-toolbar-search-form">
                        <div class="input-group">
                            <input type="text" name="search" value="<?php echo e($search ?? ''); ?>" class="form-control" id="inventory-toolbar-search-input" placeholder="Search SKU or Name...">
                            <button type="submit" class="btn btn-primary"><i class="fas fa-search"></i><span class="visually-hidden">Filter</span></button>
                        </div>
                    </form>

                    <?php if (!empty($search)) { ?>
                        <a href="<?php echo e(route('product-list')); ?>" class="btn btn-outline-secondary inventory-tool-btn inventory-toolbar-clear">
                            <i class="fas fa-filter-circle-xmark me-1"></i> Clear Filter
                        </a>
                    <?php } ?>

                    <button type="button" class="btn btn-outline-secondary inventory-tool-btn inventory-toolbar-print" id="inventory_print_btn">
                        <i class="fas fa-print me-1"></i> Print
                    </button>

                    <div class="dropdown inventory-toolbar-export">
                        <button class="btn btn-outline-primary dropdown-toggle inventory-tool-btn" type="button" data-bs-toggle="dropdown">
                            <i class="fas fa-upload me-1"></i> Stock Export
                        </button>
                        <ul class="dropdown-menu">
                            <li><a class="dropdown-item" href="<?php echo e(route('inventory.Products.export', ['format' => 'xls', 'search' => $search ?? null])); ?>"><i class="far fa-file-excel me-2 text-success"></i>Export Stock Excel</a></li>
                            <li><a class="dropdown-item" href="<?php echo e(route('inventory.Products.export', ['format' => 'csv', 'search' => $search ?? null])); ?>"><i class="fas fa-file-csv me-2 text-primary"></i>Export Stock CSV</a></li>
                            <li><a class="dropdown-item" href="#" id="export_pdf"><i class="far fa-file-pdf me-2 text-danger"></i>Export Stock PDF</a></li>
                        </ul>
                    </div>

                    <div class="dropdown inventory-toolbar-import">
                        <button class="btn btn-outline-success dropdown-toggle inventory-tool-btn" type="button" data-bs-toggle="dropdown">
                            <i class="fas fa-download me-1"></i> Bulk Stock Import
                        </button>
                        <ul class="dropdown-menu dropdown-menu-end">
                            <li><a class="dropdown-item" href="<?php echo e(route('inventory.Products.import.template')); ?>"><i class="far fa-file-lines me-2 text-primary"></i>Download Stock Template</a></li>
                            <li><button type="button" class="dropdown-item" data-bs-toggle="modal" data-bs-target="#importGuideModal"><i class="fas fa-file-upload me-2 text-success"></i>Upload Stock Spreadsheet</button></li>
                            @php($lastImportKey = 'product_import_last_' . (auth()->id() ?? 'guest'))
                            <?php if (\Illuminate\Support\Facades\Cache::has($lastImportKey)) { ?>
                                <li>
                                    <form action="<?php echo e(route('inventory.Products.import.undo')); ?>" method="POST" onsubmit="return confirm('Undo the last product import? This will delete the imported items and reset their stock.');">
                                        <?php echo csrf_field(); ?>
                                          <button type="submit" class="dropdown-item text-danger">
                                              <i class="fa-solid fa-rotate me-2"></i>Undo Last Import
                                          </button>
                                    </form>
                                </li>
                            <?php } ?>
                        </ul>
                    </div>

                    <a href="<?php echo e(route('add-products')); ?>" class="btn btn-success desktop-add-product-trigger inventory-tool-btn inventory-toolbar-add">
                        <i class="fa fa-plus"></i> Add Product
                    </a>
                    <?php if (\Illuminate\Support\Facades\Route::has('inventory.damages')) { ?>
                        <a href="<?php echo e(route('inventory.damages')); ?>" class="btn btn-outline-danger inventory-tool-btn inventory-toolbar-damages">
                            <i class="fas fa-triangle-exclamation"></i> Stock Damages
                        </a>
                    <?php } ?>
                    <?php if ($showStockTransferModal) { ?>
                        <button type="button" class="btn btn-outline-dark inventory-tool-btn inventory-toolbar-transfer" data-bs-toggle="modal" data-bs-target="#transferStockModal">
                            <i class="fas fa-right-left"></i> Transfer Stock
                        </button>
                    <?php } ?>
                </div>
            </div>
        </div>

        <div class="card shadow-sm">
            <div class="card-body inventory-table-card-body">
                <form id="bulk-delete-products-form" method="POST" action="<?php echo e(route('inventory.Products.bulk-destroy')); ?>" class="inventory-bulk-bar no-print">
                    <?php echo csrf_field(); ?>
                    <?php echo method_field('DELETE'); ?>
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
                            <?php if ($hasProductRows) {
                                $productIndex = method_exists($products, 'firstItem') ? ($products->firstItem() ?? 1) : 1;
                                foreach ($productRowsForView as $product) {
                            ?>
                                    <tr>
                                        <td class="inventory-select-cell no-print">
                                            <input type="checkbox" class="form-check-input product-select-checkbox" value="<?php echo e($product->id); ?>" aria-label="Select <?php echo e($product->name); ?>">
                                        </td>
                                        <td><?php echo e($productIndex); ?></td>
                                        <td>
                                            <div class="d-flex align-items-center">
                                                <?php if (!empty($product->image_url)) { ?>
                                                    <img src="<?php echo e($product->image_url); ?>" class="rounded me-2 product-thumb-img" alt="<?php echo e($product->name); ?>" loading="lazy" onerror="this.classList.add('d-none'); if (this.nextElementSibling) this.nextElementSibling.classList.remove('d-none');">
                                                    <span class="product-thumb-empty d-none"><i class="fas fa-box-open"></i></span>
                                                <?php } else { ?>
                                                    <span class="product-thumb-empty"><i class="fas fa-box-open"></i></span>
                                                <?php } ?>
                                                <div>
                                                    <div class="fw-bold text-dark"><?php echo e($product->name); ?></div>
                                                    <small class="text-muted"><?php echo e($product->sku); ?></small>
                                                </div>
                                            </div>
                                        </td>
                                        <td><?php echo e($product->category_name ?? 'N/A'); ?></td>
                                        <td><span class="badge bg-soft-info text-info"><?php echo e($product->base_unit_name); ?></span></td>
                                        <td>
                                                <?php
                                                    $piecesPerCarton = max((int) ($product->units_per_carton ?? 0), 0);
                                                    $piecesPerRoll = max((int) ($product->units_per_roll ?? 0), 0);
                                                    $rollsPerCarton = $piecesPerRoll > 0 && $piecesPerCarton > 0 ? (int) floor($piecesPerCarton / $piecesPerRoll) : 0;
                                                ?>
                                                <?php if ((int) ($product->units_per_roll ?? 0) > 0) { ?>
                                                    <small class="d-block text-nowrap">Pieces / Carton: <strong><?php echo e($piecesPerCarton); ?></strong></small>
                                                    <small class="d-block text-nowrap">Pieces / Roll: <strong><?php echo e($piecesPerRoll); ?></strong></small>
                                                    <small class="d-block text-nowrap">Rolls / Carton: <strong><?php echo e($rollsPerCarton); ?></strong></small>
                                                <?php } else { ?>
                                                    <small class="d-block text-nowrap">Pieces / Carton: <strong><?php echo e($piecesPerCarton); ?></strong></small>
                                                    <small class="d-block text-nowrap">Roll Layer: <strong>Not used</strong></small>
                                                <?php } ?>
                                        </td>
                                        <td>
                                            <?php
                                                $displayStock = (float) ($product->active_branch_stock ?? $product->stock);
                                                $hasActiveBranch = !empty($activeBranch['name'] ?? null);
                                            ?>
                                            <span class="badge <?php echo e($displayStock <= 5 ? 'bg-danger' : 'bg-success'); ?>">
                                                <?php echo e(rtrim(rtrim(number_format((float) $displayStock, 2), '0'), '.')); ?>
                                            </span>
                                            <?php if ($hasActiveBranch) { ?>
                                                <div class="small text-muted mt-1"><?php echo e($activeBranch['name']); ?></div>
                                            <?php } ?>
                                        </td>
                                        <td>
                                            <div><?php echo e(number_format((float) $product->price, 2)); ?></div>
                                            <?php if (!is_null($product->wholesale_price) || !is_null($product->special_price)) { ?>
                                                <small class="d-block text-muted">Wholesale: <?php echo e(!is_null($product->wholesale_price) ? number_format((float) $product->wholesale_price, 2) : '-'); ?></small>
                                                <small class="d-block text-muted">Special: <?php echo e(!is_null($product->special_price) ? number_format((float) $product->special_price, 2) : '-'); ?></small>
                                            <?php } ?>
                                        </td>
                                        <td><?php echo e(number_format((float) $product->purchase_price, 2)); ?></td>
                                        <td class="text-center no-print">
                                            <div class="dropdown">
                                                <a href="#" class="product-action-trigger dropdown-toggle" data-bs-toggle="dropdown" aria-expanded="false">
                                                    <i class="fas fa-bolt"></i>
                                                    <span>Manage</span>
                                                </a>
                                                <div class="dropdown-menu dropdown-menu-end product-action-menu">
                                                    <a class="dropdown-item" href="<?php echo e(route('inventory.history', $product->id)); ?>"><i class="fas fa-chart-line me-2"></i>Run Report</a>
                                                    <a class="dropdown-item" href="<?php echo e(route('inventory.Products.edit', $product->id)); ?>"><i class="far fa-edit me-2"></i>Edit</a>
                                                    <?php if (\Illuminate\Support\Facades\Route::has('inventory.damage.store')) { ?>
                                                        <button type="button" class="dropdown-item text-warning product-damage-btn"
                                                            data-product-id="<?php echo e($product->id); ?>"
                                                            data-product-name="<?php echo e($product->name); ?>"
                                                            data-available-stock="<?php echo e($displayStock); ?>"
                                                            data-bs-toggle="modal"
                                                            data-bs-target="#recordDamageModal">
                                                            <i class="fas fa-triangle-exclamation me-2"></i>Record Damage
                                                        </button>
                                                    <?php } ?>
                                                    <form action="<?php echo e(route('inventory.Products.destroy', $product->id)); ?>" method="POST" onsubmit="return confirm('Delete this product?');">
                                                        <?php echo csrf_field(); ?><?php echo method_field('DELETE'); ?>
                                                        <button type="submit" class="dropdown-item text-danger"><i class="far fa-trash-alt me-2"></i>Delete</button>
                                                    </form>
                                                </div>
                                            </div>
                                        </td>
                                    </tr>
                            <?php
                                    $productIndex++;
                                }
                            } else {
                            ?>
                                <tr>
                                    <td colspan="10" class="text-center text-muted py-4">No products found.</td>
                                </tr>
                            <?php } ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<a href="<?php echo e(route('add-products')); ?>" class="mobile-add-product-trigger no-print" aria-label="Add product">
    <i class="fas fa-plus"></i>
    <span>Add Product</span>
</a>

<?php if ($showStockTransferModal) { ?>
<div class="modal fade" id="transferStockModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST" action="<?php echo e(route('inventory.transfer')); ?>">
                <?php echo csrf_field(); ?>
                <div class="modal-header">
                    <h5 class="modal-title">Transfer Stock Between Branches</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p class="text-muted mb-3">Move an existing company product to another branch without importing or creating the product again. Only branch stock changes; total company stock stays the same.</p>
                    <div class="mb-3">
                        <label class="form-label">Product</label>
                        <select name="product_id" class="form-select" required>
                            <option value="">Select product</option>
                            <?php foreach ($transferProductsForView as $product) { ?>
                                <option value="<?php echo e($product->id); ?>"><?php echo e($product->name); ?> (<?php echo e($product->sku); ?>)</option>
                            <?php } ?>
                        </select>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">From Branch</label>
                            <select name="from_branch_id" class="form-select" required>
                                <option value="">Select source</option>
                                <?php foreach ($branchOptionsForView as $branch) { ?>
                                    <option value="<?php echo e($branch['id']); ?>"><?php echo e($branch['name']); ?></option>
                                <?php } ?>
                            </select>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">To Branch</label>
                            <select name="to_branch_id" class="form-select" required>
                                <option value="">Select destination</option>
                                <?php foreach ($branchOptionsForView as $branch) { ?>
                                    <option value="<?php echo e($branch['id']); ?>"><?php echo e($branch['name']); ?></option>
                                <?php } ?>
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
<?php } ?>

<?php if (\Illuminate\Support\Facades\Route::has('inventory.damage.store')) { ?>
<div class="modal fade" id="recordDamageModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <form method="POST" action="<?php echo e(route('inventory.damage.store')); ?>">
                <?php echo csrf_field(); ?>
                <div class="modal-header">
                    <h5 class="modal-title">Record Damaged Stock</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Product</label>
                        <select name="product_id" class="form-select" id="damage-product-select" required>
                            <option value="">Select product</option>
                            <?php foreach ($transferProductsForView as $product) { ?>
                                <option value="<?php echo e($product->id); ?>"><?php echo e($product->name); ?> (<?php echo e($product->sku); ?>)</option>
                            <?php } ?>
                        </select>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Damaged Quantity</label>
                            <input type="number" step="0.01" min="0.01" name="quantity" class="form-control" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Reason</label>
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
                        <label class="form-label">Notes</label>
                        <textarea name="remarks" class="form-control" rows="3" placeholder="Batch, expiry date, staff note, or disposal detail"></textarea>
                    </div>
                    <div class="small text-muted mt-2" id="damage-stock-note"></div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    <button type="submit" class="btn btn-danger">Record Damage</button>
                </div>
            </form>
        </div>
    </div>
</div>
<?php } ?>

<div class="modal fade import-guide-modal" id="importGuideModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content">
            <div class="import-guide-hero">
                <div class="d-flex align-items-start justify-content-between gap-3">
                    <div>
                        <h5 class="modal-title mb-2">Before You Import</h5>
                        <p>SmartProbook can create or update products from CSV, XLS, or XLSX files. Only the product name is required.</p>
                    </div>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
            </div>
            <div class="modal-body p-4">
                <div class="import-guide-grid mb-3">
                    <div class="import-guide-panel">
                        <h6><i class="fas fa-circle-check text-success me-2"></i>Required Column</h6>
                        <ul class="import-guide-list">
                            <li><strong>name</strong> - product or stock item name.</li>
                        </ul>
                    </div>
                    <div class="import-guide-panel">
                        <h6><i class="fas fa-sliders text-primary me-2"></i>Optional Columns</h6>
                        <ul class="import-guide-list">
                            <li>sku, barcode, category, unit, unit_type, description.</li>
                            <li>retail_price, wholesale_price, special_price, purchase_price.</li>
                            <li>stock, stock_units, stock_cartons, stock_rolls, units_per_carton, units_per_roll.</li>
                        </ul>
                    </div>
                </div>
                <div class="import-guide-examples">
                    <h6><i class="far fa-lightbulb text-warning me-2"></i>Examples</h6>
                    <div class="table-responsive">
                        <table class="table table-sm import-example-table">
                            <thead>
                                <tr>
                                    <th>name</th>
                                    <th>unit</th>
                                    <th>unit_type</th>
                                    <th>stock_units</th>
                                    <th>stock</th>
                                    <th>retail_price</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td>Rice 50kg</td>
                                    <td>kg</td>
                                    <td>unit</td>
                                    <td>25</td>
                                    <td></td>
                                    <td>75000</td>
                                </tr>
                                <tr>
                                    <td>Groundnut Oil</td>
                                    <td>litre</td>
                                    <td>unit</td>
                                    <td>30</td>
                                    <td></td>
                                    <td></td>
                                </tr>
                                <tr>
                                    <td>Indomie Chicken Carton</td>
                                    <td>pcs</td>
                                    <td>carton</td>
                                    <td>0</td>
                                    <td>400</td>
                                    <td>250</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                    <p class="small text-muted mb-0 mt-2">For KG and LITRE products, SmartProbook uses <strong>stock_units</strong> as opening stock. Blank or text values in number fields are safely treated as 0.</p>
                </div>
            </div>
            <div class="modal-footer">
                <a href="<?php echo e(route('inventory.Products.import.template')); ?>" class="btn btn-light border">
                    <i class="far fa-file-lines me-2"></i>Template
                </a>
                <button type="button" class="btn btn-primary" data-bs-dismiss="modal" data-bs-toggle="modal" data-bs-target="#importProductsModal">
                    OK / Continue
                </button>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="importProductsModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST" action="<?php echo e(route('inventory.Products.import')); ?>" enctype="multipart/form-data">
                <?php echo csrf_field(); ?>
                <div class="modal-header">
                    <h5 class="modal-title">Bulk Stock Import</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p class="text-muted mb-3">Use the stock spreadsheet template to import many products, prices, packaging, and opening stock quantities at once. Missing SKU values will be generated automatically.</p>
                    <div class="alert alert-info small mb-3">
                        <strong>SmartProbook import guide:</strong>
                        <ul class="mb-0 ps-3">
                            <li>Only the product name is required. Other columns can be left blank and will default safely.</li>
                            <li>Use existing column names where possible: name, sku, barcode, category, unit, unit_type, stock, retail_price, wholesale_price, special_price, purchase_price.</li>
                            <li>For measured products, put KG or LITRE in unit_type or unit. The importer will map them to kg or litre units.</li>
                            <li>Packaging fields such as units_per_carton, units_per_roll, stock_cartons, stock_rolls, and stock_units must be numbers. Text values are treated as 0.</li>
                            <li>Leave SKU blank if you want the system to generate one automatically.</li>
                        </ul>
                    </div>
                    <div class="mb-3">
                        <a href="<?php echo e(route('inventory.Products.import.template')); ?>" class="btn btn-light border w-100">
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
                            <?php foreach ($branchOptionsForView as $branch) { ?>
                                <option value="<?php echo e($branch['id']); ?>"><?php echo e($branch['name']); ?></option>
                            <?php } ?>
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
<!-- Required DataTables Buttons Assets -->
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

        $(document).on('click', '.product-damage-btn', function() {
            var productId = String($(this).data('product-id') || '');
            var productName = String($(this).data('product-name') || 'Selected product');
            var availableStock = String($(this).data('available-stock') || '0');

            $('#damage-product-select').val(productId);
            $('#damage-stock-note').text(productName + ' has ' + availableStock + ' available in the active branch.');
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
