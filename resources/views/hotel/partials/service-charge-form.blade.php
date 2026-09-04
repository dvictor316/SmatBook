@php
    $center = $center ?? 'room_service';
    $title = $title ?? 'Service Sale';
    $placeholder = $placeholder ?? 'Service item';
@endphp
<aside class="hotel-type-panel">
    <div class="hotel-type-panel-header"><h5 class="mb-0">Post {{ $title }}</h5></div>
    <div class="hotel-type-panel-body">
        @if(($activeFolios ?? collect())->isEmpty())
            <div class="alert alert-light mb-0">No open guest folios are available for posting.</div>
        @else
            <form method="POST" action="{{ route('hotel.service_centers.charges.store', $center) }}">
                @csrf
                <div class="mb-2 hotel-dropdown-field"><label class="form-label">Guest / Room</label><select name="folio_id" class="form-select" required>@foreach($activeFolios as $folio)<option value="{{ $folio->id }}">{{ $folio->customer?->customer_name ?? $folio->customer?->name ?? 'Guest' }} - Room {{ $folio->stay?->room?->room_number ?? 'N/A' }} - {{ $folio->folio_number }}</option>@endforeach</select></div>
                <div class="mb-2"><label class="form-label">Item / Service</label><input name="description" class="form-control" placeholder="{{ $placeholder }}" required></div>
                <div class="row g-2">
                    <div class="col-6"><label class="form-label">Qty</label><input name="quantity" class="form-control" type="number" step="0.001" min="0.001" value="1"></div>
                    <div class="col-6"><label class="form-label">Unit Price</label><input name="unit_price" class="form-control" type="number" step="0.01" min="0.01" required></div>
                    <div class="col-6"><label class="form-label">Discount</label><input name="discount" class="form-control" type="number" step="0.01" min="0" value="0"></div>
                    <div class="col-6"><label class="form-label">Tax</label><input name="tax" class="form-control" type="number" step="0.01" min="0" value="0"></div>
                </div>
                <div class="my-2 hotel-dropdown-field"><label class="form-label">Payment</label><select name="payment_mode" class="form-select"><option value="charge_to_room">Charge to Room</option><option value="cash">Cash Paid</option><option value="card">Card / POS Paid</option><option value="transfer">Transfer Paid</option><option value="other">Other Paid</option></select></div>
                <div class="mb-2"><label class="form-label">Date</label><input name="service_date" class="form-control" type="date" value="{{ now()->toDateString() }}"></div>
                <div class="mb-3"><label class="form-label">Note</label><textarea name="note" class="form-control" rows="2"></textarea></div>
                <button class="btn btn-primary w-100"><i class="fas fa-print me-1"></i> Post & Print Receipt</button>
            </form>
        @endif
    </div>
</aside>
