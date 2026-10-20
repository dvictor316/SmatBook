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
/* Keep this form compact even when the app theme applies large pill buttons. */
#add-product-page { background:#f5f7fb; padding-bottom:16px; }
body:not(.login-body):not(.landing-page-body) #add-product-page > .content.container-fluid {
    max-width:1200px; margin:0 auto; padding:12px 16px 18px !important;
}
#add-product-page .add-product-heading { display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:10px; margin:0 0 12px; padding:0; }
#add-product-page .add-product-heading > div { display:flex; align-items:center; flex-wrap:wrap; gap:12px; }
#add-product-page .add-product-heading h4 { color:#172b4d !important; font-size:20px; line-height:1.3; margin:0; }
#add-product-page .product-required-note { font-size:12px; color:#64748b; }
#add-product-page .page-add-product-card { overflow:hidden; background:#fff; border:1px solid #dce3ed !important; border-radius:10px !important; box-shadow:0 2px 8px #172b4d06 !important; }
#add-product-page .product-form-body { padding:0 !important; }
#add-product-page #add_product_form > .row { --bs-gutter-y:0; --bs-gutter-x:0; margin:0; }
#add-product-page .product-form-grid { display:grid; grid-template-columns:minmax(0,1fr); gap:0; }
#add-product-page .product-form-sheet { min-width:0; min-height:0; padding:16px 18px; border:0; border-bottom:1px solid #e5eaf1; border-radius:0; background:#fff; box-shadow:none; }
#add-product-page .product-form-card-head { display:flex; align-items:center; justify-content:space-between; gap:8px; margin-bottom:12px; }
#add-product-page .product-form-card-head h6,
#add-product-page .product-form-sheet h6 { color:#172b4d; font-size:15px; font-weight:650; margin:0 0 4px; }
#add-product-page .product-form-muted { margin:0 0 10px; color:#64748b; font-size:12px; line-height:1.4; }
#add-product-page .product-form-sheet .form-label { margin-bottom:5px; color:#334155; font-size:13px; font-weight:600; line-height:1.4; }
#add-product-page .product-form-sheet .form-control,
#add-product-page .product-form-sheet .form-select { min-height:38px; border:1px solid #d8e0eb; border-radius:6px; background:#fff; color:#172b4d; font-size:13px; box-shadow:none; }
#add-product-page .product-form-sheet .form-control:focus,
#add-product-page .product-form-sheet .form-select:focus { border-color:#60a5fa; box-shadow:0 0 0 3px #3b82f620; }
#add-product-page .product-form-sheet .is-invalid { border-color:#dc3545; }
#add-product-page .product-form-sheet .form-control[readonly] { background:#f8fafc; }
#add-product-page .product-details-fields { display:grid; grid-template-columns:repeat(3,minmax(0,1fr)); gap:12px 16px; margin:0; }
#add-product-page .product-details-fields > div { width:auto; min-width:0; padding:0; margin:0; }
#add-product-page .product-details-fields > .col-12 { grid-column:1 / -1; }
#add-product-page .product-details-fields > .product-name-field,
#add-product-page .product-details-fields > .product-image-field { grid-column:span 2; }
#add-product-page .field-hint { display:block; color:#64748b; font-size:11px; line-height:1.4; margin-top:4px; }
#add-product-page .measurement-mode-grid { display:grid; grid-template-columns:repeat(4,minmax(0,1fr)); gap:8px; }
#add_product_form .measurement-mode-button {
    position:relative; display:flex; flex-direction:column; justify-content:center; gap:4px; width:100%; min-width:0;
    min-height:70px !important; margin:0; padding:10px 24px 10px 12px !important;
    border:1px solid #d8e0eb !important; border-radius:6px !important; background:#fff !important;
    color:#334155 !important; -webkit-text-fill-color:#334155 !important; box-shadow:none !important;
    font-family:inherit; font-size:13px !important; font-weight:600 !important; line-height:1.35;
    text-align:left; white-space:normal; cursor:pointer; transform:none !important;
}
#add_product_form .measurement-mode-button strong { color:inherit !important; -webkit-text-fill-color:inherit !important; font-size:13px; font-weight:600; }
#add_product_form .measurement-mode-button small { color:#64748b !important; -webkit-text-fill-color:#64748b !important; font-size:11px; font-weight:400; line-height:1.4; }
#add_product_form .measurement-mode-button:hover { background:#f8fafc !important; border-color:#93b4e8 !important; }
#add_product_form .measurement-mode-button[aria-pressed="true"] { background:#eff6ff !important; border-color:#2563eb !important; color:#1e40af !important; -webkit-text-fill-color:#1e40af !important; box-shadow:inset 0 0 0 1px #2563eb !important; }
#add_product_form .measurement-mode-button[aria-pressed="true"]::after { content:'✓'; position:absolute; right:9px; top:9px; color:#2563eb; font-size:12px; }
#add_product_form .measurement-mode-button:focus-visible { outline:3px solid #93c5fd; outline-offset:2px; }
#add-product-page .measurement-choice { margin-bottom:12px; }
#add-product-page #measurement_selection_summary { margin:8px 0 0 !important; padding:7px 10px; border:1px solid #dbeafe; border-radius:6px; background:#f8fbff; color:#365373; font-size:12px; line-height:1.45; }
#add-product-page #kg_carton_fields,
#add-product-page .carton-mode-field { margin-bottom:12px !important; }
#add-product-page .measurement-divider { border-top:1px solid #e5eaf1; margin:14px 0; }
#add-product-page .quick-summary-pills { display:flex; flex-wrap:wrap; gap:0; margin-top:0; padding:10px 12px; border:1px solid #e5eaf1; border-radius:6px; background:#f8fafc; }
#add-product-page .quick-summary-pill { flex:1 1 180px; min-width:0; padding:4px 12px; border:0; border-left:1px solid #e2e8f0; border-radius:0; background:transparent; }
#add-product-page .quick-summary-pill:first-child { border-left:0; padding-left:0; }
#add-product-page .quick-summary-pill span { display:block; margin-bottom:3px; color:#64748b; font-size:11px; }
#add-product-page .quick-summary-pill strong { color:#172b4d; font-size:14px; font-weight:650; }
#add-product-page .btn:not(.btn-close) { min-height:34px !important; padding:6px 12px !important; border-radius:6px !important; font-size:13px !important; font-weight:600 !important; line-height:1.5; box-shadow:none !important; transform:none !important; }
#add-product-page .input-group .btn { min-height:38px !important; }
#add-product-page .product-collapse-toggle { display:inline-flex; align-items:center; gap:7px; margin:12px 18px; background:#fff !important; color:#475569 !important; border-color:#d8e0eb !important; }
#add-product-page .btn.unit-action-link { min-height:26px !important; padding:2px 7px !important; }
#add-product-page .unit-suggestion-row { display:flex; flex-wrap:wrap; gap:6px; margin-top:6px; }
#add-product-page .unit-suggestion-chip { padding:3px 8px; border:1px solid #d8e0eb; border-radius:5px; background:#f8fafc; color:#475569; font-size:12px; }
#add-product-page .product-price-list-panel { padding:10px 12px; border:1px solid #e2e8f0; border-radius:6px; background:#f8fafc; }
#add-product-page .product-price-list-panel summary { cursor:pointer; list-style:none; }
#add-product-page .summary-title { display:flex; align-items:center; justify-content:space-between; gap:8px; color:#334155; font-size:13px; font-weight:600; }
#add-product-page .summary-title span { color:#64748b; font-size:12px; font-weight:400; }
#add-product-page .product-price-list-panel[open] .price-list-chevron { transform:rotate(180deg); }
#add-product-page .product-form-actions { display:flex; flex-wrap:wrap; gap:12px; padding:12px 18px !important; border-top:1px solid #e5eaf1; background:#f8fafc; }
#add-product-page .product-form-actions p { flex:1 1 240px; font-size:12px; }
#add-product-page #page_add_product_submit { min-height:36px !important; padding:7px 20px !important; border:1px solid #1d4ed8; background:#2563eb !important; color:#fff !important; }
#add-product-page #page_add_product_submit:hover { background:#1d4ed8 !important; }

#add-product-page .product-packaging-fields { display:flex; flex-wrap:wrap; align-items:start; gap:12px 16px; }
#add-product-page .product-packaging-fields > .row { flex:1 1 260px; width:auto; margin:0 !important; --bs-gutter-x:0; --bs-gutter-y:0; }
#add-product-page .product-packaging-fields > #custom_packaging_intro { flex:0 0 100%; }
#add-product-page .product-packaging-fields .col-md-6 { width:100%; }
#add-product-page .opening-stock-fields { --bs-gutter-y:12px; }
#add-product-page .opening-stock-fields > div:not(.col-12) { flex:1 1 160px; width:auto; }

/* Stronger labels and values, with the same compact layout. */
#add-product-page .product-form-sheet h6 { font-weight:700; }
#add-product-page .product-form-muted { font-weight:500; color:#526176; }
#add-product-page .product-form-sheet .form-label { font-weight:700; color:#25354c; }
#add-product-page .product-form-sheet .form-control,
#add-product-page .product-form-sheet .form-select { font-weight:600; color:#172b4d; }
#add_product_form .measurement-mode-button { font-weight:700 !important; }
#add_product_form .measurement-mode-button strong { font-weight:700; }
#add_product_form .measurement-mode-button small { font-size:12px; font-weight:500; }
#add-product-page .product-packaging-choice { padding:10px 0 12px !important; border-bottom:1px solid #e5eaf1; }
#add-product-page .product-packaging-choice .measurement-choice { margin-bottom:0; }
#add-product-page .other-measurement-choice { display:flex; flex-direction:column; justify-content:center; gap:5px; min-width:0; padding:10px 12px; border:1px solid #d8e0eb; border-radius:6px; background:#fff; }
#add-product-page .other-measurement-choice label { margin:0; color:#25354c; font-size:13px; font-weight:700; }
#add-product-page .other-measurement-choice small { color:#64748b; font-size:11px; font-weight:500; }
#add-product-page .other-measurement-choice .form-select { min-height:34px; padding-top:4px; padding-bottom:4px; }
#add-product-page .other-measurement-choice[data-active="true"] { background:#eff6ff; border-color:#2563eb; box-shadow:inset 0 0 0 1px #2563eb; }
@media (max-width:900px) {
    #add-product-page .measurement-mode-grid { grid-template-columns:repeat(2,minmax(0,1fr)); }
}
@media (max-width:767px) {
    #add-product-page .product-details-fields { grid-template-columns:repeat(2,minmax(0,1fr)); }
    #add-product-page .product-details-fields > .product-name-field { grid-column:1 / -1; }
    #add-product-page .product-details-fields > .product-image-field { grid-column:auto; }
}
@media (max-width:575px) {
    body:not(.login-body):not(.landing-page-body) #add-product-page > .content.container-fluid { padding:10px !important; }
    #add-product-page .product-form-sheet { padding:14px 12px; }
    #add-product-page .product-details-fields { grid-template-columns:minmax(0,1fr); }
    #add-product-page .product-details-fields > .product-name-field { grid-column:auto; }
    #add-product-page .product-required-note { display:none; }
    #add-product-page .quick-summary-pill { flex-basis:100%; border-left:0; padding:5px 0; }
}
@media print { #add-product-page .no-print { display:none !important; } }
</style>

<div class="page-wrapper add-product-page" id="add-product-page">
    <div class="content container-fluid">

        {{-- Page header --}}
        <div class="add-product-heading no-print">
            <div>
                <h4 class="mb-0 fw-bold text-dark"><i class="feather-package me-2 text-primary"></i>Add New Product</h4>
                <span class="product-required-note"><span class="text-danger">*</span> Required fields</span>
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
            <div class="card-body product-form-body">
                <form method="POST" action="{{ route('inventory.Products.store') }}" enctype="multipart/form-data" id="add_product_form" novalidate>
                    @csrf
                    <div class="row g-3">

                        <div class="col-12">
                            <div class="product-form-grid">
                        {{-- Section 1: Product Details --}}
                        <div>
                            <div class="product-form-sheet">
                                <div class="product-form-card-head">
                                    <div>
                                        <h6>Product details</h6>
                                        <p class="product-form-muted mb-0">Start with the fields marked required.</p>
                                    </div>
                                </div>
                                <div class="row g-3 product-details-fields">
                                    <div class="col-md-6 product-name-field">
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
                                    <div class="col-12 product-packaging-choice">
                                        <input type="hidden" name="measurement_mode" id="measurement_mode_input" value="{{ old('measurement_mode', 'pieces') }}">
                                        <div class="measurement-choice">
                                            <label class="form-label d-block">Packaging / measurement</label>
                                            <div class="measurement-mode-grid" role="group" aria-label="Measurement setup">
                                                <button type="button" class="measurement-mode-button" data-measurement-mode="pieces" aria-pressed="{{ old('measurement_mode', 'pieces') === 'pieces' ? 'true' : 'false' }}">
                                                    <strong>Pieces only (pcs)</strong><small class="d-block">Count each item individually.</small>
                                                </button>
                                                <button type="button" class="measurement-mode-button" data-measurement-mode="pieces_carton" aria-pressed="{{ old('measurement_mode', 'pieces') === 'pieces_carton' ? 'true' : 'false' }}">
                                                    <strong>Cartons containing pieces (pcs)</strong><small class="d-block">Set the pieces in each carton.</small>
                                                </button>
                                                <button type="button" class="measurement-mode-button" data-measurement-mode="kg_carton" aria-pressed="{{ old('measurement_mode', 'pieces') === 'kg_carton' ? 'true' : 'false' }}">
                                                    <strong>Cartons measured in kilograms (kg)</strong><small class="d-block">Only for stock sold by kg.</small>
                                                </button>
                                                <div class="other-measurement-choice" id="other_measurement_choice" data-active="{{ old('measurement_mode', 'pieces') === 'custom' ? 'true' : 'false' }}">
                                                    <label for="other_measurement_unit">Other measurements</label>
                                                    <select id="other_measurement_unit" aria-label="Choose another measurement" class="form-select">
                                                        <option value="" disabled @selected(old('measurement_mode', 'pieces') !== 'custom')>Choose a measurement…</option>
                                                        @foreach(($units ?? collect()) as $unit)
                                                            <option value="{{ $unit->id }}" data-symbol="{{ $unit->symbol }}" @selected(old('measurement_mode') === 'custom' && (string) old('base_unit_id', old('unit_id')) === (string) $unit->id)>{{ $unit->name }} ({{ $unit->symbol }})</option>
                                                        @endforeach
                                                    </select>
                                                    <small>All active units, including your own.</small>
                                                </div>
                                            </div>
                                            @error('measurement_mode')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
                                            <div class="alert alert-info small mt-3 mb-0" id="measurement_selection_summary" role="status" aria-live="polite">Stock unit: pieces (pcs). Enter prices per piece.</div>
                                        </div>
                                    </div>
                                    <div class="col-md-6 measurement-technical-field">
                                        <label class="form-label">Stock Unit Label</label>
                                        <input type="text" name="base_unit_name" readonly class="form-control @error('base_unit_name') is-invalid @enderror" value="{{ old('base_unit_name', 'pcs') }}" list="baseUnitSuggestions" required>
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
                                    <div class="col-md-6 measurement-technical-field">
                                        <label class="form-label">Stock Counting Unit <span class="text-danger">*</span></label>
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
                                    <div class="col-md-6 measurement-technical-field">
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
                                    <div class="col-md-6 measurement-technical-field">
                                        <label class="form-label">Purchase Unit</label>
                                        <select name="purchase_unit_id" class="form-select @error('purchase_unit_id') is-invalid @enderror">
                                            <option value="">No bulk purchase unit</option>
                                            @foreach(($units ?? collect()) as $unit)
                                                <option value="{{ $unit->id }}" data-symbol="{{ $unit->symbol }}" @selected((string) old('purchase_unit_id') === (string) $unit->id)>
                                                    {{ $unit->name }} ({{ $unit->symbol }})
                                                </option>
                                            @endforeach
                                        </select>
                                        <small class="field-hint">Optional bulk buying unit.</small>
                                        @error('purchase_unit_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                    </div>
                                    <div class="col-md-6 measurement-technical-field">
                                        <label class="form-label">Conversion Rate</label>
                                        <input type="number" step="0.000001" min="0" name="conversion_rate" class="form-control @error('conversion_rate') is-invalid @enderror" value="{{ old('conversion_rate') }}" placeholder="e.g. 12">
                                        <small class="field-hint">Only needed when purchase unit differs.</small>
                                        @error('conversion_rate')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label" id="quick_selling_price_label">Selling Price Per Piece (pcs) <span class="text-danger">*</span></label>
                                        <input type="number" step="0.01" name="price" class="form-control @error('price') is-invalid @enderror" placeholder="0.00" value="{{ old('price') }}" required>
                                        @error('price')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                    </div>
                                    @if(($priceLists ?? collect())->isNotEmpty())
                                        <div class="col-12">
                                            <details class="product-price-list-panel" @if(old('price_list_prices')) open @endif>
                                                <summary>
                                                    <div class="summary-title">
                                                        <div>
                                                            Price List Prices
                                                            <span class="d-block">Optional special prices for this product</span>
                                                        </div>
                                                        <i class="fas fa-chevron-down price-list-chevron"></i>
                                                    </div>
                                                </summary>
                                                <div class="d-flex justify-content-end mt-2 mb-3">
                                                    <a href="{{ route('price-lists.index') }}" class="btn btn-sm btn-outline-primary">Manage Price Lists</a>
                                                </div>
                                                <div class="row g-3 mt-0">
                                                    @foreach($priceLists as $priceList)
                                                        <div class="col-md-6">
                                                            <label class="form-label">{{ $priceList->name }}</label>
                                                            <input type="number"
                                                                step="0.01"
                                                                min="0"
                                                                name="price_list_prices[{{ $priceList->id }}]"
                                                                class="form-control @error('price_list_prices.' . $priceList->id) is-invalid @enderror"
                                                                value="{{ old('price_list_prices.' . $priceList->id) }}"
                                                                placeholder="Leave empty to use default price">
                                                            <small class="text-muted">{{ $priceList->currency ?? 'NGN' }}{{ $priceList->is_default ? ' - Default list' : '' }}</small>
                                                            @error('price_list_prices.' . $priceList->id)<div class="invalid-feedback">{{ $message }}</div>@enderror
                                                        </div>
                                                    @endforeach
                                                </div>
                                            </details>
                                        </div>
                                    @endif
                                    <div class="col-md-6">
                                        <label class="form-label" id="quick_purchase_price_label">Purchase Cost Per Piece (pcs) <span class="text-danger">*</span></label>
                                        <input type="number" step="0.01" name="purchase_price" class="form-control @error('purchase_price') is-invalid @enderror" placeholder="0.00" value="{{ old('purchase_price') }}" required>
                                        <small class="field-hint" id="purchase_unit_cost_hint">Cost for one piece, not a full carton.</small>
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
                                    <div class="col-md-6 product-image-field">
                                        <label class="form-label">Product Image</label>
                                        <input type="file" name="image" id="product_image_input" class="form-control @error('image') is-invalid @enderror">
                                        @error('image')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">Expiry Date <span class="text-muted small">(optional)</span></label>
                                        <input type="date" name="expiry_date" class="form-control @error('expiry_date') is-invalid @enderror" value="{{ old('expiry_date') }}">
                                        <small class="field-hint">For perishable items.</small>
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
                                        <h6>Opening stock & conversions</h6>
                                        <p class="product-form-muted mb-0">Enter packaging only where it applies.</p>
                                    </div>
                                </div>
                                <div class="product-packaging-fields">
                                <div id="kg_carton_fields" class="row g-3 mb-4" style="display: none;">
                                    <div class="col-md-6">
                                        <label class="form-label">Kg Per Carton <span class="text-danger">*</span></label>
                                        <input type="number" min="0.000001" step="0.000001" name="kg_per_carton" id="kg_per_carton_input" class="form-control @error('kg_per_carton') is-invalid @enderror" value="{{ old('kg_per_carton', old('units_per_carton', '')) }}" placeholder="e.g. 20">
                                        <small class="field-hint">The weight contained in one full carton.</small>
                                        @error('kg_per_carton')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                    </div>
                                </div>
                                <div id="custom_packaging_intro">
                                    <h6 class="mb-1">Packaging Setup</h6>
                                    <p class="product-form-muted">Use carton/roll fields only when the product is packed that way.</p>
                                </div>
                                <div class="row g-3 packaging-custom-row">
                                    <div class="col-md-4 custom-packaging-field">
                                        <label class="form-label" id="quick_rolls_per_carton_label">Rolls Per Ctn</label>
                                        <input type="number" id="quick_rolls_per_carton_helper" min="0" step="0.01" class="form-control" value="{{ $oldRollsPerCarton }}">
                                        <small class="field-hint">Leave 0 if not used.</small>
                                    </div>
                                    <div class="col-md-4 custom-packaging-field">
                                        <label class="form-label" id="quick_pcs_per_roll_label">Base Units Per Roll</label>
                                        <input type="number" id="quick_pcs_per_roll_helper" min="0" step="0.01" class="form-control" value="{{ $oldUnitsPerRoll }}">
                                        <small class="field-hint" id="quick_pcs_per_roll_help">Units inside one roll.</small>
                                    </div>
                                    <div class="col-12 custom-packaging-field pieces-carton-field">
                                        <label class="form-label" id="quick_pcs_per_carton_label">Base Units Per Carton</label>
                                        <input type="number" id="quick_pcs_per_carton_helper" min="0" step="0.01" class="form-control" value="{{ $oldPiecesPerCarton }}">
                                        <small class="field-hint" id="quick_pcs_per_carton_help">Total units in one carton.</small>
                                        @error('units_per_carton')<div class="text-danger small">{{ $message }}</div>@enderror
                                    </div>
                                    <input type="hidden" name="units_per_roll" id="quick_units_per_roll_input" value="{{ old('units_per_roll', 0) }}">
                                    <input type="hidden" name="units_per_carton" id="quick_units_per_carton_input" value="{{ old('units_per_carton', 0) }}">
                                    <input type="hidden" name="unit_type" id="quick_unit_type_input" value="{{ old('unit_type', 'unit') }}">
                                </div>

                                <div class="row g-3 mb-4 carton-mode-field" style="display: none;">
                                    <div class="col-12">
                                        <label class="form-label">Selling Price Per Full Carton <span class="text-muted">(optional)</span></label>
                                        <input type="number" min="0" step="0.01" name="carton_selling_price" id="carton_selling_price_input" class="form-control @error('carton_selling_price') is-invalid @enderror" value="{{ old('carton_selling_price') }}" placeholder="Calculated from price per base unit">
                                        <small class="field-hint" id="carton_price_hint">Leave empty to calculate it automatically.</small>
                                        @error('carton_selling_price')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                    </div>
                                </div>
                                </div>

                        {{-- Section 3: Opening Stock --}}
                                <div class="measurement-divider"></div>
                                <h6>Opening Stock</h6>
                                <p class="product-form-muted">Leave at 0 if stock will be added later.</p>
                                <div class="row g-3 opening-stock-fields">
                                    <div class="col-md-4 opening-carton-field">
                                        <label class="form-label">Opening Full Cartons</label>
                                        <input type="number" step="0.01" name="stock_cartons" class="form-control @error('stock_cartons') is-invalid @enderror" value="{{ old('stock_cartons', 0) }}">
                                        @error('stock_cartons')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                    </div>
                                    <div class="col-md-4 opening-roll-field">
                                        <label class="form-label">Opening Full Rolls</label>
                                        <input type="number" step="0.01" name="stock_rolls" class="form-control @error('stock_rolls') is-invalid @enderror" value="{{ old('stock_rolls', 0) }}">
                                        @error('stock_rolls')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label" id="quick_opening_unit_label">Opening Pcs</label>
                                        <input type="number" step="0.01" name="stock_units" class="form-control @error('stock_units') is-invalid @enderror" value="{{ old('stock_units', 0) }}">
                                        @error('stock_units')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label" id="total_opening_stock_label">Total Opening Stock (pcs)</label>
                                        <input type="text" id="quick_total_opening_stock_display" class="form-control bg-light fw-semibold" value="0 pcs" readonly>
                                    </div>
                                    <div class="col-12">
                                        <div class="quick-summary-pills">
                                            <div class="quick-summary-pill carton-summary-field">
                                                <span id="quick_units_per_carton_summary_label">Base Units Per Ctn</span>
                                                <strong id="quick_units_per_carton_preview_text">0 pcs</strong>
                                            </div>
                                            <div class="quick-summary-pill">
                                                <span>Entered Mix</span>
                                                <strong id="quick_stock_mix_preview_text">0 ctn + 0 roll + 0 pcs</strong>
                                            </div>
                                            <div class="quick-summary-pill">
                                                <span>Opening Value Preview</span>
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
                                <p class="product-form-muted">Use only when needed.</p>
                                <div class="row g-3">
                                    <div class="col-md-6">
                                        <label class="form-label">SKU</label>
                                        <input type="text" name="sku" class="form-control @error('sku') is-invalid @enderror" placeholder="Leave blank to auto-generate" value="{{ old('sku') }}">
                                        <small class="field-hint">Leave blank to auto-generate.</small>
                                        @error('sku')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">Barcode</label>
                                        <input type="text" name="barcode" class="form-control @error('barcode') is-invalid @enderror" placeholder="Scan or type barcode" value="{{ old('barcode') }}">
                                        @error('barcode')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label">Wholesale Price Per <span class="product-base-unit-label">pcs</span></label>
                                        <input type="number" step="0.01" name="wholesale_price" class="form-control @error('wholesale_price') is-invalid @enderror" placeholder="Optional" value="{{ old('wholesale_price') }}">
                                        @error('wholesale_price')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label">Special Price Per <span class="product-base-unit-label">pcs</span></label>
                                        <input type="number" step="0.01" name="special_price" class="form-control @error('special_price') is-invalid @enderror" placeholder="Optional" value="{{ old('special_price') }}">
                                        @error('special_price')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label">Reorder Level (<span class="product-base-unit-label">pcs</span>)</label>
                                        <input type="number" name="reorder_level" min="0" class="form-control @error('reorder_level') is-invalid @enderror" value="{{ old('reorder_level', 0) }}">
                                        @error('reorder_level')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label">Reorder Quantity (<span class="product-base-unit-label">pcs</span>)</label>
                                        <input type="number" name="reorder_quantity" min="0" class="form-control @error('reorder_quantity') is-invalid @enderror" value="{{ old('reorder_quantity', 0) }}">
                                        @error('reorder_quantity')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                    </div>
                                </div>
                            </div>
                        </div>

                        {{-- Save button --}}
                        <div class="col-12 d-flex justify-content-between align-items-center pt-2 product-form-actions">
                            <p class="text-muted small mb-0">Save once and the product, pricing, and opening stock will be ready together.</p>
                            <button type="submit" class="btn btn-primary px-5" id="page_add_product_submit"><i class="fas fa-check me-2" aria-hidden="true"></i>Save Product</button>
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

    function refreshKgCartonPriceHint() {
        var kgPerCarton = $('#measurement_mode_input').val() === 'kg_carton'
            ? packagingValue('#kg_per_carton_input') : packagingValue('#quick_pcs_per_carton_helper');
        var pricePerKg = parseFloat($('input[name="price"]').val()) || 0;
        var calculated = kgPerCarton * pricePerKg;
        $('#carton_price_hint').text(calculated > 0
            ? 'Automatic carton price: ' + calculated.toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 })
            : 'Leave empty to calculate it automatically.');
    }

    function setMeasurementMode(mode) {
        mode = ['pieces', 'pieces_carton', 'kg_carton', 'custom'].includes(mode) ? mode : 'pieces';
        var previous = $('#measurement_mode_input').val();
        var changed = previous !== mode;
        var isKgCarton = mode === 'kg_carton';
        var isPiecesCarton = mode === 'pieces_carton';
        var isCustom = mode === 'custom';
        $('#measurement_mode_input').val(mode);
        $('#kg_carton_fields').toggle(isKgCarton);
        $('.measurement-technical-field, .custom-packaging-field, #custom_packaging_intro, .opening-roll-field').toggle(isCustom);
        $('.pieces-carton-field').toggle(isCustom || isPiecesCarton);
        $('.packaging-custom-row').toggle(isCustom || isPiecesCarton);
        $('.carton-mode-field').toggle(isKgCarton || isPiecesCarton);
        $('.opening-carton-field, .carton-summary-field').toggle(mode !== 'pieces');
        $('#kg_per_carton_input').prop('disabled', !isKgCarton).prop('required', isKgCarton);
        $('#carton_selling_price_input').prop('disabled', isCustom || mode === 'pieces');
        $('#other_measurement_choice').attr('data-active', isCustom ? 'true' : 'false');
        if (!isCustom) $('#other_measurement_unit').val('');
        $('.measurement-mode-button').each(function () {
            var active = $(this).data('measurement-mode') === mode;
            $(this).attr('aria-pressed', active ? 'true' : 'false');
        });

        if (!isCustom) {
            applyUnitSymbol(isKgCarton ? 'kg' : 'pcs');
            $('select[name="purchase_unit_id"]').val('');
            $('input[name="conversion_rate"]').val('');
            $('#quick_rolls_per_carton_helper, #quick_pcs_per_roll_helper').val(0);
            $('input[name="stock_rolls"]').val(0);
            if (changed) {
                $('#quick_pcs_per_carton_helper, #kg_per_carton_input').val('');
                $('#carton_selling_price_input').val('');
            }
            if (isKgCarton) $('#quick_pcs_per_carton_helper').val($('#kg_per_carton_input').val() || 0);
            if (mode === 'pieces') {
                $('#quick_pcs_per_carton_helper').val(0);
                $('input[name="stock_cartons"]').val(0);
            }
        } else if (changed) {
            // Starting another setup must not carry a hidden kg/carton conversion.
            $('#quick_pcs_per_carton_helper').val(0);
            applyUnitSymbol('pcs');
        }
        $('#measurement_selection_summary').text(isKgCarton
            ? 'Stock unit: kilograms (kg). Enter kg per carton and prices per kg.'
            : (mode === 'pieces' || isPiecesCarton)
                ? 'Stock unit: pieces (pcs). Enter prices per piece. A weight in the product name does not change the unit.'
                : 'Choose a base unit above. Prices and stock totals use that unit; add carton or roll conversions only if needed.');
        refreshQuickPackagingLabels();
        calculateQuickCartonContent();
        calculateQuickStock();
        refreshKgCartonPriceHint();
    }

    $('.measurement-mode-button').on('click', function () {
        setMeasurementMode($(this).data('measurement-mode'));
    });

    function selectOtherMeasurement() {
        var unitId = $('#other_measurement_unit').val();
        var symbol = selectedUnitKey('#other_measurement_unit');
        if (!unitId || !symbol) return;
        setMeasurementMode('custom');
        applyUnitSymbol(symbol);
        $('select[name="unit_id"], select[name="base_unit_id"]').val(unitId);
        $('#other_measurement_unit').val(unitId);
    }
    $('#other_measurement_unit').on('change', selectOtherMeasurement);

    $('#kg_per_carton_input').on('input change', function () {
        if ($('#measurement_mode_input').val() === 'kg_carton') {
            $('#quick_pcs_per_carton_helper').val($(this).val() || 0);
            calculateQuickCartonContent();
            calculateQuickStock();
        }
        refreshKgCartonPriceHint();
    });

    $('input[name="price"]').on('input', refreshKgCartonPriceHint);

    function syncPackagingHiddenFields() {
        var rollsPerCtn = packagingValue('#quick_rolls_per_carton_helper');
        var pcsPerRoll  = packagingValue('#quick_pcs_per_roll_helper');
        var pcsPerCtn   = packagingValue('#quick_pcs_per_carton_helper');
        var unitLabel   = quickBaseUnitLabel();
        $('#quick_units_per_roll_input').val(pcsPerRoll > 0 ? pcsPerRoll : 0);
        $('#quick_units_per_carton_input').val(pcsPerCtn > 0 ? pcsPerCtn : 0);
        $('#quick_units_per_carton_preview_text').text(formatQuickQty(pcsPerCtn > 0 ? pcsPerCtn : 0) + ' ' + unitLabel);
    }

    function syncQuickUnitType() {
        var pcsPerRoll  = packagingValue('#quick_pcs_per_roll_helper');
        var unitType = 'unit';
        if (packagingValue('#quick_pcs_per_carton_helper') > 0) unitType = 'carton';
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

        if (rollsPerCtn <= 0 && pcsPerRoll <= 0) {
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
        $('#quick_total_opening_stock_display').val(formatQuickQty(total) + ' ' + unitLabel);
        var stockMix = $('#measurement_mode_input').val() === 'pieces' ? formatQuickQty(pieces) + ' pcs'
            : $('#measurement_mode_input').val() === 'pieces_carton' ? formatQuickQty(cartons) + ' cartons + ' + formatQuickQty(pieces) + ' loose pcs'
            : $('#measurement_mode_input').val() === 'kg_carton'
            ? formatQuickQty(cartons) + ' ctn + ' + formatQuickQty(pieces) + ' kg'
            : formatQuickQty(cartons) + ' ctn + ' + formatQuickQty(rolls) + ' roll + ' + formatQuickQty(pieces) + ' ' + unitLabel;
        $('#quick_stock_mix_preview_text').text(stockMix);
        $('#quick_stock_value_preview').text(stockValue.toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 }));
        $('#quick_final_stock_input').val(Number(total.toFixed(6)));
    }

    function refreshQuickPackagingLabels() {
        var baseUnitName = ($('input[name="base_unit_name"]').val() || 'pcs').trim();
        var unitLabel = baseUnitName.length ? baseUnitName : 'pcs';
        var titleUnit = unitLabel === 'pcs' ? 'Pieces (pcs)' : unitLabel.charAt(0).toUpperCase() + unitLabel.slice(1);
        $('#quick_pcs_per_roll_label').text(titleUnit + ' Per Roll');
        $('#quick_pcs_per_roll_help').text('How many ' + unitLabel + ' are inside one roll.');
        $('#quick_pcs_per_carton_label').text(titleUnit + ' Per Full Carton');
        $('#quick_pcs_per_carton_help').text('Enter the total ' + unitLabel + ' in one carton. Use this directly when selling ' + unitLabel + ' by carton.');
        $('#quick_units_per_carton_summary_label').text(titleUnit + ' Per Carton');
        $('#quick_opening_unit_label').text('Opening Loose ' + (unitLabel === 'pcs' ? 'Pieces (pcs)' : unitLabel));
        $('#total_opening_stock_label').text('Total Opening Stock (' + unitLabel + ')');
        $('.product-base-unit-label').text(unitLabel);
        $('#purchase_unit_cost_hint').text($('#measurement_mode_input').val() === 'custom'
            ? 'Cost for one selected purchase unit.'
            : 'Cost for one ' + (unitLabel === 'pcs' ? 'piece' : unitLabel) + ', not a full carton.');
        $('#quick_selling_price_label').text('Selling Price Per ' + (unitLabel === 'pcs' ? 'Piece (pcs)' : unitLabel) + ' ').append('<span class="text-danger">*</span>');
        $('#quick_purchase_price_label').text('Purchase Cost Per ' + (unitLabel === 'pcs' ? 'Piece (pcs)' : unitLabel) + ' ').append('<span class="text-danger">*</span>');
    }

    // Event bindings
    $('#quick_rolls_per_carton_helper, #quick_pcs_per_roll_helper, #quick_pcs_per_carton_helper').on('input change keyup', function () {
        lastPackagingFieldEdited = $(this).attr('id') === 'quick_rolls_per_carton_helper'
            ? 'rolls'
            : ($(this).attr('id') === 'quick_pcs_per_roll_helper' ? 'pcs_per_roll' : 'pcs_per_ctn');
        calculateQuickCartonContent();
        calculateQuickStock();
        refreshKgCartonPriceHint();
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
        unitSyncing = false;
        if ($('#measurement_mode_input').val() === 'custom') {
            $('#other_measurement_unit').val($('select[name="base_unit_id"]').val() || $('select[name="unit_id"]').val());
        }
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
        var purchaseUnit = selectedUnitKey('select[name="purchase_unit_id"]');
        var baseUnit = selectedUnitKey('select[name="base_unit_id"]') || selectedUnitKey('select[name="unit_id"]') || quickBaseUnitLabel();
        var conversionField = $('input[name="conversion_rate"]');

        if (purchaseUnit && normalizeUnitKey(purchaseUnit) !== normalizeUnitKey(baseUnit) && !conversionField.val()) {
            conversionField.focus();
        }
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
    setMeasurementMode($('#measurement_mode_input').val());
    calculateQuickCartonContent();
    calculateQuickStock();
    reloadCategoryOptions($('#product_category_select').val() || '').finally(function () {
        initializeQuickCategorySelect();
    }).catch(function () {});
});
</script>
<script>
(function () {
    function numberValue(id) {
        var input = document.getElementById(id);
        return input ? (parseFloat(input.value) || 0) : 0;
    }

    function setNumberValue(id, value) {
        var input = document.getElementById(id);
        if (!input) return;
        input.value = value > 0 ? Number(value.toFixed(2)).toString() : '0';
    }

    function textValue(selector, fallback) {
        var input = document.querySelector(selector);
        var value = input ? String(input.value || '').trim() : '';
        return value || fallback;
    }

    function formatQty(value) {
        return (parseFloat(value) || 0).toLocaleString(undefined, { maximumFractionDigits: 2 });
    }

    function refreshPackagingFallback() {
        var rollsPerCtn = numberValue('quick_rolls_per_carton_helper');
        var pcsPerRoll = numberValue('quick_pcs_per_roll_helper');
        var cartonInput = document.getElementById('quick_pcs_per_carton_helper');
        var directCartonUnits = cartonInput ? (parseFloat(cartonInput.value) || 0) : 0;
        var pcsPerCtn = rollsPerCtn > 0 && pcsPerRoll > 0 ? rollsPerCtn * pcsPerRoll : directCartonUnits;
        var unitLabel = textValue('input[name="base_unit_name"]', 'pcs');
        var cartons = parseFloat((document.querySelector('input[name="stock_cartons"]') || {}).value) || 0;
        var rolls = parseFloat((document.querySelector('input[name="stock_rolls"]') || {}).value) || 0;
        var pieces = parseFloat((document.querySelector('input[name="stock_units"]') || {}).value) || 0;
        var purchasePrice = parseFloat((document.querySelector('input[name="purchase_price"]') || {}).value) || 0;
        var total = (cartons * pcsPerCtn) + (rolls * pcsPerRoll) + pieces;

        if (rollsPerCtn > 0 && pcsPerRoll > 0) {
            setNumberValue('quick_pcs_per_carton_helper', pcsPerCtn);
        }

        var unitsPerRollInput = document.getElementById('quick_units_per_roll_input');
        var unitsPerCartonInput = document.getElementById('quick_units_per_carton_input');
        var unitTypeInput = document.getElementById('quick_unit_type_input');
        var pcsPerCtnPreview = document.getElementById('quick_units_per_carton_preview_text');
        var stockPreview = document.getElementById('quick_stock_preview_text');
        var stockDisplay = document.getElementById('quick_total_opening_stock_display');
        var mixPreview = document.getElementById('quick_stock_mix_preview_text');
        var valuePreview = document.getElementById('quick_stock_value_preview');
        var finalStockInput = document.getElementById('quick_final_stock_input');

        if (unitsPerRollInput) unitsPerRollInput.value = pcsPerRoll > 0 ? pcsPerRoll : 0;
        if (unitsPerCartonInput) unitsPerCartonInput.value = pcsPerCtn > 0 ? pcsPerCtn : 0;
        if (unitTypeInput) unitTypeInput.value = pcsPerCtn > 0 ? 'carton' : (pcsPerRoll > 0 ? 'roll' : 'unit');
        if (pcsPerCtnPreview) pcsPerCtnPreview.textContent = formatQty(pcsPerCtn) + ' ' + unitLabel;
        if (stockPreview) stockPreview.textContent = formatQty(total) + ' ' + unitLabel;
        if (stockDisplay) stockDisplay.value = formatQty(total) + ' ' + unitLabel;
        if (mixPreview) {
            mixPreview.textContent = textValue('#measurement_mode_input', 'custom') === 'pieces' ? formatQty(pieces) + ' pcs'
                : textValue('#measurement_mode_input', 'custom') === 'pieces_carton' ? formatQty(cartons) + ' cartons + ' + formatQty(pieces) + ' loose pcs'
                : textValue('#measurement_mode_input', 'custom') === 'kg_carton'
                ? formatQty(cartons) + ' ctn + ' + formatQty(pieces) + ' kg'
                : formatQty(cartons) + ' ctn + ' + formatQty(rolls) + ' roll + ' + formatQty(pieces) + ' ' + unitLabel;
        }
        if (valuePreview) valuePreview.textContent = (total * purchasePrice).toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 });
        if (finalStockInput) finalStockInput.value = Number(total.toFixed(6));
    }

    function bindPackagingFallback() {
        [
            'quick_rolls_per_carton_helper',
            'quick_pcs_per_roll_helper',
            'quick_pcs_per_carton_helper'
        ].forEach(function (id) {
            var input = document.getElementById(id);
            if (!input || input.dataset.packagingFallbackBound === 'true') return;
            input.dataset.packagingFallbackBound = 'true';
            input.addEventListener('input', refreshPackagingFallback);
            input.addEventListener('change', refreshPackagingFallback);
            input.addEventListener('keyup', refreshPackagingFallback);
        });

        [
            'input[name="stock_cartons"]',
            'input[name="stock_rolls"]',
            'input[name="stock_units"]',
            'input[name="purchase_price"]',
            'input[name="base_unit_name"]'
        ].forEach(function (selector) {
            var input = document.querySelector(selector);
            if (!input || input.dataset.packagingFallbackBound === 'true') return;
            input.dataset.packagingFallbackBound = 'true';
            input.addEventListener('input', refreshPackagingFallback);
            input.addEventListener('change', refreshPackagingFallback);
            input.addEventListener('keyup', refreshPackagingFallback);
        });

        refreshPackagingFallback();
    }

    bindPackagingFallback();
    document.addEventListener('DOMContentLoaded', bindPackagingFallback);
    document.addEventListener('smartprobook:page-loaded', bindPackagingFallback);
})();
</script>
@endpush
