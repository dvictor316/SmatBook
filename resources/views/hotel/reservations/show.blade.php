@extends('layout.mainlayout')

@section('content')
<div class="page-wrapper">
    <div class="content container-fluid">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h3 class="mb-0">Reservation {{ $reservation->reservation_number }}</h3>
            <a href="{{ route('hotel.rooms.calendar') }}" class="btn btn-outline-secondary">Calendar</a>
        </div>

        @include('hotel.partials.operations-action-deck', [
            'context' => 'reservation',
            'title' => 'Booking Action Centre',
            'subtitle' => 'Assign a room, check the guest in, post service charges, settle folio or hand off to housekeeping.'
        ])

        <div class="row g-3">
            <div class="col-xl-7">
                <div class="card mb-3">
                    <div class="card-header"><h5 class="mb-0">Quick View</h5></div>
                    <div class="card-body">
                        <div class="row g-2">
                            <div class="col-md-6"><strong>Guest:</strong> {{ $reservation->customer?->customer_name ?? $reservation->customer?->name ?? 'N/A' }}</div>
                            <div class="col-md-6"><strong>Status:</strong> {{ ucfirst(str_replace('_', ' ', (string) $reservation->status)) }}</div>
                            <div class="col-md-6"><strong>Room:</strong> {{ $reservation->room?->room_number ?? 'Unassigned' }}</div>
                            <div class="col-md-6"><strong>Room Type:</strong> {{ $reservation->roomType?->name ?? 'N/A' }}</div>
                            <div class="col-md-6"><strong>Arrival:</strong> {{ optional($reservation->arrival_date)->format('d M Y') }}</div>
                            <div class="col-md-6"><strong>Departure:</strong> {{ optional($reservation->departure_date)->format('d M Y') }}</div>
                            <div class="col-md-6"><strong>Total:</strong> {{ number_format((float) $reservation->total, 2) }}</div>
                            <div class="col-md-6"><strong>Deposit:</strong> {{ number_format((float) $reservation->deposit_received, 2) }}</div>
                            <div class="col-md-6"><strong>Balance:</strong> {{ number_format((float) $reservation->balance, 2) }}</div>
                            <div class="col-md-6"><strong>Special Requests:</strong> {{ $reservation->special_requests ?: 'N/A' }}</div>
                        </div>
                    </div>
                </div>

                <div class="card mb-3">
                    <div class="card-header"><h5 class="mb-0">Operational Actions</h5></div>
                    <div class="card-body">
                        <div class="d-flex gap-2 flex-wrap mb-3">
                            <a href="{{ route('hotel.reservations.index') }}" class="btn btn-light">View</a>
                            <a href="{{ route('hotel.reservations.create', ['room_type_id' => $reservation->room_type_id]) }}" class="btn btn-light">Duplicate</a>
                            @if(in_array((string)$reservation->status, ['reserved','confirmed']))
                                <form action="{{ route('hotel.checkin', $reservation) }}" method="POST">@csrf<button class="btn btn-success">Check In</button></form>
                            @endif
                            @if($stay)
                                <a href="{{ route('hotel.checkout.index', ['stay_id' => $stay->id]) }}" class="btn btn-warning">Checkout</a>
                            @endif
                            @if(in_array((string)$reservation->status, ['inquiry','reserved']))
                                <form action="{{ route('hotel.reservations.status', $reservation) }}" method="POST">@csrf<input type="hidden" name="status" value="confirmed"><button class="btn btn-primary"><i class="fas fa-check me-1"></i> Confirm</button></form>
                            @endif
                            @if(in_array((string)$reservation->status, ['cancelled','no_show']))
                                <form action="{{ route('hotel.reservations.status', $reservation) }}" method="POST">@csrf<input type="hidden" name="status" value="reserved"><button class="btn btn-outline-primary">Reinstate</button></form>
                            @endif
                        </div>

                        <div class="row g-3">
                            <div class="col-lg-6">
                                <form method="POST" action="{{ route('hotel.reservations.assign_room', $reservation) }}" class="border rounded p-2">
                                    @csrf
                                    <h6>Change Room / Assign Room</h6>
                                    <select name="room_id" class="form-control mb-2" required>
                                        <option value="">Select room</option>
                                        @foreach($availableRooms as $room)
                                            <option value="{{ $room->id }}" {{ (int)$reservation->room_id === (int)$room->id ? 'selected' : '' }}>{{ $room->room_number }}</option>
                                        @endforeach
                                    </select>
                                    <input type="text" name="reason" class="form-control mb-2" placeholder="Reason">
                                    <button class="btn btn-sm btn-outline-primary">Save Room</button>
                                </form>
                            </div>
                            <div class="col-lg-6">
                                <form method="POST" action="{{ route('hotel.reservations.extend', $reservation) }}" class="border rounded p-2">
                                    @csrf
                                    <h6>Extend Stay</h6>
                                    <div class="small mb-1">Current checkout: {{ optional($reservation->departure_date)->format('d M Y') }}</div>
                                    <input type="date" name="new_departure_date" class="form-control mb-2" required>
                                    <button class="btn btn-sm btn-outline-success">Extend</button>
                                </form>
                            </div>
                        </div>

                        @if(!in_array((string)$reservation->status, ['checked_in','completed','cancelled','no_show']))
                            <hr>
                            <form method="POST" action="{{ route('hotel.reservations.update', $reservation) }}" class="row g-2">
                                @csrf @method('PUT')
                                <div class="col-12"><h6 class="mb-1">Amend Reservation</h6><small class="text-muted">Availability and totals are recalculated before saving.</small></div>
                                <div class="col-md-3"><label class="form-label">Arrival</label><input type="date" name="arrival_date" class="form-control" value="{{ old('arrival_date', optional($reservation->arrival_date)->toDateString()) }}" required></div>
                                <div class="col-md-3"><label class="form-label">Departure</label><input type="date" name="departure_date" class="form-control" value="{{ old('departure_date', optional($reservation->departure_date)->toDateString()) }}" required></div>
                                <div class="col-md-3"><label class="form-label">Arrival Time</label><input type="time" name="arrival_time" class="form-control" value="{{ old('arrival_time', $reservation->arrival_time ? substr((string)$reservation->arrival_time, 0, 5) : '') }}"></div>
                                <div class="col-md-3"><label class="form-label">Departure Time</label><input type="time" name="departure_time" class="form-control" value="{{ old('departure_time', $reservation->departure_time ? substr((string)$reservation->departure_time, 0, 5) : '') }}"></div>
                                <div class="col-md-4"><label class="form-label">Assigned Room</label><select name="room_id" class="form-select"><option value="">Unassigned</option>@foreach($availableRooms as $room)<option value="{{ $room->id }}" @selected((int)$reservation->room_id === (int)$room->id)>{{ $room->room_number }} - {{ $room->type?->name ?? 'Room' }}</option>@endforeach</select></div>
                                <div class="col-md-2"><label class="form-label">Adults</label><input type="number" name="adults" class="form-control" min="1" value="{{ old('adults', $reservation->adults) }}" required></div>
                                <div class="col-md-2"><label class="form-label">Children</label><input type="number" name="children" class="form-control" min="0" value="{{ old('children', $reservation->children) }}" required></div>
                                <div class="col-md-4"><label class="form-label">Booking Source</label><input name="source" class="form-control" value="{{ old('source', $reservation->source) }}"></div>
                                <div class="col-md-3"><label class="form-label">Nightly Rate</label><input type="number" step="0.01" min="0" name="nightly_rate" class="form-control" value="{{ old('nightly_rate', $reservation->nightly_rate) }}" required></div>
                                <div class="col-md-3"><label class="form-label">Discount</label><input type="number" step="0.01" min="0" name="discount" class="form-control" value="{{ old('discount', $reservation->discount) }}"></div>
                                <div class="col-md-3"><label class="form-label">Tax</label><input type="number" step="0.01" min="0" name="tax" class="form-control" value="{{ old('tax', $reservation->tax) }}"></div>
                                <div class="col-md-3"><label class="form-label">Service Charge</label><input type="number" step="0.01" min="0" name="service_charge" class="form-control" value="{{ old('service_charge', $reservation->service_charge) }}"></div>
                                <input type="hidden" name="other_charges" value="{{ $reservation->other_charges }}"><input type="hidden" name="deposit_required" value="{{ $reservation->deposit_required }}"><input type="hidden" name="deposit_received" value="{{ $reservation->deposit_received }}">
                                <div class="col-md-6"><label class="form-label">Guest Requests</label><textarea name="special_requests" class="form-control" rows="2">{{ old('special_requests', $reservation->special_requests) }}</textarea></div>
                                <div class="col-md-6"><label class="form-label">Internal Notes</label><textarea name="internal_notes" class="form-control" rows="2">{{ old('internal_notes', $reservation->internal_notes) }}</textarea></div>
                                <div class="col-12"><button class="btn btn-primary"><i class="fas fa-save me-1"></i> Save Amendment</button></div>
                            </form>

                            <hr>
                            <div class="row g-2">
                                <div class="col-md-6"><form method="POST" action="{{ route('hotel.reservations.status', $reservation) }}" class="border rounded p-2">@csrf<input type="hidden" name="status" value="cancelled"><label class="form-label fw-bold">Cancel Reservation</label><textarea name="reason" class="form-control mb-2" rows="2" required placeholder="Cancellation reason"></textarea><button class="btn btn-outline-danger btn-sm">Cancel Booking</button></form></div>
                                @if(!$reservation->arrival_date?->isFuture())<div class="col-md-6"><form method="POST" action="{{ route('hotel.reservations.status', $reservation) }}" class="border rounded p-2">@csrf<input type="hidden" name="status" value="no_show"><label class="form-label fw-bold">Mark No-show</label><textarea name="reason" class="form-control mb-2" rows="2" required placeholder="No-show note"></textarea><button class="btn btn-outline-warning btn-sm">Record No-show</button></form></div>@endif
                            </div>
                        @endif
                    </div>
                </div>
            </div>

            <div class="col-xl-5">
                <div class="card">
                    <div class="card-header"><h5 class="mb-0">Operational Timeline</h5></div>
                    <div class="card-body">
                        @forelse($events as $event)
                            <div class="border-start border-3 ps-2 mb-3">
                                <div class="small text-muted">{{ optional($event->created_at)->format('d M Y H:i') }}</div>
                                <div><strong>{{ $event->title }}</strong></div>
                                <div class="small">{{ $event->description }}</div>
                            </div>
                        @empty
                            <div class="alert alert-info mb-0">No timeline events yet.</div>
                        @endforelse
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
