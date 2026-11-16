<?php

namespace Tests\Feature;

use App\Mail\RegistrationEmailApprovalMail;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class RegistrationEmailApprovalTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Mail::fake();
    }

    private function selfPayload(string $email): array
    {
        return [
            'role' => 'admin',
            'name' => 'Business Owner',
            'email' => $email,
            'password' => 'ClientPass123',
            'password_confirmation' => 'ClientPass123',
            'plan' => 'pro',
            'billing_cycle' => 'yearly',
            'field_of_operation' => 'general',
        ];
    }

    private function assistedPayload(string $email): array
    {
        return [
            'company_name' => 'Customer Business',
            'subdomain' => 'customer-business',
            'company_email' => $email,
            'industry' => 'retail',
            'plan_id' => 'starter-yearly',
            'plan_name' => 'Starter',
            'plan_price' => 20000,
            'billing_cycle' => 'yearly',
            'registration_type' => 'trial',
            'name' => 'Business Owner',
            'email' => $email,
            'password' => 'ClientPass123',
            'password_confirmation' => 'ClientPass123',
        ];
    }

    private function sendCode(string $email, string $purpose): RegistrationEmailApprovalMail
    {
        $this->postJson(route('registration-verification.send'), compact('email', 'purpose'))->assertOk();
        return Mail::sent(RegistrationEmailApprovalMail::class)->last();
    }

    private function token(RegistrationEmailApprovalMail $mail): string
    {
        return basename(parse_url($mail->verificationUrl, PHP_URL_PATH));
    }

    public function test_self_registration_requires_a_verified_matching_email_and_consumes_approval(): void
    {
        $email = 'owner@example.test';
        $this->post(route('saas-register.post'), $this->selfPayload($email))->assertSessionHasErrors('email');
        $this->assertArrayNotHasKey('password', session('_old_input', []));
        $this->assertArrayNotHasKey('password_confirmation', session('_old_input', []));
        $this->assertDatabaseMissing('users', ['email' => $email]);
        $this->assertDatabaseCount('subscriptions', 0);

        $mail = $this->sendCode($email, 'self');
        $token = $this->token($mail);
        $incorrect = $mail->code === '000000' ? '000001' : '000000';
        $this->post(route('registration-verification.verify', $token), ['code' => $incorrect])->assertSessionHasErrors('code');
        $this->post(route('saas-register.post'), $this->selfPayload($email))->assertSessionHasErrors('email');
        $this->assertDatabaseMissing('users', ['email' => $email]);

        $this->post(route('registration-verification.verify', $token), ['code' => $mail->code])->assertOk();
        $this->post(route('saas-register.post'), $this->selfPayload('other@example.test'))->assertSessionHasErrors('email');
        $this->assertDatabaseMissing('users', ['email' => 'other@example.test']);
        $this->post(route('saas-register.post'), $this->selfPayload($email))->assertSessionHasNoErrors();
        $this->assertDatabaseHas('users', ['email' => $email]);
        $this->assertDatabaseMissing('registration_email_approvals', ['email' => $email]);
        $this->assertDatabaseCount('subscriptions', 1);
        $this->post(route('registration-verification.verify', $token), ['code' => $mail->code])
            ->assertSessionHasErrors('code');
    }

    public function test_assisted_approval_works_on_another_session_and_is_bound_to_the_agent(): void
    {
        $email = 'assisted@example.test';
        $agent = User::factory()->create(['role' => 'agent', 'is_verified' => 1, 'status' => 'active']);
        $otherAgent = User::factory()->create(['role' => 'agent', 'is_verified' => 1, 'status' => 'active']);
        $this->actingAs($agent)->post(route('agent.registration.store'), $this->assistedPayload($email))
            ->assertSessionHasErrors('email');
        $this->assertDatabaseMissing('users', ['email' => $email]);
        $this->assertDatabaseCount('subscriptions', 0);

        $mail = $this->sendCode($email, 'assisted');
        $proof = session('registration_email_approval');
        $this->withSession(['registration_email_approval' => null])
            ->post(route('registration-verification.verify', $this->token($mail)), ['code' => $mail->code])
            ->assertOk();
        $this->actingAs($agent)->post(route('agent.registration.store'), $this->assistedPayload($email))
            ->assertSessionHasErrors('email');
        $this->actingAs($otherAgent)->withSession(['registration_email_approval' => $proof])
            ->post(route('agent.registration.store'), $this->assistedPayload($email))
            ->assertSessionHasErrors('email');
        $this->assertDatabaseMissing('users', ['email' => $email]);

        $this->actingAs($agent)->withSession(['registration_email_approval' => $proof])
            ->post(route('agent.registration.store'), $this->assistedPayload($email))
            ->assertSessionHasNoErrors();
        $this->assertDatabaseHas('users', ['email' => $email]);
        $this->assertDatabaseMissing('registration_email_approvals', ['email' => $email]);
        $this->assertDatabaseCount('subscriptions', 1);
    }

    public function test_attempt_limit_cooldown_and_five_minute_expiry(): void
    {
        $email = 'expiry@example.test';
        $mail = $this->sendCode($email, 'self');
        $token = $this->token($mail);
        $incorrect = $mail->code === '000000' ? '000001' : '000000';
        for ($i = 0; $i < 5; $i++) {
            $this->post(route('registration-verification.verify', $token), ['code' => $incorrect])
                ->assertSessionHasErrors('code');
        }
        $this->post(route('registration-verification.verify', $token), ['code' => $mail->code])
            ->assertSessionHasErrors('code');
        $this->postJson(route('registration-verification.send'), ['email' => $email, 'purpose' => 'self'])
            ->assertUnprocessable();
        $this->travel(61)->seconds();
        $mail = $this->sendCode($email, 'self');
        $this->post(route('registration-verification.verify', $token), ['code' => $mail->code])
            ->assertSessionHasErrors('code');
        $this->travel(301)->seconds();
        $this->post(route('registration-verification.verify', $this->token($mail)), ['code' => $mail->code])
            ->assertSessionHasErrors('code');
        $this->post(route('saas-register.post'), $this->selfPayload($email))->assertSessionHasErrors('email');
        $this->assertDatabaseMissing('users', ['email' => $email]);
    }

    public function test_delivery_failure_does_not_allow_registration(): void
    {
        Mail::shouldReceive('mailer')->andThrow(new \RuntimeException('Mail unavailable'));
        $email = 'delivery-failure@example.test';
        $this->postJson(route('registration-verification.send'), ['email' => $email, 'purpose' => 'self'])
            ->assertUnprocessable();
        $this->assertDatabaseMissing('registration_email_approvals', ['email' => $email]);
        $this->post(route('saas-register.post'), $this->selfPayload($email))->assertSessionHasErrors('email');
        $this->assertDatabaseMissing('users', ['email' => $email]);
    }

    public function test_approval_expires_if_registration_is_not_completed_in_five_minutes(): void
    {
        $email = 'approved-expiry@example.test';
        $mail = $this->sendCode($email, 'self');
        $this->post(route('registration-verification.verify', $this->token($mail)), ['code' => $mail->code])
            ->assertOk();
        $this->travel(301)->seconds();
        $this->post(route('saas-register.post'), $this->selfPayload($email))->assertSessionHasErrors('email');
        $this->assertDatabaseMissing('users', ['email' => $email]);
    }

    public function test_verification_links_use_the_configured_application_host(): void
    {
        config(['app.url' => 'https://smartprobook.example']);
        $this->withoutMiddleware(\App\Http\Middleware\IdentifyTenant::class)
            ->withServerVariables(['HTTP_HOST' => 'untrusted.example']);
        $mail = $this->sendCode('canonical@example.test', 'self');
        $this->assertStringStartsWith('https://smartprobook.example/registration/email/', $mail->verificationUrl);
    }

    public function test_assisted_approval_accepts_an_agent_assigned_through_the_roles_table(): void
    {
        $roleId = \DB::table('roles')->insertGetId(['name' => 'agent', 'created_at' => now(), 'updated_at' => now()]);
        $agent = User::factory()->create(['role' => 'admin', 'role_id' => $roleId]);
        $this->actingAs($agent);
        $this->sendCode('role-agent@example.test', 'assisted');
    }
}
