<?php

namespace App\Http\Controllers;

use App\Models\PriceList;
use App\Models\PriceListItem;
use App\Models\Product;
use App\Models\Customer;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class PriceListController extends Controller
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
        $companyId  = Auth::user()->company_id;
        $priceLists = PriceList::forCompany($companyId)->withCount('items')->latest()->paginate(25);
        return view('price-lists.index', compact('priceLists'));
    }

    public function create()
    {
        $companyId = Auth::user()->company_id;
        $products  = Product::where('company_id', $companyId)->orderBy('name')->get();
        return view('price-lists.create', compact('products'));
    }

    public function store(Request $request)
    {
        $companyId = Auth::user()->company_id;

        $data = $request->validate([
            'name'           => 'required|string|max:255',
            'currency'       => 'required|string|size:3',
            'discount_type'  => 'nullable|in:percentage,fixed',
            'discount_value' => 'nullable|numeric|min:0',
            'valid_from'     => 'nullable|date',
            'valid_to'       => 'nullable|date|after_or_equal:valid_from',
            'is_default'     => 'boolean',
            'is_active'      => 'boolean',
            'notes'          => 'nullable|string',
            'items'          => 'nullable|array',
            'items.*.product_id'   => 'required|exists:products,id',
            'items.*.price'        => 'required|numeric|min:0',
            'items.*.min_quantity' => 'nullable|numeric|min:0',
        ]);


        $branch = $this->getActiveBranchContext();
        DB::transaction(function () use ($data, $companyId, $branch) {
            $priceList = PriceList::create([
                'company_id'     => $companyId,
                'branch_id'      => $branch['id'],
                'branch_name'    => $branch['name'],
                'name'           => $data['name'],
                'currency'       => $data['currency'],
                'discount_type'  => $data['discount_type'] ?? null,
                'discount_value' => $data['discount_value'] ?? 0,
                'valid_from'     => $data['valid_from'] ?? null,
                'valid_to'       => $data['valid_to'] ?? null,
                'is_default'     => (bool) ($data['is_default'] ?? false),
                'is_active'      => (bool) ($data['is_active'] ?? true),
                'notes'          => $data['notes'] ?? null,
                'created_by'     => Auth::id(),
            ]);

            if (! empty($data['items'])) {
                foreach ($data['items'] as $item) {
                    $priceList->items()->create([
                        'product_id'   => $item['product_id'],
                        'price'        => $item['price'],
                        'min_quantity' => $item['min_quantity'] ?? 1,
                        'currency'     => $data['currency'],
                    ]);
                }
            }
        });

        return redirect()->route('price-lists.index')
            ->with('success', 'Price list created.');
    }

    public function show(PriceList $priceList)
    {
        $this->authorize($priceList);
        $priceList->load(['items.product']);
        return view('price-lists.show', compact('priceList'));
    }

    public function edit(PriceList $priceList)
    {
        $this->authorize($priceList);
        $companyId = Auth::user()->company_id;
        $products  = Product::where('company_id', $companyId)->orderBy('name')->get();
        $priceList->load('items');
        return view('price-lists.edit', compact('priceList', 'products'));
    }

    public function update(Request $request, PriceList $priceList)
    {
        $this->authorize($priceList);

        $data = $request->validate([
            'name'       => 'required|string|max:255',
            'is_active'  => 'boolean',
            'valid_from' => 'nullable|date',
            'valid_to'   => 'nullable|date',
            'notes'      => 'nullable|string',
        ]);

        $data['is_active'] = $request->boolean('is_active', true);
        $priceList->update($data);

        return redirect()->route('price-lists.index')->with('success', 'Price list updated.');
    }

    public function destroy(PriceList $priceList)
    {
        $this->authorize($priceList);
        $priceList->delete();
        return redirect()->route('price-lists.index')->with('success', 'Price list deleted.');
    }

    private function authorize(PriceList $pl): void
    {
        abort_unless($pl->company_id === Auth::user()->company_id, 403);
    }
}
