@extends('layout.mainlayout')

@section('content')
<div class="page-wrapper">
    <div class="content container-fluid">
        <div class="page-header">
            <div class="row align-items-center">
                <div class="col">
                    <h3 class="page-title">Process Purchase Return</h3>
                    <ul class="breadcrumb">
                        <li class="breadcrumb-item"><a href="{{url('index')}}">Dashboard</a></li>
                        <li class="breadcrumb-item"><a href="{{route('debit-notes')}}">Debit Notes</a></li>
                        <li class="breadcrumb-item active">Add Return</li>
                    </ul>
                </div>
            </div>
        </div>

        @if(auth()->user()->role == 'cashier')
            <div class="alert alert-warning border-0 shadow-sm mb-4">
                <i class="fas fa-exclamation-triangle me-2"></i> 
                <strong>Notice:</strong> Your account is assigned the <b>Cashier</b> role. You can view items but do not have permission to submit returns.
            </div>
        @endif

        @if(session('error'))
            <div class="alert alert-danger">{{ session('error') }}</div>
        @endif

        <div class="card shadow-sm border-0">
            <div class="card-body">
                <form action="{{ route('purchase-returns.store') }}" method="POST" id="returnForm">
                    @csrf
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="fw-bold mb-2">Original Purchase No <span class="text-danger">*</span></label>
                            <select name="purchase_id" id="purchase_select" class="form-control select2" required>
                                <option value="">-- Select Purchase --</option>
                                @foreach($purchases as $purchase)
                                    <option value="{{ $purchase->id }}">
                                        {{ $purchase->purchase_no }}
                                        -
                                        {{ data_get($purchase, 'vendor.name') ?? data_get($purchase, 'supplier.name') ?? data_get($purchase, 'vendor_name') ?? 'No Supplier' }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="fw-bold mb-2">Return Date</label>
                            <input type="date" name="return_date" class="form-control" value="{{ date('Y-m-d') }}">
                        </div>
                    </div>

                    <div class="table-responsive mt-4">
                        <table class="table table-bordered table-hover align-middle" id="returnTable">
                            <thead class="bg-light">
                                <tr>
                                    <th width="30%">Product Name</th>
                                    <th width="15%" class="text-center">Purchased Qty</th>
                                    <th width="20%">Qty to Return</th>
                                    <th width="15%">Unit Price (₦)</th>
                                    <th width="20%">Total (₦)</th>
                                </tr>
                            </thead>
                            <tbody id="item_list">
                                <tr>
                                    <td colspan="5" class="text-center py-5 text-muted">
                                        <i class="fas fa-box-open d-block mb-2 fa-2x"></i>
                                        Please select a purchase order above to load items.
                                    </td>
                                </tr>
                            </tbody>
                            <tfoot>
                                <tr class="bg-light">
                                    <th colspan="4" class="text-end py-3">Grand Total:</th>
                                    <th id="grand_total_display" class="text-primary fs-5">₦0.00</th>
                                </tr>
                            </tfoot>
                        </table>
                    </div>

                    <input type="hidden" name="total_amount" id="total_amount_input" value="0">

                    <div class="text-end mt-4">
                        @if(in_array(auth()->user()->role, ['super_admin', 'administrator', 'store_manager', 'accountant']))
                            <button type="submit" class="btn btn-primary btn-lg px-5 shadow-sm">
                                <i class="fas fa-check-circle me-2"></i> Submit Return
                            </button>
                        @else
                            <button type="button" class="btn btn-secondary btn-lg px-5 disabled" title="Permission Denied">
                                <i class="fas fa-lock me-2"></i> Submission Restricted
                            </button>
                        @endif
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
    // 1. AJAX Item Loading
    $('#purchase_select').on('change', function() {
        let purchaseId = $(this).val();
        let $itemList = $('#item_list');

        if (!purchaseId) {
            $itemList.html('<tr><td colspan="5" class="text-center py-4 text-muted">Please select a purchase order above.</td></tr>');
            return;
        }

        $itemList.html('<tr><td colspan="5" class="text-center py-4"><div class="spinner-border spinner-border-sm text-primary"></div> Fetching items...</td></tr>');

        $.ajax({
            url: "{{ url('/get-purchase-items') }}/" + purchaseId,
            method: 'GET',
            dataType: 'json',
            success: function(items) {
                let html = '';
                if (items.length === 0) {
                    html = '<tr><td colspan="5" class="text-center py-4 text-danger">No items found for this purchase record.</td></tr>';
                } else {
                    items.forEach(item => {
                        html += `
                        <tr>
                            <td><span class="text-dark fw-bold">${item.name}</span></td>
                            <td class="text-center"><span class="badge bg-soft-secondary text-secondary fs-6 border">${item.qty}</span></td>
                            <td>
                                <input type="number" 
                                       name="items[${item.product_id}][qty]" 
                                       class="form-control qty-input shadow-sm" 
                                       data-price="${item.unit_price}" 
                                       max="${item.qty}" 
                                       min="0" 
                                       value="0"
                                       ${"{{ auth()->user()->role == 'cashier' ? 'disabled' : '' }}"}>
                            </td>
                            <td>
                                <input type="hidden" name="items[${item.product_id}][unit_price]" value="${item.unit_price}">
                                <span class="text-muted">₦${parseFloat(item.unit_price).toLocaleString()}</span>
                            </td>
                            <td class="row-total fw-bold text-dark">0.00</td>
                        </tr>`;
                    });
                }
                $itemList.html(html);
                calculateGrandTotal();
            },
            error: function(xhr) {
                $itemList.html('<tr><td colspan="5" class="text-center py-4 text-danger">Error loading data. Ensure the route exists.</td></tr>');
            }
        });
    });

    // 2. Real-time Calculation logic
    $(document).on('input', '.qty-input', function() {
        let $row = $(this).closest('tr');
        let qty = parseFloat($(this).val()) || 0;
        let price = parseFloat($(this).data('price')) || 0;
        let maxQty = parseFloat($(this).attr('max'));

        if (qty > maxQty) {
            alert('Error: You cannot return more than the purchased amount (' + maxQty + ').');
            $(this).val(maxQty);
            qty = maxQty;
        }

        let rowTotal = qty * price;
        $row.find('.row-total').text(rowTotal.toLocaleString(undefined, {minimumFractionDigits: 2}));
        calculateGrandTotal();
    });

    function calculateGrandTotal() {
        let grandTotal = 0;
        $('.qty-input').each(function() {
            let qty = parseFloat($(this).val()) || 0;
            let price = parseFloat($(this).data('price')) || 0;
            grandTotal += (qty * price);
        });
        $('#grand_total_display').text('₦' + grandTotal.toLocaleString(undefined, {minimumFractionDigits: 2}));
        $('#total_amount_input').val(grandTotal);
    }
});
</script>
@endsection
