<?php

namespace App\Http\Controllers;

use App\Models\PurchaseRequisition;
use App\Models\PurchaseRequisitionItem;
use App\Models\Product;
use App\Models\Department;
use App\Models\CostCenter;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class PurchaseRequisitionController extends Controller
{
    public function index(Request $request)
    {
        $companyId = Auth::user()->company_id;
        $query = PurchaseRequisition::forCompany($companyId)
            ->with(['requestedBy', 'department'])
            ->latest();

        if ($status = $request->query('status')) {
            $query->where('status', $status);
        }

        $requisitions = $query->paginate(25);
        return view('purchase-requisitions.index', compact('requisitions'));
    }

    public function create()
    {
        $companyId = Auth::user()->company_id;
        $products    = Product::where('company_id', $companyId)->orderBy('name')->get();
        $departments = Department::forCompany($companyId)->active()->orderBy('name')->get();
        $costCenters = CostCenter::forCompany($companyId)->active()->orderBy('name')->get();
        return view('purchase-requisitions.create', compact('products', 'departments', 'costCenters'));
    }

    public function store(Request $request)
    {
        $companyId = Auth::user()->company_id;

        $data = $request->validate([
            'required_date'   => 'nullable|date',
            'priority'        => 'required|in:low,normal,urgent,critical',
            'department_id'   => 'nullable|exists:departments,id',
            'cost_center_id'  => 'nullable|exists:cost_centers,id',
            'justification'   => 'nullable|string',
            'items'           => 'required|array|min:1',
            'items.*.product_name'          => 'required|string|max:255',
            'items.*.product_id'            => 'nullable|exists:products,id',
            'items.*.quantity'              => 'required|numeric|min:0.001',
            'items.*.unit'                  => 'nullable|string|max:50',
            'items.*.estimated_unit_price'  => 'nullable|numeric|min:0',
            'items.*.specification'         => 'nullable|string',
        ]);

        DB::transaction(function () use ($data, $companyId) {
            $pr = PurchaseRequisition::create([
                'company_id'      => $companyId,
                'branch_id'       => Auth::user()->branch_id,
                'requisition_number' => $this->nextRequisitionNumber($companyId),
                'request_date'    => now()->toDateString(),
                'required_date'   => $data['required_date'] ?? null,
                'priority'        => $data['priority'],
                'status'          => 'submitted',
                'requested_by'    => Auth::id(),
                'department_id'   => $data['department_id'] ?? null,
                'cost_center_id'  => $data['cost_center_id'] ?? null,
                'justification'   => $data['justification'] ?? null,
            ]);

            foreach ($data['items'] as $item) {
                $unitPrice = $item['estimated_unit_price'] ?? 0;
                $pr->items()->create([
                    'product_id'            => $item['product_id'] ?? null,
                    'product_name'          => $item['product_name'],
                    'quantity'              => $item['quantity'],
                    'unit'                  => $item['unit'] ?? 'pcs',
                    'estimated_unit_price'  => $unitPrice,
                    'estimated_total'       => (float) $item['quantity'] * (float) $unitPrice,
                    'specification'         => $item['specification'] ?? null,
                ]);
            }
        });

        return redirect()->route('purchase-requisitions.index')
            ->with('success', 'Purchase requisition submitted.');
    }

    public function show(PurchaseRequisition $purchaseRequisition)
    {
        $this->authorizePurchaseRequisitionAccess($purchaseRequisition);
        $purchaseRequisition->load(['items.product', 'requestedBy', 'approvedBy', 'department', 'costCenter']);
        return view('purchase-requisitions.show', compact('purchaseRequisition'));
    }

    public function approve(Request $request, PurchaseRequisition $purchaseRequisition)
    {
        $this->authorizePurchaseRequisitionAccess($purchaseRequisition);
        abort_unless($purchaseRequisition->status === 'submitted', 422, 'Only submitted requisitions can be approved.');

        $purchaseRequisition->update([
            'status'      => 'approved',
            'approved_by' => Auth::id(),
            'approved_at' => now(),
        ]);

        return back()->with('success', 'Requisition approved.');
    }

    public function reject(Request $request, PurchaseRequisition $purchaseRequisition)
    {
        $this->authorizePurchaseRequisitionAccess($purchaseRequisition);
        $request->validate(['rejection_reason' => 'required|string|max:500']);

        $purchaseRequisition->update([
            'status'           => 'rejected',
            'rejection_reason' => $request->rejection_reason,
        ]);

        return back()->with('success', 'Requisition rejected.');
    }

    public function destroy(PurchaseRequisition $purchaseRequisition)
    {
        $this->authorizePurchaseRequisitionAccess($purchaseRequisition);
        abort_if(in_array($purchaseRequisition->status, ['approved', 'converted']), 422,
            'Cannot delete an approved requisition.');
        $purchaseRequisition->delete();
        return redirect()->route('purchase-requisitions.index')->with('success', 'Requisition deleted.');
    }

    private function nextRequisitionNumber(int $companyId): string
    {
        $count = PurchaseRequisition::forCompany($companyId)->withTrashed()->count() + 1;
        return 'PR-' . str_pad($count, 5, '0', STR_PAD_LEFT);
    }

    private function authorizePurchaseRequisitionAccess(PurchaseRequisition $pr): void
    {
        abort_unless($pr->company_id === Auth::user()->company_id, 403);
    }
}
