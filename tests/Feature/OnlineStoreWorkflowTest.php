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
use App\Models\Sale;
use App\Models\StorefrontSetting;
use App\Models\Transaction;
use App\Models\User;
use App\Notifications\OnlineStoreOrderPlaced;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request as ClientRequest;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Schema;
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
            'order_token' => '00000000-0000-4000-8000-000000000001',
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
        $notificationPage = $this->actingAs($user)
            ->withSession(['current_tenant_id' => $company->id, 'active_branch_scope' => 'all'])
            ->get(route('notifications.index'))
            ->assertOk()
            ->assertSee('View Online Orders')
            ->assertSee(route('online-store.settings'), false);
        $notificationPage->assertSee('id="headerNotificationBadge"', false)
            ->assertSee('data-notification-id="'.$notification->id.'"', false)
            ->assertSee($notification->data['message']);
        $this->assertMatchesRegularExpression(
            '/<span[^>]*id="headerNotificationBadge"[^>]*>\s*1\s*<\/span>/',
            $notificationPage->getContent()
        );
        $this->getJson(route('notifications.summary'))
            ->assertOk()
            ->assertJsonPath('notification_count', 1);
        $this->postJson(route('notifications.mark-read', $notification->id))
            ->assertOk()
            ->assertJsonPath('ok', true);
        $this->assertNotNull($notification->fresh()->read_at);
        $this->getJson(route('notifications.summary'))
            ->assertOk()
            ->assertJsonPath('notification_count', 0);

        $user->notify(new OnlineStoreOrderPlaced($order, 'Fresh Market'));
        $this->postJson(route('notifications.mark-all-read'))
            ->assertOk()
            ->assertJsonPath('ok', true);
        $this->getJson(route('notifications.summary'))
            ->assertOk()
            ->assertJsonPath('notification_count', 0);
    }

    public function test_storefront_categories_link_to_products_in_that_category(): void
    {
        [$user, $company] = $this->workspace('Shoe Store');
        $sneakersCategoryId = $this->category($user->id, $company->id, 'Sneakers');
        $duplicateSneakersCategoryId = $this->category($user->id, $company->id, 'sneakers');
        $sandalsCategoryId = $this->category($user->id, $company->id, 'Sandals');
        $olderSneakerId = $this->product($user->id, $company->id, $sneakersCategoryId, 'Older Sneaker', 'SHOE-001', 8, 25000);
        $newestSneakerId = $this->product($user->id, $company->id, $duplicateSneakersCategoryId, 'Newest Sneaker', 'SHOE-003', 6, 30000);
        $this->product($user->id, $company->id, $sandalsCategoryId, 'Leather Sandal', 'SHOE-002', 5, 18000);
        DB::table('products')->where('id', $olderSneakerId)->update(['created_at' => now()->subDay()]);
        DB::table('products')->where('id', $newestSneakerId)->update(['created_at' => now()]);
        StorefrontSetting::create([
            'company_id' => $company->id,
            'slug' => 'shoe-store',
            'store_name' => 'Shoe Store',
            'is_enabled' => true,
        ]);

        $this->get(route('storefront.catalogue', 'shoe-store'))
            ->assertOk()
            ->assertSee('Shop by category')
            ->assertSee('Sneakers')
            ->assertSee('Sandals')
            ->assertSee('store-category-dropdown-options', false)
            ->assertSee('category_id=' . $sneakersCategoryId, false)
            ->assertViewHas('categories', fn ($categories) => $categories->count() === 2);

        $this->get(route('storefront.products', 'shoe-store') . '?category_id=' . $sneakersCategoryId)
            ->assertOk()
            ->assertSee('Newest Sneaker')
            ->assertSee('Older Sneaker')
            ->assertDontSee('Leather Sandal')
            ->assertSee('All categories')
            ->assertViewHas('products', function ($products) use ($newestSneakerId) {
                return (int) $products->first()->id === $newestSneakerId;
            });
    }

    public function test_repeated_store_order_submission_creates_one_order_and_reserves_stock_once(): void
    {
        [$user, $company] = $this->workspace('Idempotent Store');
        $categoryId = $this->category($user->id, $company->id, 'Supplies');
        $productId = $this->product($user->id, $company->id, $categoryId, 'Reusable Bag', 'BAG-002', 10, 1000);
        StorefrontSetting::create([
            'company_id' => $company->id,
            'slug' => 'idempotent-store',
            'store_name' => 'Idempotent Store',
            'is_enabled' => true,
        ]);
        $orderData = [
            'order_token' => '00000000-0000-4000-8000-000000000008',
            'customer_name' => 'Repeat Buyer',
            'customer_phone' => '08000000000',
            'payment_method' => 'pickup',
            'items' => [$productId => 2],
        ];

        $this->get(route('storefront.products', 'idempotent-store'))
            ->assertOk()
            ->assertSee('name="order_token"', false);

        $this->post(route('storefront.orders.store', 'idempotent-store'), $orderData)
            ->assertSessionHasNoErrors()
            ->assertSessionHas('store_order_success');
        $this->post(route('storefront.orders.store', 'idempotent-store'), $orderData)
            ->assertSessionHasNoErrors()
            ->assertSessionHas('store_order_success')
            ->assertSessionHas('store_order_duplicate', true);

        $this->assertDatabaseCount('online_store_orders', 1);
        $this->assertDatabaseCount('online_store_order_items', 1);
        $this->assertDatabaseHas('products', ['id' => $productId, 'stock' => 8, 'stock_quantity' => 8]);
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

    public function test_store_homepage_has_four_product_slides_and_two_product_showcase(): void
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

        $homepage = $this->get(route('storefront.catalogue', 'gallery-store'))
            ->assertOk()
            ->assertSee('Product Gallery')
            ->assertSee(asset('css/storefront-theme.css'), false)
            ->assertSee('Featured in motion')
            ->assertSee('product-motion-stage', false)
            ->assertSee('360-degree product preview')
            ->assertSee('Pause preview')
            ->assertSee('product-motion-face--back', false)
            ->assertSee('product-motion-placeholder', false)
            ->assertSee('Shop the collection')
            ->assertSee('data-page-count="3"', false)
            ->assertSee('data-products-per-page="4"', false)
            ->assertSee('data-carousel-next', false)
            ->assertSee('Two stand-out favourites')
            ->assertSee('animated-products-grid', false)
            ->assertSee('Gallery Product 01')
            ->assertSee('Gallery Product 10')
            ->assertSee('Gallery Product 11')
            ->assertSee(route('storefront.products', 'gallery-store'), false);
        $this->assertSame(3, substr_count($homepage->getContent(), 'data-carousel-slide role='));
        $this->assertSame(2, substr_count($homepage->getContent(), 'class="animated-product-card"'));

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
        $this->assertCount(10, StorefrontSetting::themes());
        $themeCss = file_get_contents(public_path('css/storefront-theme.css'));
        $this->assertIsString($themeCss);
        foreach (array_keys(StorefrontSetting::themes()) as $themeKey) {
            $this->assertStringContainsString('body[data-store-theme="' . $themeKey . '"]', $themeCss);
        }
        $this->assertStringContainsString('--theme-accent: #3b82f6;', $themeCss);
        $this->assertStringContainsString('--theme-accent: #ef4444;', $themeCss);
        $this->assertStringContainsString('--theme-accent: #f4b400;', $themeCss);

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
            ->assertSee('id="storeTheme"', false)
            ->assertSee('Golden')
            ->assertSee('Blue')
            ->assertSee('Red')
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

        $successPage = $this->get($settingsUrl)
            ->assertOk()
            ->assertSee('Online store settings updated.');
        $successHtml = $successPage->getContent();
        $this->assertSame(1, preg_match_all('/<div class="alert alert-success alert-dismissible fade show"[^>]*data-flash-message/', $successHtml));
        $this->assertStringNotContainsString('spb-flash-popup-stack', $successHtml);

        $singleFlashPage = $this->withSession([
            'success' => 'Success alert marker',
            'error' => 'Error alert marker',
            'warning' => 'Warning alert marker',
            'info' => 'Info alert marker',
        ])->get($settingsUrl)
            ->assertOk()
            ->assertSee('Error alert marker');
        $singleFlashHtml = $singleFlashPage->getContent();
        $this->assertSame(1, preg_match_all('/<div class="alert alert-danger alert-dismissible fade show"[^>]*data-flash-message/', $singleFlashHtml));
        $this->assertStringNotContainsString('class="alert alert-success alert-dismissible fade show"', $singleFlashHtml);
        $this->assertStringNotContainsString('class="alert alert-warning alert-dismissible fade show"', $singleFlashHtml);
        $this->assertStringNotContainsString('class="alert alert-info alert-dismissible fade show"', $singleFlashHtml);

        $this->assertDatabaseHas('storefront_settings', [
            'company_id' => $company->id,
            'store_theme' => 'ocean',
        ]);

        $this->get(route('storefront.catalogue', 'theme-store'))
            ->assertOk()
            ->assertSee(asset('css/storefront-theme.css'), false)
            ->assertSee('<body data-store-theme="ocean">', false);
        $this->get(route('storefront.products', 'theme-store'))
            ->assertOk()
            ->assertSee(asset('css/storefront-theme.css'), false)
            ->assertSee('<body data-store-theme="ocean">', false);
        $this->get(route('storefront.about', 'theme-store'))
            ->assertOk()
            ->assertSee(asset('css/storefront-theme.css'), false)
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
            'order_token' => '00000000-0000-4000-8000-000000000002',
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
            'order_token' => '00000000-0000-4000-8000-000000000003',
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
            'order_token' => '00000000-0000-4000-8000-000000000004',
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
            'order_token' => '00000000-0000-4000-8000-000000000005',
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

    public function test_store_branch_stock_stays_in_sync_for_existing_and_new_orders(): void
    {
        [$user, $company] = $this->workspace('Dior Store');
        $branch = ['id' => 'branch-a', 'name' => 'Main Branch', 'is_active' => true];
        DB::table('settings')->insert([
            'key' => 'branches_json_company_' . $company->id,
            'value' => json_encode([$branch]),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $categoryId = $this->category($user->id, $company->id, 'Fashion');
        $productId = $this->product($user->id, $company->id, $categoryId, 'Dior', 'DIOR-001', 27, 15000);
        DB::table('product_branch_stocks')->insert([
            'product_id' => $productId,
            'company_id' => $company->id,
            'branch_id' => $branch['id'],
            'branch_name' => $branch['name'],
            'quantity' => 30,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $store = StorefrontSetting::create([
            'company_id' => $company->id,
            'slug' => 'dior-online',
            'store_name' => 'Dior Store',
            'is_enabled' => true,
        ]);
        $legacyOrder = OnlineStoreOrder::withoutGlobalScopes()->create([
            'company_id' => $company->id,
            'order_number' => 'WEB-OLD-DIOR-001',
            'customer_name' => 'Previous Customer',
            'customer_phone' => '08000000000',
            'payment_method' => 'pickup',
            'status' => 'pending_confirmation',
            'subtotal' => 45000,
            'total_amount' => 45000,
        ]);
        DB::table('online_store_order_items')->insert([
            'online_store_order_id' => $legacyOrder->id,
            'product_id' => $productId,
            'product_name' => 'Dior',
            'quantity' => 3,
            'unit_price' => 15000,
            'line_total' => 45000,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->actingAs($user)
            ->withSession(['current_tenant_id' => $company->id, 'active_branch_scope' => 'all'])
            ->get(route('online-store.settings'))
            ->assertOk()
            ->assertSee('Main Branch');

        $this->actingAs($user)
            ->withSession(['current_tenant_id' => $company->id, 'active_branch_scope' => 'all'])
            ->put(route('online-store.settings.update'), [
                'slug' => $store->slug,
                'store_name' => $store->store_name,
                'store_theme' => 'classic',
                'stock_branch_id' => $branch['id'],
                'is_enabled' => 1,
            ])
            ->assertSessionHasNoErrors();

        $this->assertDatabaseHas('storefront_settings', [
            'id' => $store->id,
            'stock_branch_id' => $branch['id'],
            'stock_branch_name' => $branch['name'],
        ]);
        $this->assertDatabaseHas('online_store_orders', [
            'id' => $legacyOrder->id,
            'branch_id' => $branch['id'],
            'branch_name' => $branch['name'],
        ]);
        $this->assertDatabaseHas('product_branch_stocks', [
            'product_id' => $productId,
            'branch_id' => $branch['id'],
            'quantity' => 27,
        ]);
        $this->get(route('storefront.catalogue', $store->slug))
            ->assertOk()
            ->assertSee('Dior')
            ->assertSee('27');

        $this->post(route('storefront.orders.store', $store->slug), [
            'order_token' => '00000000-0000-4000-8000-000000000006',
            'customer_name' => 'New Customer',
            'customer_phone' => '08000000001',
            'payment_method' => 'pickup',
            'items' => [$productId => 2],
        ])->assertSessionHasNoErrors();

        $newOrder = OnlineStoreOrder::withoutGlobalScopes()
            ->where('customer_name', 'New Customer')
            ->firstOrFail();
        $this->assertSame($branch['id'], $newOrder->branch_id);
        $this->assertDatabaseHas('product_branch_stocks', [
            'product_id' => $productId,
            'branch_id' => $branch['id'],
            'quantity' => 25,
        ]);
        $this->assertDatabaseHas('products', ['id' => $productId, 'stock' => 25, 'stock_quantity' => 25]);
        $this->assertDatabaseHas('inventory_history', [
            'product_id' => $productId,
            'branch_id' => $branch['id'],
            'type' => 'out',
            'quantity' => 2,
            'reference' => 'Online Store Order: ' . $newOrder->order_number,
        ]);

        $this->patch(route('online-store.orders.update', $newOrder), ['status' => 'cancelled'])
            ->assertSessionHasNoErrors();
        $this->assertDatabaseHas('product_branch_stocks', [
            'product_id' => $productId,
            'branch_id' => $branch['id'],
            'quantity' => 27,
        ]);
        $this->assertDatabaseHas('products', ['id' => $productId, 'stock' => 27, 'stock_quantity' => 27]);
        $this->assertDatabaseHas('inventory_history', [
            'product_id' => $productId,
            'branch_id' => $branch['id'],
            'type' => 'in',
            'quantity' => 2,
            'reference' => 'Online Store Order Cancelled: ' . $newOrder->order_number,
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

    public function test_company_wide_online_orders_remain_visible_when_a_branch_is_selected(): void
    {
        [$user, $company] = $this->workspace('Branch Scoped Store');
        $categoryId = $this->category($user->id, $company->id, 'Supplies');
        $productId = $this->product($user->id, $company->id, $categoryId, 'Branch Scope Item', 'BSI-001', 5, 500);
        $order = OnlineStoreOrder::withoutGlobalScopes()->create([
            'company_id' => $company->id,
            'order_number' => 'WEB-BRANCH-SCOPE-001',
            'customer_name' => 'Branch Scope Customer',
            'customer_phone' => '08000000000',
            'payment_method' => 'pickup',
            'status' => 'pending_confirmation',
            'subtotal' => 1000,
            'total_amount' => 1000,
        ]);
        $order->items()->create([
            'product_id' => $productId,
            'product_name' => 'Branch Scope Item',
            'sku' => 'BSI-001',
            'quantity' => 2,
            'unit_price' => 500,
            'line_total' => 1000,
        ]);
        config(['tenancy.enforce_scopes_in_console' => true]);

        $this->actingAs($user)
            ->withSession([
                'current_tenant_id' => $company->id,
                'active_branch_id' => 'branch-a',
                'active_branch_name' => 'Branch A',
                'active_branch_scope' => 'branch',
            ])
            ->get(route('online-store.settings'))
            ->assertOk()
            ->assertSee($order->order_number)
            ->assertSee($order->customer_name)
            ->assertSee('View ordered items')
            ->assertSee('Branch Scope Item')
            ->assertSee('Quantity: 2')
            ->assertSee('Line total');
    }

    public function test_cancelling_an_online_order_restores_reserved_stock(): void
    {
        [$user, $company] = $this->workspace('Cancellation Store');
        $categoryId = $this->category($user->id, $company->id, 'Supplies');
        $productId = $this->product($user->id, $company->id, $categoryId, 'Returnable Box', 'BOX-001', 10, 2500);
        DB::table('inventory_history')->insert([
            'user_id' => $user->id,
            'company_id' => $company->id,
            'product_id' => $productId,
            'quantity' => 10,
            'type' => 'in',
            'reference' => 'Initial Stock',
            'created_at' => now()->subMinutes(2),
            'updated_at' => now()->subMinutes(2),
        ]);
        StorefrontSetting::create([
            'company_id' => $company->id,
            'slug' => 'cancellation-store',
            'store_name' => 'Cancellation Store',
            'is_enabled' => true,
        ]);

        $this->post(route('storefront.orders.store', 'cancellation-store'), [
            'order_token' => '00000000-0000-4000-8000-000000000007',
            'customer_name' => 'Buyer One',
            'customer_phone' => '08000000000',
            'payment_method' => 'pickup',
            'items' => [$productId => 3],
        ])->assertSessionHasNoErrors();

        $order = OnlineStoreOrder::withoutGlobalScopes()->firstOrFail();
        DB::table('online_store_orders')->where('id', $order->id)->update([
            'created_at' => now()->subMinute(),
        ]);
        DB::table('products')->where('id', $productId)->update([
            'stock' => 10,
            'stock_quantity' => 7,
        ]);
        $this->actingAs($user)
            ->withSession(['current_tenant_id' => $company->id, 'active_branch_scope' => 'all'])
            ->patch(route('online-store.orders.update', $order), ['status' => 'cancelled'])
            ->assertSessionHasNoErrors();

        $this->assertDatabaseHas('online_store_orders', ['id' => $order->id, 'status' => 'cancelled']);
        $this->assertDatabaseHas('products', ['id' => $productId, 'stock' => 10, 'stock_quantity' => 10]);
        DB::table('inventory_history')
            ->where('product_id', $productId)
            ->where('reference', 'Online Store Order: ' . $order->order_number)
            ->delete();

        $this->get(route('inventory.history', $productId))
            ->assertOk()
            ->assertSee('Online Store Order: ' . $order->order_number)
            ->assertSee('Online Store Order Cancelled: ' . $order->order_number)
            ->assertViewHas('inventoryHistories', function ($histories) use ($order) {
                $movements = $histories->keyBy('reference');
                $outReference = 'Online Store Order: ' . $order->order_number;
                $inReference = 'Online Store Order Cancelled: ' . $order->order_number;

                return isset($movements[$outReference], $movements[$inReference])
                    && $movements[$outReference]->type === 'out'
                    && $movements[$inReference]->type === 'in'
                    && (float) $movements[$inReference]->running_balance === 10.0;
            });
    }

    public function test_fulfilling_an_online_order_posts_one_paid_web_sale_without_deducting_stock_again(): void
    {
        [$user, $company] = $this->workspace('Web Sales Store');
        $categoryId = $this->category($user->id, $company->id, 'Supplies');
        $productId = $this->product($user->id, $company->id, $categoryId, 'Web Order Item', 'WEB-001', 10, 2500);
        StorefrontSetting::create([
            'company_id' => $company->id,
            'slug' => 'web-sales-store',
            'store_name' => 'Web Sales Store',
            'is_enabled' => true,
        ]);

        $this->post(route('storefront.orders.store', 'web-sales-store'), [
            'order_token' => '00000000-0000-4000-8000-000000000009',
            'customer_name' => 'Web Buyer',
            'customer_phone' => '08000000000',
            'payment_method' => 'bank_transfer',
            'items' => [$productId => 3],
        ])->assertSessionHasNoErrors();

        $order = OnlineStoreOrder::withoutGlobalScopes()->firstOrFail();
        $this->actingAs($user)
            ->withSession(['current_tenant_id' => $company->id, 'active_branch_scope' => 'all'])
            ->patch(route('online-store.orders.update', $order), ['status' => 'fulfilled'])
            ->assertRedirect()
            ->assertSessionHas('success', 'Online order status updated.')
            ->assertSessionHasNoErrors();
        $this->assertDatabaseHas('online_store_orders', ['id' => $order->id, 'status' => 'fulfilled']);
        $this->patch(route('online-store.orders.update', $order), ['status' => 'fulfilled'])
            ->assertSessionHasNoErrors();

        $sale = Sale::withoutGlobalScopes()
            ->where('source_type', 'online_store_order')
            ->where('source_id', $order->id)
            ->firstOrFail();
        $this->assertSame('paid', $sale->payment_status);
        $this->assertSame('transfer', $sale->payment_method);
        $this->assertSame('web', $sale->payment_details['source']);
        $this->assertSame(7500.0, (float) $sale->total);
        $this->assertDatabaseCount('sales', 1);
        $this->assertDatabaseCount('payments', 1);
        $this->assertDatabaseHas('payments', [
            'sale_id' => $sale->id,
            'amount' => 7500,
            'method' => 'Bank Transfer',
            'status' => 'Completed',
        ]);
        $receiptEntries = DB::table('transactions')
            ->where('related_id', $sale->id)
            ->where('related_type', Sale::class)
            ->where('transaction_type', Transaction::TYPE_RECEIPT)
            ->get();
        $this->assertCount(2, $receiptEntries);
        $this->assertSame(7500.0, (float) $receiptEntries->sum('debit'));
        $this->assertSame(7500.0, (float) $receiptEntries->sum('credit'));
        $this->assertDatabaseHas('sale_items', [
            'sale_id' => $sale->id,
            'product_id' => $productId,
            'qty' => 3,
            'stock_units' => 3,
            'total_price' => 7500,
        ]);
        $this->assertDatabaseHas('products', ['id' => $productId, 'stock' => 7, 'stock_quantity' => 7]);
        $this->assertDatabaseHas('inventory_history', [
            'product_id' => $productId,
            'type' => 'status',
            'quantity' => 0,
            'reference' => 'Online Store Order Fulfilled: ' . $order->order_number,
        ]);
        $this->withSession(['current_tenant_id' => $company->id, 'active_branch_scope' => 'all'])
            ->get(route('inventory.history', $productId))
            ->assertOk()
            ->assertViewHas('inventoryHistories', function ($histories) use ($order) {
                $fulfilled = $histories->firstWhere('reference', 'Online Store Order Fulfilled: ' . $order->order_number);

                return $fulfilled
                    && $fulfilled->type === 'status'
                    && (float) $fulfilled->quantity === 0.0
                    && (float) $fulfilled->running_balance === 7.0;
            });

        $posSale = Sale::create([
            'company_id' => $company->id,
            'order_number' => 'POS-CHANNEL-TEST-001',
            'invoice_no' => 'POS-CHANNEL-TEST-001',
            'customer_name' => 'POS Buyer',
            'user_id' => $user->id,
            'terminal_id' => 'POS1',
            'subtotal' => 1000,
            'total' => 1000,
            'paid' => 1000,
            'amount_paid' => 1000,
            'balance' => 0,
            'payment_method' => 'cash',
            'payment_status' => 'paid',
            'payment_details' => ['source' => 'pos'],
        ]);
        $posItem = [
            'sale_id' => $posSale->id,
            'product_id' => $productId,
            'quantity' => 1,
            'qty' => 1,
            'unit_price' => 1000,
            'subtotal' => 1000,
            'total_price' => 1000,
            'stock_units' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ];
        if (Schema::hasColumn('sale_items', 'company_id')) {
            $posItem['company_id'] = $company->id;
        }
        DB::table('sale_items')->insert($posItem);

        $this->withSession(['current_tenant_id' => $company->id, 'active_branch_scope' => 'all'])
            ->get(route('inventory.history', $productId))
            ->assertOk()
            ->assertViewHas('inventoryHistories', function ($histories) {
                $references = $histories->pluck('reference');

                return $references->contains('POS-CHANNEL-TEST-001')
                    && $references->filter(fn ($reference) => str_starts_with((string) $reference, 'Online Store Order:'))->count() === 1;
            });

        $webReport = $this->get(route('reports.sales', ['source' => 'web', 'payment_method' => 'transfer']))
            ->assertOk()
            ->assertViewHas('reportSummary', fn ($summary) => (float) $summary->quantity === 3.0 && (float) $summary->revenue === 7500.0);
        $webReport->assertSee('Channel: Online / Web');
        $this->get(route('reports.sales', ['payment_method' => 'web']))
            ->assertOk()
            ->assertViewHas('reportSummary', fn ($summary) => (float) $summary->quantity === 3.0 && (float) $summary->revenue === 7500.0);
        $this->get(route('reports.sales', ['source' => 'pos']))
            ->assertOk()
            ->assertViewHas('reportSummary', fn ($summary) => (float) $summary->quantity === 1.0 && (float) $summary->revenue === 1000.0);
        $this->get(route('reports.sales', ['source' => 'other']))
            ->assertOk()
            ->assertViewHas('reportSummary', fn ($summary) => (float) $summary->quantity === 0.0 && (float) $summary->revenue === 0.0);

        $this->get(route('user.dashboard'))
            ->assertOk()
            ->assertViewHas('periodSales', fn ($periodSales) => (float) $periodSales['today']['revenue'] === 8500.0);
        $this->get(route('online-store.settings'))
            ->assertOk()
            ->assertSee('Paid · Sale ' . $sale->invoice_no);
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
