@extends('layout.mainlayout')

@section('content')
<div class="page-wrapper">
    <div class="content container-fluid">
        <h3>Walk-In Check-In</h3>
        <form method="POST" action="{{ route('hotel.walkin.store') }}">
            @csrf
            <div class="row">
                <div class="col-md-6">
                    <select name="room_id" class="form-control">
                        @php
                            $propertyId = App\Models\HotelProperty::where('company_id', auth()->user()->company_id)
                                ->when(auth()->user()->branch_id, fn($q) => $q->where('branch_id', auth()->user()->branch_id))
                                ->value('id');
                        @endphp
                        @foreach(App\Models\HotelRoom::where('company_id', auth()->user()->company_id)->when($propertyId, fn($q) => $q->where('property_id', $propertyId))->where('is_active', true)->get() as $room)
                            <option value="{{ $room->id }}">{{ $room->room_number }} - {{ $room->type?->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-6"><input type="datetime-local" name="expected_checkout_at" class="form-control" required></div>
            </div>
            <div class="mt-2"><button class="btn btn-primary">Check In</button></div>
        </form>
    </div>
</div>
@endsection
