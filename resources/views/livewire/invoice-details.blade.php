<div class="invoice-item invoice-table-wrap">
    <div class="invoice-table-head">
        <h6>Items:</h6>
    </div>
    <div class="row">
        <div class="col-md-12">
            <div class="table-responsive">
                <table class="table table-center table-hover mb-0">
                    <thead class="thead-light">
                        <tr>
                            <th>Product / Service</th>
                            <th>Quantity</th>
                            <th>Unit</th>
                            <th>Rate</th>
                            <th>Discount</th>
                            <th>Tax</th>
                            <th>Amount</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($sale->items as $item)
                            <tr>

                                <td>{{ $item->product_name ?? ($item->product->name ?? 'Unknown Product') }}</td>

                                <td>{{ $item->quantity ?? $item->qty }}</td>

                                <td>{{ $item->unit ?? 'Pcs' }}</td>

                                <td>${{ number_format($item->price ?? $item->rate, 2) }}</td>

                                <td>{{ $item->discount ?? 0 }}{{ ($item->discount_type == 'percentage') ? '%' : '' }}</td>

                                <td>{{ $item->tax_rate ?? $item->tax ?? 0 }}%</td>

                                <td class="text-end">
                                    ${{ number_format($item->total ?? ($item->quantity * ($item->price ?? $item->rate)), 2) }}
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="text-center text-muted">No items found for this invoice.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>