@extends('layout.mainlayout')

@section('content')
<div class="page-wrapper">
    <div class="content container-fluid">
        <div class="page-header">
            <div class="row align-items-center">
                <div class="col">
                    <h3 class="page-title">Process Sales Return (Credit Note)</h3>
                </div>
            </div>
        </div>

        <div class="card shadow-sm border-0">
            <div class="card-body">
                <form action="{{ route('reports.credit-notes-store') }}" method="POST">
                    @csrf
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="fw-bold">Select Original Sale <span class="text-danger">*</span></label>
                            <select name="invoice_id" id="invoice_select" class="form-control select2">
                                <option value="">-- Search Sale No --</option>
                                @foreach($invoices as $inv)
                                    <option value="{{ $inv->id }}">
                                        Sale #{{ $inv->display_name }} ({{ $inv->customer_name ?? 'Walk-in Customer' }})
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="fw-bold">Return Date</label>
                            <input type="date" name="credit_date" class="form-control" value="{{ date('Y-m-d') }}">
                        </div>
                    </div>

                    <div class="table-responsive mt-4">
                        <table class="table table-hover" id="salesTable">
                            <thead class="thead-light">
                                <tr>
                                    <th>Product</th>
                                    <th>Sold Qty</th>
                                    <th>Return Qty</th>
                                    <th>Price (₦)</th>
                                    <th>Total (₦)</th>
                                </tr>
                            </thead>
                            <tbody id="invoice_item_list">
                                <tr><td colspan="5" class="text-center py-4 text-muted">Select a sale to load items.</td></tr>
                            </tbody>
                            <tfoot>
                                <tr class="bg-light">
                                    <th colspan="4" class="text-end">Grand Total:</th>
                                    <th id="credit_grand_total">₦0.00</th>
                                </tr>
                            </tfoot>
                        </table>
                    </div>

                    <div class="text-end mt-4">
                        <button type="submit" class="btn btn-primary btn-lg shadow-sm">Process Credit Note</button>
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
    /**
     * 1. LOAD ITEMS VIA AJAX
     * Triggered when the sale selection changes (Supports standard and Select2)
     */
    $('#invoice_select').on('change select2:select', function() {
        let id = $(this).val();

        if(!id) {
            $('#invoice_item_list').html('<tr><td colspan="5" class="text-center py-4 text-muted">Select a sale to load items.</td></tr>');
            $('#credit_grand_total').text('₦0.00');
            return;
        }

        $('#invoice_item_list').html('<tr><td colspan="5" class="text-center"><div class="spinner-border spinner-border-sm text-primary" role="status"></div> Loading items...</td></tr>');

        $.ajax({
            url: "/get-invoice-items/" + id,
            method: 'GET',
            dataType: 'json',
            success: function(items) {
                let html = '';

                if(items.length === 0) {
                    html = '<tr><td colspan="5" class="text-center text-danger">No items found for this sale.</td></tr>';
                } else {
                    $.each(items, function(key, item) {
                        html += `
                        <tr>
                            <td>${item.name}</td>
                            <td><span class="badge bg-light text-dark">${item.qty}</span></td>
                            <td>
                                <input type="number" name="items[${item.product_id}][qty]" 
                                       class="form-control credit-qty" 
                                       data-price="${item.unit_price}" 
                                       max="${item.qty}" min="0" value="0">
                            </td>
                            <td>
                                <input type="hidden" name="items[${item.product_id}][unit_price]" value="${item.unit_price}">
                                ${parseFloat(item.unit_price).toLocaleString(undefined, {minimumFractionDigits: 2})}
                            </td>
                            <td class="row-total fw-bold text-primary">0.00</td>
                        </tr>`;
                    });
                }
                $('#invoice_item_list').html(html);
                updateGrandTotal();
            },
            error: function(xhr) {
                console.error("AJAX Error: " + xhr.status);
                $('#invoice_item_list').html('<tr><td colspan="5" class="text-center text-danger">Error: Could not retrieve items from server.</td></tr>');
            }
        });
    });

    /**
     * 2. DYNAMIC CALCULATION
     * Calculates row total and grand total as user types return quantities
     */
    $(document).on('input', '.credit-qty', function() {
        let qty = parseFloat($(this).val()) || 0;
        let price = parseFloat($(this).data('price')) || 0;
        let max = parseFloat($(this).attr('max')) || 0;

        // Validation: Ensure return quantity doesn't exceed original sold quantity
        if(qty > max) { 
            $(this).val(max); 
            qty = max; 
        }
        if(qty < 0) { 
            $(this).val(0); 
            qty = 0; 
        }

        let rowTotal = qty * price;
        $(this).closest('tr').find('.row-total').text(rowTotal.toLocaleString(undefined, {minimumFractionDigits: 2}));

        updateGrandTotal();
    });

    /**
     * 3. GRAND TOTAL HELPER
     */
    function updateGrandTotal() {
        let grandTotal = 0;
        $('.credit-qty').each(function() {
            let rowQty = parseFloat($(this).val()) || 0;
            let rowPrice = parseFloat($(this).data('price')) || 0;
            grandTotal += (rowQty * rowPrice);
        });
        $('#credit_grand_total').text('₦' + grandTotal.toLocaleString(undefined, {minimumFractionDigits: 2}));
    }
});
</script>
@endsection