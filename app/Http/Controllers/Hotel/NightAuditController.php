<?php

namespace App\Http\Controllers\Hotel;

use App\Http\Controllers\Controller;
use App\Models\FolioItem;
use App\Models\GuestFolio;
use App\Models\HotelNightAudit;
use App\Models\HotelProperty;
use App\Models\Reservation;
use App\Models\Stay;
use App\Services\Hotel\NightAuditService;
use Illuminate\Http\Request;

class NightAuditController extends Controller
{
    public function __construct(
        private readonly NightAuditService $nightAuditService
    ) {
    }

    public function index(Request $request)
    {
        $companyId = (int) auth()->user()->company_id;
        $propertyId = $this->currentPropertyId();

        $audits = HotelNightAudit::query()
            ->where('company_id', $companyId)
            ->where('property_id', $propertyId)
            ->latest('audit_date')
            ->paginate(30);

        $businessDate = now()->toDateString();
        $arrivalsExpected = Reservation::query()->where('company_id', $companyId)->where('property_id', $propertyId)->whereDate('arrival_date', $businessDate)->count();
        $arrivalsCheckedIn = Stay::query()->where('company_id', $companyId)->where('property_id', $propertyId)->whereDate('checkin_at', $businessDate)->count();
        $arrivalsPending = max(0, $arrivalsExpected - $arrivalsCheckedIn);

        $departuresExpected = Reservation::query()->where('company_id', $companyId)->where('property_id', $propertyId)->whereDate('departure_date', $businessDate)->count();
        $departuresCheckedOut = Stay::query()->where('company_id', $companyId)->where('property_id', $propertyId)->whereDate('actual_checkout_at', $businessDate)->count();
        $departuresPending = max(0, $departuresExpected - $departuresCheckedOut);

        $financial = [
            'room_charges_pending' => Stay::query()->where('company_id', $companyId)->where('property_id', $propertyId)->where('status', 'checked_in')->count(),
            'open_folios' => GuestFolio::query()->where('company_id', $companyId)->where('property_id', $propertyId)->where('status', 'open')->count(),
            'outstanding_balances' => (float) GuestFolio::query()->where('company_id', $companyId)->where('property_id', $propertyId)->sum('balance'),
            'payments_today' => (float) FolioItem::query()->where('company_id', $companyId)->where('property_id', $propertyId)->whereDate('service_date', $businessDate)->whereIn('type', ['payment', 'deposit_applied'])->sum('amount'),
        ];

        $roomStatus = [
            'occupied' => Stay::query()->where('company_id', $companyId)->where('property_id', $propertyId)->where('status', 'checked_in')->count(),
            'dirty' => \App\Models\HotelRoom::query()->where('company_id', $companyId)->where('property_id', $propertyId)->where('housekeeping_status', 'dirty')->count(),
            'maintenance' => \App\Models\HotelRoom::query()->where('company_id', $companyId)->where('property_id', $propertyId)->where('operational_status', 'maintenance')->count(),
            'out_of_order' => \App\Models\HotelRoom::query()->where('company_id', $companyId)->where('property_id', $propertyId)->where('operational_status', 'out_of_order')->count(),
        ];

        $blockingIssues = collect([
            $arrivalsPending > 0 ? $arrivalsPending . ' arrivals remain pending check-in.' : null,
            $departuresPending > 0 ? $departuresPending . ' departures still require checkout.' : null,
            $financial['open_folios'] > 0 ? $financial['open_folios'] . ' folios remain open.' : null,
        ])->filter()->values();

        if (view()->exists('hotel.night_audit.index')) {
            return view('hotel.night_audit.index', compact('audits', 'businessDate', 'arrivalsExpected', 'arrivalsCheckedIn', 'arrivalsPending', 'departuresExpected', 'departuresCheckedOut', 'departuresPending', 'financial', 'roomStatus', 'blockingIssues'));
        }

        return response()->json([
            'data' => $audits,
        ]);
    }

    public function run(Request $request)
    {
        $validated = $request->validate([
            'audit_date' => 'nullable|date',
            'force' => 'nullable|boolean',
        ]);

        $companyId = (int) auth()->user()->company_id;
        $propertyId = $this->currentPropertyId();
        $auditDate = (string) ($validated['audit_date'] ?? now()->toDateString());

        $audit = $this->nightAuditService->run(
            $companyId,
            $propertyId,
            $auditDate,
            (int) auth()->id(),
            (bool) ($validated['force'] ?? false)
        );

        return back()->with('success', 'Night audit completed. Charges posted: ' . $audit->charges_posted . ', skipped: ' . $audit->charges_skipped . '.');
    }

    public function reopen(Request $request, HotelNightAudit $audit)
    {
        abort_unless((int) $audit->company_id === (int) auth()->user()->company_id, 404);

        $validated = $request->validate([
            'reason' => 'nullable|string|max:255',
        ]);

        $this->nightAuditService->reopen($audit, (int) auth()->id(), $validated['reason'] ?? null);

        return back()->with('success', 'Night audit reopened for correction.');
    }

    private function currentPropertyId(): int
    {
        $companyId = (int) auth()->user()->company_id;
        $branchId = auth()->user()->branch_id;

        $propertyId = HotelProperty::query()
            ->where('company_id', $companyId)
            ->when($branchId, fn ($query) => $query->where('branch_id', $branchId))
            ->value('id');

        if (!$propertyId) {
            abort(422, 'No hotel property is configured for this branch.');
        }

        return (int) $propertyId;
    }
}
