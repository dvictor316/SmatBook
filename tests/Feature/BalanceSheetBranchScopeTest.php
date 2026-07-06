<?php

namespace Tests\Feature;

use App\Http\Controllers\BalanceSheetController;
use App\Http\Controllers\ReportController;
use App\Models\Account;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use ReflectionMethod;
use Tests\TestCase;

class BalanceSheetBranchScopeTest extends TestCase
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

    public function test_branch_scope_keeps_legacy_opening_balance_journal_balanced_without_leaking_other_unassigned_rows(): void
    {
        $user = User::query()->create([
            'name' => 'Balance User',
            'email' => 'balance@example.com',
            'password' => bcrypt('password'),
            'company_id' => 1,
        ]);

        $this->actingAs($user);
        session([
            'current_tenant_id' => 1,
            'active_branch_id' => 'branch-main',
            'active_branch_name' => 'Main Branch',
            'active_branch_scope' => 'branch',
        ]);

        DB::table('accounts')->insert([
            [
                'id' => 63,
                'name' => 'Moniepoint MFB',
                'code' => 'BANK-63',
                'type' => 'Asset',
                'sub_type' => 'Current Asset',
                'company_id' => 1,
                'branch_id' => 'branch-main',
                'branch_name' => 'Main Branch',
            ],
            [
                'id' => 99,
                'name' => 'Opening Balance Equity',
                'code' => 'OBE-001',
                'type' => 'Equity',
                'sub_type' => 'Opening Balance Equity',
                'company_id' => 1,
                'branch_id' => null,
                'branch_name' => null,
            ],
            [
                'id' => 70,
                'name' => 'Legacy Unassigned Bank',
                'code' => 'BANK-70',
                'type' => 'Asset',
                'sub_type' => 'Current Asset',
                'company_id' => 1,
                'branch_id' => null,
                'branch_name' => null,
            ],
        ]);

        DB::table('transactions')->insert([
            [
                'id' => 1,
                'account_id' => 63,
                'company_id' => 1,
                'user_id' => $user->id,
                'branch_id' => 'branch-main',
                'branch_name' => 'Main Branch',
                'transaction_date' => '2026-05-10',
                'reference' => 'OB-ACCT-63',
                'description' => 'Opening balance: Moniepoint MFB',
                'debit' => 5000000.00,
                'credit' => 0.00,
                'balance' => 0.00,
                'transaction_type' => Transaction::TYPE_OPENING_BALANCE,
                'related_id' => 63,
                'related_type' => Account::class,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id' => 2,
                'account_id' => 99,
                'company_id' => 1,
                'user_id' => $user->id,
                'branch_id' => null,
                'branch_name' => null,
                'transaction_date' => '2026-05-10',
                'reference' => 'OB-ACCT-63',
                'description' => 'Opening balance: Moniepoint MFB',
                'debit' => 0.00,
                'credit' => 5000000.00,
                'balance' => 0.00,
                'transaction_type' => Transaction::TYPE_OPENING_BALANCE,
                'related_id' => 63,
                'related_type' => Account::class,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id' => 3,
                'account_id' => 70,
                'company_id' => 1,
                'user_id' => $user->id,
                'branch_id' => null,
                'branch_name' => null,
                'transaction_date' => '2026-05-10',
                'reference' => 'OB-ACCT-70',
                'description' => 'Opening balance: Legacy Unassigned Bank',
                'debit' => 250.00,
                'credit' => 0.00,
                'balance' => 0.00,
                'transaction_type' => Transaction::TYPE_OPENING_BALANCE,
                'related_id' => 70,
                'related_type' => Account::class,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id' => 4,
                'account_id' => 99,
                'company_id' => 1,
                'user_id' => $user->id,
                'branch_id' => null,
                'branch_name' => null,
                'transaction_date' => '2026-05-10',
                'reference' => 'OB-ACCT-70',
                'description' => 'Opening balance: Legacy Unassigned Bank',
                'debit' => 0.00,
                'credit' => 250.00,
                'balance' => 0.00,
                'transaction_type' => Transaction::TYPE_OPENING_BALANCE,
                'related_id' => 70,
                'related_type' => Account::class,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);

        $controller = app(BalanceSheetController::class);
        $request = Request::create('/balance-sheet', 'GET', ['date' => '2026-05-10']);
        $request->setUserResolver(fn () => $user);

        $query = Transaction::withoutGlobalScopes()
            ->select('id', 'debit', 'credit')
            ->whereNull('deleted_at')
            ->where('transaction_date', '<=', '2026-05-10');

        $this->invokePrivate($controller, 'applyTransactionScope', [$query, $request]);
        $this->invokePrivate($controller, 'applyLegacyOpeningBalanceBranchScope', [$query, [
            'id' => 'branch-main',
            'name' => 'Main Branch',
            'scope' => 'branch',
        ], 'transactions']);

        $rows = $query->orderBy('id')->get();

        $this->assertSame([1, 2], $rows->pluck('id')->all());
        $this->assertSame(5000000.0, round((float) $rows->sum('debit'), 2));
        $this->assertSame(5000000.0, round((float) $rows->sum('credit'), 2));
    }

    public function test_all_branch_scope_includes_assigned_and_legacy_unassigned_rows(): void
    {
        $user = User::query()->create([
            'name' => 'All Branch User',
            'email' => 'all-branch@example.com',
            'password' => bcrypt('password'),
            'company_id' => 1,
        ]);

        $this->actingAs($user);
        session([
            'current_tenant_id' => 1,
            'active_branch_id' => 'branch-main',
            'active_branch_name' => 'Main Branch',
            'active_branch_scope' => 'branch',
        ]);

        DB::table('accounts')->insert([
            [
                'id' => 1,
                'name' => 'Main Branch Cash',
                'code' => 'CASH-1',
                'type' => 'Asset',
                'sub_type' => 'Current Asset',
                'company_id' => 1,
                'branch_id' => 'branch-main',
                'branch_name' => 'Main Branch',
            ],
            [
                'id' => 2,
                'name' => 'Legacy Shared Cash',
                'code' => 'CASH-2',
                'type' => 'Asset',
                'sub_type' => 'Current Asset',
                'company_id' => 1,
                'branch_id' => null,
                'branch_name' => null,
            ],
        ]);

        DB::table('transactions')->insert([
            [
                'id' => 10,
                'account_id' => 1,
                'company_id' => 1,
                'user_id' => $user->id,
                'branch_id' => 'branch-main',
                'branch_name' => 'Main Branch',
                'transaction_date' => '2026-05-10',
                'reference' => 'MAIN-1',
                'description' => 'Assigned branch row',
                'debit' => 100.00,
                'credit' => 0.00,
                'balance' => 0.00,
                'transaction_type' => Transaction::TYPE_JOURNAL,
                'related_id' => 1,
                'related_type' => Account::class,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id' => 11,
                'account_id' => 2,
                'company_id' => 1,
                'user_id' => $user->id,
                'branch_id' => null,
                'branch_name' => null,
                'transaction_date' => '2026-05-10',
                'reference' => 'LEGACY-1',
                'description' => 'Unassigned legacy row',
                'debit' => 200.00,
                'credit' => 0.00,
                'balance' => 0.00,
                'transaction_type' => Transaction::TYPE_JOURNAL,
                'related_id' => 2,
                'related_type' => Account::class,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);

        $controller = app(BalanceSheetController::class);
        $request = Request::create('/balance-sheet', 'GET', ['branch_id' => 'all', 'date' => '2026-05-10']);
        $request->setUserResolver(fn () => $user);

        $activeBranch = $this->invokePrivate($controller, 'resolveActiveBranch', [$request]);

        $this->assertSame('all', $activeBranch['scope']);
        $this->assertNull($activeBranch['id']);
        $this->assertNull($activeBranch['name']);
        $this->assertSame('all', session('active_branch_scope'));

        $query = Transaction::withoutGlobalScopes()
            ->select('id')
            ->whereNull('deleted_at')
            ->where('transaction_date', '<=', '2026-05-10');

        $this->invokePrivate($controller, 'applyTransactionScope', [$query, $request]);

        $this->assertSame([10, 11], $query->orderBy('id')->pluck('id')->all());
    }

    public function test_balance_sheet_includes_unposted_bank_opening_balance(): void
    {
        $user = User::query()->create([
            'name' => 'Bank Opening User',
            'email' => 'bank-opening@example.com',
            'password' => bcrypt('password'),
            'company_id' => 1,
        ]);

        $this->actingAs($user);
        session([
            'current_tenant_id' => 1,
            'active_branch_id' => 'branch-main',
            'active_branch_name' => 'Main Branch',
            'active_branch_scope' => 'branch',
        ]);

        DB::table('accounts')->insert([
            'id' => 501,
            'name' => 'Access Bank',
            'code' => 'BANK-501',
            'type' => 'Asset',
            'sub_type' => 'Current Asset',
            'company_id' => 1,
            'branch_id' => 'branch-main',
            'branch_name' => 'Main Branch',
            'opening_balance' => 125000.00,
            'current_balance' => 125000.00,
            'created_at' => '2026-05-01 00:00:00',
            'updated_at' => '2026-05-01 00:00:00',
        ]);

        $controller = app(BalanceSheetController::class);
        $request = Request::create('/balance-sheet', 'GET', ['date' => '2026-05-10']);
        $request->setUserResolver(fn () => $user);

        $view = $controller->index($request);
        $currentAssets = $view->getData()['currentAssets'];

        $bankLine = $currentAssets->firstWhere('id', 501);

        $this->assertNotNull($bankLine);
        $this->assertSame(125000.0, round((float) $bankLine->balance, 2));
    }

    public function test_profit_loss_uses_purchase_item_total_when_purchase_header_total_is_zero(): void
    {
        $user = User::query()->create([
            'name' => 'Profit User',
            'email' => 'profit@example.com',
            'password' => bcrypt('password'),
            'company_id' => 1,
        ]);

        $this->actingAs($user);
        session([
            'current_tenant_id' => 1,
            'active_branch_scope' => 'branch',
        ]);

        DB::table('purchases')->insert([
            'id' => 801,
            'purchase_no' => 'PUR-ZERO',
            'total_amount' => 0,
            'status' => 'received',
            'company_id' => 1,
            'purchase_date' => '2026-05-10',
            'purchase_type' => 'inventory',
            'created_at' => '2026-05-10 00:00:00',
            'updated_at' => '2026-05-10 00:00:00',
        ]);

        DB::table('purchase_items')->insert([
            'purchase_id' => 801,
            'qty' => 4,
            'unit_price' => 2500,
            'line_total' => 10000,
            'created_at' => '2026-05-10 00:00:00',
            'updated_at' => '2026-05-10 00:00:00',
        ]);

        $controller = app(ReportController::class);
        $request = Request::create('/reports/profit-loss-list', 'GET', [
            'start_date' => '2026-05-01',
            'end_date' => '2026-05-31',
        ]);
        $request->setUserResolver(fn () => $user);
        $request->setLaravelSession($this->app['session.store']);
        app()->instance('request', $request);

        $view = $controller->profit_loss_list($request);
        $totals = $view->getData()['totals'];

        $this->assertSame(10000.0, round((float) $totals->total_purchase_expense, 2));
    }

    private function invokePrivate(object $target, string $method, array $arguments = [])
    {
        $reflection = new ReflectionMethod($target, $method);
        $reflection->setAccessible(true);

        return $reflection->invokeArgs($target, $arguments);
    }

    private function setUpSchema(): void
    {
        Schema::dropIfExists('transactions');
        Schema::dropIfExists('purchase_items');
        Schema::dropIfExists('purchases');
        Schema::dropIfExists('expenses');
        Schema::dropIfExists('sales');
        Schema::dropIfExists('accounts');
        Schema::dropIfExists('companies');
        Schema::dropIfExists('users');

        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('name')->nullable();
            $table->string('email')->unique();
            $table->string('password')->nullable();
            $table->unsignedBigInteger('company_id')->nullable();
            $table->rememberToken()->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('accounts', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('code')->unique();
            $table->string('type')->nullable();
            $table->string('sub_type')->nullable();
            $table->unsignedBigInteger('company_id')->nullable();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->string('branch_id')->nullable();
            $table->string('branch_name')->nullable();
            $table->boolean('is_active')->default(true);
            $table->decimal('opening_balance', 15, 2)->default(0);
            $table->decimal('current_balance', 15, 2)->default(0);
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('companies', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->string('name')->nullable();
            $table->string('email')->nullable();
            $table->string('domain')->nullable();
            $table->string('plan')->nullable();
            $table->string('status')->nullable();
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
            $table->decimal('total_amount', 15, 2)->default(0);
            $table->decimal('amount_paid', 15, 2)->default(0);
            $table->date('order_date')->nullable();
            $table->string('order_status')->nullable();
            $table->unsignedBigInteger('company_id')->nullable();
            $table->string('branch_id')->nullable();
            $table->string('branch_name')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('purchases', function (Blueprint $table) {
            $table->id();
            $table->string('purchase_no')->nullable();
            $table->decimal('total_amount', 15, 2)->default(0);
            $table->string('status')->nullable();
            $table->date('purchase_date')->nullable();
            $table->string('purchase_type')->nullable();
            $table->unsignedBigInteger('company_id')->nullable();
            $table->string('branch_id')->nullable();
            $table->string('branch_name')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('purchase_items', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('purchase_id');
            $table->integer('qty')->default(0);
            $table->decimal('unit_price', 15, 2)->default(0);
            $table->decimal('line_total', 15, 2)->default(0);
            $table->timestamps();
        });

        Schema::create('expenses', function (Blueprint $table) {
            $table->id();
            $table->decimal('amount', 15, 2)->default(0);
            $table->string('category')->nullable();
            $table->string('reference')->nullable();
            $table->text('notes')->nullable();
            $table->string('status')->nullable();
            $table->date('expense_date')->nullable();
            $table->unsignedBigInteger('company_id')->nullable();
            $table->string('branch_id')->nullable();
            $table->string('branch_name')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }
}
