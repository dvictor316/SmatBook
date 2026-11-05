<?php

namespace Tests\Feature;

use App\Mail\EmailVerificationCodeMail;
use App\Models\User;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Tests\TestCase;

class PartnerRegistrationTest extends TestCase
{
    use WithFaker;

    protected function setUp(): void
    {
        parent::setUp();
        Mail::fake();

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
            $table->timestamp('email_verified_at')->nullable();
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

        Schema::create('email_verification_challenges', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id')->unique();
            $table->string('email');
            $table->string('code_hash');
            $table->timestamp('expires_at');
            $table->timestamp('sent_at')->nullable();
            $table->unsignedTinyInteger('attempts')->default(0);
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
            $table->dateTime('start_date')->nullable();
            $table->dateTime('end_date')->nullable();
            $table->dateTime('initialized_at')->nullable();
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

        $response->assertRedirect(route('email-verification.notice'));
        $response->assertSessionDoesntHaveErrors(['country', 'state_region', 'local_council']);

        $this->assertGuest();
        $user = User::firstOrFail();
        $this->assertSame('Nigeria', $user->country);
        $this->assertSame('agent', $user->role);
        $this->assertSame('active', $user->status);
        $this->assertSame(1, (int) $user->is_verified);
        $this->assertNull($user->email_verified_at);
        Mail::assertSent(EmailVerificationCodeMail::class, fn ($mail) => $mail->hasTo('agent@example.com'));
    }

    public function test_partner_registration_requires_a_real_email_address(): void
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

        $response->assertRedirect(route('saas-register', ['type' => 'partner']));
        $response->assertSessionHasErrors('email');
        $this->assertSame(0, User::count());
    }

    public function test_regular_registration_starts_a_one_month_trial_immediately(): void
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

        $subscription = \DB::table('subscriptions')->first();
        $this->assertNotNull($subscription);
        $response->assertRedirect(route('email-verification.notice'));
        $response->assertSessionDoesntHaveErrors();

        $user = User::firstOrFail();

        $this->assertGuest();
        $this->assertNull($user->email_verified_at);
        $this->assertSame('active', $user->status);
        $this->assertSame(1, (int) $user->is_verified);
        $this->assertDatabaseHas('subscriptions', [
            'user_id' => $user->id,
            'status' => 'Trial',
            'payment_status' => 'free',
        ]);
        $this->assertTrue(\Illuminate\Support\Carbon::parse($subscription->end_date)->isSameDay(now()->addMonth()));

        $mail = Mail::sent(EmailVerificationCodeMail::class)->first();
        $this->assertNotNull($mail);
        $incorrectCode = $mail->code === '000000' ? '000001' : '000000';
        $this->from(route('email-verification.notice'))
            ->post(route('email-verification.verify'), ['code' => $incorrectCode])
            ->assertRedirect(route('email-verification.notice'))
            ->assertSessionHasErrors('code');
        $this->assertGuest();
        $this->assertSame(1, (int) \DB::table('email_verification_challenges')->where('user_id', $user->id)->value('attempts'));

        $this->post(route('email-verification.verify'), ['code' => $mail->code])
            ->assertRedirect(route('saas.setup', $subscription->id));
        $this->assertAuthenticatedAs($user->fresh());
        $this->assertNotNull($user->fresh()->email_verified_at);
        $this->assertDatabaseMissing('email_verification_challenges', ['user_id' => $user->id]);
    }

    public function test_duplicate_registration_cannot_restart_a_trial_or_create_duplicate_records(): void
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

        $this->from(route('saas-register'))->post(route('saas-register.post'), $payload)
            ->assertRedirect(route('email-verification.notice'));

        $user = User::where('email', 'retry-owner@example.com')->firstOrFail();
        $originalEndDate = \DB::table('subscriptions')->where('user_id', $user->id)->value('end_date');

        auth()->logout();
        $this->from(route('saas-register'))->post(route('saas-register.post'), $payload)
            ->assertRedirect(route('saas-register'))
            ->assertSessionDoesntHaveErrors();

        $this->assertSame(1, User::where('email', 'retry-owner@example.com')->count());
        $this->assertSame($user->id, User::where('email', 'retry-owner@example.com')->value('id'));
        $this->assertSame(1, \DB::table('subscriptions')->where('user_id', $user->id)->count());
        $this->assertSame($originalEndDate, \DB::table('subscriptions')->where('user_id', $user->id)->value('end_date'));
        Mail::assertSent(EmailVerificationCodeMail::class, 1);
    }

    public function test_duplicate_phone_registration_cannot_restart_a_trial(): void
    {
        $payload = [
            'role' => 'admin',
            'name' => 'Phone First Attempt',
            'email' => 'phone-first@example.com',
            'phone' => '+2348012345678',
            'password' => 'Secure123',
            'password_confirmation' => 'Secure123',
            'plan' => 'pro',
            'billing_cycle' => 'yearly',
            'field_of_operation' => 'general',
        ];

        $this->from(route('saas-register'))->post(route('saas-register.post'), $payload)
            ->assertRedirect(route('email-verification.notice'));

        $user = User::where('phone', '+2348012345678')->firstOrFail();
        $originalEmail = $user->email;

        $payload['email'] = 'phone-second@example.com';
        auth()->logout();
        $this->from(route('saas-register'))->post(route('saas-register.post'), $payload)
            ->assertRedirect(route('saas-register'))
            ->assertSessionHasErrors('phone');

        $this->assertSame(1, User::where('phone', '+2348012345678')->count());
        $this->assertSame($originalEmail, $user->fresh()->email);
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

        $subscription = \DB::table('subscriptions')->where('user_id', $user->id)->first();
        $response->assertRedirect(route('email-verification.notice'));
        $this->assertSame('active', $user->fresh()->status);
        $this->assertSame(1, (int) $user->fresh()->is_verified);
        $this->assertSame('Approved Owner Updated', $user->fresh()->name);
        $this->assertSame(1, \DB::table('subscriptions')->where('user_id', $user->id)->count());
        $this->assertSame('Trial', $subscription->status);
        $this->assertSame('free', $subscription->payment_status);
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
