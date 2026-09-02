@extends('layout.mainlayout')
@section('content')
@php
    $showAdvancedFields = $errors->hasAny(['sku', 'barcode', 'wholesale_price', 'special_price', 'reorder_level', 'reorder_quantity', 'unit_type']);
    $oldUnitsPerCarton = (float) old('units_per_carton', 0);
    $oldUnitsPerRoll = (float) old('units_per_roll', 0);
    $oldRollsPerCarton = $oldUnitsPerRoll > 0 ? $oldUnitsPerCarton : 0;
    $oldPiecesPerCarton = $oldUnitsPerRoll > 0 ? $oldUnitsPerCarton * $oldUnitsPerRoll : $oldUnitsPerCarton;
@endphp

<style>
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

    .product-form-sheet {
        border: 1px solid #e5e7eb;
        border-radius: 22px;
        background: #fff;
        padding: 1.25rem;
        min-height: 100%;
        box-shadow: 0 18px 42px rgba(15, 23, 42, 0.07);
    }

    .product-form-sheet h6 {
        font-weight: 700;
        color: #111827;
        margin-bottom: 0.2rem;
    }

    .product-form-sheet .form-control,
    .product-form-sheet .form-select {
        color: #1f2937;
        background-color: #ffffff;
        border-color: #dbe3f0;
    }

    .product-form-sheet .form-select option,
    .product-form-sheet .form-control option {
        background-color: #ffffff;
        color: #111827;
    }

    .product-form-sheet .form-select:focus,
    .product-form-sheet .form-control:focus {
        border-color: #60a5fa;
        box-shadow: 0 0 0 0.2rem rgba(96, 165, 250, 0.18);
    }

    .quick-summary-pills {
        display: flex;
        flex-wrap: wrap;
        gap: 0.75rem;
        margin-top: 0.75rem;
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

    .page-add-product-card {
        border: 1px solid #e5e7eb;
        border-radius: 16px;
        box-shadow: 0 4px 24px rgba(15,23,42,0.06);
    }

    .page-add-product-card .card-header {
        background:
            radial-gradient(600px 180px at 8% 0%, rgba(20, 184, 166, 0.14), transparent 62%),
            radial-gradient(520px 180px at 92% 0%, rgba(245, 158, 11, 0.16), transparent 58%),
            linear-gradient(135deg, #f7fbff 0%, #fffdf6 100%);
        border-bottom: 1px solid #dbe7ff;
        border-radius: 16px 16px 0 0 !important;
        padding: 1.1rem 1.4rem;
    }

    .product-form-grid {
        display: grid;
        grid-template-columns: minmax(0, 1.08fr) minmax(360px, 0.92fr);
        gap: 1rem;
        align-items: stretch;
    }

    .product-form-card-head {
        display: flex;
        align-items: flex-start;
        justify-content: space-between;
        gap: 1rem;
        margin-bottom: 1rem;
    }

    .product-form-card-eyebrow {
        color: #0f766e;
        font-size: 0.74rem;
        font-weight: 800;
        letter-spacing: 0.08em;
        text-transform: uppercase;
        margin-bottom: 0.2rem;
    }

    .product-form-card-icon {
        width: 42px;
        height: 42px;
        border-radius: 14px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        color: var(--spb-icon-fg, #92400e);
        background: var(--spb-icon-bg, #fef3c7);
        flex: 0 0 auto;
    }

    .product-form-card-icon i,
    .product-form-card-icon svg {
        color: inherit;
        font-size: 1.12rem;
    }

    .product-form-card-icon.icon-tone-amber {
        --spb-icon-bg: #fef3c7;
        --spb-icon-fg: #92400e;
    }

    .product-form-card-icon.icon-tone-green {
        --spb-icon-bg: #dcfce7;
        --spb-icon-fg: #065f46;
    }

    .unit-suggestion-row {
        display: flex;
        flex-wrap: wrap;
        gap: 0.45rem;
        margin-top: 0.55rem;
    }

    .unit-suggestion-chip {
        border: 1px solid #dbe3f0;
        background: #f8fafc;
        color: #334155;
        border-radius: 999px;
        padding: 0.28rem 0.65rem;
        font-size: 0.78rem;
        font-weight: 700;
        line-height: 1.2;
    }

    .unit-suggestion-chip:hover {
        border-color: #0f766e;
        background: #ecfdf5;
        color: #0f766e;
    }

    .measurement-divider {
        border-top: 1px solid #e5e7eb;
        margin: 1.15rem 0;
    }

    .unit-action-link {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 0.2rem;
        border: 1px solid #bfdbfe;
        border-radius: 999px;
        background: #eff6ff;
        color: #1d4ed8;
        padding: 0.12rem 0.45rem !important;
        font-weight: 800;
        font-size: 0.68rem;
        line-height: 1.15;
        text-decoration: none;
        white-space: nowrap;
    }

    .unit-action-link i {
        font-size: 0.7rem;
    }

    .unit-action-link:hover {
        border-color: #60a5fa;
        background: #dbeafe;
        color: #1e40af;
    }

    @media (max-width: 1199.98px) {
        .product-form-grid {
            grid-template-columns: 1fr;
        }
    }

    @media print {
        .no-print, .sidebar, .header { display: none !important; }
        .page-wrapper { margin: 0 !important; }
    }
</style>

<div class="page-wrapper">
    <div class="content container-fluid" style="max-width:1100px; margin:0 auto;">

        {{-- Page header --}}
        <div class="d-flex align-items-center justify-content-between mb-3 no-print">
            <div>
                <h4 class="mb-0 fw-bold text-dark"><i class="feather-package me-2 text-primary"></i>Add New Product</h4>
                <p class="mb-0 text-muted small mt-1">Create a product with stock, pricing, and packaging in one step.</p>
            </div>
            <a href="{{ route('product-list') }}" class="btn btn-outline-secondary btn-sm">
                <i class="feather-arrow-left me-1"></i> Back to Products
            </a>
        </div>

        {{-- Flash messages --}}
        @if (session('success'))
            <div class="alert alert-success alert-dismissible fade show shadow-sm border-0" role="alert">
                <i class="feather-check-circle me-2"></i>{{ session('success') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        @endif

        @if (session('error') && !$errors->any())
            <div class="alert alert-danger alert-dismissible fade show shadow-sm border-0" role="alert">
                <i class="feather-alert-triangle me-2"></i>{{ session('error') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        @endif

        @if ($errors->any())
            <div class="alert alert-danger alert-dismissible fade show shadow-sm border-0" role="alert">
                <i class="feather-alert-triangle me-2"></i>
                <strong>Please fix the errors below:</strong>
                <ul class="mb-0 mt-1 ps-3">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        @endif

        <div id="page_category_success" class="alert alert-success alert-dismissible d-none" role="alert">
            <span id="page_category_success_text"></span>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>

        {{-- Main card --}}
        <div class="card page-add-product-card border-0 mb-4">
            <div class="card-header">
                <h5 class="mb-0 fw-bold" style="color:#111827;">Add New Product</h5>
            </div>
            <div class="card-body p-4">
                <form method="POST" action="{{ route('inventory.Products.store') }}" enctype="multipart/form-data" id="add_product_form" novalidate>
                    @csrf
                    <div class="row g-3">

                        {{-- Flow banner --}}
                        <div class="col-12">
                            <div class="product-flow-banner">
                                <div class="product-flow-step">
                                    <strong>1. Product Details</strong>
                                    <span>Name, optional category, branch, prices, and image.</span>
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
                            <div class="product-form-grid">
                        {{-- Section 1: Product Details --}}
                        <div>
                            <div class="product-form-sheet">
                                <div class="product-form-card-head">
                                    <div>
                                        <div class="product-form-card-eyebrow">Product setup</div>
                                        <h6>Identity, Pricing & Category</h6>
                                        <p class="product-form-muted mb-0">Enter the product details customers and your team will recognize.</p>
                                    </div>
                                    <span class="product-form-card-icon icon-tone-amber"><i class="fas fa-box-open"></i></span>
                                </div>
                                <div class="row g-3">
                                    <div class="col-md-6">
                                        <label class="form-label">Product Name <span class="text-danger">*</span></label>
                                        <input type="text" name="name" class="form-control @error('name') is-invalid @enderror" placeholder="e.g. Big Bull Rice 50kg" value="{{ old('name') }}" required>
                                        @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">Category</label>
                                        <div class="input-group">
                                            <select name="category_id" id="product_category_select" class="form-select quick-category-select @error('category_id') is-invalid @enderror">
                                                <option value="">No category</option>
                                                @foreach($categories as $cat)
                                                    <option value="{{ $cat->id }}" @selected((string) old('category_id') === (string) $cat->id)>{{ $cat->name }}</option>
                                                @endforeach
                                            </select>
                                            <button type="button" class="btn btn-outline-primary" data-bs-toggle="modal" data-bs-target="#addCategoryModal" title="Quick add category">+</button>
                                        </div>
                                        @error('category_id')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">Legacy Unit Label</label>
                                        <input type="text" name="base_unit_name" class="form-control @error('base_unit_name') is-invalid @enderror" value="{{ old('base_unit_name', 'pcs') }}" list="baseUnitSuggestions" required>
                                        <datalist id="baseUnitSuggestions">
                                            <option value="pcs">
                                            <option value="kg">
                                            <option value="g">
                                            <option value="litre">
                                            <option value="ml">
                                            <option value="meter">
                                            <option value="pack">
                                            <option value="bottle">
                                            <option value="carton">
                                            <option value="roll">
                                        </datalist>
                                        @error('base_unit_name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">Unit of Measure <span class="text-danger">*</span></label>
                                        <select name="unit_id" class="form-select @error('unit_id') is-invalid @enderror" required>
                                            <option value="">Select unit</option>
                                            @foreach(($units ?? collect()) as $unit)
                                                <option value="{{ $unit->id }}" data-symbol="{{ $unit->symbol }}" @selected((string) old('unit_id') === (string) $unit->id || (!old('unit_id') && $unit->symbol === 'pcs'))>
                                                    {{ $unit->name }} ({{ $unit->symbol }})
                                                </option>
                                            @endforeach
                                        </select>
                                        @error('unit_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                    </div>
                                    <div class="col-md-6">
                                        <div class="d-flex justify-content-between align-items-center gap-2">
                                            <label class="form-label mb-0">Base Unit</label>
                                            <button type="button" class="btn btn-link unit-action-link" data-bs-toggle="modal" data-bs-target="#addUnitModal" title="Add base measurement">
                                                <i class="fas fa-plus-circle"></i> Add unit
                                            </button>
                                        </div>
                                        <select name="base_unit_id" class="form-select @error('base_unit_id') is-invalid @enderror">
                                            <option value="">Same as Unit of Measure</option>
                                            @foreach(($units ?? collect()) as $unit)
                                                <option value="{{ $unit->id }}" data-symbol="{{ $unit->symbol }}" @selected((string) old('base_unit_id') === (string) $unit->id)>
                                                    {{ $unit->name }} ({{ $unit->symbol }})
                                                </option>
                                            @endforeach
                                        </select>
                                        <div class="unit-suggestion-row" aria-label="Base unit suggestions">
                                            <button type="button" class="unit-suggestion-chip" data-unit-suggestion="pcs">pcs</button>
                                            <button type="button" class="unit-suggestion-chip" data-unit-suggestion="kg">kg</button>
                                            <button type="button" class="unit-suggestion-chip" data-unit-suggestion="litre">litre</button>
                                            <button type="button" class="unit-suggestion-chip" data-unit-suggestion="pack">pack</button>
                                        </div>
                                        @error('base_unit_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">Purchase Unit</label>
                                        <select name="purchase_unit_id" class="form-select @error('purchase_unit_id') is-invalid @enderror">
                                            <option value="">No bulk purchase unit</option>
                                            @foreach(($units ?? collect()) as $unit)
                                                <option value="{{ $unit->id }}" data-symbol="{{ $unit->symbol }}" @selected((string) old('purchase_unit_id') === (string) $unit->id)>
                                                    {{ $unit->name }} ({{ $unit->symbol }})
                                                </option>
                                            @endforeach
                                        </select>
                                        @error('purchase_unit_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">Conversion Rate</label>
                                        <input type="number" step="0.000001" min="0" name="conversion_rate" class="form-control @error('conversion_rate') is-invalid @enderror" value="{{ old('conversion_rate') }}" placeholder="e.g. 12">
                                        <small class="text-muted">Optional when selling directly in the same unit. Use only when one purchase unit contains multiple base units.</small>
                                        @error('conversion_rate')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">Retail / Default Price <span class="text-danger">*</span></label>
                                        <input type="number" step="0.01" name="price" class="form-control @error('price') is-invalid @enderror" placeholder="0.00" value="{{ old('price') }}" required>
                                        @error('price')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">Purchase Price <span class="text-danger">*</span></label>
                                        <input type="number" step="0.01" name="purchase_price" class="form-control @error('purchase_price') is-invalid @enderror" placeholder="0.00" value="{{ old('purchase_price') }}" required>
                                        @error('purchase_price')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">Stock Branch</label>
                                        <select name="branch_id" class="form-select @error('branch_id') is-invalid @enderror">
                                            <option value="">Use Active Branch</option>
                                            @foreach(($availableBranches ?? []) as $branch)
                                                <option value="{{ $branch['id'] }}" @selected((string) old('branch_id') === (string) ($branch['id'] ?? ''))>{{ $branch['name'] }}</option>
                                            @endforeach
                                        </select>
                                        @error('branch_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">Product Image</label>
                                        <input type="file" name="image" id="product_image_input" class="form-control @error('image') is-invalid @enderror">
                                        <small class="text-muted">Optional.</small>
                                        @error('image')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">Expiry Date <span class="text-muted small">(optional)</span></label>
                                        <input type="date" name="expiry_date" class="form-control @error('expiry_date') is-invalid @enderror" value="{{ old('expiry_date') }}">
                                        <small class="text-muted">Use this for perishable or date-sensitive products.</small>
                                        @error('expiry_date')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                    </div>
                                </div>
                            </div>
                        </div>

                        {{-- Section 2: Packaging Setup --}}
                        <div>
                            <div class="product-form-sheet">
                                <div class="product-form-card-head">
                                    <div>
                                        <div class="product-form-card-eyebrow">Measurement</div>
                                        <h6>Packaging & Opening Stock</h6>
                                        <p class="product-form-muted mb-0">Set how the product is counted, then enter what is currently on hand.</p>
                                    </div>
                                    <span class="product-form-card-icon icon-tone-green"><i class="fas fa-balance-scale"></i></span>
                                </div>
                                <h6 class="mb-1">Packaging Setup</h6>
                                <p class="product-form-muted">Enter any two values below and the third one fills automatically.</p>
                                <div class="row g-3">
                                    <div class="col-md-4">
                                        <label class="form-label">Rolls Per Ctn</label>
                                        <input type="number" id="quick_rolls_per_carton_helper" min="0" step="0.01" class="form-control" value="{{ $oldRollsPerCarton }}">
                                        <small class="text-muted">How many rolls are inside one carton.</small>
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label">Pcs Per Roll</label>
                                        <input type="number" id="quick_pcs_per_roll_helper" min="0" step="0.01" class="form-control" value="{{ $oldUnitsPerRoll }}">
                                        <small class="text-muted">How many pcs are inside one roll.</small>
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label">Pcs Per Ctn</label>
                                        <input type="number" id="quick_pcs_per_carton_helper" min="0" step="0.01" class="form-control" value="{{ $oldPiecesPerCarton }}" readonly>
                                        <small class="text-muted">Calculated from rolls per carton and pcs per roll.</small>
                                    </div>
                                    <input type="hidden" name="units_per_roll" id="quick_units_per_roll_input" value="{{ old('units_per_roll', 0) }}">
                                    <input type="hidden" name="units_per_carton" id="quick_units_per_carton_input" value="{{ old('units_per_carton', 0) }}">
                                    <input type="hidden" name="unit_type" id="quick_unit_type_input" value="{{ old('unit_type', 'unit') }}">
                                </div>

                        {{-- Section 3: Opening Stock --}}
                                <div class="measurement-divider"></div>
                                <h6>Opening Stock</h6>
                                <p class="product-form-muted">Type the quantity you currently have. Total stock appears automatically. If you do not have stock yet, leave all three fields at 0 and save the product first.</p>
                                <div class="row g-3">
                                    <div class="col-md-4">
                                        <label class="form-label">Opening Ctn</label>
                                        <input type="number" step="0.01" name="stock_cartons" class="form-control @error('stock_cartons') is-invalid @enderror" value="{{ old('stock_cartons', 0) }}">
                                        @error('stock_cartons')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label">Opening Roll</label>
                                        <input type="number" step="0.01" name="stock_rolls" class="form-control @error('stock_rolls') is-invalid @enderror" value="{{ old('stock_rolls', 0) }}">
                                        @error('stock_rolls')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label" id="quick_opening_unit_label">Opening Pcs</label>
                                        <input type="number" step="0.01" name="stock_units" class="form-control @error('stock_units') is-invalid @enderror" value="{{ old('stock_units', 0) }}">
                                        @error('stock_units')<div class="invalid-feedback">{{ $message }}</div>@enderror
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
                                                <span>Entered Mix</span>
                                                <strong id="quick_stock_mix_preview_text">0 ctn + 0 roll + 0 pcs</strong>
                                            </div>
                                            <div class="quick-summary-pill">
                                                <span>Estimated Opening Value</span>
                                                <strong id="quick_stock_value_preview">0.00</strong>
                                            </div>
                                        </div>
                                        <input type="hidden" name="stock" id="quick_final_stock_input" value="{{ old('stock', '') }}">
                                    </div>
                                </div>
                            </div>
                        </div>
                            </div>
                        </div>

                        {{-- Advanced Fields toggle --}}
                        <div class="col-12">
                            <button class="btn btn-light border product-collapse-toggle" type="button" data-bs-toggle="collapse" data-bs-target="#advancedProductFields" aria-expanded="false" aria-controls="advancedProductFields">
                                <i class="fas fa-sliders-h"></i>
                                <span>Advanced Fields</span>
                            </button>
                        </div>

                        <div class="col-12 collapse @if($showAdvancedFields) show @endif" id="advancedProductFields">
                            <div class="product-form-sheet">
                                <h6>Advanced Options</h6>
                                <p class="product-form-muted">Only open this when the product needs a SKU, barcode, or extra price levels.</p>
                                <div class="row g-3">
                                    <div class="col-md-6">
                                        <label class="form-label">SKU</label>
                                        <input type="text" name="sku" class="form-control @error('sku') is-invalid @enderror" placeholder="Leave blank to auto-generate" value="{{ old('sku') }}">
                                        <small class="text-muted">If there is no product code yet, the system creates one automatically.</small>
                                        @error('sku')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">Barcode</label>
                                        <input type="text" name="barcode" class="form-control @error('barcode') is-invalid @enderror" placeholder="Scan or type barcode" value="{{ old('barcode') }}">
                                        @error('barcode')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label">Wholesale Price</label>
                                        <input type="number" step="0.01" name="wholesale_price" class="form-control @error('wholesale_price') is-invalid @enderror" placeholder="Optional" value="{{ old('wholesale_price') }}">
                                        @error('wholesale_price')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label">Special Discount Price</label>
                                        <input type="number" step="0.01" name="special_price" class="form-control @error('special_price') is-invalid @enderror" placeholder="Optional" value="{{ old('special_price') }}">
                                        @error('special_price')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label">Reorder Level</label>
                                        <input type="number" name="reorder_level" min="0" class="form-control @error('reorder_level') is-invalid @enderror" value="{{ old('reorder_level', 0) }}">
                                        @error('reorder_level')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label">Reorder Quantity</label>
                                        <input type="number" name="reorder_quantity" min="0" class="form-control @error('reorder_quantity') is-invalid @enderror" value="{{ old('reorder_quantity', 0) }}">
                                        @error('reorder_quantity')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                    </div>
                                </div>
                            </div>
                        </div>

                        {{-- Save button --}}
                        <div class="col-12 d-flex justify-content-between align-items-center pt-2">
                            <p class="text-muted small mb-0">Save once and the product, pricing, and opening stock will be ready together.</p>
                            <button type="submit" class="btn btn-primary px-5" id="page_add_product_submit">Save Product</button>
                        </div>

                    </div>{{-- /row --}}
                </form>
            </div>
        </div>

    </div>
</div>

{{-- Quick Add Category Modal --}}
<div class="modal fade" id="addCategoryModal" tabindex="-1" aria-hidden="true" style="z-index:1060;">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <form id="ajaxAddCategoryForm">
                @csrf
                <input type="hidden" name="type" value="product">
                <div class="modal-header">
                    <h5 class="modal-title">Quick Category</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div id="quick_category_success_message" class="alert alert-success d-none" role="alert"></div>
                    <div id="quick_category_error_message" class="alert alert-danger d-none" role="alert"></div>
                    <input type="text" name="name" id="new_category_name" class="form-control" placeholder="Category Name" required>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light border" data-bs-dismiss="modal">Close</button>
                    <button type="submit" class="btn btn-primary">Add</button>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- Quick Add Base Measurement Modal --}}
<div class="modal fade" id="addUnitModal" tabindex="-1" aria-hidden="true" style="z-index:1060;">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <form method="POST" action="{{ route('units.store') }}">
                @csrf
                <input type="hidden" name="status" value="active">
                <div class="modal-header">
                    <div>
                        <h5 class="modal-title mb-0">Add Base Measurement</h5>
                        <small class="text-muted">Create a unit like Pieces, Kilogram, Litre, Pack, or Bottle.</small>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="row g-3">
                        <div class="col-md-7">
                            <label class="form-label">Measurement Name <span class="text-danger">*</span></label>
                            <input type="text" name="name" class="form-control" placeholder="e.g. Pieces" list="unitNameSuggestions" required>
                            <datalist id="unitNameSuggestions">
                                <option value="Pieces">
                                <option value="Kilogram">
                                <option value="Gram">
                                <option value="Litre">
                                <option value="Millilitre">
                                <option value="Meter">
                                <option value="Pack">
                                <option value="Bottle">
                                <option value="Carton">
                                <option value="Roll">
                            </datalist>
                        </div>
                        <div class="col-md-5">
                            <label class="form-label">Symbol <span class="text-danger">*</span></label>
                            <input type="text" name="symbol" class="form-control" placeholder="e.g. pcs" list="unitSymbolSuggestions" required>
                            <datalist id="unitSymbolSuggestions">
                                <option value="pcs">
                                <option value="kg">
                                <option value="g">
                                <option value="litre">
                                <option value="ml">
                                <option value="m">
                                <option value="pack">
                                <option value="bottle">
                                <option value="ctn">
                                <option value="roll">
                            </datalist>
                        </div>
                    </div>
                    <p class="text-muted small mb-0 mt-3">After saving, come back to this product form and select the new measurement from the unit list.</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light border" data-bs-dismiss="modal">Close</button>
                    <button type="submit" class="btn btn-primary">Add Measurement</button>
                </div>
            </form>
        </div>
    </div>
</div>

@endsection

@push('scripts')
<script>
$(document).ready(function () {
    var categoryIndexUrl = @json(route('ajax.inventory.categories.index', [], false)) + '?type=product';
    var categoryStoreUrl = @json(route('ajax.inventory.categories.store', [], false));
    var quickCategoryError   = $('#quick_category_error_message');
    var quickCategorySuccess = $('#quick_category_success_message');

    // Category helpers
    function showQuickCategoryError(message) {
        quickCategorySuccess.addClass('d-none').text('');
        quickCategoryError.removeClass('d-none').text(message || 'Unable to complete category request.');
    }

    function clearQuickCategoryError() {
        quickCategoryError.addClass('d-none').text('');
    }

    function showQuickCategorySuccess(message) {
        clearQuickCategoryError();
        quickCategorySuccess.removeClass('d-none').text(message || 'Category added successfully.');
    }

    function parseJsonResponse(response, fallbackMessage) {
        return response.text().then(function (raw) {
            try { return JSON.parse(raw); }
            catch (e) { throw new Error(raw && raw.trim().charAt(0) === '<' ? fallbackMessage : (raw || fallbackMessage)); }
        });
    }

    function reloadCategoryOptions(selectedValue) {
        selectedValue = selectedValue || '';
        var select = document.getElementById('product_category_select');
        if (!select) return Promise.resolve();
        return fetch(categoryIndexUrl, {
            method: 'GET', credentials: 'same-origin', cache: 'no-store',
            headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
        })
        .then(function (r) { return parseJsonResponse(r, 'Category list returned HTML instead of JSON.'); })
        .then(function (payload) {
            var categories = Array.isArray(payload && payload.data) ? payload.data : [];
            select.innerHTML = '';
            select.add(new Option('No category', '', false, false));
            categories.forEach(function (cat) {
                if (!cat || !cat.id || !cat.name) return;
                var isSel = selectedValue !== '' && String(cat.id) === String(selectedValue);
                select.add(new Option(cat.name, cat.id, isSel, isSel));
            });
            if (selectedValue !== '') select.value = String(selectedValue);
        })
        .catch(function (err) { showQuickCategoryError(err.message || 'Unable to load categories.'); });
    }

    function initializeQuickCategorySelect() {
        var categorySelect = $('#product_category_select');
        if (!categorySelect.length || !$.fn.select2) return;

        if (!categorySelect.find('option[value=""]').length) {
            categorySelect.prepend(new Option('No category', '', false, false));
        }

        if (categorySelect.hasClass('select2-hidden-accessible')) {
            categorySelect.select2('destroy');
        }

        categorySelect.select2({
            width: '100%',
            placeholder: 'No category',
            dropdownCssClass: 'quick-category-dropdown',
            minimumResultsForSearch: Infinity
        });
    }

    function upsertCategoryOption(selector, category) {
        if (!category || !category.id || !category.name) return;
        var select = document.querySelector(selector);
        if (!select) return;
        var val = String(category.id);
        var opt = Array.from(select.options).find(function (o) { return o.value === val; });
        if (!opt) { opt = new Option(category.name, category.id, true, true); select.add(opt); }
        else { opt.text = category.name; opt.selected = true; }
        select.value = val;
        $(select).trigger('change');
    }

    function findExistingCategory(selector, rawName) {
        var name = String(rawName || '').trim().toLowerCase();
        if (!name) return null;
        var select = document.querySelector(selector);
        if (!select) return null;
        var opt = Array.from(select.options).find(function (o) {
            return o.value && String(o.textContent || '').trim().toLowerCase() === name;
        });
        if (!opt) return null;
        return { id: opt.value, name: String(opt.textContent || '').trim() };
    }

    // Packaging calculation
    var lastPackagingFieldEdited = null;

    function packagingValue(selector) { return parseFloat($(selector).val()) || 0; }
    function setPackagingValue(selector, value) { $(selector).val(value > 0 ? value : 0); }
    function quickBaseUnitLabel() {
        var raw = ($('input[name="base_unit_name"]').val() || 'pcs').trim();
        return raw.length ? raw : 'pcs';
    }
    function formatQuickQty(value) {
        return (parseFloat(value) || 0).toLocaleString(undefined, { maximumFractionDigits: 2 });
    }

    function syncPackagingHiddenFields() {
        var rollsPerCtn = packagingValue('#quick_rolls_per_carton_helper');
        var pcsPerRoll  = packagingValue('#quick_pcs_per_roll_helper');
        var pcsPerCtn   = packagingValue('#quick_pcs_per_carton_helper');
        var unitLabel   = quickBaseUnitLabel();
        $('#quick_units_per_roll_input').val(pcsPerRoll > 0 ? pcsPerRoll : 0);
        $('#quick_units_per_carton_input').val(rollsPerCtn > 0 ? rollsPerCtn : pcsPerCtn);
        $('#quick_units_per_carton_preview_text').text(formatQuickQty(pcsPerCtn > 0 ? pcsPerCtn : 0) + ' ' + unitLabel);
    }

    function syncQuickUnitType() {
        var pcsPerRoll  = packagingValue('#quick_pcs_per_roll_helper');
        var rollsPerCtn = packagingValue('#quick_rolls_per_carton_helper');
        var unitType = 'unit';
        if (rollsPerCtn > 0) unitType = 'carton';
        else if (pcsPerRoll > 0) unitType = 'roll';
        $('#quick_unit_type_input').val(unitType);
    }

    function calculateQuickCartonContent() {
        var rollsPerCtn = packagingValue('#quick_rolls_per_carton_helper');
        var pcsPerRoll  = packagingValue('#quick_pcs_per_roll_helper');
        var pcsPerCtn   = packagingValue('#quick_pcs_per_carton_helper');

        if (rollsPerCtn > 0 && pcsPerRoll > 0) {
            pcsPerCtn = rollsPerCtn * pcsPerRoll;
            setPackagingValue('#quick_pcs_per_carton_helper', pcsPerCtn);
            syncPackagingHiddenFields();
            syncQuickUnitType();
            return;
        }

        if (rollsPerCtn <= 0 || pcsPerRoll <= 0) {
            pcsPerCtn = 0;
            setPackagingValue('#quick_pcs_per_carton_helper', pcsPerCtn);
        }

        var filled = [rollsPerCtn, pcsPerRoll, pcsPerCtn].filter(function (v) { return v > 0; }).length;
        if (filled >= 2) {
            if (lastPackagingFieldEdited === 'pcs_per_ctn' && rollsPerCtn > 0 && pcsPerCtn > 0) {
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
        var cartons       = parseFloat($('input[name="stock_cartons"]').val()) || 0;
        var rolls         = parseFloat($('input[name="stock_rolls"]').val()) || 0;
        var pieces        = parseFloat($('input[name="stock_units"]').val()) || 0;
        var rollsPerCtn   = parseFloat($('#quick_units_per_carton_input').val()) || 0;
        var pcsPerRoll    = parseFloat($('#quick_units_per_roll_input').val()) || 0;
        var pcsPerCtn     = packagingValue('#quick_pcs_per_carton_helper');
        var purchasePrice = parseFloat($('input[name="purchase_price"]').val()) || 0;
        var unitLabel     = quickBaseUnitLabel();

        var fromCartons = pcsPerCtn > 0
            ? cartons * pcsPerCtn
            : (pcsPerRoll > 0 ? cartons * rollsPerCtn * pcsPerRoll : cartons * rollsPerCtn);
        var fromRolls = pcsPerRoll > 0 ? rolls * pcsPerRoll : rolls;
        var total = fromCartons + fromRolls + pieces;

        var stockValue = total * purchasePrice;
        $('#quick_stock_preview_text').text(formatQuickQty(total) + ' ' + unitLabel);
        $('#quick_stock_mix_preview_text').text(formatQuickQty(cartons) + ' ctn + ' + formatQuickQty(rolls) + ' roll + ' + formatQuickQty(pieces) + ' ' + unitLabel);
        $('#quick_stock_value_preview').text(stockValue.toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 }));
        $('#quick_final_stock_input').val(Math.round(total));
    }

    function refreshQuickPackagingLabels() {
        var baseUnitName = ($('input[name="base_unit_name"]').val() || 'pcs').trim();
        var unitLabel = baseUnitName.length ? baseUnitName : 'pcs';
        $('#quick_opening_unit_label').text('Opening ' + unitLabel.charAt(0).toUpperCase() + unitLabel.slice(1));
    }

    // Event bindings
    $('#quick_rolls_per_carton_helper, #quick_pcs_per_roll_helper, #quick_pcs_per_carton_helper').on('input change keyup', function () {
        lastPackagingFieldEdited = $(this).attr('id') === 'quick_rolls_per_carton_helper'
            ? 'rolls'
            : ($(this).attr('id') === 'quick_pcs_per_roll_helper' ? 'pcs_per_roll' : 'pcs_per_ctn');
        calculateQuickCartonContent();
        calculateQuickStock();
    });

    $('input[name="stock_cartons"], input[name="stock_rolls"], input[name="stock_units"], input[name="purchase_price"]').on('input', function () {
        calculateQuickStock();
    });

    var unitSyncing = false;

    $('input[name="base_unit_name"]').on('input', function () {
        applyUnitSymbol($(this).val());
    });

    function normalizeUnitKey(value) {
        var normalized = String(value || '').trim().toLowerCase();
        var aliases = {
            pc: 'pcs',
            pcs: 'pcs',
            piece: 'pcs',
            pieces: 'pcs',
            kg: 'kg',
            kilogram: 'kg',
            kilograms: 'kg',
            kilo: 'kg',
            g: 'g',
            gram: 'g',
            grams: 'g',
            l: 'litre',
            lt: 'litre',
            ltr: 'litre',
            litre: 'litre',
            liter: 'litre',
            liters: 'litre',
            litres: 'litre',
            ml: 'ml',
            millilitre: 'ml',
            millilitres: 'ml',
            milliliter: 'ml',
            milliliters: 'ml',
            m: 'meter',
            meter: 'meter',
            metre: 'meter',
            metres: 'meter',
            pk: 'pack',
            pack: 'pack',
            packs: 'pack',
            bottle: 'bottle',
            bottles: 'bottle',
            carton: 'carton',
            cartons: 'carton',
            ctn: 'carton',
            roll: 'roll',
            rolls: 'roll'
        };

        return aliases[normalized] || normalized;
    }

    function preferredUnitLabel(value) {
        var normalized = normalizeUnitKey(value);
        var labels = {
            pcs: 'pcs',
            kg: 'kg',
            g: 'g',
            litre: 'litre',
            ml: 'ml',
            meter: 'meter',
            pack: 'pack',
            bottle: 'bottle',
            carton: 'carton',
            roll: 'roll'
        };

        return labels[normalized] || String(value || '').trim();
    }

    function optionUnitKeys(option) {
        if (!option) return [];
        var direct = String(option.getAttribute('data-symbol') || option.dataset.symbol || '').trim();
        var text = String(option.textContent || '').trim().toLowerCase();
        var match = text.match(/^(.*?)\s*\((.*?)\)\s*$/);
        var parts = [direct, text, match ? match[1] : '', match ? match[2] : ''];
        return Array.from(new Set(parts.map(normalizeUnitKey).filter(Boolean)));
    }

    function selectedUnitKey(selector) {
        var select = document.querySelector(selector);
        if (!select || !select.value) return '';
        return optionUnitKeys(select.options[select.selectedIndex])[0] || '';
    }

    function selectUnitBySuggestion(selector, symbol) {
        var select = document.querySelector(selector);
        if (!select) return;
        var normalized = normalizeUnitKey(symbol);
        if (!normalized) return;
        var option = Array.from(select.options).find(function (opt) {
            return optionUnitKeys(opt).includes(normalized);
        });
        if (option) {
            var changed = select.value !== option.value;
            select.value = option.value;
            if (changed && !unitSyncing) {
                $(select).trigger('change');
            }
        }
    }

    function applyUnitSymbol(symbol) {
        symbol = preferredUnitLabel(symbol);
        if (!symbol) return;
        if (unitSyncing) return;
        unitSyncing = true;
        $('input[name="base_unit_name"]').val(symbol);
        selectUnitBySuggestion('select[name="unit_id"]', symbol);
        selectUnitBySuggestion('select[name="base_unit_id"]', symbol);
        selectUnitBySuggestion('select[name="purchase_unit_id"]', symbol);
        unitSyncing = false;
        refreshQuickPackagingLabels();
        calculateQuickCartonContent();
        calculateQuickStock();
    }

    $('select[name="unit_id"]').on('change', function () {
        applyUnitSymbol(selectedUnitKey('select[name="unit_id"]'));
    });

    $('select[name="base_unit_id"]').on('change', function () {
        var symbol = selectedUnitKey('select[name="base_unit_id"]') || selectedUnitKey('select[name="unit_id"]');
        applyUnitSymbol(symbol);
    });

    $('select[name="purchase_unit_id"]').on('change', function () {
        applyUnitSymbol(selectedUnitKey('select[name="purchase_unit_id"]'));
    });

    $('.unit-suggestion-chip').on('click', function () {
        var symbol = $(this).data('unit-suggestion') || '';
        applyUnitSymbol(symbol);
    });

    $('#add_product_form').on('submit', function () {
        var img = document.getElementById('product_image_input');
        var submitButton = $('#page_add_product_submit');
        if (img && (!img.files || img.files.length === 0)) img.disabled = true;
        submitButton.prop('disabled', true).text('Saving Product...');
    });

    // AJAX Quick Add Category
    $('#ajaxAddCategoryForm').on('submit', function (e) {
        e.preventDefault();
        var form = this;
        var btn = $(this).find('button[type="submit"]');
        var typedName = $('#new_category_name').val();
        btn.prop('disabled', true);
        clearQuickCategoryError();

        var existing = findExistingCategory('#product_category_select', typedName);
        if (existing) {
            upsertCategoryOption('#product_category_select', existing);
            showQuickCategorySuccess('Existing category selected.');
            bootstrap.Modal.getOrCreateInstance(document.getElementById('addCategoryModal')).hide();
            form.reset();
            btn.prop('disabled', false);
            return;
        }

        fetch(categoryStoreUrl, {
            method: 'POST', credentials: 'same-origin',
            headers: {
                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            },
            body: JSON.stringify({ name: typedName, type: 'product' })
        })
        .then(function (res) {
            return parseJsonResponse(res, 'Category save returned HTML instead of JSON.').then(function (data) {
                if (!res.ok) {
                    var msg = (data && data.message) ||
                        (data && data.errors && Object.values(data.errors)[0] && Object.values(data.errors)[0][0]) ||
                        'Failed to add category.';
                    throw new Error(msg);
                }
                return data;
            });
        })
        .then(function (data) {
            if (data && data.data) {
                upsertCategoryOption('#product_category_select', data.data);
                reloadCategoryOptions(String(data.data.id)).then(function () {
                    showQuickCategorySuccess((data && data.message) || 'Category added successfully.');
                    bootstrap.Modal.getOrCreateInstance(document.getElementById('addCategoryModal')).hide();
                    form.reset();
                });
            }
        })
        .catch(function (err) {
            showQuickCategoryError(err.message || 'Unable to add category.');
        })
        .finally(function () { btn.prop('disabled', false); });
    });

    // Init
    refreshQuickPackagingLabels();
    calculateQuickCartonContent();
    calculateQuickStock();
    reloadCategoryOptions($('#product_category_select').val() || '').finally(function () {
        initializeQuickCategorySelect();
    }).catch(function () {});
});
</script>
@endpush
