<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\DeploymentManager;
use App\Models\Subscription;
use App\Models\User;
use App\Support\DeploymentCommissionPayoutService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request as ClientRequest;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use RuntimeException;
use Tests\TestCase;

class ManagerAgentPaystackPayoutTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        config([
            'services.paystack.secret' => 'sk_test_payout',
            'services.paystack.secretKey' => 'sk_test_payout',
            'services.paystack.secret_key' => 'sk_test_payout',
        ]);
    }

    public function test_paystack_payout_is_initiated_and_completed_by_signed_webhook(): void
    {
        [$manager, $commissionId] = $this->payoutWorkspace();

        Http::fake(function (ClientRequest $request) {
            if (str_contains($request->url(), '/bank/resolve')) {
                return Http::response(['status' => true, 'data' => ['account_name' => 'Verified Manager']]);
            }
            if (str_ends_with($request->url(), '/transferrecipient')) {
                return Http::response(['status' => true, 'data' => ['recipient_code' => 'RCP_test_manager']]);
            }
            if (str_ends_with($request->url(), '/transfer')) {
                return Http::response(['status' => true, 'data' => [
                    'status' => 'pending',
                    'transfer_code' => 'TRF_test_manager',
                ]]);
            }

            return Http::response(['status' => false, 'message' => 'Unexpected test request.'], 400);
        });

        $payout = app(DeploymentCommissionPayoutService::class)
            ->createPayoutForManager($manager->user_id, false, $manager->user_id);

        $this->assertNotNull($payout);
        $this->assertSame('paystack', $payout->gateway);
        $this->assertSame('processing', $payout->status);
        $this->assertMatchesRegularExpression('/^[a-z0-9_-]{16,50}$/', $payout->payout_reference);
        $this->assertSame('RCP_test_manager', $manager->fresh()->payout_recipient_code);
        $this->assertDatabaseHas('deployment_commissions', [
            'id' => $commissionId,
            'status' => 'pending',
            'payout_id' => $payout->id,
        ]);

        Http::assertSent(function (ClientRequest $request) use ($payout) {
            return str_ends_with($request->url(), '/transfer')
                && $request['amount'] === 1250000
                && $request['reference'] === $payout->payout_reference;
        });

        $payload = json_encode([
            'event' => 'transfer.success',
            'data' => [
                'reference' => $payout->payout_reference,
                'transfer_code' => 'TRF_test_manager',
            ],
        ], JSON_THROW_ON_ERROR);
        $signature = hash_hmac('sha512', $payload, 'sk_test_payout');

        $this->call('POST', '/api/paystack/webhook', [], [], [], [
            'CONTENT_TYPE' => 'application/json',
            'HTTP_ACCEPT' => 'application/json',
            'HTTP_X_PAYSTACK_SIGNATURE' => $signature,
        ], $payload)->assertOk();

        $this->assertDatabaseHas('deployment_manager_payouts', [
            'id' => $payout->id,
            'status' => 'paid',
        ]);
        $this->assertDatabaseHas('deployment_commissions', [
            'id' => $commissionId,
            'status' => 'paid',
            'payout_id' => $payout->id,
        ]);
    }

    public function test_inconclusive_transfer_keeps_commission_locked_for_same_reference_retry(): void
    {
        [$manager, $commissionId] = $this->payoutWorkspace('RCP_existing');

        $simulateTimeout = true;
        Http::fake(function (ClientRequest $request) use (&$simulateTimeout) {
            if (str_ends_with($request->url(), '/transfer')) {
                if ($simulateTimeout) {
                    throw new RuntimeException('Simulated network timeout.');
                }

                return Http::response(['status' => true, 'data' => [
                    'status' => 'pending',
                    'transfer_code' => 'TRF_retry',
                ]]);
            }

            return Http::response(['status' => false], 400);
        });

        $service = app(DeploymentCommissionPayoutService::class);
        $payout = $service->createPayoutForManager($manager->user_id, false, $manager->user_id);

        $this->assertSame('manual_review', $payout->status);
        $this->assertDatabaseHas('deployment_commissions', [
            'id' => $commissionId,
            'status' => 'pending',
            'payout_id' => $payout->id,
            'payout_reference' => $payout->payout_reference,
        ]);

        $originalReference = $payout->payout_reference;
        $simulateTimeout = false;

        $retried = $service->retryManualReviewPayoutForManager($manager->user_id, $manager->user_id);

        $this->assertSame($payout->id, $retried->id);
        $this->assertSame($originalReference, $retried->payout_reference);
        $this->assertSame('processing', $retried->status);
    }

    public function test_agent_can_save_bank_details_and_request_own_payout_from_the_portal(): void
    {
        [$manager, $commissionId] = $this->payoutWorkspace();
        $user = User::findOrFail($manager->user_id);
        $manager->delete(); // An agent may not have a payout profile yet.
        $this->withoutMiddleware([
            \App\Http\Middleware\ForceLogoutExpiredSession::class,
            \App\Http\Middleware\EnforceDeviceSessionLimit::class,
        ]);
        Http::preventStrayRequests();
        Http::fake([
            'api.paystack.co/bank?*' => Http::response(['status' => true, 'data' => [['name' => 'Access Bank', 'code' => '044']]]),
            'api.paystack.co/bank/resolve?*' => Http::response(['status' => true, 'data' => ['account_name' => 'Agent Account']]),
            'api.paystack.co/transferrecipient' => Http::response(['status' => true, 'data' => ['recipient_code' => 'RCP_agent']]),
            'api.paystack.co/transfer' => Http::response(['status' => true, 'data' => ['status' => 'pending', 'transfer_code' => 'TRF_agent']]),
        ]);

        $response = $this->actingAs($user)->from(route('agent.earnings'))->post(route('agent.earnings.request-payout'), [
            'payout_bank_name' => 'Access Bank',
            'payout_bank_code' => '044',
            'payout_account_name' => 'Agent Account',
            'payout_account_number' => '0123456789',
            'payout_provider' => 'paystack',
            'minimum_payout_amount' => 5000,
        ]);

        $response->assertRedirect(route('agent.earnings'))->assertSessionHasNoErrors()->assertSessionHas('success');
        $this->assertDatabaseHas('deployment_manager_payouts', ['manager_id' => $user->id, 'status' => 'processing', 'amount' => 12500]);
        $this->assertDatabaseHas('deployment_commissions', ['id' => $commissionId, 'status' => 'pending']);
        Http::assertSentCount(4);
        Http::assertSent(fn (ClientRequest $request) => str_ends_with($request->url(), '/transfer') && $request['amount'] === 1250000);

        // A repeat click must not send the same commissions a second time.
        $this->post(route('agent.earnings.request-payout'))->assertSessionHas('error');
        Http::assertSentCount(4);
        $this->assertDatabaseCount('deployment_manager_payouts', 1);
    }

    public function test_agent_payout_validates_entered_details_before_sending_money(): void
    {
        [$manager] = $this->payoutWorkspace('RCP_existing');
        $user = User::findOrFail($manager->user_id);
        $this->withoutMiddleware([
            \App\Http\Middleware\ForceLogoutExpiredSession::class,
            \App\Http\Middleware\EnforceDeviceSessionLimit::class,
        ]);
        Http::preventStrayRequests();
        Http::fake();

        $this->actingAs($user)->from(route('agent.earnings'))->post(route('agent.earnings.request-payout'), [
            'payout_bank_code' => '044',
            'payout_bank_name' => 'Access Bank',
            'payout_account_name' => 'Agent Account',
            'payout_account_number' => '',
            'payout_provider' => 'paystack',
        ])->assertRedirect(route('agent.earnings'))->assertSessionHasErrors('payout_account_number');

        Http::assertNothingSent();
        $this->assertDatabaseCount('deployment_manager_payouts', 0);
        $this->assertSame('0123456789', $manager->fresh()->payout_account_number);
    }

    public function test_saving_agent_bank_details_automatically_starts_bank_transfer_without_opt_in(): void
    {
        [$manager] = $this->payoutWorkspace('RCP_existing');
        $user = User::findOrFail($manager->user_id);
        $this->withoutMiddleware([
            \App\Http\Middleware\ForceLogoutExpiredSession::class,
            \App\Http\Middleware\EnforceDeviceSessionLimit::class,
        ]);
        Http::preventStrayRequests();
        Http::fake([
            'api.paystack.co/bank?*' => Http::response(['status' => true, 'data' => [['name' => 'Access Bank', 'code' => '044']]]),
            'api.paystack.co/transfer' => Http::response(['status' => true, 'data' => ['status' => 'pending', 'transfer_code' => 'TRF_auto']]),
        ]);

        $this->actingAs($user)->from(route('agent.earnings'))->post(route('agent.earnings.payout-profile'), [
            'payout_bank_name' => 'Access Bank', 'payout_bank_code' => '044',
            'payout_account_name' => 'Agent Account', 'payout_account_number' => '0123456789',
            'payout_provider' => 'paystack', 'minimum_payout_amount' => 5000,
        ])->assertSessionHasNoErrors()->assertSessionHas('success');

        $this->assertTrue($manager->fresh()->auto_payout_enabled);
        $this->assertDatabaseHas('deployment_manager_payouts', [
            'manager_id' => $user->id, 'status' => 'processing', 'is_automatic' => true, 'amount' => 12500,
        ]);
        Http::assertSent(fn (ClientRequest $request) => str_ends_with($request->url(), '/transfer') && $request['recipient'] === 'RCP_existing');
    }

    public function test_automatic_payout_can_find_agent_profile_while_customer_is_logged_in(): void
    {
        [$manager] = $this->payoutWorkspace('RCP_existing');
        $manager->update(['auto_payout_enabled' => true]);
        $customer = User::factory()->create(['role' => 'admin']);
        $this->actingAs($customer);
        config(['tenancy.enforce_scopes_in_console' => true]);
        // Reproduce the web checkout scope that previously hid the agent profile.
        $this->assertNull(DeploymentManager::where('user_id', $manager->user_id)->first());
        Http::preventStrayRequests();
        Http::fake(['api.paystack.co/transfer' => Http::response([
            'status' => true, 'data' => ['status' => 'pending', 'transfer_code' => 'TRF_checkout'],
        ])]);

        $payout = app(DeploymentCommissionPayoutService::class)->attemptAutoPayout($manager->user_id);
        $this->assertNotNull($payout);
        $this->assertSame('processing', $payout->status);
        $this->assertSame($manager->user_id, $payout->manager_id);
        $this->assertTrue($payout->is_automatic);
        Http::assertSentCount(1);
        $this->assertNull(app(DeploymentCommissionPayoutService::class)->attemptAutoPayout($manager->user_id));
        Http::assertSentCount(1);
    }

    public function test_scheduled_payouts_send_eligible_agent_commissions_without_dashboard_visit(): void
    {
        [$manager] = $this->payoutWorkspace('RCP_existing');
        $manager->update(['auto_payout_enabled' => true]);
        Http::preventStrayRequests();
        Http::fake(['api.paystack.co/transfer' => Http::response([
            'status' => true, 'data' => ['status' => 'pending', 'transfer_code' => 'TRF_scheduled'],
        ])]);

        $this->artisan('agents:process-payouts')->assertSuccessful();
        $this->assertDatabaseHas('deployment_manager_payouts', [
            'manager_id' => $manager->user_id, 'status' => 'processing', 'is_automatic' => true,
        ]);
        $this->artisan('agents:process-payouts')->assertSuccessful();
        Http::assertSentCount(1);
    }

    public function test_existing_agent_profiles_are_enabled_without_enabling_other_staff(): void
    {
        [$manager] = $this->payoutWorkspace();
        $staff = User::factory()->create(['role' => 'cashier']);
        $staffProfile = DeploymentManager::create(['user_id' => $staff->id, 'auto_payout_enabled' => false]);
        $migration = require database_path('migrations/2026_10_20_000001_enable_automatic_bank_payouts_for_agents.php');
        $migration->up();

        $this->assertTrue($manager->fresh()->auto_payout_enabled);
        $this->assertFalse($staffProfile->fresh()->auto_payout_enabled);
    }

    public function test_automatic_transfer_failure_is_shown_instead_of_reporting_success(): void
    {
        [$manager, $commissionId] = $this->payoutWorkspace('RCP_existing');
        $user = User::findOrFail($manager->user_id);
        $this->withoutMiddleware([
            \App\Http\Middleware\ForceLogoutExpiredSession::class,
            \App\Http\Middleware\EnforceDeviceSessionLimit::class,
        ]);
        Http::preventStrayRequests();
        Http::fake([
            'api.paystack.co/bank?*' => Http::response(['status' => true, 'data' => [['name' => 'Access Bank', 'code' => '044']]]),
            'api.paystack.co/transfer' => Http::response(['status' => false, 'message' => 'Your balance is not enough to fulfill this request'], 400),
        ]);

        $this->actingAs($user)->from(route('agent.earnings'))->post(route('agent.earnings.payout-profile'), [
            'payout_bank_name' => 'Access Bank', 'payout_bank_code' => '044',
            'payout_account_name' => 'Agent Account', 'payout_account_number' => '0123456789',
            'payout_provider' => 'paystack', 'minimum_payout_amount' => 5000,
        ])->assertSessionHas('error', 'Your balance is not enough to fulfill this request')->assertSessionMissing('success');

        $this->assertDatabaseHas('deployment_manager_payouts', ['manager_id' => $user->id, 'status' => 'failed']);
        $this->assertDatabaseHas('deployment_commissions', ['id' => $commissionId, 'status' => 'pending', 'payout_id' => null]);
    }

    private function payoutWorkspace(?string $recipientCode = null): array
    {
        $user = User::factory()->create(['role' => 'agent']);
        $company = Company::withoutGlobalScope('tenant')->create([
            'user_id' => $user->id,
            'owner_id' => $user->id,
            'name' => 'Payout Customer',
            'status' => 'active',
            'plan' => 'Professional Yearly',
        ]);
        $subscription = Subscription::withoutGlobalScopes()->create([
            'user_id' => $user->id,
            'company_id' => $company->id,
            'plan' => 'Professional Yearly',
            'billing_cycle' => 'Yearly',
            'amount' => 80000,
            'status' => 'Active',
            'payment_status' => 'paid',
        ]);
        $manager = DeploymentManager::withoutGlobalScopes()->create([
            'user_id' => $user->id,
            'status' => 'active',
            'commission_rate' => 35,
            'payout_bank_name' => 'Access Bank',
            'payout_bank_code' => '044',
            'payout_account_name' => 'Manager Name',
            'payout_account_number' => '0123456789',
            'payout_provider' => 'paystack',
            'payout_recipient_code' => $recipientCode,
            'payout_status' => $recipientCode ? 'verified' : 'configured',
            'minimum_payout_amount' => 5000,
        ]);
        $commissionId = DB::table('deployment_commissions')->insertGetId([
            'manager_id' => $user->id,
            'company_id' => $company->id,
            'subscription_id' => $subscription->id,
            'amount' => 12500,
            'commission_rate' => 35,
            'status' => 'pending',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return [$manager, $commissionId];
    }
}
