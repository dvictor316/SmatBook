@php
    $hotelPanel = request('panel', 'overview');
    $hotelMenuPanels = [
        'progress' => 'Upgrade Progress',
        'overview' => 'Hotel Dashboard',
        'tenants' => 'Hotel Tenants',
        'properties' => 'Properties',
        'rooms' => 'Front Desk / Room Board',
        'room_gallery' => 'Room Gallery',
        'room_calendar' => 'Room Calendar',
        'room_types' => 'Room Types',
        'reservations' => 'Reservations',
        'availability' => 'Availability Search',
        'check_in' => 'Check-In',
        'stays' => 'Current Stays / In-House',
        'checkout' => 'Checkout',
        'guests' => 'Guest Profiles',
        'folios' => 'Guest Folios',
        'deposits' => 'Deposits / Payments',
        'housekeeping' => 'Housekeeping',
        'maintenance' => 'Maintenance',
        'service_restaurant' => 'Restaurant / POS',
        'service_bar' => 'Bar',
        'service_gym' => 'Gym',
        'service_spa' => 'Spa',
        'service_ticketing' => 'Ticketing / Events',
        'service_minibar' => 'Minibar',
        'service_laundry' => 'Laundry',
        'service_room_service' => 'Room Service',
        'night_audits' => 'Night Audit',
        'reports' => 'Hotel Reports',
    ];
@endphp
@foreach($hotelMenuPanels as $hotelMenuPanel => $hotelMenuLabel)
    <li>
        <a class="{{ $hotelPanel === $hotelMenuPanel ? 'active' : '' }}" href="{{ route('super_admin.hotels.index', array_merge($routeParams ?? [], $hotelMenuPanel === 'overview' ? [] : ['panel' => $hotelMenuPanel])) }}">
            @if($hotelMenuPanel === 'progress')<i class="fas fa-list-check me-1"></i>@endif
            {{ $hotelMenuLabel }}
            @if(in_array($hotelMenuPanel, ['room_gallery','service_ticketing'], true))
                <span class="badge bg-warning text-dark ms-1">New</span>
            @endif
        </a>
    </li>
@endforeach
