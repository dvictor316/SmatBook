@extends('layout.mainlayout')

@section('content')
<div class="page-wrapper">
    <div class="content container-fluid">
        <h3 class="mb-3">New Reservation</h3>
        @if(!$property)
            <div class="alert alert-warning">No hotel property is mapped to your current branch yet. Complete Hotel setup first.</div>
        @endif

        <form method="POST" action="{{ route('hotel.reservations.store') }}" id="reservationForm">
            @csrf
            <div class="row g-3">
                <div class="col-xl-8">
                    <div class="card mb-3">
                        <div class="card-header"><h5 class="mb-0">Stay Details</h5></div>
                        <div class="card-body row g-2">
                            <div class="col-md-4"><label class="form-label">Arrival</label><input type="date" name="arrival_date" id="arrival_date" class="form-control" value="{{ old('arrival_date', $arrivalDate) }}" required></div>
                            <div class="col-md-4"><label class="form-label">Departure</label><input type="date" name="departure_date" id="departure_date" class="form-control" value="{{ old('departure_date', $departureDate) }}" required></div>
                            <div class="col-md-2"><label class="form-label">Adults</label><input type="number" name="adults" id="adults" class="form-control" value="{{ old('adults', 1) }}" min="1"></div>
                            <div class="col-md-2"><label class="form-label">Children</label><input type="number" name="children" id="children" class="form-control" value="{{ old('children', 0) }}" min="0"></div>
                        </div>
                    </div>

                    <div class="card mb-3">
                        <div class="card-header"><h5 class="mb-0">Availability</h5></div>
                        <div class="card-body row g-2">
                            <div class="col-md-6">
                                <label class="form-label">Room Type</label>
                                <select name="room_type_id" id="room_type_id" class="form-control">
                                    <option value="">Select room type</option>
                                    @foreach($roomTypes as $roomType)
                                        <option value="{{ $roomType->id }}" {{ (int) old('room_type_id', $prefilledRoomTypeId) === (int) $roomType->id ? 'selected' : '' }}>{{ $roomType->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Room (Optional)</label>
                                <select name="room_id" id="room_id" class="form-control">
                                    <option value="">Unassigned</option>
                                    @foreach($availableRooms as $room)
                                        <option value="{{ $room->id }}" data-room-type="{{ $room->room_type_id }}" {{ (int) old('room_id', $prefilledRoomId) === (int) $room->id ? 'selected' : '' }}>{{ $room->room_number }} - {{ $room->type?->name ?? '' }}</option>
                                    @endforeach
                                </select>
                                <small class="text-muted">Reservation can remain unassigned and be allocated later by front desk.</small>
                            </div>
                            <div class="col-12">
                                <div class="alert alert-light mb-0" id="availability_status">Change dates or room type to refresh availability.</div>
                                <div class="row g-2 mt-1" id="availability_cards"></div>
                            </div>
                        </div>
                    </div>

                    <div class="card mb-3">
                        <div class="card-header"><h5 class="mb-0">Guest</h5></div>
                        <div class="card-body row g-2">
                            <div class="col-md-6"><label class="form-label">Existing Guest ID</label><input type="number" name="customer_id" class="form-control" value="{{ old('customer_id') }}" placeholder="Enter customer ID"></div>
                            <div class="col-md-6"><label class="form-label">Source</label><input type="text" name="source" class="form-control" value="{{ old('source', 'direct') }}" placeholder="Direct, OTA, Agent"></div>
                        </div>
                    </div>

                    <div class="card mb-3">
                        <div class="card-header"><h5 class="mb-0">Rate</h5></div>
                        <div class="card-body row g-2">
                            <div class="col-md-4"><label class="form-label">Nightly Rate</label><input type="number" step="0.01" min="0" name="nightly_rate" id="nightly_rate" class="form-control" value="{{ old('nightly_rate', 0) }}"></div>
                            <div class="col-md-4"><label class="form-label">Discount</label><input type="number" step="0.01" min="0" id="preview_discount" class="form-control" value="0"></div>
                            <div class="col-md-4"><label class="form-label">Service Charge %</label><input type="number" step="0.01" min="0" id="preview_service" class="form-control" value="0"></div>
                        </div>
                    </div>

                    <div class="card mb-3">
                        <div class="card-header"><h5 class="mb-0">Deposit</h5></div>
                        <div class="card-body row g-2">
                            <div class="col-md-4"><label class="form-label">Deposit Required</label><input type="number" step="0.01" min="0" name="deposit_required" id="deposit_required" class="form-control" value="{{ old('deposit_required', 0) }}"></div>
                            <div class="col-md-4"><label class="form-label">Deposit Received</label><input type="number" step="0.01" min="0" name="deposit_received" id="deposit_received" class="form-control" value="{{ old('deposit_received', 0) }}"></div>
                            <div class="col-md-4"><label class="form-label">Payment Method (Preview)</label><select class="form-control"><option>Cash</option><option>Transfer</option><option>POS</option></select></div>
                        </div>
                    </div>

                    <div class="card mb-3">
                        <div class="card-header"><h5 class="mb-0">Notes</h5></div>
                        <div class="card-body row g-2">
                            <div class="col-12"><label class="form-label">Special Request</label><textarea name="special_requests" class="form-control" rows="2">{{ old('special_requests') }}</textarea></div>
                            <div class="col-12"><label class="form-label">Internal Note</label><textarea name="internal_notes" class="form-control" rows="2">{{ old('internal_notes') }}</textarea></div>
                        </div>
                    </div>

                    <div class="d-flex gap-2">
                        <button class="btn btn-primary">Create Reservation</button>
                        <a href="{{ route('hotel.rooms.calendar') }}" class="btn btn-outline-secondary">Back to Calendar</a>
                    </div>
                </div>

                <div class="col-xl-4">
                    <div class="card sticky-top" style="top: 80px;">
                        <div class="card-header"><h5 class="mb-0">Live Summary</h5></div>
                        <div class="card-body">
                            <div class="d-flex justify-content-between"><span>Nights</span><strong id="sum_nights">1</strong></div>
                            <div class="d-flex justify-content-between"><span>Room Rate</span><strong id="sum_rate">0.00</strong></div>
                            <div class="d-flex justify-content-between"><span>Accommodation</span><strong id="sum_accommodation">0.00</strong></div>
                            <div class="d-flex justify-content-between"><span>Tax (preview)</span><strong id="sum_tax">0.00</strong></div>
                            <div class="d-flex justify-content-between"><span>Service Charge</span><strong id="sum_service">0.00</strong></div>
                            <div class="d-flex justify-content-between"><span>Discount</span><strong id="sum_discount">0.00</strong></div>
                            <hr>
                            <div class="d-flex justify-content-between"><span>Total</span><strong id="sum_total">0.00</strong></div>
                            <div class="d-flex justify-content-between"><span>Deposit</span><strong id="sum_deposit">0.00</strong></div>
                            <div class="d-flex justify-content-between"><span>Balance</span><strong id="sum_balance">0.00</strong></div>
                            <small class="text-muted d-block mt-2">Summary is UX preview; backend remains source of truth.</small>
                        </div>
                    </div>
                </div>
            </div>
        </form>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const arrival = document.getElementById('arrival_date');
    const departure = document.getElementById('departure_date');
    const nightlyRate = document.getElementById('nightly_rate');
    const discount = document.getElementById('preview_discount');
    const servicePct = document.getElementById('preview_service');
    const deposit = document.getElementById('deposit_received');
    const roomType = document.getElementById('room_type_id');
    const roomSelect = document.getElementById('room_id');
    const availabilityStatus = document.getElementById('availability_status');
    const availabilityCards = document.getElementById('availability_cards');
    const propertyId = @json($property?->id);

    const outputs = {
        nights: document.getElementById('sum_nights'),
        rate: document.getElementById('sum_rate'),
        accommodation: document.getElementById('sum_accommodation'),
        tax: document.getElementById('sum_tax'),
        service: document.getElementById('sum_service'),
        discount: document.getElementById('sum_discount'),
        total: document.getElementById('sum_total'),
        deposit: document.getElementById('sum_deposit'),
        balance: document.getElementById('sum_balance'),
    };

    function updateRoomOptions() {
        const selectedType = roomType.value;
        Array.from(roomSelect.options).forEach((option, index) => {
            if (index === 0) {
                option.hidden = false;
                return;
            }
            option.hidden = selectedType !== '' && option.dataset.roomType !== selectedType;
        });
    }

    function loadAvailability() {
        if (!arrival.value || !departure.value || !propertyId) return;
        const params = new URLSearchParams({
            property_id: propertyId,
            arrival_date: arrival.value,
            departure_date: departure.value
        });
        if (roomType.value) params.set('room_type_id', roomType.value);

        availabilityStatus.className = 'alert alert-info mb-0';
        availabilityStatus.textContent = 'Checking live room availability...';

        fetch(`{{ route('hotel.availability.rooms_json') }}?${params.toString()}`, {
            headers: {'Accept': 'application/json'}
        })
            .then((response) => response.ok ? response.json() : Promise.reject(response))
            .then((payload) => {
                availabilityStatus.className = 'alert alert-light mb-0';
                availabilityStatus.textContent = `${payload.available_count} available, ${payload.unavailable_count} unavailable for selected dates.`;
                roomSelect.innerHTML = '<option value="">Unassigned</option>';
                availabilityCards.innerHTML = '';

                payload.rooms.forEach((room) => {
                    if (room.available) {
                        const option = document.createElement('option');
                        option.value = room.id;
                        option.dataset.roomType = room.room_type_id || '';
                        option.dataset.rate = room.rate || 0;
                        option.textContent = `${room.room_number} - ${room.room_type || 'Room'} - ${Number(room.rate || 0).toFixed(2)}`;
                        roomSelect.appendChild(option);
                    }

                    const card = document.createElement('div');
                    card.className = 'col-md-6';
                    card.innerHTML = `<div class="border rounded p-2 ${room.available ? 'bg-white' : 'bg-light text-muted'}"><div class="d-flex justify-content-between"><strong>Room ${room.room_number}</strong><span class="badge ${room.available ? 'bg-success' : 'bg-secondary'}">${room.available ? 'Available' : 'Unavailable'}</span></div><small>${room.room_type || 'Room'} - ${room.operational_status} / ${room.housekeeping_status}</small></div>`;
                    availabilityCards.appendChild(card);
                });
            })
            .catch(() => {
                availabilityStatus.className = 'alert alert-warning mb-0';
                availabilityStatus.textContent = 'Availability could not be refreshed right now.';
            });
    }

    function parseFloatSafe(value) {
        const parsed = parseFloat(value);
        return Number.isFinite(parsed) ? parsed : 0;
    }

    function calculate() {
        const start = arrival.value ? new Date(arrival.value + 'T00:00:00') : null;
        const end = departure.value ? new Date(departure.value + 'T00:00:00') : null;
        let nights = 1;
        if (start && end) {
            const diff = Math.round((end - start) / (1000 * 60 * 60 * 24));
            nights = diff > 0 ? diff : 1;
        }

        const rate = parseFloatSafe(nightlyRate.value);
        const accommodation = rate * nights;
        const discountAmount = parseFloatSafe(discount.value);
        const serviceAmount = accommodation * (parseFloatSafe(servicePct.value) / 100);
        const tax = accommodation * 0.075;
        const total = Math.max(0, accommodation + serviceAmount + tax - discountAmount);
        const paid = parseFloatSafe(deposit.value);
        const balance = Math.max(0, total - paid);

        outputs.nights.textContent = nights.toString();
        outputs.rate.textContent = rate.toFixed(2);
        outputs.accommodation.textContent = accommodation.toFixed(2);
        outputs.tax.textContent = tax.toFixed(2);
        outputs.service.textContent = serviceAmount.toFixed(2);
        outputs.discount.textContent = discountAmount.toFixed(2);
        outputs.total.textContent = total.toFixed(2);
        outputs.deposit.textContent = paid.toFixed(2);
        outputs.balance.textContent = balance.toFixed(2);
    }

    [arrival, departure, nightlyRate, discount, servicePct, deposit].forEach((el) => {
        if (el) {
            el.addEventListener('input', calculate);
            el.addEventListener('change', calculate);
        }
    });

    roomSelect.addEventListener('change', () => {
        const selected = roomSelect.selectedOptions[0];
        if (selected && selected.dataset.rate && parseFloatSafe(nightlyRate.value) === 0) {
            nightlyRate.value = selected.dataset.rate;
            calculate();
        }
    });

    [arrival, departure, roomType].forEach((el) => el.addEventListener('change', loadAvailability));
    roomType.addEventListener('change', updateRoomOptions);
    updateRoomOptions();
    calculate();
    loadAvailability();
});
</script>
@endsection
