<?php

namespace Tests\Feature\Sales;

use App\Models\Company;
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

        $this->actingAs($user)->withoutMiddleware()
            ->get(route('sales.showPos'))
            ->assertOk()
            ->assertSee('id="pos-cash-account-label">Payment Method', false)
            ->assertSee('id="pos-cash-amount-label">Amount Paid', false)
            ->assertDontSee('Payment Method: Cash Account')
            ->assertDontSee('Amount Paid: Cash');
    }
}
