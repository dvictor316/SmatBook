<?php

namespace Tests\Feature\Auth;

use App\Models\Company;
use App\Models\User;
use App\Services\TwoFactorAuthenticationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use PragmaRX\Google2FA\Google2FA;
use Tests\TestCase;

class TwoFactorAuthenticationTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_enrol_and_secret_is_encrypted_at_rest(): void
    {
        $user = $this->makeUser();

        $this->actingAs($user)
            ->post(route('two-factor.setup'), ['current_password' => 'Password123'])
            ->assertRedirect(route('two-factor'));

        $user->refresh();
        $secret = $user->two_factor_secret;
        $this->assertNotEmpty($secret);
        $this->assertNotSame($secret, DB::table('users')->where('id', $user->id)->value('two_factor_secret'));

        $setupPage = $this->get(route('two-factor'));
        $setupPage->assertOk()->assertSee('Authenticator setup QR code');
        $this->assertStringContainsString('no-store', (string) $setupPage->headers->get('Cache-Control'));

        $code = app(Google2FA::class)->getCurrentOtp($secret);
        $response = $this->withSession(['two_factor_setup_authorized_until' => now()->addMinutes(10)->timestamp])
            ->post(route('two-factor.confirm'), ['code' => $code]);

        $response->assertOk()->assertSee('Save your recovery codes now');
        $user->refresh();
        $this->assertTrue($user->hasTwoFactorAuthenticationEnabled());
        $this->assertCount(8, $user->two_factor_recovery_codes);
        $this->assertTrue(collect($user->two_factor_recovery_codes)->every(fn ($hash) => str_starts_with($hash, '$2y$')));
    }

    public function test_enabled_user_must_complete_challenge_before_login(): void
    {
        $user = $this->makeUser();
        $secret = $this->enableTwoFactor($user);

        $this->post(route('saas-login.post'), [
            'login' => $user->email,
            'password' => 'Password123',
        ])->assertRedirect(route('two-factor.challenge'))
            ->assertSessionHas('two_factor_login_user_id', $user->id);

        $this->assertGuest();

        $this->post(route('two-factor.challenge.verify'), [
            'code' => app(Google2FA::class)->getCurrentOtp($secret),
        ])->assertRedirect();

        $this->assertAuthenticatedAs($user);
    }

    public function test_recovery_code_is_consumed_after_one_login(): void
    {
        $user = $this->makeUser();
        $this->enableTwoFactor($user);
        $recoveryCode = 'alpha-bravo';
        $user->forceFill(['two_factor_recovery_codes' => [Hash::make('alphabravo')]])->save();

        $this->post(route('saas-login.post'), [
            'login' => $user->email,
            'password' => 'Password123',
        ])->assertRedirect(route('two-factor.challenge'));

        $this->post(route('two-factor.challenge.verify'), ['code' => $recoveryCode])->assertRedirect();

        $this->assertAuthenticatedAs($user);
        $this->assertSame([], $user->fresh()->two_factor_recovery_codes);
    }

    public function test_used_totp_cannot_be_replayed(): void
    {
        $user = $this->makeUser();
        $secret = $this->enableTwoFactor($user);
        $code = app(Google2FA::class)->getCurrentOtp($secret);
        $service = app(TwoFactorAuthenticationService::class);

        $this->assertTrue($service->verifyTotp($user->fresh(), $code));
        $this->assertFalse($service->verifyTotp($user->fresh(), $code));
    }

    public function test_disabling_two_factor_requires_password_and_second_factor(): void
    {
        $user = $this->makeUser();
        $this->enableTwoFactor($user);
        $user->forceFill(['two_factor_recovery_codes' => [Hash::make('disablecode')]])->save();

        $this->actingAs($user)->delete(route('two-factor.disable'), [
            'current_password' => 'wrong-password',
            'code' => 'disable-code',
        ])->assertSessionHasErrors('current_password');

        $this->delete(route('two-factor.disable'), [
            'current_password' => 'Password123',
            'code' => 'disable-code',
        ])->assertRedirect(route('two-factor'));

        $user->refresh();
        $this->assertFalse($user->hasTwoFactorAuthenticationEnabled());
        $this->assertNull($user->two_factor_secret);
        $this->assertNull($user->two_factor_recovery_codes);
    }

    private function makeUser(): User
    {
        $company = Company::create([
            'name' => 'Secure Ledger Limited',
            'email' => 'office@example.test',
            'status' => 'active',
            'country' => 'Nigeria',
            'currency_code' => 'NGN',
        ]);

        $user = User::factory()->create([
            'name' => 'Security Test User',
            'email' => fake()->unique()->safeEmail(),
            'password' => Hash::make('Password123'),
            'role' => 'admin',
            'company_id' => $company->id,
            'allow_login' => 1,
        ]);

        $company->forceFill(['user_id' => $user->id, 'owner_id' => $user->id])->save();
        return $user;
    }

    private function enableTwoFactor(User $user): string
    {
        $secret = app(TwoFactorAuthenticationService::class)->generateSecret();
        $user->forceFill([
            'two_factor_secret' => $secret,
            'two_factor_confirmed_at' => now(),
            'two_factor_last_used_timestep' => null,
            'two_factor_recovery_codes' => [],
        ])->save();

        return $secret;
    }
}
