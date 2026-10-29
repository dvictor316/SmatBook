<?php

namespace Tests\Feature;

use App\Http\Middleware\BlockExpiredDemoUser;
use App\Http\Middleware\CheckPlanAccess;
use App\Http\Middleware\EnforceDeviceSessionLimit;
use App\Http\Middleware\ForceLogoutExpiredSession;
use App\Http\Middleware\RequireActiveBranch;
use App\Http\Middleware\SubscriptionActive;
use App\Models\Company;
use App\Models\OnlineStoreOrder;
use App\Models\StorefrontSetting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request as ClientRequest;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class OnlineStoreWorkflowTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware([
            SubscriptionActive::class,
            CheckPlanAccess::class,
            RequireActiveBranch::class,
            EnforceDeviceSessionLimit::class,
            ForceLogoutExpiredSession::class,
            BlockExpiredDemoUser::class,
        ]);
    }

    public function test_public_store_lists_only_its_company_products_and_reserves_stock(): void
    {
        [$user, $company] = $this->workspace('Fresh Market');
        [$foreignUser, $foreignCompany] = $this->workspace('Foreign Market');
        $categoryId = $this->category($user->id, $company->id, 'Fruit');
        $productId = $this->product($user->id, $company->id, $categoryId, 'Orange Crate', 'ORG-001', 10, 12500);
        DB::table('products')->where('id', $productId)->update([
            'description' => 'Fresh oranges packed in a sturdy crate.',
        ]);
        $outOfStockId = $this->product($user->id, $company->id, $categoryId, 'Seasonal Mangoes', 'MAN-001', 0, 5000);
        $inactiveProductId = $this->product($user->id, $company->id, $categoryId, 'Archived Pears', 'PEA-001', 5, 4000);
        DB::table('products')->where('id', $inactiveProductId)->update(['status' => 'inactive']);
        $this->product($foreignUser->id, $foreignCompany->id, $categoryId, 'Foreign Apples', 'APL-999', 8, 9000);
        StorefrontSetting::create([
            'company_id' => $company->id,
            'slug' => 'fresh-market',
            'store_name' => 'Fresh Market',
            'is_enabled' => true,
        ]);

        $this->get(route('storefront.catalogue', 'fresh-market'))
            ->assertOk()
            ->assertSee('About Us')
            ->assertSee(asset('css/storefront-theme.css'), false)
            ->assertSee('Orange Crate')
            ->assertSee('Fresh oranges packed in a sturdy crate.')
            ->assertSee('Seasonal Mangoes')
            ->assertSee('Out of stock')
            ->assertDontSee('Archived Pears')
            ->assertDontSee('Foreign Apples');
        $this->get(route('storefront.about', 'fresh-market'))
            ->assertOk()
            ->assertSee(asset('css/storefront-theme.css'), false);
        $this->assertDatabaseHas('products', ['id' => $outOfStockId, 'stock' => 0]);

        $this->post(route('storefront.orders.store', 'fresh-market'), [
            'customer_name' => 'Ada Buyer',
            'customer_phone' => '08000000000',
            'payment_method' => 'pickup',
            'items' => [$productId => 2],
        ])->assertSessionHasNoErrors()->assertSessionHas('store_order_success');

        $this->assertDatabaseHas('online_store_orders', [
            'company_id' => $company->id,
            'customer_name' => 'Ada Buyer',
            'total_amount' => 25000,
            'status' => 'pending_confirmation',
            'whatsapp_notification_status' => 'not_configured',
        ]);
        $this->assertDatabaseHas('products', ['id' => $productId, 'stock' => 8, 'stock_quantity' => 8]);
        $notification = $user->notifications()->firstOrFail();
        $order = OnlineStoreOrder::withoutGlobalScopes()->latest('id')->firstOrFail();
        $this->assertSame('online_store_order', $notification->data['type']);
        $this->assertSame((int) $order->id, (int) $notification->data['order_id']);
        $this->assertSame($order->order_number, $notification->data['target_item']);
        $this->actingAs($user)
            ->withSession(['current_tenant_id' => $company->id, 'active_branch_scope' => 'all'])
            ->get(route('notifications.index'))
            ->assertOk()
            ->assertSee('View Online Orders')
            ->assertSee(route('online-store.settings'), false);
    }

    public function test_custom_domain_resolves_the_enabled_store(): void
    {
        [$user, $company] = $this->workspace('Mapped Store');
        StorefrontSetting::create([
            'company_id' => $company->id,
            'slug' => 'mapped-store',
            'custom_domain' => 'shop.mapped-store.test',
            'store_name' => 'Mapped Store',
            'description' => 'A family business serving its customers.',
            'is_enabled' => true,
        ]);

        $this->get('http://shop.mapped-store.test/store')
            ->assertOk()
            ->assertSee('Mapped Store')
            ->assertSee('About Us');
        $this->get('http://shop.mapped-store.test/store/about')
            ->assertOk()
            ->assertSee('A family business serving its customers.')
            ->assertSee('Product Gallery');
    }

    public function test_store_homepage_features_ten_products_and_gallery_shows_the_full_catalogue(): void
    {
        [$user, $company] = $this->workspace('Gallery Store');
        $categoryId = $this->category($user->id, $company->id, 'Collection');
        $firstProductId = null;
        for ($number = 1; $number <= 11; $number++) {
            $productId = $this->product(
                $user->id,
                $company->id,
                $categoryId,
                sprintf('Gallery Product %02d', $number),
                sprintf('GAL-%03d', $number),
                5,
                1000 + $number
            );
            if ($number === 1) {
                $firstProductId = $productId;
            }
        }
        DB::table('products')->where('id', $firstProductId)->update([
            'brand' => 'Gallery Brand',
            'fashion_size' => 'Medium',
            'fashion_color' => 'Navy',
        ]);
        StorefrontSetting::create([
            'company_id' => $company->id,
            'slug' => 'gallery-store',
            'store_name' => 'Gallery Store',
            'is_enabled' => true,
        ]);

        $this->get(route('storefront.catalogue', 'gallery-store'))
            ->assertOk()
            ->assertSee('Product Gallery')
            ->assertSee(asset('css/storefront-theme.css'), false)
            ->assertSee('Featured in motion')
            ->assertSee('product-motion-stage', false)
            ->assertSee('360-degree product preview')
            ->assertSee('Pause preview')
            ->assertSee('product-motion-face--back', false)
            ->assertSee('product-motion-placeholder', false)
            ->assertSee('Gallery Product 01')
            ->assertSee('Gallery Product 10')
            ->assertDontSee('Gallery Product 11')
            ->assertSee(route('storefront.products', 'gallery-store'), false);

        $this->get(route('storefront.products', 'gallery-store'))
            ->assertOk()
            ->assertSee('Gallery Product 11')
            ->assertSee('Place Online Order')
            ->assertSee('Your Cart')
            ->assertSee('product-media', false)
            ->assertSee('product-meta', false)
            ->assertSee('Gallery Brand')
            ->assertSee('Size: Medium')
            ->assertSee('Colour: Navy')
            ->assertSee('product-purchase', false)
            ->assertSee('add-to-cart', false)
            ->assertSee('Add to cart')
            ->assertSee('smartprobook-cart-gallery-store');
    }

    public function test_business_owner_can_choose_one_of_ten_storefront_color_themes(): void
    {
        [$user, $company] = $this->workspace('Theme Store');
        StorefrontSetting::create([
            'company_id' => $company->id,
            'slug' => 'theme-store',
            'store_name' => 'Theme Store',
            'is_enabled' => true,
        ]);
        $settingsUrl = route('online-store.settings');

        $this->actingAs($user)
            ->withSession(['current_tenant_id' => $company->id, 'active_branch_scope' => 'all']);

        $this->get($settingsUrl)
            ->assertOk()
            ->assertSee('Choose one of 10 background and color themes')
            ->assertSee('value="classic"', false)
            ->assertSee('value="ocean"', false)
            ->assertSee('value="forest"', false)
            ->assertSee('value="sunset"', false)
            ->assertSee('value="royal"', false)
            ->assertSee('value="rose"', false)
            ->assertSee('value="sand"', false)
            ->assertSee('value="midnight"', false)
            ->assertSee('value="teal"', false)
            ->assertSee('value="crimson"', false);

        $this->put(route('online-store.settings.update'), [
                'slug' => 'theme-store',
                'store_name' => 'Theme Store',
                'store_theme' => 'ocean',
                'is_enabled' => 1,
            ])
            ->assertSessionHasNoErrors();

        $this->assertDatabaseHas('storefront_settings', [
            'company_id' => $company->id,
            'store_theme' => 'ocean',
        ]);

        $this->get(route('storefront.catalogue', 'theme-store'))
            ->assertOk()
            ->assertSee('<body data-store-theme="ocean">', false);
        $this->get(route('storefront.products', 'theme-store'))
            ->assertOk()
            ->assertSee('<body data-store-theme="ocean">', false);
        $this->get(route('storefront.about', 'theme-store'))
            ->assertOk()
            ->assertSee('<body data-store-theme="ocean">', false);

        $this->put(route('online-store.settings.update'), [
            'slug' => 'theme-store',
            'store_name' => 'Theme Store',
            'store_theme' => 'unsupported-theme',
            'is_enabled' => 1,
        ])->assertSessionHasErrors('store_theme');

        $this->assertDatabaseHas('storefront_settings', [
            'company_id' => $company->id,
            'store_theme' => 'ocean',
        ]);
    }

    public function test_order_and_status_changes_send_whatsapp_cloud_template_notifications(): void
    {
        [$user, $company] = $this->workspace('WhatsApp Store');
        $categoryId = $this->category($user->id, $company->id, 'Clothing');
        $productId = $this->product($user->id, $company->id, $categoryId, 'Cotton Shirt', 'SHI-001', 8, 12000);
        StorefrontSetting::create([
            'company_id' => $company->id,
            'slug' => 'whatsapp-store',
            'store_name' => 'WhatsApp Store',
            'owner_whatsapp_number' => '+2348012345678',
            'is_enabled' => true,
        ]);
        config()->set('services.whatsapp.access_token', 'test-cloud-token');
        config()->set('services.whatsapp.phone_number_id', '123456789');
        config()->set('services.whatsapp.api_version', 'v21.0');
        config()->set('services.whatsapp.order_template', 'smartprobook_order_notification');
        config()->set('services.whatsapp.template_language', 'en');
        Http::fake(['graph.facebook.com/*' => Http::response(['messages' => [['id' => 'wamid.test']]], 200)]);

        $this->get(route('storefront.products', 'whatsapp-store'))
            ->assertOk()
            ->assertSee('Send purchase request to WhatsApp')
            ->assertSee('does not reserve stock')
            ->assertSee('wa.me/', false);

        $this->post(route('storefront.orders.store', 'whatsapp-store'), [
            'customer_name' => 'Ada Buyer',
            'customer_phone' => '+2348099999999',
            'payment_method' => 'pickup',
            'items' => [$productId => 1],
        ])->assertSessionHasNoErrors()
            ->assertSessionHas('store_order_success')
            ->assertSessionHas('store_whatsapp_link');

        $order = OnlineStoreOrder::withoutGlobalScopes()->firstOrFail();
        $this->assertDatabaseHas('online_store_orders', [
            'id' => $order->id,
            'whatsapp_notification_status' => 'sent',
        ]);
        $this->assertNotNull($order->fresh()->whatsapp_notified_at);

        $this->actingAs($user)
            ->withSession(['current_tenant_id' => $company->id, 'active_branch_scope' => 'all'])
            ->patch(route('online-store.orders.update', $order), ['status' => 'confirmed'])
            ->assertSessionHasNoErrors();

        $this->assertDatabaseHas('online_store_orders', [
            'id' => $order->id,
            'status' => 'confirmed',
            'whatsapp_notification_status' => 'sent',
        ]);
        Http::assertSentCount(2);
        Http::assertSent(fn (ClientRequest $request) =>
            str_contains($request->url(), '/v21.0/123456789/messages')
            && $request['template']['name'] === 'smartprobook_order_notification'
            && count($request['template']['components'][0]['parameters']) === 6
            && $request['to'] === '2348012345678'
        );
    }

    public function test_orders_use_the_business_owners_international_phone_for_whatsapp_when_no_override_is_set(): void
    {
        [$user, $company] = $this->workspace('Owner Contact Store');
        $user->forceFill(['phone' => '+2348012345678'])->save();
        $categoryId = $this->category($user->id, $company->id, 'Accessories');
        $productId = $this->product($user->id, $company->id, $categoryId, 'Travel Bag', 'BAG-001', 5, 8000);
        StorefrontSetting::create([
            'company_id' => $company->id,
            'slug' => 'owner-contact-store',
            'store_name' => 'Owner Contact Store',
            'owner_whatsapp_number' => '+15551234567',
            'is_enabled' => true,
        ]);
        config()->set('services.whatsapp.access_token', 'test-cloud-token');
        config()->set('services.whatsapp.phone_number_id', '123456789');
        config()->set('services.whatsapp.api_version', 'v21.0');
        Http::fake(['graph.facebook.com/*' => Http::response(['messages' => [['id' => 'wamid.owner']]], 200)]);

        $this->post(route('storefront.orders.store', 'owner-contact-store'), [
            'customer_name' => 'Owner Fallback Buyer',
            'customer_phone' => '+2348099999999',
            'payment_method' => 'pickup',
            'items' => [$productId => 1],
        ])->assertSessionHasNoErrors()
            ->assertSessionHas('store_whatsapp_link', fn ($link) => str_contains($link, 'wa.me/2348012345678'));

        Http::assertSent(fn (ClientRequest $request) => $request['to'] === '2348012345678');
        $this->assertDatabaseHas('online_store_orders', [
            'company_id' => $company->id,
            'whatsapp_notification_status' => 'sent',
        ]);
    }

    public function test_local_owner_phone_uses_sms_and_available_store_email_for_order_contact(): void
    {
        [$user, $company] = $this->workspace('Local Contact Store');
        $user->forceFill(['phone' => '08012345678'])->save();
        DB::table('companies')->where('id', $company->id)->update(['email' => 'orders@example.test']);
        $categoryId = $this->category($user->id, $company->id, 'Household');
        $productId = $this->product($user->id, $company->id, $categoryId, 'Storage Jar', 'JAR-001', 4, 3500);
        StorefrontSetting::create([
            'company_id' => $company->id,
            'slug' => 'local-contact-store',
            'store_name' => 'Local Contact Store',
            'owner_whatsapp_number' => '+15551234567',
            'is_enabled' => true,
        ]);

        $this->get(route('storefront.products', 'local-contact-store'))
            ->assertOk()
            ->assertSee('Send purchase request by SMS')
            ->assertSee('Email purchase request')
            ->assertDontSee('Send purchase request to WhatsApp');

        $this->post(route('storefront.orders.store', 'local-contact-store'), [
            'customer_name' => 'Local Fallback Buyer',
            'customer_phone' => '08000000000',
            'payment_method' => 'pickup',
            'items' => [$productId => 1],
        ])->assertSessionHasNoErrors()
            ->assertSessionMissing('store_whatsapp_link')
            ->assertSessionHas('store_sms_link', fn ($link) => str_starts_with($link, 'sms:08012345678?body='))
            ->assertSessionHas('store_email_link', fn ($link) => str_starts_with($link, 'mailto:' . $user->email . '?subject='));
    }

    public function test_whatsapp_api_failure_does_not_discard_customer_order(): void
    {
        [$user, $company] = $this->workspace('WhatsApp Failure Store');
        $categoryId = $this->category($user->id, $company->id, 'Household');
        $productId = $this->product($user->id, $company->id, $categoryId, 'Storage Basket', 'BAS-001', 5, 2500);
        StorefrontSetting::create([
            'company_id' => $company->id,
            'slug' => 'whatsapp-failure-store',
            'store_name' => 'WhatsApp Failure Store',
            'owner_whatsapp_number' => '+2348012345678',
            'is_enabled' => true,
        ]);
        config()->set('services.whatsapp.access_token', 'test-cloud-token');
        config()->set('services.whatsapp.phone_number_id', '123456789');
        config()->set('services.whatsapp.api_version', 'v21.0');
        Http::fake(['graph.facebook.com/*' => Http::response(['error' => ['message' => 'Template unavailable']], 400)]);

        $this->post(route('storefront.orders.store', 'whatsapp-failure-store'), [
            'customer_name' => 'Grace Customer',
            'customer_phone' => '+2348099999999',
            'payment_method' => 'pickup',
            'items' => [$productId => 1],
        ])->assertSessionHasNoErrors()->assertSessionHas('store_order_success');

        $this->assertDatabaseHas('online_store_orders', [
            'company_id' => $company->id,
            'customer_name' => 'Grace Customer',
            'status' => 'pending_confirmation',
            'whatsapp_notification_status' => 'failed',
        ]);
    }

    public function test_business_owner_has_a_direct_online_store_sidebar_link(): void
    {
        [$user, $company] = $this->workspace('Sidebar Store');

        $this->actingAs($user)
            ->withSession(['current_tenant_id' => $company->id, 'active_branch_scope' => 'all'])
            ->get(route('online-store.settings'))
            ->assertOk()
            ->assertSee('href="' . route('online-store.settings') . '"', false);
    }

    public function test_cancelling_an_online_order_restores_reserved_stock(): void
    {
        [$user, $company] = $this->workspace('Cancellation Store');
        $categoryId = $this->category($user->id, $company->id, 'Supplies');
        $productId = $this->product($user->id, $company->id, $categoryId, 'Returnable Box', 'BOX-001', 10, 2500);
        StorefrontSetting::create([
            'company_id' => $company->id,
            'slug' => 'cancellation-store',
            'store_name' => 'Cancellation Store',
            'is_enabled' => true,
        ]);

        $this->post(route('storefront.orders.store', 'cancellation-store'), [
            'customer_name' => 'Buyer One',
            'customer_phone' => '08000000000',
            'payment_method' => 'pickup',
            'items' => [$productId => 3],
        ])->assertSessionHasNoErrors();

        $order = OnlineStoreOrder::withoutGlobalScopes()->firstOrFail();
        $this->actingAs($user)
            ->withSession(['current_tenant_id' => $company->id, 'active_branch_scope' => 'all'])
            ->patch(route('online-store.orders.update', $order), ['status' => 'cancelled'])
            ->assertSessionHasNoErrors();

        $this->assertDatabaseHas('online_store_orders', ['id' => $order->id, 'status' => 'cancelled']);
        $this->assertDatabaseHas('products', ['id' => $productId, 'stock' => 10, 'stock_quantity' => 10]);
    }

    public function test_foreign_workspace_cannot_update_an_online_order(): void
    {
        [$owner, $ownerCompany] = $this->workspace('Order Owner');
        [$other, $otherCompany] = $this->workspace('Other Workspace');
        $order = OnlineStoreOrder::withoutGlobalScopes()->create([
            'company_id' => $ownerCompany->id,
            'order_number' => 'WEB-FOREIGN-001',
            'customer_name' => 'Customer',
            'customer_phone' => '08000000000',
            'payment_method' => 'pickup',
            'status' => 'pending_confirmation',
            'subtotal' => 1000,
            'total_amount' => 1000,
        ]);

        $this->actingAs($other)
            ->withSession(['current_tenant_id' => $otherCompany->id, 'active_branch_scope' => 'all'])
            ->patch(route('online-store.orders.update', $order), ['status' => 'fulfilled'])
            ->assertNotFound();

        $this->assertSame('pending_confirmation', $order->fresh()->status);
    }

    private function workspace(string $name): array
    {
        $user = User::factory()->create(['role' => 'admin']);
        $company = Company::withoutGlobalScope('tenant')->create([
            'user_id' => $user->id,
            'owner_id' => $user->id,
            'name' => $name,
            'status' => 'active',
            'plan' => 'Enterprise Yearly',
            'currency_code' => 'NGN',
        ]);
        $user->forceFill(['company_id' => $company->id])->save();
        return [$user, $company];
    }

    private function category(int $userId, int $companyId, string $name): int
    {
        return DB::table('categories')->insertGetId([
            'company_id' => $companyId,
            'user_id' => $userId,
            'name' => $name,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function product(int $userId, int $companyId, int $categoryId, string $name, string $sku, int $stock, float $price): int
    {
        return DB::table('products')->insertGetId([
            'user_id' => $userId,
            'company_id' => $companyId,
            'category_id' => $categoryId,
            'name' => $name,
            'sku' => $sku,
            'price' => $price,
            'purchase_price' => 5000,
            'stock' => $stock,
            'stock_quantity' => $stock,
            'status' => 'active',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}
