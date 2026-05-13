<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Estimate;
use App\Models\Customer;
use App\Models\Product;
use App\Support\PriceListUsage;
use Carbon\Carbon;
use Illuminate\Support\Facades\Schema;

class EstimateController extends Controller
{
    public function __construct(private readonly PriceListUsage $priceListUsage)
    {
    }

    private function normalizeEstimatePayload(array $validated): array
    {
        $items = $this->normalizeEstimateItems($validated['items'] ?? []);
        if (!empty($items)) {
            $subtotal = collect($items)->sum('line_subtotal');
            $tax = collect($items)->sum('tax');
            $discount = collect($items)->sum('discount');
            $validated['items'] = $items;
        } else {
            unset($validated['items']);
            $subtotal = Estimate::normalizeMoney($validated['subtotal'] ?? 0);
            $tax = Estimate::normalizeMoney($validated['tax'] ?? 0);
            $discount = Estimate::normalizeMoney($validated['discount'] ?? 0);
        }

        $validated['subtotal'] = $subtotal;
        $validated['tax'] = $tax;
        $validated['discount'] = $discount;
        $validated['total_amount'] = Estimate::calculateTotal($subtotal, $tax, $discount);

        return $validated;
    }

    private function normalizeEstimateItems(array $items): array
    {
        return collect($items)
            ->map(function ($item) {
                $productId = $item['product_id'] ?? null;
                $name = trim((string) ($item['name'] ?? ''));
                $quantity = max(0, (float) ($item['quantity'] ?? 0));
                $rate = max(0, (float) ($item['rate'] ?? 0));
                $discount = max(0, (float) ($item['discount'] ?? 0));
                $tax = max(0, (float) ($item['tax'] ?? 0));
                $lineSubtotal = round($quantity * $rate, 2);
                $amount = round(max(0, $lineSubtotal - $discount + $tax), 2);

                return [
                    'product_id' => $productId ? (int) $productId : null,
                    'name' => $name,
                    'price_source' => $item['price_source'] ?? 'retail',
                    'price_list_id' => !empty($item['price_list_id']) ? (int) $item['price_list_id'] : null,
                    'quantity' => $quantity,
                    'rate' => round($rate, 2),
                    'discount' => round($discount, 2),
                    'tax' => round($tax, 2),
                    'line_subtotal' => $lineSubtotal,
                    'amount' => $amount,
                ];
            })
            ->filter(fn ($item) => $item['product_id'] || $item['name'] !== '' || $item['quantity'] > 0 || $item['rate'] > 0)
            ->values()
            ->all();
    }

    private function estimateFormData(?Estimate $estimate = null): array
    {
        $companyId = (int) (auth()->user()?->company_id ?? session('current_tenant_id') ?? 0);

        $customersQuery = Customer::query();
        if ($companyId > 0 && Schema::hasColumn('customers', 'company_id')) {
            $customersQuery->where('company_id', $companyId);
        }

        $productsQuery = Product::query();
        if ($companyId > 0 && Schema::hasColumn('products', 'company_id')) {
            $productsQuery->where('company_id', $companyId);
        }

        $customers = $customersQuery
            ->orderBy(Schema::hasColumn('customers', 'name') ? 'name' : 'id')
            ->get();

        $products = $productsQuery
            ->orderBy(Schema::hasColumn('products', 'name') ? 'name' : 'id')
            ->get();

        $priceLists = $this->priceListUsage->activeForCurrentContext($companyId);

        return [
            'customers' => $customers,
            'products' => $products,
            'priceLists' => $priceLists,
            'priceListData' => $this->priceListUsage->toFrontend($priceLists),
            'estimateItems' => old('items', $estimate?->items ?: [[
                'product_id' => '',
                'name' => '',
                'price_source' => 'retail',
                'price_list_id' => $estimate?->price_list_id,
                'quantity' => 1,
                'rate' => 0,
                'discount' => 0,
                'tax' => 0,
                'amount' => 0,
            ]]),
        ];
    }

    private function applyTenantScope($query)
    {
        $companyId = (int) (auth()->user()?->company_id ?? session('current_tenant_id') ?? 0);
        $userId = (int) (auth()->id() ?? 0);

        if ($companyId > 0 && Schema::hasColumn('estimates', 'company_id')) {
            $query->where('company_id', $companyId);
        } elseif ($userId > 0 && Schema::hasColumn('estimates', 'user_id')) {
            $query->where('user_id', $userId);
        }

        return $query;
    }

    private function conversionItems(Estimate $estimate, string $target): array
    {
        return collect($estimate->items ?? [])
            ->map(function ($item) use ($target) {
                $quantity = max(0.01, (float) ($item['quantity'] ?? $item['qty'] ?? 1));
                $rate = round((float) ($item['rate'] ?? $item['price'] ?? 0), 2);
                $lineSubtotal = round($quantity * $rate, 2);
                $discount = round((float) ($item['discount'] ?? 0), 2);
                $tax = round((float) ($item['tax'] ?? 0), 2);
                $amount = round(max(0, $lineSubtotal - $discount + $tax), 2);

                if ($target === 'pos') {
                    return [
                        'product_id' => !empty($item['product_id']) ? (int) $item['product_id'] : null,
                        'name' => $item['name'] ?? '',
                        'qty' => $quantity,
                        'rate' => $rate,
                        'discount' => $discount,
                        'tax' => $tax,
                    ];
                }

                return [
                    'product_id' => !empty($item['product_id']) ? (int) $item['product_id'] : '',
                    'name' => $item['name'] ?? '',
                    'price_level' => $item['price_source'] ?? 'retail',
                    'qty' => $quantity,
                    'rate' => number_format($rate, 2, '.', ''),
                    'discount' => $discount,
                    'tax' => $tax,
                    'amount' => $amount,
                ];
            })
            ->filter(fn ($item) => !empty($item['product_id']) || trim((string) ($item['name'] ?? '')) !== '')
            ->values()
            ->all();
    }

    public function convertToInvoice($id)
    {
        $estimate = $this->applyTenantScope(Estimate::with('customer'))->findOrFail($id);
        $invoiceDate = Carbon::parse($estimate->issue_date ?? now())->format('d-m-Y');
        $dueDate = Carbon::parse($estimate->expiry_date ?? now()->addDays(30))->format('d-m-Y');

        session()->flash('quotation_prefill', [
            'customer_id' => $estimate->customer_id,
            'invoice_date' => $invoiceDate,
            'due_date' => $dueDate,
            'description' => trim("Converted from Sales Order {$estimate->estimate_number}.\n\n" . (string) ($estimate->notes ?? '')),
            'items' => $this->conversionItems($estimate, 'invoice'),
        ]);

        return redirect()
            ->route('add-invoice')
            ->with('info', 'Sales order loaded into the invoice form. Review, choose customer if needed, then save/send.');
    }

    public function convertToCashSale($id)
    {
        $estimate = $this->applyTenantScope(Estimate::with('customer'))->findOrFail($id);

        session()->flash('pos_prefill', [
            'source' => 'sales_order',
            'reference' => $estimate->estimate_number ?? ('SO-' . $estimate->id),
            'customer_id' => $estimate->customer_id,
            'customer_name' => $estimate->customer?->name ?? $estimate->customer?->customer_name ?? null,
            'items' => $this->conversionItems($estimate, 'pos'),
        ]);

        return redirect()
            ->route('sales.showPos')
            ->with('success', 'Sales order loaded into POS. Review stock, payment account, and collect payment to complete the cash sale.');
    }

    public function index()
    {
        $estimates = $this->applyTenantScope(Estimate::with('customer'))->get();

        $sent = $this->applyTenantScope(Estimate::query())->where('status', 'Sent')->count();
        $draft = $this->applyTenantScope(Estimate::query())->where('status', 'Draft')->count();
        $expired = $this->applyTenantScope(Estimate::query())->where('status', 'Expired')->count();

        // Updated view path to match your Blade file location
        return view('livewire.index-estimates', compact('estimates', 'sent', 'draft', 'expired'));
    }

    public function create()
    {
        return view('estimates.create', $this->estimateFormData());
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'estimate_number' => 'required|string|unique:estimates',
            'customer_id' => 'required|exists:customers,id',
            'price_list_id' => 'nullable|exists:price_lists,id',
            'issue_date' => 'required|date',
            'expiry_date' => 'required|date|after_or_equal:issue_date',
            'items' => 'nullable|array',
            'items.*.product_id' => 'nullable|exists:products,id',
            'items.*.name' => 'nullable|string|max:255',
            'items.*.price_source' => 'nullable|string|max:50',
            'items.*.price_list_id' => 'nullable|exists:price_lists,id',
            'items.*.quantity' => 'nullable|numeric|min:0',
            'items.*.rate' => 'nullable|numeric|min:0',
            'items.*.discount' => 'nullable|numeric|min:0',
            'items.*.tax' => 'nullable|numeric|min:0',
            'subtotal' => 'nullable|numeric',
            'tax' => 'nullable|numeric',
            'discount' => 'nullable|numeric',
            'total_amount' => 'nullable|numeric',
            'status' => 'required|in:Draft,Sent,Accepted,Declined,Expired',
            'notes' => 'nullable|string',
        ]);

        $payload = $this->normalizeEstimatePayload($validated);
        if (Schema::hasColumn('estimates', 'company_id')) {
            $payload['company_id'] = auth()->user()?->company_id ?? session('current_tenant_id');
        }
        if (Schema::hasColumn('estimates', 'user_id')) {
            $payload['user_id'] = auth()->id();
        }

        Estimate::create($payload);

        return redirect()->route('estimates.index')->with('success', 'Estimate created successfully.');
    }

    public function show($id)
    {
        $estimate = $this->applyTenantScope(Estimate::with('customer'))->findOrFail($id);
        return view('estimates.show', compact('estimate'));
    }

    public function edit($id)
    {
        $estimate = $this->applyTenantScope(Estimate::query())->findOrFail($id);

        return view('estimates.edit', array_merge(['estimate' => $estimate], $this->estimateFormData($estimate)));
    }

    public function update(Request $request, $id)
    {
        $estimate = $this->applyTenantScope(Estimate::query())->findOrFail($id);

        $validated = $request->validate([
            'estimate_number' => 'required|string|unique:estimates,estimate_number,' . $estimate->id,
            'customer_id' => 'required|exists:customers,id',
            'price_list_id' => 'nullable|exists:price_lists,id',
            'issue_date' => 'required|date',
            'expiry_date' => 'required|date|after_or_equal:issue_date',
            'items' => 'nullable|array',
            'items.*.product_id' => 'nullable|exists:products,id',
            'items.*.name' => 'nullable|string|max:255',
            'items.*.price_source' => 'nullable|string|max:50',
            'items.*.price_list_id' => 'nullable|exists:price_lists,id',
            'items.*.quantity' => 'nullable|numeric|min:0',
            'items.*.rate' => 'nullable|numeric|min:0',
            'items.*.discount' => 'nullable|numeric|min:0',
            'items.*.tax' => 'nullable|numeric|min:0',
            'subtotal' => 'nullable|numeric',
            'tax' => 'nullable|numeric',
            'discount' => 'nullable|numeric',
            'total_amount' => 'nullable|numeric',
            'status' => 'required|in:Draft,Sent,Accepted,Declined,Expired',
            'notes' => 'nullable|string',
        ]);

        $estimate->update($this->normalizeEstimatePayload($validated));

        return redirect()->route('estimates.index')->with('success', 'Estimate updated successfully.');
    }

    public function destroy($id)
    {
        $estimate = $this->applyTenantScope(Estimate::query())->findOrFail($id);
        $estimate->delete();

        return redirect()->route('estimates.index')->with('success', 'Estimate deleted successfully.');
    }
}
