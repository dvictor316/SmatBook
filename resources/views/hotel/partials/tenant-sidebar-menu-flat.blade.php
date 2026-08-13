<li class="{{ Request::routeIs('hotel.dashboard') ? 'active' : '' }}"><a href="{{ route('hotel.dashboard') }}"><i class="fe fe-home"></i><span>Hotel Dashboard</span></a></li>
<li class="{{ Request::routeIs('hotel.frontdesk') ? 'active' : '' }}"><a href="{{ route('hotel.frontdesk') }}"><i class="fe fe-briefcase"></i><span>Front Desk</span></a></li>
<li class="{{ Request::routeIs('hotel.rooms.calendar') ? 'active' : '' }}"><a href="{{ route('hotel.rooms.calendar') }}"><i class="fe fe-calendar"></i><span>Room Calendar</span></a></li>
<li class="{{ Request::routeIs('hotel.availability.*') ? 'active' : '' }}"><a href="{{ route('hotel.availability.index') }}"><i class="fe fe-search"></i><span>Availability</span></a></li>
<li class="{{ Request::routeIs('hotel.reservations.*') ? 'active' : '' }}"><a href="{{ route('hotel.reservations.index') }}"><i class="fe fe-book"></i><span>Reservations</span></a></li>
<li class="{{ Request::routeIs('hotel.checkin.index') ? 'active' : '' }}"><a href="{{ route('hotel.checkin.index') }}"><i class="fe fe-log-in"></i><span>Check-In</span></a></li>
<li class="{{ Request::routeIs('hotel.in_house') ? 'active' : '' }}"><a href="{{ route('hotel.in_house') }}"><i class="fe fe-users"></i><span>Current Stays</span></a></li>
<li class="{{ Request::routeIs('hotel.checkout.index') ? 'active' : '' }}"><a href="{{ route('hotel.checkout.index') }}"><i class="fe fe-log-out"></i><span>Checkout</span></a></li>
<li class="{{ Request::routeIs('hotel.guests') ? 'active' : '' }}"><a href="{{ route('hotel.guests') }}"><i class="fe fe-user"></i><span>Guest Profiles</span></a></li>
<li class="{{ Request::routeIs('hotel.folios.*') ? 'active' : '' }}"><a href="{{ route('hotel.folios.index') }}"><i class="fe fe-file-text"></i><span>Guest Folios</span></a></li>
<li class="{{ Request::routeIs('hotel.deposits') ? 'active' : '' }}"><a href="{{ route('hotel.deposits') }}"><i class="fe fe-credit-card"></i><span>Deposits / Payments</span></a></li>
<li class="{{ Request::routeIs('hotel.rooms.*') && !Request::routeIs('hotel.rooms.calendar') ? 'active' : '' }}"><a href="{{ route('hotel.rooms.index') }}"><i class="fe fe-layers"></i><span>Rooms</span></a></li>
<li class="{{ Request::routeIs('hotel.room_types.*') ? 'active' : '' }}"><a href="{{ route('hotel.room_types.index') }}"><i class="fe fe-grid"></i><span>Room Types</span></a></li>
<li class="{{ Request::routeIs('hotel.rate_plans.*') ? 'active' : '' }}"><a href="{{ route('hotel.rate_plans.index') }}"><i class="fe fe-dollar-sign"></i><span>Rate Plans</span></a></li>
<li class="{{ Request::routeIs('hotel.housekeeping.*') ? 'active' : '' }}"><a href="{{ route('hotel.housekeeping.index') }}"><i class="fe fe-check-square"></i><span>Housekeeping</span></a></li>
<li class="{{ Request::routeIs('hotel.maintenance.*') ? 'active' : '' }}"><a href="{{ route('hotel.maintenance.index') }}"><i class="fe fe-tool"></i><span>Maintenance</span></a></li>
<li class="{{ Request::routeIs('hotel.restaurant.pos') ? 'active' : '' }}"><a href="{{ route('hotel.restaurant.pos') }}"><i class="fe fe-shopping-cart"></i><span>Restaurant / POS</span></a></li>
<li class="{{ Request::routeIs('hotel.service_centers.show') ? 'active' : '' }}"><a href="{{ route('hotel.service_centers.show', 'bar') }}"><i class="fe fe-compass"></i><span>Bar / Gym / Spa</span></a></li>
<li class="{{ Request::routeIs('hotel.minibar.index') ? 'active' : '' }}"><a href="{{ route('hotel.minibar.index') }}"><i class="fe fe-box"></i><span>Minibar</span></a></li>
<li class="{{ Request::routeIs('hotel.laundry.index') ? 'active' : '' }}"><a href="{{ route('hotel.laundry.index') }}"><i class="fe fe-droplet"></i><span>Laundry</span></a></li>
<li class="{{ Request::routeIs('hotel.room_service.index') ? 'active' : '' }}"><a href="{{ route('hotel.room_service.index') }}"><i class="fe fe-coffee"></i><span>Room Service</span></a></li>
<li class="{{ Request::routeIs('hotel.night_audit.*') ? 'active' : '' }}"><a href="{{ route('hotel.night_audit.index') }}"><i class="fe fe-moon"></i><span>Night Audit</span></a></li>
<li class="{{ Request::routeIs('hotel.reports.index') ? 'active' : '' }}"><a href="{{ route('hotel.reports.index') }}"><i class="fe fe-bar-chart"></i><span>Hotel Reports</span></a></li>
