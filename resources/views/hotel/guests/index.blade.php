@extends('layout.mainlayout')

@section('style')
<style>
    .guest-crm { background:#f1f2f4; color:#2d3748; }
    .crm-top { display:grid; grid-template-columns:220px repeat(4,minmax(0,1fr)); gap:14px; align-items:stretch; margin-bottom:18px; }
    .crm-profile-rail { display:flex; gap:12px; align-items:center; padding:16px; }
    .crm-avatar { width:64px; height:64px; border-radius:50%; background:#22343b; color:#fff; display:flex; align-items:center; justify-content:center; font-size:24px; font-weight:700; }
    .crm-stat, .crm-profile-rail, .crm-panel { background:#fff; border:1px solid #e1e5eb; border-radius:6px; box-shadow:0 6px 16px rgba(15,23,42,.035); }
    .crm-stat { padding:18px; min-height:92px; }
    .crm-stat small { color:#6b7280; text-transform:uppercase; letter-spacing:.04em; }
    .crm-tabs { display:flex; gap:24px; border-bottom:1px solid #dce2ea; margin-bottom:18px; padding-left:4px; }
    .crm-tabs span { padding:12px 0; color:#6b7280; font-weight:600; }
    .crm-tabs .active { color:#315bdc; border-bottom:4px solid #6366f1; }
    .crm-shell { display:grid; grid-template-columns:260px minmax(0,1fr); gap:18px; }
    .crm-side { padding:18px; }
    .crm-side .field { display:flex; justify-content:space-between; border-bottom:1px solid #edf1f5; padding:11px 0; color:#6b7280; }
    .guest-row { display:grid; grid-template-columns:70px 1.1fr 1fr .7fr 1fr 1.2fr 1.2fr; gap:12px; align-items:center; padding:13px; border-bottom:1px solid #edf1f5; }
    .guest-row.header { background:#fbfbfb; color:#6b7280; text-transform:uppercase; font-size:12px; font-weight:700; }
    .guest-photo { width:48px; height:48px; border-radius:8px; background:#e5e7eb; display:flex; align-items:center; justify-content:center; font-weight:700; color:#334155; }
    .guest-tag { display:inline-flex; border-radius:999px; padding:5px 8px; background:#eef2ff; color:#3742a0; font-size:12px; font-weight:600; }
    .guest-alert { border:1px solid #e6dcc5; background:#fffdf5; border-radius:4px; padding:8px; font-size:12px; color:#5b4636; }
    .guest-actions { display:grid; gap:6px; }
    .guest-note-form { display:grid; grid-template-columns:minmax(0,1fr) auto; gap:6px; margin-top:7px; }
    .guest-note-form textarea { min-height:38px; resize:vertical; font-size:12px; }
    @media(max-width:1199px){.crm-top,.crm-shell{grid-template-columns:1fr}.guest-row{grid-template-columns:60px 1fr}.guest-row.header{display:none}.guest-row > div{min-width:0}}
</style>
@endsection

@section('content')
@php
    $isPaginator = $guests instanceof \Illuminate\Pagination\LengthAwarePaginator;
    $guestCollection = $isPaginator ? $guests->getCollection() : collect($guests);
    $leadGuest = $guestCollection->first();
    $totalStays = $guestCollection->sum(fn($guest) => (int) ($guest->total_stays ?? 0));
    $totalSpend = $guestCollection->sum(fn($guest) => (float) ($guest->total_spend ?? 0));
    $outstanding = $guestCollection->sum(fn($guest) => (float) ($guest->outstanding_balance ?? 0));
@endphp
<div class="page-wrapper guest-crm">
    <div class="content container-fluid">
        <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
            <div><h3 class="mb-1">Guest CRM</h3><p class="text-muted mb-0">Hotel guest profiles, stay history, loyalty and recommendations.</p></div>
            <div class="d-flex flex-wrap gap-2">
                <a href="{{ route('hotel.search') }}" class="btn btn-outline-primary">Search Guests</a>
                <a href="{{ route('hotel.walkin.create') }}" class="btn btn-primary">Walk-In</a>
                <button type="button" onclick="window.print()" class="btn btn-outline-dark">Print CRM</button>
            </div>
        </div>

        <div class="crm-top">
            <div class="crm-profile-rail"><div class="crm-avatar">{{ strtoupper(substr((string)($leadGuest?->customer_name ?? $leadGuest?->name ?? 'G'),0,1)) }}</div><div><strong>{{ $leadGuest?->customer_name ?? $leadGuest?->name ?? 'Guest Profiles' }}</strong><div class="small text-muted">{{ $leadGuest?->email ?? 'CRM Directory' }}</div></div></div>
            <div class="crm-stat"><small>Lifetime Stays</small><h4 class="mb-0">{{ number_format($totalStays) }}</h4></div>
            <div class="crm-stat"><small>Lifetime Spend</small><h4 class="mb-0">{{ number_format($totalSpend, 2) }}</h4></div>
            <div class="crm-stat"><small>Outstanding</small><h4 class="mb-0 {{ $outstanding > 0 ? 'text-danger' : 'text-success' }}">{{ number_format($outstanding, 2) }}</h4></div>
            <div class="crm-stat"><small>Profiles Loaded</small><h4 class="mb-0">{{ $guestCollection->count() }}</h4></div>
        </div>

        <div class="crm-tabs"><span class="active">Profile</span><span>Stays</span><span>Engagement</span><span>Notes</span></div>

        <div class="crm-shell">
            <aside class="crm-panel crm-side">
                <h5>Guest Filters</h5>
                <div class="field"><span>Status</span><strong>Active</strong></div>
                <div class="field"><span>Loyalty</span><strong>All</strong></div>
                <div class="field"><span>Balance</span><strong>{{ $outstanding > 0 ? 'Has Due' : 'Clear' }}</strong></div>
                <div class="field"><span>Source</span><strong>Hotel PMS</strong></div>
                <div class="mt-3 d-grid gap-2"><a href="{{ route('hotel.reservations.create') }}" class="btn btn-primary btn-sm">Book Reservation</a><a href="{{ route('hotel.walkin.create') }}" class="btn btn-outline-primary btn-sm">Walk-In Check-In</a><a href="{{ route('hotel.frontdesk') }}" class="btn btn-outline-dark btn-sm">Front Desk</a></div>
            </aside>

            <main class="crm-panel">
                <div class="guest-row header"><div>Image</div><div>Last Name</div><div>First Name</div><div>Stays</div><div>Spend</div><div>Recommendation / Notes</div><div>Actions</div></div>
                @forelse($guests as $guest)
                    @php
                        $fullName = (string) ($guest->customer_name ?? $guest->name ?? 'Guest');
                        $parts = preg_split('/\s+/', trim($fullName));
                        $first = $parts[0] ?? $fullName;
                        $last = count($parts) > 1 ? end($parts) : '-';
                    @endphp
                    <div class="guest-row">
                        <div><div class="guest-photo">{{ strtoupper(substr($fullName,0,1)) }}</div></div>
                        <div><strong>{{ $last }}</strong><div class="small text-muted">{{ $guest->phone ?: 'No phone' }}</div></div>
                        <div>{{ $first }}<div class="small text-muted">{{ $guest->email ?: 'No email' }}</div></div>
                        <div><span class="guest-tag">{{ $guest->total_stays ?? 0 }} stays</span></div>
                        <div>{{ number_format((float) ($guest->total_spend ?? 0), 2) }}<div class="small text-muted">Due {{ number_format((float) ($guest->outstanding_balance ?? 0), 2) }}</div></div>
                        <div>
                            <div class="guest-alert">{{ (float)($guest->outstanding_balance ?? 0) > 0 ? 'Settle balance before next departure.' : (($guest->notes ?? null) ?: 'Good profile for repeat booking and loyalty follow-up.') }}</div>
                            <form method="POST" action="{{ route('hotel.guests.note', $guest) }}" class="guest-note-form">
                                @csrf
                                <textarea name="notes" class="form-control" placeholder="Guest preference, alert, loyalty note">{{ $guest->notes }}</textarea>
                                <button class="btn btn-sm btn-outline-primary">Save</button>
                            </form>
                        </div>
                        <div class="guest-actions">
                            <a href="{{ route('hotel.reservations.create', ['customer_id' => $guest->id]) }}" class="btn btn-sm btn-primary">Book</a>
                            @if($guest->open_folio_id)
                                <a href="{{ route('hotel.folios.show', $guest->open_folio_id) }}" class="btn btn-sm btn-outline-primary">Folio</a>
                            @else
                                <a href="{{ route('hotel.walkin.create', ['customer_id' => $guest->id]) }}" class="btn btn-sm btn-outline-secondary">Check In</a>
                            @endif
                            @if($guest->latest_stay_id)
                                <a href="{{ route('hotel.checkout.index', ['stay_id' => $guest->latest_stay_id]) }}" class="btn btn-sm btn-outline-dark">Checkout</a>
                            @endif
                            <button type="button" class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#guestProfile{{ $guest->id }}">Profile & ID</button>
                        </div>
                    </div>
                    @php $profile = $guest->hotel_profile; @endphp
                    <div class="modal fade" id="guestProfile{{ $guest->id }}" tabindex="-1"><div class="modal-dialog modal-lg"><form method="POST" action="{{ route('hotel.guests.profile', $guest) }}" class="modal-content">@csrf
                        <div class="modal-header"><h5 class="modal-title">{{ $fullName }} - Identity & Preferences</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
                        <div class="modal-body"><div class="row g-3">
                            <div class="col-md-4"><label class="form-label">Nationality</label><input name="nationality" value="{{ $profile?->nationality }}" class="form-control"></div>
                            <div class="col-md-4"><label class="form-label">Date of Birth</label><input type="date" name="date_of_birth" value="{{ $profile?->date_of_birth?->toDateString() }}" class="form-control"></div>
                            <div class="col-md-4"><label class="form-label">Gender</label><select name="gender" class="form-select"><option value="">Not recorded</option>@foreach(['female','male','non_binary','prefer_not_to_say'] as $value)<option value="{{ $value }}" @selected($profile?->gender === $value)>{{ ucwords(str_replace('_',' ',$value)) }}</option>@endforeach</select></div>
                            <div class="col-md-4"><label class="form-label">ID Type</label><select name="id_type" class="form-select"><option value="">Select</option>@foreach(['passport','national_id','drivers_licence','residence_permit','other'] as $value)<option value="{{ $value }}" @selected($profile?->id_type === $value)>{{ ucwords(str_replace('_',' ',$value)) }}</option>@endforeach</select></div>
                            <div class="col-md-4"><label class="form-label">ID Number</label><input name="id_number" value="{{ $profile?->id_number }}" class="form-control"></div>
                            <div class="col-md-4"><label class="form-label">ID Expiry</label><input type="date" name="id_expiry_date" value="{{ $profile?->id_expiry_date?->toDateString() }}" class="form-control"></div>
                            <div class="col-md-4"><label class="form-label">Issuing Country</label><input name="id_issuing_country" value="{{ $profile?->id_issuing_country }}" class="form-control"></div>
                            <div class="col-md-4"><label class="form-label">Loyalty Number</label><input name="loyalty_number" value="{{ $profile?->loyalty_number }}" class="form-control"></div>
                            <div class="col-md-4"><label class="form-label">Guest Tier</label><select name="vip_status" class="form-select">@foreach(['standard','silver','gold','platinum','vip'] as $value)<option value="{{ $value }}" @selected(($profile?->vip_status ?? 'standard') === $value)>{{ ucfirst($value) }}</option>@endforeach</select></div>
                            <div class="col-md-6"><label class="form-label">Preferences</label><textarea name="preferences" class="form-control">{{ $profile?->preferences }}</textarea></div>
                            <div class="col-md-6"><label class="form-label">Allergies / Safety Alerts</label><textarea name="allergies" class="form-control">{{ $profile?->allergies }}</textarea></div>
                            <div class="col-md-4"><label><input type="checkbox" name="do_not_rent" value="1" @checked($profile?->do_not_rent)> Do not rent</label></div>
                            <div class="col-md-8"><input name="do_not_rent_reason" value="{{ $profile?->do_not_rent_reason }}" class="form-control" placeholder="Reason when do-not-rent is selected"></div>
                        </div></div><div class="modal-footer"><button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button><button class="btn btn-primary">Save Profile</button></div>
                    </form></div></div>
                @empty
                    <div class="p-4 text-muted">No hotel guests found.</div>
                @endforelse
                @if($isPaginator)<div class="p-3">{{ $guests->links() }}</div>@endif
            </main>
        </div>
    </div>
</div>
@endsection
