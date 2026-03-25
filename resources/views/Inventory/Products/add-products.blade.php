@extends('layout.mainlayout')
@section('content')
<div class="page-wrapper">
    <div class="content container-fluid">
        
        {{-- Header with Export Actions --}}
        <div class="page-header mb-4 no-print">
            <div class="row align-items-center">
                <div class="col">
                    <h4 class="fw-bold text-dark">Add New Product & Packaging Rules</h4>
                </div>
                <div class="col-auto">
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

        {{-- Main Form --}}
        <div class="card shadow-sm border-0 mb-4">
            <div class="card-body">
                <form action="{{ route('inventory.Products.store') }}" method="POST" enctype="multipart/form-data" novalidate id="add_product_form">
                    @csrf
                    <div class="row">
                        {{-- Basic Info --}}
                        <div class="col-md-4 mb-3">
                            <label class="form-label fw-bold">Product Name</label>
                            <input type="text" name="name" id="p_name" class="form-control @error('name') is-invalid @enderror" placeholder="e.g. Indomie Onion" value="{{ old('name') }}" required>
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label fw-bold">SKU / Code</label>
                            <input type="text" name="sku" id="p_sku" class="form-control @error('sku') is-invalid @enderror" value="{{ old('sku') }}" placeholder="Leave blank to auto-generate">
                            <small class="text-muted">A unique SKU will be generated if the product does not already have one.</small>
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label fw-bold">Barcode</label>
                            <input type="text" name="barcode" id="p_barcode" class="form-control @error('barcode') is-invalid @enderror" value="{{ old('barcode') }}" placeholder="Optional barcode">
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label fw-bold d-flex justify-content-between">
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
                            <label class="form-label fw-bold">Stock Branch</label>
                            <select name="branch_id" class="form-control">
                                <option value="">Use Active Branch</option>
                                @foreach(($availableBranches ?? []) as $branch)
                                    <option value="{{ $branch['id'] }}" @selected((string) old('branch_id') === (string) ($branch['id'] ?? ''))>{{ $branch['name'] }}</option>
                                @endforeach
                            </select>
                            <small class="text-muted">Choose the branch that should receive the opening stock for this product. Basic plan stays on a single branch.</small>
                        </div>

                        <hr class="my-3 text-muted">
                        <h5 class="mb-3 text-primary"><i class="feather-package me-2"></i>Packaging & Conversion Rules</h5>

                        {{-- Conversion Logic --}}
                        <div class="col-md-3 mb-3">
                            <label class="form-label fw-bold text-info">Unit Total <span class="text-muted fw-normal d-block small" id="unit_total_hint">Total units inside one carton</span></label>
                            <input type="number" id="unit_total_per_carton" class="form-control bg-light-info" value="{{ old('unit_total_per_carton', 0) }}" min="0" step="0.01">
                            <small class="text-muted" id="unit_total_help">Type the full number of sellable units inside one carton first.</small>
                        </div>
                        <div class="col-md-3 mb-3">
                            <label class="form-label fw-bold text-warning">Roll Content <span class="text-muted fw-normal d-block small" id="roll_content_hint">Units per roll</span></label>
                            <input type="number" name="units_per_roll" id="upr" class="form-control bg-light-warning @error('units_per_roll') is-invalid @enderror" value="{{ old('units_per_roll', 0) }}" min="0" step="0.01">
                            <small class="text-muted" id="roll_content_help">Enter how many sellable units are inside one roll. Leave this at 0 if the product does not use rolls.</small>
                        </div>
                        <div class="col-md-3 mb-3">
                            <label class="form-label fw-bold text-danger">Carton Content <span class="text-muted fw-normal d-block small" id="carton_content_hint">Auto-calculated rolls per carton</span></label>
                            <input type="number" name="units_per_carton" id="upc" class="form-control bg-light-danger @error('units_per_carton') is-invalid @enderror" value="{{ old('units_per_carton', 0) }}" min="0" step="0.01">
                            <small class="text-muted" id="carton_content_help">This is calculated from unit total and roll content. If rolls are not used, it matches the unit total.</small>
                        </div>
                        <div class="col-md-3 mb-3">
                            <label class="form-label fw-bold">Base Unit Name</label>
                            <input type="text" name="base_unit_name" class="form-control @error('base_unit_name') is-invalid @enderror" value="{{ old('base_unit_name', 'Unit') }}" placeholder="e.g. Unit, Tablet, Bottle">
                        </div>
                        <div class="col-md-3 mb-3">
                            <label class="form-label fw-bold">Default Sale Unit</label>
                            <select name="unit_type" id="unit_type" class="form-control @error('unit_type') is-invalid @enderror" required>
                                <option value="unit" @selected(old('unit_type', 'unit') === 'unit')>Unit</option>
                                <option value="sachet" @selected(old('unit_type') === 'sachet')>Sachet</option>
                                <option value="roll" @selected(old('unit_type') === 'roll')>Roll</option>
                                <option value="carton" @selected(old('unit_type') === 'carton')>Carton</option>
                            </select>
                            <small class="text-muted">Choose how this product is normally sold.</small>
                        </div>
                        <div class="col-md-3 mb-3"></div>

                        <hr class="my-3 text-muted">
                        <h5 class="mb-3 text-success"><i class="feather-shopping-cart me-2"></i>Pricing & Initial Stock</h5>

                        <div class="col-md-3 mb-3">
                            <label class="form-label">Opening Carton Quantity</label>
                            <input type="number" name="stock_cartons" id="stock_cartons" class="form-control" value="{{ old('stock_cartons', 0) }}">
                        </div>
                        <div class="col-md-3 mb-3">
                            <label class="form-label">Opening Roll Quantity</label>
                            <input type="number" name="stock_rolls" id="stock_rolls" class="form-control" value="{{ old('stock_rolls', 0) }}">
                        </div>
                        <div class="col-md-3 mb-3">
                            <label class="form-label">Purchase Price (Per Unit)</label>
                            <input type="number" step="0.01" name="purchase_price" class="form-control @error('purchase_price') is-invalid @enderror" value="{{ old('purchase_price') }}" required>
                        </div>
                        <div class="col-md-3 mb-3">
                            <label class="form-label">Retail / Default Price</label>
                            <input type="number" step="0.01" name="price" class="form-control @error('price') is-invalid @enderror" value="{{ old('price') }}" required>
                        </div>
                        <div class="col-md-3 mb-3">
                            <label class="form-label">Wholesale Price</label>
                            <input type="number" step="0.01" name="wholesale_price" class="form-control @error('wholesale_price') is-invalid @enderror" value="{{ old('wholesale_price') }}">
                        </div>
                        <div class="col-md-3 mb-3">
                            <label class="form-label">Special Discount Price</label>
                            <input type="number" step="0.01" name="special_price" class="form-control @error('special_price') is-invalid @enderror" value="{{ old('special_price') }}">
                        </div>
                        <div class="col-md-3 mb-3">
                            <label class="form-label fw-bold text-info" id="units_per_carton_label">Units Per Carton</label>
                            <div class="form-control bg-light-info fw-semibold" id="units_per_carton_preview">0 Units</div>
                            <small class="text-muted">Packaging preview only. This does not increase stock until you enter opening cartons, rolls, or loose units below.</small>
                        </div>
                        
                        {{-- Automated Calculation Preview --}}
                        <div class="col-md-3 mb-3">
                            <label class="form-label" id="opening_unit_label">Opening Loose Unit Quantity</label>
                            <input type="number" name="stock_units" id="stock_units" class="form-control" value="{{ old('stock_units', 0) }}">
                            <small class="text-muted">Enter only the loose units/pieces already on hand, not the carton definition above.</small>
                        </div>
                        <div class="col-md-3 mb-3">
                            <label class="form-label fw-bold text-primary">Total Opening Stock (Units)</label>
                            <div class="form-control bg-dark text-white fw-bold" id="total_pieces_preview">0</div>
                            <input type="hidden" name="stock" id="final_stock_input" value="{{ old('stock', 0) }}">
                            <small class="text-muted">Calculated from opening cartons + opening rolls + opening loose units only.</small>
                        </div>
                        <div class="col-md-3 mb-3">
                            <label class="form-label fw-bold">Product Image</label>
                            <input type="file" name="image" id="product_image_input" class="form-control @error('image') is-invalid @enderror">
                            <small class="text-muted">Leave this empty if the product has no image.</small>
                        </div>
                    </div>

                    <div class="text-end no-print mt-3">
                        <button type="submit" class="btn btn-primary btn-lg px-5 shadow">Save Product & Stock</button>
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

            fetch("{{ route('categories.store') }}", {
                method: "POST",
                headers: {
                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                    'Content-Type': 'application/json',
                    'Accept': 'application/json'
                },
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
            .then((data) => {
                if (data?.data) {
                    $('#product_category_select').append(new Option(data.data.name, data.data.id, true, true)).trigger('change');
                    bootstrap.Modal.getInstance(document.getElementById('addCategoryModal'))?.hide();
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
