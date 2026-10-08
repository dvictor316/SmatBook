<?php

namespace Tests\Feature\Auth;

use App\Models\Company;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Hash;
use Laravel\Socialite\Facades\Socialite;
use Laravel\Socialite\Two\User as SocialiteUser;
use Tests\TestCase;

class LoginIdentifierTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_login_with_email_or_formatted_phone_number(): void
    {
        $user = $this->makeUser();

        $this->post(route('saas-login.post'), [
            'login' => 'owner@example.test',
            'password' => 'Password123',
        ])->assertRedirect();
        $this->assertAuthenticatedAs($user);

        $this->get(route('logout'))->assertRedirect(route('login'));
        $this->assertGuest();

        $this->post(route('saas-login.post'), [
            'login' => '+234 801-234-5678',
            'password' => 'Password123',
        ])->assertRedirect();
        $this->assertAuthenticatedAs($user);
    }

    public function test_authentication_pages_present_clear_email_phone_and_logout_states(): void
    {
        $this->get(route('saas-login'))
            ->assertOk()
            ->assertSee('Email or Phone Number')
            ->assertSee('Sign In')
            ->assertSee('LinkedIn');

        $this->get(route('saas-register'))
            ->assertOk()
            ->assertSee('Email Address (or use phone)')
            ->assertSee('Phone Number (or use email)')
            ->assertSee('Create Account')
            ->assertSee('LinkedIn');

        $user = $this->makeUser();
        $this->actingAs($user)
            ->get(route('logout'))
            ->assertRedirect(route('login'))
            ->assertSessionHas('success', 'Logout successful.');
        $this->assertGuest();
    }

    public function test_linkedin_openid_can_create_a_verified_social_account(): void
    {
        Config::set('services.linkedin-openid', [
            'client_id' => 'linkedin-client-id',
            'client_secret' => 'linkedin-client-secret',
            'redirect' => 'http://localhost/auth/linkedin/callback',
        ]);

        $socialUser = (new SocialiteUser())->map([
            'id' => 'linkedin-member-123',
            'name' => 'LinkedIn Member',
            'email' => 'linkedin.member@example.test',
            'avatar' => null,
        ]);
        Socialite::fake('linkedin-openid', $socialUser);

        $this->get(route('social.login', ['provider' => 'linkedin']))
            ->assertRedirect('https://socialite.fake/linkedin-openid/authorize');

        $this->get(route('social.callback', ['provider' => 'linkedin']))
            ->assertRedirect(route('home'));

        $this->assertDatabaseHas('users', [
            'email' => 'linkedin.member@example.test',
            'linkedin_id' => 'linkedin-member-123',
            'provider_name' => 'linkedin',
            'is_verified' => 1,
        ]);
        $this->assertAuthenticatedAs(User::where('email', 'linkedin.member@example.test')->firstOrFail());
    }

    private function makeUser(): User
    {
        $company = Company::create([
            'name' => 'Identifier Test Company',
            'email' => 'company@example.test',
            'status' => 'active',
            'country' => 'Nigeria',
            'currency_code' => 'NGN',
        ]);

        $user = User::factory()->create([
            'name' => 'Business Owner',
            'email' => 'owner@example.test',
            'phone' => '+2348012345678',
            'password' => Hash::make('Password123'),
            'role' => 'admin',
            'company_id' => $company->id,
            'allow_login' => 1,
        ]);

        $company->forceFill(['user_id' => $user->id, 'owner_id' => $user->id])->save();
        return $user;
    }
}
