<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\ProductLot;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Schema;

class LotTrackingController extends Controller
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
        $query     = ProductLot::where('company_id', $companyId)->with('product');

        if ($productId = $request->query('product_id')) {
            $query->where('product_id', $productId);
        }

        $lots     = $query->latest()->paginate(25);
        $productsQuery = Product::where('company_id', $companyId)->orderBy('name');

        if (Schema::hasColumn('products', 'track_lots')) {
            $productsQuery->where('track_lots', true);
        }

        $products = $productsQuery->get(['id', 'name']);

        return view('inventory.lots.index', compact('lots', 'products'));
    }

    public function show(ProductLot $productLot)
    {
        abort_unless($productLot->company_id === Auth::user()->company_id, 403);
        $productLot->load(['product', 'serialNumbers']);
        return view('inventory.lots.show', compact('productLot'));
    }

    public function adjust(Request $request, ProductLot $productLot)
    {
        abort_unless($productLot->company_id === Auth::user()->company_id, 403);

        $data = $request->validate([
            'adjustment' => 'required|numeric',
            'reason'     => 'required|string|max:255',
        ]);

        $newQty = $productLot->quantity + $data['adjustment'];
        abort_if($newQty < 0, 422, 'Quantity cannot go below zero.');

        $branch = $this->getActiveBranchContext();
        $productLot->update([
            'quantity' => $newQty,
            'branch_id' => $branch['id'],
            'branch_name' => $branch['name'],
        ]);

        return back()->with('success', 'Lot quantity adjusted.');
    }
}
