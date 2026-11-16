<?php

namespace Tests\Feature;

use App\Mail\EmailVerificationCodeMail;
use App\Mail\RegistrationEmailApprovalMail;
use App\Http\Middleware\RequireRegistrationEmailVerification;
use App\Models\User;
use App\Services\EmailVerificationService;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Http\Request;
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
            $table->string('email')->nullable();
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
        DB::statement('CREATE UNIQUE INDEX users_email_unique ON users (email) WHERE deleted_at IS NULL');

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
        (require database_path('migrations/2026_11_16_000001_create_registration_email_approvals_table.php'))->up();

        Schema::create('settings', function (Blueprint $table) {
            $table->id();
            $table->string('key')->unique();
            $table->text('value')->nullable();
            $table->timestamps();
        });
        DB::table('settings')->insert([
            'key' => 'saas_email_verification',
            'value' => '1',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

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

    private function approveOwnerEmail(string $email): void
    {
        $this->postJson(route('registration-verification.send'), ['email' => $email, 'purpose' => 'self'])->assertOk();
        $mail = Mail::sent(RegistrationEmailApprovalMail::class)->last();
        $this->post(route('registration-verification.verify', basename(parse_url($mail->verificationUrl, PHP_URL_PATH))), [
            'code' => $mail->code,
        ])->assertOk();
    }

    public function test_email_verification_setting_is_loaded_once_per_request(): void
    {
        $settingQueries = 0;
        DB::listen(static function ($query) use (&$settingQueries): void {
            if (str_contains(strtolower($query->sql), 'from "settings"')) {
                $settingQueries++;
            }
        });

        $verification = app(EmailVerificationService::class);

        $this->assertTrue($verification->isEnabled());
        $this->assertTrue($verification->isEnabled());
        $this->assertSame(1, $settingQueries);
    }

    public function test_verified_user_skips_registration_verification_database_lookups(): void
    {
        $user = User::factory()->create(['email_verified_at' => now()]);
        $queries = [];
        DB::listen(static function ($query) use (&$queries): void {
            $queries[] = strtolower($query->sql);
        });
        $request = Request::create('/dashboard', 'GET');
        $request->setUserResolver(static fn () => $user);

        $response = app(RequireRegistrationEmailVerification::class)->handle(
            $request,
            static fn () => response('ok')
        );

        $this->assertSame('ok', $response->getContent());
        $this->assertFalse(collect($queries)->contains(fn (string $sql) => str_contains($sql, 'email_verification_challenges')));
        $this->assertFalse(collect($queries)->contains(fn (string $sql) => str_contains($sql, '"settings"')));
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
        $this->approveOwnerEmail('pending-admin@example.com');
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
        $response->assertRedirect(route('saas.setup', $subscription->id));
        $response->assertSessionDoesntHaveErrors();

        $user = User::firstOrFail();

        $this->assertAuthenticatedAs($user);
        $this->assertNotNull($user->email_verified_at);
        $this->assertSame('active', $user->status);
        $this->assertSame(1, (int) $user->is_verified);
        $this->assertDatabaseHas('subscriptions', [
            'user_id' => $user->id,
            'status' => 'Trial',
            'payment_status' => 'free',
        ]);
        $this->assertTrue(\Illuminate\Support\Carbon::parse($subscription->end_date)->isSameDay(now()->addMonth()));

        $this->assertDatabaseMissing('registration_email_approvals', ['email' => $user->email]);
    }

    public function test_new_business_registration_still_requires_a_code_when_optional_verification_is_disabled(): void
    {
        DB::table('settings')->where('key', 'saas_email_verification')->update(['value' => '0']);

        $response = $this->from(route('saas-register'))->post(route('saas-register.post'), [
            'role' => 'admin',
            'name' => 'No Code Admin',
            'email' => 'no-code-admin@example.com',
            'password' => 'Secure123',
            'password_confirmation' => 'Secure123',
            'plan' => 'pro',
            'billing_cycle' => 'monthly',
            'amount' => '19500',
            'field_of_operation' => 'general',
        ]);

        $response->assertSessionHasErrors('email');
        $this->assertDatabaseMissing('users', ['email' => 'no-code-admin@example.com']);
        $this->approveOwnerEmail('no-code-admin@example.com');
        $response = $this->post(route('saas-register.post'), [
            'role' => 'admin', 'name' => 'No Code Admin', 'email' => 'no-code-admin@example.com',
            'password' => 'Secure123', 'password_confirmation' => 'Secure123',
            'plan' => 'pro', 'billing_cycle' => 'monthly', 'field_of_operation' => 'general',
        ]);
        $user = User::where('email', 'no-code-admin@example.com')->firstOrFail();
        $subscriptionId = DB::table('subscriptions')->where('user_id', $user->id)->value('id');

        $response->assertRedirect(route('saas.setup', $subscriptionId));
        $this->assertAuthenticatedAs($user);
        $this->assertDatabaseMissing('email_verification_challenges', ['user_id' => $user->id]);
        Mail::assertNotSent(EmailVerificationCodeMail::class);
    }

    public function test_disabling_email_verification_allows_users_with_pending_codes_to_log_in(): void
    {
        DB::table('settings')->where('key', 'saas_email_verification')->update(['value' => '0']);
        $user = User::create([
            'name' => 'Pending Code Agent',
            'email' => 'pending-code-agent@example.com',
            'password' => bcrypt('Secure123'),
            'role' => 'agent',
            'status' => 'active',
            'is_verified' => 1,
        ]);
        DB::table('email_verification_challenges')->insert([
            'user_id' => $user->id,
            'email' => $user->email,
            'code_hash' => bcrypt('123456'),
            'expires_at' => now()->addMinutes(5),
            'sent_at' => now(),
            'attempts' => 0,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $response = $this->post(route('saas-login.post'), [
            'login' => $user->email,
            'password' => 'Secure123',
        ]);

        $response->assertRedirect();
        $this->assertNotSame(route('email-verification.notice'), $response->headers->get('Location'));
        $this->assertAuthenticatedAs($user);
    }

    public function test_registration_can_reuse_an_email_from_a_soft_deleted_account(): void
    {
        $deletedUser = User::create([
            'name' => 'Deleted Account',
            'email' => 'reused-email@example.com',
            'password' => bcrypt('Secure123'),
            'role' => 'admin',
            'status' => 'active',
            'is_verified' => 1,
        ]);
        \DB::table('subscriptions')->insert([
            'user_id' => $deletedUser->id,
            'plan' => 'Legacy',
            'plan_name' => 'Legacy',
            'billing_cycle' => 'yearly',
            'domain_prefix' => 'legacy-workspace',
            'status' => 'Active',
            'payment_status' => 'paid',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $deletedUser->delete();

        $this->approveOwnerEmail('reused-email@example.com');
        $response = $this->post(route('saas-register.post'), [
            'role' => 'admin',
            'name' => 'New Account',
            'email' => 'reused-email@example.com',
            'password' => 'Secure123',
            'password_confirmation' => 'Secure123',
            'plan' => 'pro',
            'billing_cycle' => 'monthly',
            'amount' => '19500',
            'field_of_operation' => 'general',
        ]);

        $response->assertRedirect(route('saas.setup', DB::table('subscriptions')->where('user_id', User::query()->where('email', 'reused-email@example.com')->value('id'))->value('id')));
        $this->assertSame(2, User::withTrashed()->where('email', 'reused-email@example.com')->count());
        $this->assertSame(1, User::query()->where('email', 'reused-email@example.com')->count());
        $this->assertNotSame($deletedUser->id, User::query()->where('email', 'reused-email@example.com')->value('id'));
        $this->assertDatabaseHas('subscriptions', [
            'user_id' => User::query()->where('email', 'reused-email@example.com')->value('id'),
            'status' => 'Trial',
            'payment_status' => 'free',
        ]);
        $this->assertDatabaseHas('subscriptions', [
            'user_id' => $deletedUser->id,
            'domain_prefix' => 'legacy-workspace',
            'status' => 'Active',
            'payment_status' => 'paid',
        ]);
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

        $this->approveOwnerEmail($payload['email']);
        $this->from(route('saas-register'))->post(route('saas-register.post'), $payload)
            ->assertRedirect();

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
        Mail::assertSent(RegistrationEmailApprovalMail::class, 1);
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

        $this->approveOwnerEmail($payload['email']);
        $this->from(route('saas-register'))->post(route('saas-register.post'), $payload)
            ->assertRedirect();

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

    public function test_approved_owner_can_retry_an_unpaid_registration_after_email_confirmation(): void
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

        $this->approveOwnerEmail('approved-retry@example.com');
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
        $response->assertRedirect(route('saas.setup', $subscription->id));
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
