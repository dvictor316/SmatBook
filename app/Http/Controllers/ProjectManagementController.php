<?php

namespace App\Http\Controllers;

use App\Models\Company;
use App\Models\Project;
use App\Models\ProjectTask;
use App\Models\Subscription;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Illuminate\View\View;

class ProjectManagementController extends Controller
{
    public function index(Request $request): View
    {
        $user = $request->user();
        $this->ensureProjectModuleAccess($request);

        if (!Schema::hasTable('projects') || !Schema::hasTable('project_tasks')) {
            return view('projects.index', [
                'projects' => collect(),
                'stats' => [
                    'total' => 0,
                    'in_progress' => 0,
                    'completed' => 0,
                    'budget' => 0,
                    'spent' => 0,
                ],
            ])->with('error', 'Project module is not initialized yet. Please run database migrations.');
        }

        $projects = Project::query()
            ->where(function ($query) use ($user) {
                $query->where('created_by', $user->id);

                if (!empty($user->company_id)) {
                    $query->orWhere('company_id', $user->company_id);
                }
            })
            ->with(['tasks' => fn ($query) => $query->latest()])
            ->latest('updated_at')
            ->get();

        $stats = [
            'total' => $projects->count(),
            'in_progress' => $projects->where('status', 'in_progress')->count(),
            'completed' => $projects->where('status', 'completed')->count(),
            'budget' => $projects->sum('budget'),
            'spent' => $projects->sum('spent'),
        ];

        return view('projects.index', compact('projects', 'stats'));
    }

    public function storeProject(Request $request): RedirectResponse
    {
        $this->ensureProjectModuleAccess($request);

        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'client_name' => ['nullable', 'string', 'max:120'],
            'status' => ['required', 'in:planning,in_progress,on_hold,completed'],
            'priority' => ['required', 'in:low,medium,high'],
            'start_date' => ['nullable', 'date'],
            'due_date' => ['nullable', 'date'],
            'budget' => ['nullable', 'numeric', 'min:0'],
            'spent' => ['nullable', 'numeric', 'min:0'],
            'progress' => ['nullable', 'integer', 'min:0', 'max:100'],
            'notes' => ['nullable', 'string'],
        ]);

        $user = $request->user();

        Project::create([
            ...$data,
            'company_id' => $user->company_id,
            'created_by' => $user->id,
            'budget' => (float) ($data['budget'] ?? 0),
            'spent' => (float) ($data['spent'] ?? 0),
            'progress' => (int) ($data['progress'] ?? 0),
        ]);

        return back()->with('success', 'Project added successfully.');
    }

    public function updateProject(Request $request, Project $project): RedirectResponse
    {
        $this->ensureProjectModuleAccess($request);
        $this->authorizeProject($request, $project);

        $data = $request->validate([
            'status' => ['required', 'in:planning,in_progress,on_hold,completed'],
            'progress' => ['nullable', 'integer', 'min:0', 'max:100'],
            'spent' => ['nullable', 'numeric', 'min:0'],
        ]);

        if (($data['status'] ?? null) === 'completed') {
            $data['progress'] = 100;
        }

        $project->update([
            'status' => $data['status'],
            'progress' => (int) ($data['progress'] ?? $project->progress),
            'spent' => (float) ($data['spent'] ?? $project->spent),
        ]);

        return back()->with('success', 'Project updated.');
    }

    public function storeTask(Request $request, Project $project): RedirectResponse
    {
        $this->ensureProjectModuleAccess($request);
        $this->authorizeProject($request, $project);

        $data = $request->validate([
            'title' => ['required', 'string', 'max:150'],
            'assignee' => ['nullable', 'string', 'max:120'],
            'status' => ['required', 'in:todo,in_progress,done'],
            'due_date' => ['nullable', 'date'],
            'estimated_hours' => ['nullable', 'numeric', 'min:0'],
        ]);

        $project->tasks()->create([
            ...$data,
            'estimated_hours' => isset($data['estimated_hours']) ? (float) $data['estimated_hours'] : null,
            'completed_at' => $data['status'] === 'done' ? now() : null,
        ]);

        return back()->with('success', 'Task added successfully.');
    }

    public function updateTask(Request $request, ProjectTask $task): RedirectResponse
    {
        $this->ensureProjectModuleAccess($request);
        $project = $task->project;
        $this->authorizeProject($request, $project);

        $data = $request->validate([
            'status' => ['required', 'in:todo,in_progress,done'],
            'actual_hours' => ['nullable', 'numeric', 'min:0'],
        ]);

        $task->update([
            'status' => $data['status'],
            'actual_hours' => isset($data['actual_hours']) ? (float) $data['actual_hours'] : $task->actual_hours,
            'completed_at' => $data['status'] === 'done' ? now() : null,
        ]);

        return back()->with('success', 'Task updated.');
    }

    private function authorizeProject(Request $request, Project $project): void
    {
        $user = $request->user();

        $owned = (int) $project->created_by === (int) $user->id;
        $sameCompany = !empty($user->company_id) && (int) $project->company_id === (int) $user->company_id;

        abort_unless($owned || $sameCompany, 403);
    }

    private function ensureProjectModuleAccess(Request $request): void
    {
        $user = $request->user();
        $role = strtolower((string) ($user->role ?? ''));
        $isSuperAdmin = in_array($role, ['super_admin', 'superadmin', 'admin'], true)
            || strtolower((string) ($user->email ?? '')) === 'donvictorlive@gmail.com';

        if ($isSuperAdmin) {
            return;
        }

        $plan = null;
        if (!empty($user->company_id)) {
            $plan = Company::where('id', $user->company_id)->value('plan');
        }
        if (!$plan) {
            $plan = Subscription::where('user_id', $user->id)
                ->latest('id')
                ->value('plan');
        }

        abort_unless(Str::lower((string) $plan) === 'enterprise', 403, 'Project module is available for Enterprise plan only.');
    }
}
