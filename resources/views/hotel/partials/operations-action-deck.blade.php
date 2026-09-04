@php
    $hotelActionDeckContext = $context ?? 'hotel';
    $hotelActionGroups = [
        [
            'label' => 'Front Office',
            'items' => [
                ['label' => 'New Reservation', 'hint' => 'Create a dated booking', 'icon' => 'fa-calendar-plus', 'route' => 'hotel.reservations.create'],
                ['label' => 'Walk-In Guest', 'hint' => 'Sell a room now', 'icon' => 'fa-person-walking-luggage', 'route' => 'hotel.walkin.create'],
                ['label' => 'Check In', 'hint' => 'Arrivals and room handover', 'icon' => 'fa-right-to-bracket', 'route' => 'hotel.checkin.index'],
                ['label' => 'Check Out', 'hint' => 'Settle folio and release room', 'icon' => 'fa-right-from-bracket', 'route' => 'hotel.checkout.index'],
            ],
        ],
        [
            'label' => 'Rooms',
            'items' => [
                ['label' => 'Room Board', 'hint' => 'Live front desk rack', 'icon' => 'fa-border-all', 'route' => 'hotel.frontdesk'],
                ['label' => 'Add Room', 'hint' => 'Create room inventory', 'icon' => 'fa-square-plus', 'route' => 'hotel.rooms.create'],
                ['label' => 'Room Calendar', 'hint' => 'Timeline and blocks', 'icon' => 'fa-calendar-days', 'route' => 'hotel.rooms.calendar'],
                ['label' => 'Housekeeping', 'hint' => 'Clean, inspect, assign', 'icon' => 'fa-broom', 'route' => 'hotel.housekeeping.index'],
            ],
        ],
        [
            'label' => 'Guest Revenue',
            'items' => [
                ['label' => 'Guest Folios', 'hint' => 'Charges and balances', 'icon' => 'fa-file-invoice-dollar', 'route' => 'hotel.folios.index'],
                ['label' => 'Bar Order', 'hint' => 'Post bar charges', 'icon' => 'fa-martini-glass-citrus', 'route' => 'hotel.service_centers.show', 'params' => ['bar']],
                ['label' => 'Spa Booking', 'hint' => 'Post wellness services', 'icon' => 'fa-spa', 'route' => 'hotel.service_centers.show', 'params' => ['spa']],
                ['label' => 'Gym Access', 'hint' => 'Passes and memberships', 'icon' => 'fa-dumbbell', 'route' => 'hotel.service_centers.show', 'params' => ['gym']],
                ['label' => 'Ticket/Event', 'hint' => 'Sell event access', 'icon' => 'fa-ticket', 'route' => 'hotel.service_centers.show', 'params' => ['ticketing']],
            ],
        ],
        [
            'label' => 'Controls',
            'items' => [
                ['label' => 'Maintenance', 'hint' => 'Open room work order', 'icon' => 'fa-screwdriver-wrench', 'route' => 'hotel.maintenance.index'],
                ['label' => 'Availability', 'hint' => 'Search rooms to sell', 'icon' => 'fa-magnifying-glass-chart', 'route' => 'hotel.availability.index'],
                ['label' => 'Night Audit', 'hint' => 'Close business day', 'icon' => 'fa-moon', 'route' => 'hotel.night_audit.index'],
                ['label' => 'Reports', 'hint' => 'Revenue and occupancy', 'icon' => 'fa-chart-line', 'route' => 'hotel.reports.index'],
            ],
        ],
    ];
@endphp

<style>
    .hotel-action-deck { background:#fff; border:1px solid #d8e2ee; border-radius:8px; padding:14px; margin-bottom:18px; box-shadow:0 12px 28px rgba(15,23,42,.06); }
    .hotel-action-deck-head { display:flex; justify-content:space-between; align-items:end; gap:12px; flex-wrap:wrap; margin-bottom:12px; }
    .hotel-action-deck-head h4 { margin:0; color:#061b33; font-weight:800; font-size:18px; }
    .hotel-action-deck-head p { margin:3px 0 0; color:#64748b; }
    .hotel-action-grid { display:grid; grid-template-columns:repeat(4,minmax(0,1fr)); gap:10px; }
    .hotel-action-group { position:relative; overflow:hidden; border:1px solid #e2eaf4; border-radius:8px; padding:10px; background-color:#f8fbff; background-image:linear-gradient(90deg,rgba(255,255,255,.94),rgba(255,255,255,.8)),url('/assets/img/hotel-keto/banner2.jpg'); background-size:cover; background-position:center; }
    .hotel-action-group.front-office { background-image:linear-gradient(90deg,rgba(255,255,255,.94),rgba(255,255,255,.8)),url('/assets/img/hotel-keto/banner2.jpg'); }
    .hotel-action-group.rooms { background-image:linear-gradient(90deg,rgba(255,255,255,.94),rgba(255,255,255,.8)),url('/assets/img/hotel-keto/room1.jpg'); }
    .hotel-action-group.guest-revenue { background-image:linear-gradient(90deg,rgba(255,255,255,.94),rgba(255,255,255,.8)),url('/assets/img/hotel-keto/gallery5.jpg'); }
    .hotel-action-group.controls { background-image:linear-gradient(90deg,rgba(255,255,255,.94),rgba(255,255,255,.8)),url('/assets/img/hotel-keto/room6.jpg'); }
    .hotel-action-group-title { display:block; color:#334155; text-transform:uppercase; letter-spacing:.08em; font-size:11px; font-weight:800; margin-bottom:8px; position:relative; }
    .hotel-action-list { display:grid; gap:7px; }
    .hotel-action-btn { min-height:58px; display:grid; grid-template-columns:34px minmax(0,1fr); gap:9px; align-items:center; text-decoration:none; color:#10233f; background:#fff; border:1px solid #d9e3ee; border-radius:8px; padding:8px 10px; transition:transform .16s ease, border-color .16s ease, box-shadow .16s ease; }
    .hotel-action-btn:hover { color:#10233f; border-color:#0b5fb8; transform:translateY(-1px); box-shadow:0 10px 20px rgba(11,95,184,.12); }
    .hotel-action-btn i { width:34px; height:34px; display:grid; place-items:center; border-radius:8px; color:#fff; background:#0b5fb8; }
    .hotel-action-btn strong { display:block; font-size:13px; line-height:1.15; color:#061b33; }
    .hotel-action-btn span { display:block; font-size:11px; color:#64748b; margin-top:2px; line-height:1.25; }
    @media(max-width:1199px){.hotel-action-grid{grid-template-columns:repeat(2,1fr)}}
    @media(max-width:767px){.hotel-action-grid{grid-template-columns:1fr}.hotel-action-btn{min-height:52px}}
</style>

<section class="hotel-action-deck">
    <div class="hotel-action-deck-head">
        <div>
            <h4>{{ $title ?? 'Hotel Operations Command' }}</h4>
            <p>{{ $subtitle ?? 'Fast actions for front desk, room control, guest revenue, housekeeping and audit.' }}</p>
        </div>
        <span class="badge bg-light text-dark">{{ ucfirst($hotelActionDeckContext) }} workflow</span>
    </div>
    <div class="hotel-action-grid">
        @foreach($hotelActionGroups as $group)
            <div class="hotel-action-group {{ \Illuminate\Support\Str::slug($group['label']) }}">
                <span class="hotel-action-group-title">{{ $group['label'] }}</span>
                <div class="hotel-action-list">
                    @foreach($group['items'] as $item)
                        @if(Route::has($item['route']))
                            <a class="hotel-action-btn" href="{{ route($item['route'], $item['params'] ?? []) }}">
                                <i class="fas {{ $item['icon'] }}"></i>
                                <span><strong>{{ $item['label'] }}</strong><span>{{ $item['hint'] }}</span></span>
                            </a>
                        @endif
                    @endforeach
                </div>
            </div>
        @endforeach
    </div>
</section>
