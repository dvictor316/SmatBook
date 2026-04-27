<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\SerialNumber;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class SerialNumberController extends Controller
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
    public function index(Request $request)
    {
        $companyId = Auth::user()->company_id;
        $query     = SerialNumber::where('company_id', $companyId)->with('product');

        if ($status = $request->query('status')) {
            $query->where('status', $status);
        }

        if ($productId = $request->query('product_id')) {
            $query->where('product_id', $productId);
        }

        $serials  = $query->latest()->paginate(25);
        $products = Product::where('company_id', $companyId)
            ->where('track_serials', true)
            ->orderBy('name')
            ->get(['id', 'name']);

        return view('inventory.serials.index', compact('serials', 'products'));
    }

    public function show(SerialNumber $serialNumber)
    {
        abort_unless($serialNumber->company_id === Auth::user()->company_id, 403);
        $serialNumber->load('product');
        return view('inventory.serials.show', compact('serialNumber'));
    }
}
