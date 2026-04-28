@extends('layout.mainlayout')

@section('content')
<div class="page-wrapper">
    <div class="content container-fluid">
        <div class="page-header">
            <div class="content-page-header d-flex justify-content-between align-items-center flex-wrap gap-2">
                <div>
                    <h5 class="mb-1">Barcode Management</h5>
                    <p class="text-muted mb-0">Manage product barcodes within the current company workspace.</p>
                </div>
            </div>
        </div>

        <div class="row g-3">
            <div class="col-xl-4 col-lg-5">
                <div class="card">
                    <div class="card-header">
                        <h6 class="card-title mb-0">Add Barcode</h6>
                    </div>
                    <div class="card-body">
                        <form method="POST" action="{{ route('inventory.barcodes.store') }}">
                            @csrf
                            <div class="mb-3">
                                <label class="form-label">Product</label>
                                <select name="product_id" class="form-select" required>
                                    <option value="">Select product</option>
                                    @foreach($products as $product)
                                        <option value="{{ $product->id }}" @selected(old('product_id') == $product->id)>
                                            {{ $product->name }}{{ $product->sku ? ' - ' . $product->sku : '' }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Barcode</label>
                                <input type="text" name="barcode" class="form-control" value="{{ old('barcode') }}" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Barcode Type</label>
                                <select name="barcode_type" class="form-select">
                                    @foreach(['EAN13', 'EAN8', 'UPC', 'QR', 'CODE128', 'CODE39'] as $type)
                                        <option value="{{ $type }}" @selected(old('barcode_type', 'EAN13') === $type)>{{ $type }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="form-check mb-3">
                                <input class="form-check-input" type="checkbox" name="is_primary" value="1" id="is_primary" @checked(old('is_primary'))>
                                <label class="form-check-label" for="is_primary">Set as primary barcode</label>
                            </div>
                            <button type="submit" class="btn btn-primary w-100 w-sm-auto">Save Barcode</button>
                        </form>
                    </div>
                </div>
            </div>

            <div class="col-xl-8 col-lg-7">
                <div class="card">
                    <div class="card-header">
                        <h6 class="card-title mb-0">Saved Barcodes</h6>
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-hover mb-0">
                                <thead>
                                    <tr>
                                        <th>Barcode</th>
                                        <th>Product</th>
                                        <th>Type</th>
                                        <th>Primary</th>
                                        <th class="text-end">Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($barcodes as $barcode)
                                        <tr>
                                            <td>{{ $barcode->barcode }}</td>
                                            <td>{{ $barcode->product?->name ?? 'N/A' }}</td>
                                            <td>{{ $barcode->barcode_type ?? 'EAN13' }}</td>
                                            <td>{!! $barcode->is_primary ? '<span class="badge bg-success">Yes</span>' : '<span class="badge bg-light text-dark">No</span>' !!}</td>
                                            <td class="text-end">
                                                <form action="{{ route('inventory.barcodes.destroy', $barcode) }}" method="POST" class="d-inline" onsubmit="return confirm('Remove this barcode?')">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button class="btn btn-sm btn-outline-danger">Delete</button>
                                                </form>
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="5" class="text-center text-muted py-4">No barcodes added yet.</td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                    @if($barcodes->hasPages())
                        <div class="card-footer">{{ $barcodes->links() }}</div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
