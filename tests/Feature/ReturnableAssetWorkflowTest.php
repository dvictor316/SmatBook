<?php

namespace Tests\Feature;

use App\Http\Middleware\BlockExpiredDemoUser;
use App\Http\Middleware\CheckPlanAccess;
use App\Http\Middleware\EnforceDeviceSessionLimit;
use App\Http\Middleware\ForceLogoutExpiredSession;
use App\Http\Middleware\RequireActiveBranch;
use App\Http\Middleware\SubscriptionActive;
use App\Models\Company;
use App\Models\ReturnableAsset;
use App\Models\ReturnableAssetMovement;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReturnableAssetWorkflowTest extends TestCase
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

    public function test_asset_can_be_issued_and_partially_returned(): void
    {
        [$user, $company] = $this->workspace('Custody Company');
        $session = ['current_tenant_id' => $company->id, 'active_branch_scope' => 'all'];

        $this->actingAs($user)->withSession($session)->post(route('returnable-assets.store'), [
            'asset_code' => 'CRATE-001',
            'name' => 'Reusable Produce Crate',
            'category' => 'Crate',
            'total_quantity' => 20,
            'deposit_amount' => 2500,
            'replacement_cost' => 5000,
        ])->assertSessionHasNoErrors();

        $asset = ReturnableAsset::withoutGlobalScopes()->where('company_id', $company->id)->firstOrFail();
        $this->actingAs($user)->withSession($session)->post(route('returnable-assets.issue', $asset), [
            'quantity' => 8,
            'custodian_name' => 'Market Customer',
            'issued_on' => now()->toDateString(),
            'due_on' => now()->addWeek()->toDateString(),
            'deposit_held' => 20000,
        ])->assertSessionHasNoErrors();

        $issue = ReturnableAssetMovement::withoutGlobalScopes()->where('movement_type', 'issue')->firstOrFail();
        $this->assertSame(12, (int) $asset->fresh()->available_quantity);

        $this->actingAs($user)->withSession($session)->post(route('returnable-assets.return', $issue), [
            'quantity' => 3,
            'returned_on' => now()->toDateString(),
            'condition' => 'good',
        ])->assertSessionHasNoErrors();

        $this->assertSame(15, (int) $asset->fresh()->available_quantity);
        $this->assertSame(3, (int) $issue->fresh()->returned_quantity);
        $this->assertSame(5, $issue->fresh()->outstanding_quantity);
        $this->assertDatabaseHas('returnable_asset_movements', [
            'parent_movement_id' => $issue->id,
            'movement_type' => 'return',
            'quantity' => 3,
        ]);
    }

    public function test_foreign_tenant_cannot_issue_or_return_an_asset(): void
    {
        [$owner, $ownerCompany] = $this->workspace('Owner Company');
        [$other, $otherCompany] = $this->workspace('Other Company');
        $asset = ReturnableAsset::withoutGlobalScopes()->create([
            'company_id' => $ownerCompany->id,
            'created_by' => $owner->id,
            'asset_code' => 'PALLET-001',
            'name' => 'Warehouse Pallet',
            'total_quantity' => 4,
            'available_quantity' => 4,
            'status' => 'active',
        ]);

        $this->actingAs($other)
            ->withSession(['current_tenant_id' => $otherCompany->id, 'active_branch_scope' => 'all'])
            ->post(route('returnable-assets.issue', $asset), [
                'quantity' => 1,
                'custodian_name' => 'Unauthorised',
                'issued_on' => now()->toDateString(),
            ])->assertNotFound();

        $this->assertSame(4, (int) $asset->fresh()->available_quantity);
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
        ]);
        $user->forceFill(['company_id' => $company->id])->save();

        return [$user, $company];
    }
}
