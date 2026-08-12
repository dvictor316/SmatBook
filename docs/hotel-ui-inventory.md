# Hotel UI Inventory

Generated during the Hotel UX reconstruction pass.

## Dashboard
- Route: `hotel.dashboard`
- Controller: `HotelDashboardController@dashboard`
- View: `resources/views/hotel/dashboard/index.blade.php`
- Purpose: Analytics command center
- Data: room KPIs, arrivals/departures, folio balances, revenue trends, department revenue
- Actions: new reservation, walk-in, check-in, checkout, night audit, front desk, add room
- Layout: analytical dashboard with charts, activity widgets, quick actions
- Functional: yes
- Duplicate risk: low

## Front Desk
- Route: `hotel.frontdesk`
- Controller: `HotelDashboardController@frontDesk`
- View: `resources/views/hotel/frontdesk/index.blade.php`
- Purpose: live hotel operations
- Data: room board, arrivals, departures, in-house, pending check-ins, alerts
- Actions: reserve, walk-in, check-in, checkout, room-state actions, grouped search
- Layout: room-centric operational board with alerts and queues
- Functional: yes
- Duplicate risk: low

## Reservations
- Route: `hotel.reservations.index`
- Controller: `ReservationController@index`
- View: `resources/views/hotel/reservations/index.blade.php`
- Purpose: booking management workspace
- Data: filtered reservations, guest/room/type/source/balance fields
- Actions: view, check-in, filter/search
- Layout: booking management table with rich filters
- Functional: yes
- Duplicate risk: medium

## Reservation Create
- Route: `hotel.reservations.create`
- Controller: `ReservationController@create`
- View: `resources/views/hotel/reservations/create.blade.php`
- Purpose: guided booking workflow
- Data: property, room types, available rooms, prefilled calendar slot values
- Actions: create reservation
- Layout: multi-section booking flow with live summary sidebar
- Functional: yes
- Duplicate risk: low

## Reservation Detail
- Route: `hotel.reservations.show`
- Controller: `ReservationController@show`
- View: `resources/views/hotel/reservations/show.blade.php`
- Purpose: reservation quick-view and operational control
- Data: reservation, stay, operational timeline, available rooms
- Actions: check-in, assign room, extend stay
- Layout: detail panel + action workspace + timeline
- Functional: yes
- Duplicate risk: low

## Availability Search
- Route: `hotel.availability.index`
- Controller: `AvailabilityController@index`
- View: `resources/views/hotel/availability/index.blade.php`
- Purpose: room search entry
- Data: current property
- Actions: search available rooms
- Layout: search-first booking interface
- Functional: yes
- Duplicate risk: low

## Availability Results
- Route: `hotel.availability.search`
- Controller: `AvailabilityController@search`
- View: `resources/views/hotel/availability/results.blade.php`
- Purpose: room offer/result selection
- Data: available rooms for date window
- Actions: book now, walk-in
- Layout: result cards with pricing estimate
- Functional: yes
- Duplicate risk: low

## Check-In
- Route: `hotel.checkin.index`
- Controller: `CheckInController@index`
- View: `resources/views/hotel/checkin/index.blade.php`
- Purpose: arrival workflow queue
- Data: pending reservations with room/deposit/readiness context
- Actions: open reservation, complete check-in
- Layout: arrival queue + readiness guidance
- Functional: yes
- Duplicate risk: low

## Walk-In
- Route: `hotel.walkin.create`
- Controller: `WalkInController@create`
- View: `resources/views/hotel/walkin/create.blade.php`
- Purpose: fast reception workflow
- Data: current property, available rooms, room types
- Actions: complete walk-in check-in
- Layout: stepped quick-entry flow
- Functional: yes
- Duplicate risk: low

## In-House
- Route: `hotel.in_house`
- Controller: `HotelDashboardController@inHouse`
- View: `resources/views/hotel/in_house/index.blade.php`
- Purpose: active-stay control
- Data: stay, room, guest, folio charges/payments/balance
- Actions: folio, checkout
- Layout: active-stay control table
- Functional: yes
- Duplicate risk: low

## Checkout
- Route: `hotel.checkout.index`
- Controller: `CheckInController@checkoutDesk`
- View: `resources/views/hotel/checkout/index.blade.php`
- Purpose: payment and settlement workspace
- Data: stays, selected stay, selected folio, folio items
- Actions: settle and complete checkout
- Layout: stay selection + settlement panel + detailed folio
- Functional: yes
- Duplicate risk: low

## Guests
- Route: `hotel.guests`
- Controller: `HotelDashboardController@guests`
- View: `resources/views/hotel/guests/index.blade.php`
- Purpose: hotel CRM list
- Data: guest profile summary, last stay, total stays, spend, balance
- Actions: browse only currently
- Layout: CRM-oriented guest ledger table
- Functional: partially
- Duplicate risk: medium

## Folios Index
- Route: `hotel.folios.index`
- Controller: `FolioController@index`
- View: `resources/views/hotel/folios/index.blade.php`
- Purpose: guest financial statements list
- Data: folio balances, room, guest, status
- Actions: open statement
- Layout: financial ledger index
- Functional: yes
- Duplicate risk: low

## Folio Detail
- Route: `hotel.folios.show`
- Controller: `FolioController@show`
- View: `resources/views/hotel/folios/show.blade.php`
- Purpose: guest account statement
- Data: folio, stay, reservation, ledger items, running balance
- Actions: add charge, post service, go to checkout settlement
- Layout: account statement + posting forms + ledger
- Functional: yes
- Duplicate risk: low

## Deposits
- Route: `hotel.deposits`
- Controller: `HotelDashboardController@deposits`
- View: `resources/views/hotel/deposits/index.blade.php`
- Purpose: deposit tracking
- Data: reservation deposit received/required, gap, guest, status
- Actions: browse only currently
- Layout: deposit workspace
- Functional: partially
- Duplicate risk: medium

## Rooms
- Route: `hotel.rooms.index`
- Controller: `HotelRoomController@index`
- View: `resources/views/hotel/rooms/index.blade.php`
- Purpose: visual room inventory
- Data: room, type, statuses, current guest, next reservation
- Actions: view mode switch, edit, deactivate
- Layout: grid default plus table alternative
- Functional: yes
- Duplicate risk: low

## Room Status
- Route: `hotel.rooms.status`
- Controller: `HotelWorkspaceController@roomStatus`
- View: `resources/views/hotel/rooms/status.blade.php`
- Purpose: room-state audit board
- Data: room statuses and counts
- Actions: manage room
- Layout: status board grid
- Functional: yes
- Duplicate risk: low

## Room Calendar
- Route: `hotel.rooms.calendar`
- Controller: `HotelWorkspaceController@roomCalendar`
- View: `resources/views/hotel/rooms/calendar.blade.php`
- Purpose: PMS timeline
- Data: room/date grid, reservations, stays, blocks, maintenance, unassigned reservations
- Actions: quick reserve, assign room, block room
- Layout: horizontal timeline calendar
- Functional: yes
- Duplicate risk: low

## Room Types
- Route: `hotel.room_types.index`
- Controller: `HotelRoomTypeController@index`
- View: `resources/views/hotel/room_types/index.blade.php`
- Purpose: room type configuration
- Data: room type inventory, bed/capacity/rate/status
- Actions: edit, deactivate
- Layout: configuration table
- Functional: yes
- Duplicate risk: low

## Rate Plans
- Route: `hotel.rate_plans.index`
- Controller: `HotelRatePlanController@index`
- View: `resources/views/hotel/rate_plans/index.blade.php`
- Purpose: pricing workspace
- Data: rate plans, room types, status
- Actions: create, duplicate, activate/deactivate
- Layout: form + management table
- Functional: yes
- Duplicate risk: medium

## Housekeeping
- Route: `hotel.housekeeping.index`
- Controller: `HousekeepingController@index`
- View: `resources/views/hotel/housekeeping/index.blade.php`
- Purpose: cleaning workflow board
- Data: grouped housekeeping tasks, dirty rooms, arrival pressure, priority tasks
- Actions: mark clean, complete tasks
- Layout: kanban-like workflow + side queues
- Functional: yes
- Duplicate risk: low

## Maintenance
- Route: `hotel.maintenance.index`
- Controller: `MaintenanceController@index`
- View: `resources/views/hotel/maintenance/index.blade.php`
- Purpose: ticket workspace
- Data: ticket summary, rooms, reservation conflicts, paginated tickets
- Actions: create ticket, update status
- Layout: service desk form + ticket table + conflict panel
- Functional: yes
- Duplicate risk: low

## Laundry
- Route: `hotel.laundry.index`
- Controller: `HotelWorkspaceController@laundry`
- View: `resources/views/hotel/operations/laundry.blade.php`
- Purpose: laundry service review
- Data: folio laundry charges
- Actions: browse only currently
- Layout: service workspace with summary + ledger
- Functional: partially
- Duplicate risk: medium

## Minibar
- Route: `hotel.minibar.index`
- Controller: `HotelWorkspaceController@minibar`
- View: `resources/views/hotel/operations/minibar.blade.php`
- Purpose: minibar activity workspace
- Data: minibar charges, active stays
- Actions: open folio from active stay
- Layout: active room list + recent postings
- Functional: partially
- Duplicate risk: low

## Room Service
- Route: `hotel.room_service.index`
- Controller: `HotelWorkspaceController@roomService`
- View: `resources/views/hotel/operations/room_service.blade.php`
- Purpose: room-service charge feed
- Data: service charge items
- Actions: browse only currently
- Layout: service ticket feed
- Functional: partially
- Duplicate risk: medium

## Conference & Events
- Route: `hotel.conference.index`
- Controller: `HotelWorkspaceController@conference`
- View: `resources/views/hotel/operations/conference.blade.php`
- Purpose: event-linked bookings workspace
- Data: bookings with event/conference source
- Actions: browse only currently
- Layout: event pipeline summary + booking ledger
- Functional: partially
- Duplicate risk: medium

## Corporate Accounts
- Route: `hotel.corporate_accounts.index`
- Controller: `HotelWorkspaceController@corporateAccounts`
- View: `resources/views/hotel/business/corporate_accounts.blade.php`
- Purpose: B2B receivables list
- Data: city-ledger folios by corporate customer
- Actions: browse only currently
- Layout: receivables workspace
- Functional: partially
- Duplicate risk: medium

## Group Bookings
- Route: `hotel.group_bookings.index`
- Controller: `HotelWorkspaceController@groupBookings`
- View: `resources/views/hotel/business/group_bookings.blade.php`
- Purpose: group booking workspace
- Data: reservation-level group candidates
- Actions: browse only currently
- Layout: group booking management list
- Functional: partially
- Duplicate risk: medium

## Booking Sources
- Route: `hotel.booking_sources.index`
- Controller: `HotelWorkspaceController@bookingSources`
- View: `resources/views/hotel/business/booking_sources.blade.php`
- Purpose: source/channel performance review
- Data: aggregated source counts and values
- Actions: browse only currently
- Layout: source performance leaderboard
- Functional: yes
- Duplicate risk: low

## Reports
- Route: `hotel.reports.index`
- Controller: `HotelWorkspaceController@reports`
- View: `resources/views/hotel/reports/index.blade.php`
- Purpose: report catalogue
- Data: report KPI snippets and drill-in links
- Actions: open report categories
- Layout: report center cards
- Functional: partially
- Duplicate risk: low

## Settings
- Route: `hotel.settings`
- Controller: `HotelDashboardController@settings`
- View: `resources/views/hotel/settings/index.blade.php`
- Purpose: property configuration overview
- Data: property configuration fields
- Actions: open hotel setup
- Layout: tabbed configuration shell
- Functional: partially
- Duplicate risk: low

## Night Audit
- Route: `hotel.night_audit.index`
- Controller: `NightAuditController@index`
- View: `resources/views/hotel/night_audit/index.blade.php`
- Purpose: end-of-day control center
- Data: arrivals/departures, room status, open folios, audit history, blocking issues
- Actions: run audit, reopen audit
- Layout: control checklist + run panel + audit history
- Functional: yes
- Duplicate risk: low

## Search
- Route: `hotel.search`
- Controller: `HotelWorkspaceController@search`
- View: `resources/views/hotel/search/index.blade.php`
- Purpose: grouped hotel search results
- Data: guests, reservations, rooms, folios, receipts
- Actions: open reservation/folio
- Layout: grouped search results by entity type
- Functional: yes
- Duplicate risk: low
