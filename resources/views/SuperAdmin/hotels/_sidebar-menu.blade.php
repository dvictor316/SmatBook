@php
    $hotelPanel = request('panel', 'overview');
    $hotelMenuPanels = [
        'overview' => 'Hotel Dashboard',
        'tenants' => 'Hotel Tenants',
        'properties' => 'Properties',
        'rooms' => 'Front Desk / Room Board',
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
            {{ $hotelMenuLabel }}
        </a>
    </li>
@endforeach
