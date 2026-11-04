<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ChatTenantIsolationTest extends TestCase
{
    use RefreshDatabase;

    public function test_tenant_chat_directory_and_search_only_include_users_in_active_company(): void
    {
        $owner = User::factory()->create(['name' => 'Current Tenant User', 'company_id' => 10]);
        $coworker = User::factory()->create(['name' => 'Same Company Coworker', 'company_id' => 10]);
        $foreignUser = User::factory()->create(['name' => 'Foreign Company User', 'company_id' => 20]);

        $this->actingAs($owner)
            ->withoutMiddleware()
            ->withSession(['current_tenant_id' => 10, 'workspace_context' => 'business'])
            ->get(route('chat.index'))
            ->assertOk()
            ->assertSee('Same Company Coworker')
            ->assertDontSee('Foreign Company User');

        $this->getJson(route('chat.search-users', ['q' => 'Company']))
            ->assertOk()
            ->assertJsonFragment(['id' => $coworker->id, 'name' => 'Same Company Coworker'])
            ->assertJsonMissing(['id' => $foreignUser->id, 'name' => 'Foreign Company User']);
    }

    public function test_tenant_cannot_open_or_message_a_user_from_another_company(): void
    {
        $owner = User::factory()->create(['company_id' => 10]);
        $foreignUser = User::factory()->create(['company_id' => 20]);

        $this->actingAs($owner)
            ->withoutMiddleware()
            ->withSession(['current_tenant_id' => 10, 'workspace_context' => 'business'])
            ->get(route('chat.show', $foreignUser->id))
            ->assertNotFound();

        $this->post(route('chat.store'), [
            'receiver_id' => $foreignUser->id,
            'message' => 'This should not cross tenant boundaries.',
        ])->assertNotFound();

        $this->get(route('chat.messages', $foreignUser->id))->assertNotFound();
        $this->postJson(route('chat.mark-read'), ['user_id' => $foreignUser->id])->assertNotFound();
        $this->get(route('messages.thread', $foreignUser->id))->assertNotFound();

        $this->post(route('messages.store'), [
            'receiver_id' => $foreignUser->id,
            'subject' => 'Cross-tenant attempt',
            'content' => 'This should not be sent.',
        ])->assertNotFound();
    }

    public function test_platform_super_admin_can_still_search_users_in_platform_workspace(): void
    {
        $admin = User::factory()->create(['role' => 'super_admin']);
        $tenantUser = User::factory()->create(['company_id' => 20]);

        $this->actingAs($admin)
            ->withoutMiddleware()
            ->withSession(['workspace_context' => 'platform'])
            ->getJson(route('chat.search-users', ['q' => $tenantUser->name]))
            ->assertOk()
            ->assertJsonFragment(['id' => $tenantUser->id, 'name' => $tenantUser->name]);
    }
}
