@extends('layout.mainlayout')

@section('content')
    <div class="page-wrapper">
        <div class="content container-fluid">
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

            <div class="card">
                <div class="card-body">
                    <form action="{{ route('inventory.Products.update', $product->id) }}" method="POST" enctype="multipart/form-data">
                        @csrf
                        @method('PUT')

                        <h5 class="mb-3">Product Information</h5>
                        <div class="row">
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label>Item Name *</label>
                                    <input type="text" name="name" class="form-control" value="{{ old('name', $product->name) }}" required>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label>Product Code (SKU) *</label>
                                    <input type="text" name="sku" class="form-control" value="{{ old('sku', $product->sku) }}" required>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label>Category *</label>
                                    <select name="category_id" class="form-control" required>
                                        @foreach($categories as $category)
                                            <option value="{{ $category->id }}" {{ $product->category_id == $category->id ? 'selected' : '' }}>
                                                {{ $category->name }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label>Retail / Default Price *</label>
                                    <input type="number" step="0.01" name="price" class="form-control" value="{{ old('price', $product->price) }}" required>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label>Purchase Price *</label>
                                    <input type="number" step="0.01" name="purchase_price" class="form-control" value="{{ old('purchase_price', $product->purchase_price) }}" required>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label>Total Stock Quantity *</label>
                                    <input type="number" name="stock" class="form-control" value="{{ old('stock', $product->active_branch_stock ?? $product->stock) }}" required>
                                    <small class="text-muted d-block mt-1">Updating this value will set the sellable stock for the current active branch{{ !empty($activeBranch['name'] ?? null) ? ' (' . $activeBranch['name'] . ')' : '' }}.</small>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label>Wholesale Price</label>
                                    <input type="number" step="0.01" name="wholesale_price" class="form-control" value="{{ old('wholesale_price', $product->wholesale_price) }}">
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label>Special Discount Price</label>
                                    <input type="number" step="0.01" name="special_price" class="form-control" value="{{ old('special_price', $product->special_price) }}">
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label>Reorder Level</label>
                                    <input type="number" name="reorder_level" min="0" class="form-control" value="{{ old('reorder_level', $product->reorder_level ?? 0) }}">
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label>Suggested Reorder Qty</label>
                                    <input type="number" name="reorder_quantity" min="0" class="form-control" value="{{ old('reorder_quantity', $product->reorder_quantity ?? 0) }}">
                                </div>
                            </div>
                        </div>

                        <h5 class="mb-3 mt-4">Packaging & Units (Required for Breakdown)</h5>
                        <div class="row">
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label>Base Unit Name * (e.g., Unit, Tablet, Bottle)</label>
                                    <input type="text" name="base_unit_name" class="form-control" value="{{ old('base_unit_name', $product->base_unit_name) }}" placeholder="e.g. Pcs" required>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label>Unit Total <small class="d-block text-muted" id="edit_unit_total_hint">Total units inside one carton</small></label>
                                    <input type="number" id="edit_unit_total_per_carton" min="0" step="0.01" class="form-control" value="{{ old('unit_total_per_carton', ((float) old('units_per_roll', $product->units_per_roll ?? 0) > 0) ? (float) old('units_per_carton', $product->units_per_carton ?? 0) * (float) old('units_per_roll', $product->units_per_roll ?? 0) : old('units_per_carton', $product->units_per_carton ?? 0)) }}">
                                    <small class="text-muted" id="edit_unit_total_help">Type the full number of sellable units inside one carton first.</small>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label>Roll Content <small class="d-block text-muted" id="edit_roll_content_hint">Units per roll</small></label>
                                    <input type="number" name="units_per_roll" min="0" class="form-control" value="{{ old('units_per_roll', $product->units_per_roll ?? 0) }}">
                                    <small class="text-muted" id="edit_roll_content_help">Leave 0 when the item is sold in cartons and units only.</small>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label>Carton Content <small class="d-block text-muted" id="edit_carton_content_hint">Auto-calculated rolls per carton</small></label>
                                    <input type="number" name="units_per_carton" min="0" step="0.01" class="form-control" value="{{ old('units_per_carton', $product->units_per_carton ?? 0) }}">
                                    <small class="text-muted" id="edit_carton_content_help">This is calculated from unit total and roll content. If rolls are not used, it matches the unit total.</small>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label>Default Unit Type *</label>
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
                                    <label id="edit_units_per_carton_label">Units Per Carton</label>
                                    <input type="text" class="form-control bg-light" id="edit_units_per_carton_preview" value="0 Units" readonly>
                                </div>
                            </div>
                        </div>

                        <div class="row mt-3">
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label>Status *</label>
                                    <select name="status" class="form-control" required>
                                        <option value="active" {{ $product->status == 'active' ? 'selected' : '' }}>Active</option>
                                        <option value="inactive" {{ $product->status == 'inactive' ? 'selected' : '' }}>Inactive</option>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label>Barcode</label>
                                    <input type="text" name="barcode" class="form-control" value="{{ old('barcode', $product->barcode) }}">
                                </div>
                            </div>
                             <div class="col-md-4">
                                <div class="form-group">
                                    <label>Product Image</label>
                                    <input type="file" name="image" class="form-control">
                                    <small class="text-muted">Leave this empty if the product has no image. Existing image stays in place if no new file is selected.</small>
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
                                    <label>Product Description</label>
                                    <textarea name="description" rows="3" class="form-control">{{ old('description', $product->description) }}</textarea>
                                </div>
                            </div>
                        </div>

                        <div class="form-group text-right">
                            <hr>
                            <p class="small text-muted">Updating for domain: {{ env('SESSION_DOMAIN', 'Localhost') }}</p>
                            <a href="{{ route('product-list') }}" class="btn btn-secondary">Cancel</a>
                            <button type="submit" class="btn btn-primary">Save Changes</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
<script>
    $(document).ready(function () {
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
    });
</script>
@endpush
