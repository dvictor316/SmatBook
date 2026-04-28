<?php

namespace App\Http\Controllers;

use App\Models\ManufacturingOrder;
use App\Models\ManufacturingOrderItem;
use App\Models\BillOfMaterials;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class ManufacturingController extends Controller
{
    /**
     * Get the active branch context (id, name) from session.
     *
     * @return array
     */
    private function getActiveBranchContext(): array
    {
        $branchId = session('active_branch_id') ? (string) session('active_branch_id') : null;
        $branchName = session('active_branch_name') ? (string) session('active_branch_name') : null;

        if (!$branchId && !$branchName && \Schema::hasTable('settings')) {
            $companyId = (int) (auth()->user()?->company_id ?? session('current_tenant_id') ?? 0);
            if ($companyId > 0) {
                $key = 'branches_json_company_' . $companyId;
                $raw = (string) (\DB::table('settings')->where('key', $key)->value('value') ?? '');
                $branches = json_decode($raw, true) ?: [];
                $first = collect($branches)->first();
                if ($first) {
                    $branchId = $branchId ?: ($first['id'] ?? null);
                    $branchName = $branchName ?: ($first['name'] ?? null);
                }
            }
        }

        return [
            'id' => $branchId,
            'name' => $branchName,
        ];
    }
    public function index(Request $request)
    {
        $companyId = Auth::user()->company_id;
        $query     = ManufacturingOrder::forCompany($companyId)->with(['product', 'bom']);

        if ($status = $request->query('status')) {
            $query->where('status', $status);
        }

        $orders = $query->latest('planned_start_date')->paginate(25);
        return view('manufacturing.index', compact('orders'));
    }

    public function create()
    {
        $companyId = Auth::user()->company_id;
        $boms      = BillOfMaterials::forCompany($companyId)->where('status', 'active')->with('product')->get();
        return view('manufacturing.create', compact('boms'));
    }

    public function store(Request $request)
    {
        $companyId = Auth::user()->company_id;

        $data = $request->validate([
            'bom_id'               => 'required|exists:bill_of_materials,id',
            'quantity_to_produce'  => 'required|numeric|min:0.001',
            'planned_start_date'   => 'required|date',
            'planned_end_date'     => 'required|date|after_or_equal:planned_start_date',
            'notes'                => 'nullable|string',
        ]);

        $bom = BillOfMaterials::with('items.componentProduct')->findOrFail($data['bom_id']);
        abort_unless($bom->company_id === $companyId, 403);

        $branch = $this->getActiveBranchContext();
        DB::transaction(function () use ($data, $companyId, $bom, $branch) {
            $order = ManufacturingOrder::create([
                'company_id'           => $companyId,
                'branch_id'            => $branch['id'],
                'mo_number'            => $this->nextMoNumber($companyId),
                'bom_id'               => $bom->id,
                'product_id'           => $bom->product_id,
                'planned_quantity'     => $data['quantity_to_produce'],
                'produced_quantity'    => 0,
                'planned_start_date'   => $data['planned_start_date'],
                'planned_end_date'     => $data['planned_end_date'],
                'status'               => 'planned',
                'notes'                => $data['notes'] ?? null,
                'created_by'           => Auth::id(),
            ]);

            $ratio = $data['quantity_to_produce'] / $bom->output_quantity;

            foreach ($bom->items as $bomItem) {
                $order->items()->create([
                    'product_id'            => $bomItem->component_product_id,
                    'product_name'          => $bomItem->component_name,
                    'required_quantity'     => $bomItem->quantity * $ratio,
                    'consumed_quantity'     => 0,
                    'unit_cost'             => $bomItem->unit_cost,
                    'unit'                  => $bomItem->unit,
                ]);
            }
        });

        return redirect()->route('manufacturing.index')
            ->with('success', 'Manufacturing order created.');
    }

    public function show(ManufacturingOrder $manufacturingOrder)
    {
        $this->authorizeManufacturingAccess($manufacturingOrder);
        $manufacturingOrder->load(['product', 'bom', 'items.componentProduct']);
        return view('manufacturing.show', compact('manufacturingOrder'));
    }

    public function start(ManufacturingOrder $manufacturingOrder)
    {
        $this->authorizeManufacturingAccess($manufacturingOrder);
        abort_unless(in_array($manufacturingOrder->status, ['planned', 'draft']), 422,
            'Order cannot be started.');

        $manufacturingOrder->update([
            'status'     => 'in_progress',
            'actual_start_date' => now(),
        ]);

        return back()->with('success', 'Manufacturing order started.');
    }

    public function complete(Request $request, ManufacturingOrder $manufacturingOrder)
    {
        $this->authorizeManufacturingAccess($manufacturingOrder);
        abort_unless($manufacturingOrder->status === 'in_progress', 422,
            'Only in-progress orders can be completed.');

        $data = $request->validate([
            'quantity_produced' => 'required|numeric|min:0.001',
        ]);

        $manufacturingOrder->update([
            'status'            => 'completed',
            'produced_quantity' => $data['quantity_produced'],
            'actual_end_date'   => now(),
        ]);

        return back()->with('success', 'Manufacturing order completed.');
    }

    public function cancel(ManufacturingOrder $manufacturingOrder)
    {
        $this->authorizeManufacturingAccess($manufacturingOrder);
        abort_unless(in_array($manufacturingOrder->status, ['planned', 'draft']), 422,
            'Only planned orders can be cancelled.');

        $manufacturingOrder->update(['status' => 'cancelled']);
        return back()->with('success', 'Manufacturing order cancelled.');
    }

    private function nextMoNumber(int $companyId): string
    {
        $count = ManufacturingOrder::where('company_id', $companyId)->withTrashed()->count() + 1;
        return 'MO-' . str_pad($count, 5, '0', STR_PAD_LEFT);
    }

    private function authorizeManufacturingAccess(ManufacturingOrder $mo): void
    {
        abort_unless($mo->company_id === Auth::user()->company_id, 403);
    }
}
