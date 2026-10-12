<?php

namespace Tests\Feature;

use App\Http\Middleware\BlockExpiredDemoUser;
use App\Http\Middleware\CheckPlanAccess;
use App\Http\Middleware\EnforceDeviceSessionLimit;
use App\Http\Middleware\ForceLogoutExpiredSession;
use App\Http\Middleware\RequireActiveBranch;
use App\Http\Middleware\SubscriptionActive;
use App\Models\BillPayment;
use App\Models\Company;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class BillPaymentWorkflowTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        config(['services.flutterwave.secret_key' => 'FLWSECK_TEST-workflow']);
        $this->withoutMiddleware([
            SubscriptionActive::class,
            CheckPlanAccess::class,
            RequireActiveBranch::class,
            EnforceDeviceSessionLimit::class,
            ForceLogoutExpiredSession::class,
            BlockExpiredDemoUser::class,
        ]);
    }

    public function test_payment_is_validated_submitted_and_reconciled_with_provider_commission(): void
    {
        [$user, $company] = $this->workspace('Utility Company');

        Http::fake(function (Request $request) {
            if (str_contains($request->url(), '/validate')) {
                return Http::response(['status' => 'success', 'data' => ['name' => 'Ada Customer', 'fee' => 25]]);
            }

            if ($request->method() === 'POST' && str_contains($request->url(), '/payment')) {
                return Http::response(['status' => 'success', 'data' => [
                    'code' => '200',
                    'fee' => 100,
                    'reference' => 'FLW-BILL-001',
                    'tx_ref' => 'FLW-TX-001',
                ]]);
            }

            if (str_contains($request->url(), '/bills/history')) {
                parse_str((string) parse_url($request->url(), PHP_URL_QUERY), $query);
                return Http::response(['status' => 'success', 'data' => [[
                    'customer_reference' => $query['reference'] ?? '',
                    'status' => 'successful',
                    'fee' => 100,
                    'commission' => 45,
                    'flw_ref' => 'FLW-BILL-001',
                    'tx_id' => 'FLW-TX-001',
                ]]]);
            }

            return Http::response(['status' => 'error', 'message' => 'Unexpected test request.'], 400);
        });

        $this->actingAs($user)
            ->withSession(['current_tenant_id' => $company->id, 'active_branch_scope' => 'all'])
            ->post(route('bill-payments.store'), [
                'category_code' => 'utilities',
                'biller_code' => 'EKEDC',
                'item_code' => 'PREPAID',
                'product_name' => 'Electricity Prepaid',
                'customer_identifier' => '01234567890',
                'amount' => 5000,
            ])
            ->assertSessionHasNoErrors()
            ->assertSessionHas('success');

        $payment = BillPayment::withoutGlobalScopes()->firstOrFail();
        $this->assertSame($company->id, (int) $payment->company_id);
        $this->assertSame('successful', $payment->status);
        $this->assertSame('100.00', $payment->provider_fee);
        $this->assertSame('45.00', $payment->commission_earned);
        $this->assertSame('Ada Customer', $payment->customer_name);

        Http::assertSentCount(3);
    }

    public function test_foreign_workspace_cannot_refresh_another_company_payment(): void
    {
        [$owner, $ownerCompany] = $this->workspace('Owner Company');
        [$other, $otherCompany] = $this->workspace('Other Company');
        $payment = BillPayment::withoutGlobalScopes()->create([
            'company_id' => $ownerCompany->id,
            'created_by' => $owner->id,
            'reference' => 'SPB-BILL-FOREIGN',
            'biller_code' => 'EKEDC',
            'item_code' => 'PREPAID',
            'customer_identifier' => '01234567890',
            'amount' => 5000,
            'status' => 'pending',
        ]);

        Http::fake();

        $this->actingAs($other)
            ->withSession(['current_tenant_id' => $otherCompany->id, 'active_branch_scope' => 'all'])
            ->post(route('bill-payments.refresh', $payment))
            ->assertNotFound();

        Http::assertNothingSent();
    }

    private function workspace(string $name): array
    {
        $user = User::factory()->create(['role' => 'admin']);
        $company = Company::withoutGlobalScope('tenant')->create([
            'user_id' => $user->id,
            'owner_id' => $user->id,
            'name' => $name,
            'status' => 'active',
            'plan' => 'Enterprise Yearly',
        ]);
        $user->forceFill(['company_id' => $company->id])->save();

        return [$user, $company];
    }
}
