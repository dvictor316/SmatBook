@extends('layout.mainlayout')

@section('content')
<style>
    .pos-content-area {
        margin-left: var(--sb-sidebar-w, 270px); padding: 30px; transition: all 0.3s;
        background-color: #fdfaf0; min-height: 100vh; margin-top: 60px;
    }
    body.mini-sidebar .pos-content-area { margin-left: var(--sb-sidebar-collapsed, 80px); }
    @media (max-width: 991.98px) {
        .pos-content-area { margin-left: 0 !important; padding: 15px; }
    }
    .edit-card { border: none; border-radius: 15px; box-shadow: 0 10px 30px rgba(0,0,0,0.08); background: #fff; }
    .section-title { color: #0369a1; font-weight: 700; border-bottom: 2px solid #d4af37; padding-bottom: 10px; margin-bottom: 25px; }
    .table-thead-blue th { background-color: #0369a1 !important; color: white !important; text-transform: uppercase; font-size: 11px; }
    .grand-total-box { background: #f8f9fa; border: 1px dashed #d4af37; padding: 20px; border-radius: 10px; }
    .form-control-sm, .form-select-sm { border: 1px solid #ced4da; }
</style>

<div class="pos-content-area">
    <div class="container-fluid">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h3 class="fw-bold mb-0" style="color: #0369a1;">Edit Sale Transaction</h3>
                <p class="text-muted small">Invoice: <span class="fw-bold text-dark">#{{ $sale->invoice_no }}</span></p>
            </div>
            <a href="{{ route('sales.index') }}" class="btn btn-outline-secondary btn-sm"><i class="fas fa-arrow-left me-1"></i> Back</a>
        </div>

        <div class="card edit-card">
            <div class="card-body p-4">
                <form id="edit-sale-form" method="POST" action="{{ route('sales.update', $sale->id) }}">
                    @csrf 
                    @method('PUT')

                    <h5 class="section-title"><i class="fas fa-file-invoice me-2"></i>General Details</h5>
                    <div class="row g-3 mb-5">
                        <div class="col-md-4">
                            <label class="form-label fw-bold small">Customer</label>
                            <select class="form-select" name="customer_id" required>
                                @foreach($customers as $customer)
                                    <option value="{{ $customer->id }}" {{ $sale->customer_id == $customer->id ? 'selected' : '' }}>{{ $customer->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-bold small">Sale Date</label>
                            <input type="date" class="form-control" name="sale_date" value="{{ $sale->created_at->format('Y-m-d') }}">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-bold small">Reference No</label>
                            <input type="text" class="form-control" name="reference_no" value="{{ $sale->reference_no }}">
                        </div>
                    </div>

                    <h5 class="section-title"><i class="fas fa-shopping-cart me-2"></i>Items List</h5>
                    <div class="table-responsive mb-3">
                        <table class="table table-hover align-middle">
                            <thead class="table-thead-blue">
                                <tr>
                                    <th style="width: 35%;">Product</th>
                                    <th style="width: 15%;">Qty</th>
                                    <th style="width: 15%;">Rate (₦)</th>
                                    <th style="width: 15%;">Disc (%)</th>
                                    <th class="text-end" style="width: 15%;">Total</th>
                                    <th></th>
                                </tr>
                            </thead>
                            <tbody id="table-body">
                                @foreach($sale->items as $index => $item)
                                <tr class="item-row">
                                    <td>
                                        <select name="items[{{ $index }}][product_id]" class="form-select form-select-sm product-select" required>
                                            @foreach($products as $product)
                                                <option value="{{ $product->id }}" data-price="{{ $product->price }}" {{ $item->product_id == $product->id ? 'selected' : '' }}>{{ $product->name }}</option>
                                            @endforeach
                                        </select>
                                    </td>
                                    <td><input type="number" name="items[{{ $index }}][quantity]" class="form-control form-control-sm qty" value="{{ $item->qty }}" min="1"></td>
                                    <td><input type="number" name="items[{{ $index }}][rate]" class="form-control form-control-sm rate" value="{{ $item->unit_price }}"></td>
                                    <td><input type="number" name="items[{{ $index }}][discount]" class="form-control form-control-sm discount" value="{{ $item->discount }}"></td>
                                    <td class="text-end fw-bold text-dark row-total">₦{{ number_format($item->total_price, 2) }}</td>
                                    <td class="text-center"><button type="button" class="btn btn-sm text-danger remove-row"><i class="fas fa-trash-alt"></i></button></td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>

                    <button type="button" id="add-row" class="btn btn-sm btn-primary mb-5 shadow-sm"><i class="fas fa-plus me-1"></i> Add Item</button>

                    <div class="row justify-content-end">
                        <div class="col-md-4">
                            <div class="grand-total-box">
                                <div class="d-flex justify-content-between align-items-center">
                                    <span class="fw-bold text-muted">Grand Total:</span>
                                    <h3 class="fw-bold text-primary mb-0" id="grand-total">₦{{ number_format($sale->total, 2) }}</h3>
                                </div>
                                <input type="hidden" name="final_total" id="final_total_input" value="{{ $sale->total }}">
                            </div>
                        </div>
                    </div>

                    <div class="d-flex justify-content-end gap-2 mt-4 border-top pt-4">
                        <a href="{{ route('sales.index') }}" class="btn btn-light border px-4">Cancel</a>
                        <button type="submit" class="btn btn-primary px-5 shadow">Save Changes</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script>
$(document).ready(function() {
    let rowCount = {{ $sale->items->count() }};

    $('#add-row').click(function() {
        let row = `<tr class="item-row">
            <td>
                <select name="items[${rowCount}][product_id]" class="form-select form-select-sm product-select" required>
                    <option value="">Select Item</option>
                    @foreach($products as $p)
                        <option value="{{$p->id}}" data-price="{{$p->price}}">{{$p->name}}</option>
                    @endforeach
                </select>
            </td>
            <td><input type="number" name="items[${rowCount}][quantity]" class="form-control form-control-sm qty" value="1" min="1"></td>
            <td><input type="number" name="items[${rowCount}][rate]" class="form-control form-control-sm rate" value="0"></td>
            <td><input type="number" name="items[${rowCount}][discount]" class="form-control form-control-sm discount" value="0"></td>
            <td class="text-end fw-bold text-dark row-total">₦0.00</td>
            <td class="text-center"><button type="button" class="btn btn-sm text-danger remove-row"><i class="fas fa-trash-alt"></i></button></td>
        </tr>`;
        $('#table-body').append(row); 
        rowCount++;
    });

    $(document).on('click', '.remove-row', function() { 
        $(this).closest('tr').remove(); 
        calculateGrandTotal(); 
    });

    $(document).on('change', '.product-select', function() { 
        let price = $(this).find(':selected').data('price') || 0;
        $(this).closest('tr').find('.rate').val(price); 
        calculateRowTotal($(this).closest('tr')); 
    });

    $(document).on('input', '.qty, .rate, .discount', function() { 
        calculateRowTotal($(this).closest('tr')); 
    });

    function calculateRowTotal(row) {
        let q = parseFloat(row.find('.qty').val()) || 0;
        let r = parseFloat(row.find('.rate').val()) || 0;
        let d = parseFloat(row.find('.discount').val()) || 0;
        
        let subtotal = q * r;
        let total = subtotal - (subtotal * (d / 100));
        
        row.find('.row-total').text('₦' + total.toLocaleString(undefined, {minimumFractionDigits: 2}));
        calculateGrandTotal();
    }

    function calculateGrandTotal() {
        let gt = 0; 
        $('.row-total').each(function() { 
            let val = $(this).text().replace('₦', '').replace(/,/g, '');
            gt += parseFloat(val) || 0; 
        });
        $('#grand-total').text('₦' + gt.toLocaleString(undefined, {minimumFractionDigits: 2}));
        $('#final_total_input').val(gt);
    }
});
</script>
@endsection
