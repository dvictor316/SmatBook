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

    /* ── Avatar ── */
    .user-avatar-circle {
        width: 36px; height: 36px; border-radius: 50%;
        background: linear-gradient(135deg, #1a2236, #2d3a57);
        color: #d4a017; font-weight: 700; font-size: 0.88rem;
        display: flex; align-items: center; justify-content: center;
        flex-shrink: 0;
    }
    .user-avatar-img {
        width: 36px; height: 36px; border-radius: 50%; object-fit: cover; flex-shrink: 0;
    }
    td.td-avatar { width: 52px; padding-right: 4px !important; }

    /* ── Status badge ── */
    .badge-active   { background: #dcfce7; color: #16a34a; border-radius: 20px; padding: 3px 10px; font-size: 0.72rem; font-weight: 700; }
    .badge-inactive { background: #fee2e2; color: #dc2626; border-radius: 20px; padding: 3px 10px; font-size: 0.72rem; font-weight: 700; }

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

        <div class="prokip-tabs">
            <a href="#" class="prokip-tab active">Users</a>
            <a href="{{ route('roles.index') }}" class="prokip-tab">Roles</a>
            <a href="#" class="prokip-tab">Sales Commission Agent</a>
        </div>

        <div class="d-flex align-items-start justify-content-between mb-4 flex-wrap gap-3">
            <div>
                <div class="users-page-title">Users &amp; Roles</div>
                <div class="users-page-sub">Users</div>
            </div>
            <a href="{{ route('users.create') }}" class="btn-add-user">
                <i class="fas fa-plus"></i> Add User
            </a>
        </div>

        @if(session('success'))
            <div class="alert alert-success alert-dismissible fade show">{{ session('success') }}<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
        @endif
        @if(session('error'))
            <div class="alert alert-danger alert-dismissible fade show">{{ session('error') }}<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
        @endif

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
                                <th class="td-avatar"></th>
                                <th>Username</th>
                                <th>Name</th>
                                <th>Role</th>
                                <th>Email</th>
                                <th>Status</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($users as $user)
                            @php
                                $roleName   = $user->roleModel->name ?? ucwords(str_replace('_', ' ', $user->role ?? ''));
                                $isActive   = ($user->is_active ?? $user->status ?? true);
                                $initials   = strtoupper(substr($user->name ?? $user->first_name ?? '?', 0, 1));
                            @endphp
                            <tr>
                                <td class="td-avatar">
                                    @if($user->profile_photo)
                                        <img src="{{ asset('storage/'.$user->profile_photo) }}" alt="" class="user-avatar-img">
                                    @else
                                        <div class="user-avatar-circle">{{ $initials }}</div>
                                    @endif
                                </td>
                                <td><span class="user-username">{{ $user->username ?? $user->name }}</span></td>
                                <td>{{ $user->name }}</td>
                                <td>{{ $roleName }}</td>
                                <td>{{ $user->email }}</td>
                                <td>
                                    @if($isActive)
                                        <span class="badge-active">Active</span>
                                    @else
                                        <span class="badge-inactive">Inactive</span>
                                    @endif
                                </td>
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
                                <td colspan="7" class="text-center py-5 text-muted">No users found.</td>
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

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {

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
