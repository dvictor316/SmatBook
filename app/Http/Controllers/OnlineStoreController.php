<?php

namespace App\Http\Controllers;

use App\Models\Company;
use App\Models\OnlineStoreOrder;
use App\Models\Payment;
use App\Models\Product;
use App\Models\Sale;
use App\Models\StorefrontSetting;
use App\Models\User;
use App\Notifications\OnlineStoreOrderPlaced;
use App\Services\WhatsAppOrderNotifier;
use App\Support\BranchInventoryService;
use App\Support\LedgerService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class OnlineStoreController extends Controller
{
    public function __construct(private readonly BranchInventoryService $branchInventory)
    {
    }

    public function settings(): View
    {
        $companyId = $this->companyId();
        $company = Company::withoutGlobalScope('tenant')->findOrFail($companyId);
        $branches = $this->branchesForCompany($companyId);
        $setting = StorefrontSetting::firstOrNew(['company_id' => $companyId], [
            'slug' => $this->uniqueSlug($company->name ?: 'store', $companyId),
            'store_name' => $company->name ?: 'Online Store',
        ]);
        $selectedBranch = $branches->firstWhere('id', (string) $setting->stock_branch_id)
            ?: $branches->firstWhere('id', trim((string) session('active_branch_id', '')))
            ?: $branches->firstWhere('is_active', true)
            ?: $branches->first();
        $setting->stock_branch_id = $selectedBranch['id'] ?? null;
        $setting->stock_branch_name = $selectedBranch['name'] ?? null;
        $orders = OnlineStoreOrder::with(['items', 'sale'])->latest()->paginate(\App\Support\PageSize::resolve())->withQueryString();
        $storeThemes = StorefrontSetting::themes();

        return view('storefront.settings', compact('setting', 'orders', 'storeThemes', 'branches'));
    }

    public function updateSettings(Request $request): RedirectResponse
    {
        $companyId = $this->companyId();
        $branches = $this->branchesForCompany($companyId);
        $settingId = StorefrontSetting::where('company_id', $companyId)->value('id');
        $validated = $request->validate([
            'slug' => ['required', 'alpha_dash', 'min:3', 'max:100', Rule::unique('storefront_settings')->ignore($settingId)],
            'custom_domain' => ['nullable', 'string', 'max:191', Rule::unique('storefront_settings')->ignore($settingId)],
            'store_name' => 'required|string|max:191',
            'store_theme' => ['required', Rule::in(array_keys(StorefrontSetting::themes()))],
            'stock_branch_id' => ['nullable', 'string', 'max:64', Rule::in($branches->pluck('id')->all())],
            'description' => 'nullable|string|max:1000',
            'contact_phone' => 'nullable|string|max:60',
            'contact_email' => 'nullable|email|max:191',
            'owner_whatsapp_number' => ['nullable', 'string', 'regex:/^\+[1-9][0-9]{7,14}$/'],
            'fulfilment_note' => 'nullable|string|max:191',
            'is_enabled' => 'nullable|boolean',
        ]);
        $stockBranch = $branches->firstWhere('id', (string) ($validated['stock_branch_id'] ?? ''));
        if ($request->boolean('is_enabled') && $branches->isNotEmpty() && ! $stockBranch) {
            return back()
                ->withErrors(['stock_branch_id' => 'Select the branch that will fulfil online store orders.'])
                ->withInput();
        }

        $validated['slug'] = Str::slug($validated['slug']);
        $validated['custom_domain'] = $this->normaliseHost($validated['custom_domain'] ?? null);
        $validated['is_enabled'] = $request->boolean('is_enabled');
        $validated['stock_branch_id'] = $stockBranch['id'] ?? null;
        $validated['stock_branch_name'] = $stockBranch['name'] ?? null;

        DB::transaction(function () use ($companyId, $validated, $stockBranch) {
            StorefrontSetting::updateOrCreate(['company_id' => $companyId], $validated);
            if ($stockBranch) {
                $this->reconcileUnassignedOrders($companyId, $stockBranch);
            }
        });

        return back()->with('success', 'Online store settings updated.');
    }

    public function updateOrder(Request $request, OnlineStoreOrder $onlineStoreOrder): RedirectResponse
    {
        abort_unless((int) $onlineStoreOrder->company_id === $this->companyId(), 404);
        $validated = $request->validate([
            'status' => 'required|in:pending_confirmation,confirmed,ready,fulfilled,cancelled',
        ]);

        $previousStatus = $onlineStoreOrder->status;
        DB::transaction(function () use ($onlineStoreOrder, $validated) {
            $order = OnlineStoreOrder::query()
                ->with('items')
                ->lockForUpdate()
                ->findOrFail($onlineStoreOrder->id);
            abort_if($order->status === 'fulfilled' && $validated['status'] !== 'fulfilled', 422, 'A fulfilled order cannot be reopened.');
            abort_if($order->status === 'cancelled' && $validated['status'] !== 'cancelled', 422, 'A cancelled order cannot be reopened.');

            if ($validated['status'] === 'fulfilled' && $order->status !== 'fulfilled') {
                $this->postOnlineOrderSale($order);
            }

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
                    if (filled($order->branch_id) && Schema::hasTable('product_branch_stocks')) {
                        $this->branchInventory->adjustBranchStock(
                            $product,
                            (float) $line->quantity,
                            ['id' => (string) $order->branch_id, 'name' => (string) ($order->branch_name ?? '')],
                            (int) $order->company_id,
                            false
                        );
                    }
                    $this->recordOnlineStockMovement(
                        $product,
                        (float) $line->quantity,
                        'in',
                        'Online Store Order Cancelled: ' . $order->order_number,
                        filled($order->branch_id)
                            ? ['id' => (string) $order->branch_id, 'name' => (string) ($order->branch_name ?? '')]
                            : null,
                        (int) $order->company_id
                    );
                }
            }

            $order->update(['status' => $validated['status']]);
        });

        if ($previousStatus !== $validated['status']) {
            $this->clearDashboardMetricsCache($onlineStoreOrder->branch_id);
            $store = StorefrontSetting::where('company_id', $onlineStoreOrder->company_id)->first();
            if ($store) {
                $this->notifyOwner($store, OnlineStoreOrder::with('items')->findOrFail($onlineStoreOrder->id));
            }
        }

        return back()->with('success', 'Online order status updated.');
    }

    public function catalogue(Request $request, ?string $slug = null): View
    {
        $store = $this->resolvePublicStore($request, $slug);
        $company = Company::withoutGlobalScope('tenant')->findOrFail($store->company_id);
        $products = Product::withoutGlobalScopes()
            ->where('company_id', $store->company_id)
            ->where('status', 'active')
            ->orderBy('name')
            ->limit(40)
            ->get();
        $this->attachStoreStock($products, $store);

        return view('storefront.home', compact('store', 'company', 'products'));
    }

    public function productGallery(Request $request, ?string $slug = null): View
    {
        $store = $this->resolvePublicStore($request, $slug);
        $company = Company::withoutGlobalScope('tenant')->findOrFail($store->company_id);
        $orderContact = $store->orderContact();
        $search = trim((string) $request->query('q'));
        $products = Product::withoutGlobalScopes()
            ->where('company_id', $store->company_id)
            ->where('status', 'active')
            ->when($search !== '', fn ($query) => $query->where(function ($inner) use ($search) {
                $inner->where('name', 'like', "%{$search}%")
                    ->orWhere('sku', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%")
                    ->orWhere('brand', 'like', "%{$search}%")
                    ->orWhere('fashion_size', 'like', "%{$search}%")
                    ->orWhere('fashion_color', 'like', "%{$search}%");
            }))
            ->orderBy('name')
            ->paginate(\App\Support\PageSize::resolve())->withQueryString();
        $products->getCollection()->transform(function (Product $product) use ($store) {
            $product->setAttribute('storefront_available_stock', $this->availableStoreStock($product, $store));

            return $product;
        });

        $orderToken = (string) Str::uuid();

        return view('storefront.catalogue', compact('store', 'company', 'products', 'search', 'orderContact', 'orderToken'));
    }

    public function about(Request $request, ?string $slug = null): View
    {
        $store = $this->resolvePublicStore($request, $slug);
        $company = Company::withoutGlobalScope('tenant')->findOrFail($store->company_id);

        return view('storefront.about', compact('store', 'company'));
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
            'order_token' => ['required', 'uuid'],
            'items' => 'required|array|min:1',
            'items.*' => 'nullable|numeric|min:0|max:1000000',
        ]);

        $quantities = collect($validated['items'])
            ->map(fn ($quantity) => round((float) $quantity, 4))
            ->filter(fn ($quantity) => $quantity > 0);
        abort_if($quantities->isEmpty(), 422, 'Select at least one product quantity.');
        $stockBranch = $this->storeStockBranch($store);
        $orderToken = $validated['order_token'];

        [$order, $created] = DB::transaction(function () use ($store, $validated, $quantities, $stockBranch, $orderToken) {
            StorefrontSetting::withoutGlobalScopes()
                ->whereKey($store->id)
                ->lockForUpdate()
                ->firstOrFail();
            $existingOrder = OnlineStoreOrder::withoutGlobalScopes()
                ->where('company_id', $store->company_id)
                ->where('submission_token', $orderToken)
                ->lockForUpdate()
                ->first();
            if ($existingOrder) {
                return [$existingOrder, false];
            }

            $products = Product::withoutGlobalScopes()
                ->where('company_id', $store->company_id)
                ->whereIn('id', $quantities->keys())
                ->where('status', 'active')
                ->lockForUpdate()
                ->get()
                ->keyBy('id');
            abort_if($products->count() !== $quantities->count(), 422, 'One or more selected products are unavailable.');

            $subtotal = 0.0;
            $availableStockByProduct = [];
            foreach ($quantities as $productId => $quantity) {
                $product = $products->get((int) $productId);
                $available = $this->availableStoreStock($product, $store, $stockBranch, true);
                abort_if($quantity > $available, 422, "Only {$available} units of {$product->name} are available.");
                $availableStockByProduct[$product->id] = $available;
                $subtotal += $quantity * (float) ($product->price ?? 0);
            }

            $order = OnlineStoreOrder::withoutGlobalScopes()->create([
                'company_id' => $store->company_id,
                'branch_id' => $stockBranch['id'] ?? null,
                'branch_name' => $stockBranch['name'] ?? null,
                'submission_token' => $orderToken,
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
                    'product_name' => trim($product->name . ($product->variantLabel() !== '' ? ' · ' . $product->variantLabel() : '')),
                    'sku' => $product->sku,
                    'quantity' => $quantity,
                    'unit_price' => $product->price,
                    'line_total' => round($quantity * (float) $product->price, 2),
                ]);
                $globalRemaining = max(0, max(
                    (float) ($product->stock ?? 0),
                    (float) ($product->stock_quantity ?? 0)
                ) - $quantity);
                Product::withoutGlobalScopes()->whereKey($product->id)->update([
                    'stock' => $globalRemaining,
                    'stock_quantity' => $globalRemaining,
                ]);

                if ($stockBranch && Schema::hasTable('product_branch_stocks')) {
                    if ($this->branchStockRow($product, $stockBranch, (int) $store->company_id)) {
                        $this->branchInventory->adjustBranchStock(
                            $product,
                            -1 * (float) $quantity,
                            $stockBranch,
                            (int) $store->company_id,
                            false
                        );
                    } else {
                        $this->branchInventory->setBranchStock(
                            $product,
                            max(0, (float) $availableStockByProduct[$product->id] - (float) $quantity),
                            $stockBranch,
                            (int) $store->company_id
                        );
                    }
                }
                $this->recordOnlineStockMovement(
                    $product,
                    (float) $quantity,
                    'out',
                    'Online Store Order: ' . $order->order_number,
                    $stockBranch,
                    (int) $store->company_id
                );
            }

            return [$order, true];
        });

        if (! $created) {
            return back()
                ->with('store_order_success', $order->order_number)
                ->with('store_order_duplicate', true);
        }

        $orderContact = $store->orderContact();
        $this->notifyBusinessOwnerInApp($store, $order);
        $this->notifyOwner($store, $order, $orderContact);
        $order->loadMissing('items');
        $orderMessage = "Hello {$store->store_name}, I placed an online order.\n"
            . "Order: {$order->order_number}\n"
            . "Customer: {$order->customer_name}\n"
            . "Phone: {$order->customer_phone}\n"
            . "Payment: " . str($order->payment_method)->replace('_', ' ')->title();
        if (filled($order->customer_email)) {
            $orderMessage .= "\nEmail: {$order->customer_email}";
        }
        if (filled($order->delivery_address)) {
            $orderMessage .= "\nDelivery address: {$order->delivery_address}";
        }
        foreach ($order->items as $item) {
            $orderMessage .= "\n{$item->product_name} x {$item->quantity} - " . number_format((float) $item->line_total, 2);
        }
        $orderMessage .= "\nTotal: " . number_format((float) $order->total_amount, 2)
            . "\nPlease confirm availability and fulfilment.";
        if (filled($order->customer_note)) {
            $orderMessage .= "\nCustomer note: {$order->customer_note}";
        }

        $whatsappLink = $orderContact['whatsapp']
            ? 'https://wa.me/' . $orderContact['whatsapp'] . '?text=' . rawurlencode($orderMessage)
            : null;
        $smsLink = $orderContact['sms']
            ? 'sms:' . $orderContact['sms'] . '?body=' . rawurlencode($orderMessage)
            : null;
        $emailLink = $orderContact['email']
            ? 'mailto:' . $orderContact['email']
                . '?subject=' . rawurlencode('Online store order ' . $order->order_number)
                . '&body=' . rawurlencode($orderMessage)
            : null;

        return back()
            ->with('store_order_success', $order->order_number)
            ->with('store_whatsapp_link', $whatsappLink)
            ->with('store_sms_link', $smsLink)
            ->with('store_email_link', $emailLink);
    }

    private function notifyOwner(StorefrontSetting $store, OnlineStoreOrder $order, ?array $orderContact = null): void
    {
        $result = app(WhatsAppOrderNotifier::class)->send($store, $order, $orderContact);
        $order->update([
            'whatsapp_notification_status' => $result['status'],
            'whatsapp_notification_error' => $result['error'],
            'whatsapp_notified_at' => $result['status'] === 'sent' ? now() : null,
        ]);
    }

    private function notifyBusinessOwnerInApp(StorefrontSetting $store, OnlineStoreOrder $order): void
    {
        $company = Company::withoutGlobalScope('tenant')
            ->select(['id', 'user_id', 'owner_id'])
            ->find($store->company_id);
        $ownerId = $company?->user_id ?: $company?->owner_id;
        if (! $ownerId) {
            return;
        }

        $owner = User::query()->find($ownerId);
        $owner?->notify(new OnlineStoreOrderPlaced($order, $store->store_name));
    }

    private function branchesForCompany(int $companyId): Collection
    {
        if (! Schema::hasTable('settings')) {
            return collect();
        }

        $raw = DB::table('settings')
            ->where('key', 'branches_json_company_' . $companyId)
            ->value('value');
        $decoded = json_decode((string) $raw, true);

        return collect(is_array($decoded) ? $decoded : [])
            ->filter(fn ($branch) => is_array($branch))
            ->map(fn (array $branch) => [
                'id' => trim((string) ($branch['id'] ?? '')),
                'name' => trim((string) ($branch['name'] ?? '')),
                'is_active' => filter_var($branch['is_active'] ?? true, FILTER_VALIDATE_BOOLEAN),
            ])
            ->filter(fn (array $branch) => $branch['id'] !== '' && $branch['name'] !== '' && $branch['is_active'])
            ->values();
    }

    private function storeStockBranch(StorefrontSetting $store): ?array
    {
        $branches = $this->branchesForCompany((int) $store->company_id);
        $configuredBranchId = trim((string) $store->stock_branch_id);

        if ($configuredBranchId !== '') {
            $branch = $branches->firstWhere('id', $configuredBranchId);
            abort_unless($branch, 503, 'The online store stock branch is unavailable. Please select an active branch in store settings.');

            return $branch;
        }

        return $branches->firstWhere('is_active', true) ?? $branches->first();
    }

    private function attachStoreStock(Collection $products, StorefrontSetting $store): void
    {
        $stockBranch = $this->storeStockBranch($store);
        foreach ($products as $product) {
            $product->setAttribute(
                'storefront_available_stock',
                $this->availableStoreStock($product, $store, $stockBranch)
            );
        }
    }

    private function recordOnlineStockMovement(
        Product $product,
        float $quantity,
        string $type,
        string $reference,
        ?array $branch,
        int $companyId
    ): void {
        if (! Schema::hasTable('inventory_history')) {
            return;
        }

        $row = [
            'user_id' => (int) ($product->user_id ?: DB::table('users')->min('id')),
            'product_id' => $product->id,
            'quantity' => $quantity,
            'type' => $type,
            'reference' => $reference,
            'created_at' => now(),
            'updated_at' => now(),
        ];
        if (Schema::hasColumn('inventory_history', 'company_id')) {
            $row['company_id'] = $companyId;
        }
        if ($branch && Schema::hasColumn('inventory_history', 'branch_id')) {
            $row['branch_id'] = $branch['id'] ?? null;
        }
        if ($branch && Schema::hasColumn('inventory_history', 'branch_name')) {
            $row['branch_name'] = $branch['name'] ?? null;
        }

        DB::table('inventory_history')->insert($row);
    }

    private function availableStoreStock(
        Product $product,
        StorefrontSetting $store,
        ?array $stockBranch = null,
        bool $lockForUpdate = false
    ): float {
        $productStock = max((float) ($product->stock ?? 0), (float) ($product->stock_quantity ?? 0));
        if (! $stockBranch || ! Schema::hasTable('product_branch_stocks')) {
            return max(0, $productStock);
        }

        $branchStockQuery = $this->branchStocksForProduct($product, (int) $store->company_id)
            ->where('branch_id', $stockBranch['id']);
        if ($lockForUpdate) {
            $branchStockQuery->lockForUpdate();
        }
        $branchStock = $branchStockQuery->first();

        if ($branchStock) {
            if (Schema::hasColumn('product_branch_stocks', 'is_available') && ! $branchStock->is_available) {
                return 0;
            }

            return max(0, (float) $branchStock->quantity);
        }

        return $this->branchStocksForProduct($product, (int) $store->company_id)->exists()
            ? 0
            : max(0, $productStock);
    }

    private function branchStockRow(Product $product, array $branch, int $companyId, bool $lockForUpdate = false): ?object
    {
        $query = $this->branchStocksForProduct($product, $companyId)
            ->where('branch_id', $branch['id']);
        if ($lockForUpdate) {
            $query->lockForUpdate();
        }

        return $query->first();
    }

    private function branchStocksForProduct(Product $product, int $companyId): \Illuminate\Database\Query\Builder
    {
        $query = DB::table('product_branch_stocks')->where('product_id', $product->id);
        if (Schema::hasColumn('product_branch_stocks', 'company_id')) {
            $query->where(function ($tenant) use ($companyId) {
                $tenant->where('company_id', $companyId)
                    ->orWhereNull('company_id');
            });
        }

        return $query;
    }

    private function reconcileUnassignedOrders(int $companyId, array $branch): void
    {
        if (! Schema::hasTable('online_store_orders') || ! Schema::hasTable('online_store_order_items')) {
            return;
        }

        $orders = OnlineStoreOrder::withoutGlobalScopes()
            ->where('company_id', $companyId)
            ->where(function ($query) {
                $query->whereNull('branch_id')->orWhere('branch_id', '');
            })
            ->where(function ($query) {
                $query->whereNull('branch_name')->orWhere('branch_name', '');
            })
            ->whereRaw("LOWER(COALESCE(status, '')) <> 'cancelled'")
            ->with('items')
            ->lockForUpdate()
            ->get();

        if ($orders->isEmpty()) {
            return;
        }

        $orderedByProduct = $orders
            ->flatMap(fn (OnlineStoreOrder $order) => $order->items)
            ->groupBy('product_id')
            ->map(fn (Collection $items) => (float) $items->sum('quantity'));
        $products = Product::withoutGlobalScopes()
            ->where('company_id', $companyId)
            ->whereIn('id', $orderedByProduct->keys())
            ->lockForUpdate()
            ->get()
            ->keyBy('id');

        foreach ($orderedByProduct as $productId => $orderedQuantity) {
            $product = $products->get((int) $productId);
            if (! $product) {
                continue;
            }

            if (! Schema::hasTable('product_branch_stocks')) {
                continue;
            }

            $branchStock = $this->branchStockRow($product, $branch, $companyId, true);
            if ($branchStock) {
                if (Schema::hasColumn('product_branch_stocks', 'is_available') && ! $branchStock->is_available) {
                    throw ValidationException::withMessages([
                        'stock_branch_id' => "The selected branch is unavailable for {$product->name}; review its inventory before assigning existing orders.",
                    ]);
                }

                $remainingStock = (float) $branchStock->quantity - $orderedQuantity;
                if ($remainingStock < 0) {
                    throw ValidationException::withMessages([
                        'stock_branch_id' => "The selected branch does not have enough recorded stock to reconcile existing {$product->name} orders.",
                    ]);
                }
            } else {
                if ($this->branchStocksForProduct($product, $companyId)->exists()) {
                    throw ValidationException::withMessages([
                        'stock_branch_id' => "{$product->name} has inventory in other branches but none in the selected branch. Assign its stock before linking the store.",
                    ]);
                }

                $remainingStock = max((float) ($product->stock ?? 0), (float) ($product->stock_quantity ?? 0));
            }

            $this->branchInventory->setBranchStock($product, $remainingStock, $branch, $companyId);
        }

        OnlineStoreOrder::withoutGlobalScopes()
            ->whereIn('id', $orders->pluck('id'))
            ->update([
                'branch_id' => $branch['id'],
                'branch_name' => $branch['name'],
                'updated_at' => now(),
            ]);
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

    private function postOnlineOrderSale(OnlineStoreOrder $order): Sale
    {
        $existingSale = Sale::withoutGlobalScopes()
            ->where('company_id', $order->company_id)
            ->where('source_type', 'online_store_order')
            ->where('source_id', $order->id)
            ->lockForUpdate()
            ->first();

        if ($existingSale) {
            return $existingSale;
        }

        abort_if($order->items->isEmpty(), 422, 'This online order has no items to post as a sale.');

        $amount = round((float) $order->total_amount, 2);
        $paymentMethod = $order->payment_method === 'bank_transfer' ? 'transfer' : 'cash';
        $branchName = trim((string) $order->branch_name) ?: 'Online Store';
        $userId = (int) (Auth::id() ?? Company::withoutGlobalScope('tenant')->whereKey($order->company_id)->value('user_id'));
        abort_if($userId < 1, 422, 'A business user is required to post this online sale.');

        $saleAttributes = [
            'company_id' => $order->company_id,
            'branch_id' => $order->branch_id,
            'branch_name' => $branchName,
            'order_number' => $order->order_number,
            'invoice_no' => $order->order_number,
            'customer_name' => $order->customer_name,
            'user_id' => $userId,
            'terminal_id' => 'WEB',
            'source_type' => 'online_store_order',
            'source_id' => $order->id,
            'source_reference' => $order->order_number,
            'order_date' => now(),
            'subtotal' => $order->subtotal,
            'discount' => 0,
            'tax' => 0,
            'total' => $amount,
            'paid' => $amount,
            'amount_paid' => $amount,
            'balance' => 0,
            'payment_method' => $paymentMethod,
            'payment_status' => 'paid',
            'payment_details' => [
                'source' => 'web',
                'online_store_order_id' => $order->id,
                'online_store_order_number' => $order->order_number,
                'online_payment_method' => $order->payment_method,
                'branch_id' => $order->branch_id,
                'branch_name' => $branchName,
            ],
        ];
        if (Schema::hasColumn('sales', 'order_status')) {
            $saleAttributes['order_status'] = 'completed';
        }
        $sale = Sale::create($saleAttributes);

        foreach ($order->items as $line) {
            $product = Product::withoutGlobalScopes()
                ->where('company_id', $order->company_id)
                ->find($line->product_id);
            abort_if(! $product, 422, "Product {$line->product_name} is no longer available; the order was not fulfilled.");

            $quantity = (float) $line->quantity;
            $lineTotal = round((float) $line->line_total, 2);
            $item = [
                'sale_id' => $sale->id,
                'product_id' => $product->id,
                'unit_price' => $line->unit_price,
                'subtotal' => $lineTotal,
                'total_price' => $lineTotal,
                'created_at' => now(),
                'updated_at' => now(),
            ];
            if (Schema::hasColumn('sale_items', 'quantity')) {
                $item['quantity'] = $quantity;
            }
            if (Schema::hasColumn('sale_items', 'qty')) {
                $item['qty'] = $quantity;
            }
            if (Schema::hasColumn('sale_items', 'product_name')) {
                $item['product_name'] = $line->product_name;
            }
            if (Schema::hasColumn('sale_items', 'unit_type')) {
                $item['unit_type'] = $product->unit_type ?: 'unit';
            }
            if (Schema::hasColumn('sale_items', 'stock_units')) {
                $item['stock_units'] = $quantity;
            }
            if (Schema::hasColumn('sale_items', 'company_id')) {
                $item['company_id'] = $order->company_id;
            }
            if (Schema::hasColumn('sale_items', 'branch_id')) {
                $item['branch_id'] = $order->branch_id;
            }
            if (Schema::hasColumn('sale_items', 'branch_name')) {
                $item['branch_name'] = $branchName;
            }

            DB::table('sale_items')->insert($item);
        }

        if ($amount > 0) {
            $payment = [
                'sale_id' => $sale->id,
                'amount' => $amount,
                'method' => $paymentMethod === 'transfer' ? 'Bank Transfer' : 'Cash',
                'status' => 'Completed',
                'reference' => $order->order_number,
                'note' => 'Online order paid when fulfilled.',
                'created_by' => $userId,
            ];
            foreach ([
                'company_id' => $order->company_id,
                'user_id' => $userId,
                'branch_id' => $order->branch_id,
                'branch_name' => $branchName,
                'source' => 'web',
            ] as $column => $value) {
                if (Schema::hasColumn('payments', $column)) {
                    $payment[$column] = $value;
                }
            }
            Payment::create($payment);
        }

        LedgerService::postSale($sale->fresh());

        return $sale;
    }

    private function clearDashboardMetricsCache(?string $branchId): void
    {
        $companyId = $this->companyId();
        Cache::forget('metrics_co_' . $companyId);
        Cache::forget('metrics_co_' . $companyId . '_global');
        Cache::forget('metrics_co_' . $companyId . '_branch_all');

        $branchId = $branchId ?: session('active_branch_id');
        if (filled($branchId)) {
            Cache::forget('metrics_co_' . $companyId . '_branch_' . $branchId);
        }
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
