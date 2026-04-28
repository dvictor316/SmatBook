<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\SerialNumber;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Schema;

class SerialNumberController extends Controller
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
        $query     = SerialNumber::where('company_id', $companyId)->with(['product', 'lot']);

        if ($status = $request->query('status')) {
            $query->where('status', $status);
        }

        if ($productId = $request->query('product_id')) {
            $query->where('product_id', $productId);
        }

        $serials  = $query->latest()->paginate(25);
        $productsQuery = Product::where('company_id', $companyId)->orderBy('name');

        if (Schema::hasColumn('products', 'track_serials')) {
            $productsQuery->where('track_serials', true);
        }

        $products = $productsQuery->get(['id', 'name']);

        return view('inventory.serials.index', compact('serials', 'products'));
    }

    public function show(SerialNumber $serialNumber)
    {
        abort_unless($serialNumber->company_id === Auth::user()->company_id, 403);
        $serialNumber->load(['product', 'lot']);
        return view('inventory.serials.show', compact('serialNumber'));
    }
}
