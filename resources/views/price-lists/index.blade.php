@extends('layout.mainlayout')

@section('content')
<div class="page-wrapper">
    <div class="content container-fluid">
        <div class="page-header">
            <div class="content-page-header d-flex justify-content-between align-items-center flex-wrap gap-2">
                <div>
                    <h5 class="mb-1">Price Lists</h5>
                    <p class="text-muted mb-0">Customer pricing lists and product-specific rate cards.</p>
                </div>
                <a href="{{ route('price-lists.create') }}" class="btn btn-primary">New Price List</a>
            </div>
        </div>

        <div class="card">
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead>
                            <tr>
                                <th>Name</th>
                                <th>Currency</th>
                                <th>Discount</th>
                                <th>Items</th>
                                <th>Validity</th>
                                <th>Status</th>
                                <th class="text-end">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($priceLists as $priceList)
                                <tr>
                                    <td>{{ $priceList->name }}</td>
                                    <td>{{ $priceList->currency }}</td>
                                    <td>
                                        @if($priceList->discount_type)
                                            {{ $priceList->discount_type === 'percentage' ? number_format((float) $priceList->discount_value, 2) . '%' : number_format((float) $priceList->discount_value, 2) }}
                                        @else
                                            N/A
                                        @endif
                                    </td>
                                    <td>{{ number_format((int) ($priceList->items_count ?? $priceList->items?->count() ?? 0)) }}</td>
                                    <td>
                                        {{ $priceList->valid_from ? $priceList->valid_from->format('d M Y') : 'N/A' }}
                                        -
                                        {{ $priceList->valid_to ? $priceList->valid_to->format('d M Y') : 'N/A' }}
                                    </td>
                                    <td>{!! $priceList->is_active ? '<span class="badge bg-success">Active</span>' : '<span class="badge bg-secondary">Inactive</span>' !!}</td>
                                    <td class="text-end">
                                        <a href="{{ route('price-lists.show', $priceList) }}" class="btn btn-sm btn-outline-primary">View</a>
                                        <a href="{{ route('price-lists.edit', $priceList) }}" class="btn btn-sm btn-outline-secondary">Edit</a>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="7" class="text-center text-muted py-4">No price lists found yet.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
            @if($priceLists->hasPages())
                <div class="card-footer">{{ $priceLists->links() }}</div>
            @endif
        </div>
    </div>
</div>
@endsection
