@extends('layout.app')

@section('title', 'Create RFQ')

@section('content')
<div class="content container-fluid">
    <div class="page-header">
        <div class="row align-items-center">
            <div class="col">
                <h3 class="page-title">New Request for Quotation</h3>
                <ul class="breadcrumb">
                    <li class="breadcrumb-item"><a href="{{ route('home') }}">Dashboard</a></li>
                    <li class="breadcrumb-item"><a href="{{ route('rfq.index') }}">RFQs</a></li>
                    <li class="breadcrumb-item active">Create</li>
                </ul>
            </div>
        </div>
    </div>

    <form action="{{ route('rfq.store') }}" method="POST">
        @csrf
        <div class="row">
            <div class="col-md-8">
                <div class="card mb-3">
                    <div class="card-header"><h5 class="card-title mb-0">RFQ Details</h5></div>
                    <div class="card-body">
                        <div class="mb-3">
                            <label class="form-label">Title <span class="text-danger">*</span></label>
                            <input type="text" name="title" class="form-control @error('title') is-invalid @enderror"
                                   value="{{ old('title') }}" required>
                            @error('title')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Description</label>
                            <textarea name="description" class="form-control" rows="3">{{ old('description') }}</textarea>
                        </div>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Required Date</label>
                                <input type="date" name="required_date" class="form-control" value="{{ old('required_date') }}">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Closing Date</label>
                                <input type="date" name="closing_date" class="form-control" value="{{ old('closing_date') }}">
                            </div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Notes</label>
                            <textarea name="notes" class="form-control" rows="2">{{ old('notes') }}</textarea>
                        </div>
                    </div>
                </div>

                {{-- Items Section --}}
                <div class="card mb-3">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h5 class="card-title mb-0">Items</h5>
                        <button type="button" class="btn btn-outline-primary btn-sm" id="addItem">
                            <i class="fe fe-plus me-1"></i> Add Item
                        </button>
                    </div>
                    <div class="card-body p-0">
                        <table class="table mb-0" id="itemsTable">
                            <thead class="table-light">
                                <tr>
                                    <th>Product</th><th>Description</th><th>Qty</th><th>Unit</th><th></th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr class="item-row">
                                    <td>
                                        <select name="items[0][product_id]" class="form-select form-select-sm">
                                            <option value="">Select product…</option>
                                            @foreach($products ?? [] as $p)
                                                <option value="{{ $p->id }}">{{ $p->name }}</option>
                                            @endforeach
                                        </select>
                                    </td>
                                    <td><input type="text" name="items[0][description]" class="form-control form-control-sm"></td>
                                    <td><input type="number" name="items[0][quantity]" class="form-control form-control-sm" value="1" min="0.01" step="0.01" style="width:80px"></td>
                                    <td><input type="text" name="items[0][unit]" class="form-control form-control-sm" placeholder="pcs" style="width:70px"></td>
                                    <td><button type="button" class="btn btn-sm btn-outline-danger remove-item">×</button></td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <div class="col-md-4">
                <div class="card mb-3">
                    <div class="card-header"><h5 class="card-title mb-0">Suppliers</h5></div>
                    <div class="card-body">
                        <p class="text-muted small">Select suppliers to send this RFQ to:</p>
                        @foreach($suppliers ?? [] as $s)
                            <div class="form-check mb-1">
                                <input class="form-check-input" type="checkbox" name="supplier_ids[]" value="{{ $s->id }}"
                                       id="sup_{{ $s->id }}" {{ in_array($s->id, old('supplier_ids', [])) ? 'checked' : '' }}>
                                <label class="form-check-label" for="sup_{{ $s->id }}">{{ $s->name }}</label>
                            </div>
                        @endforeach
                    </div>
                </div>

                <div class="d-grid gap-2">
                    <button type="submit" name="action" value="draft" class="btn btn-outline-secondary">Save as Draft</button>
                    <button type="submit" name="action" value="send" class="btn btn-primary">Save & Send to Suppliers</button>
                    <a href="{{ route('rfq.index') }}" class="btn btn-link text-muted">Cancel</a>
                </div>
            </div>
        </div>
    </form>
</div>

@push('scripts')
<script>
let rowIndex = 1;
document.getElementById('addItem').addEventListener('click', function () {
    const tbody = document.querySelector('#itemsTable tbody');
    const row = tbody.querySelector('.item-row').cloneNode(true);
    row.querySelectorAll('input,select').forEach(el => {
        el.name = el.name.replace(/\[\d+\]/, '[' + rowIndex + ']');
        if (el.type !== 'checkbox') el.value = el.tagName === 'INPUT' && el.name.includes('quantity') ? '1' : '';
    });
    tbody.appendChild(row);
    rowIndex++;
});
document.getElementById('itemsTable').addEventListener('click', function (e) {
    if (e.target.classList.contains('remove-item')) {
        const rows = document.querySelectorAll('.item-row');
        if (rows.length > 1) e.target.closest('tr').remove();
    }
});
</script>
@endpush
@endsection
