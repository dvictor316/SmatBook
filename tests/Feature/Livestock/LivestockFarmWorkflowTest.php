<?php

namespace Tests\Feature\Livestock;

use App\Http\Middleware\RequireActiveBranch;
use App\Http\Middleware\SubscriptionActive;
use App\Models\Account;
use App\Models\Company;
use App\Models\LivestockDailyProduction;
use App\Models\LivestockFarm;
use App\Models\LivestockFlock;
use App\Models\LivestockInvestment;
use App\Models\LivestockInventoryMovement;
use App\Models\LivestockOpexEntry;
use App\Models\Transaction;
use App\Models\User;
use App\Support\LivestockAccess;
use App\Support\PlanAccess;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LivestockFarmWorkflowTest extends TestCase
{
    use RefreshDatabase;

    private Company $company;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->company = Company::create(['name' => 'Sunrise Layers', 'industry' => 'livestock', 'plan' => 'Livestock']);
        $this->user = User::factory()->create(['company_id' => $this->company->id]);
        $this->actingAs($this->user)->withoutMiddleware([RequireActiveBranch::class, SubscriptionActive::class]);
    }

    public function test_livestock_company_receives_workspace_and_accounting_access(): void
    {
        $this->assertTrue(LivestockAccess::userIsLivestockTenant($this->user));
        $this->assertSame('enterprise', PlanAccess::resolveTierForUser($this->user));
        $this->get(route('livestock.dashboard'))->assertOk()->assertSee('Set Up Your Layer Farm');
    }

    public function test_flock_creation_records_point_of_cage_birds_as_working_capital(): void
    {
        $farm = $this->createFarm();

        $this->post(route('livestock.flocks.store'), [
            'farm_id' => $farm->id,
            'batch_code' => 'BATCH-001',
            'breed' => 'ISA Brown',
            'placement_date' => now()->toDateString(),
            'age_at_placement_weeks' => 14,
            'opening_birds' => 1000,
            'cost_per_bird' => 2500,
            'supplier' => 'Quality Pullets Ltd',
        ])->assertRedirect()->assertSessionHasNoErrors();

        $flock = LivestockFlock::firstOrFail();
        $this->assertSame(1000, $flock->current_birds);
        $this->assertDatabaseHas('livestock_investments', [
            'flock_id' => $flock->id,
            'cost_class' => 'working_capital',
            'category' => 'point_of_cage_birds',
            'cost' => 2500000,
        ]);
    }

    public function test_daily_production_calculates_birds_eggs_and_hen_day_rate(): void
    {
        $farm = $this->createFarm();
        $flock = $this->createFlock($farm);

        $this->post(route('livestock.production.store'), [
            'farm_id' => $farm->id,
            'flock_id' => $flock->id,
            'production_date' => now()->toDateString(),
            'opening_birds' => 1000,
            'mortality' => 2,
            'culled' => 1,
            'egg_crates' => 30,
            'loose_eggs' => 10,
            'damaged_eggs' => 5,
            'feed_kg' => 115.5,
            'water_litres' => 220,
        ])->assertRedirect()->assertSessionHasNoErrors();

        $production = LivestockDailyProduction::firstOrFail();
        $this->assertSame(997, $production->closing_birds);
        $this->assertSame(910, $production->total_good_eggs);
        $this->assertEquals(91.0, $production->hen_day_percent);
        $this->assertSame(997, $flock->fresh()->current_birds);

        $this->delete(route('livestock.production.destroy', $production))->assertRedirect();
        $this->assertSame(1000, $flock->fresh()->current_birds);
    }

    public function test_cost_return_register_accepts_all_major_record_classes(): void
    {
        $farm = $this->createFarm();
        $flock = $this->createFlock($farm);

        $this->post(route('livestock.investments.store'), [
            'farm_id' => $farm->id, 'cost_class' => 'capital', 'category' => 'battery_cages',
            'description' => 'Automated battery cages', 'cost_date' => now()->startOfMonth()->toDateString(),
            'cost' => 1200000, 'salvage_value' => 0, 'useful_life_months' => 120,
        ])->assertRedirect()->assertSessionHasNoErrors();

        $this->post(route('livestock.opex.store'), [
            'farm_id' => $farm->id, 'flock_id' => $flock->id, 'expense_date' => now()->toDateString(),
            'category' => 'feeds', 'description' => 'Layer mash', 'quantity' => 100, 'unit' => 'kg',
            'unit_cost' => 500, 'amount' => 50000, 'frequency' => 'daily',
        ])->assertRedirect()->assertSessionHasNoErrors();

        $this->post(route('livestock.revenue.store'), [
            'farm_id' => $farm->id, 'flock_id' => $flock->id, 'revenue_date' => now()->toDateString(),
            'source' => 'eggs', 'description' => 'Wholesale eggs', 'quantity' => 100, 'unit' => 'crate',
            'unit_price' => 2000, 'amount' => 200000, 'customer' => 'Market Distributor',
        ])->assertRedirect()->assertSessionHasNoErrors();

        $asset = LivestockInvestment::where('category', 'battery_cages')->firstOrFail();
        $this->assertEquals(10000, $asset->allocationAsOf(now()));
        $this->assertDatabaseHas('livestock_opex_entries', ['category' => 'feeds', 'amount' => 50000]);
        $this->assertDatabaseHas('livestock_revenue_entries', ['source' => 'eggs', 'amount' => 200000]);
        $this->get(route('livestock.dashboard'))->assertOk()->assertSee('Net Farm Return')->assertSee('Battery Cages');
    }

    public function test_production_and_sales_reconcile_feed_and_egg_inventory(): void
    {
        $farm = $this->createFarm();
        $flock = $this->createFlock($farm);

        $this->post(route('livestock.inventory.store'), [
            'farm_id' => $farm->id, 'movement_date' => now()->toDateString(), 'item_type' => 'feed',
            'movement_type' => 'opening', 'quantity' => 500, 'unit_cost' => 600, 'notes' => 'Opening feed count',
        ])->assertRedirect()->assertSessionHasNoErrors();

        $this->post(route('livestock.production.store'), [
            'farm_id' => $farm->id, 'flock_id' => $flock->id, 'production_date' => now()->toDateString(),
            'opening_birds' => 1000, 'mortality' => 0, 'culled' => 0, 'egg_crates' => 20,
            'loose_eggs' => 5, 'damaged_eggs' => 2, 'feed_kg' => 110, 'water_litres' => 200,
        ])->assertRedirect()->assertSessionHasNoErrors();

        $this->post(route('livestock.revenue.store'), [
            'farm_id' => $farm->id, 'flock_id' => $flock->id, 'revenue_date' => now()->toDateString(),
            'source' => 'eggs', 'quantity' => 10, 'unit' => 'crate', 'unit_price' => 2500,
            'amount' => 25000, 'customer' => 'Distributor',
        ])->assertRedirect()->assertSessionHasNoErrors();

        $this->assertEquals(390, LivestockInventoryMovement::where('item_type', 'feed')->sum('quantity'));
        $this->assertEquals(305, LivestockInventoryMovement::where('item_type', 'eggs')->sum('quantity'));
        $this->assertDatabaseHas('livestock_inventory_movements', ['movement_type' => 'production', 'quantity' => 605]);
        $this->assertDatabaseHas('livestock_inventory_movements', ['movement_type' => 'sale', 'quantity' => -300]);
    }

    public function test_opex_can_post_and_reverse_a_balanced_journal(): void
    {
        $farm = $this->createFarm();
        $expense = Account::create(['company_id' => $this->company->id, 'user_id' => $this->user->id, 'code' => 'FARM-OPEX', 'name' => 'Farm Operating Expense', 'type' => Account::TYPE_EXPENSE, 'sub_type' => 'Operating Expense', 'is_active' => true]);
        $cash = Account::create(['company_id' => $this->company->id, 'user_id' => $this->user->id, 'code' => 'CASH', 'name' => 'Cash', 'type' => Account::TYPE_ASSET, 'sub_type' => 'Cash & Bank', 'is_active' => true]);

        $this->post(route('livestock.opex.store'), [
            'farm_id' => $farm->id, 'expense_date' => now()->toDateString(), 'category' => 'maintenance',
            'description' => 'Cage repairs', 'quantity' => 1, 'unit' => 'job', 'unit_cost' => 45000,
            'amount' => 45000, 'frequency' => 'occasional', 'post_to_ledger' => 1,
            'debit_account_id' => $expense->id, 'credit_account_id' => $cash->id,
        ])->assertRedirect()->assertSessionHasNoErrors();

        $entry = LivestockOpexEntry::firstOrFail();
        $this->assertNotNull($entry->posted_at);
        $this->assertEquals(45000, Transaction::where('related_type', LivestockOpexEntry::class)->sum('debit'));
        $this->assertEquals(45000, Transaction::where('related_type', LivestockOpexEntry::class)->sum('credit'));

        $this->post(route('livestock.opex.journal.reverse', $entry))->assertRedirect()->assertSessionHasNoErrors();
        $this->assertNotNull($entry->fresh()->reversed_at);
        $this->assertEquals(90000, Transaction::where('related_type', LivestockOpexEntry::class)->sum('debit'));
        $this->assertEquals(90000, Transaction::where('related_type', LivestockOpexEntry::class)->sum('credit'));
    }

    private function createFarm(): LivestockFarm
    {
        return LivestockFarm::create([
            'company_id' => $this->company->id, 'name' => 'Main Farm', 'code' => 'MAIN', 'farm_type' => 'layers',
            'bird_capacity' => 5000, 'eggs_per_crate' => 30, 'is_active' => true, 'created_by' => $this->user->id,
        ]);
    }

    private function createFlock(LivestockFarm $farm): LivestockFlock
    {
        return LivestockFlock::create([
            'company_id' => $this->company->id, 'farm_id' => $farm->id, 'batch_code' => 'BATCH-001',
            'placement_date' => now()->toDateString(), 'age_at_placement_weeks' => 14, 'opening_birds' => 1000,
            'current_birds' => 1000, 'cost_per_bird' => 2500, 'status' => 'active', 'created_by' => $this->user->id,
        ]);
    }
}
