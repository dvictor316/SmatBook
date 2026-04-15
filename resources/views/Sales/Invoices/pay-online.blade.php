@extends('layout.mainlayout')

@section('page-title', 'SalesInvoicespay Online')

@section('content')
<div class="container-fluid py-4">
    <div class="card border-0 shadow-sm">
        <div class="card-body">
            @if($invoice)
                <h4 class="mb-2">Pay Invoice: <span class="text-primary">{{ $invoice->invoice_no ?? $invoice->id }}</span></h4>
                <p class="mb-1">Customer: <strong>{{ $invoice->customer->name ?? $invoice->customer_name ?? 'N/A' }}</strong></p>
                <p class="mb-1">Total Amount: <strong>{{ number_format($invoice->total ?? $invoice->subtotal ?? 0, 2) }}</strong></p>
                <p class="mb-3">Status: <span class="badge bg-info">{{ $invoice->payment_status ?? $invoice->status ?? 'N/A' }}</span></p>

                <h6 class="mt-4">Items</h6>
                <div class="table-responsive mb-3">
                    <table class="table table-bordered">
                        <thead>
                            <tr>
                                <th>Product</th>
                                <th>Qty</th>
                                <th>Unit Price</th>
                                <th>Total</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($invoice->items as $item)
                                <tr>
                                    <td>{{ $item->product->name ?? $item->product_name ?? 'N/A' }}</td>
                                    <td>{{ $item->qty }}</td>
                                    <td>{{ number_format($item->unit_price, 2) }}</td>
                                    <td>{{ number_format($item->qty * $item->unit_price, 2) }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <div class="alert alert-info">Payment integration coming soon. Please contact admin to complete payment.</div>
            @else
                <div class="alert alert-danger mb-3">Invoice not found or invalid link.</div>
            @endif
            <div class="mt-3">
                <a href="{{ url()->previous() }}" class="btn btn-light border">Go Back</a>
                <a href="{{ route('home') }}" class="btn btn-primary">Home</a>
            </div>
        </div>
    </div>
</div>
@endsection
