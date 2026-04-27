@extends('layout.app')

@section('title', 'New Purchase Requisition')

@section('content')
<div class="content container-fluid">
    <div class="page-header">
        <div class="row align-items-center">
            <div class="col">
                <h3 class="page-title">New Purchase Requisition</h3>
                <ul class="breadcrumb">
                    <li class="breadcrumb-item"><a href="{{ route('home') }}">Dashboard</a></li>
                    <li class="breadcrumb-item"><a href="{{ route('purchase-requisitions.index') }}">Requisitions</a></li>
                    <li class="breadcrumb-item active">New</li>
                </ul>
            </div>
        </div>
    </div>

    <div class="row justify-content-center">
        <div class="col-lg-10">
            <div class="card">
                <div class="card-body">
                    @if($errors->any())
                        <div class="alert alert-danger">
                            <ul class="mb-0">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
                        </div>
                    @endif

                    <form action="{{ route('purchase-requisitions.store') }}" method="POST" id="prForm">
                        @csrf
                        {{-- Header Fields --}}
                        <div class="row g-3 mb-4">
                            <div class="col-md-4">
                                <label class="form-label fw-semibold">Required By Date <span class="text-danger">*</span></label>
                                <input type="date" name="required_date" class="form-control @error('required_date') is-invalid @enderror"
                                       value="{{ old('required_date') }}" required>
                                @error('required_date')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                            <div class="col-md-4">
                                <label class="form-label fw-semibold">Priority <span class="text-danger">*</span></label>
                                <select name="priority" class="form-select @error('priority') is-invalid @enderror" required>
                                    <option value="low" @selected(old('priority') === 'low')>Low</option>
                                    <option value="normal" @selected(old('priority','normal') === 'normal')>Normal</option>
                                    <option value="urgent" @selected(old('priority') === 'urgent')>Urgent</option>
                                    <option value="critical" @selected(old('priority') === 'critical')>Critical</option>
                                </select>
                                @error('priority')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                            <div class="col-md-4">
                                <label class="form-label fw-semibold">Department</label>
                                <select name="department_id" class="form-select @error('department_id') is-invalid @enderror">
                                    <option value="">-- None --</option>
                                    @foreach($departments as $dept)
                                        <option value="{{ $dept->id }}" @selected(old('department_id') == $dept->id)>{{ $dept->name }}</option>
                                    @endforeach
                                </select>
                                @error('department_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-semibold">Cost Center</label>
                                <select name="cost_center_id" class="form-select @error('cost_center_id') is-invalid @enderror">
                                    <option value="">-- None --</option>
                                    @foreach($costCenters as $cc)
                                        <option value="{{ $cc->id }}" @selected(old('cost_center_id') == $cc->id)>{{ $cc->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-semibold">Justification</label>
                                <input type="text" name="justification" class="form-control" value="{{ old('justification') }}" placeholder="Brief reason for this request">
                            </div>
                        </div>

                        {{-- Items Table --}}
                        <h6 class="fw-bold mb-2">Items <span class="text-danger">*</span></h6>
                        <div class="table-responsive">
                            <table class="table table-bordered" id="itemsTable">
                                <thead class="table-light">
                                    <tr>
                                        <th width="25%">Product Name <span class="text-danger">*</span></th>
                                        <th width="20%">Product (link)</th>
                                        <th width="10%">Qty <span class="text-danger">*</span></th>
                                        <th width="10%">Unit</th>
                                        <th width="15%">Est. Unit Price</th>
                                        <th width="15%">Specification</th>
                                        <th width="5%"></th>
                                    </tr>
                                </thead>
                                <tbody id="itemsBody">
                                    @php $items = old('items', [['product_name'=>'','product_id'=>'','quantity'=>'','unit'=>'','estimated_unit_price'=>'','specification'=>'']]) @endphp
                                    @foreach($items as $i => $item)
                                    <tr class="item-row">
                                        <td><input type="text" name="items[{{ $i }}][product_name]" class="form-control form-control-sm" value="{{ $item['product_name'] ?? '' }}" required></td>
                                        <td>
                                            <select name="items[{{ $i }}][product_id]" class="form-select form-select-sm">
                                                <option value="">-- Optional --</option>
                                                @foreach($products as $p)
                                                    <option value="{{ $p->id }}" @selected(($item['product_id'] ?? '') == $p->id)>{{ $p->name }}</option>
                                                @endforeach
                                            </select>
                                        </td>
                                        <td><input type="number" name="items[{{ $i }}][quantity]" class="form-control form-control-sm" value="{{ $item['quantity'] ?? '' }}" min="0.01" step="0.01" required></td>
                                        <td><input type="text" name="items[{{ $i }}][unit]" class="form-control form-control-sm" value="{{ $item['unit'] ?? '' }}" placeholder="pcs"></td>
                                        <td><input type="number" name="items[{{ $i }}][estimated_unit_price]" class="form-control form-control-sm" value="{{ $item['estimated_unit_price'] ?? '' }}" step="0.01" min="0"></td>
                                        <td><input type="text" name="items[{{ $i }}][specification]" class="form-control form-control-sm" value="{{ $item['specification'] ?? '' }}"></td>
                                        <td class="text-center"><button type="button" class="btn btn-sm btn-outline-danger remove-row" title="Remove">&times;</button></td>
                                    </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>

                        <button type="button" id="addRow" class="btn btn-sm btn-outline-secondary mb-4">
                            <i class="fe fe-plus me-1"></i> Add Item
                        </button>

                        <div class="d-flex gap-2">
                            <button type="submit" class="btn btn-primary">Submit Requisition</button>
                            <a href="{{ route('purchase-requisitions.index') }}" class="btn btn-outline-secondary">Cancel</a>
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

    function buildOptions() {
        let opts = '<option value="">-- Optional --</option>';
        productsJson.forEach(p => { opts += `<option value="${p.id}">${p.name}</option>`; });
        return opts;
    }

    document.getElementById('addRow').addEventListener('click', function () {
        const tbody = document.getElementById('itemsBody');
        const tr = document.createElement('tr');
        tr.className = 'item-row';
        tr.innerHTML = `
            <td><input type="text" name="items[${rowIndex}][product_name]" class="form-control form-control-sm" required></td>
            <td><select name="items[${rowIndex}][product_id]" class="form-select form-select-sm">${buildOptions()}</select></td>
            <td><input type="number" name="items[${rowIndex}][quantity]" class="form-control form-control-sm" min="0.01" step="0.01" required></td>
            <td><input type="text" name="items[${rowIndex}][unit]" class="form-control form-control-sm" placeholder="pcs"></td>
            <td><input type="number" name="items[${rowIndex}][estimated_unit_price]" class="form-control form-control-sm" step="0.01" min="0"></td>
            <td><input type="text" name="items[${rowIndex}][specification]" class="form-control form-control-sm"></td>
            <td class="text-center"><button type="button" class="btn btn-sm btn-outline-danger remove-row">&times;</button></td>
        `;
        tbody.appendChild(tr);
        rowIndex++;
    });

    document.getElementById('itemsBody').addEventListener('click', function (e) {
        if (e.target.classList.contains('remove-row')) {
            const rows = document.querySelectorAll('.item-row');
            if (rows.length > 1) e.target.closest('tr').remove();
        }
    });
})();
</script>
@endpush
@endsection
