<?php

namespace Tests\Feature;

use App\Http\Middleware\EnforceDeviceSessionLimit;
use App\Http\Middleware\ForceLogoutExpiredSession;
use App\Http\Middleware\RequireActiveBranch;
use App\Http\Middleware\VerifyTenantSession;
use App\Models\Company;
use App\Models\Plan;
use App\Models\Subscription;
use App\Models\User;
use App\Support\SchemaColumnCache;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TrialPlanAccessTest extends TestCase
{
    use RefreshDatabase;

    public function test_enterprise_trial_shows_and_opens_financial_reports_from_its_selected_plan(): void
    {
        $user = $this->workspaceUser();
        $enterprise = Plan::create([
            'name' => 'Enterprise Solo',
            'billing_cycle' => 'Yearly',
            'price' => 200000,
            'is_active' => true,
        ]);
        $this->createTrial([
            'user_id' => $user->id,
            'company_id' => $user->company_id,
            'plan_id' => $enterprise->id,
            'billing_cycle' => 'Yearly',
            'amount' => 200000,
        ]);

        $this->get(route('reports.hub'))->assertOk()
            ->assertViewHas('reportAccess', 'enterprise')
            ->assertSee('Balance Sheet')
            ->assertSee('Trial Balance')
            ->assertSee('Cash Flow');

        foreach (['balance-sheet', 'trial-balance', 'reports.cash-flow'] as $report) {
            $this->get(route($report))->assertOk();
        }
        $this->get(route('reports.cash-flow.export'))->assertDownload();
    }

    public function test_a_basic_trial_keeps_the_basic_report_catalogue_and_route_entitlements(): void
    {
        $user = $this->workspaceUser();
        $this->createTrial([
            'user_id' => $user->id,
            'company_id' => $user->company_id,
            'plan' => 'Basic',
            'amount' => 80000,
        ]);

        $this->get(route('reports.hub', ['tab' => 'management']))->assertOk()
            ->assertViewHas('reportAccess', 'basic')
            ->assertViewHas('allowedTabs', ['standard'])
            ->assertSee('Cash Flow')->assertDontSee('Trial Balance')->assertDontSee('Balance Sheet');
        $this->get(route('reports.cash-flow'))->assertOk();
        $this->get(route('trial-balance'))->assertRedirect(route('user.dashboard'));
        foreach (['balance-sheet', 'balance-sheet-summary', 'balance-sheet-comparison', 'balance-sheet.export'] as $report) {
            $this->get(route($report))->assertRedirect(route('user.dashboard'));
        }
    }

    public function test_guided_demo_can_switch_from_a_limited_preview_to_enterprise_reports(): void
    {
        $this->workspaceUser(demo: true);

        $this->get(route('demo.workspace.plan', ['plan' => 'starter']))->assertRedirect(route('user.dashboard'));
        $this->get(route('reports.hub'))->assertOk()->assertViewHas('reportAccess', 'starter');
        $this->get(route('balance-sheet'))->assertRedirect(route('user.dashboard'));
        $this->get(route('reports.cash-flow.export'))->assertRedirect(route('user.dashboard'));

        $this->get(route('demo.workspace.plan', ['plan' => 'enterprise']))->assertRedirect(route('user.dashboard'));
        $this->get(route('reports.hub', ['tab' => 'management']))->assertOk()
            ->assertViewHas('reportAccess', 'enterprise')
            ->assertSee('Balance Sheet')->assertSee('Trial Balance')->assertSee('Cash Flow');
        $this->get(route('balance-sheet'))->assertOk();
        $this->get(route('trial-balance'))->assertOk();
        $this->get(route('reports.cash-flow'))->assertOk();
    }

    private function createTrial(array $plan): void
    {
        Subscription::create(app(SchemaColumnCache::class)->filterPayload('subscriptions', Subscription::trialPayload() + $plan));
    }

    private function workspaceUser(bool $demo = false): User
    {
        $user = User::factory()->create(['role' => 'admin', 'status' => 'active']);
        $company = Company::create([
            'name' => 'Trial Plan Reports',
            'user_id' => $user->id,
            'owner_id' => $user->id,
            'plan' => 'Basic',
            'is_demo' => $demo,
            'status' => $demo ? 'demo' : 'active',
        ]);
        $user->update(['company_id' => $company->id]);
        $this->withoutMiddleware([
            RequireActiveBranch::class,
            ForceLogoutExpiredSession::class,
            EnforceDeviceSessionLimit::class,
            VerifyTenantSession::class,
        ])->actingAs($user)->withSession([
            'current_tenant_id' => $company->id,
            'workspace_context' => 'business',
            'active_branch_scope' => 'all',
        ]);

        return $user;
    }
}
