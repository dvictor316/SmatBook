@extends('layout.app')

@section('title', 'New Goods Received Note')

@section('content')
<div class="content container-fluid">
    <div class="page-header">
        <div class="row align-items-center">
            <div class="col">
                <h3 class="page-title">New Goods Received Note (GRN)</h3>
                <ul class="breadcrumb">
                    <li class="breadcrumb-item"><a href="{{ route('home') }}">Dashboard</a></li>
                    <li class="breadcrumb-item"><a href="{{ route('grn.index') }}">GRNs</a></li>
                    <li class="breadcrumb-item active">New</li>
                </ul>
            </div>
        </div>
    </div>

    <div class="row justify-content-center">
        <div class="col-lg-11">
            <div class="card">
                <div class="card-body">
                    @if($errors->any())
                        <div class="alert alert-danger">
                            <ul class="mb-0">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
                        </div>
                    @endif

                    <form action="{{ route('grn.store') }}" method="POST">
                        @csrf
                        {{-- Header --}}
                        <div class="row g-3 mb-4">
                            <div class="col-md-4">
                                <label class="form-label fw-semibold">Supplier <span class="text-danger">*</span></label>
                                <select name="supplier_id" class="form-select @error('supplier_id') is-invalid @enderror" required>
                                    <option value="">-- Select Supplier --</option>
                                    @foreach($suppliers as $s)
                                        <option value="{{ $s->id }}" @selected(old('supplier_id') == $s->id)>{{ $s->name }}</option>
                                    @endforeach
                                </select>
                                @error('supplier_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                            <div class="col-md-4">
                                <label class="form-label fw-semibold">Purchase Order # (optional)</label>
                                <input type="text" name="purchase_order_id" class="form-control" value="{{ old('purchase_order_id') }}" placeholder="PO reference">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label fw-semibold">Received Date <span class="text-danger">*</span></label>
                                <input type="date" name="received_date" class="form-control @error('received_date') is-invalid @enderror"
                                       value="{{ old('received_date', now()->toDateString()) }}" required>
                                @error('received_date')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                            <div class="col-12">
                                <label class="form-label fw-semibold">Notes</label>
                                <textarea name="notes" class="form-control" rows="2">{{ old('notes') }}</textarea>
                            </div>
                        </div>

                        {{-- Items --}}
                        <h6 class="fw-bold mb-2">Received Items <span class="text-danger">*</span></h6>
                        <div class="table-responsive">
                            <table class="table table-bordered" id="itemsTable">
                                <thead class="table-light">
                                    <tr>
                                        <th>Product <span class="text-danger">*</span></th>
                                        <th>Product Name</th>
                                        <th>Ordered Qty</th>
                                        <th>Received Qty <span class="text-danger">*</span></th>
                                        <th>Unit Cost</th>
                                        <th>Lot #</th>
                                        <th>Serial #</th>
                                        <th>Expiry Date</th>
                                        <th></th>
                                    </tr>
                                </thead>
                                <tbody id="itemsBody">
                                    @php $grnItems = old('items', [['product_id'=>'','product_name'=>'','ordered_quantity'=>'','received_quantity'=>'','unit_cost'=>'','lot_number'=>'','serial_number'=>'','expiry_date'=>'']]) @endphp
                                    @foreach($grnItems as $i => $item)
                                    <tr class="item-row">
                                        <td>
                                            <select name="items[{{ $i }}][product_id]" class="form-select form-select-sm product-select" required>
                                                <option value="">-- Select --</option>
                                                @foreach($products as $p)
                                                    <option value="{{ $p->id }}" data-name="{{ $p->name }}" @selected(($item['product_id'] ?? '') == $p->id)>{{ $p->name }}</option>
                                                @endforeach
                                            </select>
                                        </td>
                                        <td><input type="text" name="items[{{ $i }}][product_name]" class="form-control form-control-sm product-name" value="{{ $item['product_name'] ?? '' }}" placeholder="Or type name"></td>
                                        <td><input type="number" name="items[{{ $i }}][ordered_quantity]" class="form-control form-control-sm" value="{{ $item['ordered_quantity'] ?? '' }}" step="0.01" min="0"></td>
                                        <td><input type="number" name="items[{{ $i }}][received_quantity]" class="form-control form-control-sm" value="{{ $item['received_quantity'] ?? '' }}" step="0.01" min="0.01" required></td>
                                        <td><input type="number" name="items[{{ $i }}][unit_cost]" class="form-control form-control-sm" value="{{ $item['unit_cost'] ?? '' }}" step="0.01" min="0"></td>
                                        <td><input type="text" name="items[{{ $i }}][lot_number]" class="form-control form-control-sm" value="{{ $item['lot_number'] ?? '' }}"></td>
                                        <td><input type="text" name="items[{{ $i }}][serial_number]" class="form-control form-control-sm" value="{{ $item['serial_number'] ?? '' }}"></td>
                                        <td><input type="date" name="items[{{ $i }}][expiry_date]" class="form-control form-control-sm" value="{{ $item['expiry_date'] ?? '' }}"></td>
                                        <td class="text-center"><button type="button" class="btn btn-sm btn-outline-danger remove-row">&times;</button></td>
                                    </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>

                        <button type="button" id="addRow" class="btn btn-sm btn-outline-secondary mb-4">
                            <i class="fe fe-plus me-1"></i> Add Item
                        </button>

                        <div class="d-flex gap-2">
                            <button type="submit" class="btn btn-primary">Save GRN</button>
                            <a href="{{ route('grn.index') }}" class="btn btn-outline-secondary">Cancel</a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
(function () {
    const productsJson = @json($products->map(fn($p) => ['id' => $p->id, 'name' => $p->name]));
    let rowIndex = {{ count(old('items', [[]])) }};

    function buildProductOptions(selectedId = '') {
        let opts = '<option value="">-- Select --</option>';
        productsJson.forEach(p => {
            opts += `<option value="${p.id}" data-name="${p.name}" ${p.id == selectedId ? 'selected' : ''}>${p.name}</option>`;
        });
        return opts;
    }

    function buildRow(idx) {
        return `<tr class="item-row">
            <td><select name="items[${idx}][product_id]" class="form-select form-select-sm product-select" required>${buildProductOptions()}</select></td>
            <td><input type="text" name="items[${idx}][product_name]" class="form-control form-control-sm product-name" placeholder="Or type name"></td>
            <td><input type="number" name="items[${idx}][ordered_quantity]" class="form-control form-control-sm" step="0.01" min="0"></td>
            <td><input type="number" name="items[${idx}][received_quantity]" class="form-control form-control-sm" step="0.01" min="0.01" required></td>
            <td><input type="number" name="items[${idx}][unit_cost]" class="form-control form-control-sm" step="0.01" min="0"></td>
            <td><input type="text" name="items[${idx}][lot_number]" class="form-control form-control-sm"></td>
            <td><input type="text" name="items[${idx}][serial_number]" class="form-control form-control-sm"></td>
            <td><input type="date" name="items[${idx}][expiry_date]" class="form-control form-control-sm"></td>
            <td class="text-center"><button type="button" class="btn btn-sm btn-outline-danger remove-row">&times;</button></td>
        </tr>`;
    }

    document.getElementById('addRow').addEventListener('click', function () {
        document.getElementById('itemsBody').insertAdjacentHTML('beforeend', buildRow(rowIndex++));
    });

    document.getElementById('itemsBody').addEventListener('click', function (e) {
        if (e.target.classList.contains('remove-row')) {
            const rows = document.querySelectorAll('.item-row');
            if (rows.length > 1) e.target.closest('tr').remove();
        }
    });

    // Auto-fill product name when product is selected
    document.getElementById('itemsBody').addEventListener('change', function (e) {
        if (e.target.classList.contains('product-select')) {
            const row = e.target.closest('tr');
            const nameInput = row.querySelector('.product-name');
            const selected = e.target.options[e.target.selectedIndex];
            if (selected && selected.dataset.name && !nameInput.value) {
                nameInput.value = selected.dataset.name;
            }
        }
    });
})();
</script>
@endpush
@endsection
