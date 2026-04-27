@extends('layout.mainlayout')

@section('content')
<div class="page-wrapper">
    <div class="content container-fluid">
        <div class="page-header">
            <div class="content-page-header d-flex justify-content-between align-items-center flex-wrap gap-2">
                <div>
                    <h5 class="mb-1">RFQ {{ $rfq->rfq_number }}</h5>
                    <p class="text-muted mb-0">Review quotation request details, items, and supplier responses.</p>
                </div>
                <div class="d-flex gap-2">
                    @if($rfq->status === 'draft')
                        <a href="{{ route('rfq.edit', $rfq) }}" class="btn btn-outline-primary">Edit</a>
                        <form action="{{ route('rfq.send', $rfq) }}" method="POST">
                            @csrf
                            <button type="submit" class="btn btn-primary">Send to Suppliers</button>
                        </form>
                    @endif
                    <a href="{{ route('rfq.index') }}" class="btn btn-outline-secondary">Back</a>
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-xl-4">
                <div class="card">
                    <div class="card-body">
                        <table class="table table-sm mb-0">
                            <tr><th>Title</th><td>{{ $rfq->title }}</td></tr>
                            <tr><th>Required Date</th><td>{{ $rfq->required_date ? $rfq->required_date->format('d M Y') : 'N/A' }}</td></tr>
                            <tr><th>Status</th><td>{{ ucfirst($rfq->status ?? 'draft') }}</td></tr>
                            <tr><th>Notes</th><td>{{ $rfq->notes ?: 'N/A' }}</td></tr>
                        </table>
                    </div>
                </div>

                <div class="card">
                    <div class="card-header"><h6 class="card-title mb-0">Add Supplier</h6></div>
                    <div class="card-body">
                        <form method="POST" action="{{ route('rfq.suppliers.add', $rfq) }}">
                            @csrf
                            <div class="mb-3">
                                <label class="form-label">Supplier</label>
                                <select name="supplier_id" class="form-select" required>
                                    <option value="">Select supplier</option>
                                    @foreach($suppliers ?? [] as $supplier)
                                        <option value="{{ $supplier->id }}">{{ $supplier->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Supplier Name</label>
                                <input type="text" name="supplier_name" class="form-control" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Supplier Email</label>
                                <input type="email" name="supplier_email" class="form-control">
                            </div>
                            <button type="submit" class="btn btn-outline-primary">Add Supplier</button>
                        </form>
                    </div>
                </div>
            </div>

            <div class="col-xl-8">
                <div class="card mb-4">
                    <div class="card-header"><h6 class="card-title mb-0">Items</h6></div>
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-hover mb-0">
                                <thead>
                                    <tr>
                                        <th>Product</th>
                                        <th>Quantity</th>
                                        <th>Unit</th>
                                        <th>Specifications</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($rfq->items as $item)
                                        <tr>
                                            <td>{{ $item->product_name ?: ($item->product?->name ?? 'N/A') }}</td>
                                            <td>{{ number_format((float) $item->quantity, 2) }}</td>
                                            <td>{{ $item->unit ?: 'N/A' }}</td>
                                            <td>{{ $item->specifications ?: 'N/A' }}</td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="4" class="text-center text-muted py-4">No RFQ items added yet.</td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <div class="card">
                    <div class="card-header"><h6 class="card-title mb-0">Suppliers and Quotes</h6></div>
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-hover mb-0">
                                <thead>
                                    <tr>
                                        <th>Supplier</th>
                                        <th>Status</th>
                                        <th>Quoted Amount</th>
                                        <th>Responded</th>
                                        <th class="text-end">Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($rfq->rfqSuppliers as $rfqSupplier)
                                        <tr>
                                            <td>{{ $rfqSupplier->supplier_name ?: ($rfqSupplier->supplier?->name ?? 'N/A') }}</td>
                                            <td>{{ ucfirst($rfqSupplier->status ?? 'pending') }}</td>
                                            <td>{{ $rfqSupplier->total_quoted_amount !== null ? number_format((float) $rfqSupplier->total_quoted_amount, 2) : 'N/A' }}</td>
                                            <td>{{ $rfqSupplier->responded_at ? $rfqSupplier->responded_at->format('d M Y H:i') : 'N/A' }}</td>
                                            <td class="text-end">
                                                <form method="POST" action="{{ route('rfq.suppliers.remove', [$rfq, $rfqSupplier]) }}" class="d-inline">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="btn btn-sm btn-outline-danger">Remove</button>
                                                </form>
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="5" class="text-center text-muted py-4">No suppliers attached yet.</td>
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
