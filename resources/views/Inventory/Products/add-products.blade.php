@extends('layout.mainlayout')
@section('content')
<style>
    .product-form-shell {
        max-width: 1320px;
        margin: 0 auto;
    }
    .product-form-hero {
        background:
            radial-gradient(circle at top right, rgba(37, 99, 235, 0.16), transparent 30%),
            linear-gradient(135deg, #ffffff 0%, #f6fbff 52%, #eef4ff 100%);
        border: 1px solid #dbeafe;
        border-radius: 28px;
        padding: 28px 30px;
        box-shadow: 0 18px 45px rgba(59, 130, 246, 0.08);
        margin-bottom: 24px;
    }
    .product-form-kicker {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        padding: 8px 14px;
        border-radius: 999px;
        background: #eff6ff;
        color: #2563eb;
        font-size: 11px;
        font-weight: 800;
        text-transform: uppercase;
        letter-spacing: 0.12em;
        margin-bottom: 14px;
    }
    .product-form-title {
        font-size: clamp(1.7rem, 3vw, 2.4rem);
        line-height: 1.05;
        font-weight: 800;
        color: #0f172a;
        margin: 0 0 8px;
        letter-spacing: -0.04em;
    }
    .product-form-copy {
        max-width: 760px;
        color: #64748b;
        font-size: 1rem;
        margin: 0;
    }
    .product-form-card {
        border: 1px solid #e5eefc;
        border-radius: 28px;
        box-shadow: 0 20px 45px rgba(15, 23, 42, 0.06);
        overflow: hidden;
    }
    .product-form-card .card-body {
        padding: 28px;
        background: linear-gradient(180deg, #ffffff 0%, #fbfdff 100%);
    }
    .product-section {
        border: 1px solid #e2e8f0;
        border-radius: 22px;
        background: #ffffff;
        padding: 22px 22px 8px;
        margin-bottom: 18px;
        box-shadow: inset 0 1px 0 rgba(255,255,255,0.7);
    }
    .product-section-header {
        display: flex;
        align-items: flex-start;
        justify-content: space-between;
        gap: 16px;
        margin-bottom: 18px;
    }
    .product-section-title {
        margin: 0;
        color: #0f172a;
        font-size: 1.02rem;
        font-weight: 800;
        letter-spacing: -0.02em;
    }
    .product-section-copy {
        margin: 4px 0 0;
        color: #64748b;
        font-size: 0.9rem;
    }
    .field-label {
        font-size: 0.84rem;
        font-weight: 800;
        color: #0f172a;
        margin-bottom: 7px;
    }
    .field-note {
        color: #64748b;
        font-size: 0.78rem;
        margin-top: 6px;
    }
    .product-form-card .form-control,
    .product-form-card .form-select,
    .product-form-card .select2-container .select2-selection--single {
        min-height: 49px;
        border-radius: 14px !important;
        border-color: #dbe3f0;
        box-shadow: none;
    }
    .product-form-card textarea.form-control {
        min-height: 120px;
        border-radius: 18px !important;
    }
    .product-form-card .form-control:focus,
    .product-form-card .form-select:focus {
        border-color: #60a5fa;
        box-shadow: 0 0 0 4px rgba(96, 165, 250, 0.12);
    }
    .product-form-actions {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 14px;
        margin-top: 8px;
        padding-top: 16px;
        border-top: 1px solid #e5eefc;
    }
    .product-form-actions .helper {
        color: #64748b;
        font-size: 0.82rem;
        margin: 0;
    }
    @media (max-width: 991.98px) {
        .product-form-hero,
        .product-form-card .card-body,
        .product-section {
            padding: 20px 18px;
        }
        .product-form-actions {
            flex-direction: column;
            align-items: stretch;
        }
    }
</style>
    <div class="page-wrapper">
    <div class="content container-fluid product-form-shell">
        
        <div class="product-form-hero no-print">
            <div class="row align-items-center g-3">
                <div class="col-lg">
                    <span class="product-form-kicker"><i class="feather-package"></i> Inventory Entry</span>
                    <h1 class="product-form-title">Create a product with stock, pricing, and packaging in one clean flow.</h1>
                    <p class="product-form-copy">Everything stays in the same place, so users can enter the product once, understand the stock math instantly, and move on without confusion.</p>
                </div>
                <div class="col-lg-auto">
                    <div class="btn-group shadow-sm">
                        <button onclick="window.print()" class="btn btn-white border btn-sm">
                            <i class="feather-printer me-1"></i> Print
                        </button>
                        <button id="export_pdf" class="btn btn-white border text-danger btn-sm">
                            <i class="feather-file-text me-1"></i> PDF
                        </button>
                    </div>
                </div>
            </div>
        </div>

        @if (session('success'))
            <div class="alert alert-success alert-dismissible fade show shadow-sm border-0" role="alert">
                <i class="feather-check-circle me-2"></i>{{ session('success') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        @endif

        @if (session('error'))
            <div class="alert alert-danger alert-dismissible fade show shadow-sm border-0" role="alert">
                <i class="feather-alert-triangle me-2"></i>{{ session('error') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        @endif

        @if ($errors->any())
            <div class="alert alert-danger shadow-sm border-0" role="alert">
                <div class="fw-semibold mb-2">Please fix these issues before saving:</div>
                <ul class="mb-0 ps-3">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <div id="category_page_success_message" class="alert alert-success alert-dismissible fade shadow-sm border-0 d-none" role="alert">
            <span id="category_page_success_text"></span>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>

        {{-- Main Form --}}
        <div class="card product-form-card border-0 mb-4">
            <div class="card-body">
                <form action="{{ route('inventory.Products.store') }}" method="POST" enctype="multipart/form-data" novalidate id="add_product_form">
                    @csrf
                    <div class="product-section">
                        <div class="product-section-header">
                            <div>
                                <h5 class="product-section-title">Core Product Details</h5>
                                <p class="product-section-copy">Start with the product identity, category, and the branch that should own the opening stock.</p>
                            </div>
                        </div>
                    <div class="row">
                        <div class="col-md-4 mb-3">
                            <label class="field-label">Product Name</label>
                            <input type="text" name="name" id="p_name" class="form-control @error('name') is-invalid @enderror" placeholder="e.g. Indomie Onion" value="{{ old('name') }}" required>
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="field-label">SKU / Code</label>
                            <input type="text" name="sku" id="p_sku" class="form-control @error('sku') is-invalid @enderror" value="{{ old('sku') }}" placeholder="Leave blank to auto-generate">
                            <small class="field-note">A unique SKU will be generated if the product does not already have one.</small>
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="field-label">Barcode</label>
                            <input type="text" name="barcode" id="p_barcode" class="form-control @error('barcode') is-invalid @enderror" value="{{ old('barcode') }}" placeholder="Optional barcode">
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="field-label d-flex justify-content-between">
                                Category
                                <a href="javascript:void(0);" data-bs-toggle="modal" data-bs-target="#addCategoryModal" class="text-primary small">+ New</a>
                            </label>
                            <select name="category_id" id="product_category_select" class="form-control select2 @error('category_id') is-invalid @enderror" required>
                                <option value="">Select Category</option>
                                @foreach($categories as $category)
                                    <option value="{{ $category->id }}" @selected((string) old('category_id') === (string) $category->id)>{{ $category->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="field-label">Stock Branch</label>
                            <select name="branch_id" class="form-control">
                                <option value="">Use Active Branch</option>
                                @foreach(($availableBranches ?? []) as $branch)
                                    <option value="{{ $branch['id'] }}" @selected((string) old('branch_id') === (string) ($branch['id'] ?? ''))>{{ $branch['name'] }}</option>
                                @endforeach
                            </select>
                            <small class="field-note">Choose the branch that should receive the opening stock for this product. Basic plan stays on a single branch.</small>
                        </div>
                    </div>
                    </div>

                    <div class="product-section">
                        <div class="product-section-header">
                            <div>
                                <h5 class="product-section-title">Packaging & Conversion Rules</h5>
                                <p class="product-section-copy">Define how cartons, rolls, and base units relate so stock calculations stay accurate everywhere.</p>
                            </div>
                        </div>
                    <div class="row">
                        <div class="col-md-3 mb-3">
                            <label class="field-label text-info">Unit Total <span class="text-muted fw-normal d-block small" id="unit_total_hint">Total units inside one carton</span></label>
                            <input type="number" id="unit_total_per_carton" class="form-control bg-light-info" value="{{ old('unit_total_per_carton', 0) }}" min="0" step="0.01">
                            <small class="field-note" id="unit_total_help">Type the full number of sellable units inside one carton first.</small>
                        </div>
                        <div class="col-md-3 mb-3">
                            <label class="field-label text-warning">Roll Content <span class="text-muted fw-normal d-block small" id="roll_content_hint">Units per roll</span></label>
                            <input type="number" name="units_per_roll" id="upr" class="form-control bg-light-warning @error('units_per_roll') is-invalid @enderror" value="{{ old('units_per_roll', 0) }}" min="0" step="0.01">
                            <small class="field-note" id="roll_content_help">Enter how many sellable units are inside one roll. Leave this at 0 if the product does not use rolls.</small>
                        </div>
                        <div class="col-md-3 mb-3">
                            <label class="field-label text-danger">Carton Content <span class="text-muted fw-normal d-block small" id="carton_content_hint">Auto-calculated rolls per carton</span></label>
                            <input type="number" name="units_per_carton" id="upc" class="form-control bg-light-danger @error('units_per_carton') is-invalid @enderror" value="{{ old('units_per_carton', 0) }}" min="0" step="0.01">
                            <small class="field-note" id="carton_content_help">This is calculated from unit total and roll content. If rolls are not used, it matches the unit total.</small>
                        </div>
                        <div class="col-md-3 mb-3">
                            <label class="field-label">Base Unit Name</label>
                            <input type="text" name="base_unit_name" class="form-control @error('base_unit_name') is-invalid @enderror" value="{{ old('base_unit_name', 'Unit') }}" placeholder="e.g. Unit, Tablet, Bottle">
                        </div>
                        <div class="col-md-3 mb-3">
                            <label class="field-label">Default Sale Unit</label>
                            <select name="unit_type" id="unit_type" class="form-control @error('unit_type') is-invalid @enderror" required>
                                <option value="unit" @selected(old('unit_type', 'unit') === 'unit')>Unit</option>
                                <option value="sachet" @selected(old('unit_type') === 'sachet')>Sachet</option>
                                <option value="roll" @selected(old('unit_type') === 'roll')>Roll</option>
                                <option value="carton" @selected(old('unit_type') === 'carton')>Carton</option>
                            </select>
                            <small class="field-note">Choose how this product is normally sold.</small>
                        </div>
                        <div class="col-md-3 mb-3"></div>
                    </div>
                    </div>

                    <div class="product-section">
                        <div class="product-section-header">
                            <div>
                                <h5 class="product-section-title">Pricing, Stock & Media</h5>
                                <p class="product-section-copy">Enter opening quantities, selling prices, reorder levels, and optional product media in one place.</p>
                            </div>
                        </div>
                    <div class="row">
                        <div class="col-md-3 mb-3">
                            <label class="field-label">Opening Carton Quantity</label>
                            <input type="number" name="stock_cartons" id="stock_cartons" class="form-control" value="{{ old('stock_cartons', 0) }}">
                        </div>
                        <div class="col-md-3 mb-3">
                            <label class="field-label">Opening Roll Quantity</label>
                            <input type="number" name="stock_rolls" id="stock_rolls" class="form-control" value="{{ old('stock_rolls', 0) }}">
                        </div>
                        <div class="col-md-3 mb-3">
                            <label class="field-label">Purchase Price (Per Unit)</label>
                            <input type="number" step="0.01" name="purchase_price" class="form-control @error('purchase_price') is-invalid @enderror" value="{{ old('purchase_price') }}" required>
                        </div>
                        <div class="col-md-3 mb-3">
                            <label class="field-label">Retail / Default Price</label>
                            <input type="number" step="0.01" name="price" class="form-control @error('price') is-invalid @enderror" value="{{ old('price') }}" required>
                        </div>
                        <div class="col-md-3 mb-3">
                            <label class="field-label">Wholesale Price</label>
                            <input type="number" step="0.01" name="wholesale_price" class="form-control @error('wholesale_price') is-invalid @enderror" value="{{ old('wholesale_price') }}">
                        </div>
                        <div class="col-md-3 mb-3">
                            <label class="field-label">Special Discount Price</label>
                            <input type="number" step="0.01" name="special_price" class="form-control @error('special_price') is-invalid @enderror" value="{{ old('special_price') }}">
                        </div>
                        <div class="col-md-3 mb-3">
                            <label class="field-label text-info" id="units_per_carton_label">Units Per Carton</label>
                            <div class="form-control bg-light-info fw-semibold" id="units_per_carton_preview">0 Units</div>
                            <small class="field-note">Packaging preview only. This does not increase stock until you enter opening cartons, rolls, or loose units below.</small>
                        </div>
                        
                        <div class="col-md-3 mb-3">
                            <label class="field-label" id="opening_unit_label">Opening Loose Unit Quantity</label>
                            <input type="number" name="stock_units" id="stock_units" class="form-control" value="{{ old('stock_units', 0) }}">
                            <small class="field-note">Enter only the loose units/pieces already on hand, not the carton definition above.</small>
                        </div>
                        <div class="col-md-3 mb-3">
                            <label class="field-label text-primary">Total Opening Stock (Units)</label>
                            <div class="form-control bg-dark text-white fw-bold" id="total_pieces_preview">0</div>
                            <input type="hidden" name="stock" id="final_stock_input" value="{{ old('stock', 0) }}">
                            <small class="field-note">Calculated from opening cartons + opening rolls + opening loose units only.</small>
                        </div>
                        <div class="col-md-3 mb-3">
                            <label class="field-label">Product Image</label>
                            <input type="file" name="image" id="product_image_input" class="form-control @error('image') is-invalid @enderror">
                            <small class="field-note">Leave this empty if the product has no image.</small>
                        </div>
                        <div class="col-md-3 mb-3">
                            <label class="field-label">Reorder Level</label>
                            <input type="number" name="reorder_level" min="0" class="form-control" value="{{ old('reorder_level', 0) }}">
                            <small class="field-note">Stock threshold that should trigger a low-stock alert for this item.</small>
                        </div>
                        <div class="col-md-3 mb-3">
                            <label class="field-label">Suggested Reorder Qty</label>
                            <input type="number" name="reorder_quantity" min="0" class="form-control" value="{{ old('reorder_quantity', 0) }}">
                            <small class="field-note">Recommended replenishment quantity when the item drops below its threshold.</small>
                        </div>
                    </div>
                    </div>

                    <div class="product-form-actions no-print">
                        <p class="helper">Save once and the product, pricing, and opening stock will be ready together.</p>
                        <div class="text-end">
                        <button type="submit" class="btn btn-primary btn-lg px-5 shadow">Save Product & Stock</button>
                        </div>
                    </div>
                </form>
            </div>
        </div>

    </div>
</div>

<div class="modal fade" id="addCategoryModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <form id="ajaxAddCategoryForm">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title">Quick Category</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div id="category_error_message" class="alert alert-danger d-none" role="alert"></div>
                    <input type="text" name="name" id="new_category_name" class="form-control" placeholder="Category Name" required>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light border" data-bs-dismiss="modal">Close</button>
                    <button type="submit" class="btn btn-primary">Add Category</button>
                </div>
            </form>
        </div>
    </div>
</div>

<style>
    .bg-light-danger { background-color: #fff5f5; border: 1px solid #feb2b2; }
    .bg-light-warning { background-color: #fffaf0; border: 1px solid #fbd38d; }
    .bg-light-info { background-color: #f0f9ff; border: 1px solid #7dd3fc; }
    @media print { .no-print, .sidebar, .header { display: none !important; } .page-wrapper { margin: 0 !important; } }
</style>
@endsection

@push('scripts')
<script>
    $(document).ready(function() {
        const categoryIndexUrl = '/inventory/products/category';
        const categoryStoreUrl = '/inventory/products/category';
        const categoryError = $('#category_error_message');
        const categoryPageSuccess = $('#category_page_success_message');
        const categoryPageSuccessText = $('#category_page_success_text');

        function showCategoryError(message) {
            categoryError.removeClass('d-none').text(message || 'Unable to complete category request.');
        }

        function clearCategoryError() {
            categoryError.addClass('d-none').text('');
        }

        function showCategorySuccess(message) {
            categoryPageSuccessText.text(message || 'Category added successfully.');
            categoryPageSuccess.removeClass('d-none').addClass('show');

            window.setTimeout(() => {
                categoryPageSuccess.removeClass('show').addClass('d-none');
            }, 3500);
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

        async function reloadCategoryOptions(selectedValue = '') {
            const select = document.getElementById('product_category_select');
            if (!select) {
                return;
            }

            const response = await fetch(categoryIndexUrl, {
                headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
            });
            const payload = await parseJsonResponse(response, 'Category list returned HTML instead of JSON.');
            const categories = Array.isArray(payload?.data) ? payload.data : [];

            select.innerHTML = '';
            select.add(new Option('Select Category', '', false, false));

            categories.forEach((category) => {
                if (!category?.id || !category?.name) {
                    return;
                }

                const isSelected = selectedValue !== '' && String(category.id) === String(selectedValue);
                select.add(new Option(category.name, category.id, isSelected, isSelected));
            });

            if (selectedValue !== '') {
                select.value = String(selectedValue);
            }
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
        }

        function refreshPackagingLabels() {
            const baseUnitName = ($('input[name="base_unit_name"]').val() || 'unit').trim();
            const unitLabel = baseUnitName.length ? baseUnitName : 'unit';
            const titleUnit = unitLabel.charAt(0).toUpperCase() + unitLabel.slice(1);

            $('#unit_total_hint').text('Total ' + unitLabel + 's inside one carton');
            $('#unit_total_help').text('Type the full number of sellable ' + unitLabel + 's inside one carton first.');
            $('#roll_content_hint').text(unitLabel + 's per roll');
            $('#roll_content_help').text('Enter how many sellable ' + unitLabel + 's are inside one roll. Leave this at 0 if the product does not use rolls.');
            $('#carton_content_hint').text('Auto-calculated rolls per carton');
            $('#carton_content_help').text('This is calculated from total ' + unitLabel + 's and ' + unitLabel + 's per roll. If rolls are not used, it matches the unit total.');
            $('#units_per_carton_label').text(titleUnit + 's Per Carton');
            $('#opening_unit_label').text('Opening Loose ' + titleUnit + ' Quantity');
        }

        refreshPackagingLabels();
        calculateTotalPieces();
        // Automatic Calculation Logic
        function calculateCartonContent() {
            let unitTotal = parseFloat($('#unit_total_per_carton').val()) || 0;
            let unitsPerRoll = parseFloat($('#upr').val()) || 0;
            let cartonContent = unitsPerRoll > 0 ? (unitTotal / unitsPerRoll) : unitTotal;

            if (!Number.isFinite(cartonContent)) {
                cartonContent = 0;
            }

            $('#upc').val(cartonContent);
            $('#units_per_carton_preview').text(unitTotal.toLocaleString() + " Units");
        }

        function calculateTotalPieces() {
            let cartons = parseFloat($('#stock_cartons').val()) || 0;
            let rolls = parseFloat($('#stock_rolls').val()) || 0;
            let units = parseFloat($('#stock_units').val()) || 0;
            let rollsPerCarton = parseFloat($('#upc').val()) || 0;
            let unitsPerRoll = parseFloat($('#upr').val()) || 0;

            let fromCartons = unitsPerRoll > 0 ? (cartons * rollsPerCarton * unitsPerRoll) : (cartons * rollsPerCarton);
            let fromRolls = unitsPerRoll > 0 ? (rolls * unitsPerRoll) : rolls;
            let total = units + fromRolls + fromCartons;
            $('#total_pieces_preview').text(total.toLocaleString() + " Units");
            $('#final_stock_input').val(total);
        }

        $('#stock_cartons, #stock_rolls, #stock_units, #upc, #upr, #unit_total_per_carton').on('input', function() {
            if (this.id === 'upr' || this.id === 'unit_total_per_carton') {
                calculateCartonContent();
            }
            calculateTotalPieces();
        });

        $('input[name="base_unit_name"]').on('input', function() {
            refreshPackagingLabels();
        });

        calculateCartonContent();
        reloadCategoryOptions($('#product_category_select').val() || '').catch((error) => {
            console.error('Unable to load categories', error);
            showCategoryError(error.message || 'Unable to load categories.');
        });

        $('#add_product_form').on('submit', function() {
            const imageInput = document.getElementById('product_image_input');
            if (imageInput && (!imageInput.files || imageInput.files.length === 0)) {
                imageInput.disabled = true;
            }
        });

        $('#ajaxAddCategoryForm').on('submit', function(e) {
            e.preventDefault();

            const form = this;
            const btn = $(this).find('button[type="submit"]');
            btn.prop('disabled', true);
            clearCategoryError();

            fetch(categoryStoreUrl, {
                method: "POST",
                headers: {
                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                },
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
            .then((data) => {
                if (data?.data) {
                    upsertCategoryOption('#product_category_select', data.data);
                    reloadCategoryOptions(String(data.data.id))
                        .then(() => {
                            clearCategoryError();
                            showCategorySuccess(data?.message || 'Category added successfully.');
                            bootstrap.Modal.getOrCreateInstance(document.getElementById('addCategoryModal')).hide();
                            form.reset();
                        });
                }
            })
            .catch((err) => {
                showCategoryError(err.message || 'Unable to add category.');
            })
            .finally(() => btn.prop('disabled', false));
        });
    });
</script>
@endpush
