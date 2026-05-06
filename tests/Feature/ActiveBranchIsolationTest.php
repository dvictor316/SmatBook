<?php

namespace Tests\Feature;

use App\Http\Middleware\VerifyTenantSession;
use App\Models\Setting;
use App\Models\User;
use App\Support\ActiveBranchResolver;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Tests\TestCase;

class ActiveBranchIsolationTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Config::set('database.default', 'sqlite');
        Config::set('database.connections.sqlite.database', ':memory:');
        DB::purge('sqlite');
        DB::reconnect('sqlite');

        $this->setUpSchema();
        $this->registerTestRoutes();
    }

    public function test_resolver_replaces_stale_branch_from_another_tenant(): void
    {
        $user = User::query()->create([
            'name' => 'Tenant User',
            'email' => 'tenant@example.com',
            'password' => bcrypt('password'),
            'company_id' => 1,
        ]);

        Setting::query()->create([
            'key' => 'branches_json_company_1',
            'value' => json_encode([
                ['id' => 'branch-a', 'name' => 'Branch A', 'is_active' => true],
            ]),
        ]);

        Setting::query()->create([
            'key' => 'branches_json_company_2',
            'value' => json_encode([
                ['id' => 'branch-b', 'name' => 'Branch B', 'is_active' => true],
            ]),
        ]);

        $this->actingAs($user);
        $this->withSession([
            'current_tenant_id' => 1,
            'active_branch_id' => 'branch-b',
            'active_branch_name' => 'Branch B',
        ]);

        $resolved = app(ActiveBranchResolver::class)->ensureSession($user);

        $this->assertTrue($resolved);
        $this->assertSame('branch-a', session('active_branch_id'));
        $this->assertSame('Branch A', session('active_branch_name'));
    }

    public function test_verify_tenant_session_middleware_resets_invalid_branch_context(): void
    {
        $user = User::query()->create([
            'name' => 'Tenant User',
            'email' => 'tenant2@example.com',
            'password' => bcrypt('password'),
            'company_id' => 7,
        ]);

        Setting::query()->create([
            'key' => 'branches_json_company_7',
            'value' => json_encode([
                ['id' => 'branch-7', 'name' => 'Branch Seven', 'is_active' => true],
            ]),
        ]);

        Setting::query()->create([
            'key' => 'branches_json_company_8',
            'value' => json_encode([
                ['id' => 'branch-8', 'name' => 'Branch Eight', 'is_active' => true],
            ]),
        ]);

        $response = $this
            ->actingAs($user)
            ->withSession([
                'current_tenant_id' => 8,
                'active_branch_id' => 'branch-8',
                'active_branch_name' => 'Branch Eight',
            ])
            ->get('/__test/verify-tenant-session');

        $response->assertOk()
            ->assertJson([
                'tenant_id' => 7,
                'branch_id' => 'branch-7',
                'branch_name' => 'Branch Seven',
            ]);
    }

    private function setUpSchema(): void
    {
        Schema::dropIfExists('settings');
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

        Schema::create('settings', function (Blueprint $table) {
            $table->id();
            $table->string('key')->unique();
            $table->longText('value')->nullable();
            $table->timestamps();
        });
    }

    private function registerTestRoutes(): void
    {
        if (Route::has('__test.verify-tenant-session')) {
            return;
        }

        Route::middleware(VerifyTenantSession::class)
            ->get('/__test/verify-tenant-session', function () {
                return response()->json([
                    'tenant_id' => (int) session('current_tenant_id', 0),
                    'branch_id' => session('active_branch_id'),
                    'branch_name' => session('active_branch_name'),
                ]);
            })
            ->name('__test.verify-tenant-session');
    }
}
