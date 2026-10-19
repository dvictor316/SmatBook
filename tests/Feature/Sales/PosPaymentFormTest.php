<?php

namespace Tests\Feature\Sales;

use App\Models\Company;
use App\Models\Account;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PosPaymentFormTest extends TestCase
{
    use RefreshDatabase;

    public function test_pos_payment_form_uses_method_neutral_labels(): void
    {
        $company = Company::create(['name' => 'POS Payment Company', 'industry' => 'retail', 'plan' => 'Enterprise']);
        $user = User::factory()->create(['company_id' => $company->id, 'role' => 'admin']);
        $cashAccount = Account::create([
            'company_id' => $company->id, 'name' => 'Cash Drawer', 'code' => 'CASH-1',
            'type' => Account::TYPE_ASSET, 'sub_type' => 'Cash', 'is_active' => true,
        ]);

        $response = $this->actingAs($user)->withoutMiddleware()
            ->get(route('sales.showPos'))
            ->assertOk()
            ->assertSee('id="pos-cash-account-label">Payment Method', false)
            ->assertSee('id="pos-cash-amount-label">Amount Paid', false)
            ->assertSee('id="deposit-account" value="'.$cashAccount->id.'"', false)
            ->assertSee('Cash Drawer')
            ->assertDontSee('Payment Method: Cash Account')
            ->assertDontSee('Amount Paid: Cash');

        $html = $response->getContent();
        $this->assertGreaterThan(strpos($html, 'id="grand-total"'), strpos($html, '<div class="pos-payment-tabs"'));
        $this->assertLessThan(strpos($html, 'id="amount-paid"'), strpos($html, '<div class="pos-payment-tabs"'));

        $cashAccount->update(['is_active' => false]);
        $this->get(route('sales.showPos'))
            ->assertOk()
            ->assertSee('Contact your administrator to configure a cash account');
    }
}
