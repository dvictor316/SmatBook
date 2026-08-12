@extends('layout.mainlayout')

@section('content')
<div class="page-wrapper">
    <div class="content container-fluid">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <div>
                <h3 class="mb-0">Walk-In</h3>
                <p class="text-muted mb-0">Fast reception workflow for immediate arrivals</p>
            </div>
        </div>
        <form method="POST" action="{{ route('hotel.walkin.store') }}">
            @csrf
            <div class="row g-3">
                <div class="col-xl-8">
                    <div class="card mb-3"><div class="card-header"><h5 class="mb-0">1. Available Room</h5></div><div class="card-body row g-2">
                        <div class="col-md-6"><label class="form-label">Room Type</label><select class="form-control"><option value="">Any</option>@foreach($roomTypes as $type)<option>{{ $type->name }}</option>@endforeach</select></div>
                        <div class="col-md-6"><label class="form-label">Room</label><select name="room_id" class="form-control" required><option value="">Select room</option>@foreach($rooms as $room)<option value="{{ $room->id }}">{{ $room->room_number }} - {{ $room->type?->name }}</option>@endforeach</select></div>
                    </div></div>

                    <div class="card mb-3"><div class="card-header"><h5 class="mb-0">2. Guest</h5></div><div class="card-body row g-2"><div class="col-md-6"><label class="form-label">Existing Guest ID</label><input type="number" name="customer_id" class="form-control" placeholder="Enter guest ID if existing"></div><div class="col-md-6"><label class="form-label">Property</label><input type="text" class="form-control" value="{{ $property?->name ?? 'Current Property' }}" disabled></div></div></div>

                    <div class="card mb-3"><div class="card-header"><h5 class="mb-0">3. Stay</h5></div><div class="card-body row g-2"><div class="col-md-6"><label class="form-label">Expected Checkout</label><input type="datetime-local" name="expected_checkout_at" class="form-control" required></div><div class="col-md-6"><label class="form-label">Check-In Time</label><input type="text" class="form-control" value="{{ now()->format('d M Y H:i') }}" disabled></div></div></div>
                </div>
                <div class="col-xl-4">
                    <div class="card h-100"><div class="card-header"><h5 class="mb-0">6. Check In</h5></div><div class="card-body d-grid gap-2"><p class="text-muted">This workflow is optimized for quick reception processing using currently available rooms.</p><button class="btn btn-primary">Complete Walk-In Check-In</button><a href="{{ route('hotel.frontdesk') }}" class="btn btn-outline-secondary">Back to Front Desk</a></div></div>
                </div>
            </div>
        </form>
    </div>
</div>
@endsection
