@extends('layout.mainlayout')

@section('content')
<div class="page-wrapper">
    <div class="content container-fluid">
        <div class="page-header">
            <div class="content-page-header d-flex justify-content-between align-items-center flex-wrap gap-2">
                <div>
                    <h5 class="mb-1">Compare Quotes for {{ $rfq->rfq_number }}</h5>
                    <p class="text-muted mb-0">Review supplier responses and select a winner safely.</p>
                </div>
                <a href="{{ route('rfq.show', $rfq) }}" class="btn btn-outline-secondary">Back to RFQ</a>
            </div>
        </div>

        <div class="card">
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead>
                            <tr>
                                <th>Supplier</th>
                                <th>Status</th>
                                <th>Quoted Amount</th>
                                <th>Currency</th>
                                <th>Notes</th>
                                <th class="text-end">Winner</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($rfq->rfqSuppliers as $rfqSupplier)
                                <tr>
                                    <td>{{ $rfqSupplier->supplier_name ?: ($rfqSupplier->supplier?->name ?? 'N/A') }}</td>
                                    <td>{{ ucfirst($rfqSupplier->status ?? 'pending') }}</td>
                                    <td>{{ $rfqSupplier->total_quoted_amount !== null ? number_format((float) $rfqSupplier->total_quoted_amount, 2) : 'N/A' }}</td>
                                    <td>{{ $rfqSupplier->currency ?: 'N/A' }}</td>
                                    <td>{{ $rfqSupplier->notes ?: 'N/A' }}</td>
                                    <td class="text-end">
                                        @if(($rfqSupplier->is_winner ?? false) || ($rfqSupplier->is_selected ?? false))
                                            <span class="badge bg-success">Selected</span>
                                        @elseif($rfqSupplier->total_quoted_amount !== null)
                                            <form method="POST" action="{{ route('rfq.winner', $rfq) }}">
                                                @csrf
                                                <input type="hidden" name="rfq_supplier_id" value="{{ $rfqSupplier->id }}">
                                                <button type="submit" class="btn btn-sm btn-outline-success">Select Winner</button>
                                            </form>
                                        @else
                                            <span class="text-muted">Awaiting quote</span>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="text-center text-muted py-4">No supplier quotes available yet.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
