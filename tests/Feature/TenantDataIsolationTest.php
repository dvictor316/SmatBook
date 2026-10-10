<?php

namespace Tests\Feature;

use App\Models\Traits\TenantScoped;
use App\Models\User;
use App\Traits\Multitenantable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class TenantDataIsolationTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Config::set('database.default', 'sqlite');
        Config::set('database.connections.sqlite.database', ':memory:');
        Config::set('tenancy.enforce_scopes_in_console', true);
        DB::purge('sqlite');
        DB::reconnect('sqlite');

        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('name')->nullable();
            $table->string('email')->unique();
            $table->string('password')->nullable();
            $table->string('role')->nullable();
            $table->unsignedBigInteger('company_id')->nullable();
            $table->rememberToken();
            $table->timestamps();
            $table->softDeletes();
        });

        foreach (['modern_tenant_records', 'legacy_tenant_records'] as $tableName) {
            Schema::create($tableName, function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('company_id')->nullable();
                $table->unsignedBigInteger('user_id')->nullable();
                $table->string('branch_id')->nullable();
                $table->string('branch_name')->nullable();
                $table->string('name');
                $table->timestamps();
            });
        }
    }

    public function test_both_tenant_scopes_hide_foreign_company_records(): void
    {
        $user = $this->tenantUser(10);
        $this->actingAs($user);
        session(['current_tenant_id' => 10, 'active_branch_scope' => 'all']);

        DB::table('modern_tenant_records')->insert([
            ['company_id' => 10, 'user_id' => $user->id, 'name' => 'Mine'],
            ['company_id' => 20, 'user_id' => 999, 'name' => 'Foreign'],
        ]);
        DB::table('legacy_tenant_records')->insert([
            ['company_id' => 10, 'user_id' => $user->id, 'name' => 'Mine'],
            ['company_id' => 20, 'user_id' => 999, 'name' => 'Foreign'],
        ]);

        $this->assertSame(['Mine'], ModernTenantRecord::query()->pluck('name')->all());
        $this->assertSame(['Mine'], LegacyTenantRecord::query()->pluck('name')->all());
    }

    public function test_query_parameters_cannot_enable_all_branch_scope(): void
    {
        $user = $this->tenantUser(10);
        $this->actingAs($user);
        session([
            'current_tenant_id' => 10,
            'active_branch_id' => 'branch-a',
            'active_branch_name' => 'Branch A',
            'active_branch_scope' => 'branch',
        ]);
        request()->query->replace(['all_branches' => '1', 'branch_scope' => 'all']);

        foreach (['modern_tenant_records', 'legacy_tenant_records'] as $tableName) {
            DB::table($tableName)->insert([
                ['company_id' => 10, 'user_id' => $user->id, 'branch_id' => 'branch-a', 'branch_name' => 'Branch A', 'name' => 'Visible'],
                ['company_id' => 10, 'user_id' => $user->id, 'branch_id' => 'branch-b', 'branch_name' => 'Branch B', 'name' => 'Hidden'],
            ]);
        }

        $this->assertSame(['Visible'], ModernTenantRecord::query()->pluck('name')->all());
        $this->assertSame(['Visible'], LegacyTenantRecord::query()->pluck('name')->all());
    }

    public function test_submitted_company_id_is_replaced_by_active_tenant(): void
    {
        $user = $this->tenantUser(10);
        $this->actingAs($user);
        session(['current_tenant_id' => 10, 'active_branch_scope' => 'all']);

        $modern = ModernTenantRecord::query()->create([
            'company_id' => 20,
            'user_id' => $user->id,
            'name' => 'Modern',
        ]);
        $legacy = LegacyTenantRecord::query()->create([
            'company_id' => 20,
            'user_id' => $user->id,
            'name' => 'Legacy',
        ]);

        $this->assertSame(10, (int) $modern->company_id);
        $this->assertSame(10, (int) $legacy->company_id);
        $this->assertDatabaseMissing('modern_tenant_records', ['company_id' => 20]);
        $this->assertDatabaseMissing('legacy_tenant_records', ['company_id' => 20]);
    }

    private function tenantUser(int $companyId): User
    {
        return User::query()->create([
            'name' => 'Tenant Owner',
            'email' => "owner-{$companyId}@example.test",
            'password' => bcrypt('password'),
            'role' => 'admin',
            'company_id' => $companyId,
        ]);
    }
}

class ModernTenantRecord extends Model
{
    use TenantScoped;

    protected $table = 'modern_tenant_records';

    protected $guarded = [];
}

class LegacyTenantRecord extends Model
{
    use Multitenantable;

    protected $table = 'legacy_tenant_records';

    protected $guarded = [];
}
