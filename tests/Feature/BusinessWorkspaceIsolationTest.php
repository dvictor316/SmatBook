<?php

namespace Tests\Feature;

use App\Http\Middleware\EnforceDeviceSessionLimit;
use App\Http\Middleware\ForceLogoutExpiredSession;
use App\Models\Company;
use App\Models\Plan;
use App\Models\Subscription;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BusinessWorkspaceIsolationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutMiddleware([
            EnforceDeviceSessionLimit::class,
            ForceLogoutExpiredSession::class,
        ]);
    }

    public function test_owner_can_switch_between_owned_businesses_without_changing_primary_assignment(): void
    {
        $owner = User::factory()->create(['role' => 'admin']);
        $first = $this->createBusiness($owner, 'First Business');
        $second = $this->createBusiness($owner, 'Second Business');
        $owner->forceFill(['company_id' => $first->id])->save();

        Subscription::query()->create([
            'user_id' => $owner->id,
            'company_id' => $second->id,
            'plan' => 'Enterprise Yearly',
            'billing_cycle' => 'Yearly',
            'amount' => 300000,
            'status' => 'Active',
            'payment_status' => 'paid',
            'start_date' => now(),
            'end_date' => now()->addYear(),
        ]);

        $response = $this->actingAs($owner)
            ->post(route('workspace.businesses.activate', ['companyId' => $second->id]));

        $response->assertRedirect(route('user.dashboard'));
        $this->assertSame($second->id, (int) session('current_tenant_id'));
        $this->assertSame('enterprise yearly', session('user_plan'));
        $this->assertSame($first->id, (int) $owner->fresh()->company_id);
        $this->assertSame($second->id, (int) Subscription::resolveCurrentForUser($owner)->company_id);
    }

    public function test_user_cannot_switch_to_an_unowned_business(): void
    {
        $owner = User::factory()->create(['role' => 'admin']);
        $otherOwner = User::factory()->create(['role' => 'admin']);
        $first = $this->createBusiness($owner, 'First Business');
        $foreign = $this->createBusiness($otherOwner, 'Foreign Business');
        $owner->forceFill(['company_id' => $first->id])->save();

        $response = $this->actingAs($owner)
            ->post(route('workspace.businesses.activate', ['companyId' => $foreign->id]));
        $response->assertForbidden();

        $this->assertNotSame($foreign->id, (int) session('current_tenant_id'));
    }

    public function test_additional_business_gets_a_separate_paid_subscription_before_setup(): void
    {
        $owner = User::factory()->create(['role' => 'admin']);
        $first = $this->createBusiness($owner, 'First Business');
        $owner->forceFill(['company_id' => $first->id])->save();

        Plan::query()->create([
            'name' => 'Starter Yearly',
            'price' => 20000,
            'billing_cycle' => 'yearly',
            'status' => 'active',
            'is_active' => true,
            'user_limit' => 1,
        ]);

        $response = $this->actingAs($owner)
            ->withSession([
                'current_tenant_id' => $first->id,
                'creating_additional_business' => true,
            ])
            ->get(route('subscription.upgrade.redirect', [
                'plan' => 'starter',
                'new_business' => 1,
            ]));

        $subscription = Subscription::withoutGlobalScope('tenant')->latest('id')->firstOrFail();

        $response->assertRedirect(route('saas.setup', ['id' => $subscription->id]));
        $this->assertTrue($subscription->is_business_addition);
        $this->assertNull($subscription->company_id);
        $this->assertSame('unpaid', $subscription->payment_status);
        $this->assertSame('pending', strtolower($subscription->status));

        $retryResponse = $this->get(route('subscription.upgrade.redirect', [
            'plan' => 'starter',
            'new_business' => 1,
        ]));

        $retryResponse->assertRedirect(route('saas.setup', ['id' => $subscription->id]));
        $this->assertSame(1, Subscription::withoutGlobalScope('tenant')
            ->where('user_id', $owner->id)
            ->where('is_business_addition', true)
            ->whereNull('company_id')
            ->count());

        $setupResponse = $this->post(route('saas.store'), [
            'subscription_id' => $subscription->id,
            'customer_name' => 'Second Business',
            'domain_prefix' => 'second-business',
            'branch_name' => 'Headquarters',
            'employees' => '1-5',
        ]);

        $subscription->refresh();
        $setupResponse->assertRedirect(route('saas.checkout', $subscription->id));
        $this->assertNotNull($subscription->company_id);
        $this->assertNotSame($first->id, (int) $subscription->company_id);
        $this->assertSame(2, Company::withoutGlobalScope('tenant')->where('user_id', $owner->id)->count());
        $this->assertSame((int) $subscription->company_id, (int) session('current_tenant_id'));
        $this->assertSame($first->id, (int) $owner->fresh()->company_id);
    }

    private function createBusiness(User $owner, string $name): Company
    {
        return Company::withoutGlobalScope('tenant')->create([
            'user_id' => $owner->id,
            'owner_id' => $owner->id,
            'name' => $name,
            'status' => 'active',
            'plan' => 'Enterprise Yearly',
            'domain_prefix' => str($name)->slug(),
        ]);
    }
}
