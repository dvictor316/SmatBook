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
        $bankAccount = Account::create([
            'company_id' => $company->id, 'name' => 'Main Bank', 'code' => 'BANK-1',
            'type' => Account::TYPE_ASSET, 'sub_type' => 'Bank', 'is_active' => true,
        ]);
        $legacyCash = Account::create([
            'company_id' => $company->id, 'name' => 'Petty Cash', 'code' => 'CASH-OLD',
            'type' => Account::TYPE_ASSET, 'sub_type' => 'Cash & Bank', 'is_active' => true,
        ]);
        $legacyBank = Account::create([
            'company_id' => $company->id, 'name' => 'Old Bank', 'code' => 'BANK-OLD',
            'type' => Account::TYPE_ASSET, 'is_active' => true,
        ]);

        $response = $this->actingAs($user)->withoutMiddleware()->withSession([
            'active_branch_id' => 'branch-1',
            'active_branch_name' => 'Main Branch',
        ])
            ->get(route('sales.showPos'))
            ->assertOk()
            ->assertSee('id="pos-cash-account-label">Payment Method', false)
            ->assertSee('id="rail-suspend-sale-btn"', false)
            ->assertSee('id="rail-sales-suspense-btn"', false)
            ->assertSee('id="pos-suspense-modal"', false)
            ->assertSee('id="pos-cash-amount-label">Amount Paid', false)
            ->assertSee('id="deposit-account" value="'.$cashAccount->id.'"', false)
            ->assertSee('Cash Drawer')
            ->assertSee('<option value="'.$bankAccount->id.'">Main Bank', false)
            ->assertSee('<option value="'.$legacyBank->id.'">Old Bank', false)
            ->assertDontSee('<option value="'.$cashAccount->id.'">Cash Drawer', false)
            ->assertDontSee('<option value="'.$legacyCash->id.'">Petty Cash', false)
            ->assertDontSee('Payment Method: Cash Account')
            ->assertDontSee('Amount Paid: Cash');

        $html = $response->getContent();
        $this->assertGreaterThan(strpos($html, 'id="grand-total"'), strpos($html, '<div class="pos-payment-tabs"'));
        $this->assertLessThan(strpos($html, 'id="amount-paid"'), strpos($html, '<div class="pos-payment-tabs"'));

        $cashAccount->update(['is_active' => false]);
        $this->get(route('sales.showPos'))
            ->assertOk()
            ->assertSee('Cash account not configured')
            ->assertSee('Call your administrator to set it up.');

        $this->assertContains('Cash', Account::subtypeOptionsFor(Account::TYPE_ASSET));
        $this->assertContains('Bank', Account::subtypeOptionsFor(Account::TYPE_ASSET));
    }

    public function test_new_chart_accounts_use_separate_cash_and_bank_subtypes(): void
    {
        $company = Company::create(['name' => 'Chart Company', 'industry' => 'retail', 'plan' => 'Enterprise']);
        $user = User::factory()->create(['company_id' => $company->id, 'role' => 'admin']);
        $this->actingAs($user)->withoutMiddleware();

        foreach (['Cash', 'Bank'] as $subType) {
            $this->post(route('settings.chart-of-accounts.store'), [
                'code' => strtoupper($subType).'-NEW',
                'name' => $subType.' account',
                'type' => Account::TYPE_ASSET,
                'sub_type' => $subType,
            ])->assertRedirect(route('chart-of-accounts'));
            $this->assertDatabaseHas('accounts', [
                'company_id' => $company->id,
                'name' => $subType.' account',
                'sub_type' => $subType,
            ]);
        }

        $this->post(route('settings.chart-of-accounts.store'), [
            'code' => 'COMBINED-NEW',
            'name' => 'Combined account',
            'type' => Account::TYPE_ASSET,
            'sub_type' => 'Cash & Bank',
        ])->assertSessionHasErrors('sub_type');
        $this->assertDatabaseMissing('accounts', ['company_id' => $company->id, 'code' => 'COMBINED-NEW']);
    }
}
