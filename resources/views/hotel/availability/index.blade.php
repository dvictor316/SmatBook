@extends('layout.mainlayout')

@section('style')
<style>
    .availability-search { background:#f4f7fb; color:#172033; }
    .av-hero { background:linear-gradient(135deg,#0b2f54,#0f766e); color:#fff; border-radius:16px; padding:22px; margin-bottom:16px; }
    .av-hero h3 { color:#fff; margin:0; font-size:28px; font-weight:900; }
    .av-card { background:#fff; border:1px solid #dce4ef; border-radius:14px; box-shadow:0 10px 28px rgba(15,23,42,.06); }
    .av-form { display:grid; grid-template-columns:repeat(5,minmax(0,1fr)); gap:12px; }
    .av-steps { display:grid; grid-template-columns:repeat(4,minmax(0,1fr)); gap:12px; margin-top:16px; }
    .av-step { padding:14px; border-radius:12px; background:#fff; border:1px solid #dce4ef; }
    .av-step strong { display:block; color:#0b5fb8; }
    @media(max-width:991px){.av-form,.av-steps{grid-template-columns:1fr 1fr}}
    @media(max-width:575px){.av-form,.av-steps{grid-template-columns:1fr}}
</style>
@endsection

@section('content')
<div class="page-wrapper availability-search">
    <div class="content container-fluid">
        <section class="av-hero">
            <h3>Availability Search</h3>
            <p class="mb-0">Find sellable rooms, calculate stay nights, and move directly into reservation or walk-in booking.</p>
        </section>
        <div class="av-card p-4">
            <form method="POST" action="{{ route('hotel.availability.search') }}" class="av-form align-items-end">
                @csrf
                <div><label class="form-label">Check In</label><input type="date" name="arrival_date" class="form-control" required></div>
                <div><label class="form-label">Check Out</label><input type="date" name="departure_date" class="form-control" required></div>
                <div><label class="form-label">Adults</label><input type="number" name="adults" min="1" value="1" class="form-control"></div>
                <div><label class="form-label">Children</label><input type="number" name="children" min="0" value="0" class="form-control"></div>
                <div><label class="form-label">Property</label><input type="hidden" name="property_id" value="{{ $property?->id }}"><input type="text" class="form-control" value="{{ $property?->name ?? 'Current Property' }}" disabled></div>
                <div class="d-flex gap-2 flex-wrap" style="grid-column:1/-1"><button class="btn btn-primary">Search Available Rooms</button><a href="{{ route('hotel.rooms.calendar') }}" class="btn btn-outline-secondary">Open Calendar</a><a href="{{ route('hotel.frontdesk') }}" class="btn btn-outline-dark">Front Desk</a></div>
            </form>
        </div>
        <div class="av-steps"><div class="av-step"><strong>1 Search</strong><span class="text-muted">Date, guests and property.</span></div><div class="av-step"><strong>2 Select</strong><span class="text-muted">Choose available room/rate.</span></div><div class="av-step"><strong>3 Book</strong><span class="text-muted">Create reservation or walk-in.</span></div><div class="av-step"><strong>4 Post</strong><span class="text-muted">Deposits and folio flow into accounting.</span></div></div>
    </div>
</div>
@endsection
