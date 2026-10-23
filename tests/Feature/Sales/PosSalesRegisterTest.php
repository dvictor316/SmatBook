<?php

namespace Tests\Feature\Sales;

use App\Models\Company;
use App\Models\Sale;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Illuminate\Http\Request;

class PosSalesRegisterTest extends TestCase
{
    use RefreshDatabase;

    public function test_pos_sales_register_renders_return_links_with_one_server_paginator(): void
    {
        $company = Company::create(['name' => 'Retail Company', 'industry' => 'retail', 'plan' => 'Enterprise']);
        $user = User::factory()->create(['company_id' => $company->id, 'role' => 'admin']);

        foreach (range(1, 16) as $number) {
            Sale::create([
                'company_id' => $company->id, 'user_id' => $user->id, 'terminal_id' => 'POS-1',
                'invoice_no' => 'POS-'.str_pad((string) $number, 4, '0', STR_PAD_LEFT), 'customer_name' => 'Walk-in Customer',
                'subtotal' => 1000, 'total' => 1000, 'paid' => 1000, 'amount_paid' => 1000, 'balance' => 0,
                'payment_method' => 'cash', 'payment_status' => 'paid',
            ]);
        }

        $response = $this->actingAs($user)->withoutMiddleware()->get(route('pos.sales', ['per_page' => 10]));

        $response->assertOk()
            ->assertSee('Sales Register')
            ->assertSee('Process Return')
            ->assertSee('class="pos-content-area pos-sales-compact"', false)
            ->assertSee('class="payment-total-strip"', false);
        $html = $response->getContent();
        $this->assertSame(1, substr_count($html, '<nav aria-label="Results pages"'), 'Expected one Laravel paginator navigation.');
        $this->assertFalse(str_contains($html, 'table-hover datatable'), 'The server-paginated table must not initialize DataTables.');
        $this->assertFalse(str_contains($html, '$posReturnUrl'), 'The obsolete undefined POS return variable leaked into the view.');
    }

    public function test_invoice_list_uses_only_server_pagination_and_static_sales_routes_win(): void
    {
        $company = Company::create(['name' => 'Invoice Company', 'industry' => 'retail', 'plan' => 'Enterprise']);
        $user = User::factory()->create(['company_id' => $company->id, 'role' => 'admin']);
        foreach (range(1, 16) as $number) {
            Sale::create([
                'company_id' => $company->id, 'user_id' => $user->id, 'invoice_no' => 'INV-'.str_pad((string) $number, 4, '0', STR_PAD_LEFT),
                'customer_name' => 'Invoice Customer', 'subtotal' => 2500, 'total' => 2500, 'paid' => 2500, 'amount_paid' => 2500,
                'balance' => 0, 'payment_method' => 'transfer', 'payment_status' => 'paid',
            ]);
        }

        $response = $this->actingAs($user)->withoutMiddleware()->get(route('invoices'));
        $response->assertOk()->assertSee('Invoices');
        $html = $response->getContent();
        $this->assertFalse(str_contains($html, 'table-hover datatable'), 'Invoice server pagination must not also initialize DataTables.');
        $this->assertSame('sales.reports', app('router')->getRoutes()->match(Request::create('/sales/reports', 'GET'))->getName());
        $this->assertSame('sales.chart-data', app('router')->getRoutes()->match(Request::create('/sales/chart-data', 'GET'))->getName());
        $this->assertSame('sales.returnToPos', app('router')->getRoutes()->match(Request::create('/sales/return-to-pos', 'GET'))->getName());
    }

    public function test_pos_receipts_render_standard_and_both_thermal_paper_widths(): void
    {
        $company = Company::create(['name' => 'Receipt Company', 'industry' => 'retail', 'plan' => 'Enterprise']);
        $user = User::factory()->create(['company_id' => $company->id, 'role' => 'admin']);
        $sale = Sale::create([
            'company_id' => $company->id, 'user_id' => $user->id,
            'invoice_no' => 'POS-PRINT-1', 'customer_name' => 'Walk-in Customer',
            'subtotal' => 1000, 'total' => 1000, 'paid' => 1000,
            'amount_paid' => 1000, 'balance' => 0, 'payment_method' => 'cash',
            'payment_status' => 'paid',
        ]);

        $this->actingAs($user)->withoutMiddleware();
        $this->get(route('sales.invoice.print', $sale->id))->assertOk()->assertSee('POS-PRINT-1');
        $this->get(route('sales.invoice.print', ['id' => $sale->id, 'format' => 'thermal', 'paper' => 58]))
            ->assertOk()->assertSee('58mm')->assertSee('POS-PRINT-1');
        $this->get(route('sales.invoice.print', ['id' => $sale->id, 'format' => 'thermal', 'paper' => 80]))
            ->assertOk()->assertSee('80mm')->assertSee('POS-PRINT-1');
    }
}
