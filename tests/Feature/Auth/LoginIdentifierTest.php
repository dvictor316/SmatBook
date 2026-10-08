<?php

namespace Tests\Feature\Auth;

use App\Models\Company;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
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
            ->assertSee('Sign In');

        $this->get(route('saas-register'))
            ->assertOk()
            ->assertSee('Email Address (or use phone)')
            ->assertSee('Phone Number (or use email)')
            ->assertSee('Create Account');

        $user = $this->makeUser();
        $this->actingAs($user)
            ->get(route('logout'))
            ->assertRedirect(route('login'))
            ->assertSessionHas('success', 'Logout successful.');
        $this->assertGuest();
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
