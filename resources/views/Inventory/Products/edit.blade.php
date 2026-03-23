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
                                    <label>Selling Price *</label>
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
                                    <label>Current Stock Quantity *</label>
                                    <input type="number" name="stock" class="form-control" value="{{ old('stock', $product->stock) }}" required>
                                </div>
                            </div>
                        </div>

                        <h5 class="mb-3 mt-4">Packaging & Units (Required for Breakdown)</h5>
                        <div class="row">
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label>Base Unit Name * (e.g., Pcs, Kg)</label>
                                    <input type="text" name="base_unit_name" class="form-control" value="{{ old('base_unit_name', $product->base_unit_name) }}" placeholder="e.g. Pcs" required>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label>Units Per Carton</label>
                                    <input type="number" name="units_per_carton" min="0" class="form-control" value="{{ old('units_per_carton', $product->units_per_carton ?? 0) }}">
                                    <small class="text-muted">Set `0` if this product is not sold in cartons.</small>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label>Units Per Roll (Optional)</label>
                                    <input type="number" name="units_per_roll" min="0" class="form-control" value="{{ old('units_per_roll', $product->units_per_roll ?? 0) }}">
                                    <small class="text-muted">Use `0` if this product is not sold by roll.</small>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label>Default Unit Type *</label>
                                    <select name="unit_type" class="form-control" required>
                                        <option value="unit" {{ $product->unit_type == 'unit' ? 'selected' : '' }}>Unit</option>
                                        <option value="carton" {{ $product->unit_type == 'carton' ? 'selected' : '' }}>Carton</option>
                                        <option value="roll" {{ $product->unit_type == 'roll' ? 'selected' : '' }}>Roll</option>
                                    </select>
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
                                    @if($product->image)
                                        <div class="mt-2">
                                            <img src="{{ $product->image_url }}" alt="Current Image" width="80" class="img-thumbnail" onerror="this.onerror=null;this.src='{{ asset('assets/img/products/product-01.png') }}';">
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
