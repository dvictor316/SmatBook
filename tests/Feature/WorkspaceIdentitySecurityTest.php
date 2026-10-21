<?php

namespace Tests\Feature;

use App\Http\Middleware\IdentifyTenant;
use App\Http\Middleware\VerifyTenantSession;
use App\Models\Company;
use App\Models\Category;
use App\Models\Domain;
use App\Models\Subscription;
use App\Models\User;
use App\Support\BusinessWorkspaceResolver;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\View;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class WorkspaceIdentitySecurityTest extends TestCase
{
    use RefreshDatabase;

    public function test_super_admin_cannot_inherit_a_clients_workspace_or_primary_assignment(): void
    {
        $client = User::factory()->create();
        $foreign = $this->business($client, 'Client Private Business');
        $admin = User::factory()->create(['role' => 'super_admin', 'company_id' => $foreign->id]);
        $this->actingAs($admin);
        session(['workspace_context' => 'business', 'current_tenant_id' => $foreign->id,
            'current_tenant_name' => $foreign->name, 'active_branch_scope' => 'all']);
        $admin->setRelation('company', $foreign);

        $this->verifySession();

        $this->assertNull(session('current_tenant_id'));
        $this->assertNull(session('current_tenant_name'));
        $this->assertNull(session('active_branch_scope'));
        $this->assertNull($admin->company);
        $this->assertFalse(app(BusinessWorkspaceResolver::class)->canAccess($admin, $foreign->id));
        // Repair is request-local; it does not reassign businesses in the database.
        $this->assertSame($foreign->id, (int) $admin->fresh()->company_id);
    }

    public function test_valid_workspace_refreshes_a_stale_label_and_preloaded_company(): void
    {
        $owner = User::factory()->create(['role' => 'super_admin']);
        $own = $this->business($owner, 'My Own Business');
        $foreign = $this->business(User::factory()->create(), 'Other Business');
        $owner->forceFill(['company_id' => $own->id])->save();
        $this->actingAs($owner);
        session(['workspace_context' => 'business', 'current_tenant_id' => $own->id,
            'current_tenant_name' => $foreign->name]);
        $owner->setRelation('company', $foreign);

        $this->verifySession();

        $this->assertSame($own->name, session('current_tenant_name'));
        $this->assertTrue($owner->company->is($own));
    }

    public function test_platform_context_clears_business_and_branch_identity(): void
    {
        $admin = User::factory()->create(['role' => 'super_admin']);
        $own = $this->business($admin, 'Own Business');
        $admin->forceFill(['company_id' => $own->id])->save();
        $this->actingAs($admin);
        session(['workspace_context' => 'platform', 'current_tenant_id' => $own->id,
            'current_tenant_name' => $own->name, 'active_branch_id' => 'old-branch']);

        $this->verifySession();

        $this->assertNull(session('current_tenant_id'));
        $this->assertNull(session('current_tenant_name'));
        $this->assertNull(session('active_branch_id'));
        $this->assertNull($admin->company);
    }

    public function test_switching_businesses_replaces_the_loaded_company_relation(): void
    {
        $owner = User::factory()->create();
        $first = $this->business($owner, 'First');
        $second = $this->business($owner, 'Second');
        $owner->setRelation('company', $first);
        $this->actingAs($owner);

        app(BusinessWorkspaceResolver::class)->activate($owner, $second);

        $this->assertTrue($owner->company->is($second));
        $this->assertSame('Second', session('current_tenant_name'));
    }

    public function test_domain_record_id_is_never_used_as_the_company_id(): void
    {
        config(['app.domain' => 'smartprobook.com']);
        $owner = User::factory()->create();
        $foreign = $this->business(User::factory()->create(), 'Foreign');
        $own = $this->business($owner, 'My Business');
        $subscription = Subscription::withoutGlobalScope('tenant')->create([
            'user_id' => $owner->id, 'company_id' => $own->id, 'plan' => 'Enterprise',
            'amount' => 0, 'status' => 'Active', 'payment_status' => 'paid',
        ]);
        Schema::table('domains', function (Blueprint $table) {
            $table->unsignedBigInteger('tenant_id')->nullable();
            $table->unsignedBigInteger('subscription_id')->nullable();
        });
        $domain = Domain::withoutGlobalScope('tenant')->create([
            'tenant_id' => $owner->id, 'subscription_id' => $subscription->id, 'package_name' => 'Enterprise',
            'domain_name' => 'my-business', 'customer_name' => 'My Business', 'email' => $owner->email,
        ]);
        $this->assertSame($foreign->id, $domain->id);
        $this->actingAs($owner);

        app(IdentifyTenant::class)->handle(Request::create('https://my-business.smartprobook.com/dashboard'), fn () => response('ok'));

        $this->assertSame($own->id, session('current_tenant_id'));
        $this->assertTrue($owner->company->is($own));
    }

    public function test_foreign_subdomain_is_rejected_even_for_super_admin(): void
    {
        config(['app.domain' => 'smartprobook.com']);
        $client = User::factory()->create();
        $company = $this->business($client, 'Client');
        Domain::withoutGlobalScope('tenant')->create([
            'package_name' => 'Enterprise', 'domain_name' => 'client',
            'customer_name' => 'Client', 'email' => $client->email,
        ]);
        $this->actingAs(User::factory()->create(['role' => 'super_admin']));
        $request = Request::create('https://client.smartprobook.com/dashboard', 'GET', [], [], [], ['HTTP_ACCEPT' => 'application/json']);

        $response = app(IdentifyTenant::class)->handle($request, fn () => response('private data'));

        $this->assertSame(403, $response->getStatusCode());
        $this->assertNotSame($company->id, session('current_tenant_id'));
    }

    public function test_view_composer_does_not_reuse_another_authenticated_users_identity(): void
    {
        $first = User::factory()->create();
        $second = User::factory()->create();
        $this->actingAs($first);
        $firstView = View::file(__FILE__);
        View::callComposer($firstView);
        $this->assertSame($first->id, $firstView->getData()['currentUser']->id);

        $this->actingAs($second);
        $secondView = View::file(__FILE__);
        View::callComposer($secondView);
        $this->assertSame($second->id, $secondView->getData()['currentUser']->id);
    }

    public function test_poisoned_session_is_repaired_before_reading_or_writing_business_records(): void
    {
        $admin = User::factory()->create(['role' => 'super_admin']);
        $own = $this->business($admin, 'Own');
        $client = User::factory()->create();
        $foreign = $this->business($client, 'Client');
        $admin->forceFill(['company_id' => $own->id])->save();
        Category::create(['company_id' => $own->id, 'user_id' => $admin->id, 'name' => 'Own Category']);
        Category::create(['company_id' => $foreign->id, 'user_id' => $client->id, 'name' => 'Private Client Category']);
        $this->actingAs($admin);
        config(['tenancy.enforce_scopes_in_console' => true]);
        session(['workspace_context' => 'business', 'current_tenant_id' => $foreign->id]);

        $this->verifySession();

        $this->assertSame($own->id, session('current_tenant_id'));
        $this->assertSame(['Own Category'], Category::pluck('name')->all());
        $created = Category::create(['company_id' => $foreign->id, 'user_id' => $admin->id, 'name' => 'New Category']);
        $this->assertSame($own->id, (int) $created->company_id);
        $this->assertDatabaseMissing('categories', ['company_id' => $foreign->id, 'name' => 'New Category']);
    }

    public function test_subscription_lookup_ignores_a_foreign_session_even_before_middleware(): void
    {
        $owner = User::factory()->create();
        $own = $this->business($owner, 'Own');
        $client = User::factory()->create();
        $foreign = $this->business($client, 'Client');
        $owner->forceFill(['company_id' => $own->id])->save();
        foreach ([[$owner, $own], [$client, $foreign]] as [$user, $company]) {
            Subscription::withoutGlobalScope('tenant')->create([
                'user_id' => $user->id, 'company_id' => $company->id, 'plan' => 'Enterprise',
                'amount' => 0, 'status' => 'Active', 'payment_status' => 'paid',
            ]);
        }
        $this->actingAs($owner);
        session(['current_tenant_id' => $foreign->id]);

        $this->assertSame($own->id, (int) Subscription::resolveCurrentForUser($owner)->company_id);
    }

    public function test_foreign_route_model_is_not_bound_before_workspace_validation(): void
    {
        $this->withoutMiddleware([
            \App\Http\Middleware\ForceLogoutExpiredSession::class,
            \App\Http\Middleware\EnforceDeviceSessionLimit::class,
        ]);
        Route::middleware('web')->get('/__security/categories/{securityCategory}', function (Category $securityCategory) {
            return response()->json(['name' => $securityCategory->name]);
        });
        $admin = User::factory()->create(['role' => 'super_admin']);
        $own = $this->business($admin, 'Own');
        $client = User::factory()->create();
        $foreign = $this->business($client, 'Client');
        $admin->forceFill(['company_id' => $own->id])->save();
        $category = Category::create(['company_id' => $foreign->id, 'user_id' => $client->id, 'name' => 'Secret Client Category']);
        config(['tenancy.enforce_scopes_in_console' => true]);

        $this->actingAs($admin)
            ->withSession(['workspace_context' => 'business', 'current_tenant_id' => $foreign->id])
            ->getJson('/__security/categories/'.$category->id)
            ->assertNotFound()
            ->assertDontSee('Secret Client Category');
        $this->assertSame($own->id, session('current_tenant_id'));
    }

    private function verifySession(): void
    {
        app(VerifyTenantSession::class)->handle(request(), fn () => response('ok'));
    }

    private function business(User $owner, string $name): Company
    {
        return Company::withoutGlobalScope('tenant')->create([
            'user_id' => $owner->id, 'owner_id' => $owner->id,
            'name' => $name, 'domain_prefix' => str($name)->slug(), 'status' => 'active',
        ]);
    }
}
