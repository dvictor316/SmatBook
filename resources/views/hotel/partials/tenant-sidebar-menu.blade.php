@php
    $hotelServiceCenter = request()->route('center');
    $hotelLinkClass = fn (...$patterns) => request()->routeIs(...$patterns) ? 'active' : '';
    $hotelIsServiceCenter = fn ($name) => request()->routeIs('hotel.service_centers.show') && $hotelServiceCenter === $name;
@endphp

<li class="menu-title"><span>Dashboard</span></li>
<li class="{{ $hotelLinkClass('hotel.dashboard') }}"><a href="{{ route('hotel.dashboard') }}">Hotel Dashboard</a></li>

<li class="menu-title"><span>Front Office</span></li>
<li class="{{ $hotelLinkClass('hotel.frontdesk') }}"><a href="{{ route('hotel.frontdesk') }}">Front Desk / Room Board</a></li>
<li class="{{ $hotelLinkClass('hotel.rooms.calendar') }}"><a href="{{ route('hotel.rooms.calendar') }}">Room Calendar</a></li>
<li class="{{ $hotelLinkClass('hotel.availability.*') }}"><a href="{{ route('hotel.availability.index') }}">Availability Search</a></li>
<li class="{{ $hotelLinkClass('hotel.reservations.*') }}"><a href="{{ route('hotel.reservations.index') }}">Reservations</a></li>
<li class="{{ $hotelLinkClass('hotel.checkin.index') }}"><a href="{{ route('hotel.checkin.index') }}">Check-In</a></li>
<li class="{{ $hotelLinkClass('hotel.in_house') }}"><a href="{{ route('hotel.in_house') }}">Current Stays / In-House</a></li>
<li class="{{ $hotelLinkClass('hotel.checkout.index') }}"><a href="{{ route('hotel.checkout.index') }}">Checkout</a></li>
<li class="{{ $hotelLinkClass('hotel.guests') }}"><a href="{{ route('hotel.guests') }}">Guest Profiles</a></li>
<li class="{{ $hotelLinkClass('hotel.folios.*') }}"><a href="{{ route('hotel.folios.index') }}">Guest Folios</a></li>
<li class="{{ $hotelLinkClass('hotel.deposits') }}"><a href="{{ route('hotel.deposits') }}">Deposits / Payments</a></li>

<li class="menu-title"><span>Rooms</span></li>
<li class="{{ $hotelLinkClass('hotel.rooms.index', 'hotel.rooms.create', 'hotel.rooms.edit', 'hotel.rooms.status', 'hotel.rooms.show') }}"><a href="{{ route('hotel.rooms.index') }}">Rooms</a></li>
<li class="{{ $hotelLinkClass('hotel.room_types.*') }}"><a href="{{ route('hotel.room_types.index') }}">Room Types</a></li>
<li class="{{ $hotelLinkClass('hotel.rate_plans.*') }}"><a href="{{ route('hotel.rate_plans.index') }}">Rate Plans</a></li>
<li class="{{ $hotelLinkClass('hotel.housekeeping.*') }}"><a href="{{ route('hotel.housekeeping.index') }}">Housekeeping</a></li>
<li class="{{ $hotelLinkClass('hotel.maintenance.*') }}"><a href="{{ route('hotel.maintenance.index') }}">Maintenance</a></li>

<li class="menu-title"><span>Services</span></li>
<li class="{{ $hotelLinkClass('hotel.restaurant.pos') }}"><a href="{{ route('hotel.restaurant.pos') }}">Restaurant / POS</a></li>
<li class="{{ $hotelIsServiceCenter('bar') ? 'active' : '' }}"><a href="{{ route('hotel.service_centers.show', 'bar') }}">Bar</a></li>
<li class="{{ $hotelIsServiceCenter('spa') ? 'active' : '' }}"><a href="{{ route('hotel.service_centers.show', 'spa') }}">Spa</a></li>
<li class="{{ $hotelIsServiceCenter('gym') ? 'active' : '' }}"><a href="{{ route('hotel.service_centers.show', 'gym') }}">Gym</a></li>
<li class="{{ $hotelLinkClass('hotel.minibar.index') }}"><a href="{{ route('hotel.minibar.index') }}">Minibar</a></li>
<li class="{{ $hotelLinkClass('hotel.laundry.index') }}"><a href="{{ route('hotel.laundry.index') }}">Laundry</a></li>
<li class="{{ $hotelLinkClass('hotel.room_service.index') }}"><a href="{{ route('hotel.room_service.index') }}">Room Service</a></li>
<li class="{{ $hotelLinkClass('hotel.conference.index') }}"><a href="{{ route('hotel.conference.index') }}">Conference & Events</a></li>
<li class="{{ $hotelIsServiceCenter('ticketing') ? 'active' : '' }}"><a href="{{ route('hotel.service_centers.show', 'ticketing') }}">Ticketing / Events</a></li>

<li class="menu-title"><span>Finance & Operations</span></li>
<li class="{{ $hotelLinkClass('hotel.deposits') }}"><a href="{{ route('hotel.deposits') }}">Payments & Deposits</a></li>
<li class="{{ $hotelLinkClass('hotel.corporate_accounts.index') }}"><a href="{{ route('hotel.corporate_accounts.index') }}">Corporate Accounts</a></li>
<li class="{{ $hotelLinkClass('hotel.group_bookings.index') }}"><a href="{{ route('hotel.group_bookings.index') }}">Group Bookings</a></li>
<li class="{{ $hotelLinkClass('hotel.booking_sources.index') }}"><a href="{{ route('hotel.booking_sources.index') }}">Booking Sources</a></li>

<li class="menu-title"><span>Analytics & Admin</span></li>
<li class="{{ $hotelLinkClass('hotel.night_audit.*') }}"><a href="{{ route('hotel.night_audit.index') }}">Night Audit</a></li>
<li class="{{ $hotelLinkClass('hotel.reports.index') }}"><a href="{{ route('hotel.reports.index') }}">Hotel Reports</a></li>
<li class="{{ $hotelLinkClass('hotel.settings') }}"><a href="{{ route('hotel.settings') }}">Hotel Settings</a></li>
