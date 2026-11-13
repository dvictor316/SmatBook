<?php

namespace Tests\Feature\Auth;

use App\Mail\EmailVerificationCodeMail;
use App\Models\Company;
use App\Models\Subscription;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Hash;
use App\Services\TwoFactorAuthenticationService;
use App\Services\EmailVerificationService;
use Laravel\Socialite\Facades\Socialite;
use Laravel\Socialite\Two\User as SocialiteUser;
use Tests\TestCase;

class SocialAuthenticationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Mail::fake();
        foreach (['google', 'facebook', 'linkedin-openid'] as $provider) {
            Config::set("services.$provider", [
                'client_id' => 'test-client',
                'client_secret' => 'test-secret',
                'redirect' => 'http://localhost/auth/'.($provider === 'linkedin-openid' ? 'linkedin' : $provider).'/callback',
            ]);
        }
    }

    public static function providers(): array
    {
        return [['google', 'google'], ['facebook', 'facebook'], ['linkedin', 'linkedin-openid']];
    }

    /** @dataProvider providers */
    public function test_new_social_signup_collects_business_details_and_verifies_email(string $provider, string $driver): void
    {
        $this->fakeProvider($driver, 'new-'.$provider, 'new-'.$provider.'@example.test');
        $this->get(route('social.login', ['provider' => $provider, 'intent' => 'register', 'plan' => 'pro']))
            ->assertRedirect();
        $this->get(route('social.callback', $provider))->assertRedirect(route('saas-register', ['plan' => 'pro']));
        $this->assertDatabaseMissing('users', ['email' => 'new-'.$provider.'@example.test']);

        $this->get(route('saas-register', ['plan' => 'pro']))
            ->assertOk()->assertSee('no SmartProbook password is required')
            ->assertDontSee('name="password"', false);
        $this->post(route('saas-register.post'), [
            'name' => 'Social Owner',
            'email' => 'tampered@example.test',
            'role' => 'admin',
            'plan' => 'pro',
            'billing_cycle' => 'yearly',
            'field_of_operation' => 'general',
        ])->assertRedirect(route('email-verification.notice'));
        $user = User::where('email', 'new-'.$provider.'@example.test')->firstOrFail();
        $this->assertSame('new-'.$provider, $user->{$provider.'_id'});
        $this->assertGuest();
        $trial = Subscription::where('user_id', $user->id)->firstOrFail();
        $this->assertTrue($trial->isTrial());
        $mail = Mail::sent(EmailVerificationCodeMail::class)->first();
        $this->assertNotNull($mail);
        $this->post(route('email-verification.verify'), ['code' => $mail->code])
            ->assertRedirect(route('saas.setup', $trial->id));
        $this->assertAuthenticatedAs($user);
    }

    /** @dataProvider providers */
    public function test_existing_email_is_connected_only_after_email_code(string $provider, string $driver): void
    {
        $user = $this->owner('existing-'.$provider.'@example.test');
        $this->fakeProvider($driver, 'existing-'.$provider, $user->email);
        $this->get(route('social.login', ['provider' => $provider, 'intent' => 'login']))->assertRedirect();
        $this->get(route('social.callback', $provider))->assertRedirect(route('email-verification.notice'));
        $this->assertGuest();
        $this->assertNull($user->fresh()->{$provider.'_id'});
        $mail = Mail::sent(EmailVerificationCodeMail::class)->first();
        $this->assertNotNull($mail);
        $wrongCode = $mail->code === '000000' ? '000001' : '000000';
        $this->assertTrue($mail->sameSession);
        $this->post(route('email-verification.verify'), ['code' => $wrongCode])->assertSessionHasErrors('code');
        $this->assertNull($user->fresh()->{$provider.'_id'});
        $this->post(route('email-verification.verify'), ['code' => $mail->code])->assertRedirect(route('home'));
        $this->assertSame('existing-'.$provider, $user->fresh()->{$provider.'_id'});
        $this->assertAuthenticatedAs($user);
    }

    public function test_linked_social_account_respects_disabled_login(): void
    {
        $user = $this->owner('disabled@example.test');
        $user->update(['google_id' => 'disabled-google', 'allow_login' => 0]);
        $this->fakeProvider('google', 'disabled-google', $user->email);
        $this->get(route('social.login', 'google'))->assertRedirect();
        $this->get(route('social.callback', 'google'))
            ->assertRedirect(route('saas-login'))->assertSessionHasErrors('login');
        $this->assertGuest();
    }

    public function test_pending_social_connection_does_not_block_normal_password_login(): void
    {
        $user = $this->owner('password-login@example.test');
        $this->fakeProvider('google', 'pending-google', $user->email);
        $this->get(route('social.login', 'google'))->assertRedirect();
        $this->get(route('social.callback', 'google'))->assertRedirect(route('email-verification.notice'));
        $this->post(route('saas-login.post'), ['login' => $user->email, 'password' => 'Password123'])
            ->assertRedirect(route('home'));
        $this->assertAuthenticatedAs($user);
        $this->assertNull($user->fresh()->google_id);
    }

    public function test_social_registration_verification_cannot_be_bypassed_when_optional_verification_is_disabled(): void
    {
        Setting::updateOrCreate(['key' => 'saas_email_verification'], ['value' => '0']);
        $this->fakeProvider('google', 'pending-registration', 'pending-registration@example.test');
        $this->get(route('social.login', ['provider' => 'google', 'intent' => 'register', 'plan' => 'pro']))->assertRedirect();
        $this->get(route('social.callback', 'google'))->assertRedirect();
        $this->get(route('saas-register', ['plan' => 'pro']))->assertOk();
        $this->post(route('saas-register.post'), [
            'name' => 'Pending Owner',
            'plan' => 'pro',
            'billing_cycle' => 'yearly',
            'field_of_operation' => 'general',
        ])->assertRedirect(route('email-verification.notice'));
        $this->get(route('social.login', 'google'))->assertRedirect();
        $this->get(route('social.callback', 'google'))->assertRedirect(route('email-verification.notice'));
        $this->assertGuest();
    }

    public function test_partner_social_signup_keeps_the_agent_role_and_location_requirements(): void
    {
        $this->get(route('saas-register', ['type' => 'partner']))
            ->assertOk()->assertSee('auth/google', false);
        $this->fakeProvider('google', 'partner-google', 'partner@example.test');
        $this->get(route('social.login', ['provider' => 'google', 'intent' => 'register', 'type' => 'partner']))
            ->assertRedirect();
        $this->get(route('social.callback', 'google'))
            ->assertRedirect(route('saas-register', ['type' => 'partner']));
        $this->get(route('saas-register', ['type' => 'partner']))
            ->assertOk()->assertSee('partner agent profile')->assertDontSee('name="password"', false);
        $this->post(route('saas-register.post'), [
            'name' => 'Partner User',
            'role' => 'admin',
            'country' => 'Nigeria',
            'state_region' => 'Lagos',
            'local_council' => 'Ikeja',
        ])->assertRedirect(route('email-verification.notice'));
        $user = User::where('email', 'partner@example.test')->firstOrFail();
        $this->assertSame('agent', $user->role);
        $this->assertSame('Lagos', $user->state_region);
        $this->assertSame('partner-google', $user->google_id);
        $this->assertDatabaseMissing('subscriptions', ['user_id' => $user->id]);
        $mail = Mail::sent(EmailVerificationCodeMail::class)->first();
        $this->post(route('email-verification.verify'), ['code' => $mail->code])
            ->assertRedirect(route('agent.dashboard'));
    }

    public function test_linking_existing_account_still_requires_two_factor_authentication(): void
    {
        $user = $this->owner('two-factor@example.test');
        $user->forceFill([
            'two_factor_secret' => app(TwoFactorAuthenticationService::class)->generateSecret(),
            'two_factor_confirmed_at' => now(),
            'two_factor_recovery_codes' => [],
        ])->save();
        $this->fakeProvider('google', 'two-factor-google', $user->email);
        $this->get(route('social.login', 'google'))->assertRedirect();
        $this->get(route('social.callback', 'google'))->assertRedirect(route('email-verification.notice'));
        $mail = Mail::sent(EmailVerificationCodeMail::class)->first();
        $this->post(route('email-verification.verify'), ['code' => $mail->code])
            ->assertRedirect(route('two-factor.challenge'))
            ->assertSessionHas('two_factor_login_user_id', $user->id);
        $this->assertGuest();
    }

    public function test_linking_code_also_finishes_an_existing_email_verification(): void
    {
        $user = $this->owner('pending-link@example.test');
        $user->forceFill(['email_verified_at' => null])->save();
        $this->assertSame('sent', app(EmailVerificationService::class)->sendCode($user, true));
        $this->fakeProvider('google', 'pending-link-google', $user->email);
        $this->get(route('social.login', 'google'))->assertRedirect();
        $this->get(route('social.callback', 'google'))->assertRedirect(route('email-verification.notice'));
        $mail = Mail::sent(EmailVerificationCodeMail::class)->last();
        $this->assertTrue($mail->sameSession);
        $this->post(route('email-verification.verify'), ['code' => $mail->code])->assertRedirect(route('home'));
        $this->assertNotNull($user->fresh()->email_verified_at);
        $this->assertDatabaseMissing('email_verification_challenges', ['user_id' => $user->id]);
    }

    public function test_oauth_callback_rejects_invalid_state(): void
    {
        $this->withSession(['state' => 'expected-state'])
            ->get(route('social.callback', ['provider' => 'google', 'state' => 'wrong-state', 'code' => 'unused']))
            ->assertRedirect(route('saas-login'))
            ->assertSessionHas('error', 'Google login session expired. Please retry.');
        $this->assertGuest();
    }

    public function test_missing_provider_email_does_not_create_an_account(): void
    {
        $this->fakeProvider('google', 'no-email', '');
        $this->get(route('social.login', 'google'))->assertRedirect();
        $this->get(route('social.callback', 'google'))
            ->assertRedirect(route('saas-login'))->assertSessionHasErrors('login');
        $this->assertDatabaseMissing('users', ['google_id' => 'no-email']);
        $this->assertGuest();
    }

    /** @dataProvider providers */
    public function test_provider_redirect_includes_oauth_state(string $provider, string $driver): void
    {
        $response = $this->get(route('social.login', $provider))->assertRedirect();
        parse_str((string) parse_url($response->headers->get('Location'), PHP_URL_QUERY), $query);
        $this->assertNotEmpty($query['state'] ?? null);
        $response->assertSessionHas('state', $query['state']);
    }

    public function test_user_can_cancel_social_signup_and_use_the_normal_form(): void
    {
        $this->fakeProvider('google', 'cancel-google', 'cancel@example.test');
        $this->get(route('social.login', ['provider' => 'google', 'intent' => 'register', 'plan' => 'pro']))->assertRedirect();
        $this->get(route('social.callback', 'google'))->assertRedirect();
        $this->post(route('social.register.cancel'))->assertRedirect(route('saas-register', ['plan' => 'pro']));
        $this->get(route('saas-register', ['plan' => 'pro']))
            ->assertOk()->assertSee('name="password"', false)->assertDontSee('no SmartProbook password is required');
    }

    private function fakeProvider(string $driver, string $id, string $email): void
    {
        Socialite::fake($driver, (new SocialiteUser())->map([
            'id' => $id,
            'name' => 'Social User',
            'email' => $email,
            'avatar' => null,
        ]));
    }

    private function owner(string $email): User
    {
        $company = Company::create(['name' => 'Social Company', 'status' => 'active']);
        $user = User::factory()->create(['email' => $email, 'password' => Hash::make('Password123'), 'role' => 'admin', 'company_id' => $company->id, 'allow_login' => 1]);
        $company->forceFill(['user_id' => $user->id, 'owner_id' => $user->id])->save();
        return $user;
    }
}
