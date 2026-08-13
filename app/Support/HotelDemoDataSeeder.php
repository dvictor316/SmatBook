<?php

namespace App\Support;

use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use RuntimeException;

class HotelDemoDataSeeder
{
    private const TAG = 'SPB_DEMO_HOTEL_SEED';

    private array $columns = [];

    public function seed(?int $companyId = null, mixed $branchId = null, bool $fresh = false): array
    {
        $this->assertRequiredTables();

        $companyId = $this->resolveCompanyId($companyId);
        $branchId = $this->resolveBranchId($companyId, $branchId);

        if ($fresh) {
            $this->cleanup($companyId);
        }

        return DB::transaction(function () use ($companyId, $branchId) {
            $now = now();
            $today = Carbon::today();

            $propertyId = $this->upsertAndGetId('hotel_properties', [
                'company_id' => $companyId,
                'code' => 'SPB-DEMO-PROP',
            ], [
                'branch_id' => $branchId,
                'name' => 'SmartProbook Grand Demo Hotel',
                'address' => 'Demo Business District, Lagos',
                'city' => 'Lagos',
                'state' => 'Lagos',
                'country' => 'Nigeria',
                'phone' => '+234 800 000 0000',
                'email' => 'hotel-demo@smartprobook.local',
                'currency_code' => 'NGN',
                'timezone' => 'Africa/Lagos',
                'default_checkin_time' => '14:00:00',
                'default_checkout_time' => '12:00:00',
                'is_active' => 1,
                'updated_at' => $now,
                'created_at' => $now,
            ]);

            $roomTypes = $this->seedRoomTypes($companyId, $propertyId, $now);
            $rooms = $this->seedRooms($companyId, $propertyId, $roomTypes, $now);
            $ratePlans = $this->seedRatePlans($companyId, $propertyId, $roomTypes, $today, $now);
            $customers = $this->seedCustomers($companyId, $now);
            $reservations = $this->seedReservations($companyId, $propertyId, $roomTypes, $rooms, $ratePlans, $customers, $today, $now);
            $stays = $this->seedStays($companyId, $propertyId, $rooms, $reservations, $customers, $today, $now);
            $folios = $this->seedFolios($companyId, $propertyId, $stays, $reservations, $customers, $now);

            $this->seedFolioItems($companyId, $propertyId, $folios, $stays, $reservations, $now);
            $this->seedHousekeeping($companyId, $propertyId, $rooms, $stays, $now);
            $this->seedMaintenance($companyId, $propertyId, $rooms, $now);
            $this->seedNightAudit($companyId, $propertyId, $today, $now);
            $this->seedOperationalEvents($companyId, $propertyId, $rooms, $reservations, $stays, $customers, $now);

            return [
                'company_id' => $companyId,
                'branch_id' => $branchId,
                'property_id' => $propertyId,
                'room_types' => count($roomTypes),
                'rooms' => count($rooms),
                'reservations' => count($reservations),
                'stays' => count($stays),
                'folios' => count($folios),
            ];
        });
    }

    public function cleanup(?int $companyId = null): array
    {
        $this->assertRequiredTables(false);
        $companyId = $companyId ?: null;

        return DB::transaction(function () use ($companyId) {
            $reservationIds = $this->idsLike('reservations', 'reservation_number', 'SPB-DEMO-%', $companyId);
            $folioIds = $this->idsLike('guest_folios', 'folio_number', 'SPB-DEMO-%', $companyId);
            $propertyIds = $this->idsLike('hotel_properties', 'code', 'SPB-DEMO-%', $companyId);
            $roomIds = $this->idsLike('hotel_rooms', 'notes', '%' . self::TAG . '%', $companyId);
            $roomTypeIds = $this->idsLike('hotel_room_types', 'description', '%' . self::TAG . '%', $companyId);
            $customerIds = $this->idsLike('customers', 'email', '%@smartprobook-demo.local', $companyId);
            $stayIds = Schema::hasTable('stays') && !empty($reservationIds)
                ? $this->query('stays', $companyId)->whereIn('reservation_id', $reservationIds)->pluck('id')->all()
                : [];

            $deleted = [];
            $deleted['folio_items'] = $this->deleteByIds('folio_items', 'folio_id', $folioIds, $companyId);
            $deleted['hotel_nightly_charges'] = $this->deleteByIds('hotel_nightly_charges', 'folio_id', $folioIds, $companyId);
            $deleted['guest_folios'] = $this->deleteByPrimaryIds('guest_folios', $folioIds, $companyId);
            $deleted['hotel_housekeeping_tasks'] = $this->deleteByIds('hotel_housekeeping_tasks', 'room_id', $roomIds, $companyId);
            $deleted['hotel_maintenance_tickets'] = $this->deleteByIds('hotel_maintenance_tickets', 'room_id', $roomIds, $companyId);
            $deleted['hotel_room_blocks'] = $this->deleteByIds('hotel_room_blocks', 'room_id', $roomIds, $companyId);
            $deleted['hotel_operational_events'] = $this->deleteTaggedEvents($companyId);
            $deleted['hotel_night_audits'] = $this->deleteTaggedAudits($propertyIds, $companyId);
            $deleted['stays'] = $this->deleteByPrimaryIds('stays', $stayIds, $companyId);
            $deleted['reservations'] = $this->deleteByPrimaryIds('reservations', $reservationIds, $companyId);
            $deleted['hotel_rate_plans'] = $this->deleteByIds('hotel_rate_plans', 'property_id', $propertyIds, $companyId);
            $deleted['hotel_rooms'] = $this->deleteByPrimaryIds('hotel_rooms', $roomIds, $companyId);
            $deleted['hotel_room_types'] = $this->deleteByPrimaryIds('hotel_room_types', $roomTypeIds, $companyId);
            $deleted['hotel_properties'] = $this->deleteByPrimaryIds('hotel_properties', $propertyIds, $companyId);
            $deleted['customers'] = $this->deleteByPrimaryIds('customers', $customerIds, $companyId);

            Storage::disk('public')->deleteDirectory('hotel/rooms/demo-seed');

            return $deleted;
        });
    }

    private function seedRoomTypes(int $companyId, int $propertyId, Carbon $now): array
    {
        $types = [
            'STD-KING' => ['Standard King', 'King bed, workspace, rainfall shower', 'King', 1, 2, 1, 2, 45000, 52000],
            'DLX-SUITE' => ['Deluxe Suite', 'Larger suite with lounge area and premium amenities', 'King', 1, 2, 2, 4, 75000, 85000],
            'EXEC-TWIN' => ['Executive Twin', 'Two executive beds for business and group travellers', 'Twin', 2, 2, 1, 3, 62000, 70000],
            'FAM-ROOM' => ['Family Room', 'Family-friendly room with flexible bedding', 'Queen + Twin', 3, 2, 3, 5, 90000, 105000],
        ];

        $ids = [];
        foreach ($types as $code => $type) {
            $ids[$code] = $this->upsertAndGetId('hotel_room_types', [
                'company_id' => $companyId,
                'property_id' => $propertyId,
                'code' => $code,
            ], [
                'name' => $type[0],
                'description' => $type[1] . ' [' . self::TAG . ']',
                'bed_type' => $type[2],
                'beds' => $type[3],
                'max_adults' => $type[4],
                'max_children' => $type[5],
                'max_occupancy' => $type[6],
                'base_rate' => $type[7],
                'weekend_rate' => $type[8],
                'extra_adult_charge' => 10000,
                'extra_child_charge' => 6000,
                'extra_bed_charge' => 15000,
                'is_active' => 1,
                'updated_at' => $now,
                'created_at' => $now,
            ]);
        }

        return $ids;
    }

    private function seedRooms(int $companyId, int $propertyId, array $roomTypes, Carbon $now): array
    {
        $roomRows = [
            ['101', '1', 'A', 'STD-KING', 'occupied', 'clean', 45000],
            ['102', '1', 'A', 'DLX-SUITE', 'reserved', 'clean', 75000],
            ['103', '1', 'A', 'STD-KING', 'available', 'dirty', 45000],
            ['104', '1', 'A', 'EXEC-TWIN', 'occupied', 'dirty', 62000],
            ['105', '1', 'B', 'FAM-ROOM', 'available', 'clean', 90000],
            ['201', '2', 'A', 'DLX-SUITE', 'reserved', 'inspection', 75000],
            ['202', '2', 'A', 'EXEC-TWIN', 'available', 'cleaning', 62000],
            ['203', '2', 'B', 'STD-KING', 'maintenance', 'dirty', 45000],
            ['204', '2', 'B', 'DLX-SUITE', 'occupied', 'clean', 75000],
            ['301', '3', 'A', 'FAM-ROOM', 'available', 'clean', 90000],
            ['302', '3', 'A', 'STD-KING', 'out_of_order', 'dirty', 45000],
            ['303', '3', 'B', 'EXEC-TWIN', 'available', 'clean', 62000],
            ['304', '3', 'B', 'DLX-SUITE', 'reserved', 'clean', 75000],
            ['401', '4', 'PENT', 'FAM-ROOM', 'occupied', 'clean', 110000],
            ['402', '4', 'PENT', 'DLX-SUITE', 'available', 'inspection', 85000],
        ];

        $ids = [];
        foreach ($roomRows as $index => $room) {
            [$number, $floor, $wing, $typeCode, $operational, $housekeeping, $rate] = $room;
            $media = $this->createRoomMedia($number, $typeCode, $index);
            $ids[$number] = $this->upsertAndGetId('hotel_rooms', [
                'property_id' => $propertyId,
                'room_number' => $number,
            ], [
                'company_id' => $companyId,
                'room_type_id' => $roomTypes[$typeCode] ?? null,
                'floor' => $floor,
                'wing' => $wing,
                'base_rate_override' => $rate,
                'room_image' => $media['room_image'],
                'panorama_image' => $media['panorama_image'],
                'operational_status' => $operational,
                'housekeeping_status' => $housekeeping,
                'is_active' => 1,
                'notes' => self::TAG . ' demo room for PMS UI review.',
                'updated_at' => $now,
                'created_at' => $now,
            ]);
        }

        return $ids;
    }

    private function seedRatePlans(int $companyId, int $propertyId, array $roomTypes, Carbon $today, Carbon $now): array
    {
        $plans = [];
        foreach ($roomTypes as $code => $roomTypeId) {
            $plans[$code] = $this->upsertAndGetId('hotel_rate_plans', [
                'company_id' => $companyId,
                'property_id' => $propertyId,
                'code' => 'DEMO-' . $code,
            ], [
                'name' => str_replace('-', ' ', $code) . ' Flexible Rate',
                'room_type_id' => $roomTypeId,
                'rate' => match ($code) {
                    'DLX-SUITE' => 75000,
                    'EXEC-TWIN' => 62000,
                    'FAM-ROOM' => 90000,
                    default => 45000,
                },
                'start_date' => $today->copy()->subDays(30)->toDateString(),
                'end_date' => $today->copy()->addDays(90)->toDateString(),
                'applicable_days' => 'all',
                'min_stay' => 1,
                'max_stay' => 30,
                'meal_plan' => 'Breakfast',
                'is_active' => 1,
                'updated_at' => $now,
                'created_at' => $now,
            ]);
        }

        return $plans;
    }

    private function seedCustomers(int $companyId, Carbon $now): array
    {
        if (!Schema::hasTable('customers')) {
            return [];
        }

        $guests = [
            'john.doe@smartprobook-demo.local' => ['John Doe', '+234 801 000 1001', 'Victoria Island, Lagos'],
            'mary.jane@smartprobook-demo.local' => ['Mary Jane', '+234 801 000 1002', 'Lekki Phase 1, Lagos'],
            'victor.alpha@smartprobook-demo.local' => ['Victor Alpha', '+234 801 000 1003', 'Ikeja GRA, Lagos'],
            'grace.e@smartprobook-demo.local' => ['Grace E.', '+234 801 000 1004', 'Abuja Central'],
            'corporate.team@smartprobook-demo.local' => ['Corporate Hold', '+234 801 000 1005', 'Corporate Travel Desk'],
        ];

        $ids = [];
        foreach ($guests as $email => $guest) {
            $where = ['email' => $email];
            if ($this->hasColumn('customers', 'company_id')) {
                $where['company_id'] = $companyId;
            }

            $ids[$email] = $this->upsertAndGetId('customers', $where, [
                'company_id' => $companyId,
                'name' => $guest[0],
                'phone' => $guest[1],
                'address' => $guest[2] . ' [' . self::TAG . ']',
                'status' => 'active',
                'updated_at' => $now,
                'created_at' => $now,
            ]);
        }

        return $ids;
    }

    private function seedReservations(int $companyId, int $propertyId, array $roomTypes, array $rooms, array $ratePlans, array $customers, Carbon $today, Carbon $now): array
    {
        $rows = [
            ['SPB-DEMO-0001', 'john.doe@smartprobook-demo.local', '101', 'STD-KING', $today, 3, 'checked_in', 'Direct', 45000, 30000],
            ['SPB-DEMO-0002', 'mary.jane@smartprobook-demo.local', '102', 'DLX-SUITE', $today->copy()->addDay(), 2, 'confirmed', 'Booking.com', 75000, 50000],
            ['SPB-DEMO-0003', 'victor.alpha@smartprobook-demo.local', '204', 'DLX-SUITE', $today, 4, 'checked_in', 'Corporate', 75000, 100000],
            ['SPB-DEMO-0004', 'grace.e@smartprobook-demo.local', '304', 'EXEC-TWIN', $today->copy()->addDays(2), 3, 'confirmed', 'Walk-in', 62000, 0],
            ['SPB-DEMO-0005', 'corporate.team@smartprobook-demo.local', '301', 'FAM-ROOM', $today->copy()->addDays(5), 5, 'reserved', 'Corporate', 90000, 120000],
        ];

        $ids = [];
        foreach ($rows as $row) {
            [$number, $email, $roomNo, $typeCode, $arrival, $nights, $status, $source, $rate, $deposit] = $row;
            $arrival = Carbon::parse($arrival);
            $departure = $arrival->copy()->addDays($nights);
            $subtotal = $rate * $nights;
            $tax = round($subtotal * 0.075, 2);
            $service = round($subtotal * 0.05, 2);
            $total = $subtotal + $tax + $service;

            $ids[$number] = $this->upsertAndGetId('reservations', [
                'company_id' => $companyId,
                'reservation_number' => $number,
            ], [
                'property_id' => $propertyId,
                'customer_id' => $customers[$email] ?? null,
                'room_type_id' => $roomTypes[$typeCode] ?? null,
                'room_id' => $rooms[$roomNo] ?? null,
                'arrival_date' => $arrival->toDateString(),
                'arrival_time' => '14:00:00',
                'departure_date' => $departure->toDateString(),
                'departure_time' => '12:00:00',
                'nights' => $nights,
                'adults' => $typeCode === 'FAM-ROOM' ? 2 : 1,
                'children' => $typeCode === 'FAM-ROOM' ? 2 : 0,
                'rate_plan_id' => $ratePlans[$typeCode] ?? null,
                'nightly_rate' => $rate,
                'subtotal' => $subtotal,
                'tax' => $tax,
                'service_charge' => $service,
                'total' => $total,
                'deposit_required' => round($total * 0.35, 2),
                'deposit_received' => $deposit,
                'balance' => max(0, $total - $deposit),
                'source' => $source,
                'special_requests' => 'Demo reservation for front desk, calendar and guest journey review. [' . self::TAG . ']',
                'internal_notes' => self::TAG,
                'status' => $status,
                'updated_at' => $now,
                'created_at' => $now,
            ]);
        }

        return $ids;
    }

    private function seedStays(int $companyId, int $propertyId, array $rooms, array $reservations, array $customers, Carbon $today, Carbon $now): array
    {
        if (!Schema::hasTable('stays')) {
            return [];
        }

        $rows = [
            ['SPB-DEMO-0001', 'john.doe@smartprobook-demo.local', '101', 45000, 'checked_in'],
            ['SPB-DEMO-0003', 'victor.alpha@smartprobook-demo.local', '204', 75000, 'checked_in'],
        ];

        $ids = [];
        foreach ($rows as $row) {
            [$reservationNo, $email, $roomNo, $rate, $status] = $row;
            $ids[$reservationNo] = $this->upsertAndGetId('stays', [
                'company_id' => $companyId,
                'reservation_id' => $reservations[$reservationNo] ?? null,
            ], [
                'property_id' => $propertyId,
                'customer_id' => $customers[$email] ?? null,
                'room_id' => $rooms[$roomNo] ?? null,
                'checkin_at' => $today->copy()->setTime(14, 15)->toDateTimeString(),
                'expected_checkout_at' => $today->copy()->addDays($reservationNo === 'SPB-DEMO-0003' ? 4 : 3)->setTime(12, 0)->toDateTimeString(),
                'agreed_rate' => $rate,
                'adults' => 1,
                'children' => 0,
                'status' => $status,
                'updated_at' => $now,
                'created_at' => $now,
            ]);
        }

        return $ids;
    }

    private function seedFolios(int $companyId, int $propertyId, array $stays, array $reservations, array $customers, Carbon $now): array
    {
        if (!Schema::hasTable('guest_folios')) {
            return [];
        }

        $rows = [
            ['SPB-DEMO-FOLIO-0001', 'SPB-DEMO-0001', 'john.doe@smartprobook-demo.local', 145125, 30000],
            ['SPB-DEMO-FOLIO-0003', 'SPB-DEMO-0003', 'victor.alpha@smartprobook-demo.local', 322500, 100000],
        ];

        $ids = [];
        foreach ($rows as $row) {
            [$folioNo, $reservationNo, $email, $charges, $payments] = $row;
            $ids[$reservationNo] = $this->upsertAndGetId('guest_folios', [
                'company_id' => $companyId,
                'folio_number' => $folioNo,
            ], [
                'property_id' => $propertyId,
                'stay_id' => $stays[$reservationNo] ?? null,
                'reservation_id' => $reservations[$reservationNo] ?? null,
                'customer_id' => $customers[$email] ?? null,
                'opening_deposit' => $payments,
                'total_charges' => $charges,
                'total_payments' => $payments,
                'balance' => max(0, $charges - $payments),
                'status' => 'open',
                'updated_at' => $now,
                'created_at' => $now,
            ]);
        }

        return $ids;
    }

    private function seedFolioItems(int $companyId, int $propertyId, array $folios, array $stays, array $reservations, Carbon $now): void
    {
        if (!Schema::hasTable('folio_items')) {
            return;
        }

        $items = [
            ['SPB-DEMO-0001', 'Room Charge - Standard King', 'ROOM', 3, 45000, 'charge'],
            ['SPB-DEMO-0001', 'Breakfast Service', 'RESTAURANT', 2, 5500, 'charge'],
            ['SPB-DEMO-0001', 'Opening deposit', 'DEPOSIT', 1, 30000, 'payment'],
            ['SPB-DEMO-0003', 'Room Charge - Deluxe Suite', 'ROOM', 4, 75000, 'charge'],
            ['SPB-DEMO-0003', 'Minibar and Laundry', 'MINIBAR', 1, 22500, 'charge'],
            ['SPB-DEMO-0003', 'Corporate deposit', 'DEPOSIT', 1, 100000, 'payment'],
        ];

        foreach ($items as $item) {
            [$reservationNo, $description, $code, $quantity, $unitPrice, $type] = $item;
            $amount = $quantity * $unitPrice;
            $postingKey = self::TAG . '-' . $reservationNo . '-' . $code;

            $this->upsertAndGetId('folio_items', [
                'company_id' => $companyId,
                'posting_key' => $postingKey,
            ], [
                'property_id' => $propertyId,
                'folio_id' => $folios[$reservationNo] ?? null,
                'stay_id' => $stays[$reservationNo] ?? null,
                'reservation_id' => $reservations[$reservationNo] ?? null,
                'source_type' => 'hotel_demo_seed',
                'description' => $description . ' [' . self::TAG . ']',
                'amount' => $type === 'payment' ? -abs($amount) : $amount,
                'quantity' => $quantity,
                'unit_price' => $unitPrice,
                'type' => $type,
                'service_code' => $code,
                'service_date' => now()->toDateString(),
                'meta' => json_encode(['seed' => self::TAG]),
                'updated_at' => $now,
                'created_at' => $now,
            ]);
        }
    }

    private function seedHousekeeping(int $companyId, int $propertyId, array $rooms, array $stays, Carbon $now): void
    {
        if (!Schema::hasTable('hotel_housekeeping_tasks')) {
            return;
        }

        $tasks = [
            ['103', null, 'checkout_clean', 'open', 'urgent', 'Departure cleaning before 3 PM arrival.'],
            ['104', 'SPB-DEMO-0001', 'stayover_service', 'in_progress', 'high', 'Refresh linen, towels and minibar checklist.'],
            ['202', null, 'deep_clean', 'open', 'normal', 'Deep clean after long stay checkout.'],
            ['402', null, 'inspection', 'open', 'normal', 'Supervisor inspection before releasing room.'],
        ];

        foreach ($tasks as $task) {
            [$roomNo, $reservationNo, $type, $status, $priority, $note] = $task;
            $this->upsertAndGetId('hotel_housekeeping_tasks', [
                'company_id' => $companyId,
                'room_id' => $rooms[$roomNo] ?? null,
                'task_type' => $type,
            ], [
                'property_id' => $propertyId,
                'stay_id' => $reservationNo ? ($stays[$reservationNo] ?? null) : null,
                'status' => $status,
                'priority' => $priority,
                'note' => $note . ' [' . self::TAG . ']',
                'updated_at' => $now,
                'created_at' => $now,
            ]);
        }
    }

    private function seedMaintenance(int $companyId, int $propertyId, array $rooms, Carbon $now): void
    {
        if (!Schema::hasTable('hotel_maintenance_tickets')) {
            return;
        }

        $tickets = [
            ['SPB-DEMO-MT-001', '203', 'open', 'high', 'A/C not cooling', 'Guest reported weak cooling. Engineering inspection required.'],
            ['SPB-DEMO-MT-002', '302', 'in_progress', 'urgent', 'Water leak inspection', 'Bathroom leak. Room kept out of order until cleared.'],
            ['SPB-DEMO-MT-003', '105', 'completed', 'low', 'TV remote replacement', 'Remote replaced and room returned to service.'],
        ];

        foreach ($tickets as $ticket) {
            [$ticketNo, $roomNo, $status, $severity, $title, $description] = $ticket;
            $this->upsertAndGetId('hotel_maintenance_tickets', [
                'company_id' => $companyId,
                'ticket_no' => $ticketNo,
            ], [
                'property_id' => $propertyId,
                'room_id' => $rooms[$roomNo] ?? null,
                'status' => $status,
                'severity' => $severity,
                'title' => $title,
                'description' => $description . ' [' . self::TAG . ']',
                'resolved_at' => $status === 'completed' ? $now : null,
                'resolution_note' => $status === 'completed' ? self::TAG . ' demo resolution.' : null,
                'updated_at' => $now,
                'created_at' => $now,
            ]);
        }
    }

    private function seedNightAudit(int $companyId, int $propertyId, Carbon $today, Carbon $now): void
    {
        if (!Schema::hasTable('hotel_night_audits')) {
            return;
        }

        foreach ([1, 2] as $daysAgo) {
            $date = $today->copy()->subDays($daysAgo);
            $this->upsertAndGetId('hotel_night_audits', [
                'company_id' => $companyId,
                'property_id' => $propertyId,
                'audit_date' => $date->toDateString(),
            ], [
                'status' => 'completed',
                'stays_scanned' => 8 + $daysAgo,
                'charges_posted' => 6 + $daysAgo,
                'charges_skipped' => $daysAgo === 1 ? 0 : 1,
                'total_amount' => 275000 + ($daysAgo * 25000),
                'run_at' => $date->copy()->setTime(23, 45)->toDateTimeString(),
                'meta' => json_encode(['seed' => self::TAG]),
                'updated_at' => $now,
                'created_at' => $now,
            ]);
        }
    }

    private function seedOperationalEvents(int $companyId, int $propertyId, array $rooms, array $reservations, array $stays, array $customers, Carbon $now): void
    {
        if (!Schema::hasTable('hotel_operational_events')) {
            return;
        }

        $events = [
            ['reservation_created', 'New corporate booking created', 'SPB-DEMO-0005', null, null],
            ['checkin_completed', 'John Doe checked into Room 101', 'SPB-DEMO-0001', '101', 'john.doe@smartprobook-demo.local'],
            ['housekeeping_alert', 'Room 103 needs priority cleaning', null, '103', null],
            ['maintenance_opened', 'Maintenance ticket opened for Room 203', null, '203', null],
        ];

        foreach ($events as $index => $event) {
            [$type, $title, $reservationNo, $roomNo, $email] = $event;
            $this->upsertAndGetId('hotel_operational_events', [
                'company_id' => $companyId,
                'event_type' => $type,
                'title' => $title,
            ], [
                'property_id' => $propertyId,
                'reservation_id' => $reservationNo ? ($reservations[$reservationNo] ?? null) : null,
                'stay_id' => $reservationNo ? ($stays[$reservationNo] ?? null) : null,
                'customer_id' => $email ? ($customers[$email] ?? null) : null,
                'room_id' => $roomNo ? ($rooms[$roomNo] ?? null) : null,
                'description' => self::TAG . ' timeline event for PMS activity feed.',
                'meta' => json_encode(['seed' => self::TAG, 'sequence' => $index + 1]),
                'updated_at' => $now,
                'created_at' => $now,
            ]);
        }
    }

    private function createRoomMedia(string $roomNumber, string $typeCode, int $index): array
    {
        if (!$this->hasColumn('hotel_rooms', 'room_image') && !$this->hasColumn('hotel_rooms', 'panorama_image')) {
            return ['room_image' => null, 'panorama_image' => null];
        }

        $palette = [
            ['#0b2f5f', '#d4a33d', '#eef6ff'],
            ['#064e3b', '#f59e0b', '#ecfdf5'],
            ['#1e3a8a', '#14b8a6', '#eff6ff'],
            ['#4c1d95', '#f97316', '#faf5ff'],
        ][$index % 4];

        $roomPath = 'hotel/rooms/demo-seed/room-' . $roomNumber . '.svg';
        $panoPath = 'hotel/rooms/demo-seed/panorama-' . $roomNumber . '.svg';
        Storage::disk('public')->put($roomPath, $this->roomSvg($roomNumber, $typeCode, $palette, 900, 560));
        Storage::disk('public')->put($panoPath, $this->roomSvg($roomNumber, $typeCode . ' Panorama', $palette, 1400, 520));

        return ['room_image' => $roomPath, 'panorama_image' => $panoPath];
    }

    private function roomSvg(string $roomNumber, string $label, array $palette, int $width, int $height): string
    {
        [$primary, $accent, $soft] = $palette;
        $innerWidth = $width - 88;
        $innerHeight = $height - 96;
        $windowWidth = $width - 164;
        $bedWidth = $width - 240;
        $pillowWidth = $width - 290;
        $accentX = $width - 150;

        return <<<SVG
<svg xmlns="http://www.w3.org/2000/svg" width="{$width}" height="{$height}" viewBox="0 0 {$width} {$height}">
  <defs>
    <linearGradient id="g" x1="0" x2="1" y1="0" y2="1"><stop offset="0" stop-color="{$soft}"/><stop offset="1" stop-color="#ffffff"/></linearGradient>
    <linearGradient id="w" x1="0" x2="1"><stop offset="0" stop-color="{$primary}" stop-opacity=".14"/><stop offset="1" stop-color="{$accent}" stop-opacity=".2"/></linearGradient>
  </defs>
  <rect width="{$width}" height="{$height}" fill="url(#g)"/>
  <rect x="44" y="48" width="{$innerWidth}" height="{$innerHeight}" rx="36" fill="#fff" stroke="{$primary}" stroke-opacity=".18"/>
  <rect x="82" y="96" width="{$windowWidth}" height="150" rx="22" fill="url(#w)"/>
  <rect x="120" y="275" width="{$bedWidth}" height="92" rx="18" fill="{$primary}" opacity=".9"/>
  <rect x="145" y="245" width="{$pillowWidth}" height="92" rx="26" fill="#ffffff" stroke="{$accent}" stroke-width="5"/>
  <circle cx="{$accentX}" cy="150" r="54" fill="{$accent}" opacity=".9"/>
  <text x="110" y="150" font-family="Verdana, Arial, sans-serif" font-size="42" font-weight="700" fill="{$primary}">Room {$roomNumber}</text>
  <text x="110" y="204" font-family="Verdana, Arial, sans-serif" font-size="24" fill="#40546b">{$label}</text>
  <text x="150" y="330" font-family="Verdana, Arial, sans-serif" font-size="28" font-weight="700" fill="#ffffff">SmartProbook Hotel Preview</text>
</svg>
SVG;
    }

    private function assertRequiredTables(bool $strict = true): void
    {
        $required = ['hotel_properties', 'hotel_room_types', 'hotel_rooms'];
        foreach ($required as $table) {
            if (!Schema::hasTable($table) && $strict) {
                throw new RuntimeException("Missing required hotel table: {$table}. Run migrations first.");
            }
        }
    }

    private function resolveCompanyId(?int $companyId): int
    {
        if ($companyId) {
            return $companyId;
        }

        if (Schema::hasTable('hotel_properties')) {
            $existing = DB::table('hotel_properties')->whereNotNull('company_id')->value('company_id');
            if ($existing) {
                return (int) $existing;
            }
        }

        if (Schema::hasTable('companies')) {
            $id = DB::table('companies')->orderBy('id')->value('id');
            if ($id) {
                return (int) $id;
            }
        }

        throw new RuntimeException('No company found. Create a company first or pass --company=ID.');
    }

    private function resolveBranchId(int $companyId, mixed $branchId): ?int
    {
        if (is_numeric($branchId)) {
            return (int) $branchId;
        }

        if (!Schema::hasTable('branches')) {
            return null;
        }

        $query = DB::table('branches')->orderBy('id');
        if ($this->hasColumn('branches', 'company_id')) {
            $query->where('company_id', $companyId);
        }

        $id = $query->value('id');
        return is_numeric($id) ? (int) $id : null;
    }

    private function upsertAndGetId(string $table, array $where, array $values): int
    {
        if (!Schema::hasTable($table)) {
            return 0;
        }

        $where = $this->filterColumns($table, $where);
        $values = $this->filterColumns($table, $values);
        $query = DB::table($table);
        foreach ($where as $column => $value) {
            $query->where($column, $value);
        }

        $existing = !empty($where) ? $query->first() : null;
        if ($existing) {
            DB::table($table)->where('id', $existing->id)->update($values);
            return (int) $existing->id;
        }

        return (int) DB::table($table)->insertGetId(array_merge($where, $values));
    }

    private function idsLike(string $table, string $column, string $value, ?int $companyId): array
    {
        if (!Schema::hasTable($table) || !$this->hasColumn($table, $column)) {
            return [];
        }

        return $this->query($table, $companyId)->where($column, 'like', $value)->pluck('id')->map(fn ($id) => (int) $id)->all();
    }

    private function deleteByIds(string $table, string $column, array $ids, ?int $companyId): int
    {
        if (!Schema::hasTable($table) || empty($ids) || !$this->hasColumn($table, $column)) {
            return 0;
        }

        return $this->query($table, $companyId)->whereIn($column, $ids)->delete();
    }

    private function deleteByPrimaryIds(string $table, array $ids, ?int $companyId): int
    {
        if (!Schema::hasTable($table) || empty($ids)) {
            return 0;
        }

        return $this->query($table, $companyId)->whereIn('id', $ids)->delete();
    }

    private function deleteTaggedEvents(?int $companyId): int
    {
        if (!Schema::hasTable('hotel_operational_events')) {
            return 0;
        }

        return $this->query('hotel_operational_events', $companyId)
            ->where(function ($query) {
                $query->where('description', 'like', '%' . self::TAG . '%');
                if ($this->hasColumn('hotel_operational_events', 'meta')) {
                    $query->orWhere('meta', 'like', '%' . self::TAG . '%');
                }
            })
            ->delete();
    }

    private function deleteTaggedAudits(array $propertyIds, ?int $companyId): int
    {
        if (!Schema::hasTable('hotel_night_audits') || empty($propertyIds)) {
            return 0;
        }

        return $this->query('hotel_night_audits', $companyId)
            ->whereIn('property_id', $propertyIds)
            ->where('meta', 'like', '%' . self::TAG . '%')
            ->delete();
    }

    private function query(string $table, ?int $companyId)
    {
        $query = DB::table($table);
        if ($companyId && $this->hasColumn($table, 'company_id')) {
            $query->where('company_id', $companyId);
        }

        return $query;
    }

    private function filterColumns(string $table, array $data): array
    {
        $columns = $this->columns($table);
        return array_intersect_key($data, array_flip($columns));
    }

    private function hasColumn(string $table, string $column): bool
    {
        return in_array($column, $this->columns($table), true);
    }

    private function columns(string $table): array
    {
        if (!array_key_exists($table, $this->columns)) {
            $this->columns[$table] = Schema::hasTable($table) ? Schema::getColumnListing($table) : [];
        }

        return $this->columns[$table];
    }
}
