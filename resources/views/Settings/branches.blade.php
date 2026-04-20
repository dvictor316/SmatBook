<?php $page = 'branches'; ?>
@extends('layout.mainlayout')
@section('content')

<style>
    .branch-shell { display: grid; gap: 20px; }
    .branch-card {
        border: 1px solid #dbe7ff;
        border-radius: 18px;
        background: linear-gradient(180deg, #ffffff 0%, #f8fbff 100%);
        box-shadow: 0 12px 28px rgba(15, 23, 42, 0.05);
    }
    .branch-card .card-body { padding: 22px; }
    .branch-summary {
        display: grid;
        grid-template-columns: repeat(4, minmax(0, 1fr));
        gap: 16px;
    }
    .branch-tile {
        padding: 18px;
        border-radius: 16px;
        border: 1px solid #dbe7ff;
        background: #fff;
    }
    .branch-tile small {
        display: block;
        margin-bottom: 6px;
        font-size: 0.72rem;
        font-weight: 800;
        text-transform: uppercase;
        letter-spacing: 0.08em;
        color: #64748b;
    }
    .branch-tile strong { font-size: 1.6rem; color: #0f172a; }
    .branch-row {
        border: 1px solid #e6eefc;
        border-radius: 16px;
        background: #fff;
        padding: 18px;
        margin-bottom: 14px;
    }
    @media (max-width: 991px) {
        .branch-summary { grid-template-columns: 1fr; }
    }
</style>

<div class="page-wrapper">
    <div class="content container-fluid">
        <div class="row">
            <div class="col-xl-3 col-md-4">
                <div class="card">
                    <div class="card-body">
                        <div class="page-header">
                            <div class="content-page-header">
                                <h5>Settings</h5>
                            </div>
                        </div>
                        @component('components.settings-menu')
                        @endcomponent
                    </div>
                </div>
            </div>

            <div class="col-xl-9 col-md-8">
                <div class="content-page-header d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
                    <div>
                        <h4 class="mb-1">Business Branches</h4>
                        <p class="text-muted mb-0">Define operating branches and choose the active branch context for the workspace.</p>
                    </div>
                    @if($activeBranch)
                        <span class="badge bg-primary text-white px-3 py-2">
                            Active Branch: {{ $activeBranch['name'] }}
                        </span>
                    @endif
                </div>

                <div class="branch-shell">
                    <div class="card branch-card">
                        <div class="card-body">
                            <div class="branch-summary">
                                <div class="branch-tile">
                                    <small>Total Branches</small>
                                    <strong>{{ $branches->count() }}</strong>
                                </div>
                                <div class="branch-tile">
                                    <small>Active Branches</small>
                                    <strong>{{ $branches->where('is_active', true)->count() }}</strong>
                                </div>
                                <div class="branch-tile">
                                    <small>Current Workspace</small>
                                    <strong>{{ $activeBranch['code'] ?? 'N/A' }}</strong>
                                </div>
                                <div class="branch-tile">
                                    <small>{{ strtoupper((string) ($planLabel ?? 'Basic')) }} Allowance</small>
                                    <strong>{{ $branchLimit === null ? 'Unlimited' : $branchLimit }}</strong>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="card branch-card">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
                                <h5 class="mb-0">Create Branch</h5>
                                <span class="badge {{ ($branchLimit !== null && ($branchSlotsRemaining ?? 0) === 0) ? 'bg-danger' : 'bg-info' }} text-white px-3 py-2">
                                    @if($branchLimit === null)
                                        Unlimited branches on this plan
                                    @else
                                        {{ $branchSlotsRemaining }} of {{ $branchLimit }} slots left
                                    @endif
                                </span>
                            </div>
                            <form method="POST" action="{{ route('settings.branches.store') }}">
                                @csrf
                                <div class="row g-3">
                                    <div class="col-md-4">
                                        <label class="form-label">Branch Name</label>
                                        <input type="text" name="name" class="form-control" placeholder="Victoria Island Branch" required>
                                    </div>
                                    <div class="col-md-2">
                                        <label class="form-label">Code</label>
                                        <input type="text" name="code" class="form-control" placeholder="VI">
                                    </div>
                                    <div class="col-md-3">
                                        <label class="form-label">Manager</label>
                                        <input type="text" name="manager" class="form-control" placeholder="Branch lead">
                                    </div>
                                    <div class="col-md-3">
                                        <label class="form-label">Phone</label>
                                        <input type="text" name="phone" class="form-control" placeholder="+234...">
                                    </div>
                                    <div class="col-md-9">
                                        <label class="form-label">Address</label>
                                        <input type="text" name="address" class="form-control" placeholder="Street, city, state">
                                    </div>
                                    <div class="col-md-3 d-flex align-items-end">
                                        <button type="submit" class="btn btn-primary w-100">Add Branch</button>
                                    </div>
                                </div>
                            </form>
                        </div>
                    </div>

                    <div class="card branch-card">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
                                <div>
                                    <h5 class="mb-1">Branch Directory</h5>
                                    <p class="text-muted mb-0">Switch the active branch or update branch details here.</p>
                                </div>
                            </div>

                            @forelse($branches as $branch)
                                <div class="branch-row">
                                    <div class="d-flex justify-content-between align-items-start flex-wrap gap-3 mb-3">
                                        <div>
                                            <div class="fw-bold">{{ $branch['name'] }}</div>
                                            <div class="text-muted small">{{ $branch['code'] ?: 'No code' }} • {{ $branch['manager'] ?: 'No manager assigned' }}</div>
                                        </div>
                                        <div class="d-flex align-items-center gap-2 flex-wrap">
                                            @if(($activeBranch['id'] ?? null) === $branch['id'])
                                                <span class="badge bg-success text-white px-3 py-2">Active Branch</span>
                                            @else
                                                <form method="POST" action="{{ route('settings.branches.activate') }}">
                                                    @csrf
                                                    <input type="hidden" name="branch_id" value="{{ $branch['id'] }}">
                                                    <input type="hidden" name="redirect_to" value="{{ url()->full() }}">
                                                    <button type="submit" class="btn btn-outline-primary btn-sm">Make Active</button>
                                                </form>
                                            @endif
                                        </div>
                                    </div>

                                    <form method="POST" action="{{ route('settings.branches.update', $branch['id']) }}">
                                        @csrf
                                        @method('PUT')
                                        <div class="row g-3">
                                            <div class="col-md-3">
                                                <label class="form-label">Branch Name</label>
                                                <input type="text" name="name" class="form-control" value="{{ $branch['name'] }}" required>
                                            </div>
                                            <div class="col-md-2">
                                                <label class="form-label">Code</label>
                                                <input type="text" name="code" class="form-control" value="{{ $branch['code'] }}">
                                            </div>
                                            <div class="col-md-3">
                                                <label class="form-label">Manager</label>
                                                <input type="text" name="manager" class="form-control" value="{{ $branch['manager'] }}">
                                            </div>
                                            <div class="col-md-2">
                                                <label class="form-label">Phone</label>
                                                <input type="text" name="phone" class="form-control" value="{{ $branch['phone'] }}">
                                            </div>
                                            <div class="col-md-2">
                                                <label class="form-label">Status</label>
                                                <select name="is_active" class="form-select">
                                                    <option value="1" {{ $branch['is_active'] ? 'selected' : '' }}>Active</option>
                                                    <option value="0" {{ !$branch['is_active'] ? 'selected' : '' }}>Inactive</option>
                                                </select>
                                            </div>
                                            <div class="col-md-8">
                                                <label class="form-label">Address</label>
                                                <input type="text" name="address" class="form-control" value="{{ $branch['address'] }}">
                                            </div>
                                            <div class="col-md-4 d-flex align-items-end justify-content-end gap-2">
                                                <button type="submit" class="btn btn-outline-primary">Save Changes</button>
                                            </div>
                                        </div>
                                    </form>

                                    <div class="text-end mt-3">
                                        <form method="POST" action="{{ route('settings.branches.destroy', $branch['id']) }}" onsubmit="return confirm('Remove this branch?');">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-outline-danger btn-sm">Remove Branch</button>
                                        </form>
                                    </div>
                                </div>
                            @empty
                                <div class="text-center text-muted py-5">
                                    No branches created yet. Add your first branch to begin separating operations by location.
                                </div>
                            @endforelse
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
