<?php

namespace Tests\Feature;

use App\Models\Purchase;
use App\Models\Sale;
use App\Support\LedgerService;
use App\Support\TaxReturnPreparationService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class TaxLedgerReportingIntegrityTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Config::set('database.default', 'sqlite');
        Config::set('database.connections.sqlite.database', ':memory:');
        DB::purge('sqlite');
        DB::reconnect('sqlite');

        $this->setUpSchema();
    }

    public function test_sale_tax_posting_creates_balanced_tax_payable_ledger_entries(): void
    {
        $sale = Sale::query()->create([
            'company_id' => 1,
            'branch_id' => 'branch-main',
            'branch_name' => 'Main Branch',
            'invoice_no' => 'INV-1001',
            'order_date' => '2026-05-11',
            'subtotal' => 100000,
            'tax' => 7500,
            'discount' => 0,
            'shipping_cost' => 0,
            'total' => 107500,
            'paid' => 0,
            'amount_paid' => 0,
            'balance' => 107500,
            'payment_status' => 'unpaid',
            'order_status' => 'completed',
        ]);

        LedgerService::postSale($sale);

        $rows = DB::table('transactions')
            ->where('related_type', Sale::class)
            ->where('related_id', $sale->id)
            ->orderBy('id')
            ->get();

        $this->assertCount(4, $rows);
        $this->assertEquals(107500.0, round((float) $rows->sum('debit'), 2));
        $this->assertEquals(107500.0, round((float) $rows->sum('credit'), 2));

        $taxPayableCode = DB::table('accounts')->where('name', 'Tax Payable')->value('code');
        $taxRow = DB::table('transactions')
            ->join('accounts', 'transactions.account_id', '=', 'accounts.id')
            ->where('transactions.related_type', Sale::class)
            ->where('transactions.related_id', $sale->id)
            ->where('accounts.code', $taxPayableCode)
            ->select('transactions.credit')
            ->first();

        $this->assertNotNull($taxRow);
        $this->assertEquals(7500.0, round((float) ($taxRow->credit ?? 0), 2));
    }

    public function test_purchase_tax_posting_creates_balanced_input_vat_ledger_entries(): void
    {
        $purchase = Purchase::query()->create([
            'company_id' => 1,
            'branch_id' => 'branch-main',
            'branch_name' => 'Main Branch',
            'purchase_no' => 'PUR-1001',
            'purchase_date' => '2026-05-11',
            'total_amount' => 107500,
            'tax_amount' => 7500,
            'status' => 'received',
        ]);

        LedgerService::postPurchase($purchase);

        $rows = DB::table('transactions')
            ->where('related_type', Purchase::class)
            ->where('related_id', $purchase->id)
            ->orderBy('id')
            ->get();

        $this->assertCount(4, $rows);
        $this->assertEquals(107500.0, round((float) $rows->sum('debit'), 2));
        $this->assertEquals(107500.0, round((float) $rows->sum('credit'), 2));

        $inputVatCode = DB::table('accounts')->where('name', 'Input VAT')->value('code');
        $taxRow = DB::table('transactions')
            ->join('accounts', 'transactions.account_id', '=', 'accounts.id')
            ->where('transactions.related_type', Purchase::class)
            ->where('transactions.related_id', $purchase->id)
            ->where('accounts.code', $inputVatCode)
            ->select('transactions.debit')
            ->first();

        $this->assertNotNull($taxRow);
        $this->assertEquals(7500.0, round((float) ($taxRow->debit ?? 0), 2));
    }

    public function test_tax_return_preparation_respects_branch_scope_for_sales_and_purchases(): void
    {
        DB::table('sales')->insert([
            [
                'id' => 1,
                'company_id' => 1,
                'branch_id' => 'branch-main',
                'branch_name' => 'Main Branch',
                'invoice_no' => 'INV-1',
                'order_date' => '2026-05-01',
                'subtotal' => 100000,
                'tax' => 7500,
                'tax_amount' => 7500,
                'discount' => 0,
                'shipping_cost' => 0,
                'total' => 107500,
                'total_amount' => 107500,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id' => 2,
                'company_id' => 1,
                'branch_id' => 'branch-alt',
                'branch_name' => 'Alt Branch',
                'invoice_no' => 'INV-2',
                'order_date' => '2026-05-02',
                'subtotal' => 200000,
                'tax' => 15000,
                'tax_amount' => 15000,
                'discount' => 0,
                'shipping_cost' => 0,
                'total' => 215000,
                'total_amount' => 215000,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);

        DB::table('purchases')->insert([
            [
                'id' => 1,
                'company_id' => 1,
                'branch_id' => 'branch-main',
                'branch_name' => 'Main Branch',
                'purchase_no' => 'PUR-1',
                'purchase_date' => '2026-05-03',
                'total_amount' => 53750,
                'tax_amount' => 3750,
                'status' => 'received',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id' => 2,
                'company_id' => 1,
                'branch_id' => 'branch-alt',
                'branch_name' => 'Alt Branch',
                'purchase_no' => 'PUR-2',
                'purchase_date' => '2026-05-04',
                'total_amount' => 107500,
                'tax_amount' => 7500,
                'status' => 'received',
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);

        $service = app(TaxReturnPreparationService::class);

        $mainBranch = $service->prepare('2026-05-01', '2026-05-31', [
            'filing_type' => 'vat',
            'company_id' => 1,
            'branch_scope' => 'branch',
            'branch_id' => 'branch-main',
            'branch_name' => 'Main Branch',
            'currency_code' => 'NGN',
        ]);

        $allBranches = $service->prepare('2026-05-01', '2026-05-31', [
            'filing_type' => 'vat',
            'company_id' => 1,
            'branch_scope' => 'all',
            'currency_code' => 'NGN',
        ]);

        $this->assertEquals(7500.0, $mainBranch['sales_tax']);
        $this->assertEquals(3750.0, $mainBranch['purchase_tax']);
        $this->assertEquals(3750.0, $mainBranch['tax_due']);

        $this->assertEquals(22500.0, $allBranches['sales_tax']);
        $this->assertEquals(11250.0, $allBranches['purchase_tax']);
        $this->assertEquals(11250.0, $allBranches['tax_due']);
    }

    private function setUpSchema(): void
    {
        Schema::create('accounts', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('company_id')->nullable();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->string('branch_id')->nullable();
            $table->string('branch_name')->nullable();
            $table->string('code')->unique();
            $table->string('name');
            $table->string('type')->nullable();
            $table->string('sub_type')->nullable();
            $table->decimal('opening_balance', 15, 2)->default(0);
            $table->decimal('current_balance', 15, 2)->default(0);
            $table->boolean('is_active')->default(true);
            $table->unsignedBigInteger('parent_id')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('transactions', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('account_id');
            $table->unsignedBigInteger('company_id')->nullable();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->string('branch_id')->nullable();
            $table->string('branch_name')->nullable();
            $table->date('transaction_date');
            $table->string('reference')->nullable();
            $table->string('description')->nullable();
            $table->decimal('debit', 15, 2)->default(0);
            $table->decimal('credit', 15, 2)->default(0);
            $table->decimal('balance', 15, 2)->default(0);
            $table->string('transaction_type')->nullable();
            $table->unsignedBigInteger('related_id')->nullable();
            $table->string('related_type')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('sales', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('company_id')->nullable();
            $table->string('branch_id')->nullable();
            $table->string('branch_name')->nullable();
            $table->string('invoice_no')->nullable();
            $table->date('order_date')->nullable();
            $table->decimal('subtotal', 15, 2)->default(0);
            $table->decimal('tax', 15, 2)->default(0);
            $table->decimal('tax_amount', 15, 2)->default(0);
            $table->decimal('discount', 15, 2)->default(0);
            $table->decimal('shipping_cost', 15, 2)->default(0);
            $table->decimal('total', 15, 2)->default(0);
            $table->decimal('total_amount', 15, 2)->default(0);
            $table->decimal('paid', 15, 2)->default(0);
            $table->decimal('amount_paid', 15, 2)->default(0);
            $table->decimal('balance', 15, 2)->default(0);
            $table->string('payment_status')->nullable();
            $table->string('order_status')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('sale_items', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('sale_id')->nullable();
            $table->unsignedBigInteger('product_id')->nullable();
            $table->integer('qty')->default(0);
            $table->decimal('unit_price', 15, 2)->default(0);
            $table->decimal('discount', 15, 2)->default(0);
            $table->decimal('tax', 15, 2)->default(0);
            $table->timestamps();
        });

        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('company_id')->nullable();
            $table->string('name')->nullable();
            $table->decimal('purchase_price', 15, 2)->default(0);
            $table->decimal('price', 15, 2)->default(0);
            $table->timestamps();
        });

        Schema::create('purchases', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('company_id')->nullable();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->string('branch_id')->nullable();
            $table->string('branch_name')->nullable();
            $table->string('purchase_no')->nullable();
            $table->date('purchase_date')->nullable();
            $table->decimal('total_amount', 15, 2)->default(0);
            $table->decimal('tax_amount', 15, 2)->default(0);
            $table->string('status')->nullable();
            $table->timestamps();
        });
    }
}
