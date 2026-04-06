<?php

namespace App\Http\Controllers;

use App\Models\Company;
use App\Models\Expense;
use App\Models\ExpenseClaim;
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
        $planTier = $this->resolvePlanTier($user);
        $isSuperAdmin = $this->isSuperAdmin($user);

        if (!class_exists(\App\Models\Project::class) || !class_exists(\App\Models\ProjectTask::class)) {
            return view('projects.index', [
                'projects' => collect(),
                'planTier' => $planTier,
                'isSuperAdmin' => $isSuperAdmin,
                'stats' => [
                    'total' => 0,
                    'in_progress' => 0,
                    'completed' => 0,
                    'budget' => 0,
                    'spent' => 0,
                ],
            ])->with('error', 'Project module classes are not loaded. Run `composer dump-autoload` and refresh this page.');
        }

        if (!Schema::hasTable('projects') || !Schema::hasTable('project_tasks')) {
            return view('projects.index', [
                'projects' => collect(),
                'planTier' => $planTier,
                'isSuperAdmin' => $isSuperAdmin,
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

        $trackedExpenseTotals = collect();
        if (Schema::hasTable('expenses') && Schema::hasColumn('expenses', 'project_id')) {
            $trackedExpenseTotals = Expense::query()
                ->selectRaw('project_id, SUM(amount) as total')
                ->whereNotNull('project_id')
                ->groupBy('project_id')
                ->pluck('total', 'project_id');
        }

        $pendingClaimTotals = collect();
        if (Schema::hasTable('expense_claims')) {
            $pendingClaimTotals = ExpenseClaim::query()
                ->selectRaw('project_id, SUM(amount) as total')
                ->whereNotNull('project_id')
                ->whereIn('status', ['pending', 'approved'])
                ->groupBy('project_id')
                ->pluck('total', 'project_id');
        }

        $projects->transform(function (Project $project) use ($trackedExpenseTotals, $pendingClaimTotals) {
            $project->tracked_costs = (float) ($trackedExpenseTotals[$project->id] ?? 0);
            $project->pending_claims_total = (float) ($pendingClaimTotals[$project->id] ?? 0);

            return $project;
        });

        $stats = [
            'total' => $projects->count(),
            'in_progress' => $projects->where('status', 'in_progress')->count(),
            'completed' => $projects->where('status', 'completed')->count(),
            'budget' => $projects->sum('budget'),
            'spent' => $projects->sum('spent'),
            'tracked_costs' => $projects->sum('tracked_costs'),
            'pending_claims' => $projects->sum('pending_claims_total'),
        ];

        return view('projects.index', compact('projects', 'stats', 'planTier', 'isSuperAdmin'));
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
        $isSuperAdmin = $this->isSuperAdmin($user);

        if ($isSuperAdmin) {
            return;
        }

        $planTier = $this->resolvePlanTier($user);
        abort_unless(in_array($planTier, ['professional', 'enterprise'], true), 403, 'Project module is available for Professional and Enterprise plans.');
    }

    private function isSuperAdmin($user): bool
    {
        $role = strtolower((string) ($user->role ?? ''));
        return in_array($role, ['super_admin', 'superadmin', 'admin'], true)
            || strtolower((string) ($user->email ?? '')) === 'donvictorlive@gmail.com';
    }

    private function resolvePlanTier($user): string
    {
        $plan = null;

        if (!empty($user->company_id)) {
            $plan = Company::where('id', $user->company_id)->value('plan');
        }

        if (!$plan) {
            $plan = Subscription::where('user_id', $user->id)
                ->latest('id')
                ->value('plan');
        }

        $normalized = Str::lower((string) $plan);

        if (str_contains($normalized, 'enterprise')) {
            return 'enterprise';
        }
        if (str_contains($normalized, 'pro') || str_contains($normalized, 'professional')) {
            return 'professional';
        }

        return 'basic';
    }
}
