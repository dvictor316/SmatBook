<?php

namespace App\Http\Controllers;

use App\Models\GoodsReceivedNote;
use App\Models\GrnItem;
use App\Models\Purchase;
use App\Models\Supplier;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class GoodsReceivedNoteController extends Controller
{
    public function index(Request $request)
    {
        $grns = GoodsReceivedNote::query()
            ->with(['supplier', 'purchaseOrder', 'createdBy'])
            ->tap(fn ($query) => $this->applyTenantBranchScope($query, 'goods_received_notes'))
            ->latest('received_date')
            ->paginate(25);

        return view('grn.index', compact('grns'));
    }

    public function create()
    {
        $suppliers = Supplier::query()
            ->tap(fn ($query) => $this->applyTenantBranchScope($query, 'suppliers'))
            ->orderBy('name')
            ->get();
        $products  = Product::query()
            ->tap(fn ($query) => $this->applyTenantBranchScope($query, 'products'))
            ->orderBy('name')
            ->get();
        $purchaseOrders = Purchase::query()
            ->tap(fn ($query) => $this->applyTenantBranchScope($query, 'purchases'))
            ->orderByDesc('id')
            ->get();
        return view('grn.create', compact('suppliers', 'products', 'purchaseOrders'));
    }

    public function store(Request $request)
    {
        $scope = $this->scopeContext();
        $companyId = $scope['company_id'];
        $branchId = $scope['branch_id'] !== '' ? $scope['branch_id'] : (Auth::user()->branch_id ?? null);

        $data = $request->validate([
            'supplier_id'           => 'required|exists:suppliers,id',
            'purchase_order_id'     => 'nullable|exists:purchases,id',
            'received_date'         => 'required|date',
            'notes'                 => 'nullable|string',
            'items'                 => 'required|array|min:1',
            'items.*.product_id'    => 'required|exists:products,id',
            'items.*.product_name'  => 'required|string|max:255',
            'items.*.ordered_quantity'  => 'nullable|numeric|min:0',
            'items.*.received_quantity' => 'required|numeric|min:0.001',
            'items.*.unit_cost'     => 'nullable|numeric|min:0',
            'items.*.lot_number'    => 'nullable|string|max:100',
            'items.*.serial_number' => 'nullable|string|max:200',
            'items.*.expiry_date'   => 'nullable|date',
        ]);

        DB::transaction(function () use ($data, $companyId, $branchId) {
            $supplier = Supplier::query()
                ->tap(fn ($query) => $this->applyTenantBranchScope($query, 'suppliers'))
                ->findOrFail($data['supplier_id']);

            if (!empty($data['purchase_order_id'])) {
                Purchase::query()
                    ->tap(fn ($query) => $this->applyTenantBranchScope($query, 'purchases'))
                    ->findOrFail($data['purchase_order_id']);
            }

            $grn = GoodsReceivedNote::create([
                'company_id'        => $companyId,
                'branch_id'         => $branchId,
                'grn_number'        => $this->nextGrnNumber($companyId, $branchId),
                'supplier_id'       => $supplier->id,
                'purchase_order_id' => $data['purchase_order_id'] ?? null,
                'received_date'     => $data['received_date'],
                'status'            => 'received',
                'notes'             => $data['notes'] ?? null,
                'created_by'        => Auth::id(),
            ]);

            foreach ($data['items'] as $item) {
                $product = Product::query()
                    ->tap(fn ($query) => $this->applyTenantBranchScope($query, 'products'))
                    ->findOrFail($item['product_id']);

                $grn->items()->create([
                    'product_id'          => $product->id,
                    'product_name'        => $item['product_name'] ?: $product->name,
                    'ordered_quantity'    => $item['ordered_quantity'] ?? 0,
                    'received_quantity'   => $item['received_quantity'],
                    'rejected_quantity'   => 0,
                    'unit_cost'           => $item['unit_cost'] ?? 0,
                    'lot_number'          => $item['lot_number'] ?? null,
                    'serial_number'       => $item['serial_number'] ?? null,
                    'expiry_date'         => $item['expiry_date'] ?? null,
                ]);
            }
        });

        return redirect()->route('grn.index')
            ->with('success', 'Goods Received Note created.');
    }

    public function show(GoodsReceivedNote $goodsReceivedNote)
    {
        $this->authorizeGrnAccess($goodsReceivedNote);
        $goodsReceivedNote->load(['supplier', 'purchaseOrder', 'createdBy', 'items.product']);
        $grn = $goodsReceivedNote;
        return view('grn.show', compact('grn'));
    }

    public function destroy(GoodsReceivedNote $goodsReceivedNote)
    {
        $this->authorizeGrnAccess($goodsReceivedNote);
        abort_if($goodsReceivedNote->status === 'accepted', 422,
            'Cannot delete an accepted GRN.');
        $goodsReceivedNote->delete();
        return redirect()->route('grn.index')->with('success', 'GRN deleted.');
    }

    private function nextGrnNumber(int $companyId, $branchId = null): string
    {
        $query = GoodsReceivedNote::withTrashed()->where('company_id', $companyId);
        if ($branchId !== null && \Illuminate\Support\Facades\Schema::hasColumn('goods_received_notes', 'branch_id')) {
            $query->where('branch_id', $branchId);
        }
        $count = $query->count() + 1;
        return 'GRN-' . str_pad($count, 5, '0', STR_PAD_LEFT);
    }

    private function authorizeGrnAccess(GoodsReceivedNote $grn): void
    {
        $this->authorizeTenantBranchModelAccess($grn);
    }
}
