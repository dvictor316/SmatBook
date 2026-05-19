@extends('layout.mainlayout')

@section('content')
<div class="page-wrapper">
    <div class="content container-fluid">
        <div class="page-header">
            <div class="row align-items-center">
                <div class="col">
                    <h3 class="page-title">Process POS Return</h3>
                    <ul class="breadcrumb">
                        <li class="breadcrumb-item"><a href="{{ route('pos.sales') }}">POS Sales</a></li>
                        <li class="breadcrumb-item active">Process Return</li>
                    </ul>
                </div>
            </div>
        </div>

        @if(session('success'))
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                {{ session('success') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        @endif

        <div class="card shadow-sm border-0">
            <div class="card-body">
                <form action="{{ route('pos.return.store') }}" method="POST">
                    @csrf
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="fw-bold">Select POS Sale <span class="text-danger">*</span></label>
                            <select name="sale_id" id="sale_select" class="form-control select2">
                                <option value="">-- Search Sale No --</option>
                                @foreach($sales as $sale)
                                    <option value="{{ $sale->id }}"
                                        {{ isset($selectedSale) && $selectedSale->id == $sale->id ? 'selected' : '' }}>
                                        {{ $sale->receipt_no ?? $sale->invoice_no ?? 'Sale #'.$sale->id }}
                                        ({{ $sale->customer->name ?? 'Walk-in Customer' }})
                                    </option>
                                @endforeach
                            </select>
                            @error('sale_id')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="fw-bold">Return Date</label>
                            <input type="date" name="return_date" class="form-control" value="{{ date('Y-m-d') }}">
                            @error('return_date')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="fw-bold">Note (optional)</label>
                        <input type="text" name="note" class="form-control" placeholder="Reason for return..." value="{{ old('note') }}">
                    </div>

                    <div class="table-responsive mt-4">
                        <table class="table table-hover" id="posReturnTable">
                            <thead class="thead-light">
                                <tr>
                                    <th>Product</th>
                                    <th>Sold Qty</th>
                                    <th>Return Qty</th>
                                    <th>Unit Price (₦)</th>
                                    <th>Total (₦)</th>
                                </tr>
                            </thead>
                            <tbody id="sale_item_list">
                                @if(isset($selectedSale) && $selectedSale->items->isNotEmpty())
                                    @foreach($selectedSale->items as $item)
                                        <tr>
                                            <td>{{ $item->product->name ?? 'Unknown' }}</td>
                                            <td><span class="badge bg-light text-dark">{{ $item->qty }}</span></td>
                                            <td>
                                                <input type="number"
                                                    name="items[{{ $item->product_id }}][qty]"
                                                    class="form-control return-qty"
                                                    data-price="{{ $item->unit_price }}"
                                                    max="{{ $item->qty }}" min="0" value="0">
                                            </td>
                                            <td>
                                                <input type="hidden" name="items[{{ $item->product_id }}][unit_price]" value="{{ $item->unit_price }}">
                                                <input type="hidden" name="items[{{ $item->product_id }}][unit_type]" value="{{ $item->unit_type ?? 'unit' }}">
                                                {{ number_format($item->unit_price, 2) }}
                                            </td>
                                            <td class="row-total fw-bold text-primary">0.00</td>
                                        </tr>
                                    @endforeach
                                @else
                                    <tr><td colspan="5" class="text-center py-4 text-muted">Select a POS sale to load items.</td></tr>
                                @endif
                            </tbody>
                            <tfoot>
                                <tr class="bg-light">
                                    <th colspan="4" class="text-end">Grand Total:</th>
                                    <th id="return_grand_total">₦0.00</th>
                                </tr>
                            </tfoot>
                        </table>
                    </div>

                    <div class="text-end mt-4">
                        <a href="{{ route('pos.sales') }}" class="btn btn-secondary me-2">Cancel</a>
                        <button type="submit" class="btn btn-warning btn-lg shadow-sm">
                            <i class="fas fa-undo me-1"></i> Process Return
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection

@section('script')
<script>
$(document).ready(function() {

    // ── 1. Load items via AJAX when sale changes ──────────────────
    $('#sale_select').on('change select2:select', function() {
        let id = $(this).val();

        if (!id) {
            $('#sale_item_list').html('<tr><td colspan="5" class="text-center py-4 text-muted">Select a POS sale to load items.</td></tr>');
            $('#return_grand_total').text('₦0.00');
            return;
        }

        // Update URL without reload so user can reload/bookmark
        let url = new URL(window.location.href);
        url.searchParams.set('sale_id', id);
        window.history.replaceState(null, '', url.toString());

        $('#sale_item_list').html('<tr><td colspan="5" class="text-center"><div class="spinner-border spinner-border-sm text-primary" role="status"></div> Loading items...</td></tr>');

        $.ajax({
            url: "{{ url('/get-invoice-items') }}/" + id,
            method: 'GET',
            dataType: 'json',
            success: function(items) {
                let html = '';
                if (items.length === 0) {
                    html = '<tr><td colspan="5" class="text-center text-danger">No items found for this sale.</td></tr>';
                } else {
                    $.each(items, function(key, item) {
                        html += `
                        <tr>
                            <td>${item.name}</td>
                            <td><span class="badge bg-light text-dark">${item.qty}</span></td>
                            <td>
                                <input type="number" name="items[${item.product_id}][qty]"
                                       class="form-control return-qty"
                                       data-price="${item.unit_price}"
                                       max="${item.qty}" min="0" value="0">
                            </td>
                            <td>
                                <input type="hidden" name="items[${item.product_id}][unit_price]" value="${item.unit_price}">
                                <input type="hidden" name="items[${item.product_id}][unit_type]" value="${item.unit_type || 'unit'}">
                                ${parseFloat(item.unit_price).toLocaleString(undefined, {minimumFractionDigits: 2})}
                            </td>
                            <td class="row-total fw-bold text-primary">0.00</td>
                        </tr>`;
                    });
                }
                $('#sale_item_list').html(html);
                updateGrandTotal();
            },
            error: function(xhr) {
                $('#sale_item_list').html('<tr><td colspan="5" class="text-center text-danger">Error: Could not retrieve items from server.</td></tr>');
            }
        });
    });

    // ── 2. Dynamic qty calculation ────────────────────────────────
    $(document).on('input', '.return-qty', function() {
        let qty   = parseFloat($(this).val()) || 0;
        let price = parseFloat($(this).data('price')) || 0;
        let max   = parseFloat($(this).attr('max')) || 0;

        if (qty > max) { $(this).val(max); qty = max; }
        if (qty < 0)   { $(this).val(0);   qty = 0;   }

        let rowTotal = qty * price;
        $(this).closest('tr').find('.row-total').text(rowTotal.toLocaleString(undefined, {minimumFractionDigits: 2}));
        updateGrandTotal();
    });

    // ── 3. Grand total helper ─────────────────────────────────────
    function updateGrandTotal() {
        let grandTotal = 0;
        $('.return-qty').each(function() {
            grandTotal += (parseFloat($(this).val()) || 0) * (parseFloat($(this).data('price')) || 0);
        });
        $('#return_grand_total').text('₦' + grandTotal.toLocaleString(undefined, {minimumFractionDigits: 2}));
    }

    // Trigger on page load if a sale is already selected
    if ($('#sale_select').val()) {
        $('#sale_select').trigger('change');
    }
});
</script>
@endsection
