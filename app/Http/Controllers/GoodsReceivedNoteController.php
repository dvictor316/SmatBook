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
use Illuminate\Support\Facades\Schema;
use App\Support\BranchInventoryService;

class GoodsReceivedNoteController extends Controller
{
    public function __construct(private readonly BranchInventoryService $branchInventory)
    {
    }

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
        $requestedPurchaseOrderId = (int) request('purchase_order_id', 0);
        $suppliers = Supplier::query()
            ->tap(fn ($query) => $this->applyTenantBranchScope($query, 'suppliers'))
            ->orderBy('name')
            ->get();
        $products  = Product::query()
            ->tap(fn ($query) => $this->applyTenantBranchScope($query, 'products'))
            ->orderBy('name')
            ->get();
        $purchaseOrders = Purchase::query()
            ->with(['supplier', 'items.product'])
            ->tap(fn ($query) => $this->applyTenantBranchScope($query, 'purchases'))
            ->where(function ($query) {
                $query->whereNull('status')
                    ->orWhereNotIn('status', ['received', 'closed', 'cancelled', 'canceled']);
            })
            ->orderByDesc('id')
            ->get();
        $purchaseOrders = $purchaseOrders->filter(function ($purchaseOrder) {
            $items = $purchaseOrder->items ?? collect();

            return $items->contains(function ($item) {
                $ordered = (float) ($item->qty ?? 0);
                $received = Schema::hasColumn('purchase_items', 'received_qty')
                    ? (float) ($item->received_qty ?? 0)
                    : 0;

                return $ordered - $received > 0.0001;
            });
        })->values();

        $selectedPurchaseOrder = $requestedPurchaseOrderId > 0
            ? $purchaseOrders->firstWhere('id', $requestedPurchaseOrderId)
            : null;

        return view('grn.create', compact('suppliers', 'products', 'purchaseOrders', 'selectedPurchaseOrder'));
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
            'items.*.purchase_item_id' => 'nullable|integer',
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

            $purchaseOrder = null;
            if (!empty($data['purchase_order_id'])) {
                $purchaseOrder = Purchase::query()
                    ->with('items.product')
                    ->tap(fn ($query) => $this->applyTenantBranchScope($query, 'purchases'))
                    ->findOrFail($data['purchase_order_id']);

                if ((int) $purchaseOrder->supplier_id !== (int) $supplier->id) {
                    throw new \RuntimeException('Selected supplier does not match the selected purchase order.');
                }
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

            $receiptStatuses = [];

            foreach ($data['items'] as $item) {
                $product = Product::query()
                    ->tap(fn ($query) => $this->applyTenantBranchScope($query, 'products'))
                    ->findOrFail($item['product_id']);

                $orderedQuantity = (float) ($item['ordered_quantity'] ?? 0);
                $receivedQuantity = (float) $item['received_quantity'];
                $purchaseItem = null;

                if ($purchaseOrder) {
                    $purchaseItemId = (int) ($item['purchase_item_id'] ?? 0);
                    $purchaseItem = $purchaseItemId > 0
                        ? $purchaseOrder->items->firstWhere('id', $purchaseItemId)
                        : $purchaseOrder->items->firstWhere('product_id', $product->id);

                    if (!$purchaseItem) {
                        throw new \RuntimeException("{$product->name} is not on the selected purchase order.");
                    }

                    $orderedQuantity = (float) ($purchaseItem->qty ?? 0);
                    $alreadyReceived = Schema::hasColumn('purchase_items', 'received_qty')
                        ? (float) ($purchaseItem->received_qty ?? 0)
                        : 0;
                    $remaining = max(0, $orderedQuantity - $alreadyReceived);

                    if ($receivedQuantity > $remaining) {
                        throw new \RuntimeException("Received quantity for {$product->name} exceeds the outstanding purchase order quantity.");
                    }

                    if (Schema::hasColumn('purchase_items', 'received_qty')) {
                        $purchaseItem->received_qty = round($alreadyReceived + $receivedQuantity, 4);
                        $purchaseItem->save();
                    }

                    $newRemaining = max(0, $orderedQuantity - (float) ($purchaseItem->received_qty ?? $alreadyReceived + $receivedQuantity));
                    $receiptStatuses[] = $newRemaining > 0 ? 'partial' : 'received';
                }

                $grn->items()->create([
                    'product_id'          => $product->id,
                    'product_name'        => $item['product_name'] ?: $product->name,
                    'ordered_quantity'    => $orderedQuantity,
                    'received_quantity'   => $receivedQuantity,
                    'rejected_quantity'   => 0,
                    'unit_cost'           => $item['unit_cost'] ?? 0,
                    'lot_number'          => $item['lot_number'] ?? null,
                    'serial_numbers'      => $item['serial_number'] ?? null,
                    'expiry_date'         => $item['expiry_date'] ?? null,
                    'total_cost'          => round($receivedQuantity * (float) ($item['unit_cost'] ?? 0), 2),
                ]);

                if (Schema::hasColumn('products', 'stock')) {
                    $product->increment('stock', $receivedQuantity);
                }
                if (Schema::hasColumn('products', 'stock_quantity')) {
                    $product->increment('stock_quantity', $receivedQuantity);
                }

                $this->branchInventory->adjustBranchStock(
                    $product,
                    $receivedQuantity,
                    [
                        'id' => $branchId,
                        'name' => session('active_branch_name'),
                    ],
                    (int) ($product->company_id ?? $companyId)
                );
            }

            if ($purchaseOrder) {
                $hasOutstanding = $purchaseOrder->items->contains(function ($item) {
                    $ordered = (float) ($item->qty ?? 0);
                    $received = Schema::hasColumn('purchase_items', 'received_qty')
                        ? (float) ($item->received_qty ?? 0)
                        : 0;

                    return $ordered - $received > 0.0001;
                });

                $purchaseOrder->status = $hasOutstanding ? 'partially_received' : 'received';
                $purchaseOrder->save();
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
