<?php

namespace Tests\Feature;

use App\Models\ActiveUserSession;
use App\Models\User;
use App\Support\DeviceSessionManager;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
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
}
