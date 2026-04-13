@extends('layout.mainlayout')

@section('content')
    <style>
        .product-form-shell {
            max-width: 1320px;
            margin: 0 auto;
        }
        .product-form-hero {
            background:
                radial-gradient(circle at top right, rgba(16, 185, 129, 0.12), transparent 30%),
                linear-gradient(135deg, #ffffff 0%, #f8fffc 45%, #eefaf6 100%);
            border: 1px solid #d1fae5;
            border-radius: 28px;
            padding: 28px 30px;
            box-shadow: 0 18px 45px rgba(16, 185, 129, 0.07);
            margin-bottom: 24px;
        }
        .product-form-kicker {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 8px 14px;
            border-radius: 999px;
            background: #ecfdf5;
            color: #059669;
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
            border: 1px solid #dcfce7;
            border-radius: 28px;
            box-shadow: 0 20px 45px rgba(15, 23, 42, 0.06);
            overflow: hidden;
        }
        .product-form-card .card-body {
            padding: 28px;
            background: linear-gradient(180deg, #ffffff 0%, #fbfffd 100%);
        }
        .product-section {
            border: 1px solid #e2e8f0;
            border-radius: 22px;
            background: #ffffff;
            padding: 22px 22px 8px;
            margin-bottom: 18px;
        }
        .product-section-header {
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
        .product-form-card .form-select {
            min-height: 49px;
            border-radius: 14px;
            border-color: #dbe3f0;
            box-shadow: none;
        }
        .product-form-card textarea.form-control {
            min-height: 120px;
            border-radius: 18px;
        }
        .product-form-card .form-control:focus,
        .product-form-card .form-select:focus {
            border-color: #34d399;
            box-shadow: 0 0 0 4px rgba(52, 211, 153, 0.12);
        }
        .product-form-actions {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 14px;
            margin-top: 8px;
            padding-top: 16px;
            border-top: 1px solid #dcfce7;
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
            @if($errors->any())
                <div class="alert alert-danger">
                    <ul>
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            @component('components.page-header')
                @slot('title') Edit Product @endslot
            @endcomponent

            <div class="product-form-hero">
                <span class="product-form-kicker"><i class="feather-edit-3"></i> Product Update</span>
                <h1 class="product-form-title">Update product details without losing the stock and pricing context.</h1>
                <p class="product-form-copy">The edit screen keeps the same fields, but now they are grouped more clearly so updates feel faster, safer, and easier to understand.</p>
            </div>

            <div class="card product-form-card">
                <div class="card-body">
                    <form action="{{ route('inventory.Products.update', $product->id) }}" method="POST" enctype="multipart/form-data">
                        @csrf
                        @method('PUT')

                        <div class="product-section">
                            <div class="product-section-header">
                                <h5 class="product-section-title">Product Information</h5>
                                <p class="product-section-copy">Update the product identity, pricing, and branch sellable stock in one place.</p>
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
                                    <label class="field-label">Product Code (SKU) *</label>
                                    <input type="text" name="sku" class="form-control" value="{{ old('sku', $product->sku) }}" required>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label class="field-label">Category *</label>
                                    @if($categories->isNotEmpty())
                                        <div class="input-group">
                                            <select name="category_id" id="edit_product_category_select" class="form-control" required>
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
                                            <div class="form-control bg-light d-flex align-items-center text-muted">No category added yet.</div>
                                            <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#editAddCategoryModal">
                                                Add Category
                                            </button>
                                            <small class="field-note">Create the first category here and it will be selected automatically.</small>
                                        </div>
                                    @endif
                                </div>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label class="field-label">Retail / Default Price *</label>
                                    <input type="number" step="0.01" name="price" class="form-control" value="{{ old('price', $product->price) }}" required>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label class="field-label">Purchase Price *</label>
                                    <input type="number" step="0.01" name="purchase_price" class="form-control" value="{{ old('purchase_price', $product->purchase_price) }}" required>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label class="field-label">Total Stock Quantity *</label>
                                    <input type="number" name="stock" class="form-control" value="{{ old('stock', $product->active_branch_stock ?? $product->stock) }}" required>
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
                        </div>

                        <div class="product-section">
                            <div class="product-section-header">
                                <h5 class="product-section-title">Packaging & Units</h5>
                                <p class="product-section-copy">Keep packaging definitions clean so sales and inventory conversions stay consistent.</p>
                            </div>
                        <div class="row">
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label class="field-label">Base Unit Name * (e.g., Unit, Tablet, Bottle)</label>
                                    <input type="text" name="base_unit_name" class="form-control" value="{{ old('base_unit_name', $product->base_unit_name) }}" placeholder="e.g. Pcs" required>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label class="field-label">Unit Total <small class="d-block text-muted" id="edit_unit_total_hint">Total units inside one carton</small></label>
                                    <input type="number" id="edit_unit_total_per_carton" min="0" step="0.01" class="form-control" value="{{ old('unit_total_per_carton', ((float) old('units_per_roll', $product->units_per_roll ?? 0) > 0) ? (float) old('units_per_carton', $product->units_per_carton ?? 0) * (float) old('units_per_roll', $product->units_per_roll ?? 0) : old('units_per_carton', $product->units_per_carton ?? 0)) }}">
                                    <small class="field-note" id="edit_unit_total_help">Type the full number of sellable units inside one carton first.</small>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label class="field-label">Roll Content <small class="d-block text-muted" id="edit_roll_content_hint">Units per roll</small></label>
                                    <input type="number" name="units_per_roll" min="0" class="form-control" value="{{ old('units_per_roll', $product->units_per_roll ?? 0) }}">
                                    <small class="field-note" id="edit_roll_content_help">Leave 0 when the item is sold in cartons and units only.</small>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label class="field-label">Carton Content <small class="d-block text-muted" id="edit_carton_content_hint">Auto-calculated rolls per carton</small></label>
                                    <input type="number" name="units_per_carton" min="0" step="0.01" class="form-control" value="{{ old('units_per_carton', $product->units_per_carton ?? 0) }}">
                                    <small class="field-note" id="edit_carton_content_help">This is calculated from unit total and roll content. If rolls are not used, it matches the unit total.</small>
                                </div>
                            </div>
                            <div class="col-md-3">
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

                        <div class="row">
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label class="field-label" id="edit_units_per_carton_label">Units Per Carton</label>
                                    <input type="text" class="form-control bg-light" id="edit_units_per_carton_preview" value="0 Units" readonly>
                                </div>
                            </div>
                        </div>
                        </div>

                        <div class="product-section">
                            <div class="product-section-header">
                                <h5 class="product-section-title">Status, Media & Notes</h5>
                                <p class="product-section-copy">Manage visibility, barcode, imagery, and descriptive notes for this product.</p>
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
                            <p class="helper">Updating for domain: {{ env('SESSION_DOMAIN', 'Localhost') }}</p>
                            <div class="text-end">
                                <a href="{{ route('product-list') }}" class="btn btn-secondary">Cancel</a>
                                <button type="submit" class="btn btn-primary">Save Changes</button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="editAddCategoryModal" tabindex="-1" style="z-index: 1060;">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <form id="editAjaxAddCategoryForm">
                    @csrf
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
        const editCategoryStoreUrl = @json(route('inventory.categories.store'));

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
            const cartonContent = unitsPerRoll > 0 ? (unitTotal / unitsPerRoll) : unitTotal;
            $('#edit_roll_content_hint').text(unitLabel + 's per roll');
            $('#edit_roll_content_help').text('Leave 0 when the item is sold in cartons and ' + unitLabel + 's only.');
            const titleUnit = unitLabel.charAt(0).toUpperCase() + unitLabel.slice(1);

            $('input[name="units_per_carton"]').val(Number.isFinite(cartonContent) ? cartonContent : 0);
            $('#edit_unit_total_hint').text('Total ' + unitLabel + 's inside one carton');
            $('#edit_unit_total_help').text('Type the full number of sellable ' + unitLabel + 's inside one carton first.');
            $('#edit_carton_content_hint').text('Auto-calculated rolls per carton');
            $('#edit_carton_content_help').text('This is calculated from total ' + unitLabel + 's and ' + unitLabel + 's per roll. If rolls are not used, it matches the unit total.');
            $('#edit_units_per_carton_label').text(titleUnit + 's Per Carton');
            $('#edit_units_per_carton_preview').val(unitTotal.toLocaleString() + ' Units');
        }

        refreshEditPackagingLabels();
        $('input[name="base_unit_name"], #edit_unit_total_per_carton, input[name="units_per_roll"]').on('input', function () {
            refreshEditPackagingLabels();
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
                body: JSON.stringify({ name: $('#edit_new_category_name').val() })
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
                                <select name="category_id" id="edit_product_category_select" class="form-control" required>
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
