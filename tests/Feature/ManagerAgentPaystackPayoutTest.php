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
