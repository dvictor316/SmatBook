<?php

namespace Tests\Feature\Sales;

use App\Models\Account;
use App\Models\Company;
use App\Models\Payment;
use App\Models\Sale;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Maatwebsite\Excel\Facades\Excel;
use Tests\TestCase;

class PosSalesBankFilterTest extends TestCase
{
    use RefreshDatabase;

    private function workspace(): array
    {
        $company = Company::create(['name' => 'POS Banks', 'plan' => 'Enterprise']);
        $user = User::factory()->create(['company_id' => $company->id, 'role' => 'admin']);
        $this->actingAs($user)->withoutMiddleware()->withSession(['active_branch_scope' => 'all', 'current_tenant_id' => $company->id]);

        return [$company, $user];
    }

    private function account(Company $company, string $name, ?string $branch = null): Account
    {
        return Account::create(['company_id' => $company->id, 'name' => $name, 'code' => fake()->unique()->numerify('BANK-#####'),
            'type' => 'Asset', 'sub_type' => 'Bank', 'is_active' => true, 'branch_id' => $branch]);
    }

    private function sale(Company $company, User $user, string $reference, array $details = [], string $method = 'split'): Sale
    {
        return Sale::create(['company_id' => $company->id, 'user_id' => $user->id, 'terminal_id' => 'POS-1',
            'invoice_no' => $reference, 'customer_name' => 'Bank Customer', 'subtotal' => 1000, 'total' => 1000,
            'paid' => 1000, 'amount_paid' => 1000, 'balance' => 0, 'payment_status' => 'paid',
            'payment_method' => $method, 'payment_details' => $details]);
    }

    private function payment(Sale $sale, Account $account, float $amount, string $status = 'Completed'): void
    {
        Payment::create(['company_id' => $sale->company_id, 'user_id' => $sale->user_id, 'created_by' => $sale->user_id,
            'sale_id' => $sale->id, 'account_id' => $account->id, 'method' => 'Bank Transfer', 'amount' => $amount, 'status' => $status]);
    }

    public function test_dropdown_lists_available_banks_and_excludes_other_business_and_non_bank_accounts(): void
    {
        [$company] = $this->workspace();
        $moniepoint = $this->account($company, 'Moniepoint');
        $this->account($company, 'UBA Bank');
        $other = Company::create(['name' => 'Other Business']);
        $this->account($other, 'Private Other Bank');
        $this->account($company, 'Inventory Assets');
        $this->account($company, 'Inactive Bank')->update(['is_active' => false]);
        $this->get(route('pos.sales'))->assertOk()
            ->assertSee('value="account:'.$moniepoint->id.'"', false)->assertSee('UBA Bank')
            ->assertDontSee('Private Other Bank')->assertDontSee('Inventory Assets')->assertDontSee('Inactive Bank');
    }

    public function test_bank_filter_matches_receiving_accounts_and_totals_only_the_selected_bank_share(): void
    {
        [$company, $user] = $this->workspace();
        $moniepoint = $this->account($company, 'Moniepoint');
        $uba = $this->account($company, 'UBA Bank');
        $split = $this->sale($company, $user, 'MATCHED-SPLIT', ['split' => ['cash' => 300, 'transfer' => 200, 'card' => 500]]);
        $this->payment($split, $moniepoint, 200);
        $this->payment($split, $uba, 500);
        $different = $this->sale($company, $user, 'OTHER-BANK-ONLY');
        $this->payment($different, $uba, 1000);
        $failed = $this->sale($company, $user, 'FAILED-TRANSFER', ['transfer_account_id' => $moniepoint->id, 'split' => ['transfer' => 1000]]);
        $this->payment($failed, $moniepoint, 1000, 'Failed');

        $response = $this->get(route('pos.sales', ['payment_method' => 'account:'.$moniepoint->id]));
        $response->assertOk()->assertSee('MATCHED-SPLIT')->assertDontSee('OTHER-BANK-ONLY')->assertDontSee('FAILED-TRANSFER')
            ->assertSee('Paid Into Moniepoint')->assertViewHas('selectedAccountTotal', 200.0)
            ->assertViewHas('totalSalesCount', 1)->assertViewHas('totalRevenue', 1000.0);
        $this->get(route('pos.sales', ['payment_method' => 'account:'.$uba->id]))->assertOk()
            ->assertViewHas('selectedAccountTotal', 1500.0)->assertViewHas('totalSalesCount', 2);
    }

    public function test_legacy_split_and_single_mode_sales_filter_by_saved_account_ids(): void
    {
        [$company, $user] = $this->workspace();
        $bank = $this->account($company, 'Moniepoint');
        $this->sale($company, $user, 'LEGACY-SPLIT', ['transfer_account_id' => (string) $bank->id, 'card_account_id' => $bank->id,
            'split' => ['cash' => 300, 'transfer' => 200, 'card' => 500]]);
        $this->sale($company, $user, 'LEGACY-DIRECT', ['payment_account_id' => $bank->id], 'transfer');
        $this->sale($company, $user, 'ZERO-BANK-SHARE', ['transfer_account_id' => $bank->id, 'split' => ['cash' => 1000, 'transfer' => 0]]);
        $this->get(route('pos.sales', ['payment_method' => 'account:'.$bank->id]))->assertOk()
            ->assertSee('LEGACY-SPLIT')->assertSee('LEGACY-DIRECT')->assertDontSee('ZERO-BANK-SHARE')
            ->assertViewHas('selectedAccountTotal', 1700.0)->assertViewHas('totalSalesCount', 2);
    }

    public function test_unavailable_accounts_cannot_be_selected_from_another_business_or_branch(): void
    {
        [$company] = $this->workspace();
        $other = Company::create(['name' => 'Other Tenant']);
        $otherBank = $this->account($other, 'Other Tenant Bank');
        $this->get(route('pos.sales', ['payment_method' => 'account:'.$otherBank->id]))->assertStatus(422);
        $this->get(route('pos.sales', ['payment_method' => 'account:invalid']))->assertStatus(422);
        $otherBranchBank = $this->account($company, 'Branch B Bank', 'branch-b');
        $this->withSession(['active_branch_scope' => 'branch', 'active_branch_id' => 'branch-a', 'active_branch_name' => 'Branch A'])
            ->get(route('pos.sales', ['payment_method' => 'account:'.$otherBranchBank->id]))->assertStatus(422);
    }

    public function test_export_keeps_the_bank_filter_and_selected_account_amount(): void
    {
        [$company, $user] = $this->workspace();
        $bank = $this->account($company, 'Moniepoint');
        $other = $this->account($company, 'UBA Bank');
        $matched = $this->sale($company, $user, 'EXPORT-MATCH');
        $this->payment($matched, $bank, 250);
        $this->payment($matched, $other, 750);
        $notMatched = $this->sale($company, $user, 'EXPORT-OTHER');
        $this->payment($notMatched, $other, 1000);
        $this->freezeTime();
        Excel::fake();
        $this->get(route('pos.sales', ['payment_method' => 'account:'.$bank->id, 'export' => 'xlsx']))->assertOk();
        Excel::assertDownloaded('pos-sales-'.now()->format('Y-m-d-His').'.xlsx', function ($export) {
            $rows = $export->collection();

            return $rows->count() === 1 && $rows->first()['invoice_no'] === 'EXPORT-MATCH'
                && $rows->first()['selected_account_amount'] === 250.0
                && in_array('Paid Into Selected Account', $export->headings(), true);
        });
    }
}
