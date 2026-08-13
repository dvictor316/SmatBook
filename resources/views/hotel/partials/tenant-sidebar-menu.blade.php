<li class="menu-title"><span>Front Office</span></li>
<li><a href="{{ route('hotel.dashboard') }}">Hotel Dashboard</a></li>
<li><a href="{{ route('hotel.frontdesk') }}">Front Desk / Room Board</a></li>
<li><a href="{{ route('hotel.rooms.calendar') }}">Room Calendar</a></li>
<li><a href="{{ route('hotel.availability.index') }}">Availability Search</a></li>
<li><a href="{{ route('hotel.reservations.index') }}">Reservations</a></li>
<li><a href="{{ route('hotel.checkin.index') }}">Check-In</a></li>
<li><a href="{{ route('hotel.in_house') }}">Current Stays / In-House</a></li>
<li><a href="{{ route('hotel.checkout.index') }}">Checkout</a></li>
<li><a href="{{ route('hotel.guests') }}">Guest Profiles</a></li>
<li><a href="{{ route('hotel.folios.index') }}">Guest Folios</a></li>
<li><a href="{{ route('hotel.deposits') }}">Deposits / Payments</a></li>

<li class="menu-title"><span>Rooms</span></li>
<li><a href="{{ route('hotel.rooms.index') }}">Rooms</a></li>
<li><a href="{{ route('hotel.room_types.index') }}">Room Types</a></li>
<li><a href="{{ route('hotel.rate_plans.index') }}">Rate Plans</a></li>
<li><a href="{{ route('hotel.rooms.status') }}">Room Status</a></li>
<li><a href="{{ route('hotel.housekeeping.index') }}">Housekeeping</a></li>
<li><a href="{{ route('hotel.maintenance.index') }}">Maintenance</a></li>

<li class="menu-title"><span>Service Centers</span></li>
<li><a href="{{ route('hotel.restaurant.pos') }}">Restaurant / POS</a></li>
<li><a href="{{ route('hotel.service_centers.show', 'bar') }}">Bar</a></li>
<li><a href="{{ route('hotel.service_centers.show', 'gym') }}">Gym</a></li>
<li><a href="{{ route('hotel.service_centers.show', 'spa') }}">Spa</a></li>
<li><a href="{{ route('hotel.minibar.index') }}">Minibar</a></li>
<li><a href="{{ route('hotel.laundry.index') }}">Laundry</a></li>
<li><a href="{{ route('hotel.room_service.index') }}">Room Service</a></li>
<li><a href="{{ route('hotel.conference.index') }}">Conference & Events</a></li>

<li class="menu-title"><span>Controls & Reports</span></li>
<li><a href="{{ route('hotel.corporate_accounts.index') }}">Corporate Accounts</a></li>
<li><a href="{{ route('hotel.group_bookings.index') }}">Group Bookings</a></li>
<li><a href="{{ route('hotel.booking_sources.index') }}">Booking Sources</a></li>
<li><a href="{{ route('hotel.night_audit.index') }}">Night Audit</a></li>
<li><a href="{{ route('hotel.reports.index') }}">Hotel Reports</a></li>
