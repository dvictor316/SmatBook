<?php

namespace Tests\Feature;

use App\Http\Middleware\EnforceDeviceSessionLimit;
use App\Http\Middleware\ForceLogoutExpiredSession;
use App\Models\Company;
use App\Models\Plan;
use App\Models\Subscription;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
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

    public function test_switching_from_a_tenant_subdomain_returns_to_the_canonical_app_host(): void
    {
        config(['app.url' => 'https://smartprobook.com']);
        $owner = User::factory()->create(['role' => 'admin']);
        $first = $this->createBusiness($owner, 'First Business');
        $second = $this->createBusiness($owner, 'Second Business');
        $owner->forceFill(['company_id' => $first->id])->save();
        $this->actingAs($owner);
        session(['current_tenant_id' => $first->id]);

        $request = \Illuminate\Http\Request::create(
            'https://first-business.smartprobook.com/workspace/businesses/'.$second->id.'/activate',
            'POST'
        );
        $request->setUserResolver(fn () => $owner);
        $response = app(\App\Http\Controllers\BusinessWorkspaceController::class)->activate(
            $request,
            $second->id,
            app(\App\Support\BusinessWorkspaceResolver::class),
            app(\App\Support\ActiveBranchResolver::class)
        );

        $this->assertSame('https://smartprobook.com/dashboard', $response->getTargetUrl());
        $this->assertSame($second->id, (int) session('current_tenant_id'));
    }

    public function test_pending_additional_workspace_redirects_to_checkout_without_a_plans_redirect_loop(): void
    {
        $owner = User::factory()->create(['role' => 'admin']);
        $first = $this->createBusiness($owner, 'First Business');
        $second = $this->createBusiness($owner, 'Second Business');
        $owner->forceFill(['company_id' => $first->id])->save();
        Subscription::withoutGlobalScope('tenant')->create([
            'user_id' => $owner->id,
            'company_id' => $first->id,
            'plan' => 'Enterprise Yearly',
            'amount' => 300000,
            'status' => 'Active',
            'payment_status' => 'paid',
        ]);
        $pending = Subscription::withoutGlobalScope('tenant')->create([
            'user_id' => $owner->id,
            'company_id' => $second->id,
            'plan' => 'Enterprise Yearly',
            'amount' => 300000,
            'status' => 'Pending',
            'payment_status' => 'unpaid',
        ]);
        Route::middleware(['web', 'auth', 'subscription.active'])
            ->get('/__workspace/subscription-gate', fn () => response('ok'));

        $response = $this->actingAs($owner)
            ->withSession(['current_tenant_id' => $second->id])
            ->get('/__workspace/subscription-gate');

        $response->assertRedirect(route('saas.checkout', $pending->id));
    }

    public function test_unsubscribed_workspace_stays_on_plans_instead_of_redirecting_back_to_dashboard(): void
    {
        $owner = User::factory()->create(['role' => 'admin']);
        $first = $this->createBusiness($owner, 'First Business');
        $second = $this->createBusiness($owner, 'Second Business');
        $second->update(['status' => 'pending']);
        $owner->forceFill(['company_id' => $first->id])->save();
        Route::middleware(['web', 'auth', 'subscription.active'])
            ->get('/__workspace/unsubscribed-gate', fn () => response('ok'));

        $response = $this->actingAs($owner)
            ->withSession(['current_tenant_id' => $second->id])
            ->get('/__workspace/unsubscribed-gate');

        $response->assertRedirect(route('membership-plans', ['stay' => 1]));
        $this->get(route('membership-plans', ['stay' => 1]))->assertOk();
    }

    public function test_user_cannot_switch_to_an_unowned_business(): void
    {
        $owner = User::factory()->create(['role' => 'admin']);
        $otherOwner = User::factory()->create(['role' => 'admin']);
        $first = $this->createBusiness($owner, 'First Business');
        $foreign = $this->createBusiness($otherOwner, 'Foreign Business');
        $owner->forceFill(['company_id' => $first->id])->save();

        $response = $this->actingAs($owner)
            ->postJson(route('workspace.businesses.activate', ['companyId' => $foreign->id]));
        $response->assertForbidden();

        $this->assertNotSame($foreign->id, (int) session('current_tenant_id'));
    }

    public function test_accessible_businesses_are_reused_within_a_read_only_request(): void
    {
        $owner = User::factory()->create(['role' => 'admin']);
        $company = $this->createBusiness($owner, 'Cached Business');
        $owner->forceFill(['company_id' => $company->id])->save();
        $companyQueries = 0;
        $retiredWorkspaceQueries = 0;

        DB::listen(static function ($query) use (&$companyQueries, &$retiredWorkspaceQueries): void {
            $sql = strtolower($query->sql);
            if (str_contains($sql, 'from "companies"')) {
                $companyQueries++;
            }
            if (str_contains($sql, 'from "settings"')) {
                $retiredWorkspaceQueries++;
            }
        });

        $resolver = app(\App\Support\BusinessWorkspaceResolver::class);
        $first = $resolver->accessibleCompanies($owner);
        $second = $resolver->accessibleCompanies($owner);

        $this->assertSame([$company->id], $first->modelKeys());
        $this->assertSame($first->modelKeys(), $second->modelKeys());
        $this->assertSame(1, $companyQueries);
        $this->assertSame(1, $retiredWorkspaceQueries);
    }

    public function test_current_subscription_resolution_does_not_reload_the_same_record(): void
    {
        $owner = User::factory()->create(['role' => 'admin']);
        $company = $this->createBusiness($owner, 'Subscription Lookup Business');
        $owner->forceFill(['company_id' => $company->id])->save();
        $subscription = Subscription::withoutGlobalScope('tenant')->create([
            'user_id' => $owner->id,
            'company_id' => $company->id,
            'plan' => 'Basic',
            'billing_cycle' => 'Yearly',
            'amount' => 80000,
            'status' => 'Active',
            'payment_status' => 'paid',
            'start_date' => now(),
            'end_date' => now()->addYear(),
        ]);
        $subscriptionQueries = 0;

        DB::listen(static function ($query) use (&$subscriptionQueries): void {
            if (str_contains(strtolower($query->sql), 'subscriptions')) {
                $subscriptionQueries++;
            }
        });

        $resolved = Subscription::resolveCurrentForUser($owner);

        $this->assertSame($subscription->id, $resolved?->id);
        $this->assertSame(1, $subscriptionQueries);
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
