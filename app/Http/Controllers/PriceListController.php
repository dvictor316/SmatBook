<?php

namespace App\Http\Controllers;

use App\Models\PriceList;
use App\Models\PriceListItem;
use App\Models\Product;
use App\Models\Customer;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class PriceListController extends Controller
{
    private function normalizedPriceListData(PriceList $priceList): array
    {
        return [
            'discount_type' => $priceList->discount_type
                ?? (($priceList->type ?? null) === 'fixed' ? 'fixed' : (($priceList->type ?? null) === 'discount' ? 'percentage' : null)),
            'discount_value' => $priceList->discount_value ?? $priceList->adjustment_value ?? 0,
            'notes' => $priceList->notes ?? $priceList->description,
        ];
    }

    private function makePriceListPayload(array $data, int $companyId, array $branch, bool $includeBranch = true): array
    {
        $payload = [
            'name'       => $data['name'],
            'currency'   => $data['currency'],
            'valid_from' => $data['valid_from'] ?? null,
            'valid_to'   => $data['valid_to'] ?? null,
            'is_active'  => (bool) ($data['is_active'] ?? true),
            'created_by' => Auth::id(),
        ];

        if (Schema::hasColumn('price_lists', 'company_id')) {
            $payload['company_id'] = $companyId;
        }
        if ($includeBranch && Schema::hasColumn('price_lists', 'branch_id')) {
            $payload['branch_id'] = $branch['id'];
        }
        if ($includeBranch && Schema::hasColumn('price_lists', 'branch_name')) {
            $payload['branch_name'] = $branch['name'];
        }
        if (Schema::hasColumn('price_lists', 'discount_type')) {
            $payload['discount_type'] = $data['discount_type'] ?? null;
        }
        if (Schema::hasColumn('price_lists', 'discount_value')) {
            $payload['discount_value'] = $data['discount_value'] ?? 0;
        }
        if (Schema::hasColumn('price_lists', 'type')) {
            $payload['type'] = ($data['discount_type'] ?? null) === 'fixed' ? 'fixed' : 'discount';
        }
        if (Schema::hasColumn('price_lists', 'adjustment_value')) {
            $payload['adjustment_value'] = $data['discount_value'] ?? 0;
        }
        if (Schema::hasColumn('price_lists', 'is_default')) {
            $payload['is_default'] = (bool) ($data['is_default'] ?? false);
        }
        if (Schema::hasColumn('price_lists', 'notes')) {
            $payload['notes'] = $data['notes'] ?? null;
        }
        if (Schema::hasColumn('price_lists', 'description')) {
            $payload['description'] = $data['notes'] ?? null;
        }

        return $payload;
    }

    private function normalizePriceListItems(array $data)
    {
        return collect($data['items'] ?? [])
            ->map(function ($item) {
                return [
                    'product_id' => $item['product_id'] ?? null,
                    'price' => $item['price'] ?? null,
                    'min_quantity' => $item['min_quantity'] ?? 1,
                ];
            })
            ->filter(function ($item) {
                return $item['product_id'] !== null
                    || $item['price'] !== null
                    || (float) ($item['min_quantity'] ?? 1) !== 1.0;
            })
            ->values();
    }

    private function validatePriceListItemsForRequest($items)
    {
        foreach ($items as $index => $item) {
            if (empty($item['product_id'])) {
                return back()
                    ->withErrors(["items.{$index}.product_id" => 'Please select a product for each price list item.'])
                    ->withInput();
            }

            if ($item['price'] === null || $item['price'] === '') {
                return back()
                    ->withErrors(["items.{$index}.price" => 'Please enter a price for each selected product.'])
                    ->withInput();
            }
        }

        return null;
    }

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
            'items.*.product_id'   => 'nullable|exists:products,id',
            'items.*.price'        => 'nullable|numeric|min:0',
            'items.*.min_quantity' => 'nullable|numeric|min:0',
        ]);

        $items = $this->normalizePriceListItems($data);
        if ($response = $this->validatePriceListItemsForRequest($items)) {
            return $response;
        }

        $branch = $this->getActiveBranchContext();
        DB::transaction(function () use ($data, $items, $companyId, $branch) {
            $priceList = PriceList::create($this->makePriceListPayload($data, $companyId, $branch));

            if ($items->isNotEmpty()) {
                foreach ($items as $item) {
                    $itemPayload = [
                        'product_id'   => $item['product_id'],
                        'min_quantity' => $item['min_quantity'] ?? 1,
                    ];

                    if (Schema::hasColumn('price_list_items', 'price')) {
                        $itemPayload['price'] = $item['price'];
                    }
                    if (Schema::hasColumn('price_list_items', 'unit_price')) {
                        $itemPayload['unit_price'] = $item['price'];
                    }
                    if (Schema::hasColumn('price_list_items', 'currency')) {
                        $itemPayload['currency'] = $data['currency'];
                    }

                    $priceList->items()->create($itemPayload);
                }
            }
        });

        return redirect()->route('price-lists.index')
            ->with('success', 'Price list created.');
    }

    public function show(PriceList $priceList)
    {
        $this->authorizePriceListAccess($priceList);
        $priceList->load(['items.product']);
        $formData = $this->normalizedPriceListData($priceList);
        return view('price-lists.show', compact('priceList', 'formData'));
    }

    public function edit(PriceList $priceList)
    {
        $this->authorizePriceListAccess($priceList);
        $companyId = Auth::user()->company_id;
        $products  = Product::where('company_id', $companyId)->orderBy('name')->get();
        $priceList->load('items');
        $formData = $this->normalizedPriceListData($priceList);
        return view('price-lists.edit', compact('priceList', 'products', 'formData'));
    }

    public function update(Request $request, PriceList $priceList)
    {
        $this->authorizePriceListAccess($priceList);

        $data = $request->validate([
            'name'           => 'required|string|max:255',
            'currency'       => 'required|string|size:3',
            'discount_type'  => 'nullable|in:percentage,fixed',
            'discount_value' => 'nullable|numeric|min:0',
            'is_default'     => 'boolean',
            'is_active'      => 'boolean',
            'valid_from'     => 'nullable|date',
            'valid_to'       => 'nullable|date|after_or_equal:valid_from',
            'notes'          => 'nullable|string',
            'items'          => 'nullable|array',
            'items.*.product_id'   => 'nullable|exists:products,id',
            'items.*.price'        => 'nullable|numeric|min:0',
            'items.*.min_quantity' => 'nullable|numeric|min:0',
        ]);

        $items = $this->normalizePriceListItems($data);
        if ($response = $this->validatePriceListItemsForRequest($items)) {
            return $response;
        }

        $branch = $this->getActiveBranchContext();
        DB::transaction(function () use ($priceList, $data, $items, $branch) {
            $payload = $this->makePriceListPayload($data, (int) ($priceList->company_id ?? Auth::user()->company_id), $branch, false);
            unset($payload['created_by']);
            $priceList->update($payload);

            $priceList->items()->delete();
            foreach ($items as $item) {
                $itemPayload = [
                    'product_id'   => $item['product_id'],
                    'min_quantity' => $item['min_quantity'] ?? 1,
                ];

                if (Schema::hasColumn('price_list_items', 'price')) {
                    $itemPayload['price'] = $item['price'];
                }
                if (Schema::hasColumn('price_list_items', 'unit_price')) {
                    $itemPayload['unit_price'] = $item['price'];
                }
                if (Schema::hasColumn('price_list_items', 'currency')) {
                    $itemPayload['currency'] = $data['currency'];
                }

                $priceList->items()->create($itemPayload);
            }
        });

        return redirect()->route('price-lists.index')->with('success', 'Price list updated.');
    }

    public function destroy(PriceList $priceList)
    {
        $this->authorizePriceListAccess($priceList);
        $priceList->delete();
        return redirect()->route('price-lists.index')->with('success', 'Price list deleted.');
    }

    private function authorizePriceListAccess(PriceList $pl): void
    {
        abort_unless($pl->company_id === Auth::user()->company_id, 403);
    }
}
