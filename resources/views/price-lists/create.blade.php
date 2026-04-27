@extends('layout.mainlayout')

@section('content')
<div class="page-wrapper">
    <div class="content container-fluid">
        <div class="page-header">
            <div class="content-page-header">
                <h5 class="mb-1">Create Price List</h5>
                <p class="text-muted mb-0">Set up a branch-safe selling rate list for products.</p>
            </div>
        </div>

        <div class="card">
            <div class="card-body">
                <form method="POST" action="{{ route('price-lists.store') }}">
                    @csrf
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">Name</label>
                            <input type="text" name="name" class="form-control" value="{{ old('name') }}" required>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Currency</label>
                            <input type="text" name="currency" class="form-control" value="{{ old('currency', 'NGN') }}" maxlength="3" required>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Discount Type</label>
                            <select name="discount_type" class="form-select">
                                <option value="">None</option>
                                <option value="percentage" @selected(old('discount_type') === 'percentage')>Percentage</option>
                                <option value="fixed" @selected(old('discount_type') === 'fixed')>Fixed</option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Discount Value</label>
                            <input type="number" step="0.01" min="0" name="discount_value" class="form-control" value="{{ old('discount_value') }}">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Valid From</label>
                            <input type="date" name="valid_from" class="form-control" value="{{ old('valid_from') }}">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Valid To</label>
                            <input type="date" name="valid_to" class="form-control" value="{{ old('valid_to') }}">
                        </div>
                        <div class="col-md-3 d-flex align-items-end">
                            <div class="form-check me-3">
                                <input class="form-check-input" type="checkbox" name="is_default" value="1" id="is_default" @checked(old('is_default'))>
                                <label class="form-check-label" for="is_default">Default</label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" name="is_active" value="1" id="is_active" @checked(old('is_active', true))>
                                <label class="form-check-label" for="is_active">Active</label>
                            </div>
                        </div>
                        <div class="col-12">
                            <label class="form-label">Notes</label>
                            <textarea name="notes" class="form-control" rows="3">{{ old('notes') }}</textarea>
                        </div>
                    </div>

                    <hr>

                    <h6 class="mb-3">Items</h6>
                    <div class="table-responsive">
                        <table class="table table-bordered" id="price-items-table">
                            <thead>
                                <tr>
                                    <th>Product</th>
                                    <th>Price</th>
                                    <th>Min Qty</th>
                                    <th class="text-end">Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                @php $items = old('items', [['product_id' => '', 'price' => '', 'min_quantity' => 1]]); @endphp
                                @foreach($items as $index => $item)
                                    <tr>
                                        <td>
                                            <select name="items[{{ $index }}][product_id]" class="form-select">
                                                <option value="">Select product</option>
                                                @foreach($products as $product)
                                                    <option value="{{ $product->id }}" @selected(($item['product_id'] ?? '') == $product->id)>{{ $product->name }}</option>
                                                @endforeach
                                            </select>
                                        </td>
                                        <td><input type="number" step="0.01" min="0" name="items[{{ $index }}][price]" class="form-control" value="{{ $item['price'] ?? '' }}"></td>
                                        <td><input type="number" step="0.01" min="0" name="items[{{ $index }}][min_quantity]" class="form-control" value="{{ $item['min_quantity'] ?? 1 }}"></td>
                                        <td class="text-end"><button type="button" class="btn btn-sm btn-outline-danger remove-price-item">Remove</button></td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    <button type="button" class="btn btn-outline-secondary btn-sm mb-3" id="add-price-item">Add Item</button>

                    <div>
                        <button type="submit" class="btn btn-primary">Create Price List</button>
                        <a href="{{ route('price-lists.index') }}" class="btn btn-outline-secondary">Cancel</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const products = @json($products->map(fn($product) => ['id' => $product->id, 'name' => $product->name])->values());
    const tbody = document.querySelector('#price-items-table tbody');
    let rowIndex = tbody.querySelectorAll('tr').length;

    document.getElementById('add-price-item').addEventListener('click', function () {
        const row = document.createElement('tr');
        const options = ['<option value="">Select product</option>'].concat(products.map(product => `<option value="${product.id}">${product.name}</option>`)).join('');
        row.innerHTML = `
            <td><select name="items[${rowIndex}][product_id]" class="form-select">${options}</select></td>
            <td><input type="number" step="0.01" min="0" name="items[${rowIndex}][price]" class="form-control"></td>
            <td><input type="number" step="0.01" min="0" name="items[${rowIndex}][min_quantity]" class="form-control" value="1"></td>
            <td class="text-end"><button type="button" class="btn btn-sm btn-outline-danger remove-price-item">Remove</button></td>
        `;
        tbody.appendChild(row);
        rowIndex++;
    });

    tbody.addEventListener('click', function (event) {
        if (event.target.classList.contains('remove-price-item') && tbody.querySelectorAll('tr').length > 1) {
            event.target.closest('tr').remove();
        }
    });
});
</script>
@endsection
