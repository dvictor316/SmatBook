<?php

namespace Tests\Unit;

use App\Support\DemoSettings;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class DemoSettingsTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Config::set('database.default', 'sqlite');
        Config::set('database.connections.sqlite.database', ':memory:');

        DB::purge('sqlite');
        DB::reconnect('sqlite');

        Schema::dropAllTables();

        Schema::create('settings', function ($table) {
            $table->id();
            $table->string('key')->unique();
            $table->text('value')->nullable();
            $table->timestamps();
        });
    }

    public function test_it_returns_expected_defaults(): void
    {
        $settings = app(DemoSettings::class);

        $this->assertTrue($settings->isEnabled());
        $this->assertTrue($settings->autoResetOnSessionStart());
        $this->assertSame(48, $settings->lifetimeHours());
        $this->assertContains('subscription.', $settings->blockedRoutePrefixes());
    }

    public function test_it_persists_updated_demo_configuration(): void
    {
        $settings = app(DemoSettings::class);

        $settings->update([
            'enabled' => false,
            'auto_reset_on_session_start' => false,
            'lifetime_hours' => 24,
            'blocked_route_prefixes' => 'super_admin.,reports.custom.',
            'blocked_routes' => 'account-settings.update,demo.custom',
        ]);

        $fresh = app(DemoSettings::class);

        $this->assertFalse($fresh->isEnabled());
        $this->assertFalse($fresh->autoResetOnSessionStart());
        $this->assertSame(24, $fresh->lifetimeHours());
        $this->assertSame(['super_admin.', 'reports.custom.'], $fresh->blockedRoutePrefixes());
        $this->assertSame(['account-settings.update', 'demo.custom'], $fresh->blockedRoutes());
    }
}
