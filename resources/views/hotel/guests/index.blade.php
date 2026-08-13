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
    .guest-row { display:grid; grid-template-columns:70px 1.1fr 1fr .7fr 1fr 1.2fr auto; gap:12px; align-items:center; padding:13px; border-bottom:1px solid #edf1f5; }
    .guest-row.header { background:#fbfbfb; color:#6b7280; text-transform:uppercase; font-size:12px; font-weight:700; }
    .guest-photo { width:48px; height:48px; border-radius:8px; background:#e5e7eb; display:flex; align-items:center; justify-content:center; font-weight:700; color:#334155; }
    .guest-tag { display:inline-flex; border-radius:999px; padding:5px 8px; background:#eef2ff; color:#3742a0; font-size:12px; font-weight:600; }
    .guest-alert { border:1px solid #e6dcc5; background:#fffdf5; border-radius:4px; padding:8px; font-size:12px; color:#5b4636; }
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
            <a href="{{ route('hotel.search') }}" class="btn btn-outline-primary">Search Guests</a>
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
                <div class="mt-3 d-grid gap-2"><a href="{{ route('hotel.reservations.create') }}" class="btn btn-primary btn-sm">Book Reservation</a><a href="{{ route('hotel.frontdesk') }}" class="btn btn-outline-dark btn-sm">Front Desk</a></div>
            </aside>

            <main class="crm-panel">
                <div class="guest-row header"><div>Image</div><div>Last Name</div><div>First Name</div><div>Stays</div><div>Spend</div><div>Recommendation</div><div>Note</div></div>
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
                        <div><div class="guest-alert">{{ (float)($guest->outstanding_balance ?? 0) > 0 ? 'Settle balance before next departure.' : 'Good profile for repeat booking and loyalty follow-up.' }}</div></div>
                        <div><button type="button" class="btn btn-sm btn-outline-secondary">Add Note</button></div>
                    </div>
                @empty
                    <div class="p-4 text-muted">No hotel guests found.</div>
                @endforelse
                @if($isPaginator)<div class="p-3">{{ $guests->links() }}</div>@endif
            </main>
        </div>
    </div>
</div>
@endsection
