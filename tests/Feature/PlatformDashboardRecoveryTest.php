<?php

namespace Tests\Feature;

use App\Http\Controllers\SettingController;
use App\Http\Middleware\EnforceDeviceSessionLimit;
use App\Http\Middleware\ForceLogoutExpiredSession;
use App\Models\Company;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Tests\TestCase;

class PlatformDashboardRecoveryTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware([ForceLogoutExpiredSession::class, EnforceDeviceSessionLimit::class]);
    }

    public function test_platform_dashboard_renders_with_no_business_or_branch_selected(): void
    {
        $admin = User::factory()->create(['role' => 'super_admin', 'company_id' => null]);
        Setting::create(['key' => 'company_name', 'value' => 'Foreign Cached Branding']);
        $this->actingAs($admin)->withSession(['workspace_context' => 'platform'])
            ->get(route('super_admin.dashboard'))
            ->assertOk()
            ->assertSee('Platform Module Catalogue')
            ->assertSee('<title>SmartProbook Dashboard</title>', false)
            ->assertDontSee('Please complete setup and select a branch')
            ->assertDontSee('Foreign Cached Branding');
        $this->assertNull(session('current_tenant_id'));
        $this->assertNull(session('active_branch_id'));
    }

    public function test_business_profiles_are_separate_and_do_not_inherit_global_branding(): void
    {
        $first = User::factory()->create();
        $second = User::factory()->create();
        $firstCompany = $this->business($first, 'First Company');
        $secondCompany = $this->business($second, 'Second Company');
        Setting::create(['key' => 'company_name', 'value' => 'Old Shared Name']);
        Setting::create(['key' => 'company_name_company_'.$firstCompany->id, 'value' => 'First Brand']);
        Setting::create(['key' => 'favicon_company_'.$firstCompany->id, 'value' => 'first-private.png']);
        $this->actingAs($first);
        session(['current_tenant_id' => $firstCompany->id]);
        $this->assertSame('First Brand', Setting::businessProfile()['company_name']);

        $this->actingAs($second);
        // Deliberately retain the other account's session: authorization must reject it.
        $profile = Setting::businessProfile();
        $this->assertSame('Second Company', $profile['company_name']);
        $this->assertNull($profile['favicon']);
        $this->assertSame($secondCompany->id, (int) $second->company_id);
    }

    public function test_profile_updates_do_not_overwrite_another_business_or_platform_settings(): void
    {
        $owner = User::factory()->create();
        $company = $this->business($owner, 'Own Company');
        Setting::create(['key' => 'company_name', 'value' => 'Legacy Shared Name']);
        Setting::create(['key' => 'company_name_company_9999', 'value' => 'Other Business']);
        Setting::create(['key' => 'mail_smtp_enabled', 'value' => '1']);
        $this->actingAs($owner);
        session(['current_tenant_id' => $company->id]);
        app(SettingController::class)->update(Request::create('/settings', 'POST', ['company_name' => 'New Own Name']));

        $this->assertSame('New Own Name', Setting::get('company_name_company_'.$company->id));
        $this->assertSame('Legacy Shared Name', Setting::get('company_name'));
        $this->assertSame('Other Business', Setting::get('company_name_company_9999'));
        $this->assertSame('1', Setting::get('mail_smtp_enabled'));
        $settings = app(SettingController::class)->company_settings()->getData()['settings'];
        $this->assertFalse($settings->offsetExists('company_name_company_9999'));
        $this->assertSame('New Own Name', $settings['company_name']);
    }

    public function test_submitted_settings_key_cannot_target_another_business(): void
    {
        $owner = User::factory()->create();
        $this->business($owner, 'Own Company');
        $this->actingAs($owner);
        $this->expectException(\Symfony\Component\HttpKernel\Exception\HttpException::class);
        app(SettingController::class)->update(Request::create('/settings', 'POST', [
            'company_name_company_9999' => 'Attempted overwrite',
        ]));
    }

    private function business(User $owner, string $name): Company
    {
        $company = Company::withoutGlobalScope('tenant')->create([
            'user_id' => $owner->id, 'owner_id' => $owner->id, 'name' => $name,
        ]);
        $owner->forceFill(['company_id' => $company->id])->save();
        return $company;
    }
}
