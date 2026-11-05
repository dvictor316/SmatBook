<?php

namespace Tests\Feature;

use App\Models\DeploymentManager;
use App\Models\Subscription;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\DB;
use Laravel\Socialite\Facades\Socialite;
use Tests\TestCase;

class AssistedBusinessRegistrationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Mail::fake();
    }

    public function test_agent_state_manager_and_super_admin_can_activate_a_one_month_trial(): void
    {
        $registrars = [
            'agent' => ['route' => 'agent.registration.store', 'manager_record' => false],
            'state_manager' => ['route' => 'deployment.customers.store', 'manager_record' => true],
            'super_admin' => ['route' => 'super_admin.assisted_registrations.store', 'manager_record' => false],
        ];

        foreach ($registrars as $role => $registration) {
            $registrar = User::factory()->create([
                'role' => $role,
                'is_verified' => 1,
                'status' => 'active',
            ]);

            if ($registration['manager_record']) {
                DeploymentManager::withoutGlobalScopes()->create([
                    'user_id' => $registrar->id,
                    'business_name' => ucfirst(str_replace('_', ' ', $role)).' Office',
                    'status' => 'active',
                    'deployment_limit' => 100,
                    'commission_rate' => 35,
                ]);
            }

            $slug = str_replace('_', '-', $role).'-client';
            $email = $slug.'@example.test';
            $password = 'ClientPass123';
            $customerPhone = match ($role) {
                'agent' => '+2348098765432',
                'state_manager' => '+2348098765433',
                default => '+2348098765434',
            };
            $plan = $role === 'agent'
                ? ['id' => 'livestock-yearly', 'name' => 'Livestock', 'price' => 200000]
                : ['id' => 'starter-yearly', 'name' => 'Starter', 'price' => 20000];

            $registrationPayload = [
                'company_name' => ucfirst($slug).' Limited',
                'subdomain' => $slug,
                'company_email' => $email,
                'phone' => '+2348012345678',
                'industry' => $role === 'agent' ? 'livestock' : 'retail',
                'plan_id' => $plan['id'],
                'plan_name' => $plan['name'],
                'plan_price' => $plan['price'],
                'billing_cycle' => 'yearly',
                'name' => ucfirst($role).' Client Owner',
                'email' => $email,
                'password' => $password,
                'password_confirmation' => $password,
                'customer_phone' => $customerPhone,
            ];

            $response = $this->actingAs($registrar)->post(route($registration['route']), $registrationPayload);

            $subscription = \DB::table('subscriptions')->where('deployed_by', $registrar->id)->first();
            $customer = User::where('email', $email)->first();
            $company = \DB::table('companies')->where('domain_prefix', $slug)->first();
            $dashboardRoute = match ($role) {
                'agent' => 'agent.dashboard',
                'state_manager' => 'deployment.dashboard',
                default => 'super_admin.dashboard',
            };

            $this->assertNotNull($subscription, "{$role} registration did not create a subscription.");
            $response->assertRedirect(route($dashboardRoute));
            $response->assertSessionHasNoErrors();
            $this->assertNotNull($customer);
            $this->assertNotNull($company);
            $this->assertTrue(Hash::check($password, $customer->password));
            $this->assertSame((int) $company->id, (int) $customer->company_id);
            $this->assertSame('active', strtolower((string) $customer->status));
            $this->assertSame(1, (int) $customer->is_verified);
            $this->assertSame('active', strtolower((string) $company->status));
            $this->assertSame('trial', strtolower((string) $subscription->status));
            $this->assertSame('free', strtolower((string) $subscription->payment_status));
            $this->assertTrue(\Illuminate\Support\Carbon::parse($subscription->end_date)->isSameDay(now()->addMonth()));
            $this->assertSame(
                'active',
                strtolower((string) \DB::table('deployment_companies')->where('company_id', $company->id)->value('deployment_status'))
            );
            $this->assertDatabaseHas('domains', [
                'domain_name' => $slug,
                'status' => 'Active',
            ]);
            $this->assertDatabaseHas('deployment_companies', [
                'manager_id' => $registrar->id,
                'company_id' => $company->id,
                'deployment_status' => 'active',
            ]);

            $retryResponse = $this->actingAs($registrar)->post(route($registration['route']), $registrationPayload);
            $retryResponse->assertSessionHasErrors('email');
            $this->assertSame(1, \DB::table('users')->where('email', $email)->count());
            $this->assertSame(1, \DB::table('companies')->where('domain_prefix', $slug)->count());
            $this->assertSame(1, \DB::table('subscriptions')->where('deployed_by', $registrar->id)->count());

            $trial = Subscription::findOrFail($subscription->id);
            $trial->update(['end_date' => now()->subDay()]);

            $this->actingAs($customer)
                ->withSession([
                    'current_tenant_id' => $company->id,
                    'workspace_context' => 'business',
                ])
                ->get(route('user.dashboard'))
                ->assertRedirect(route('subscription.expired'));

            $this->get(route('subscription.expired'))
                ->assertRedirect(route('subscription.upgrade.redirect', [
                    'plan' => $trial->renewalPlanKey(),
                ]));
        }
    }

    public function test_business_registration_page_loads_plan_catalog_once(): void
    {
        $agent = User::factory()->create([
            'role' => 'agent',
            'is_verified' => 1,
            'status' => 'active',
        ]);
        $planCatalogQueries = 0;

        DB::listen(static function ($query) use (&$planCatalogQueries): void {
            $sql = strtolower($query->sql);
            if (str_contains($sql, 'plans') && str_contains($sql, 'billing_cycle') && str_contains($sql, 'is_active')) {
                $planCatalogQueries++;
            }
        });

        $this->actingAs($agent)
            ->get(route('agent.registration.create'))
            ->assertOk();

        $this->assertSame(1, $planCatalogQueries);
    }

    public function test_public_registration_starts_trial_and_activates_workspace_before_payment(): void
    {
        $response = $this->from(route('saas-register'))->post(route('saas-register.post'), [
            'role' => 'admin',
            'name' => 'Public Business Owner',
            'email' => 'public-owner@example.test',
            'password' => 'ClientPass123',
            'password_confirmation' => 'ClientPass123',
            'plan' => 'pro',
            'billing_cycle' => 'yearly',
            'field_of_operation' => 'general',
        ]);

        $response->assertRedirect(route('saas.setup', \DB::table('subscriptions')->value('id')));

        $owner = User::where('email', 'public-owner@example.test')->firstOrFail();
        $subscription = \DB::table('subscriptions')->where('user_id', $owner->id)->first();
        $this->assertNotNull($subscription);

        $this->assertSame('active', strtolower((string) $owner->status));
        $this->assertSame('trial', strtolower((string) $subscription->status));
        $this->assertSame('free', strtolower((string) $subscription->payment_status));
        $this->assertTrue(\Illuminate\Support\Carbon::parse($subscription->end_date)->isSameDay(now()->addMonth()));

        $setupResponse = $this->post(route('saas.store'), [
            'subscription_id' => $subscription->id,
            'customer_name' => 'Public Business Limited',
            'domain_prefix' => 'public-business',
            'branch_name' => 'Head Office',
            'employees' => '1-10',
        ]);

        $setupResponse->assertRedirect(route('user.dashboard'));
        $this->assertDatabaseHas('companies', [
            'user_id' => $owner->id,
            'domain_prefix' => 'public-business',
            'status' => 'active',
        ]);
        $this->assertDatabaseHas('domains', [
            'domain_name' => 'public-business',
            'status' => 'Active',
        ]);

        $trial = Subscription::findOrFail($subscription->id);
        $trial->update(['end_date' => now()->subDay()]);

        $this->get(route('user.dashboard'))
            ->assertRedirect(route('subscription.expired'));
        $this->get(route('subscription.expired'))
            ->assertRedirect(route('subscription.upgrade.redirect', [
                'plan' => $trial->renewalPlanKey(),
            ]));
    }

    public function test_social_registration_starts_a_one_month_trial(): void
    {
        $socialUser = \Mockery::mock();
        $socialUser->shouldReceive('getId')->once()->andReturn('google-trial-user');
        $socialUser->shouldReceive('getEmail')->once()->andReturn('google-trial@example.test');
        $socialUser->shouldReceive('getName')->once()->andReturn('Google Trial User');

        $driver = \Mockery::mock();
        $driver->shouldReceive('redirectUrl')->once()->andReturnSelf();
        $driver->shouldReceive('scopes')->once()->andReturnSelf();
        $driver->shouldReceive('with')->once()->andReturnSelf();
        $driver->shouldReceive('stateless')->once()->andReturnSelf();
        $driver->shouldReceive('user')->once()->andReturn($socialUser);
        Socialite::shouldReceive('driver')->once()->with('google')->andReturn($driver);

        $this->withSession([
            'social_auth_context' => [
                'provider' => 'google',
                'intent' => 'register',
                'plan' => 'pro',
                'cycle' => 'yearly',
                'plan_id' => null,
                'amount' => 150000,
            ],
        ])->get(route('social.callback', ['provider' => 'google']))
            ->assertRedirect(route('saas.setup', \DB::table('subscriptions')->value('id')));

        $customer = User::where('email', 'google-trial@example.test')->firstOrFail();
        $trial = Subscription::where('user_id', $customer->id)->firstOrFail();

        $this->assertSame('active', strtolower((string) $customer->status));
        $this->assertSame(1, (int) $customer->is_verified);
        $this->assertSame('trial', strtolower((string) $trial->status));
        $this->assertSame('free', strtolower((string) $trial->payment_status));
        $this->assertTrue($trial->end_date->isSameDay(now()->addMonth()));
    }

    public function test_super_admin_paid_registration_is_separate_from_free_custom_deployment(): void
    {
        $superAdmin = User::factory()->create(['role' => 'super_admin']);

        $this->actingAs($superAdmin)
            ->get(route('super_admin.assisted_registrations.create'))
            ->assertOk()
            ->assertViewIs('deployment.users.create')
            ->assertViewHas('pageTitle', 'Register New Business Owner')
            ->assertViewHas('formActionRoute', 'super_admin.assisted_registrations.store')
            ->assertViewHas('isSuperAdminDeployment', false);

        $this->actingAs($superAdmin)
            ->get(route('super_admin.custom_deployments.create'))
            ->assertOk()
            ->assertViewIs('deployment.users.create')
            ->assertViewHas('pageTitle', 'Deploy Custom Unlimited User')
            ->assertViewHas('formActionRoute', 'super_admin.custom_deployments.store')
            ->assertViewHas('isSuperAdminDeployment', true);
    }
}
