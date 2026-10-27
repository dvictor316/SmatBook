@extends('layout.mainlayout')

@section('content')
    @php
        $editMeasurementMode = old('measurement_mode', 'custom');
        $cartonProductUnit = $product->activeProductUnits->first(function ($productUnit) {
            return in_array(strtolower((string) ($productUnit->unit_name ?: $productUnit->unit_symbol)), ['carton', 'ctn'], true);
        });
    @endphp
    <style>
        .product-form-shell { max-width: 900px; margin: 0 auto; }
        .product-form-heading { display: flex; align-items: center; gap: 12px; margin-bottom: 18px; }
        .product-form-heading h1 { font-size: 1.35rem; margin: 0; font-weight: 700; color: #172033; }
        .product-form-heading p { color: #64748b; font-size: .86rem; margin: 2px 0 0; }
        .product-form-heading > div { min-width: 0; overflow-wrap: anywhere; }
        .product-form-back { width: 36px; height: 36px; flex: 0 0 36px; display: grid; place-items: center; border: 1px solid #cbd5e1; border-radius: 6px; color: #334155; background: #fff; }
        .product-edit-form { background: #fff; border: 1px solid #e2e8f0; border-radius: 6px; padding: 20px; }
        .product-section { padding: 0 0 12px; margin-bottom: 18px; border-bottom: 1px solid #e2e8f0; }
        .product-section-title { font-size: 1rem; font-weight: 700; color: #172033; margin: 0 0 14px; }
        .product-section-copy { display: none; }
        .field-label { font-size: .84rem; font-weight: 600; color: #334155; margin-bottom: 5px; }
        .field-note { color: #64748b; font-size: .77rem; margin-top: 4px; }
        .product-edit-form .form-group { margin-bottom: 14px; }
        .product-edit-form .form-control, .product-edit-form .form-select { min-height: 40px; border-radius: 6px; }
        .product-edit-form textarea.form-control { min-height: 88px; }
        .product-advanced { border-bottom: 1px solid #e2e8f0; margin-bottom: 18px; }
        .product-advanced summary { cursor: pointer; font-weight: 700; color: #172033; padding: 0 0 16px; list-style: none; }
        .product-advanced summary::-webkit-details-marker { display: none; }
        .product-advanced summary::after { content: '+'; float: right; font-size: 1.1rem; }
        .product-advanced[open] summary::after { content: '-'; }
        .product-advanced .product-section { border: 0; margin: 0; }
        .product-form-actions { display: flex; justify-content: flex-end; gap: 10px; }
        .product-form-actions .btn { min-width: 108px; }
        @media (max-width: 576px) {
            .product-form-shell { padding-left: 12px; padding-right: 12px; }
            .product-edit-form { padding: 16px; }
            .product-form-actions .btn { flex: 1; }
        }
    </style>
    <div class="page-wrapper">
        <div class="content container-fluid product-form-shell">
            <div class="product-form-heading">
                <a href="{{ route('product-list') }}" class="product-form-back" title="Back to products" aria-label="Back to products"><i class="fas fa-arrow-left"></i></a>
                <div>
                    <h1>Edit product</h1>
                    <p>{{ $product->name }}{{ !empty($activeBranch['name']) ? ' | ' . $activeBranch['name'] : '' }}</p>
                </div>
            </div>

            <form class="product-edit-form" action="{{ route('inventory.Products.update', $product->id) }}" method="POST" enctype="multipart/form-data" novalidate>
                        @csrf
                        @method('PUT')

                        <div class="product-section">
                            <div class="product-section-header">
                                <h5 class="product-section-title">Product Information</h5>
                            </div>
                        <div class="row">
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label class="field-label">Item Name *</label>
                                    <input type="text" name="name" class="form-control" value="{{ old('name', $product->name) }}" required>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label class="field-label">Product Code (SKU)</label>
                                    <input type="text" name="sku" class="form-control" value="{{ old('sku', $product->sku) }}" placeholder="Leave blank to auto-generate">
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label class="field-label">Category</label>
                                    @if($categories->isNotEmpty())
                                        <div class="input-group">
                                            <select name="category_id" id="edit_product_category_select" class="form-control">
                                                <option value="">No category</option>
                                                @foreach($categories as $category)
                                                    <option value="{{ $category->id }}" {{ (string) old('category_id', $product->category_id) === (string) $category->id ? 'selected' : '' }}>
                                                        {{ $category->name }}
                                                    </option>
                                                @endforeach
                                            </select>
                                            <button type="button" class="btn btn-outline-primary" data-bs-toggle="modal" data-bs-target="#editAddCategoryModal" title="Quick add category">+</button>
                                        </div>
                                    @else
                                        <div class="d-flex flex-column gap-2">
                                            <input type="hidden" name="category_id" id="edit_product_category_select" value="{{ old('category_id', $product->category_id) }}">
                                            <div class="form-control bg-light d-flex align-items-center text-muted">No category selected.</div>
                                            <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#editAddCategoryModal">
                                                Add Category
                                            </button>
                                            <small class="field-note">You can save without a category or add one here.</small>
                                        </div>
                                    @endif
                                </div>
                            </div>
                        </div>

                        @if($fashionCatalogEnabled)
                            <div class="row">
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label class="field-label">Brand (optional)</label>
                                        <input type="text" name="brand" maxlength="191" class="form-control" value="{{ old('brand', $product->brand) }}">
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label class="field-label">Size</label>
                                        <input type="text" name="fashion_size" maxlength="80" class="form-control" value="{{ old('fashion_size', $product->fashion_size) }}">
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label class="field-label">Colour</label>
                                        <input type="text" name="fashion_color" maxlength="80" class="form-control" value="{{ old('fashion_color', $product->fashion_color) }}">
                                    </div>
                                </div>
                            </div>
                        @endif

                        <div class="row">
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label class="field-label" id="edit_selling_price_label">Retail / Default Price *</label>
                                    <input type="number" step="0.01" name="price" class="form-control" value="{{ old('price', $product->price) }}" required>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label class="field-label" id="edit_purchase_price_label">Purchase Price *</label>
                                    <input type="number" step="0.01" name="purchase_price" class="form-control" value="{{ old('purchase_price', $product->purchase_price) }}" required>
                                </div>
                            </div>
                            <div class="col-md-4" id="edit_total_stock_field">
                                <div class="form-group">
                                    <label class="field-label" id="edit_stock_label">Total Stock Quantity *</label>
                                    <input type="number" step="0.000001" min="0" name="stock" id="edit_total_stock" class="form-control" value="{{ old('stock', $product->active_branch_stock ?? $product->stock) }}" required>
                                    <small class="field-note">Updating this value will set the sellable stock for the current active branch{{ !empty($activeBranch['name'] ?? null) ? ' (' . $activeBranch['name'] . ')' : '' }}.</small>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label class="field-label">Wholesale Price</label>
                                    <input type="number" step="0.01" name="wholesale_price" class="form-control" value="{{ old('wholesale_price', $product->wholesale_price) }}">
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label class="field-label">Special Discount Price</label>
                                    <input type="number" step="0.01" name="special_price" class="form-control" value="{{ old('special_price', $product->special_price) }}">
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label class="field-label">Reorder Level</label>
                                    <input type="number" name="reorder_level" min="0" class="form-control" value="{{ old('reorder_level', $product->reorder_level ?? 0) }}">
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label class="field-label">Suggested Reorder Qty</label>
                                    <input type="number" name="reorder_quantity" min="0" class="form-control" value="{{ old('reorder_quantity', $product->reorder_quantity ?? 0) }}">
                                </div>
                            </div>
                        </div>
                        <div class="mt-2">
                            <div class="form-check">
                                <input type="checkbox" class="form-check-input" id="edit_packaging_stock_toggle" @checked(old('stock_entry_mode') === 'packaging')>
                                <label class="form-check-label" for="edit_packaging_stock_toggle">Enter stock as cartons, rolls, and loose pieces</label>
                            </div>
                            <input type="hidden" name="stock_entry_mode" id="edit_stock_entry_mode" value="{{ old('stock_entry_mode', 'total') }}">
                            <input type="hidden" name="stock" id="edit_packaging_stock_value" value="" disabled>
                            <div id="edit_packaging_stock_fields" class="row mt-2" style="display: none;">
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label class="field-label">Full Cartons</label>
                                        <input type="number" min="0" step="0.01" name="stock_cartons" class="form-control" value="{{ old('stock_cartons', 0) }}" disabled>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label class="field-label">Full Rolls</label>
                                        <input type="number" min="0" step="0.01" name="stock_rolls" class="form-control" value="{{ old('stock_rolls', 0) }}" disabled>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label class="field-label" id="edit_loose_stock_label">Loose Pieces (pcs)</label>
                                        <input type="number" min="0" step="0.01" name="stock_units" class="form-control" value="{{ old('stock_units', 0) }}" disabled>
                                    </div>
                                </div>
                                <div class="col-12">
                                    <small class="field-note">Cartons and rolls are converted using the packaging quantities below. The total is saved as stock in the product's base unit.</small>
                                    <div class="field-note fw-semibold" id="edit_packaging_stock_preview" aria-live="polite"></div>
                                </div>
                            </div>
                        </div>
                        </div>

                        <details class="product-advanced" @if($errors->hasAny(['measurement_mode', 'kg_per_carton', 'units_per_carton', 'units_per_roll', 'unit_type', 'unit_id', 'base_unit_id', 'purchase_unit_id'])) open @endif>
                            <summary>Packaging and units</summary>
                        <div class="product-section">
                        <input type="hidden" name="measurement_mode" id="edit_measurement_mode_input" value="{{ $editMeasurementMode }}">
                        <div class="form-group">
                            <label class="field-label d-block">How is this product sold?</label>
                            <div class="btn-group w-100" role="group" aria-label="Measurement setup">
                                <button type="button" class="btn edit-measurement-mode-button" data-measurement-mode="kg_carton">
                                    <i class="fas fa-weight-hanging me-1"></i> Kg + Carton
                                </button>
                                <button type="button" class="btn edit-measurement-mode-button" data-measurement-mode="custom">Other packaging</button>
                            </div>
                        </div>
                        <div id="edit_kg_carton_fields" class="row" style="display: none;">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label class="field-label">Kg Per Carton *</label>
                                    <input type="number" min="0.000001" step="0.000001" name="kg_per_carton" id="edit_kg_per_carton" class="form-control" value="{{ old('kg_per_carton', $product->units_per_carton) }}">
                                    <small class="field-note">The weight contained in one full carton.</small>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label class="field-label">Carton Selling Price <span class="text-muted">(optional)</span></label>
                                    <input type="number" min="0" step="0.01" name="carton_selling_price" id="edit_carton_selling_price" class="form-control" value="{{ old('carton_selling_price', $cartonProductUnit?->selling_price) }}" placeholder="Calculated from price per kg">
                                    <small class="field-note" id="edit_carton_price_hint">Leave empty to calculate it automatically.</small>
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-3 edit-measurement-technical-field">
                                <div class="form-group">
                                    <label class="field-label">Legacy Unit Label (e.g., Unit, Tablet, Bottle)</label>
                                    <input type="text" name="base_unit_name" class="form-control" value="{{ old('base_unit_name', $product->base_unit_name ?: 'pcs') }}" placeholder="e.g. Pcs">
                                </div>
                            </div>
                            <div class="col-md-3 edit-measurement-technical-field">
                                <div class="form-group">
                                    <label class="field-label">Unit of Measure</label>
                                    <select name="unit_id" class="form-control">
                                        <option value="">Select unit</option>
                                        @foreach(($units ?? collect()) as $unit)
                                            <option value="{{ $unit->id }}" @selected((string) old('unit_id', $product->unit_id) === (string) $unit->id || (!old('unit_id', $product->unit_id) && $unit->symbol === 'pcs'))>
                                                {{ $unit->name }} ({{ $unit->symbol }})
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-3 edit-measurement-technical-field">
                                <div class="form-group">
                                    <label class="field-label">Base Unit</label>
                                    <select name="base_unit_id" class="form-control">
                                        <option value="">Same as Unit of Measure</option>
                                        @foreach(($units ?? collect()) as $unit)
                                            <option value="{{ $unit->id }}" @selected((string) old('base_unit_id', $product->base_unit_id) === (string) $unit->id)>
                                                {{ $unit->name }} ({{ $unit->symbol }})
                                            </option>
                                        @endforeach
                                    </select>
                                    <small class="field-note">Smallest unit used for stock tracking.</small>
                                </div>
                            </div>
                            <div class="col-md-3 edit-measurement-technical-field">
                                <div class="form-group">
                                    <label class="field-label">Purchase Unit</label>
                                    <select name="purchase_unit_id" class="form-control">
                                        <option value="">No bulk purchase unit</option>
                                        @foreach(($units ?? collect()) as $unit)
                                            <option value="{{ $unit->id }}" @selected((string) old('purchase_unit_id', $product->purchase_unit_id) === (string) $unit->id)>
                                                {{ $unit->name }} ({{ $unit->symbol }})
                                            </option>
                                        @endforeach
                                    </select>
                                    <small class="field-note">Optional. Does not change the base stock unit; it only controls purchase conversion.</small>
                                </div>
                            </div>
                            <div class="col-md-3 edit-measurement-technical-field">
                                <div class="form-group">
                                    <label class="field-label">Conversion Rate</label>
                                    <input type="number" step="0.000001" min="0" name="conversion_rate" class="form-control" value="{{ old('conversion_rate', $product->conversion_rate) }}" placeholder="e.g. 12">
                                    <small class="field-note">Base units inside one purchase unit.</small>
                                </div>
                            </div>
                            <div class="col-md-3 edit-custom-packaging-field">
                                <div class="form-group">
                                    <label class="field-label">Unit Total <small class="d-block text-muted" id="edit_unit_total_hint">Total units inside one carton</small></label>
                                    <input type="number" id="edit_unit_total_per_carton" min="0" step="0.01" class="form-control" value="{{ old('unit_total_per_carton', old('units_per_carton', $product->units_per_carton ?? 0)) }}">
                                    <small class="field-note" id="edit_unit_total_help">Type the full number of sellable units inside one carton first.</small>
                                </div>
                            </div>
                            <div class="col-md-3 edit-custom-packaging-field">
                                <div class="form-group">
                                    <label class="field-label">Roll Content <small class="d-block text-muted" id="edit_roll_content_hint">Units per roll</small></label>
                                    <input type="number" name="units_per_roll" min="0" class="form-control" value="{{ old('units_per_roll', $product->units_per_roll ?? 0) }}">
                                    <small class="field-note" id="edit_roll_content_help">Leave 0 when the item is sold in cartons and units only.</small>
                                </div>
                            </div>
                            <div class="col-md-3 edit-custom-packaging-field">
                                <div class="form-group">
                                    <label class="field-label">Carton Content <small class="d-block text-muted" id="edit_carton_content_hint">Auto-calculated rolls per carton</small></label>
                                    <input type="number" name="units_per_carton" min="0" step="0.01" class="form-control" value="{{ old('units_per_carton', $product->units_per_carton ?? 0) }}">
                                    <small class="field-note" id="edit_carton_content_help">This is calculated from unit total and roll content. If rolls are not used, it matches the unit total.</small>
                                </div>
                            </div>
                            <div class="col-md-3 edit-measurement-technical-field">
                                <div class="form-group">
                                    <label class="field-label">Default Unit Type *</label>
                                    <select name="unit_type" class="form-control" required>
                                        <option value="unit" {{ $product->unit_type == 'unit' ? 'selected' : '' }}>Unit</option>
                                        <option value="sachet" {{ $product->unit_type == 'sachet' ? 'selected' : '' }}>Sachet</option>
                                        <option value="roll" {{ $product->unit_type == 'roll' ? 'selected' : '' }}>Roll</option>
                                        <option value="carton" {{ $product->unit_type == 'carton' ? 'selected' : '' }}>Carton</option>
                                    </select>
                                </div>
                            </div>
                        </div>

                        <div class="row edit-custom-packaging-field">
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label class="field-label" id="edit_units_per_carton_label">Units Per Carton</label>
                                    <input type="text" class="form-control bg-light" id="edit_units_per_carton_preview" value="0 Units" readonly>
                                </div>
                            </div>
                        </div>
                        </div>
                        </details>

                        <div class="product-section">
                            <div class="product-section-header">
                                <h5 class="product-section-title">Status, Media & Notes</h5>
                            </div>
                        <div class="row mt-1">
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label class="field-label">Status *</label>
                                    <select name="status" class="form-control" required>
                                        <option value="active" {{ $product->status == 'active' ? 'selected' : '' }}>Active</option>
                                        <option value="inactive" {{ $product->status == 'inactive' ? 'selected' : '' }}>Inactive</option>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label class="field-label">Barcode</label>
                                    <input type="text" name="barcode" class="form-control" value="{{ old('barcode', $product->barcode) }}">
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label class="field-label">Expiry Date <span class="text-muted small">(optional)</span></label>
                                    <input type="date" name="expiry_date" class="form-control" value="{{ old('expiry_date', $product->expiry_date ? \Carbon\Carbon::parse($product->expiry_date)->format('Y-m-d') : '') }}">
                                </div>
                            </div>
                             <div class="col-md-4">
                                <div class="form-group">
                                    <label class="field-label">Product Image</label>
                                    <input type="file" name="image" class="form-control">
                                    <small class="field-note">Leave this empty if the product has no image. Existing image stays in place if no new file is selected.</small>
                                    @if($product->image)
                                        <div class="mt-2">
                                            <img src="{{ $product->image_url }}" alt="Current Image" width="80" class="img-thumbnail">
                                            <small class="d-block">Current Asset</small>
                                        </div>
                                    @endif
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-12">
                                <div class="form-group">
                                    <label class="field-label">Product Description</label>
                                    <textarea name="description" rows="3" class="form-control">{{ old('description', $product->description) }}</textarea>
                                </div>
                            </div>
                        </div>
                        </div>

                        <div class="product-form-actions">
                            <a href="{{ route('product-list') }}" class="btn btn-outline-secondary">Cancel</a>
                            <button type="submit" class="btn btn-primary">Save changes</button>
                        </div>
            </form>
        </div>
    </div>

    <div class="modal fade" id="editAddCategoryModal" tabindex="-1" style="z-index: 1060;">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <form id="editAjaxAddCategoryForm">
                    @csrf
                    <input type="hidden" name="type" value="product">
                    <div class="modal-header">
                        <h5 class="modal-title">Quick Category</h5>
                    </div>
                    <div class="modal-body">
                        <input type="text" name="name" id="edit_new_category_name" class="form-control" placeholder="Category Name" required>
                    </div>
                    <div class="modal-footer">
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
        const editCategoryStoreUrl = @json(route('ajax.inventory.categories.store', [], false));

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

        function refreshEditPackagingLabels() {
            const baseUnitName = ($('input[name="base_unit_name"]').val() || 'unit').trim();
            const unitLabel = baseUnitName.length ? baseUnitName : 'unit';

            const unitTotal = parseFloat($('#edit_unit_total_per_carton').val()) || 0;
            const unitsPerRoll = parseFloat($('input[name="units_per_roll"]').val()) || 0;
            const rollsPerCarton = unitsPerRoll > 0 ? (unitTotal / unitsPerRoll) : 0;
            $('#edit_roll_content_hint').text(unitLabel + 's per roll');
            $('#edit_roll_content_help').text('Leave 0 when the item is sold in cartons and ' + unitLabel + 's only.');
            const titleUnit = unitLabel.charAt(0).toUpperCase() + unitLabel.slice(1);

            $('input[name="units_per_carton"]').val(Number.isFinite(unitTotal) ? unitTotal : 0);
            $('#edit_unit_total_hint').text('Total ' + unitLabel + 's inside one carton');
            $('#edit_unit_total_help').text('Type the full number of sellable ' + unitLabel + 's inside one carton first.');
            $('#edit_carton_content_hint').text('Auto-calculated rolls per carton');
            $('#edit_carton_content_help').text(unitsPerRoll > 0 && Number.isFinite(rollsPerCarton) ? rollsPerCarton.toLocaleString() + ' rolls per carton for reference. Saved value remains total ' + unitLabel + 's per carton.' : 'Saved value remains total ' + unitLabel + 's per carton.');
            $('#edit_units_per_carton_label').text(titleUnit + 's Per Carton');
            $('#edit_units_per_carton_preview').val(unitTotal.toLocaleString() + ' Units');
        }

        function selectEditKgUnit(selector) {
            const select = document.querySelector(selector);
            if (!select) return;
            const option = Array.from(select.options).find((item) => /\(kg\)\s*$/i.test(String(item.textContent || '').trim()));
            if (option) select.value = option.value;
        }

        function refreshEditCartonPriceHint() {
            const kgPerCarton = parseFloat($('#edit_kg_per_carton').val()) || 0;
            const pricePerKg = parseFloat($('input[name="price"]').val()) || 0;
            const calculated = kgPerCarton * pricePerKg;
            $('#edit_carton_price_hint').text(calculated > 0
                ? 'Automatic carton price: ' + window.formatCompactAmount(calculated)
                : 'Leave empty to calculate it automatically.');
        }

        function refreshEditPackagingStock() {
            const cartons = parseFloat($('input[name="stock_cartons"]').val()) || 0;
            const rolls = parseFloat($('input[name="stock_rolls"]').val()) || 0;
            const looseUnits = parseFloat($('input[name="stock_units"]').val()) || 0;
            const unitsPerCarton = parseFloat($('input[name="units_per_carton"]').val()) || 0;
            const unitsPerRoll = parseFloat($('input[name="units_per_roll"]').val()) || 0;
            const baseUnit = ($('input[name="base_unit_name"]').val() || 'pcs').trim();
            const total = (cartons * unitsPerCarton) + (rolls * (unitsPerRoll > 0 ? unitsPerRoll : 1)) + looseUnits;
            $('#edit_packaging_stock_value').val(total);
            $('#edit_loose_stock_label').text('Loose ' + (baseUnit || 'pieces') + (baseUnit === 'pcs' ? ' (pcs)' : ''));
            $('#edit_packaging_stock_preview').text('Total: ' + total.toLocaleString(undefined, { maximumFractionDigits: 6 }) + ' ' + (baseUnit || 'base units'));
        }

        function setEditStockEntryMode(usePackaging) {
            const packagingFields = $('#edit_packaging_stock_fields');
            const totalStock = $('#edit_total_stock');
            const packagingStock = $('#edit_packaging_stock_value');
            const modeInput = $('#edit_stock_entry_mode');

            modeInput.val(usePackaging ? 'packaging' : 'total');
            packagingFields.toggle(usePackaging);
            $('#edit_total_stock_field').toggle(!usePackaging);
            totalStock.prop('disabled', usePackaging);
            packagingStock.prop('disabled', !usePackaging);
            $('input[name="stock_cartons"], input[name="stock_rolls"], input[name="stock_units"]').prop('disabled', !usePackaging);
            if (usePackaging) refreshEditPackagingStock();
        }

        $('#edit_packaging_stock_toggle').on('change', function () {
            setEditStockEntryMode(this.checked);
        });
        $('input[name="stock_cartons"], input[name="stock_rolls"], input[name="stock_units"], input[name="units_per_carton"], input[name="units_per_roll"], input[name="base_unit_name"]').on('input change', refreshEditPackagingStock);

        function setEditMeasurementMode(mode) {
            mode = mode === 'kg_carton' ? 'kg_carton' : 'custom';
            const isKgCarton = mode === 'kg_carton';
            $('#edit_measurement_mode_input').val(mode);
            $('#edit_kg_carton_fields').toggle(isKgCarton);
            $('.edit-measurement-technical-field, .edit-custom-packaging-field').toggle(!isKgCarton);
            $('.edit-measurement-mode-button').each(function () {
                const active = $(this).data('measurement-mode') === mode;
                const kgButton = $(this).data('measurement-mode') === 'kg_carton';
                $(this).removeClass('btn-primary btn-secondary btn-outline-primary btn-outline-secondary')
                    .addClass(active ? (kgButton ? 'btn-primary' : 'btn-secondary') : (kgButton ? 'btn-outline-primary' : 'btn-outline-secondary'));
            });

            if (isKgCarton) {
                $('input[name="base_unit_name"]').val('kg');
                selectEditKgUnit('select[name="unit_id"]');
                selectEditKgUnit('select[name="base_unit_id"]');
                $('select[name="purchase_unit_id"]').val('');
                $('input[name="conversion_rate"], input[name="units_per_roll"]').val(0);
                const kgPerCarton = $('#edit_kg_per_carton').val() || 0;
                $('#edit_unit_total_per_carton, input[name="units_per_carton"]').val(kgPerCarton);
                $('select[name="unit_type"]').val('carton');
                $('#edit_selling_price_label').text('Selling Price Per Kg *');
                $('#edit_purchase_price_label').text('Purchase Cost Per Kg *');
                $('#edit_stock_label').text('Current Stock in Kg *');
            } else {
                $('#edit_selling_price_label').text('Retail / Default Price *');
                $('#edit_purchase_price_label').text('Purchase Price *');
                $('#edit_stock_label').text('Total Stock Quantity *');
            }

            refreshEditPackagingLabels();
            refreshEditCartonPriceHint();
        }

        $('.edit-measurement-mode-button').on('click', function () {
            setEditMeasurementMode($(this).data('measurement-mode'));
        });

        $('#edit_kg_per_carton').on('input change', function () {
            if ($('#edit_measurement_mode_input').val() === 'kg_carton') {
                $('#edit_unit_total_per_carton, input[name="units_per_carton"]').val($(this).val() || 0);
                refreshEditPackagingLabels();
            }
            refreshEditCartonPriceHint();
        });

        $('input[name="price"]').on('input', refreshEditCartonPriceHint);

        refreshEditPackagingLabels();
        setEditMeasurementMode($('#edit_measurement_mode_input').val());
        setEditStockEntryMode($('#edit_packaging_stock_toggle').is(':checked'));
        $('input[name="base_unit_name"], #edit_unit_total_per_carton, input[name="units_per_roll"]').on('input', function () {
            refreshEditPackagingLabels();
            refreshEditPackagingStock();
        });

        $('#editAjaxAddCategoryForm').on('submit', function(e) {
            e.preventDefault();
            const form = this;
            const btn = $(this).find('button[type="submit"]');
            btn.prop('disabled', true);

            fetch(editCategoryStoreUrl, {
                method: "POST",
                credentials: 'same-origin',
                headers: {
                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: JSON.stringify({ name: $('#edit_new_category_name').val(), type: 'product' })
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
                if (data.data) {
                    const currentSelect = $('#edit_product_category_select');

                    if (currentSelect.is('select')) {
                        upsertCategoryOption('#edit_product_category_select', data.data);
                    } else {
                        currentSelect.val(data.data.id);
                        const categoryShell = currentSelect.closest('.d-flex.flex-column.gap-2');
                        const replacement = `
                            <div class="input-group">
                                <select name="category_id" id="edit_product_category_select" class="form-control">
                                    <option value="">No category</option>
                                    <option value="${data.data.id}" selected>${data.data.name}</option>
                                </select>
                                <button type="button" class="btn btn-outline-primary" data-bs-toggle="modal" data-bs-target="#editAddCategoryModal" title="Quick add category">+</button>
                            </div>
                        `;
                        categoryShell.html(replacement);
                    }

                    bootstrap.Modal.getInstance(document.getElementById('editAddCategoryModal')).hide();
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
