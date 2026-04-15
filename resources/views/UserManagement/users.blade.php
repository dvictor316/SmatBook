<?php $page = 'users'; ?>
@extends('layout.mainlayout')

@section('page-title', 'Users & Roles')

@push('styles')
<style>
    /* ── Tabs ── */
    .prokip-tabs {
        display: flex;
        gap: 0;
        border-bottom: 2px solid #e8eaf0;
        margin-bottom: 28px;
    }
    .prokip-tab {
        padding: 12px 24px;
        font-weight: 600;
        font-size: 0.92rem;
        color: #7a869a;
        cursor: pointer;
        border-bottom: 3px solid transparent;
        margin-bottom: -2px;
        text-decoration: none;
        transition: color 0.15s, border-color 0.15s;
    }
    .prokip-tab:hover { color: #1a2236; }
    .prokip-tab.active { color: #1a2236; border-bottom-color: #d4a017; }

    /* ── Page Header ── */
    .users-page-title { font-size: 1.6rem; font-weight: 700; color: #1a2236; margin-bottom: 2px; }
    .users-page-sub { font-size: 0.88rem; color: #7a869a; }

    /* ── Add button ── */
    .btn-add-user {
        background: #d4a017;
        border: none;
        color: #fff;
        padding: 10px 22px;
        border-radius: 8px;
        font-weight: 700;
        font-size: 0.9rem;
        display: inline-flex;
        align-items: center;
        gap: 6px;
        transition: background 0.2s;
    }
    .btn-add-user:hover { background: #b88b12; color: #fff; }

    /* ── Table ──  */
    .users-table thead th {
        background: #f4f6fb;
        color: #7a869a;
        font-size: 0.72rem;
        font-weight: 700;
        letter-spacing: 0.07em;
        text-transform: uppercase;
        border-bottom: 1px solid #e4e8f0;
    }
    .users-table tbody tr:hover { background: #fafbff; }
    .users-table .user-username { font-weight: 600; color: #1a2236; font-size: 0.88rem; }

    /* ── Action icons ── */
    .action-icon { color: #7a869a; font-size: 1rem; padding: 4px 6px; transition: color 0.15s; }
    .action-icon:hover { color: #1a2236; }

    /* ── Modal collapsible sections ── */
    .section-toggle {
        display: flex;
        align-items: center;
        justify-content: space-between;
        padding: 14px 20px;
        background: #f4f6fb;
        border-radius: 8px;
        cursor: pointer;
        font-weight: 600;
        font-size: 0.92rem;
        color: #1a2236;
        user-select: none;
        border: none;
        width: 100%;
        text-align: left;
    }
    .section-toggle .chevron { transition: transform 0.2s; }
    .section-toggle[aria-expanded="false"] .chevron { transform: rotate(-90deg); }

    /* ── Toggle switch ── */
    .toggle-wrap { display: flex; align-items: center; gap: 10px; }
    .toggle-switch { position: relative; display: inline-block; width: 44px; height: 24px; }
    .toggle-switch input { opacity: 0; width: 0; height: 0; }
    .toggle-slider {
        position: absolute; inset: 0;
        background: #ccc; border-radius: 24px; cursor: pointer;
        transition: background 0.2s;
    }
    .toggle-slider:before {
        content: ''; position: absolute;
        width: 18px; height: 18px;
        left: 3px; top: 3px;
        background: #fff; border-radius: 50%;
        transition: transform 0.2s;
    }
    .toggle-switch input:checked + .toggle-slider { background: #d4a017; }
    .toggle-switch input:checked + .toggle-slider:before { transform: translateX(20px); }

    /* ── Username preview ── */
    .username-preview {
        font-size: 0.82rem;
        color: #5b5b8b;
        margin-top: 4px;
    }
    .username-input-group {
        display: flex;
        border: 1px solid #d0d5e0;
        border-radius: 8px;
        overflow: hidden;
    }
    .username-input-group input {
        border: none !important;
        border-radius: 0 !important;
        flex: 1;
        box-shadow: none !important;
    }
    .username-suffix {
        background: #f4f6fb;
        border-left: 1px solid #d0d5e0;
        padding: 8px 14px;
        font-size: 0.88rem;
        color: #7a869a;
        white-space: nowrap;
        display: flex;
        align-items: center;
    }

    /* ── Show entries / search row ── */
    .table-toolbar {
        display: flex;
        align-items: center;
        justify-content: space-between;
        margin-bottom: 14px;
        flex-wrap: wrap;
        gap: 10px;
    }
    .table-toolbar .show-entries { display: flex; align-items: center; gap: 8px; font-size: 0.88rem; color: #555; }
    .table-toolbar .search-box input { border-radius: 8px; border: 1px solid #d0d5e0; padding: 7px 14px; font-size: 0.88rem; }
</style>
@endpush

@section('content')
@php
    $isSuperAdminRoute = request()->routeIs('super_admin.*');
    $storeRouteName = $isSuperAdminRoute && app('router')->has('super_admin.users.store') ? 'super_admin.users.store' : 'users.store';
    $showRouteName  = $isSuperAdminRoute && app('router')->has('super_admin.users.show')  ? 'super_admin.users.show'  : 'users.show';
    $editRouteName  = $isSuperAdminRoute && app('router')->has('super_admin.users.edit')  ? 'super_admin.users.edit'  : 'users.edit';
    $deleteRouteName = $isSuperAdminRoute && app('router')->has('super_admin.users.destroy') ? 'super_admin.users.destroy' : 'users.destroy';
    $suffix = $companySuffix ?? '';
@endphp

<div class="page-wrapper">
    <div class="content container-fluid">

        {{-- ── Tabs ── --}}
        <div class="prokip-tabs">
            <a href="#" class="prokip-tab active">Users</a>
            <a href="{{ route('roles.index') }}" class="prokip-tab">Roles</a>
            <a href="#" class="prokip-tab">Sales Commission Agent</a>
        </div>

        {{-- ── Header ── --}}
        <div class="d-flex align-items-start justify-content-between mb-4 flex-wrap gap-3">
            <div>
                <div class="users-page-title">Users &amp; Roles</div>
                <div class="users-page-sub">Users</div>
            </div>
            <button class="btn-add-user" data-bs-toggle="modal" data-bs-target="#add_user">
                <i class="fas fa-plus"></i> Add
            </button>
        </div>

        @if(session('success'))
            <div class="alert alert-success alert-dismissible fade show">{{ session('success') }}<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
        @endif
        @if(session('error'))
            <div class="alert alert-danger alert-dismissible fade show">{{ session('error') }}<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
        @endif

        {{-- ── Table Card ── --}}
        <div class="card border-0 shadow-sm">
            <div class="card-body">

                <div class="table-toolbar">
                    <div class="show-entries">
                        Show
                        <select class="form-select form-select-sm d-inline-block w-auto" id="entriesCount">
                            <option value="10">10</option>
                            <option value="25" selected>25</option>
                            <option value="50">50</option>
                            <option value="100">100</option>
                        </select>
                    </div>
                    <div class="search-box">
                        <input type="text" id="userSearch" placeholder="Search ..." class="form-control form-control-sm">
                    </div>
                </div>

                <div class="table-responsive">
                    <table class="table table-hover users-table" id="usersTable">
                        <thead>
                            <tr>
                                <th>Username</th>
                                <th>Name</th>
                                <th>Role</th>
                                <th>Email</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($users as $user)
                            <tr>
                                <td><span class="user-username">{{ $user->username ?? $user->name }}</span></td>
                                <td>{{ $user->name }}</td>
                                <td>
                                    @php
                                        $roleName = $user->roleModel->name ?? ucwords(str_replace('_', ' ', $user->role ?? ''));
                                    @endphp
                                    {{ $roleName }}
                                </td>
                                <td>{{ $user->email }}</td>
                                <td>
                                    <a href="{{ route($showRouteName, $user->id) }}" class="action-icon" title="View">
                                        <i class="far fa-eye"></i>
                                    </a>
                                    @if(!in_array($user->role, ['super_admin']))
                                    <a href="{{ route($editRouteName, $user->id) }}" class="action-icon" title="Edit">
                                        <i class="far fa-edit"></i>
                                    </a>
                                    @endif
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="5" class="text-center py-5 text-muted">No users found.</td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                @if(method_exists($users, 'links'))
                <div class="d-flex align-items-center justify-content-between mt-3 flex-wrap gap-2">
                    <div class="text-muted small">
                        Showing {{ $users->firstItem() ?? 0 }} to {{ $users->lastItem() ?? 0 }} of {{ $users->total() }} entries
                    </div>
                    {{ $users->links() }}
                </div>
                @endif

            </div>
        </div>

    </div>
</div>

{{-- ═══════════════════════════════════════════════════ --}}
{{-- MODAL: ADD USER (Prokip pattern)                    --}}
{{-- ═══════════════════════════════════════════════════ --}}
<div class="modal fade" id="add_user" tabindex="-1" aria-labelledby="addUserLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header border-0 pb-0">
                <div>
                    <h4 class="modal-title fw-bold mb-1" id="addUserLabel">Add user</h4>
                    <p class="text-muted small mb-0">Users &amp; Roles</p>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>

            <form action="{{ route($storeRouteName) }}" method="POST" enctype="multipart/form-data" id="addUserForm">
                @csrf
                <div class="modal-body pt-3">

                    {{-- ── Section 1: Personal Information ── --}}
                    <div class="mb-3">
                        <button type="button" class="section-toggle" data-bs-toggle="collapse" data-bs-target="#sectionPersonal" aria-expanded="true">
                            Personal Information <i class="fas fa-chevron-down chevron"></i>
                        </button>
                        <div class="collapse show" id="sectionPersonal">
                            <div class="pt-3 px-1">
                                <div class="row g-3">
                                    <div class="col-md-6">
                                        <label class="form-label">First Name:<span class="text-danger">*</span></label>
                                        <input type="text" name="first_name" class="form-control" placeholder="First Name" required>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">Last Name:</label>
                                        <input type="text" name="last_name" class="form-control" placeholder="Last Name">
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">Email:<span class="text-danger">*</span></label>
                                        <input type="email" name="email" class="form-control" placeholder="Email" required>
                                    </div>
                                    <div class="col-md-6 d-flex align-items-end pb-1">
                                        <div class="toggle-wrap">
                                            <label class="toggle-switch">
                                                <input type="checkbox" name="is_active" value="1" checked>
                                                <span class="toggle-slider"></span>
                                            </label>
                                            <span class="text-muted small">Is active ?</span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- ── Section 2: Roles and Permissions ── --}}
                    <div class="mb-3">
                        <button type="button" class="section-toggle" data-bs-toggle="collapse" data-bs-target="#sectionRoles" aria-expanded="false">
                            Roles and Permissions <i class="fas fa-chevron-down chevron"></i>
                        </button>
                        <div class="collapse" id="sectionRoles">
                            <div class="pt-3 px-1">
                                <div class="row g-3">

                                    {{-- Allow login toggle --}}
                                    <div class="col-12">
                                        <div class="toggle-wrap">
                                            <label class="toggle-switch">
                                                <input type="checkbox" name="allow_login" value="1" checked>
                                                <span class="toggle-slider"></span>
                                            </label>
                                            <span class="text-muted small">Allow login</span>
                                        </div>
                                    </div>

                                    {{-- Username with tenant suffix --}}
                                    <div class="col-12">
                                        <label class="form-label">Username:</label>
                                        <div class="username-input-group">
                                            <input type="text" name="username_base" id="usernameBase"
                                                   class="form-control"
                                                   placeholder="Leave blank to auto generate username"
                                                   autocomplete="off">
                                            @if($suffix)
                                            <span class="username-suffix">-{{ $suffix }}</span>
                                            @endif
                                        </div>
                                        <div class="username-preview" id="usernamePreview">
                                            Leave blank to auto generate username
                                        </div>
                                    </div>

                                    {{-- Password --}}
                                    <div class="col-md-6">
                                        <label class="form-label">Password:<span class="text-danger">*</span></label>
                                        <input type="password" name="password" class="form-control" placeholder="Password" required autocomplete="new-password">
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">Confirm Password:<span class="text-danger">*</span></label>
                                        <input type="password" name="confirm_password" class="form-control" placeholder="Confirm Password" required autocomplete="new-password">
                                    </div>

                                    {{-- Role --}}
                                    <div class="col-12">
                                        <label class="form-label">Role:<span class="text-danger">*</span></label>
                                        <select name="role" class="form-select" required>
                                            <option value="" disabled selected>Select a role</option>
                                            @foreach(($roles ?? []) as $role)
                                                <option value="{{ $role }}">{{ ucwords(str_replace('_', ' ', $role)) }}</option>
                                            @endforeach
                                        </select>
                                    </div>

                                    {{-- Access Locations --}}
                                    @if(($branches ?? collect())->isNotEmpty())
                                    <div class="col-12">
                                        <label class="form-label fw-semibold">Access locations</label>
                                        <div class="form-check mb-2">
                                            <input class="form-check-input" type="checkbox" name="access_all_locations" value="1" id="accessAll" checked>
                                            <label class="form-check-label" for="accessAll">All Locations</label>
                                        </div>
                                        <div id="branchCheckboxes">
                                            @foreach($branches as $branch)
                                            <div class="form-check mb-1">
                                                <input class="form-check-input branch-check" type="checkbox"
                                                       name="access_branches[]" value="{{ $branch['id'] }}"
                                                       id="branch_{{ $branch['id'] }}">
                                                <label class="form-check-label" for="branch_{{ $branch['id'] }}">
                                                    {{ $branch['name'] }} {{ $branch['code'] ? '(' . $branch['code'] . ')' : '' }}
                                                </label>
                                            </div>
                                            @endforeach
                                        </div>
                                    </div>
                                    @endif

                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- ── Section 3: Invoices ── --}}
                    <div class="mb-3">
                        <button type="button" class="section-toggle" data-bs-toggle="collapse" data-bs-target="#sectionInvoices" aria-expanded="false">
                            Invoices <i class="fas fa-chevron-down chevron"></i>
                        </button>
                        <div class="collapse" id="sectionInvoices">
                            <div class="pt-3 px-1">
                                <p class="text-muted small">Invoice access settings will be available here.</p>
                            </div>
                        </div>
                    </div>

                </div>
                <div class="modal-footer border-0">
                    <button type="button" class="btn btn-light border" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-add-user">Save User</button>
                </div>
            </form>

        </div>
    </div>
</div>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {

    // ── Chevron toggle sync ──
    document.querySelectorAll('.section-toggle').forEach(function (btn) {
        var target = document.querySelector(btn.dataset.bsTarget);
        if (!target) return;
        target.addEventListener('show.bs.collapse', function () { btn.setAttribute('aria-expanded', 'true'); });
        target.addEventListener('hide.bs.collapse', function () { btn.setAttribute('aria-expanded', 'false'); });
    });

    // ── Username live preview ──
    var usernameInput = document.getElementById('usernameBase');
    var preview = document.getElementById('usernamePreview');
    var suffix = '{{ $suffix ? "-" . $suffix : "" }}';

    if (usernameInput && preview) {
        usernameInput.addEventListener('input', function () {
            var val = this.value.trim().replace(/[^a-zA-Z0-9_]/g, '');
            if (val) {
                preview.textContent = 'Your username will be: ' + val + suffix;
            } else {
                preview.textContent = 'Leave blank to auto generate username';
            }
        });

        // Auto-fill from first name
        var firstNameInput = document.querySelector('[name="first_name"]');
        if (firstNameInput) {
            firstNameInput.addEventListener('input', function () {
                if (!usernameInput.value) {
                    var val = this.value.trim().replace(/\s+/g, '').replace(/[^a-zA-Z0-9_]/g, '');
                    preview.textContent = val ? 'Your username will be: ' + val + suffix : 'Leave blank to auto generate username';
                }
            });
        }
    }

    // ── All Locations toggle ──
    var accessAll = document.getElementById('accessAll');
    var branchChecks = document.querySelectorAll('.branch-check');
    if (accessAll) {
        accessAll.addEventListener('change', function () {
            branchChecks.forEach(function (c) { c.checked = false; c.disabled = accessAll.checked; });
        });
        // Init state
        branchChecks.forEach(function (c) { c.disabled = accessAll.checked; });
    }

    // ── Client-side search ──
    var searchInput = document.getElementById('userSearch');
    var tableBody = document.querySelector('#usersTable tbody');
    if (searchInput && tableBody) {
        searchInput.addEventListener('input', function () {
            var q = this.value.toLowerCase();
            tableBody.querySelectorAll('tr').forEach(function (row) {
                row.style.display = row.textContent.toLowerCase().includes(q) ? '' : 'none';
            });
        });
    }

});
</script>
@endpush

@endsection
