<?php

namespace App\Http\Controllers;

use App\Models\BillOfMaterials;
use App\Models\BomItem;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class BOMController extends Controller
{
    /**
     * Get the active branch context (id, name) from session.
     *
     * @return array
     */
    private function getActiveBranchContext(): array
    {
        return [
            'id' => session('active_branch_id', Auth::user()->branch_id ?? null),
            'name' => session('active_branch_name', null),
        ];
    }
    public function index()
    {
        $companyId = Auth::user()->company_id;
        $boms      = BillOfMaterials::forCompany($companyId)
            ->with('product')
            ->latest()
            ->paginate(25);
        return view('bom.index', compact('boms'));
    }

    public function create()
    {
        $companyId = Auth::user()->company_id;
        $products  = Product::where('company_id', $companyId)->orderBy('name')->get();
        return view('bom.create', compact('products'));
    }

    public function store(Request $request)
    {
        $companyId = Auth::user()->company_id;

        $data = $request->validate([
            'product_id'      => 'required|exists:products,id',
            'output_quantity' => 'required|numeric|min:0.001',
            'unit'            => 'nullable|string|max:50',
            'bom_type'        => 'required|in:standard,assembly,phantom',
            'instructions'    => 'nullable|string',
            'items'           => 'required|array|min:1',
            'items.*.component_product_id' => 'required|exists:products,id',
            'items.*.quantity'             => 'required|numeric|min:0.001',
            'items.*.unit'                 => 'nullable|string|max:50',
            'items.*.unit_cost'            => 'nullable|numeric|min:0',
            'items.*.scrap_percentage'     => 'nullable|numeric|min:0|max:100',
            'items.*.item_type'            => 'nullable|in:component,byproduct,phantom',
        ]);

        $branch = $this->getActiveBranchContext();
        DB::transaction(function () use ($data, $companyId, $branch) {
            $product = Product::find($data['product_id']);

            $bom = BillOfMaterials::create([
                'company_id'      => $companyId,
                'branch_id'       => $branch['id'],
                'branch_name'     => $branch['name'],
                'bom_number'      => 'BOM-' . strtoupper(substr($product->name, 0, 3)) . '-' . now()->format('Ymd'),
                'product_id'      => $data['product_id'],
                'output_quantity' => $data['output_quantity'],
                'unit'            => $data['unit'] ?? 'pcs',
                'bom_type'        => $data['bom_type'],
                'status'          => 'active',
                'instructions'    => $data['instructions'] ?? null,
                'created_by'      => Auth::id(),
            ]);

            foreach ($data['items'] as $i => $item) {
                $component = Product::find($item['component_product_id']);
                $bom->items()->create([
                    'component_product_id' => $item['component_product_id'],
                    'component_name'       => $component->name ?? '',
                    'quantity'             => $item['quantity'],
                    'unit'                 => $item['unit'] ?? 'pcs',
                    'unit_cost'            => $item['unit_cost'] ?? ($component->cost_price ?? 0),
                    'scrap_percentage'     => $item['scrap_percentage'] ?? 0,
                    'item_type'            => $item['item_type'] ?? 'component',
                    'sort_order'           => $i,
                ]);
            }

            // Recalculate standard cost
            $bom->update(['standard_cost' => $bom->calculateStandardCost()]);
        });

        return redirect()->route('bom.index')->with('success', 'Bill of Materials created.');
    }

    public function show(BillOfMaterials $bom)
    {
        $this->authorizeBomAccess($bom);
        $bom->load(['product', 'items.componentProduct']);
        return view('bom.show', compact('bom'));
    }

    public function destroy(BillOfMaterials $bom)
    {
        $this->authorizeBomAccess($bom);
        abort_if($bom->orders()->where('status', '!=', 'cancelled')->count() > 0, 422,
            'BOM has active manufacturing orders.');
        $bom->delete();
        return redirect()->route('bom.index')->with('success', 'BOM deleted.');
    }

    private function authorizeBomAccess(BillOfMaterials $bom): void
    {
        abort_unless($bom->company_id === Auth::user()->company_id, 403);
    }
}
