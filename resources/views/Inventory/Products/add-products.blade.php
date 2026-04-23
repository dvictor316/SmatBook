@extends('layout.mainlayout')
@section('content')

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
        border-radius: 14px;
        background: #fff;
        padding: 1.1rem 1.2rem 0.6rem;
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
        background: linear-gradient(135deg, #f4f7ff 0%, #f8fbff 100%);
        border-bottom: 1px solid #dbe7ff;
        border-radius: 16px 16px 0 0 !important;
        padding: 1.1rem 1.4rem;
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

                        {{-- Section 1: Product Details --}}
                        <div class="col-12">
                            <div class="product-form-sheet">
                                <h6>Product Details</h6>
                                <p class="product-form-muted">Enter the basic information first. This is all most products need.</p>
                                <div class="row g-3">
                                    <div class="col-md-6">
                                        <label class="form-label">Product Name <span class="text-danger">*</span></label>
                                        <input type="text" name="name" class="form-control @error('name') is-invalid @enderror" placeholder="e.g. Big Bull Rice 50kg" value="{{ old('name') }}" required>
                                        @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                    </div>
                                    <div class="col-md-4">
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
                                    <div class="col-md-2">
                                        <label class="form-label">Base Unit</label>
                                        <input type="text" name="base_unit_name" class="form-control @error('base_unit_name') is-invalid @enderror" value="{{ old('base_unit_name', 'pcs') }}" required>
                                        @error('base_unit_name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label">Retail / Default Price <span class="text-danger">*</span></label>
                                        <input type="number" step="0.01" name="price" class="form-control @error('price') is-invalid @enderror" placeholder="0.00" value="{{ old('price') }}" required>
                                        @error('price')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label">Purchase Price <span class="text-danger">*</span></label>
                                        <input type="number" step="0.01" name="purchase_price" class="form-control @error('purchase_price') is-invalid @enderror" placeholder="0.00" value="{{ old('purchase_price') }}" required>
                                        @error('purchase_price')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label">Stock Branch</label>
                                        <select name="branch_id" class="form-select @error('branch_id') is-invalid @enderror">
                                            <option value="">Use Active Branch</option>
                                            @foreach(($availableBranches ?? []) as $branch)
                                                <option value="{{ $branch['id'] }}" @selected((string) old('branch_id') === (string) ($branch['id'] ?? ''))>{{ $branch['name'] }}</option>
                                            @endforeach
                                        </select>
                                        @error('branch_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label">Product Image</label>
                                        <input type="file" name="image" id="product_image_input" class="form-control @error('image') is-invalid @enderror">
                                        <small class="text-muted">Optional.</small>
                                        @error('image')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                    </div>
                                </div>
                            </div>
                        </div>

                        {{-- Section 2: Packaging Setup --}}
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
                                    <input type="hidden" name="unit_type" id="quick_unit_type_input" value="unit">
                                </div>
                            </div>
                        </div>

                        {{-- Section 3: Opening Stock --}}
                        <div class="col-12">
                            <div class="product-form-sheet">
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
                                                <span>Estimated Opening Value</span>
                                                <strong id="quick_stock_value_preview">0.00</strong>
                                            </div>
                                        </div>
                                        <input type="hidden" name="stock" id="quick_final_stock_input" value="{{ old('stock', '') }}">
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

                        <div class="col-12 collapse @if($errors->hasAny(['sku','barcode','wholesale_price','special_price','reorder_level'])) show @endif" id="advancedProductFields">
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
                                </div>
                            </div>
                        </div>

                        {{-- Save button --}}
                        <div class="col-12 d-flex justify-content-between align-items-center pt-2">
                            <p class="text-muted small mb-0">Save once and the product, pricing, and opening stock will be ready together.</p>
                            <button type="submit" class="btn btn-primary px-5">Save Product</button>
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

@endsection

@push('scripts')
<script>
$(document).ready(function () {
    var categoryIndexUrl = @json(route('ajax.inventory.categories.index', [], false));
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

    function syncPackagingHiddenFields() {
        var rollsPerCtn = packagingValue('#quick_rolls_per_carton_helper');
        var pcsPerRoll  = packagingValue('#quick_pcs_per_roll_helper');
        var pcsPerCtn   = packagingValue('#quick_pcs_per_carton_helper');
        $('#quick_units_per_roll_input').val(pcsPerRoll > 0 ? pcsPerRoll : 0);
        $('#quick_units_per_carton_input').val(rollsPerCtn > 0 ? rollsPerCtn : pcsPerCtn);
        $('#quick_units_per_carton_preview_text').text((pcsPerCtn > 0 ? pcsPerCtn : 0).toLocaleString() + ' pcs');
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

        var filled = [rollsPerCtn, pcsPerRoll, pcsPerCtn].filter(function (v) { return v > 0; }).length;
        if (filled >= 2) {
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
        var cartons       = parseFloat($('input[name="stock_cartons"]').val()) || 0;
        var rolls         = parseFloat($('input[name="stock_rolls"]').val()) || 0;
        var pieces        = parseFloat($('input[name="stock_units"]').val()) || 0;
        var rollsPerCtn   = parseFloat($('#quick_units_per_carton_input').val()) || 0;
        var pcsPerRoll    = parseFloat($('#quick_units_per_roll_input').val()) || 0;
        var pcsPerCtn     = packagingValue('#quick_pcs_per_carton_helper');
        var purchasePrice = parseFloat($('input[name="purchase_price"]').val()) || 0;

        var fromCartons = pcsPerCtn > 0
            ? cartons * pcsPerCtn
            : (pcsPerRoll > 0 ? cartons * rollsPerCtn * pcsPerRoll : cartons * rollsPerCtn);
        var fromRolls = pcsPerRoll > 0 ? rolls * pcsPerRoll : rolls;
        var total = fromCartons + fromRolls + pieces;

        var stockValue = total * purchasePrice;
        $('#quick_stock_preview_text').text(total.toLocaleString() + ' pcs');
        $('#quick_stock_value_preview').text(stockValue.toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 }));
        $('#quick_final_stock_input').val(Math.round(total));
    }

    function refreshQuickPackagingLabels() {
        var baseUnitName = ($('input[name="base_unit_name"]').val() || 'pcs').trim();
        var unitLabel = baseUnitName.length ? baseUnitName : 'pcs';
        $('#quick_opening_unit_label').text('Opening ' + unitLabel.charAt(0).toUpperCase() + unitLabel.slice(1));
    }

    // Event bindings
    $('#quick_rolls_per_carton_helper, #quick_pcs_per_roll_helper, #quick_pcs_per_carton_helper').on('input', function () {
        lastPackagingFieldEdited = $(this).attr('id') === 'quick_rolls_per_carton_helper'
            ? 'rolls'
            : ($(this).attr('id') === 'quick_pcs_per_roll_helper' ? 'pcs_per_roll' : 'pcs_per_ctn');
        calculateQuickCartonContent();
        calculateQuickStock();
    });

    $('input[name="stock_cartons"], input[name="stock_rolls"], input[name="stock_units"], input[name="purchase_price"]').on('input', function () {
        calculateQuickStock();
    });

    $('input[name="base_unit_name"]').on('input', function () {
        refreshQuickPackagingLabels();
    });

    $('#add_product_form').on('submit', function () {
        var img = document.getElementById('product_image_input');
        if (img && (!img.files || img.files.length === 0)) img.disabled = true;
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
            body: JSON.stringify({ name: typedName })
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
    reloadCategoryOptions($('#product_category_select').val() || '').catch(function () {});
});
</script>
@endpush
