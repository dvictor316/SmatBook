@extends('layout.mainlayout')

@section('page-title', 'Project Management')

@section('content')
<style>
    .pm-shell {
        padding: 14px;
    }

    .pm-card {
        background: #fff;
        border: 1px solid #e2e8f0;
        border-radius: 14px;
        box-shadow: 0 8px 24px rgba(15, 23, 42, 0.05);
    }

    .pm-stat {
        padding: 14px;
    }

    .pm-stat-label {
        font-size: 11px;
        text-transform: uppercase;
        color: #64748b;
        font-weight: 700;
        margin-bottom: 6px;
    }

    .pm-stat-value {
        font-size: 1.35rem;
        font-weight: 800;
        color: #0f172a;
        line-height: 1;
    }

    .pm-board {
        display: grid;
        grid-template-columns: repeat(3, minmax(0, 1fr));
        gap: 12px;
    }

    .pm-col {
        border: 1px solid #e2e8f0;
        border-radius: 12px;
        background: #f8fafc;
        padding: 10px;
        min-height: 260px;
    }

    .pm-col h6 {
        font-size: 12px;
        text-transform: uppercase;
        font-weight: 800;
        margin-bottom: 10px;
        color: #334155;
    }

    .pm-task {
        background: #fff;
        border: 1px solid #e2e8f0;
        border-radius: 10px;
        padding: 10px;
        margin-bottom: 8px;
    }

    .pm-task h5 {
        font-size: 13px;
        margin-bottom: 4px;
        font-weight: 700;
        color: #0f172a;
    }

    .pm-task small {
        color: #64748b;
    }

    @media (max-width: 991px) {
        .pm-board {
            grid-template-columns: 1fr;
        }
    }

    .pm-module {
        border: 1px solid #e2e8f0;
        border-radius: 12px;
        padding: 14px;
        background: #ffffff;
        height: 100%;
    }

    .pm-module h6 {
        font-weight: 800;
        margin-bottom: 8px;
        color: #0f172a;
    }

    .pm-module p {
        color: #64748b;
        font-size: 13px;
        margin-bottom: 10px;
    }

    .pm-pill {
        font-size: 10px;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: .4px;
        padding: 4px 8px;
        border-radius: 999px;
    }
</style>

<div class="page-wrapper">
    <div class="content container-fluid pm-shell">
        <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
            <div>
                <h3 class="mb-1" style="font-weight:800;">Project Management</h3>
                <p class="text-muted mb-0" style="font-size:13px;">Manage projects, budgets, milestones, and task execution in one workspace.</p>
            </div>
            <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#newProjectModal">
                <i class="fas fa-plus me-1"></i> New Project
            </button>
        </div>

        @if(session('success'))
            <div class="alert alert-success">{{ session('success') }}</div>
        @endif
        @if(session('error'))
            <div class="alert alert-danger">{{ session('error') }}</div>
        @endif

        <div class="row g-3 mb-3">
            <div class="col-6 col-lg-3">
                <div class="pm-card pm-stat">
                    <div class="pm-stat-label">Projects</div>
                    <div class="pm-stat-value">{{ $stats['total'] }}</div>
                </div>
            </div>
            <div class="col-6 col-lg-3">
                <div class="pm-card pm-stat">
                    <div class="pm-stat-label">In Progress</div>
                    <div class="pm-stat-value">{{ $stats['in_progress'] }}</div>
                </div>
            </div>
            <div class="col-6 col-lg-3">
                <div class="pm-card pm-stat">
                    <div class="pm-stat-label">Completed</div>
                    <div class="pm-stat-value">{{ $stats['completed'] }}</div>
                </div>
            </div>
            <div class="col-6 col-lg-3">
                <div class="pm-card pm-stat">
                    <div class="pm-stat-label">Budget / Spent</div>
                    <div class="pm-stat-value" style="font-size:1rem;line-height:1.35;">₦{{ number_format((float) $stats['budget'], 0) }} / ₦{{ number_format((float) $stats['spent'], 0) }}</div>
                </div>
            </div>
        </div>

        <div class="row g-3">
            <div class="col-xl-7">
                <div class="pm-card p-3 h-100">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <h5 class="mb-0" style="font-weight:800;">Projects</h5>
                    </div>

                    <div class="table-responsive">
                        <table class="table align-middle mb-0">
                            <thead>
                                <tr>
                                    <th>Name</th>
                                    <th>Status</th>
                                    <th>Progress</th>
                                    <th>Budget</th>
                                    <th>Update</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($projects as $project)
                                    <tr>
                                        <td>
                                            <div class="fw-bold">{{ $project->name }}</div>
                                            <small class="text-muted">{{ $project->client_name ?: 'Internal' }}</small>
                                        </td>
                                        <td>
                                            <span class="badge bg-light text-dark">{{ str_replace('_', ' ', ucfirst($project->status)) }}</span>
                                        </td>
                                        <td style="min-width:160px;">
                                            <div class="progress" style="height: 8px;">
                                                <div class="progress-bar" style="width: {{ $project->progress }}%;"></div>
                                            </div>
                                            <small class="text-muted">{{ $project->progress }}%</small>
                                        </td>
                                        <td>₦{{ number_format((float) $project->budget, 0) }}</td>
                                        <td>
                                            <form method="POST" action="{{ route('projects.update', $project) }}" class="d-flex gap-2 align-items-center">
                                                @csrf
                                                @method('PATCH')
                                                <select name="status" class="form-select form-select-sm" style="min-width:130px;">
                                                    <option value="planning" @selected($project->status === 'planning')>Planning</option>
                                                    <option value="in_progress" @selected($project->status === 'in_progress')>In Progress</option>
                                                    <option value="on_hold" @selected($project->status === 'on_hold')>On Hold</option>
                                                    <option value="completed" @selected($project->status === 'completed')>Completed</option>
                                                </select>
                                                <input type="number" name="progress" class="form-control form-control-sm" value="{{ $project->progress }}" min="0" max="100" style="width:80px;" placeholder="%">
                                                <button type="submit" class="btn btn-sm btn-outline-primary">Save</button>
                                            </form>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="5" class="text-center text-muted py-4">No projects yet. Create your first project.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <div class="col-xl-5">
                <div class="pm-card p-3 h-100" id="profitability">
                    <span id="tracking" style="position: relative; top: -96px;"></span>
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <h5 class="mb-0" style="font-weight:800;">Project Profitability</h5>
                    </div>

                    @php
                        $tasks = $projects->flatMap->tasks;
                    @endphp

                    <div class="pm-board mb-3">
                        @foreach(['todo' => 'To Do', 'in_progress' => 'In Progress', 'done' => 'Done'] as $statusKey => $statusLabel)
                            <div class="pm-col">
                                <h6>{{ $statusLabel }}</h6>
                                @forelse($tasks->where('status', $statusKey)->take(6) as $task)
                                    <div class="pm-task">
                                        <h5>{{ $task->title }}</h5>
                                        <small>{{ $task->assignee ?: 'Unassigned' }}</small>
                                        <div class="mt-2">
                                            <form method="POST" action="{{ route('projects.tasks.update', $task) }}" class="d-flex gap-1">
                                                @csrf
                                                @method('PATCH')
                                                <select name="status" class="form-select form-select-sm">
                                                    <option value="todo" @selected($task->status === 'todo')>To Do</option>
                                                    <option value="in_progress" @selected($task->status === 'in_progress')>In Progress</option>
                                                    <option value="done" @selected($task->status === 'done')>Done</option>
                                                </select>
                                                <button class="btn btn-sm btn-outline-dark" type="submit">Go</button>
                                            </form>
                                        </div>
                                    </div>
                                @empty
                                    <small class="text-muted">No tasks</small>
                                @endforelse
                            </div>
                        @endforeach
                    </div>

                    @if($projects->isNotEmpty())
                        <form method="POST" action="{{ route('projects.tasks.store', $projects->first()) }}" class="row g-2">
                            @csrf
                            <div class="col-12">
                                <select name="project_id" class="form-select form-select-sm" id="taskProjectSelect">
                                    @foreach($projects as $project)
                                        <option value="{{ $project->id }}">{{ $project->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-12">
                                <input type="text" name="title" class="form-control form-control-sm" placeholder="New task title" required>
                            </div>
                            <div class="col-6">
                                <input type="text" name="assignee" class="form-control form-control-sm" placeholder="Assignee">
                            </div>
                            <div class="col-6">
                                <select name="status" class="form-select form-select-sm">
                                    <option value="todo">To Do</option>
                                    <option value="in_progress">In Progress</option>
                                    <option value="done">Done</option>
                                </select>
                            </div>
                            <div class="col-12">
                                <button class="btn btn-sm btn-primary" type="submit">Add Task</button>
                            </div>
                        </form>
                    @endif
                </div>
            </div>
        </div>

        @php
            $canPro = in_array(($planTier ?? 'basic'), ['professional', 'enterprise'], true) || ($isSuperAdmin ?? false);
            $canEnterprise = (($planTier ?? 'basic') === 'enterprise') || ($isSuperAdmin ?? false);
            $suiteModules = [
                ['id' => 'reputation-management', 'title' => 'Reputation Management', 'desc' => 'Track client sentiment, feedback loops, and issue closure rates.', 'tier' => 'professional'],
                ['id' => 'lead-management', 'title' => 'Lead Management', 'desc' => 'Capture, score, and move prospects across your conversion pipeline.', 'tier' => 'professional'],
                ['id' => 'appointment-scheduling', 'title' => 'Appointment Scheduling', 'desc' => 'Centralized booking board for demos, onboarding, and review meetings.', 'tier' => 'professional'],
                ['id' => 'contract-esignature', 'title' => 'Contract Upload & E-Signature', 'desc' => 'Store contracts and manage signature lifecycle from draft to signed.', 'tier' => 'enterprise'],
                ['id' => 'proposals', 'title' => 'Proposals', 'desc' => 'Create commercial proposals, track approvals, and close faster.', 'tier' => 'enterprise'],
                ['id' => 'ai-anomaly-detection', 'title' => 'AI-Powered Anomaly Detection', 'desc' => 'Flag unusual transaction, margin, and project-cost patterns early.', 'tier' => 'enterprise'],
                ['id' => 'project-management-ai', 'title' => 'Project Management AI', 'desc' => 'AI-assisted milestone planning, risk scoring, and workload balancing.', 'tier' => 'enterprise'],
            ];
        @endphp

        <div class="pm-card p-3 mt-3" id="modules">
            <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
                <h5 class="mb-0" style="font-weight:800;">Business Modules</h5>
                <small class="text-muted">Plan Matrix: Basic ₦3,000 • Pro ₦7,000 • Enterprise ₦15,000</small>
            </div>
            <div class="row g-3">
                @foreach($suiteModules as $module)
                    @php
                        $enabled = $module['tier'] === 'professional' ? $canPro : $canEnterprise;
                    @endphp
                    <div class="col-md-6 col-xl-4">
                        <div class="pm-module" id="{{ $module['id'] }}">
                            <div class="d-flex justify-content-between align-items-center mb-1">
                                <h6 class="mb-0">{{ $module['title'] }}</h6>
                                <span class="pm-pill {{ $module['tier'] === 'enterprise' ? 'bg-warning-subtle text-warning' : 'bg-info-subtle text-info' }}">
                                    {{ $module['tier'] === 'enterprise' ? 'Enterprise' : 'Pro' }}
                                </span>
                            </div>
                            <p>{{ $module['desc'] }}</p>
                            @if($enabled)
                                <button
                                    class="btn btn-sm btn-outline-primary js-run-module"
                                    type="button"
                                    data-module-title="{{ $module['title'] }}"
                                    data-module-id="{{ $module['id'] }}"
                                    data-is-ai="{{ in_array($module['id'], ['ai-anomaly-detection', 'project-management-ai'], true) ? '1' : '0' }}"
                                >
                                    Run Module
                                </button>
                            @else
                                <button class="btn btn-sm btn-outline-secondary" type="button" disabled>Upgrade Required</button>
                            @endif
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="moduleAiModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="moduleAiModalTitle">AI Module Assistant</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p class="text-muted mb-2" id="moduleAiModalNote">Use AI assistant to run this module with guided actions.</p>
                <label class="form-label small mb-1">Assistant Prompt</label>
                <textarea id="moduleAiPrompt" class="form-control" rows="4" placeholder="Type what you need..."></textarea>
            </div>
            <div class="modal-footer">
                <button class="btn btn-light" type="button" data-bs-dismiss="modal">Close</button>
                <button class="btn btn-primary" type="button" id="moduleAiOpenAssistant">Open AI Assistant</button>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="newProjectModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Create Project</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" action="{{ route('projects.store') }}">
                @csrf
                <div class="modal-body">
                    <div class="row g-3">
                        <div class="col-md-8">
                            <label class="form-label">Project Name</label>
                            <input type="text" name="name" class="form-control" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Priority</label>
                            <select name="priority" class="form-select">
                                <option value="low">Low</option>
                                <option value="medium" selected>Medium</option>
                                <option value="high">High</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Client</label>
                            <input type="text" name="client_name" class="form-control">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Status</label>
                            <select name="status" class="form-select">
                                <option value="planning" selected>Planning</option>
                                <option value="in_progress">In Progress</option>
                                <option value="on_hold">On Hold</option>
                                <option value="completed">Completed</option>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Start Date</label>
                            <input type="date" name="start_date" class="form-control">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Due Date</label>
                            <input type="date" name="due_date" class="form-control">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Budget</label>
                            <input type="number" step="0.01" name="budget" class="form-control" value="0">
                        </div>
                        <div class="col-12">
                            <label class="form-label">Notes</label>
                            <textarea name="notes" class="form-control" rows="3"></textarea>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button class="btn btn-light" type="button" data-bs-dismiss="modal">Close</button>
                    <button class="btn btn-primary" type="submit">Save Project</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function () {
        const selector = document.getElementById('taskProjectSelect');
        if (selector) {
            selector.addEventListener('change', function () {
                const form = selector.closest('form');
                if (!form) return;
                const actionTemplate = @json(route('projects.tasks.store', ['project' => '__PROJECT__']));
                form.action = actionTemplate.replace('__PROJECT__', selector.value);
            });

            selector.dispatchEvent(new Event('change'));
        }

        const runButtons = document.querySelectorAll('.js-run-module');
        const modalEl = document.getElementById('moduleAiModal');
        const modalTitle = document.getElementById('moduleAiModalTitle');
        const modalNote = document.getElementById('moduleAiModalNote');
        const promptInput = document.getElementById('moduleAiPrompt');
        const openAssistantBtn = document.getElementById('moduleAiOpenAssistant');
        let activeModuleTitle = '';

        if (runButtons.length && modalEl) {
            const aiModal = (window.bootstrap && bootstrap.Modal)
                ? bootstrap.Modal.getOrCreateInstance(modalEl)
                : null;

            runButtons.forEach(function (btn) {
                btn.addEventListener('click', function () {
                    const title = btn.getAttribute('data-module-title') || 'Module';
                    const id = btn.getAttribute('data-module-id') || '';
                    const isAi = btn.getAttribute('data-is-ai') === '1';
                    activeModuleTitle = title;

                    if (isAi) {
                        modalTitle.textContent = title + ' Assistant';
                        modalNote.textContent = 'AI will guide you through this module and generate actionable output.';
                        promptInput.value = 'Run ' + title + ' for my current projects and highlight key risks and next actions.';
                        if (aiModal) {
                            aiModal.show();
                        } else {
                            alert('AI assistant modal is currently unavailable. Please refresh the page and try again.');
                        }
                        return;
                    }

                    const target = document.getElementById(id);
                    if (target) {
                        target.scrollIntoView({ behavior: 'smooth', block: 'start' });
                    }
                });
            });
        }

        if (openAssistantBtn) {
            openAssistantBtn.addEventListener('click', function () {
                const prompt = (promptInput?.value || '').trim();
                const trigger = document.getElementById('ai-agent-trigger');
                const openAiBtn = document.getElementById('open-ai-chat-btn');
                const aiInput = document.getElementById('ai-agent-input');
                const aiSend = document.getElementById('ai-agent-send');

                if (window.bootstrap && bootstrap.Modal && modalEl) {
                    bootstrap.Modal.getOrCreateInstance(modalEl)?.hide();
                }
                trigger?.click();

                setTimeout(function () {
                    openAiBtn?.click();
                }, 250);

                setTimeout(function () {
                    if (!aiInput || !aiSend) return;
                    aiInput.value = prompt || ('Run ' + activeModuleTitle + ' for my workspace.');
                    aiSend.click();
                }, 850);
            });
        }
    });
</script>
@endpush
