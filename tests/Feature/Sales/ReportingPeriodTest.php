<?php

namespace Tests\Feature\Sales;

use App\Models\{Company, User};
use App\Support\ReportingPeriod;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Facades\Excel;
use Tests\TestCase;

class ReportingPeriodTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->travelTo(\Carbon\Carbon::parse('2026-10-22 10:00:00', 'UTC'));
        config(['reporting.timezone' => 'Africa/Lagos', 'app.timezone' => 'UTC']);
    }

    private function workspace(): array
    {
        $user = User::factory()->create(['role' => 'admin']);
        $company = Company::create(['name' => 'Calendar Business', 'user_id' => $user->id, 'owner_id' => $user->id, 'plan' => 'Enterprise']);
        $user->update(['company_id' => $company->id]);
        $this->withoutMiddleware([\App\Http\Middleware\CheckPlanAccess::class, \App\Http\Middleware\SubscriptionActive::class, \App\Http\Middleware\ForceLogoutExpiredSession::class, \App\Http\Middleware\EnforceDeviceSessionLimit::class]);
        $this->actingAs($user)->withSession(['current_tenant_id' => $company->id, 'active_branch_scope' => 'all', 'workspace_context' => 'business']);
        return [$company, $user];
    }

    private function sale(Company $company, User $user, ?string $date, float $amount, string $created = '2026-10-22 09:00:00', ?string $deleted = null): int
    {
        $ref = 'CAL-'.bin2hex(random_bytes(4));
        return DB::table('sales')->insertGetId(['company_id' => $company->id, 'user_id' => $user->id,
            'invoice_no' => $ref, 'receipt_no' => $ref.'-R', 'order_number' => $ref.'-O', 'order_date' => $date,
            'total' => $amount, 'subtotal' => $amount, 'paid' => $amount, 'amount_paid' => $amount,
            'terminal_id' => 'POS-1', 'payment_status' => 'paid', 'payment_method' => 'cash',
            'created_at' => $created, 'updated_at' => $created, 'deleted_at' => $deleted]);
    }

    public function test_daily_zero_and_calendar_to_date_totals_exclude_other_business_future_and_deleted_sales(): void
    {
        [$company, $user] = $this->workspace();
        foreach (['2026-10-21' => 100, '2026-10-19' => 200, '2026-10-12' => 300, '2026-10-01' => 400, '2026-01-02' => 500, '2025-12-31' => 600, '2026-10-23' => 700] as $date => $amount) $this->sale($company, $user, $date, $amount);
        $this->sale($company, $user, '2026-10-22', 900, deleted: '2026-10-22 09:01:00');
        $foreign = Company::create(['name' => 'Other business']);
        $this->sale($foreign, $user, '2026-10-22', 9999);
        $response = $this->get(route('workspace.business.dashboard'));
        $response->assertOk()->assertSee('Week to date')->assertSee('Sales performance');
        $this->assertSame(0.0, (float) $response->viewData('metrics')['todayRevenue']);
        $this->assertSame(0.0, (float) $response->viewData('metrics')['totalSales']);
        $rows = $response->viewData('periodSales');
        $this->assertSame(300.0, $rows['week_to_date']['revenue']);
        $this->assertSame(1000.0, $rows['month_to_date']['revenue']);
        $this->assertSame(1500.0, $rows['year_to_date']['revenue']);
        $weeklyDashboard = $this->get(route('workspace.business.dashboard', ['period' => 'last_week']));
        $weeklyDashboard->assertOk()
            ->assertSee('Sales Revenue · Last week')
            ->assertSee('Net Profit · Last week')
            ->assertSee('Expenses · Last week')
            ->assertSee('Inventory Value')
            ->assertDontSee('Inventory Items')
            ->assertDontSee('Expense Load')
            ->assertViewHas('metrics', fn ($m) =>
                (float) $m['totalSales'] === 300.0
                && (float) $m['totalProfit'] === 300.0
                && (float) $m['netProfit'] === 300.0
                && (float) $m['totalExpenses'] === 0.0
                && (float) $m['expenseRatio'] === 0.0
                && (int) $m['totalInvoices'] === 1
            );
    }

    public function test_midnight_rollover_and_new_sales_are_visible_without_clearing_cache(): void
    {
        [$company, $user] = $this->workspace();
        $this->sale($company, $user, '2026-10-22', 100);
        $this->travelTo(\Carbon\Carbon::parse('2026-10-22 22:59:00', 'UTC'));
        $this->get(route('workspace.business.dashboard'))->assertOk()->assertViewHas('metrics', fn ($m) => (float) $m['todayRevenue'] === 100.0);
        $this->travelTo(\Carbon\Carbon::parse('2026-10-22 23:01:00', 'UTC'));
        $this->get(route('workspace.business.dashboard'))->assertOk()->assertViewHas('metrics', fn ($m) => (float) $m['todayRevenue'] === 0.0);
        $this->sale($company, $user, null, 250, '2026-10-22 23:02:00');
        $this->get(route('workspace.business.dashboard'))->assertOk()->assertViewHas('metrics', fn ($m) => (float) $m['todayRevenue'] === 250.0);
        $this->get(route('pos.sales', ['period' => 'today']))->assertOk()->assertViewHas('totalRevenue', 250.0);
    }

    public function test_pos_presets_custom_pagination_and_export_use_the_same_recorded_sale_dates(): void
    {
        [$company, $user] = $this->workspace();
        $this->sale($company, $user, '2026-10-21', 100);
        $this->sale($company, $user, '2026-10-20', 200);
        $this->sale($company, $user, '2026-10-19', 300);
        $this->sale($company, $user, '2026-10-22', 400);
        $response = $this->get(route('pos.sales', ['period' => 'last_3_days', 'per_page' => 1]));
        $response->assertOk()->assertViewHas('totalRevenue', 700.0)->assertViewHas('totalSalesCount', 3);
        $this->assertSame(3, $response->viewData('sales')->total());
        $this->assertCount(1, $response->viewData('sales'));
        $this->assertStringContainsString('period=last_3_days', $response->viewData('sales')->nextPageUrl());
        $this->get(route('pos.sales', ['period' => 'yesterday']))->assertOk()->assertViewHas('totalRevenue', 100.0);
        $this->get(route('pos.sales', ['date_from' => '2026-10-19', 'date_to' => '2026-10-20']))->assertOk()->assertViewHas('totalRevenue', 500.0);
        Excel::fake();
        $this->get(route('pos.sales', ['period' => 'yesterday', 'export' => 'xlsx']))->assertOk();
        Excel::assertDownloaded('pos-sales-'.now()->format('Y-m-d-His').'.xlsx', fn ($export) => $export->collection()->count() === 1 && $export->collection()->first()['sale_date'] === '2026-10-21');
    }

    public function test_pos_report_filters_last_three_hours_and_custom_local_date_time_range(): void
    {
        [$company, $user] = $this->workspace();
        $this->sale($company, $user, '2026-10-22', 50, '2026-10-22 06:59:59');
        $this->sale($company, $user, '2026-10-21', 100, '2026-10-22 08:30:00');
        $this->sale($company, $user, '2026-10-22', 200, '2026-10-22 10:00:00');
        $this->sale($company, $user, '2026-10-22', 400, '2026-10-22 10:00:01');

        $this->get(route('pos.sales', ['period' => 'last_3_hours']))
            ->assertOk()
            ->assertViewHas('totalRevenue', 300.0)
            ->assertViewHas('totalSalesCount', 2);

        $this->get(route('pos.sales', [
            'period' => 'custom',
            'date_time_from' => '2026-10-22T11:30',
            'date_time_to' => '2026-10-22T12:30',
        ]))
            ->assertOk()
            ->assertViewHas('totalRevenue', 0.0);

        $this->travelTo(\Carbon\Carbon::parse('2026-10-22 13:00:00', 'UTC'));
        $this->sale($company, $user, '2026-10-22', 300, '2026-10-22 11:00:00');
        $this->sale($company, $user, '2026-10-22', 500, '2026-10-22 11:30:30');
        $this->get(route('pos.sales', [
            'period' => 'custom',
            'date_time_from' => '2026-10-22T11:30',
            'date_time_to' => '2026-10-22T12:30',
        ]))
            ->assertOk()
            ->assertViewHas('totalRevenue', 800.0);
    }

    public function test_invalid_dates_and_reversed_ranges_return_validation_errors(): void
    {
        $this->workspace();
        foreach ([['period' => 'unknown'], ['period' => 'custom'], ['date_from' => '2026-02-30'], ['date_from' => '2026-10-22', 'date_to' => '2026-10-20'], ['date_time_from' => '2026-10-22T13:00', 'date_time_to' => '2026-10-22T12:00']] as $input) {
            $this->getJson(route('pos.sales', $input))->assertUnprocessable();
            $this->getJson(route('workspace.business.dashboard', $input))->assertUnprocessable();
        }
    }

    public function test_week_and_year_boundaries_and_completed_previous_week_are_calendar_based(): void
    {
        $this->travelTo(\Carbon\Carbon::parse('2027-01-01 12:00:00', 'UTC'));
        $this->assertSame('2026-12-28', ReportingPeriod::preset('week_to_date')->from);
        $this->assertSame('2027-01-01', ReportingPeriod::preset('year_to_date')->from);
        $this->assertSame('2026-12-21', ReportingPeriod::preset('last_week')->from);
        $this->assertSame('2026-12-27', ReportingPeriod::preset('last_week')->to);
        $this->assertSame('2026-12-30', ReportingPeriod::preset('last_3_days')->from);
    }
}
