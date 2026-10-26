<?php

namespace App\Http\Controllers;

use App\Models\Company;
use App\Models\OnlineStoreOrder;
use App\Models\Product;
use App\Models\StorefrontSetting;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class OnlineStoreController extends Controller
{
    public function settings(): View
    {
        $companyId = $this->companyId();
        $company = Company::withoutGlobalScope('tenant')->findOrFail($companyId);
        $setting = StorefrontSetting::firstOrNew(['company_id' => $companyId], [
            'slug' => $this->uniqueSlug($company->name ?: 'store', $companyId),
            'store_name' => $company->name ?: 'Online Store',
        ]);
        $orders = OnlineStoreOrder::with('items')->latest()->paginate(\App\Support\PageSize::resolve())->withQueryString();

        return view('storefront.settings', compact('setting', 'orders'));
    }

    public function updateSettings(Request $request): RedirectResponse
    {
        $companyId = $this->companyId();
        $settingId = StorefrontSetting::where('company_id', $companyId)->value('id');
        $validated = $request->validate([
            'slug' => ['required', 'alpha_dash', 'min:3', 'max:100', Rule::unique('storefront_settings')->ignore($settingId)],
            'custom_domain' => ['nullable', 'string', 'max:191', Rule::unique('storefront_settings')->ignore($settingId)],
            'store_name' => 'required|string|max:191',
            'description' => 'nullable|string|max:1000',
            'contact_phone' => 'nullable|string|max:60',
            'contact_email' => 'nullable|email|max:191',
            'fulfilment_note' => 'nullable|string|max:191',
            'is_enabled' => 'nullable|boolean',
        ]);
        $validated['slug'] = Str::slug($validated['slug']);
        $validated['custom_domain'] = $this->normaliseHost($validated['custom_domain'] ?? null);
        $validated['is_enabled'] = $request->boolean('is_enabled');

        StorefrontSetting::updateOrCreate(['company_id' => $companyId], $validated);

        return back()->with('success', 'Online store settings updated.');
    }

    public function updateOrder(Request $request, OnlineStoreOrder $onlineStoreOrder): RedirectResponse
    {
        abort_unless((int) $onlineStoreOrder->company_id === $this->companyId(), 404);
        $validated = $request->validate([
            'status' => 'required|in:pending_confirmation,confirmed,ready,fulfilled,cancelled',
        ]);

        DB::transaction(function () use ($onlineStoreOrder, $validated) {
            $order = OnlineStoreOrder::query()
                ->with('items')
                ->lockForUpdate()
                ->findOrFail($onlineStoreOrder->id);
            abort_if($order->status === 'fulfilled' && $validated['status'] !== 'fulfilled', 422, 'A fulfilled order cannot be reopened.');
            abort_if($order->status === 'cancelled' && $validated['status'] !== 'cancelled', 422, 'A cancelled order cannot be reopened.');

            if ($validated['status'] === 'cancelled' && $order->status !== 'cancelled') {
                foreach ($order->items as $line) {
                    $product = Product::withoutGlobalScopes()
                        ->where('company_id', $order->company_id)
                        ->lockForUpdate()
                        ->find($line->product_id);
                    if (! $product) {
                        continue;
                    }

                    $restored = max(
                        (float) ($product->stock ?? 0),
                        (float) ($product->stock_quantity ?? 0)
                    ) + (float) $line->quantity;
                    Product::withoutGlobalScopes()->whereKey($product->id)->update([
                        'stock' => $restored,
                        'stock_quantity' => $restored,
                    ]);
                }
            }

            $order->update(['status' => $validated['status']]);
        });

        return back()->with('success', 'Online order status updated.');
    }

    public function catalogue(Request $request, ?string $slug = null): View
    {
        $store = $this->resolvePublicStore($request, $slug);
        $company = Company::withoutGlobalScope('tenant')->findOrFail($store->company_id);
        $search = trim((string) $request->query('q'));
        $products = Product::withoutGlobalScopes()
            ->where('company_id', $store->company_id)
            ->where('status', 'active')
            ->when($search !== '', fn ($query) => $query->where(function ($inner) use ($search) {
                $inner->where('name', 'like', "%{$search}%")
                    ->orWhere('sku', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%");
            }))
            ->orderBy('name')
            ->paginate(\App\Support\PageSize::resolve())->withQueryString();

        return view('storefront.catalogue', compact('store', 'company', 'products', 'search'));
    }

    public function placeOrder(Request $request, ?string $slug = null): RedirectResponse
    {
        $store = $this->resolvePublicStore($request, $slug);
        $validated = $request->validate([
            'customer_name' => 'required|string|max:191',
            'customer_email' => 'nullable|email|max:191',
            'customer_phone' => 'required|string|max:60',
            'delivery_address' => 'nullable|string|max:1000',
            'payment_method' => 'required|in:pay_on_delivery,bank_transfer,pickup',
            'customer_note' => 'nullable|string|max:1000',
            'items' => 'required|array|min:1',
            'items.*' => 'nullable|numeric|min:0|max:1000000',
        ]);

        $quantities = collect($validated['items'])
            ->map(fn ($quantity) => round((float) $quantity, 4))
            ->filter(fn ($quantity) => $quantity > 0);
        abort_if($quantities->isEmpty(), 422, 'Select at least one product quantity.');

        $order = DB::transaction(function () use ($store, $validated, $quantities) {
            $products = Product::withoutGlobalScopes()
                ->where('company_id', $store->company_id)
                ->whereIn('id', $quantities->keys())
                ->where('status', 'active')
                ->lockForUpdate()
                ->get()
                ->keyBy('id');
            abort_if($products->count() !== $quantities->count(), 422, 'One or more selected products are unavailable.');

            $subtotal = 0.0;
            foreach ($quantities as $productId => $quantity) {
                $product = $products->get((int) $productId);
                $available = max((float) ($product->stock ?? 0), (float) ($product->stock_quantity ?? 0));
                abort_if($quantity > $available, 422, "Only {$available} units of {$product->name} are available.");
                $subtotal += $quantity * (float) ($product->price ?? 0);
            }

            $order = OnlineStoreOrder::withoutGlobalScopes()->create([
                'company_id' => $store->company_id,
                'order_number' => 'WEB-' . now()->format('ymdHis') . '-' . strtoupper(Str::random(4)),
                'customer_name' => $validated['customer_name'],
                'customer_email' => $validated['customer_email'] ?? null,
                'customer_phone' => $validated['customer_phone'],
                'delivery_address' => $validated['delivery_address'] ?? null,
                'payment_method' => $validated['payment_method'],
                'status' => 'pending_confirmation',
                'subtotal' => round($subtotal, 2),
                'total_amount' => round($subtotal, 2),
                'customer_note' => $validated['customer_note'] ?? null,
            ]);

            foreach ($quantities as $productId => $quantity) {
                $product = $products->get((int) $productId);
                $order->items()->create([
                    'product_id' => $product->id,
                    'product_name' => $product->name,
                    'sku' => $product->sku,
                    'quantity' => $quantity,
                    'unit_price' => $product->price,
                    'line_total' => round($quantity * (float) $product->price, 2),
                ]);
                $remaining = max(0, max(
                    (float) ($product->stock ?? 0),
                    (float) ($product->stock_quantity ?? 0)
                ) - $quantity);
                Product::withoutGlobalScopes()->whereKey($product->id)->update([
                    'stock' => $remaining,
                    'stock_quantity' => $remaining,
                ]);
            }

            return $order;
        });

        return back()->with('store_order_success', $order->order_number);
    }

    private function resolvePublicStore(Request $request, ?string $slug): StorefrontSetting
    {
        $preResolved = $request->attributes->get('public_storefront');
        if ($preResolved instanceof StorefrontSetting && $preResolved->is_enabled) {
            return $preResolved;
        }

        $query = StorefrontSetting::query()->where('is_enabled', true);
        $store = $slug
            ? $query->where('slug', $slug)->first()
            : $query->where('custom_domain', $this->normaliseHost($request->getHost()))->first();

        abort_unless($store, 404, 'Online store not found.');
        return $store;
    }

    private function companyId(): int
    {
        $companyId = (int) (session('current_tenant_id') ?? Auth::user()?->company_id ?? 0);
        abort_if($companyId < 1, 403, 'An active business workspace is required.');
        return $companyId;
    }

    private function uniqueSlug(string $name, int $companyId): string
    {
        $base = Str::slug($name) ?: 'store-' . $companyId;
        $slug = $base;
        $suffix = 1;
        while (StorefrontSetting::where('slug', $slug)->where('company_id', '!=', $companyId)->exists()) {
            $slug = $base . '-' . ++$suffix;
        }
        return $slug;
    }

    private function normaliseHost(?string $host): ?string
    {
        $host = strtolower(trim((string) $host));
        $host = preg_replace('#^https?://#', '', $host);
        $host = trim((string) strtok($host, '/'));
        $host = preg_replace('/:\d+$/', '', $host);
        return $host === '' ? null : $host;
    }
}
