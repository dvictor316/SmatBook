<?php

namespace App\Http\Controllers\Hotel;

use App\Http\Controllers\Controller;
use App\Models\HotelNightAudit;
use App\Models\HotelProperty;
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

        if (view()->exists('hotel.night_audit.index')) {
            return view('hotel.night_audit.index', compact('audits'));
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
