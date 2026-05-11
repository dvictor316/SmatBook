<?php

namespace Tests\Feature;

use App\Http\Controllers\BalanceSheetController;
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

    public function test_all_branch_scope_is_strict_and_does_not_treat_all_as_a_real_branch_id(): void
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

        $this->assertSame([10], $query->orderBy('id')->pluck('id')->all());
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
    }
}
