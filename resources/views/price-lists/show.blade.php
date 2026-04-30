@extends('layout.mainlayout')

@section('content')
<div class="page-wrapper">
    <div class="content container-fluid">
        <div class="page-header">
            <div class="content-page-header d-flex justify-content-between align-items-center flex-wrap gap-2">
                <div>
                    <h5 class="mb-1">{{ $priceList->name }}</h5>
                    <p class="text-muted mb-0">Price list details and item pricing.</p>
                </div>
                <div class="d-flex gap-2">
                    <a href="{{ route('price-lists.edit', $priceList) }}" class="btn btn-outline-primary">Edit</a>
                    <a href="{{ route('price-lists.index') }}" class="btn btn-outline-secondary">Back</a>
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-xl-4">
                <div class="card">
                    <div class="card-body">
                        <table class="table table-sm mb-0">
                            <tr><th>Currency</th><td>{{ $priceList->currency }}</td></tr>
                            <tr><th>Discount</th><td>{{ $formData['discount_type'] ? ucfirst($formData['discount_type']) . ' ' . number_format((float) $formData['discount_value'], 2) : 'N/A' }}</td></tr>
                            <tr><th>Valid From</th><td>{{ $priceList->valid_from ? $priceList->valid_from->format('d M Y') : 'N/A' }}</td></tr>
                            <tr><th>Valid To</th><td>{{ $priceList->valid_to ? $priceList->valid_to->format('d M Y') : 'N/A' }}</td></tr>
                            <tr><th>Default</th><td>{{ ($priceList->is_default ?? false) ? 'Yes' : 'No' }}</td></tr>
                            <tr><th>Status</th><td>{{ $priceList->is_active ? 'Active' : 'Inactive' }}</td></tr>
                            <tr><th>Notes</th><td>{{ $formData['notes'] ?: 'N/A' }}</td></tr>
                        </table>
                    </div>
                </div>
            </div>
            <div class="col-xl-8">
                <div class="card">
                    <div class="card-header"><h6 class="card-title mb-0">Items</h6></div>
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-hover mb-0">
                                <thead>
                                    <tr>
                                        <th>Product</th>
                                        <th>Price</th>
                                        <th>Min Qty</th>
                                        <th>Currency</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($priceList->items as $item)
                                        <tr>
                                            <td>{{ $item->product?->name ?? 'N/A' }}</td>
                                            <td>{{ number_format((float) ($item->price ?? $item->unit_price), 2) }}</td>
                                            <td>{{ number_format((float) $item->min_quantity, 2) }}</td>
                                            <td>{{ $item->currency ?? $priceList->currency }}</td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="4" class="text-center text-muted py-4">No items have been added to this price list.</td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
