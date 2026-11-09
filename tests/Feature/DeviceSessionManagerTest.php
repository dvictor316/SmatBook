<?php

namespace Tests\Feature;

use App\Models\ActiveUserSession;
use App\Models\Company;
use App\Models\User;
use App\Support\DeviceSessionManager;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class DeviceSessionManagerTest extends TestCase
{
    use RefreshDatabase;

    public function test_repeated_requests_reuse_the_same_active_session_row(): void
    {
        $user = User::factory()->create(['role' => 'admin']);
        $session = app('session')->driver();
        $session->setId('concurrent-session-id');
        $session->start();
        $sessionId = $session->getId();

        $request = Request::create('/dashboard', 'GET', server: [
            'REMOTE_ADDR' => '127.0.0.1',
            'HTTP_USER_AGENT' => 'SmartProbook session test',
        ]);
        $request->setLaravelSession($session);

        $manager = app(DeviceSessionManager::class);

        $this->assertTrue($manager->ensureCurrentSession($request, $user)['allowed']);
        $this->assertTrue($manager->ensureCurrentSession($request, $user)['allowed']);

        $this->assertSame(1, ActiveUserSession::query()
            ->where('session_id', $sessionId)
            ->count());
        $this->assertSame($user->id, ActiveUserSession::query()
            ->where('session_id', $sessionId)
            ->value('user_id'));

        ActiveUserSession::query()->create([
            'user_id' => $user->id,
            'session_id' => 'expired-other-session',
            'last_seen_at' => Carbon::now()->subHours(3),
        ]);

        $manager->ensureCurrentSession($request, $user);

        $this->assertDatabaseHas('active_user_sessions', [
            'session_id' => 'expired-other-session',
        ]);
    }

    public function test_new_session_on_the_same_device_replaces_its_previous_session(): void
    {
        config(['session.driver' => 'array']);
        $user = User::factory()->create(['role' => 'admin']);
        $manager = app(DeviceSessionManager::class);
        $sessionIds = [];

        foreach (['same-device-old-session', 'same-device-new-session'] as $sessionId) {
            $session = app('session')->driver();
            $session->setId($sessionId);
            $session->start();
            $sessionIds[] = $session->getId();

            $request = Request::create('/dashboard', 'GET', cookies: [
                'spb_device_id' => 'same-device-id',
            ], server: [
                'REMOTE_ADDR' => '127.0.0.1',
                'HTTP_USER_AGENT' => 'SmartProbook same-device session test',
            ]);
            $request->setLaravelSession($session);

            $this->assertTrue($manager->ensureCurrentSession($request, $user)['allowed']);
        }

        $this->assertNotSame($sessionIds[0], $sessionIds[1]);
        $this->assertDatabaseMissing('active_user_sessions', [
            'session_id' => $sessionIds[0],
        ]);
        $this->assertDatabaseHas('active_user_sessions', [
            'session_id' => $sessionIds[1],
        ]);
        $this->assertSame(1, ActiveUserSession::query()->where('user_id', $user->id)->count());
    }

    public function test_existing_company_session_skips_subscription_and_session_count_queries(): void
    {
        $company = Company::create(['name' => 'Current Workspace']);
        $user = User::factory()->create(['role' => 'admin', 'company_id' => $company->id]);
        $session = app('session')->driver();
        $session->setId('existing-company-session');
        $session->start();
        $request = Request::create('/dashboard', 'GET', server: [
            'REMOTE_ADDR' => '127.0.0.1',
            'HTTP_USER_AGENT' => 'SmartProbook existing session test',
        ]);
        $request->setLaravelSession($session);

        ActiveUserSession::create([
            'user_id' => $user->id,
            'company_id' => $company->id,
            'session_id' => $session->getId(),
            'last_seen_at' => Carbon::now(),
        ]);

        $queries = [];
        DB::listen(static function ($query) use (&$queries): void {
            $queries[] = strtolower($query->sql);
        });

        $this->assertTrue(app(DeviceSessionManager::class)->ensureCurrentSession($request, $user)['allowed']);
        $this->assertFalse(collect($queries)->contains(fn (string $sql) => str_contains($sql, 'subscriptions')));
        $this->assertFalse(collect($queries)->contains(
            fn (string $sql) => str_contains($sql, 'active_user_sessions') && str_contains($sql, 'count(')
        ));
    }

    public function test_new_login_only_prunes_expired_sessions_for_its_own_workspace(): void
    {
        $company = Company::create(['name' => 'Current Workspace']);
        $otherCompany = Company::create(['name' => 'Other Workspace']);
        $user = User::factory()->create(['role' => 'admin', 'company_id' => $company->id]);
        $otherUser = User::factory()->create(['role' => 'admin', 'company_id' => $otherCompany->id]);

        ActiveUserSession::create([
            'user_id' => $otherUser->id,
            'company_id' => $otherCompany->id,
            'session_id' => 'expired-unrelated-session',
            'last_seen_at' => Carbon::now()->subHours(3),
        ]);
        ActiveUserSession::create([
            'user_id' => $otherUser->id,
            'company_id' => $company->id,
            'session_id' => 'expired-workspace-session',
            'last_seen_at' => Carbon::now()->subHours(3),
        ]);

        $session = app('session')->driver();
        $session->setId('new-login-session');
        $session->start();
        $session->put('current_tenant_id', $company->id);
        $request = Request::create('/login', 'POST', server: [
            'REMOTE_ADDR' => '127.0.0.1',
            'HTTP_USER_AGENT' => 'SmartProbook new login test',
        ]);
        $request->setLaravelSession($session);

        $this->assertTrue(app(DeviceSessionManager::class)->ensureCurrentSession($request, $user)['allowed']);

        $this->assertDatabaseMissing('active_user_sessions', ['session_id' => 'expired-workspace-session']);
        $this->assertDatabaseHas('active_user_sessions', ['session_id' => 'expired-unrelated-session']);
    }

    public function test_super_admin_can_use_four_devices_but_not_a_fifth(): void
    {
        config(['session.driver' => 'array']);
        $user = User::factory()->create(['role' => 'super_admin']);
        $manager = app(DeviceSessionManager::class);

        foreach (range(1, 4) as $deviceNumber) {
            $session = app('session')->driver();
            $session->setId("super-admin-device-{$deviceNumber}");
            $session->start();

            $request = Request::create('/dashboard', 'GET', cookies: [
                'spb_device_id' => "device-{$deviceNumber}",
            ], server: [
                'REMOTE_ADDR' => '127.0.0.1',
                'HTTP_USER_AGENT' => 'SmartProbook super admin session test',
            ]);
            $request->setLaravelSession($session);

            $this->assertTrue($manager->ensureCurrentSession($request, $user)['allowed']);
        }

        $session = app('session')->driver();
        $session->setId('super-admin-device-5');
        $session->start();
        $request = Request::create('/dashboard', 'GET', cookies: [
            'spb_device_id' => 'device-5',
        ], server: [
            'REMOTE_ADDR' => '127.0.0.1',
            'HTTP_USER_AGENT' => 'SmartProbook super admin session test',
        ]);
        $request->setLaravelSession($session);

        $result = $manager->ensureCurrentSession($request, $user);

        $this->assertFalse($result['allowed']);
        $this->assertSame(
            'Super admin access is limited to 4 devices at a time. Log out from another device first.',
            $result['message']
        );
        $this->assertSame(4, ActiveUserSession::query()->where('user_id', $user->id)->count());
    }
}
