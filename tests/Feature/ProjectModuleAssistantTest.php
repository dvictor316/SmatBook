<?php

namespace Tests\Feature;

use App\Models\Project;
use App\Models\ProjectTask;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProjectModuleAssistantTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config(['services.openai.api_key' => '']);
    }

    public function test_project_module_buttons_launch_the_assistant_directly(): void
    {
        $admin = User::factory()->create(['role' => 'super_admin']);

        $this->withoutMiddleware()
            ->actingAs($admin)
            ->get(route('projects.index'))
            ->assertOk()
            ->assertSee('window.openSmartProbookAiAssistant(prompt)', false)
            ->assertDontSee('moduleAiModal', false);
    }

    public function test_resource_capacity_action_returns_workspace_task_load(): void
    {
        $admin = User::factory()->create(['role' => 'super_admin']);
        $project = Project::create([
            'name' => 'Capacity review',
            'status' => 'in_progress',
            'budget' => 1000,
            'spent' => 300,
        ]);
        ProjectTask::create([
            'project_id' => $project->id,
            'title' => 'Prepare delivery plan',
            'assignee' => 'Alex',
            'status' => 'todo',
            'estimated_hours' => 12,
        ]);

        $this->actingAs($admin)
            ->getJson(route('ai.quick-agent.query', [
                'message' => 'Review resource capacity planning for my workspace.',
            ]))
            ->assertOk()
            ->assertJsonPath('ok', true)
            ->assertJsonFragment(['answer' => 'Resource capacity snapshot: 1 active task(s) across 1 assigned team member(s), totaling 12.0 estimated hours; 0 task(s) are unassigned. Alex has the highest estimate at 12.0 hours. Assign unowned tasks and review due dates before redistributing work.']);
    }

    public function test_change_order_action_returns_project_scope_risks(): void
    {
        $admin = User::factory()->create(['role' => 'super_admin']);
        $project = Project::create([
            'name' => 'Scope review',
            'status' => 'in_progress',
            'budget' => 1000,
            'spent' => 1250,
        ]);
        ProjectTask::create([
            'project_id' => $project->id,
            'title' => 'Overdue scope item',
            'assignee' => 'Alex',
            'status' => 'todo',
            'due_date' => now()->subDay()->toDateString(),
        ]);

        $this->actingAs($admin)
            ->getJson(route('ai.quick-agent.query', [
                'message' => 'Review change orders for my workspace.',
            ]))
            ->assertOk()
            ->assertJsonPath('ok', true)
            ->assertJsonFragment(['answer' => 'Change-order risk review: 1 project(s) are over budget and 1 task(s) are overdue. This workspace has no dedicated change-order log, so these are scope-risk indicators rather than recorded change requests. Review the affected project budgets and overdue work, then document any approved scope, cost, or schedule changes.']);
    }
}
