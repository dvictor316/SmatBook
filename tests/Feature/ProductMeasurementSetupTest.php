<?php

namespace Tests\Feature;

use App\Http\Middleware\{CheckPlanAccess, EnforceDeviceSessionLimit, ForceLogoutExpiredSession, RequireActiveBranch, SubscriptionActive, VerifyTenantSession};
use App\Models\{Company, Product, User};
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class ProductMeasurementSetupTest extends TestCase
{
    use RefreshDatabase;

    private function workspace(): array
    {
        $company = Company::create(['name' => 'Explicit Units Business']);
        $user = User::factory()->create(['company_id' => $company->id, 'role' => 'admin']);
        $this->actingAs($user)->withoutMiddleware([
            SubscriptionActive::class, RequireActiveBranch::class, CheckPlanAccess::class,
            ForceLogoutExpiredSession::class, EnforceDeviceSessionLimit::class, VerifyTenantSession::class,
        ])->withSession(['current_tenant_id' => $company->id, 'active_branch_id' => 'hq', 'active_branch_name' => 'Headquarters']);

        $ids = [];
        foreach (['pcs' => 'Piece', 'kg' => 'Kilogram'] as $symbol => $name) {
            $ids[$symbol] = DB::table('units')->insertGetId([
                'name' => $name, 'symbol' => $symbol, 'company_id' => $company->id,
                'created_at' => now(), 'updated_at' => now(),
            ]);
        }
        return $ids;
    }

    private function payload(array $units, string $mode): array
    {
        // Simulate switching away from weight mode with stale hidden values.
        return [
            'name' => 'Rice 50kg', 'price' => 100, 'purchase_price' => 80,
            'measurement_mode' => $mode, 'base_unit_name' => 'kg',
            'unit_id' => $units['kg'], 'base_unit_id' => $units['kg'],
            'unit_type' => 'carton', 'kg_per_carton' => 25,
            'units_per_carton' => 12, 'units_per_roll' => 5,
            'stock_cartons' => 2, 'stock_rolls' => 4, 'stock_units' => 3,
            'stock' => 999,
        ];
    }

    public function test_add_form_offers_separate_counted_and_weighted_carton_choices(): void
    {
        $this->workspace();
        $this->get(route('add-products'))->assertOk()
            ->assertSee('Pieces only (pcs)')
            ->assertSee('Cartons containing pieces (pcs)')
            ->assertSee('Cartons measured in kilograms (kg)')
            ->assertSee('Selling Price Per Piece (pcs)')
            ->assertSee('Opening Full Cartons')
            ->assertSee('Opening Full Rolls')
            ->assertSee('value="pieces_carton"', false);
    }

    public function test_piece_rice_stays_in_pieces_even_with_a_weight_in_its_name_and_stale_kg_fields(): void
    {
        $units = $this->workspace();
        $response = $this->post(route('inventory.Products.store'), $this->payload($units, 'pieces'));
        $response->assertSessionHasNoErrors()->assertSessionHas('success');
        $product = Product::sole();
        $this->assertSame('pcs', $product->base_unit_name);
        $this->assertSame($units['pcs'], (int) $product->base_unit_id);
        $this->assertSame($units['pcs'], (int) $product->unit_id);
        $this->assertSame('unit', $product->unit_type);
        $this->assertSame(3.0, (float) $product->stock);
    }

    public function test_piece_cartons_convert_to_pieces_and_keep_the_explicit_carton_price(): void
    {
        $units = $this->workspace();
        $payload = $this->payload($units, 'pieces_carton') + ['carton_selling_price' => 1100];
        $this->post(route('inventory.Products.store'), $payload)->assertSessionHasNoErrors()->assertSessionHas('success');
        $product = Product::sole();
        $this->assertSame('pcs', $product->base_unit_name);
        $this->assertSame('carton', $product->unit_type);
        $this->assertSame(47.0, (float) $product->stock);
        $this->assertSame(12.0, (float) $product->units_per_carton);
        $this->assertSame(5.0, (float) $product->units_per_roll);
        $this->assertDatabaseHas('product_units', ['product_id' => $product->id, 'unit_name' => 'carton', 'selling_price' => 1100]);
    }

    public function test_piece_carton_stock_accepts_cartons_rolls_and_loose_pieces_together(): void
    {
        $units = $this->workspace();
        $payload = $this->payload($units, 'pieces_carton');
        $payload['stock_cartons'] = 2;
        $payload['stock_rolls'] = 3;
        $payload['stock_units'] = 4;
        $payload['units_per_carton'] = 12;
        $payload['units_per_roll'] = 5;

        $this->post(route('inventory.Products.store'), $payload)
            ->assertSessionHasNoErrors()->assertSessionHas('success');

        $product = Product::sole();
        $this->assertSame(43.0, (float) $product->stock);
        $this->assertSame(12.0, (float) $product->units_per_carton);
        $this->assertSame(5.0, (float) $product->units_per_roll);
    }

    public function test_weight_cartons_still_convert_to_kilograms(): void
    {
        $units = $this->workspace();
        $this->post(route('inventory.Products.store'), $this->payload($units, 'kg_carton'))
            ->assertSessionHasNoErrors()->assertSessionHas('success');
        $product = Product::sole();
        $this->assertSame('kg', $product->base_unit_name);
        $this->assertSame(53.0, (float) $product->stock);
        $this->assertSame($units['kg'], (int) $product->base_unit_id);
    }

    public function test_piece_cartons_require_a_conversion_before_creating_stock(): void
    {
        $units = $this->workspace();
        $payload = $this->payload($units, 'pieces_carton');
        $payload['units_per_carton'] = 0;
        $this->post(route('inventory.Products.store'), $payload)->assertSessionHasErrors('units_per_carton');
        $this->assertDatabaseCount('products', 0);
    }

    public function test_saved_reorder_level_is_used_by_both_pos_product_selectors_after_creation_and_edit(): void
    {
        $units = $this->workspace();
        $payload = $this->payload($units, 'pieces');
        $payload['reorder_level'] = 6;
        $payload['reorder_quantity'] = 100;
        $this->post(route('inventory.Products.store'), $payload)->assertSessionHasNoErrors();
        $product = Product::sole();
        $this->assertSame(6, (int) $product->reorder_level);
        $this->assertSame(100, (int) $product->reorder_quantity);

        $html = $this->get(route('sales.showPos'))->assertOk()->getContent();
        $this->assertStringContainsString('aria-label="Cashier calculator"', $html);
        $this->assertStringContainsString('data-calculator-key="+"', $html);
        $this->assertStringContainsString('data-calculator-action="equals"', $html);
        $this->assertStringContainsString('catalogueUrl', $html);
        $this->assertStringContainsString('assets/plugins/sweetalert/sweetalert2.all.min.js', $html);
        $this->assertStringContainsString('assets/css/select2.min.css', $html);
        $this->assertSame(2, substr_count($html, 'data-min-stock="6"'));
        $this->assertStringNotContainsString('data-min-stock="15"', $html);

        $payload['reorder_level'] = 30;
        $payload['status'] = 'active';
        $payload['stock'] = $product->stock;
        unset($payload['stock_units'], $payload['stock_cartons'], $payload['stock_rolls']);
        $this->put(route('inventory.Products.update', $product->id), $payload)->assertSessionHasNoErrors();
        $html = $this->get(route('sales.showPos'))->assertOk()->getContent();
        $this->assertSame(2, substr_count($html, 'data-min-stock="30"'));
        $this->assertStringNotContainsString('data-min-stock="6"', $html);
    }

    public function test_pos_uses_default_threshold_only_when_no_positive_reorder_level_is_set(): void
    {
        $units = $this->workspace();
        foreach ([null, 0] as $level) {
            $payload = $this->payload($units, 'pieces');
            $payload['reorder_level'] = $level;
            $payload['reorder_quantity'] = 100;
            $this->post(route('inventory.Products.store'), $payload)->assertSessionHasNoErrors();
        }
        $html = $this->get(route('sales.showPos'))->assertOk()->getContent();
        $this->assertSame(4, substr_count($html, 'data-min-stock="15"'));
        $this->assertStringNotContainsString('data-min-stock="100"', $html);
    }

    public function test_all_available_measurements_including_custom_units_can_be_saved_with_opening_stock(): void
    {
        $this->workspace();
        $companyId = auth()->user()->company_id;
        foreach (['roll' => 'Roll', 'yd' => 'Yard', 'm²' => 'Square metre'] as $symbol => $name) {
            DB::table('units')->insert([
                'company_id' => $companyId, 'name' => $name, 'symbol' => $symbol,
                'status' => 'active', 'created_at' => now(), 'updated_at' => now(),
            ]);
        }
        $page = $this->get(route('add-products'))->assertOk()->assertSee('Other measurements')->assertSee('Square metre');
        $units = $page->viewData('units');
        $this->assertGreaterThanOrEqual(11, $units->count());
        $content = $page->getContent();
        $this->assertTrue(strpos($content, 'name="name"') < strpos($content, 'id="other_measurement_unit"'));
        $this->assertTrue(strpos($content, 'id="other_measurement_unit"') < strpos($content, 'name="price"'));

        foreach ($units as $unit) {
            $name = 'Measurement product '.$unit->id;
            $this->post(route('inventory.Products.store'), [
                'name' => $name, 'measurement_mode' => 'custom', 'unit_type' => 'unit',
                'unit_id' => $unit->id, 'base_unit_id' => $unit->id, 'base_unit_name' => $unit->symbol,
                'price' => 100, 'purchase_price' => 80, 'stock_units' => 2.5,
            ])->assertSessionHasNoErrors()->assertRedirect(route('product-list'));
            $product = Product::where('name', $name)->sole();
            $this->assertSame($unit->symbol, $product->base_unit_name);
            $this->assertSame((int) $unit->id, (int) $product->unit_id);
            $this->assertSame((int) $unit->id, (int) $product->base_unit_id);
            $this->assertSame(2.5, (float) $product->stock);
            $this->assertDatabaseHas('product_units', [
                'product_id' => $product->id, 'unit_id' => $unit->id,
                'unit_symbol' => $unit->symbol, 'conversion_factor' => 1, 'is_base_unit' => 1,
            ]);
        }
    }

    public function test_fashion_variants_are_saved_as_independent_products_with_separate_stock(): void
    {
        $units = $this->workspace();
        $companyId = (int) auth()->user()->company_id;
        DB::table('companies')->where('id', $companyId)->update(['fashion_catalog_enabled' => true]);
        $payload = $this->payload($units, 'pieces');
        $payload['name'] = 'Everyday Shirt';
        $payload['brand'] = 'Threaded House';
        $payload['use_variants'] = 1;
        $payload['variants'] = [
            ['size' => 'S', 'color' => 'Blue', 'sku' => '', 'stock' => 4],
            ['size' => 'M', 'color' => 'Blue', 'sku' => '', 'stock' => 9],
        ];

        $this->post(route('inventory.Products.store'), $payload)
            ->assertSessionHasNoErrors()
            ->assertSessionHas('success', '2 product variants added successfully.');

        $products = Product::where('name', 'Everyday Shirt')->get()->keyBy('fashion_size');
        $this->assertCount(2, $products);
        $small = $products->get('S');
        $medium = $products->get('M');
        $this->assertNotSame($small->id, $medium->id);
        $this->assertNotSame($small->sku, $medium->sku);
        $this->assertSame($small->variant_group_id, $medium->variant_group_id);
        $this->assertSame('Threaded House', $small->brand);
        $this->assertSame('S', $small->fashion_size);
        $this->assertSame('M', $medium->fashion_size);
        $this->assertSame('Blue', $small->fashion_color);
        $this->assertSame(4.0, (float) $small->stock);
        $this->assertSame(9.0, (float) $medium->stock);
        $this->assertSame('Threaded House · S · Blue', $small->variantLabel());
    }

    public function test_fashion_variant_combinations_must_be_unique(): void
    {
        $units = $this->workspace();
        DB::table('companies')->where('id', auth()->user()->company_id)->update(['fashion_catalog_enabled' => true]);
        $payload = $this->payload($units, 'pieces');
        $payload['use_variants'] = 1;
        $payload['variants'] = [
            ['size' => 'M', 'color' => 'Navy', 'sku' => '', 'stock' => 2],
            ['size' => 'm', 'color' => 'navy', 'sku' => '', 'stock' => 3],
        ];

        $this->post(route('inventory.Products.store'), $payload)
            ->assertSessionHasErrors('variants.1.size');
        $this->assertDatabaseMissing('products', ['name' => $payload['name']]);
    }

    public function test_inventory_stock_display_keeps_the_final_zero_of_twenty(): void
    {
        $units = $this->workspace();
        $payload = $this->payload($units, 'pieces');
        $payload['stock_units'] = 20;
        $payload['stock'] = 20;
        $this->post(route('inventory.Products.store'), $payload)->assertSessionHasNoErrors();

        $content = $this->get(route('product-list'))->assertOk()->getContent();
        $this->assertMatchesRegularExpression('/<span class="badge bg-success">\s*20\s*<\/span>/', $content);
    }
}
