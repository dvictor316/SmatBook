@extends('layout.mainlayout')

@section('content')
@include('hotel.partials.pms-styles')
@php
    $availableCount = $rooms->count();
    $lowestRate = $rooms->map(fn ($room) => (float) ($room->type?->base_rate ?? 0))->filter(fn ($rate) => $rate > 0)->min() ?? 0;
@endphp
<div class="page-wrapper">
    <div class="content container-fluid">
        <div class="hotel-type-page hotel-config-page">
            <div class="hotel-type-header">
                <div>
                    <span class="hotel-type-label"><i class="fe fe-log-in"></i> Reception Desk</span>
                    <h2>Walk-in check-in</h2>
                    <p>Create the guest, assign an available room, collect deposit, open the folio, and mark the room occupied.</p>
                </div>
                <div class="d-flex gap-2 flex-wrap">
                    <a href="{{ route('hotel.availability.index') }}" class="btn btn-outline-primary"><i class="fas fa-search me-1"></i> Availability</a>
                    <a href="{{ route('hotel.frontdesk') }}" class="btn btn-outline-dark"><i class="fas fa-briefcase me-1"></i> Front Desk</a>
                </div>
            </div>

            @if(!$property)
                <div class="hotel-type-panel">
                    <div class="hotel-type-panel-body">
                        <div class="alert alert-warning mb-0">No active hotel property is mapped to this branch. Complete Hotel Setup before checking in walk-in guests.</div>
                    </div>
                </div>
            @else
                <div class="hotel-ledger-strip">
                    <span>Available rooms: {{ $availableCount }}</span>
                    <span>Room types: {{ $roomTypes->count() }}</span>
                    <span>Lowest visible rate: {{ number_format((float) $lowestRate, 2) }}</span>
                </div>

                <form method="POST" action="{{ route('hotel.walkin.store') }}">
                    @csrf
                    <div class="row g-3">
                        <div class="col-xl-8">
                            <div class="hotel-type-panel mb-3">
                                <div class="hotel-type-panel-header"><h5 class="mb-0">1. Room & Stay</h5></div>
                                <div class="hotel-type-panel-body row g-3">
                                    <div class="col-md-6">
                                        <label class="form-label">Room Type Guide</label>
                                        <select class="form-control" disabled>
                                            <option>Use the room list to pick a room</option>
                                            @foreach($roomTypes as $type)
                                                <option>{{ $type->name }} - {{ number_format((float) $type->base_rate, 2) }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">Available Room</label>
                                        <select name="room_id" class="form-control" required>
                                            <option value="">Select room</option>
                                            @foreach($rooms as $room)
                                                <option value="{{ $room->id }}" @selected((int) old('room_id') === (int) $room->id)>
                                                    {{ $room->room_number }} - {{ $room->type?->name ?? 'Room' }} - {{ number_format((float) ($room->type?->base_rate ?? 0), 2) }}
                                                </option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">Expected Checkout</label>
                                        <input type="datetime-local" name="expected_checkout_at" value="{{ old('expected_checkout_at', now()->addDay()->format('Y-m-d\TH:i')) }}" class="form-control" required>
                                    </div>
                                    <div class="col-md-3">
                                        <label class="form-label">Adults</label>
                                        <input type="number" name="adults" min="1" max="20" value="{{ old('adults', 1) }}" class="form-control">
                                    </div>
                                    <div class="col-md-3">
                                        <label class="form-label">Children</label>
                                        <input type="number" name="children" min="0" max="20" value="{{ old('children', 0) }}" class="form-control">
                                    </div>
                                </div>
                            </div>

                            <div class="hotel-type-panel mb-3">
                                <div class="hotel-type-panel-header"><h5 class="mb-0">2. Guest</h5></div>
                                <div class="hotel-type-panel-body row g-3">
                                    <div class="col-12">
                                        <label class="form-label">Existing Guest</label>
                                        <select name="customer_id" class="form-control">
                                            <option value="">Create new guest below</option>
                                            @foreach($guests as $guest)
                                                <option value="{{ $guest->id }}" @selected((int) old('customer_id') === (int) $guest->id)>
                                                    {{ $guest->customer_name }}{{ $guest->phone ? ' - '.$guest->phone : '' }}{{ $guest->email ? ' - '.$guest->email : '' }}
                                                </option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">New Guest Name</label>
                                        <input type="text" name="guest_name" value="{{ old('guest_name') }}" class="form-control" placeholder="Required if no existing guest is selected">
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">Phone</label>
                                        <input type="text" name="guest_phone" value="{{ old('guest_phone') }}" class="form-control">
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">Email</label>
                                        <input type="email" name="guest_email" value="{{ old('guest_email') }}" class="form-control">
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">Address</label>
                                        <input type="text" name="guest_address" value="{{ old('guest_address') }}" class="form-control">
                                    </div>
                                </div>
                            </div>

                            <div class="hotel-type-panel">
                                <div class="hotel-type-panel-header"><h5 class="mb-0">3. Rate & Deposit</h5></div>
                                <div class="hotel-type-panel-body row g-3">
                                    <div class="col-md-4">
                                        <label class="form-label">Agreed Rate</label>
                                        <input type="number" step="0.01" min="0" name="agreed_rate" value="{{ old('agreed_rate') }}" class="form-control" placeholder="Optional override">
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label">Opening Deposit</label>
                                        <input type="number" step="0.01" min="0" name="opening_deposit" value="{{ old('opening_deposit', 0) }}" class="form-control">
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label">Payment Method</label>
                                        <select name="payment_method" class="form-control">
                                            @foreach(['cash' => 'Cash', 'transfer' => 'Transfer', 'pos' => 'POS', 'card' => 'Card', 'other' => 'Other'] as $value => $label)
                                                <option value="{{ $value }}" @selected(old('payment_method') === $value)>{{ $label }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="col-12">
                                        <label class="form-label">Reception Note</label>
                                        <textarea name="note" class="form-control" rows="2" placeholder="ID checked, luggage note, source, special request">{{ old('note') }}</textarea>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="col-xl-4">
                            <div class="hotel-type-panel h-100">
                                <div class="hotel-type-panel-header"><h5 class="mb-0">Complete Check-In</h5></div>
                                <div class="hotel-type-panel-body d-grid gap-2">
                                    <div class="hotel-config-list">
                                        <div><strong>Room becomes occupied</strong><p class="small text-muted mb-0">The selected room leaves available inventory immediately.</p></div>
                                        <div><strong>Guest folio opens</strong><p class="small text-muted mb-0">Service sales, deposits, checkout and reports use this folio.</p></div>
                                        <div><strong>Deposit posts to accounts</strong><p class="small text-muted mb-0">Any opening deposit is posted as a folio payment line.</p></div>
                                    </div>
                                    <button class="btn btn-primary btn-lg" @disabled($rooms->isEmpty())><i class="fas fa-key me-1"></i> Complete Walk-In Check-In</button>
                                    <a href="{{ route('hotel.rooms.index') }}" class="btn btn-outline-primary"><i class="fas fa-bed me-1"></i> Manage Rooms</a>
                                    <a href="{{ route('hotel.folios.index') }}" class="btn btn-outline-dark"><i class="fas fa-file-invoice me-1"></i> Guest Folios</a>
                                </div>
                            </div>
                        </div>
                    </div>
                </form>
            @endif
        </div>
    </div>
</div>
@endsection
