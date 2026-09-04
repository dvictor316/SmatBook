@php
    $hotelServiceCenter = request()->route('center');
    $hotelLinkClass = fn (...$patterns) => request()->routeIs(...$patterns) ? 'active' : '';
    $hotelIsServiceCenter = fn ($name) => request()->routeIs('hotel.service_centers.show') && $hotelServiceCenter === $name;
@endphp

<li class="{{ $hotelLinkClass('hotel.dashboard') }}"><a href="{{ route('hotel.dashboard') }}"><i class="fe fe-home"></i><span>Hotel Dashboard</span></a></li>
<li class="{{ $hotelLinkClass('hotel.frontdesk') }}"><a href="{{ route('hotel.frontdesk') }}"><i class="fe fe-briefcase"></i><span>Front Desk</span></a></li>
<li class="{{ $hotelLinkClass('hotel.rooms.calendar') }}"><a href="{{ route('hotel.rooms.calendar') }}"><i class="fe fe-calendar"></i><span>Room Calendar</span></a></li>
<li class="{{ $hotelLinkClass('hotel.availability.*') }}"><a href="{{ route('hotel.availability.index') }}"><i class="fe fe-search"></i><span>Availability</span></a></li>
<li class="{{ $hotelLinkClass('hotel.reservations.*') }}"><a href="{{ route('hotel.reservations.index') }}"><i class="fe fe-book"></i><span>Reservations</span></a></li>
<li class="{{ $hotelLinkClass('hotel.checkin.index') }}"><a href="{{ route('hotel.checkin.index') }}"><i class="fe fe-log-in"></i><span>Check-In</span></a></li>
<li class="{{ $hotelLinkClass('hotel.in_house') }}"><a href="{{ route('hotel.in_house') }}"><i class="fe fe-users"></i><span>Current Stays</span></a></li>
<li class="{{ $hotelLinkClass('hotel.checkout.index') }}"><a href="{{ route('hotel.checkout.index') }}"><i class="fe fe-log-out"></i><span>Checkout</span></a></li>
<li class="{{ $hotelLinkClass('hotel.guests') }}"><a href="{{ route('hotel.guests') }}"><i class="fe fe-user"></i><span>Guest Profiles</span></a></li>
<li class="{{ $hotelLinkClass('hotel.folios.*') }}"><a href="{{ route('hotel.folios.index') }}"><i class="fe fe-file-text"></i><span>Guest Folios</span></a></li>
<li class="{{ $hotelLinkClass('hotel.deposits') }}"><a href="{{ route('hotel.deposits') }}"><i class="fe fe-credit-card"></i><span>Payments</span></a></li>
<li class="{{ $hotelLinkClass('hotel.rooms.index', 'hotel.rooms.create', 'hotel.rooms.edit', 'hotel.rooms.status', 'hotel.rooms.show') }}"><a href="{{ route('hotel.rooms.index') }}"><i class="fe fe-layers"></i><span>Rooms</span></a></li>
<li class="{{ $hotelLinkClass('hotel.room_types.*') }}"><a href="{{ route('hotel.room_types.index') }}"><i class="fe fe-grid"></i><span>Room Types</span></a></li>
<li class="{{ $hotelLinkClass('hotel.rate_plans.*') }}"><a href="{{ route('hotel.rate_plans.index') }}"><i class="fe fe-dollar-sign"></i><span>Rate Plans</span></a></li>
<li class="{{ $hotelLinkClass('hotel.housekeeping.*') }}"><a href="{{ route('hotel.housekeeping.index') }}"><i class="fe fe-check-square"></i><span>Housekeeping</span></a></li>
<li class="{{ $hotelLinkClass('hotel.maintenance.*') }}"><a href="{{ route('hotel.maintenance.index') }}"><i class="fe fe-tool"></i><span>Maintenance</span></a></li>
<li class="{{ $hotelLinkClass('hotel.restaurant.pos') }}"><a href="{{ route('hotel.restaurant.pos') }}"><i class="fe fe-shopping-cart"></i><span>Restaurant / POS</span></a></li>
<li class="{{ $hotelIsServiceCenter('bar') ? 'active' : '' }}"><a href="{{ route('hotel.service_centers.show', 'bar') }}"><i class="fe fe-compass"></i><span>Bar</span></a></li>
<li class="{{ $hotelIsServiceCenter('gym') ? 'active' : '' }}"><a href="{{ route('hotel.service_centers.show', 'gym') }}"><i class="fe fe-activity"></i><span>Gym</span></a></li>
<li class="{{ $hotelIsServiceCenter('spa') ? 'active' : '' }}"><a href="{{ route('hotel.service_centers.show', 'spa') }}"><i class="fe fe-heart"></i><span>Spa</span></a></li>
<li class="{{ $hotelIsServiceCenter('ticketing') ? 'active' : '' }}"><a href="{{ route('hotel.service_centers.show', 'ticketing') }}"><i class="fe fe-ticket"></i><span>Ticketing / Events</span></a></li>
<li class="{{ $hotelLinkClass('hotel.minibar.index') }}"><a href="{{ route('hotel.minibar.index') }}"><i class="fe fe-box"></i><span>Minibar</span></a></li>
<li class="{{ $hotelLinkClass('hotel.laundry.index') }}"><a href="{{ route('hotel.laundry.index') }}"><i class="fe fe-droplet"></i><span>Laundry</span></a></li>
<li class="{{ $hotelLinkClass('hotel.room_service.index') }}"><a href="{{ route('hotel.room_service.index') }}"><i class="fe fe-coffee"></i><span>Room Service</span></a></li>
<li class="{{ $hotelLinkClass('hotel.conference.index') }}"><a href="{{ route('hotel.conference.index') }}"><i class="fe fe-mic"></i><span>Conference & Events</span></a></li>
<li class="{{ $hotelLinkClass('hotel.corporate_accounts.index') }}"><a href="{{ route('hotel.corporate_accounts.index') }}"><i class="fe fe-briefcase"></i><span>Corporate Accounts</span></a></li>
<li class="{{ $hotelLinkClass('hotel.group_bookings.index') }}"><a href="{{ route('hotel.group_bookings.index') }}"><i class="fe fe-users"></i><span>Group Bookings</span></a></li>
<li class="{{ $hotelLinkClass('hotel.booking_sources.index') }}"><a href="{{ route('hotel.booking_sources.index') }}"><i class="fe fe-star"></i><span>Booking Sources</span></a></li>
<li class="{{ $hotelLinkClass('hotel.night_audit.*') }}"><a href="{{ route('hotel.night_audit.index') }}"><i class="fe fe-moon"></i><span>Night Audit</span></a></li>
<li class="{{ $hotelLinkClass('hotel.reports.index') }}"><a href="{{ route('hotel.reports.index') }}"><i class="fe fe-bar-chart"></i><span>Hotel Reports</span></a></li>
<li class="{{ $hotelLinkClass('hotel.settings') }}"><a href="{{ route('hotel.settings') }}"><i class="fe fe-settings"></i><span>Hotel Settings</span></a></li>
