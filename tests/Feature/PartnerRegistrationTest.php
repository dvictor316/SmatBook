<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Tests\TestCase;

class PartnerRegistrationTest extends TestCase
{
    use WithFaker;

    protected function setUp(): void
    {
        parent::setUp();

        Config::set('database.default', 'sqlite');
        Config::set('database.connections.sqlite', [
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => '',
            'foreign_key_constraints' => true,
        ]);

        DB::purge('sqlite');
        DB::reconnect('sqlite');

        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('email')->nullable()->unique();
            $table->string('phone')->nullable()->unique();
            $table->string('password');
            $table->string('role')->nullable();
            $table->boolean('is_verified')->default(false);
            $table->string('status')->nullable();
            $table->string('country')->nullable();
            $table->string('state_region')->nullable();
            $table->string('local_council')->nullable();
            $table->unsignedBigInteger('state_manager_id')->nullable();
            $table->string('remember_token', 100)->nullable();
            $table->softDeletes();
            $table->timestamps();
        });

        Schema::create('settings', function (Blueprint $table) {
            $table->id();
            $table->string('key')->unique();
            $table->text('value')->nullable();
            $table->timestamps();
        });

        Schema::create('plans', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->decimal('price', 12, 2)->nullable();
            $table->string('billing_cycle')->nullable();
            $table->unsignedInteger('user_limit')->nullable();
            $table->timestamps();
        });

        Schema::create('subscriptions', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('plan_id')->nullable();
            $table->string('plan')->nullable();
            $table->string('plan_name')->nullable();
            $table->string('billing_cycle')->nullable();
            $table->decimal('amount', 12, 2)->nullable();
            $table->unsignedInteger('user_limit')->nullable();
            $table->string('status')->nullable();
            $table->string('payment_status')->nullable();
            $table->timestamps();
        });
    }

    public function test_partner_registration_accepts_a_valid_selected_country(): void
    {
        $response = $this->from(route('saas-register', ['type' => 'partner']))->post(route('saas-register.post'), [
            'role' => 'agent',
            'name' => 'Victor Agent',
            'email' => 'agent@example.com',
            'country' => 'Nigeria',
            'state_region' => 'Lagos',
            'local_council' => 'Ikeja',
            'password' => 'Secure123',
            'password_confirmation' => 'Secure123',
        ]);

        $response->assertRedirect(route('agent.dashboard'));
        $response->assertSessionDoesntHaveErrors(['country', 'state_region', 'local_council']);

        $this->assertAuthenticated();
        $user = User::firstOrFail();
        $this->assertSame('Nigeria', $user->country);
        $this->assertSame('agent', $user->role);
        $this->assertSame('active', $user->status);
        $this->assertSame(1, (int) $user->is_verified);
    }

    public function test_partner_registration_allows_phone_without_email(): void
    {
        $response = $this->from(route('saas-register', ['type' => 'partner']))->post(route('saas-register.post'), [
            'role' => 'agent',
            'name' => 'Phone Only Agent',
            'phone' => '+2348012345678',
            'country' => 'Nigeria',
            'state_region' => 'FCT',
            'local_council' => 'AMAC',
            'password' => 'Secure123',
            'password_confirmation' => 'Secure123',
        ]);

        $response->assertRedirect(route('agent.dashboard'));
        $response->assertSessionDoesntHaveErrors(['email', 'phone', 'country']);

        $this->assertAuthenticated();
        $user = User::firstOrFail();
        $this->assertStringContainsString('@phone.smartprobook.local', $user->email);
        $this->assertSame('agent', $user->role);
        $this->assertSame('active', $user->status);
        $this->assertSame(1, (int) $user->is_verified);
    }

    public function test_regular_registration_waits_for_super_admin_approval(): void
    {
        $response = $this->from(route('saas-register'))->post(route('saas-register.post'), [
            'role' => 'admin',
            'name' => 'Pending Admin',
            'email' => 'pending-admin@example.com',
            'password' => 'Secure123',
            'password_confirmation' => 'Secure123',
            'plan' => 'pro',
            'billing_cycle' => 'monthly',
            'amount' => '19500',
        ]);

        $response->assertRedirect(route('registration.pending.notice'));
        $response->assertSessionDoesntHaveErrors();

        $user = User::firstOrFail();

        $this->assertAuthenticatedAs($user);
        $this->assertSame('pending', $user->status);
        $this->assertSame(0, (int) $user->is_verified);
        $this->assertDatabaseHas('subscriptions', [
            'user_id' => $user->id,
            'status' => 'Trial',
            'payment_status' => 'free',
        ]);
    }
}
