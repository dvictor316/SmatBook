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
            ->assertSee('Opening Full Cartons');
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
        $this->assertSame(27.0, (float) $product->stock);
        $this->assertSame(12.0, (float) $product->units_per_carton);
        $this->assertDatabaseHas('product_units', ['product_id' => $product->id, 'unit_name' => 'carton', 'selling_price' => 1100]);
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
}
