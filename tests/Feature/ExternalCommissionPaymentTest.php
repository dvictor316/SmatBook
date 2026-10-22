<?php

namespace Tests\Feature;

use App\Models\{Company, DeploymentManager, DeploymentManagerPayout, Subscription, User};
use App\Support\DeploymentCommissionPayoutService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\{DB, Http};
use Tests\TestCase;

class ExternalCommissionPaymentTest extends TestCase
{
    use RefreshDatabase;

    public function test_preview_is_read_only_and_apply_reconciles_only_the_confirmed_old_commission(): void
    {
        $f = $this->fixtures();
        Http::fake();
        $before = $this->snapshot();
        $this->artisan('commissions:record-external-payment', $f['options'])->assertSuccessful();
        $this->assertSame($before, $this->snapshot());
        $this->artisan('commissions:record-external-payment', $f['options'] + ['--apply' => true])->assertSuccessful();
        $this->assertDatabaseHas('deployment_commissions', ['id' => $f['old'], 'status' => 'paid', 'payout_id' => $f['payout']->id, 'paid_at' => null]);
        $this->assertDatabaseHas('deployment_commissions', ['id' => $f['latest'], 'status' => 'pending', 'payout_id' => null, 'amount' => 35000]);
        $this->assertDatabaseHas('deployment_manager_payouts', ['id' => $f['payout']->id, 'status' => 'paid', 'gateway' => 'external', 'paid_at' => null]);
        $this->assertDatabaseHas('deployment_manager_payouts', ['id' => $f['duplicate']->id, 'status' => 'cancelled']);
        $this->assertSame($before['deployment_managers'], $this->snapshot()['deployment_managers']);
        $this->assertSame($before['users'], $this->snapshot()['users']);
        $this->assertSame($before['companies'], $this->snapshot()['companies']);
        $this->assertDatabaseHas('deployment_commissions', ['manager_id' => $f['other']->id, 'amount' => 1234, 'status' => 'pending', 'payout_id' => null]);
        $this->assertSame((array) $f['failed'], (array) DB::table('deployment_manager_payouts')->where('id', $f['failed']->id)->first());
        $summary = app(DeploymentCommissionPayoutService::class)->summaryForManager($f['manager']->id);
        $this->assertSame(35000.0, $summary['available']);
        $this->assertSame(68250.0, $summary['paid']);
        $this->assertDatabaseHas('activity_logs', ['action' => 'external_payment_recorded', 'user_id' => $f['admin']->id]);
        $after = $this->snapshot();
        $this->artisan('commissions:record-external-payment', $f['options'] + ['--apply' => true])->assertSuccessful();
        $this->assertSame($after, $this->snapshot());
        Http::assertNothingSent();
    }

    public function test_changed_balance_wrong_manager_or_inflight_transfer_cannot_be_reconciled(): void
    {
        $f = $this->fixtures();
        $before = $this->snapshot();
        foreach ([['--expect-available' => 36000], ['--manager' => $f['other']->id], ['--recorded-by' => $f['other']->id]] as $change) {
            $this->artisan('commissions:record-external-payment', array_replace($f['options'], $change) + ['--apply' => true])->assertFailed();
            $this->assertSame($before, $this->snapshot());
        }
        $f['duplicate']->update(['transfer_reference' => 'TRF_existing']);
        $before = $this->snapshot();
        $this->artisan('commissions:record-external-payment', $f['options'] + ['--apply' => true])->assertFailed();
        $this->assertSame($before, $this->snapshot());
    }

    public function test_failure_rolls_back_payout_and_duplicate_status_changes(): void
    {
        $f = $this->fixtures();
        DB::statement("CREATE TRIGGER fail_commission_settlement BEFORE UPDATE ON deployment_commissions BEGIN SELECT RAISE(ABORT, 'rollback external settlement'); END");
        $before = $this->snapshot();
        $this->artisan('commissions:record-external-payment', $f['options'] + ['--apply' => true])->assertFailed();
        $this->assertSame($before, $this->snapshot());
    }

    public function test_only_35000_is_sent_after_reconciliation_and_future_commissions_still_pay(): void
    {
        $f = $this->fixtures();
        config(['services.paystack.secret' => 'sk_test_settlement']);
        Http::fake(['https://api.paystack.co/transfer' => Http::response(['status' => true, 'data' => ['status' => 'success', 'transfer_code' => 'TRF_settlement']], 200)]);
        $this->artisan('commissions:record-external-payment', $f['options'] + ['--apply' => true])->assertSuccessful();
        Http::assertNothingSent();
        $service = app(DeploymentCommissionPayoutService::class);
        $payout = $service->attemptAutoPayout($f['manager']->id);
        $this->assertSame(35000.0, (float) $payout->amount);
        $this->assertSame('paid', $payout->status);
        Http::assertSent(fn ($request) => $request['amount'] === 3500000);
        $this->assertNull($service->attemptAutoPayout($f['manager']->id));
        $future = (array) DB::table('deployment_commissions')->where('id', $f['latest'])->first();
        unset($future['id']);
        $future['amount'] = 5000; $future['status'] = 'pending'; $future['payout_id'] = null; $future['payout_reference'] = null; $future['paid_at'] = null;
        DB::table('deployment_commissions')->insert($future);
        $next = $service->attemptAutoPayout($f['manager']->id);
        $this->assertSame(5000.0, (float) $next->amount);
        Http::assertSentCount(2);
        Http::assertSent(fn ($request) => $request['amount'] === 500000);
        $this->assertDatabaseHas('deployment_commissions', ['id' => $f['old'], 'payout_id' => $f['payout']->id, 'amount' => 68250, 'status' => 'paid']);
    }

    private function fixtures(): array
    {
        $admin = User::factory()->create(['role' => 'super_admin']);
        $manager = User::factory()->create(['name' => 'OGBODO THOMAS', 'role' => 'state_manager']);
        $other = User::factory()->create(['name' => 'Thomas Ogbodo', 'role' => 'admin']);
        $company = Company::create(['name' => 'Commission client', 'user_id' => $other->id]);
        $subscription = Subscription::withoutGlobalScopes()->create(['user_id' => $other->id, 'company_id' => $company->id,
            'plan' => 'Professional', 'billing_cycle' => 'Yearly', 'amount' => 100000, 'status' => 'Active', 'payment_status' => 'paid']);
        DeploymentManager::create(['user_id' => $manager->id, 'status' => 'active', 'auto_payout_enabled' => true,
            'payout_bank_code' => '044', 'payout_bank_name' => 'Access', 'payout_account_name' => 'OGBODO THOMAS',
            'payout_account_number' => '0123456789', 'payout_recipient_code' => 'RCP_settlement', 'payout_provider' => 'paystack', 'minimum_payout_amount' => 5000]);
        $base = ['manager_id' => $manager->id, 'company_id' => $company->id, 'subscription_id' => $subscription->id,
            'status' => 'pending', 'created_at' => now(), 'updated_at' => now()];
        $old = DB::table('deployment_commissions')->insertGetId($base + ['amount' => 68250]);
        $latest = DB::table('deployment_commissions')->insertGetId($base + ['amount' => 35000]);
        DB::table('deployment_commissions')->insert(array_replace($base, ['manager_id' => $other->id, 'amount' => 1234]));
        $payout = DeploymentManagerPayout::create(['manager_id' => $manager->id, 'amount' => 68250, 'currency' => 'NGN', 'status' => 'manual_review', 'payout_reference' => 'old-external-request']);
        $duplicate = DeploymentManagerPayout::create(['manager_id' => $manager->id, 'amount' => 68250, 'currency' => 'NGN', 'status' => 'manual_review', 'payout_reference' => 'old-duplicate-request']);
        $failedModel = DeploymentManagerPayout::create(['manager_id' => $manager->id, 'amount' => 103250, 'currency' => 'NGN', 'status' => 'failed', 'payout_reference' => 'failed-request']);
        $failed = DB::table('deployment_manager_payouts')->where('id', $failedModel->id)->first();
        $options = ['--manager' => $manager->id, '--commission' => $old, '--payout' => $payout->id,
            '--duplicate' => (string) $duplicate->id, '--amount' => 68250, '--expect-available' => 35000,
            '--recorded-by' => $admin->id, '--reason' => 'Owner confirms earlier commission was paid externally before the last deployment'];
        return compact('admin', 'manager', 'other', 'old', 'latest', 'payout', 'duplicate', 'failed', 'options');
    }

    private function snapshot(): array
    {
        return collect(['users', 'companies', 'deployment_managers', 'deployment_commissions', 'deployment_manager_payouts', 'activity_logs'])
            ->mapWithKeys(fn ($table) => [$table => DB::table($table)->orderBy('id')->get()->map(fn ($row) => (array) $row)->all()])->all();
    }
}
