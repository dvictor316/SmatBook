<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class SalesReportPaginationTest extends TestCase
{
    use RefreshDatabase;

    public function test_sales_report_shows_500_rows_and_totals_the_entire_filtered_result(): void
    {
        [$user, $company] = $this->business();
        $this->records($user, $company, 520);
        $this->login($user, $company);
        $response = $this->get(route('reports.sales'));
        $response->assertOk()->assertSee('Rows per page');
        $rows = $response->viewData('salesreports');
        $this->assertSame(500, $rows->perPage());
        $this->assertSame(500, $rows->count());
        $this->assertSame(520, $rows->total());
        $this->assertSame(520, (int) $response->viewData('reportSummary')->products);
        $this->assertSame(522.0, (float) $response->viewData('reportSummary')->quantity);
        $this->assertSame(52200.0, (float) $response->viewData('reportSummary')->revenue);

        $page2 = $this->get(route('reports.sales', ['page' => 2]));
        $page2->assertOk();
        $this->assertSame(20, $page2->viewData('salesreports')->count());
        $this->assertSame(50200.0, (float) $page2->viewData('reportRunningTotal'));
        $this->assertSame(52200.0, (float) $page2->viewData('reportSummary')->revenue);
    }

    public function test_filters_run_before_pagination_and_preserve_branch_and_page_size(): void
    {
        [$user, $company] = $this->business();
        $this->records($user, $company, 26);
        $this->records($user, $company, 1, 'other-branch', 'transfer');
        [$otherUser, $otherCompany] = $this->business();
        $this->records($otherUser, $otherCompany, 1);
        $this->login($user, $company);
        $query = ['per_page' => 10, 'search' => 'Product', 'payment_method' => 'cash',
            'start_date' => now()->subDay()->toDateString(), 'end_date' => now()->subDay()->toDateString()];
        $response = $this->get(route('reports.sales', $query));
        $response->assertOk();
        $rows = $response->viewData('salesreports');
        $this->assertSame(10, $rows->count());
        $this->assertSame(26, $rows->total());
        $this->assertSame(28.0, (float) $response->viewData('reportSummary')->quantity);
        parse_str(parse_url($rows->nextPageUrl(), PHP_URL_QUERY), $next);
        $this->assertSame('cash', $next['payment_method']);
        $this->assertSame('Product', $next['search']);
        $this->assertSame('10', $next['per_page']);
        $single = $this->get(route('reports.sales', ['search' => 'SKU-'.$company->id.'-hq-0000', 'per_page' => 25]));
        $single->assertOk();
        $this->assertSame(1, $single->viewData('salesreports')->total());
        $this->assertSame(3.0, (float) $single->viewData('reportSummary')->quantity);
        $this->assertSame(1, (int) $single->viewData('reportSummary')->products);
    }

    public function test_invalid_date_window_is_rejected_instead_of_silently_showing_wrong_results(): void
    {
        [$user, $company] = $this->business();
        $this->login($user, $company);
        $this->getJson(route('reports.sales', ['start_date' => '2026-10-21', 'end_date' => '2026-10-01']))->assertUnprocessable();
    }

    private function business(): array
    {
        $user = User::factory()->create(['role' => 'admin']);
        $company = Company::withoutGlobalScopes()->create(['user_id' => $user->id, 'owner_id' => $user->id, 'name' => 'Report Business']);
        $user->forceFill(['company_id' => $company->id])->save();
        return [$user, $company];
    }

    private function login(User $user, Company $company): void
    {
        $this->withoutMiddleware([
            \App\Http\Middleware\SubscriptionActive::class, \App\Http\Middleware\RequireActiveBranch::class,
            \App\Http\Middleware\CheckPlanAccess::class, \App\Http\Middleware\ForceLogoutExpiredSession::class,
            \App\Http\Middleware\EnforceDeviceSessionLimit::class, \App\Http\Middleware\VerifyTenantSession::class,
        ])->actingAs($user)->withSession([
            'current_tenant_id' => $company->id, 'active_branch_id' => 'hq',
            'active_branch_name' => 'Headquarters', 'active_branch_scope' => 'branch',
        ]);
    }

    private function records(User $user, Company $company, int $count, string $branch = 'hq', string $method = 'cash'): void
    {
        $invoice = 'PAGE-'.$company->id.'-'.$branch;
        $saleId = DB::table('sales')->insertGetId([
            'company_id' => $company->id, 'user_id' => $user->id, 'branch_id' => $branch,
            'branch_name' => $branch === 'hq' ? 'Headquarters' : 'Other Branch',
            'invoice_no' => $invoice, 'receipt_no' => $invoice.'-R', 'order_number' => $invoice.'-O',
            'order_date' => now()->subDay()->toDateString(), 'total' => ($count + 2) * 100, 'paid' => ($count + 2) * 100,
            'amount_paid' => ($count + 2) * 100, 'payment_method' => $method,
            'created_at' => now(), 'updated_at' => now(),
        ]);
        $products = [];
        for ($i = 0; $i < $count; $i++) {
            $products[] = ['company_id' => $company->id, 'user_id' => $user->id, 'name' => 'Product '.sprintf('%04d', $i),
                'sku' => 'SKU-'.$company->id.'-'.$branch.'-'.sprintf('%04d', $i), 'price' => 100, 'purchase_price' => 50,
                'stock' => 10, 'stock_quantity' => 10, 'created_at' => now(), 'updated_at' => now()];
        }
        foreach (array_chunk($products, 100) as $chunk) DB::table('products')->insert($chunk);
        $ids = DB::table('products')->where('company_id', $company->id)->where('sku', 'like', 'SKU-'.$company->id.'-'.$branch.'-%')->orderBy('id')->pluck('id');
        $items = $ids->map(fn ($id, $index) => ['sale_id' => $saleId, 'product_id' => $id, 'quantity' => $index === 0 ? 3 : 1, 'qty' => $index === 0 ? 3 : 1, 'stock_units' => $index === 0 ? 3 : 1,
            'unit_price' => 100, 'total_price' => $index === 0 ? 300 : 100, 'created_at' => now(), 'updated_at' => now()])->all();
        foreach (array_chunk($items, 100) as $chunk) DB::table('sale_items')->insert($chunk);
    }
}
