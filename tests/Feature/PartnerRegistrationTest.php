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
            $table->string('domain_prefix')->nullable();
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
            'field_of_operation' => 'general',
        ]);

        $response->assertRedirect(route('registration.pending.notice'));
        $response->assertSessionDoesntHaveErrors();

        $user = User::firstOrFail();

        $this->assertAuthenticatedAs($user);
        $this->assertSame('pending', $user->status);
        $this->assertSame(0, (int) $user->is_verified);
        $this->assertDatabaseHas('subscriptions', [
            'user_id' => $user->id,
            'status' => 'Pending',
            'payment_status' => 'unpaid',
        ]);
    }

    public function test_incomplete_registration_can_be_retried_with_the_same_email_without_duplicate_records(): void
    {
        $payload = [
            'role' => 'admin',
            'name' => 'First Attempt',
            'email' => 'retry-owner@example.com',
            'phone' => '+2348012345678',
            'password' => 'Secure123',
            'password_confirmation' => 'Secure123',
            'plan' => 'pro',
            'billing_cycle' => 'yearly',
            'field_of_operation' => 'general',
        ];

        $this->post(route('saas-register.post'), $payload)
            ->assertRedirect(route('registration.pending.notice'));

        $user = User::where('email', 'retry-owner@example.com')->firstOrFail();
        $payload['name'] = 'Retry Owner';

        auth()->logout();
        $this->post(route('saas-register.post'), $payload)
            ->assertRedirect(route('registration.pending.notice'))
            ->assertSessionDoesntHaveErrors();

        $this->assertSame(1, User::where('email', 'retry-owner@example.com')->count());
        $this->assertSame($user->id, User::where('email', 'retry-owner@example.com')->value('id'));
        $this->assertSame('Retry Owner', $user->fresh()->name);
        $this->assertSame(1, \DB::table('subscriptions')->where('user_id', $user->id)->count());
    }

    public function test_incomplete_phone_only_registration_can_be_retried_with_the_same_phone(): void
    {
        $payload = [
            'role' => 'admin',
            'name' => 'Phone First Attempt',
            'phone' => '+2348012345678',
            'password' => 'Secure123',
            'password_confirmation' => 'Secure123',
            'plan' => 'pro',
            'billing_cycle' => 'yearly',
            'field_of_operation' => 'general',
        ];

        $this->post(route('saas-register.post'), $payload)
            ->assertRedirect(route('registration.pending.notice'));

        $user = User::where('phone', '+2348012345678')->firstOrFail();
        $originalEmail = $user->email;
        $payload['name'] = 'Phone Retry Owner';

        auth()->logout();
        $this->post(route('saas-register.post'), $payload)
            ->assertRedirect(route('registration.pending.notice'))
            ->assertSessionDoesntHaveErrors();

        $this->assertSame(1, User::where('phone', '+2348012345678')->count());
        $this->assertSame($originalEmail, $user->fresh()->email);
        $this->assertSame('Phone Retry Owner', $user->fresh()->name);
        $this->assertSame(1, \DB::table('subscriptions')->where('user_id', $user->id)->count());
    }

    public function test_approved_owner_can_retry_an_unpaid_registration_without_repeating_approval(): void
    {
        $user = User::create([
            'name' => 'Approved Owner',
            'email' => 'approved-retry@example.com',
            'phone' => '+2348012345678',
            'password' => bcrypt('Secure123'),
            'role' => 'admin',
            'status' => 'active',
            'is_verified' => 1,
        ]);
        \DB::table('subscriptions')->insert([
            'user_id' => $user->id,
            'plan' => 'Professional',
            'plan_name' => 'Professional',
            'billing_cycle' => 'Yearly',
            'amount' => 150000,
            'status' => 'Pending',
            'payment_status' => 'unpaid',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $response = $this->post(route('saas-register.post'), [
            'role' => 'admin',
            'name' => 'Approved Owner Updated',
            'email' => 'approved-retry@example.com',
            'phone' => '+2348012345678',
            'password' => 'Secure123',
            'password_confirmation' => 'Secure123',
            'plan' => 'pro',
            'billing_cycle' => 'yearly',
            'field_of_operation' => 'general',
        ]);

        $response->assertRedirect(route('home'));
        $this->assertSame('active', $user->fresh()->status);
        $this->assertSame(1, (int) $user->fresh()->is_verified);
        $this->assertSame('Approved Owner Updated', $user->fresh()->name);
        $this->assertSame(1, \DB::table('subscriptions')->where('user_id', $user->id)->count());
    }

    public function test_successfully_paid_registration_cannot_be_reused(): void
    {
        $user = User::create([
            'name' => 'Paid Owner',
            'email' => 'paid-owner@example.com',
            'phone' => '+2348012345678',
            'password' => bcrypt('Secure123'),
            'role' => 'admin',
            'status' => 'active',
            'is_verified' => 1,
        ]);
        \DB::table('subscriptions')->insert([
            'user_id' => $user->id,
            'plan' => 'Professional',
            'plan_name' => 'Professional',
            'billing_cycle' => 'Yearly',
            'amount' => 150000,
            'status' => 'Active',
            'payment_status' => 'paid',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->from(route('saas-register'))->post(route('saas-register.post'), [
            'role' => 'admin',
            'name' => 'Attempted Duplicate',
            'email' => 'paid-owner@example.com',
            'phone' => '+2348012345678',
            'password' => 'Secure123',
            'password_confirmation' => 'Secure123',
            'plan' => 'pro',
            'billing_cycle' => 'yearly',
            'field_of_operation' => 'general',
        ])->assertRedirect(route('saas-register'));

        $this->assertSame('Paid Owner', $user->fresh()->name);
        $this->assertSame(1, \DB::table('subscriptions')->where('user_id', $user->id)->count());
    }
}
