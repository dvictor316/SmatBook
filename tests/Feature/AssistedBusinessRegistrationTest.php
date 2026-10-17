<?php

namespace Tests\Feature;

use App\Models\DeploymentManager;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class AssistedBusinessRegistrationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Mail::fake();
    }

    public function test_agent_state_manager_and_super_admin_can_register_a_business_for_checkout(): void
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

            $this->assertNotNull($subscription, "{$role} registration did not create a subscription.");
            $response->assertRedirect(route('saas.checkout', $subscription->id));
            $response->assertSessionHasNoErrors();
            $this->assertNotNull($customer);
            $this->assertNotNull($company);
            $this->assertTrue(Hash::check($password, $customer->password));
            $this->assertSame((int) $company->id, (int) $customer->company_id);
            $this->assertSame('pending', strtolower((string) $subscription->status));
            $this->assertSame('unpaid', strtolower((string) $subscription->payment_status));
            $this->assertDatabaseHas('deployment_companies', [
                'manager_id' => $registrar->id,
                'company_id' => $company->id,
                'deployment_status' => 'pending',
            ]);

            $retryResponse = $this->actingAs($registrar)->post(route($registration['route']), $registrationPayload);
            $retryResponse->assertRedirect(route('saas.checkout', $subscription->id));
            $retryResponse->assertSessionHasNoErrors();
            $this->assertSame(1, \DB::table('users')->where('email', $email)->count());
            $this->assertSame(1, \DB::table('companies')->where('domain_prefix', $slug)->count());
            $this->assertSame(1, \DB::table('subscriptions')->where('deployed_by', $registrar->id)->count());

            $this->get(route('saas.payment.callback', [
                'sub_id' => $subscription->id,
                'gateway' => 'flutterwave',
                'reference' => 'LOCAL-FLW-'.$subscription->id.'-TEST',
            ]));

            $this->assertDatabaseHas('domains', [
                'domain_name' => $slug,
                'status' => 'Active',
            ]);
            $this->assertSame('active', strtolower((string) \DB::table('companies')->where('id', $company->id)->value('status')));
            $this->assertSame('paid', strtolower((string) \DB::table('subscriptions')->where('id', $subscription->id)->value('payment_status')));

            $paidRetryResponse = $this->actingAs($registrar)->post(route($registration['route']), $registrationPayload);
            $paidRetryResponse->assertSessionHasErrors('email');
            $this->assertSame(1, \DB::table('users')->where('email', $email)->count());
            $this->assertSame(1, \DB::table('companies')->where('domain_prefix', $slug)->count());
            $this->assertSame(1, \DB::table('subscriptions')->where('deployed_by', $registrar->id)->count());
        }
    }

    public function test_approved_public_registration_configures_workspace_before_checkout_and_provisions_domain_after_payment(): void
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

        $response->assertRedirect(route('registration.pending.notice'));

        $owner = User::where('email', 'public-owner@example.test')->firstOrFail();
        $subscription = \DB::table('subscriptions')->where('user_id', $owner->id)->first();
        $this->assertNotNull($subscription);

        $this->assertSame('pending', strtolower((string) $owner->status));
        $this->assertSame('pending', strtolower((string) $subscription->status));
        $this->assertSame('unpaid', strtolower((string) $subscription->payment_status));

        $owner->forceFill(['status' => 'active', 'is_verified' => 1])->save();

        $this->actingAs($owner)
            ->get(route('home'))
            ->assertRedirect(route('saas.setup', $subscription->id));

        $setupResponse = $this->post(route('saas.store'), [
            'subscription_id' => $subscription->id,
            'customer_name' => 'Public Business Limited',
            'domain_prefix' => 'public-business',
            'branch_name' => 'Head Office',
            'employees' => '1-10',
        ]);

        $setupResponse->assertRedirect(route('saas.checkout', $subscription->id));
        $this->assertDatabaseHas('companies', [
            'user_id' => $owner->id,
            'domain_prefix' => 'public-business',
        ]);

        $this->get(route('saas.payment.callback', [
            'sub_id' => $subscription->id,
            'gateway' => 'flutterwave',
            'reference' => 'LOCAL-FLW-'.$subscription->id.'-TEST',
        ]));

        $this->assertDatabaseHas('domains', [
            'domain_name' => 'public-business',
            'status' => 'Active',
        ]);
        $this->assertDatabaseHas('companies', [
            'user_id' => $owner->id,
            'domain_prefix' => 'public-business',
            'status' => 'active',
        ]);
        $this->assertDatabaseHas('subscriptions', [
            'id' => $subscription->id,
            'payment_status' => 'paid',
            'status' => 'Active',
        ]);
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
