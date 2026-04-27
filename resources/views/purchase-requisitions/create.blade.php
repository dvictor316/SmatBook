@extends('layout.mainlayout')

@section('content')
<div class="page-wrapper">
    <div class="content container-fluid">
        <div class="page-header">
            <div class="content-page-header">
                <h5 class="mb-1">New Purchase Requisition</h5>
                <p class="text-muted mb-0">Capture a purchasing need with branch-aware item details.</p>
            </div>
        </div>

        <div class="card">
            <div class="card-body">
                <form action="{{ route('purchase-requisitions.store') }}" method="POST">
                    @csrf
                    <div class="row g-3 mb-4">
                        <div class="col-md-4">
                            <label class="form-label">Required Date</label>
                            <input type="date" name="required_date" class="form-control" value="{{ old('required_date') }}">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Priority</label>
                            <select name="priority" class="form-select" required>
                                @foreach(['low', 'normal', 'urgent', 'critical'] as $priority)
                                    <option value="{{ $priority }}" @selected(old('priority', 'normal') === $priority)>{{ ucfirst($priority) }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Department</label>
                            <select name="department_id" class="form-select">
                                <option value="">None</option>
                                @foreach($departments as $department)
                                    <option value="{{ $department->id }}" @selected(old('department_id') == $department->id)>{{ $department->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Cost Center</label>
                            <select name="cost_center_id" class="form-select">
                                <option value="">None</option>
                                @foreach($costCenters as $costCenter)
                                    <option value="{{ $costCenter->id }}" @selected(old('cost_center_id') == $costCenter->id)>{{ $costCenter->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Justification</label>
                            <input type="text" name="justification" class="form-control" value="{{ old('justification') }}">
                        </div>
                    </div>

                    <h6 class="mb-3">Items</h6>
                    <div class="table-responsive">
                        <table class="table table-bordered" id="pr-items-table">
                            <thead>
                                <tr>
                                    <th>Product Name</th>
                                    <th>Product Link</th>
                                    <th>Quantity</th>
                                    <th>Unit</th>
                                    <th>Estimated Unit Price</th>
                                    <th>Specification</th>
                                    <th class="text-end">Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                @php $items = old('items', [['product_name' => '', 'product_id' => '', 'quantity' => '', 'unit' => '', 'estimated_unit_price' => '', 'specification' => '']]); @endphp
                                @foreach($items as $index => $item)
                                    <tr>
                                        <td><input type="text" name="items[{{ $index }}][product_name]" class="form-control" value="{{ $item['product_name'] ?? '' }}" required></td>
                                        <td>
                                            <select name="items[{{ $index }}][product_id]" class="form-select">
                                                <option value="">Optional product</option>
                                                @foreach($products as $product)
                                                    <option value="{{ $product->id }}" @selected(($item['product_id'] ?? '') == $product->id)>{{ $product->name }}</option>
                                                @endforeach
                                            </select>
                                        </td>
                                        <td><input type="number" step="0.01" min="0.001" name="items[{{ $index }}][quantity]" class="form-control" value="{{ $item['quantity'] ?? '' }}" required></td>
                                        <td><input type="text" name="items[{{ $index }}][unit]" class="form-control" value="{{ $item['unit'] ?? '' }}"></td>
                                        <td><input type="number" step="0.01" min="0" name="items[{{ $index }}][estimated_unit_price]" class="form-control" value="{{ $item['estimated_unit_price'] ?? '' }}"></td>
                                        <td><input type="text" name="items[{{ $index }}][specification]" class="form-control" value="{{ $item['specification'] ?? '' }}"></td>
                                        <td class="text-end"><button type="button" class="btn btn-sm btn-outline-danger remove-pr-item">Remove</button></td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    <button type="button" class="btn btn-outline-secondary btn-sm mb-3" id="add-pr-item">Add Item</button>

                    <div>
                        <button type="submit" class="btn btn-primary">Submit Requisition</button>
                        <a href="{{ route('purchase-requisitions.index') }}" class="btn btn-outline-secondary">Cancel</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const products = @json($products->map(fn($product) => ['id' => $product->id, 'name' => $product->name])->values());
    const tbody = document.querySelector('#pr-items-table tbody');
    let rowIndex = tbody.querySelectorAll('tr').length;

    document.getElementById('add-pr-item').addEventListener('click', function () {
        const options = ['<option value="">Optional product</option>'].concat(products.map(product => `<option value="${product.id}">${product.name}</option>`)).join('');
        const row = document.createElement('tr');
        row.innerHTML = `
            <td><input type="text" name="items[${rowIndex}][product_name]" class="form-control" required></td>
            <td><select name="items[${rowIndex}][product_id]" class="form-select">${options}</select></td>
            <td><input type="number" step="0.01" min="0.001" name="items[${rowIndex}][quantity]" class="form-control" required></td>
            <td><input type="text" name="items[${rowIndex}][unit]" class="form-control"></td>
            <td><input type="number" step="0.01" min="0" name="items[${rowIndex}][estimated_unit_price]" class="form-control"></td>
            <td><input type="text" name="items[${rowIndex}][specification]" class="form-control"></td>
            <td class="text-end"><button type="button" class="btn btn-sm btn-outline-danger remove-pr-item">Remove</button></td>
        `;
        tbody.appendChild(row);
        rowIndex++;
    });

    tbody.addEventListener('click', function (event) {
        if (event.target.classList.contains('remove-pr-item') && tbody.querySelectorAll('tr').length > 1) {
            event.target.closest('tr').remove();
        }
    });
});
</script>
@endsection
