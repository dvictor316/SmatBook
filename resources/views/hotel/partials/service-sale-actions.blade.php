@php
    $serviceCenters = [
        'restaurant' => ['code' => 'RESTAURANT', 'label' => 'Restaurant'],
        'bar' => ['code' => 'BAR', 'label' => 'Bar'],
        'gym' => ['code' => 'GYM', 'label' => 'Gym'],
        'spa' => ['code' => 'SPA', 'label' => 'Spa'],
        'ticketing' => ['code' => 'TICKETING', 'label' => 'Ticketing'],
        'room_service' => ['code' => 'ROOM_SERVICE', 'label' => 'Room Service'],
        'laundry' => ['code' => 'LAUNDRY', 'label' => 'Laundry'],
        'minibar' => ['code' => 'MINIBAR', 'label' => 'Minibar'],
        'conference' => ['code' => 'CONFERENCE', 'label' => 'Conference'],
    ];
    $itemMeta = is_array($item->meta ?? null) ? $item->meta : [];
    $serviceCode = strtoupper((string) ($item->service_code ?? ''));
    $currentCenter = (string) ($itemMeta['center'] ?? strtolower($serviceCode));
    $quantity = (float) ($item->quantity ?? 1);
    $amount = (float) ($item->line_total ?? $item->amount ?? 0);
    $unitPrice = (float) ($item->unit_price ?? ($quantity > 0 ? ($amount / $quantity) : $amount));
    $discount = (float) ($itemMeta['discount'] ?? 0);
    $tax = (float) ($itemMeta['tax'] ?? 0);
    $serviceDate = $item->service_date ? optional($item->service_date)->toDateString() : now()->toDateString();
    $modalId = 'hotelEditServiceSale'.$item->id;
    $canManageServiceSale = !empty($item->folio_id) && in_array((string) $item->type, ['service', 'pos_charge', 'charge'], true) && !in_array($serviceCode, ['ROOM', 'ROOM_NIGHT'], true);
@endphp

<div class="hotel-sale-actions">
    <a class="btn btn-sm btn-outline-dark" target="_blank" rel="noopener" href="{{ route('hotel.folios.items.receipt', $item) }}"><i class="fas fa-print me-1"></i> Receipt</a>
    @if($canManageServiceSale)
        <button type="button" class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#{{ $modalId }}"><i class="fas fa-pen me-1"></i> Edit</button>
        <form method="POST" action="{{ route('hotel.service_centers.charges.destroy', $item) }}" onsubmit="return confirm('Delete this service sale and refresh the guest folio totals?');">
            @csrf
            @method('DELETE')
            <button type="submit" class="btn btn-sm btn-outline-danger"><i class="fas fa-trash me-1"></i> Delete</button>
        </form>
    @endif
</div>

@if($canManageServiceSale)
    <div class="modal fade" id="{{ $modalId }}" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <form method="POST" action="{{ route('hotel.service_centers.charges.update', $item) }}" class="modal-content">
                @csrf
                @method('PUT')
                <div class="modal-header">
                    <h5 class="modal-title">Edit Service Sale</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="row g-3">
                        <div class="col-12">
                            <label class="form-label">Service Center</label>
                            <select name="service_center" class="form-select" required>
                                @foreach($serviceCenters as $centerKey => $centerMeta)
                                    <option value="{{ $centerKey }}" @selected($currentCenter === $centerKey || $serviceCode === $centerMeta['code'])>{{ $centerMeta['label'] }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-6"><label class="form-label">Item / Service</label><input name="description" class="form-control" value="{{ $item->description }}" required></div>
                        <div class="col-md-6"><label class="form-label">Date</label><input name="service_date" type="date" class="form-control" value="{{ $serviceDate }}"></div>
                        <div class="col-md-3"><label class="form-label">Quantity</label><input name="quantity" type="number" min="0.001" step="0.001" class="form-control" value="{{ $quantity }}"></div>
                        <div class="col-md-3"><label class="form-label">Unit Price</label><input name="unit_price" type="number" min="0.01" step="0.01" class="form-control" value="{{ $unitPrice }}" required></div>
                        <div class="col-md-3"><label class="form-label">Discount</label><input name="discount" type="number" min="0" step="0.01" class="form-control" value="{{ $discount }}"></div>
                        <div class="col-md-3"><label class="form-label">Tax</label><input name="tax" type="number" min="0" step="0.01" class="form-control" value="{{ $tax }}"></div>
                        <div class="col-12"><label class="form-label">Internal Note</label><textarea name="note" class="form-control" rows="2">{{ $itemMeta['note'] ?? '' }}</textarea></div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Close</button>
                    <button type="submit" class="btn btn-primary"><i class="fas fa-save me-1"></i> Save Sale</button>
                </div>
            </form>
        </div>
    </div>
@endif
