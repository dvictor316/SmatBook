@extends('layout.mainlayout')

@section('content')
<div class="page-wrapper">
    <div class="content container-fluid">
        <div class="page-header">
            <div class="content-page-header">
                <h5 class="mb-1">Create RFQ</h5>
                <p class="text-muted mb-0">Create a quotation request and attach the products you want vendors to quote.</p>
            </div>
        </div>

        <div class="card">
            <div class="card-body">
                <form method="POST" action="{{ route('rfq.store') }}">
                    @csrf
                    <div class="row g-3 mb-4">
                        <div class="col-md-8">
                            <label class="form-label">Title</label>
                            <input type="text" name="title" class="form-control" value="{{ old('title') }}" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Required Date</label>
                            <input type="date" name="required_date" class="form-control" value="{{ old('required_date') }}">
                        </div>
                        <div class="col-12">
                            <label class="form-label">Notes</label>
                            <textarea name="notes" class="form-control" rows="3">{{ old('notes') }}</textarea>
                        </div>
                    </div>

                    <h6 class="mb-3">Items</h6>
                    <div class="table-responsive">
                        <table class="table table-bordered" id="rfq-items-table">
                            <thead>
                                <tr>
                                    <th>Product</th>
                                    <th>Item Name</th>
                                    <th>Quantity</th>
                                    <th>Unit</th>
                                    <th>Specifications</th>
                                    <th class="text-end">Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                @php $items = old('items', [['product_id' => '', 'product_name' => '', 'quantity' => 1, 'unit' => '', 'specifications' => '']]); @endphp
                                @foreach($items as $index => $item)
                                    <tr>
                                        <td>
                                            <select name="items[{{ $index }}][product_id]" class="form-select">
                                                <option value="">Optional product</option>
                                                @foreach($products as $product)
                                                    <option value="{{ $product->id }}" @selected(($item['product_id'] ?? '') == $product->id)>{{ $product->name }}</option>
                                                @endforeach
                                            </select>
                                        </td>
                                        <td><input type="text" name="items[{{ $index }}][product_name]" class="form-control" value="{{ $item['product_name'] ?? '' }}" required></td>
                                        <td><input type="number" step="0.01" min="0.0001" name="items[{{ $index }}][quantity]" class="form-control" value="{{ $item['quantity'] ?? 1 }}" required></td>
                                        <td><input type="text" name="items[{{ $index }}][unit]" class="form-control" value="{{ $item['unit'] ?? '' }}"></td>
                                        <td><input type="text" name="items[{{ $index }}][specifications]" class="form-control" value="{{ $item['specifications'] ?? '' }}"></td>
                                        <td class="text-end"><button type="button" class="btn btn-sm btn-outline-danger remove-rfq-item">Remove</button></td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    <button type="button" class="btn btn-outline-secondary btn-sm mb-3" id="add-rfq-item">Add Item</button>

                    <div>
                        <button type="submit" class="btn btn-primary">Create RFQ</button>
                        <a href="{{ route('rfq.index') }}" class="btn btn-outline-secondary">Cancel</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const products = @json($products->map(fn($product) => ['id' => $product->id, 'name' => $product->name])->values());
    const tbody = document.querySelector('#rfq-items-table tbody');
    let rowIndex = tbody.querySelectorAll('tr').length;

    document.getElementById('add-rfq-item').addEventListener('click', function () {
        const options = ['<option value="">Optional product</option>'].concat(products.map(product => `<option value="${product.id}">${product.name}</option>`)).join('');
        const row = document.createElement('tr');
        row.innerHTML = `
            <td><select name="items[${rowIndex}][product_id]" class="form-select">${options}</select></td>
            <td><input type="text" name="items[${rowIndex}][product_name]" class="form-control" required></td>
            <td><input type="number" step="0.01" min="0.0001" name="items[${rowIndex}][quantity]" class="form-control" value="1" required></td>
            <td><input type="text" name="items[${rowIndex}][unit]" class="form-control"></td>
            <td><input type="text" name="items[${rowIndex}][specifications]" class="form-control"></td>
            <td class="text-end"><button type="button" class="btn btn-sm btn-outline-danger remove-rfq-item">Remove</button></td>
        `;
        tbody.appendChild(row);
        rowIndex++;
    });

    tbody.addEventListener('click', function (event) {
        if (event.target.classList.contains('remove-rfq-item') && tbody.querySelectorAll('tr').length > 1) {
            event.target.closest('tr').remove();
        }
    });
});
</script>
@endsection
