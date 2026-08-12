<ul>
    <li class="menu-title"><span>Starter</span></li>

    <li class="{{ Request::is('home', 'dashboard') ? 'active' : '' }}">
        <a href="{{ route('home') }}">
            <i class="fe fe-home"></i>
            <span>Dashboard</span>
        </a>
    </li>

    <li class="{{ Request::routeIs('sales.showPos') ? 'active' : '' }}">
        <a href="{{ route('sales.showPos') }}">
            <i class="fe fe-shopping-cart"></i>
            <span>New Sale</span>
        </a>
    </li>

    <li class="{{ Request::routeIs('pos.reports') ? 'active' : '' }}">
        <a href="{{ route('pos.reports') }}">
            <i class="fe fe-bar-chart-2"></i>
            <span>POS Reports</span>
        </a>
    </li>

    <li class="{{ Request::routeIs('product-list') ? 'active' : '' }}">
        <a href="{{ route('product-list') }}">
            <i class="fe fe-package"></i>
            <span>Products</span>
        </a>
    </li>

    @if(\App\Support\HotelAccess::userIsHotelTenant(auth()->user()))
        <li class="menu-title"><span>Hotel</span></li>
        <li class="{{ Request::routeIs('hotel.dashboard') ? 'active' : '' }}"><a href="{{ route('hotel.dashboard') }}"><i class="fe fe-home"></i><span>Dashboard</span></a></li>
        <li class="{{ Request::routeIs('hotel.frontdesk') ? 'active' : '' }}"><a href="{{ route('hotel.frontdesk') }}"><i class="fe fe-briefcase"></i><span>Front Desk</span></a></li>
        <li class="{{ Request::routeIs('hotel.reservations.*') ? 'active' : '' }}"><a href="{{ route('hotel.reservations.index') }}"><i class="fe fe-book"></i><span>Reservations</span></a></li>
        <li class="{{ Request::routeIs('hotel.availability.*') ? 'active' : '' }}"><a href="{{ route('hotel.availability.index') }}"><i class="fe fe-grid"></i><span>Availability</span></a></li>
        <li class="{{ Request::routeIs('hotel.checkin.index') ? 'active' : '' }}"><a href="{{ route('hotel.checkin.index') }}"><i class="fe fe-log-in"></i><span>Check In</span></a></li>
        <li class="{{ Request::routeIs('hotel.walkin.*') ? 'active' : '' }}"><a href="{{ route('hotel.walkin.create') }}"><i class="fe fe-user-plus"></i><span>Walk-In</span></a></li>
        <li class="{{ Request::routeIs('hotel.in_house') ? 'active' : '' }}"><a href="{{ route('hotel.in_house') }}"><i class="fe fe-users"></i><span>In-House Guests</span></a></li>
        <li class="{{ Request::routeIs('hotel.checkout.index') ? 'active' : '' }}"><a href="{{ route('hotel.checkout.index') }}"><i class="fe fe-log-out"></i><span>Checkout</span></a></li>
        <li class="{{ Request::routeIs('hotel.guests') ? 'active' : '' }}"><a href="{{ route('hotel.guests') }}"><i class="fe fe-user"></i><span>Guests</span></a></li>
        <li class="{{ Request::routeIs('hotel.folios.*') ? 'active' : '' }}"><a href="{{ route('hotel.folios.index') }}"><i class="fe fe-file-text"></i><span>Guest Folios</span></a></li>
        <li class="{{ Request::routeIs('hotel.rooms.*') ? 'active' : '' }}"><a href="{{ route('hotel.rooms.index') }}"><i class="fe fe-layers"></i><span>All Rooms</span></a></li>
        <li class="{{ Request::routeIs('hotel.room_types.*') ? 'active' : '' }}"><a href="{{ route('hotel.room_types.index') }}"><i class="fe fe-grid"></i><span>Room Types</span></a></li>
        <li class="{{ Request::routeIs('hotel.rate_plans.*') ? 'active' : '' }}"><a href="{{ route('hotel.rate_plans.index') }}"><i class="fe fe-dollar-sign"></i><span>Rate Plans</span></a></li>
        <li class="{{ Request::routeIs('hotel.rooms.status') ? 'active' : '' }}"><a href="{{ route('hotel.rooms.status') }}"><i class="fe fe-activity"></i><span>Room Status</span></a></li>
        <li class="{{ Request::routeIs('hotel.rooms.calendar') ? 'active' : '' }}"><a href="{{ route('hotel.rooms.calendar') }}"><i class="fe fe-calendar"></i><span>Room Calendar</span></a></li>
        <li class="{{ Request::routeIs('hotel.housekeeping.*') ? 'active' : '' }}"><a href="{{ route('hotel.housekeeping.index') }}"><i class="fe fe-check-square"></i><span>Housekeeping</span></a></li>
        <li class="{{ Request::routeIs('hotel.restaurant.pos') ? 'active' : '' }}"><a href="{{ route('hotel.restaurant.pos') }}"><i class="fe fe-shopping-cart"></i><span>Restaurant / POS</span></a></li>
        <li class="{{ Request::routeIs('hotel.room_service.index') ? 'active' : '' }}"><a href="{{ route('hotel.room_service.index') }}"><i class="fe fe-coffee"></i><span>Room Service</span></a></li>
        <li class="{{ Request::routeIs('hotel.laundry.index') ? 'active' : '' }}"><a href="{{ route('hotel.laundry.index') }}"><i class="fe fe-droplet"></i><span>Laundry</span></a></li>
        <li class="{{ Request::routeIs('hotel.minibar.index') ? 'active' : '' }}"><a href="{{ route('hotel.minibar.index') }}"><i class="fe fe-box"></i><span>Minibar</span></a></li>
        <li class="{{ Request::routeIs('hotel.maintenance.*') ? 'active' : '' }}"><a href="{{ route('hotel.maintenance.index') }}"><i class="fe fe-tool"></i><span>Maintenance</span></a></li>
        <li class="{{ Request::routeIs('hotel.conference.index') ? 'active' : '' }}"><a href="{{ route('hotel.conference.index') }}"><i class="fe fe-users"></i><span>Conference & Events</span></a></li>
        <li class="{{ Request::routeIs('hotel.corporate_accounts.index') ? 'active' : '' }}"><a href="{{ route('hotel.corporate_accounts.index') }}"><i class="fe fe-briefcase"></i><span>Corporate Accounts</span></a></li>
        <li class="{{ Request::routeIs('hotel.group_bookings.index') ? 'active' : '' }}"><a href="{{ route('hotel.group_bookings.index') }}"><i class="fe fe-user-check"></i><span>Group Bookings</span></a></li>
        <li class="{{ Request::routeIs('hotel.booking_sources.index') ? 'active' : '' }}"><a href="{{ route('hotel.booking_sources.index') }}"><i class="fe fe-link"></i><span>Travel Agents / Booking Sources</span></a></li>
        <li class="{{ Request::routeIs('hotel.deposits') ? 'active' : '' }}"><a href="{{ route('hotel.deposits') }}"><i class="fe fe-credit-card"></i><span>Deposits</span></a></li>
        <li class="{{ Request::routeIs('hotel.night_audit.*') ? 'active' : '' }}"><a href="{{ route('hotel.night_audit.index') }}"><i class="fe fe-moon"></i><span>Night Audit</span></a></li>
        <li class="{{ Request::routeIs('hotel.reports.index') ? 'active' : '' }}"><a href="{{ route('hotel.reports.index') }}"><i class="fe fe-bar-chart"></i><span>Hotel Reports</span></a></li>
    @endif

    <li class="{{ Request::routeIs('inventory.Products', 'product-list') ? 'active' : '' }}">
        <a href="{{ route('product-list') }}">
            <i class="fe fe-archive"></i>
            <span>Stock Overview</span>
        </a>
    </li>

    @if(auth()->user()?->role === 'super_admin' || auth()->user()?->role === 'administrator')
        <li class="{{ Request::routeIs('users.index') ? 'active' : '' }}">
            <a href="{{ route('users.index') }}">
                <i class="fe fe-user-plus"></i>
                <span>Users</span>
            </a>
        </li>

        <li class="{{ Request::routeIs('roles.index') ? 'active' : '' }}">
            <a href="{{ route('roles.index') }}">
                <i class="fe fe-shield"></i>
                <span>Roles & Permission</span>
            </a>
        </li>
    @endif

    <li class="{{ Request::routeIs('profile') ? 'active' : '' }}">
        <a href="{{ route('profile') }}">
            <i class="fe fe-user"></i>
            <span>Profile</span>
        </a>
    </li>

    <li class="{{ Request::routeIs('membership-plans') ? 'active' : '' }}">
        <a href="{{ route('membership-plans') }}">
            <i class="fe fe-settings"></i>
            <span>Billing & Subscription</span>
        </a>
    </li>
</ul>
