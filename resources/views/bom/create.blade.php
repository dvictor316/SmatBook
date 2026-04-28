@extends('layout.app')

@section('title', 'Create BOM')

@section('content')
<div class="page-wrapper">
<div class="content container-fluid">
    <div class="page-header"><h3 class="page-title">Create Bill of Materials</h3></div>
    <div class="card">
        <div class="card-body">
            <form method="POST" action="{{ route('bom.store') }}" class="row g-3">
                @csrf
                <div class="col-md-4">
                    <label class="form-label">Finished Product</label>
                    <select name="product_id" class="form-select" required>
                        <option value="">Select product</option>
                        @foreach($products as $product)
                            <option value="{{ $product->id }}">{{ $product->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label">Output Qty</label>
                    <input type="number" step="0.0001" min="0.0001" name="output_quantity" class="form-control" value="1" required>
                </div>
                <div class="col-md-2">
                    <label class="form-label">Unit</label>
                    <input type="text" name="unit" class="form-control" value="pcs">
                </div>
                <div class="col-md-4">
                    <label class="form-label">BOM Type</label>
                    <select name="bom_type" class="form-select" required>
                        @foreach(['standard', 'assembly', 'phantom'] as $type)
                            <option value="{{ $type }}">{{ ucfirst($type) }}</option>
                        @endforeach
                    </select>
                </div>
                @for($i = 0; $i < 3; $i++)
                    <div class="col-md-4">
                        <label class="form-label">Component</label>
                        <select name="items[{{ $i }}][component_product_id]" class="form-select" {{ $i === 0 ? 'required' : '' }}>
                            <option value="">Select component</option>
                            @foreach($products as $product)
                                <option value="{{ $product->id }}">{{ $product->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">Qty</label>
                        <input type="number" step="0.0001" min="0.0001" name="items[{{ $i }}][quantity]" class="form-control" {{ $i === 0 ? 'required' : '' }}>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">Unit</label>
                        <input type="text" name="items[{{ $i }}][unit]" class="form-control" value="pcs">
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">Unit Cost</label>
                        <input type="number" step="0.0001" min="0" name="items[{{ $i }}][unit_cost]" class="form-control">
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">Scrap %</label>
                        <input type="number" step="0.01" min="0" max="100" name="items[{{ $i }}][scrap_percentage]" class="form-control">
                    </div>
                @endfor
                <div class="col-12">
                    <label class="form-label">Instructions</label>
                    <textarea name="instructions" class="form-control" rows="3"></textarea>
                </div>
                <div class="col-12">
                    <button class="btn btn-primary">Save BOM</button>
                </div>
            </form>
        </div>
    </div>
</div>
</div>
@endsection
