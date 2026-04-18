<?php $page = 'permission'; ?>
@extends('layout.mainlayout')

@push('styles')
<style>
/* ═══════════════════════════════════════════════════════════════
   ROLE PERMISSIONS PAGE  ―  Prokip App Style
═══════════════════════════════════════════════════════════════ */

/* ─── Role badge in breadcrumb ───────────────────────────────── */
.perm-role-badge {
    display: inline-flex; align-items: center; gap: 5px;
    background: #fef3c7; color: #92400e; border: 1px solid #fde68a;
    padding: 2px 10px; border-radius: 20px;
    font-size: 0.75rem; font-weight: 700; letter-spacing: 0.03em;
}

/* ─── Tab bar (matches prokip-tabs from users.blade.php) ─────── */
.perm-tab-bar {
    display: flex; gap: 0; border-bottom: 2px solid #e8eaf0;
    overflow-x: auto; scrollbar-width: none;
}
.perm-tab-bar::-webkit-scrollbar { display: none; }
.perm-tab {
    padding: 13px 22px; font-weight: 600; font-size: 0.9rem; color: #7a869a;
    cursor: pointer; border: none; background: none;
    border-bottom: 3px solid transparent; margin-bottom: -2px;
    white-space: nowrap; display: inline-flex; align-items: center; gap: 6px;
    transition: color .15s, border-color .15s; flex-shrink: 0;
}
.perm-tab:hover { color: #1a2236; }
.perm-tab.active { color: #1a2236; border-bottom-color: #d4a017; }
.perm-tab-cnt {
    background: #f0f2f8; color: #94a3b8;
    border-radius: 10px; font-size: 10px; font-weight: 700; padding: 1px 7px;
}
.perm-tab.active .perm-tab-cnt { background: #fef3c7; color: #d4a017; }

/* ─── Toolbar ─────────────────────────────────────────────────── */
.perm-toolbar {
    display: flex; align-items: center; justify-content: space-between;
    padding: 10px 20px; border-bottom: 1px solid #e8eaf0;
    flex-wrap: wrap; gap: 8px;
}
.perm-toolbar-left  { display: flex; align-items: center; gap: 10px; flex-wrap: wrap; }
.perm-toolbar-right { display: flex; align-items: center; gap: 8px; flex-wrap: wrap; }
.perm-role-info { font-weight: 700; color: #1a2236; font-size: 0.88rem; }
.perm-count-badge {
    background: #fef3c7; color: #92400e; border: 1px solid #fde68a;
    border-radius: 20px; padding: 2px 10px; font-size: 0.75rem; font-weight: 700;
}
.perm-search-wrap { position: relative; }
.perm-search-icon {
    position: absolute; left: 9px; top: 50%; transform: translateY(-50%);
    color: #94a3b8; font-size: 12px; pointer-events: none;
}
.perm-search-box {
    padding: 6px 12px 6px 28px; border: 1px solid #d0d5e0;
    border-radius: 8px; font-size: 0.85rem; width: 200px;
    outline: none; transition: border-color .15s;
}
.perm-search-box:focus { border-color: #d4a017; }
.btn-grant-all {
    background: #d4a017; border: none; color: #fff; padding: 7px 16px;
    border-radius: 8px; font-size: 0.82rem; font-weight: 700; cursor: pointer;
    display: inline-flex; align-items: center; gap: 5px; transition: background 0.2s;
}
.btn-grant-all:hover { background: #b88b12; }
.btn-revoke-all {
    background: #fff; border: 1px solid #e2e8f0; color: #64748b;
    padding: 7px 16px; border-radius: 8px; font-size: 0.82rem; font-weight: 600;
    cursor: pointer; display: inline-flex; align-items: center; gap: 5px; transition: all 0.2s;
}
.btn-revoke-all:hover { border-color: #fca5a5; color: #dc2626; background: #fff5f5; }

/* ─── Tab panels ──────────────────────────────────────────────── */
.perm-panel { display: none; }
.perm-panel.active { display: block; }
.perm-search-active .perm-panel { display: block !important; }
.perm-search-active .perm-tab-bar { display: none !important; }

/* ─── Module section row ──────────────────────────────────────── */
.perm-section { padding: 18px 24px; }
.perm-section.perm-sec--hidden { display: none !important; }
.perm-module-sep { border: none; border-top: 1px solid #e8eaf0; margin: 0; }

/* Two-column layout */
.perm-row { display: flex; gap: 28px; }
.perm-row-left { width: 190px; flex-shrink: 0; }
.perm-row-right { flex: 1; display: flex; flex-wrap: wrap; align-content: flex-start; }

/* Module name */
.perm-mod-name { font-weight: 700; color: #1a2236; font-size: 0.92rem; margin-bottom: 8px; }
.perm-section--granted .perm-mod-name { color: #15803d; }

/* Select All checkbox */
.perm-select-all-wrap {
    display: flex; align-items: center; gap: 6px;
    font-size: 0.82rem; color: #555; cursor: pointer;
    user-select: none; margin-bottom: 5px;
}
.perm-select-all-wrap input { accent-color: #d4a017; cursor: pointer; width: 14px; height: 14px; }

/* Count */
.perm-sec-count { font-size: 0.75rem; color: #94a3b8; }
.perm-section--granted .perm-sec-count { color: #16a34a; font-weight: 700; }

/* Permission items — 2 per row */
.perm-item {
    flex: 0 0 50%; display: flex; align-items: center; gap: 7px;
    padding: 4px 0; font-size: 0.88rem; color: #374151;
    cursor: pointer; user-select: none;
}
.perm-item input[type=checkbox],
.perm-item input[type=radio] {
    accent-color: #2563eb; cursor: pointer; flex-shrink: 0;
    width: 14px; height: 14px;
}
.perm-item input:checked + span { color: #1a2236; font-weight: 600; }

/* Sub-headings and separators */
.perm-sub-title {
    flex: 0 0 100%; font-size: 0.7rem; font-weight: 700;
    text-transform: uppercase; letter-spacing: 0.06em;
    color: #94a3b8; padding: 6px 0 2px;
}
.perm-sep { flex: 0 0 100%; border: none; border-top: 1px dashed #e8eaf0; margin: 4px 0; }

/* ─── Sticky save bar ─────────────────────────────────────────── */
.perm-action-bar {
    position: sticky; bottom: 0; background: #fff;
    border-top: 1px solid #e8eaf0; padding: 14px 24px;
    display: flex; justify-content: flex-end; gap: 10px;
    z-index: 50; box-shadow: 0 -4px 16px rgba(0,0,0,.06);
}
.btn-perm-save {
    background: #d4a017; border: none; color: #fff; padding: 10px 30px;
    border-radius: 8px; font-size: 0.9rem; font-weight: 700;
    display: inline-flex; align-items: center; gap: 7px;
    cursor: pointer; transition: background 0.2s;
}
.btn-perm-save:hover { background: #b88b12; }
.btn-perm-cancel {
    background: #fff; border: 1px solid #d0d5e0; color: #6b7280;
    padding: 10px 24px; border-radius: 8px; font-size: 0.9rem; font-weight: 600;
    text-decoration: none; display: inline-flex; align-items: center; transition: all 0.2s;
}
.btn-perm-cancel:hover { background: #f9fafb; color: #374151; }

@media (max-width: 768px) {
    .perm-row { flex-direction: column; gap: 10px; }
    .perm-row-left { width: 100%; display: flex; align-items: center; gap: 14px; flex-wrap: wrap; }
    .perm-item { flex: 0 0 100%; }
}
    padding: 22px 28px 20px; border-radius: 14px; color: #fff;
    display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 14px;
    margin-bottom: 14px; box-shadow: 0 8px 28px rgba(26,34,54,0.22);
}
.perm-hero-left { display: flex; align-items: center; gap: 16px; }
.perm-hero-icon {
    width: 46px; height: 46px; background: rgba(212,160,23,0.18);
    border: 2px solid rgba(212,160,23,0.4); border-radius: 12px;
    display: flex; align-items: center; justify-content: center;
    font-size: 1.35rem; color: #d4a017; flex-shrink: 0;
}
.perm-hero-title { font-size: 1.25rem; font-weight: 700; color: #fff; margin-bottom: 2px; line-height: 1.2; }
.perm-hero-sub   { font-size: 0.82rem; color: rgba(255,255,255,0.65); }
.perm-role-badge {
    display: inline-flex; align-items: center; gap: 5px;
    background: rgba(212,160,23,0.2); border: 1px solid rgba(212,160,23,0.5);
    color: #f5c842; padding: 3px 10px; border-radius: 20px;
    font-size: 0.75rem; font-weight: 700; letter-spacing: 0.04em; text-transform: uppercase;
}
.perm-hero-actions { display: flex; align-items: center; gap: 10px; flex-wrap: wrap; }
.btn-perm-back {
    background: rgba(255,255,255,0.1); border: 1px solid rgba(255,255,255,0.25);
    color: #fff; padding: 7px 18px; border-radius: 20px; font-size: 0.82rem; font-weight: 600;
    text-decoration: none; display: inline-flex; align-items: center; gap: 6px; transition: background 0.2s;
}
.btn-perm-back:hover { background: rgba(255,255,255,0.2); color: #fff; }

/* ─── Main shell card ─────────────────────────────────────────── */
.perm-main-card {
    border-radius: 14px; overflow: hidden;
    box-shadow: 0 4px 18px rgba(0,0,0,.09);
    border: 1px solid #d5dbe8; margin-bottom: 14px;
}

/* ─── Category tab bar ────────────────────────────────────────── */
.perm-tab-bar {
    display: flex; gap: 4px; padding: 10px 12px 0;
    background: #f2f5fb; border-bottom: 2px solid #d5dbe8;
    overflow-x: auto; scrollbar-width: none;
}
.perm-tab-bar::-webkit-scrollbar { display: none; }
.perm-tab {
    padding: 7px 14px 9px; font-size: 11.5px; font-weight: 700; color: #7a8aad;
    cursor: pointer; border-radius: 8px 8px 0 0; white-space: nowrap;
    background: transparent; border: none; border-bottom: 3px solid transparent;
    display: inline-flex; align-items: center; gap: 5px; margin-bottom: -2px;
    transition: all .15s; flex-shrink: 0;
}
.perm-tab:hover { color: #1a2236; background: #e8edf7; }
.perm-tab.active {
    color: #fff; background: linear-gradient(160deg, #1a2236 0%, #2d3a57 100%);
    border-bottom-color: #d4a017; box-shadow: 0 -2px 8px rgba(26,34,54,0.12);
}
.perm-tab-cnt {
    display: inline-flex; align-items: center; justify-content: center;
    min-width: 16px; height: 16px; border-radius: 8px;
    font-size: 9px; font-weight: 800; padding: 0 4px;
    background: rgba(255,255,255,0.15); color: rgba(255,255,255,0.85);
}
.perm-tab:not(.active) .perm-tab-cnt { background: #dde3f0; color: #5a6a8a; }

/* ─── Dark Toolbar ────────────────────────────────────────────── */
.perm-toolbar {
    background: linear-gradient(90deg, #1a2236 0%, #28364f 100%);
    padding: 9px 14px; display: flex; align-items: center; gap: 10px; flex-wrap: wrap;
}
.perm-toolbar-left  { display: flex; align-items: center; gap: 8px; flex: 1; flex-wrap: wrap; }
.perm-toolbar-right { display: flex; align-items: center; gap: 8px; flex-wrap: wrap; }
.perm-toolbar-title { font-weight: 700; font-size: 0.83rem; color: #e2e8f0; }
.perm-count-badge {
    background: rgba(212,160,23,0.2); color: #fbbf24;
    border: 1px solid rgba(212,160,23,0.35); border-radius: 20px;
    padding: 2px 10px; font-size: 0.72rem; font-weight: 700;
}
.perm-search-wrap { position: relative; }
.perm-search-icon {
    position: absolute; left: 9px; top: 50%; transform: translateY(-50%);
    color: rgba(255,255,255,0.4); font-size: 11px; pointer-events: none;
}
.perm-search-box {
    padding: 5px 10px 5px 26px; border: 1px solid rgba(255,255,255,0.15);
    border-radius: 20px; font-size: 11.5px; color: #fff;
    background: rgba(255,255,255,0.08); width: 180px; outline: none; transition: all .15s;
}
.perm-search-box::placeholder { color: rgba(255,255,255,0.35); }
.perm-search-box:focus { border-color: rgba(212,160,23,0.7); background: rgba(255,255,255,0.12); }
.btn-grant-all {
    background: #d4a017; border: none; color: #fff; padding: 5px 14px;
    border-radius: 20px; font-size: 11px; font-weight: 700; cursor: pointer;
    display: inline-flex; align-items: center; gap: 5px; transition: background 0.2s;
    box-shadow: 0 2px 8px rgba(212,160,23,0.3);
}
.btn-grant-all:hover { background: #b88b12; }
.btn-revoke-all {
    background: transparent; border: 1px solid rgba(255,255,255,0.2);
    color: rgba(255,255,255,0.7); padding: 5px 14px; border-radius: 20px;
    font-size: 11px; font-weight: 600; cursor: pointer;
    display: inline-flex; align-items: center; gap: 5px; transition: all 0.2s;
}
.btn-revoke-all:hover { border-color: #f87171; color: #fca5a5; background: rgba(239,68,68,0.12); }

/* ─── Tab panels ──────────────────────────────────────────────── */
.perm-panel { display: none; padding: 10px; background: #e8ecf4; }
.perm-panel.active { display: block; }
.perm-search-active .perm-panel { display: block !important; }
.perm-search-active .perm-tab-bar { display: none; }

/* ─── Cards grid ──────────────────────────────────────────────── */
.perm-cards-grid {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 8px;
}
@media (max-width: 1100px) { .perm-cards-grid { grid-template-columns: repeat(2, 1fr); } }
@media (max-width: 640px)  { .perm-cards-grid { grid-template-columns: 1fr; } }

/* ─── Module card ─────────────────────────────────────────────── */
.perm-section {
    border-radius: 10px; overflow: hidden; background: #fff;
    border: 1px solid #dde3ee; box-shadow: 0 1px 4px rgba(0,0,0,0.05);
    transition: box-shadow .15s, transform .15s;
}
.perm-section:hover { box-shadow: 0 4px 14px rgba(26,34,54,0.1); transform: translateY(-1px); }
.perm-section.perm-section--granted { border-color: #60a5fa; }
.perm-section.perm-sec--hidden { display: none !important; }

/* Dark card header */
.perm-sec-head {
    display: flex; align-items: center; gap: 8px; padding: 9px 11px;
    cursor: pointer; user-select: none;
    background: linear-gradient(135deg, #1a2236 0%, #2d3a57 100%);
}
.perm-section--granted .perm-sec-head {
    background: linear-gradient(135deg, #0c4a6e 0%, #1e40af 100%);
}
.perm-sec-icon {
    width: 28px; height: 28px; border-radius: 7px;
    display: flex; align-items: center; justify-content: center;
    font-size: 0.75rem; flex-shrink: 0;
    background: rgba(255,255,255,0.12); color: #c7d2e8;
}
.perm-sec-name { font-size: 11.5px; font-weight: 700; color: #fff; flex: 1; line-height: 1.3; }
.perm-sec-count {
    font-size: 10px; color: rgba(255,255,255,0.55);
    background: rgba(255,255,255,0.08); border-radius: 8px;
    padding: 1px 7px; white-space: nowrap; flex-shrink: 0;
}
.perm-section--granted .perm-sec-count { color: #86efac; background: rgba(22,163,74,0.2); }
.perm-sec-chevron { color: rgba(255,255,255,0.45); font-size: 9px; transition: transform .2s; flex-shrink: 0; }
.perm-section.collapsed .perm-sec-chevron { transform: rotate(-90deg); }
.perm-section.collapsed .perm-sec-body { display: none; }

/* Toggle switch */
.perm-card-toggle { display: flex; align-items: center; gap: 3px; cursor: pointer; flex-shrink: 0; }
.perm-card-toggle input[type=checkbox] { display: none; }
.perm-toggle-track {
    width: 24px; height: 13px; background: rgba(255,255,255,0.18); border-radius: 13px;
    position: relative; transition: background 0.2s; flex-shrink: 0;
}
.perm-toggle-thumb {
    width: 9px; height: 9px; background: rgba(255,255,255,0.55); border-radius: 50%;
    position: absolute; top: 2px; left: 2px; transition: transform 0.2s;
}
.perm-card-toggle input:checked ~ .perm-toggle-track { background: #d4a017; }
.perm-card-toggle input:checked ~ .perm-toggle-track .perm-toggle-thumb { transform: translateX(11px); background: #fff; }
.perm-toggle-label { font-size: 9.5px; font-weight: 700; color: rgba(255,255,255,0.55); }

/* Card body */
.perm-sec-body { padding: 6px 8px 8px; }
.perm-items-grid { display: flex; flex-wrap: wrap; }

.perm-sub-title {
    flex: 0 0 100%; font-size: 0.6rem; font-weight: 800; text-transform: uppercase;
    letter-spacing: 0.07em; color: #94a3b8; padding: 4px 4px 2px; margin-top: 1px;
}
.perm-sep { flex: 0 0 100%; border: none; border-top: 1px dashed #e8edf5; margin: 3px 0; }

/* Permission items — 2 columns inside card */
.perm-item {
    flex: 0 0 50%;
    display: flex; align-items: center; gap: 5px;
    font-size: 11px; color: #374151; cursor: pointer;
    padding: 3px 4px; border-radius: 4px; line-height: 1.3;
    transition: background 0.1s; -webkit-user-select: none; user-select: none;
}
.perm-item:hover { background: #f0f4ff; }
.perm-item input[type=checkbox],
.perm-item input[type=radio] {
    width: 12px; height: 12px; accent-color: #2563eb; flex-shrink: 0; cursor: pointer; margin: 0;
}
.perm-item input:checked + span { color: #1a2236; font-weight: 600; }
@media (max-width: 575px) { .perm-item { flex: 0 0 100%; } }

/* ─── Sticky save bar ─────────────────────────────────────────── */
.perm-action-bar {
    position: sticky; bottom: 0;
    background: linear-gradient(90deg, #1a2236 0%, #2d3a57 100%);
    padding: 12px 20px; display: flex; justify-content: center; gap: 10px;
    z-index: 50; box-shadow: 0 -4px 20px rgba(26,34,54,0.3);
}
.btn-perm-save {
    background: #d4a017; border: none; color: #fff; padding: 10px 32px;
    border-radius: 20px; font-size: 0.87rem; font-weight: 700;
    display: inline-flex; align-items: center; gap: 7px; cursor: pointer;
    transition: background 0.2s; box-shadow: 0 4px 12px rgba(212,160,23,0.35);
}
.btn-perm-save:hover { background: #b88b12; }
.btn-perm-cancel {
    background: rgba(255,255,255,0.1); border: 1px solid rgba(255,255,255,0.2);
    color: rgba(255,255,255,0.75); padding: 10px 22px; border-radius: 20px;
    font-size: 0.87rem; font-weight: 600;
    text-decoration: none; display: inline-flex; align-items: center; transition: all 0.2s;
}
.btn-perm-cancel:hover { background: rgba(255,255,255,0.18); color: #fff; }

/* ─── Icon accent colors on dark header ───────────────────────── */
.perm-sec-head .icon-blue   { color: #93c5fd; }
.perm-sec-head .icon-gold   { color: #fcd34d; }
.perm-sec-head .icon-green  { color: #86efac; }
.perm-sec-head .icon-teal   { color: #5eead4; }
.perm-sec-head .icon-purple { color: #d8b4fe; }
.perm-sec-head .icon-rose   { color: #fda4af; }
.perm-sec-head .icon-indigo { color: #a5b4fc; }
.perm-sec-head .icon-orange { color: #fdba74; }
.perm-sec-head .icon-sky    { color: #7dd3fc; }
.perm-sec-head .icon-pink   { color: #f9a8d4; }
.perm-sec-head .icon-lime   { color: #bef264; }
.perm-sec-head .icon-slate  { color: #cbd5e1; }
</style>
@endpush

@section('content')
<div class="page-wrapper">
<div class="content container-fluid">

    <div class="page-header">
        <div class="row align-items-center">
            <div class="col">
                <h3 class="page-title">Role Permissions</h3>
                <ul class="breadcrumb">
                    <li class="breadcrumb-item"><a href="{{ url('dashboard') }}">Dashboard</a></li>
                    <li class="breadcrumb-item"><a href="{{ route('roles.index') }}">Roles</a></li>
                    <li class="breadcrumb-item active">
                        Permissions &mdash;
                        <span class="perm-role-badge"><i class="fa fa-user-tag"></i> {{ $role->name }}</span>
                    </li>
                </ul>
            </div>
            <div class="col-auto">
                <a href="{{ route('roles.index') }}" class="btn btn-secondary btn-sm rounded-pill px-3">
                    <i class="fa fa-arrow-left me-1"></i> Back to Roles
                </a>
            </div>
        </div>
    </div>

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show mb-3" role="alert">
            <i class="fa fa-check-circle me-2"></i>{{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    <form action="{{ route('roles.permissions.update') }}" method="POST" id="permForm">
        @csrf
        <input type="hidden" name="role_id" value="{{ $role->id }}">

        @php
            $assigned    = $assignedPermissions ?? [];
            $permChecked = fn (string $p) => in_array($p, $assigned, true);

            $modules = [
                /* ── CORE ─────────────────────────────────────── */
                ['group'=>'Dashboard',       'section'=>'dashboard',           'icon'=>'fa-tachometer-alt',    'ic'=>'icon-blue',  'cat'=>'Core',
                 'items'=>[
                     ['t'=>'cb','p'=>'dashboard.overview.view','l'=>'View Dashboard'],
                 ]],
                ['group'=>'User Management', 'section'=>'user_mgmt',           'icon'=>'fa-users',             'ic'=>'icon-indigo','cat'=>'Core',
                 'items'=>[
                     ['t'=>'cb','p'=>'user_management.users.view',  'l'=>'View Users'],
                     ['t'=>'cb','p'=>'user_management.users.create','l'=>'Add User'],
                     ['t'=>'cb','p'=>'user_management.users.edit',  'l'=>'Edit User'],
                     ['t'=>'cb','p'=>'user_management.users.delete','l'=>'Delete User'],
                 ]],
                ['group'=>'Roles',           'section'=>'roles',               'icon'=>'fa-user-shield',       'ic'=>'icon-purple','cat'=>'Core',
                 'items'=>[
                     ['t'=>'cb','p'=>'roles.roles.view',  'l'=>'View Roles'],
                     ['t'=>'cb','p'=>'roles.roles.create','l'=>'Add Role'],
                     ['t'=>'cb','p'=>'roles.roles.edit',  'l'=>'Edit Role'],
                     ['t'=>'cb','p'=>'roles.roles.delete','l'=>'Delete Role'],
                 ]],

                /* ── SALES & CRM ──────────────────────────────── */
                ['group'=>'Customer',        'section'=>'customer',            'icon'=>'fa-address-book',      'ic'=>'icon-teal',  'cat'=>'Sales & CRM',
                 'items'=>[
                     ['t'=>'hd','l'=>'View Access'],
                     ['t'=>'rd','rn'=>'perm_radio[customers_view]','p'=>'customers.customers.view_all','l'=>'View All Customers'],
                     ['t'=>'rd','rn'=>'perm_radio[customers_view]','p'=>'customers.customers.view_own','l'=>'View Own Customers'],
                     ['t'=>'sp'],
                     ['t'=>'hd','l'=>'No-Sell Filter'],
                     ['t'=>'rd','rn'=>'perm_radio[customers_no_sell]','p'=>'customers.customers.view_no_sell_1month', 'l'=>'No Sell — 1 Month'],
                     ['t'=>'rd','rn'=>'perm_radio[customers_no_sell]','p'=>'customers.customers.view_no_sell_3months','l'=>'No Sell — 3 Months'],
                     ['t'=>'rd','rn'=>'perm_radio[customers_no_sell]','p'=>'customers.customers.view_no_sell_6months','l'=>'No Sell — 6 Months'],
                     ['t'=>'rd','rn'=>'perm_radio[customers_no_sell]','p'=>'customers.customers.view_no_sell_1year',  'l'=>'No Sell — 1 Year'],
                     ['t'=>'rd','rn'=>'perm_radio[customers_no_sell]','p'=>'customers.customers.view_irrespective',   'l'=>'Irrespective of Sell'],
                     ['t'=>'sp'],
                     ['t'=>'cb','p'=>'customers.customers.create','l'=>'Add Customer'],
                     ['t'=>'cb','p'=>'customers.customers.edit',  'l'=>'Edit Customer'],
                     ['t'=>'cb','p'=>'customers.customers.delete','l'=>'Delete Customer'],
                 ]],
                ['group'=>'Sales / Invoice', 'section'=>'invoices',            'icon'=>'fa-file-invoice-dollar','ic'=>'icon-green','cat'=>'Sales & CRM',
                 'items'=>[
                     ['t'=>'rd','rn'=>'perm_radio[invoices_view]','p'=>'sales.invoices.view_all','l'=>'View All Invoices'],
                     ['t'=>'rd','rn'=>'perm_radio[invoices_view]','p'=>'sales.invoices.view_own','l'=>'View Own Invoices'],
                     ['t'=>'sp'],
                     ['t'=>'cb','p'=>'sales.invoices.create','l'=>'Add Invoice'],
                     ['t'=>'cb','p'=>'sales.invoices.edit',  'l'=>'Edit Invoice'],
                     ['t'=>'cb','p'=>'sales.invoices.delete','l'=>'Delete Invoice'],
                 ]],
                ['group'=>'POS Sales',       'section'=>'pos',                 'icon'=>'fa-cash-register',     'ic'=>'icon-gold',  'cat'=>'Sales & CRM',
                 'items'=>[
                     ['t'=>'cb','p'=>'sales.pos.view',  'l'=>'View POS Sales'],
                     ['t'=>'cb','p'=>'sales.pos.create','l'=>'Create POS Sale'],
                 ]],
                ['group'=>'Quotations',      'section'=>'quotations',          'icon'=>'fa-file-alt',          'ic'=>'icon-sky',   'cat'=>'Sales & CRM',
                 'items'=>[
                     ['t'=>'rd','rn'=>'perm_radio[quotations_view]','p'=>'sales.quotations.view_all','l'=>'View All Quotations'],
                     ['t'=>'rd','rn'=>'perm_radio[quotations_view]','p'=>'sales.quotations.view_own','l'=>'View Own Quotations'],
                     ['t'=>'sp'],
                     ['t'=>'cb','p'=>'sales.quotations.create','l'=>'Add Quotation'],
                     ['t'=>'cb','p'=>'sales.quotations.edit',  'l'=>'Edit Quotation'],
                     ['t'=>'cb','p'=>'sales.quotations.delete','l'=>'Delete Quotation'],
                 ]],
                ['group'=>'Estimates',       'section'=>'estimates',           'icon'=>'fa-file-invoice',      'ic'=>'icon-lime',  'cat'=>'Sales & CRM',
                 'items'=>[
                     ['t'=>'cb','p'=>'estimates.estimates.view',  'l'=>'View Estimates'],
                     ['t'=>'cb','p'=>'estimates.estimates.create','l'=>'Add Estimate'],
                     ['t'=>'cb','p'=>'estimates.estimates.edit',  'l'=>'Edit Estimate'],
                     ['t'=>'cb','p'=>'estimates.estimates.delete','l'=>'Delete Estimate'],
                 ]],
                ['group'=>'Recurring Invoices','section'=>'recurring_invoices','icon'=>'fa-calendar-alt',      'ic'=>'icon-teal',  'cat'=>'Sales & CRM',
                 'items'=>[
                     ['t'=>'cb','p'=>'recurring_invoices.recurring_invoices.view',  'l'=>'View Recurring Invoices'],
                     ['t'=>'cb','p'=>'recurring_invoices.recurring_invoices.create','l'=>'Add Recurring Invoice'],
                     ['t'=>'cb','p'=>'recurring_invoices.recurring_invoices.edit',  'l'=>'Edit Recurring Invoice'],
                     ['t'=>'cb','p'=>'recurring_invoices.recurring_invoices.delete','l'=>'Delete Recurring Invoice'],
                 ]],
                ['group'=>'Follow-Ups',      'section'=>'follow_ups',          'icon'=>'fa-phone-alt',         'ic'=>'icon-pink',  'cat'=>'Sales & CRM',
                 'items'=>[
                     ['t'=>'cb','p'=>'follow_ups.follow_ups.view',  'l'=>'View Follow-Ups'],
                     ['t'=>'cb','p'=>'follow_ups.follow_ups.create','l'=>'Add Follow-Up'],
                     ['t'=>'cb','p'=>'follow_ups.follow_ups.edit',  'l'=>'Edit Follow-Up'],
                     ['t'=>'cb','p'=>'follow_ups.follow_ups.delete','l'=>'Delete Follow-Up'],
                 ]],
                ['group'=>'Collections Hub', 'section'=>'collections_hub',     'icon'=>'fa-coins',             'ic'=>'icon-gold',  'cat'=>'Sales & CRM',
                 'items'=>[
                     ['t'=>'cb','p'=>'collections_hub.collections_hub.view',  'l'=>'View Collections Hub'],
                     ['t'=>'cb','p'=>'collections_hub.collections_hub.create','l'=>'Add Collection'],
                     ['t'=>'cb','p'=>'collections_hub.collections_hub.edit',  'l'=>'Edit Collection'],
                 ]],

                /* ── PURCHASING ────────────────────────────────── */
                ['group'=>'Supplier',        'section'=>'supplier',            'icon'=>'fa-truck',             'ic'=>'icon-orange','cat'=>'Purchasing',
                 'items'=>[
                     ['t'=>'rd','rn'=>'perm_radio[vendors_view]','p'=>'vendors.vendors.view_all','l'=>'View All Suppliers'],
                     ['t'=>'rd','rn'=>'perm_radio[vendors_view]','p'=>'vendors.vendors.view_own','l'=>'View Own Suppliers'],
                     ['t'=>'sp'],
                     ['t'=>'cb','p'=>'vendors.vendors.create','l'=>'Add Supplier'],
                     ['t'=>'cb','p'=>'vendors.vendors.edit',  'l'=>'Edit Supplier'],
                     ['t'=>'cb','p'=>'vendors.vendors.delete','l'=>'Delete Supplier'],
                 ]],
                ['group'=>'Purchase',        'section'=>'purchase',            'icon'=>'fa-shopping-cart',     'ic'=>'icon-rose',  'cat'=>'Purchasing',
                 'items'=>[
                     ['t'=>'rd','rn'=>'perm_radio[purchases_view]','p'=>'purchases.purchases.view_all','l'=>'View All Purchases'],
                     ['t'=>'rd','rn'=>'perm_radio[purchases_view]','p'=>'purchases.purchases.view_own','l'=>'View Own Purchases'],
                     ['t'=>'sp'],
                     ['t'=>'cb','p'=>'purchases.purchases.create',        'l'=>'Add Purchase'],
                     ['t'=>'cb','p'=>'purchases.purchases.edit',          'l'=>'Edit Purchase'],
                     ['t'=>'cb','p'=>'purchases.purchases.delete',        'l'=>'Delete Purchase'],
                     ['t'=>'cb','p'=>'purchases.purchases.add_payment',   'l'=>'Add Payment'],
                     ['t'=>'cb','p'=>'purchases.purchases.edit_payment',  'l'=>'Edit Payment'],
                     ['t'=>'cb','p'=>'purchases.purchases.delete_payment','l'=>'Delete Payment'],
                 ]],
                ['group'=>'Purchase Orders', 'section'=>'purchase_orders',     'icon'=>'fa-clipboard-list',    'ic'=>'icon-indigo','cat'=>'Purchasing',
                 'items'=>[
                     ['t'=>'cb','p'=>'purchase_orders.purchase_orders.view',  'l'=>'View Purchase Orders'],
                     ['t'=>'cb','p'=>'purchase_orders.purchase_orders.create','l'=>'Add Purchase Order'],
                     ['t'=>'cb','p'=>'purchase_orders.purchase_orders.edit',  'l'=>'Edit Purchase Order'],
                     ['t'=>'cb','p'=>'purchase_orders.purchase_orders.delete','l'=>'Delete Purchase Order'],
                 ]],

                /* ── INVENTORY ─────────────────────────────────── */
                ['group'=>'Product',         'section'=>'product',             'icon'=>'fa-box',               'ic'=>'icon-orange','cat'=>'Inventory',
                 'items'=>[
                     ['t'=>'cb','p'=>'inventory.products.view',               'l'=>'View Products'],
                     ['t'=>'cb','p'=>'inventory.products.create',             'l'=>'Add Product'],
                     ['t'=>'cb','p'=>'inventory.products.edit',               'l'=>'Edit Product'],
                     ['t'=>'cb','p'=>'inventory.products.delete',             'l'=>'Delete Product'],
                     ['t'=>'cb','p'=>'inventory.products.add_opening_stock',  'l'=>'Add Opening Stock'],
                     ['t'=>'cb','p'=>'inventory.products.view_purchase_price','l'=>'View Purchase Price'],
                 ]],
                ['group'=>'Stock Manager',   'section'=>'stock',               'icon'=>'fa-warehouse',         'ic'=>'icon-teal',  'cat'=>'Inventory',
                 'items'=>[
                     ['t'=>'cb','p'=>'inventory.stock.view','l'=>'View Stock'],
                     ['t'=>'cb','p'=>'inventory.stock.edit','l'=>'Edit Stock'],
                     ['t'=>'sp'],
                     ['t'=>'cb','p'=>'inventory.categories.view',  'l'=>'View Categories'],
                     ['t'=>'cb','p'=>'inventory.categories.create','l'=>'Add Category'],
                     ['t'=>'cb','p'=>'inventory.categories.edit',  'l'=>'Edit Category'],
                     ['t'=>'cb','p'=>'inventory.categories.delete','l'=>'Delete Category'],
                 ]],

                /* ── FINANCE ───────────────────────────────────── */
                ['group'=>'Expenses',        'section'=>'expenses',            'icon'=>'fa-receipt',           'ic'=>'icon-rose',  'cat'=>'Finance',
                 'items'=>[
                     ['t'=>'cb','p'=>'finance.expenses.view',  'l'=>'View Expenses'],
                     ['t'=>'cb','p'=>'finance.expenses.create','l'=>'Add Expense'],
                     ['t'=>'cb','p'=>'finance.expenses.edit',  'l'=>'Edit Expense'],
                     ['t'=>'cb','p'=>'finance.expenses.delete','l'=>'Delete Expense'],
                 ]],
                ['group'=>'Payments',        'section'=>'payments',            'icon'=>'fa-credit-card',       'ic'=>'icon-green', 'cat'=>'Finance',
                 'items'=>[
                     ['t'=>'cb','p'=>'finance.payments.view',  'l'=>'View Payments'],
                     ['t'=>'cb','p'=>'finance.payments.create','l'=>'Add Payment'],
                     ['t'=>'cb','p'=>'finance.payments.edit',  'l'=>'Edit Payment'],
                 ]],
                ['group'=>'Accounting',      'section'=>'accounting',          'icon'=>'fa-book',              'ic'=>'icon-indigo','cat'=>'Finance',
                 'items'=>[
                     ['t'=>'hd','l'=>'Chart of Accounts'],
                     ['t'=>'cb','p'=>'accounting.chart_of_accounts.view',  'l'=>'View Accounts'],
                     ['t'=>'cb','p'=>'accounting.chart_of_accounts.create','l'=>'Add Account'],
                     ['t'=>'cb','p'=>'accounting.chart_of_accounts.edit',  'l'=>'Edit Account'],
                     ['t'=>'cb','p'=>'accounting.chart_of_accounts.delete','l'=>'Delete Account'],
                     ['t'=>'sp'],
                     ['t'=>'hd','l'=>'Bank Reconciliation'],
                     ['t'=>'cb','p'=>'accounting.bank_reconciliation.view','l'=>'View Reconciliation'],
                     ['t'=>'cb','p'=>'accounting.bank_reconciliation.edit','l'=>'Edit Reconciliation'],
                     ['t'=>'sp'],
                     ['t'=>'hd','l'=>'Manual Journal'],
                     ['t'=>'cb','p'=>'accounting.manual_journal.view',  'l'=>'View Journal'],
                     ['t'=>'cb','p'=>'accounting.manual_journal.create','l'=>'Add Entry'],
                     ['t'=>'cb','p'=>'accounting.manual_journal.edit',  'l'=>'Edit Entry'],
                     ['t'=>'cb','p'=>'accounting.manual_journal.delete','l'=>'Delete Entry'],
                 ]],
                ['group'=>'Recurring Transactions','section'=>'recurring_transactions','icon'=>'fa-sync-alt',  'ic'=>'icon-sky',   'cat'=>'Finance',
                 'items'=>[
                     ['t'=>'cb','p'=>'recurring_transactions.recurring_transactions.view',  'l'=>'View Recurring Transactions'],
                     ['t'=>'cb','p'=>'recurring_transactions.recurring_transactions.create','l'=>'Add Recurring Transaction'],
                     ['t'=>'cb','p'=>'recurring_transactions.recurring_transactions.edit',  'l'=>'Edit Recurring Transaction'],
                     ['t'=>'cb','p'=>'recurring_transactions.recurring_transactions.delete','l'=>'Delete Recurring Transaction'],
                 ]],
                ['group'=>'Payment Summary',  'section'=>'payment_summary',    'icon'=>'fa-wallet',            'ic'=>'icon-gold',  'cat'=>'Finance',
                 'items'=>[
                     ['t'=>'cb','p'=>'payment_summary.payment_summary.view','l'=>'View Payment Summary'],
                 ]],

                /* ── BUDGETING ─────────────────────────────────── */
                ['group'=>'Budgets',         'section'=>'budgets',             'icon'=>'fa-chart-pie',         'ic'=>'icon-purple','cat'=>'Budgeting',
                 'items'=>[
                     ['t'=>'cb','p'=>'budgets.budgets.view',  'l'=>'View Budgets'],
                     ['t'=>'cb','p'=>'budgets.budgets.create','l'=>'Add Budget'],
                     ['t'=>'cb','p'=>'budgets.budgets.edit',  'l'=>'Edit Budget'],
                     ['t'=>'cb','p'=>'budgets.budgets.delete','l'=>'Delete Budget'],
                 ]],
                ['group'=>'Fixed Assets',    'section'=>'fixed_assets',        'icon'=>'fa-building',          'ic'=>'icon-slate', 'cat'=>'Budgeting',
                 'items'=>[
                     ['t'=>'cb','p'=>'fixed_assets.fixed_assets.view',  'l'=>'View Fixed Assets'],
                     ['t'=>'cb','p'=>'fixed_assets.fixed_assets.create','l'=>'Add Fixed Asset'],
                     ['t'=>'cb','p'=>'fixed_assets.fixed_assets.edit',  'l'=>'Edit Fixed Asset'],
                     ['t'=>'cb','p'=>'fixed_assets.fixed_assets.delete','l'=>'Delete Fixed Asset'],
                 ]],
                ['group'=>'Expense Claims',  'section'=>'expense_claims',      'icon'=>'fa-file-medical',      'ic'=>'icon-rose',  'cat'=>'Budgeting',
                 'items'=>[
                     ['t'=>'cb','p'=>'expense_claims.expense_claims.view',  'l'=>'View Expense Claims'],
                     ['t'=>'cb','p'=>'expense_claims.expense_claims.create','l'=>'Submit Expense Claim'],
                     ['t'=>'cb','p'=>'expense_claims.expense_claims.edit',  'l'=>'Edit Expense Claim'],
                     ['t'=>'cb','p'=>'expense_claims.expense_claims.delete','l'=>'Delete Expense Claim'],
                 ]],

                /* ── HR & PAYROLL ──────────────────────────────── */
                ['group'=>'Payroll',         'section'=>'payroll',             'icon'=>'fa-money-bill-wave',   'ic'=>'icon-green', 'cat'=>'HR & Payroll',
                 'items'=>[
                     ['t'=>'cb','p'=>'payroll.payroll.view',  'l'=>'View Payroll'],
                     ['t'=>'cb','p'=>'payroll.payroll.create','l'=>'Create Payroll'],
                     ['t'=>'cb','p'=>'payroll.payroll.edit',  'l'=>'Edit Payroll'],
                 ]],
                ['group'=>'Projects',        'section'=>'projects',            'icon'=>'fa-project-diagram',   'ic'=>'icon-blue',  'cat'=>'HR & Payroll',
                 'items'=>[
                     ['t'=>'cb','p'=>'projects.projects.view',  'l'=>'View Projects'],
                     ['t'=>'cb','p'=>'projects.projects.create','l'=>'Add Project'],
                     ['t'=>'cb','p'=>'projects.projects.edit',  'l'=>'Edit Project'],
                     ['t'=>'cb','p'=>'projects.projects.delete','l'=>'Delete Project'],
                 ]],

                /* ── COMPLIANCE ────────────────────────────────── */
                ['group'=>'Tax',             'section'=>'tax',                 'icon'=>'fa-percentage',        'ic'=>'icon-rose',  'cat'=>'Compliance',
                 'items'=>[
                     ['t'=>'cb','p'=>'tax.filings.view',  'l'=>'View Tax Filings'],
                     ['t'=>'cb','p'=>'tax.filings.create','l'=>'Add Tax Filing'],
                     ['t'=>'cb','p'=>'tax.filings.edit',  'l'=>'Edit Tax Filing'],
                 ]],
                ['group'=>'Approval Queue',  'section'=>'approval_queue',      'icon'=>'fa-check-double',      'ic'=>'icon-teal',  'cat'=>'Compliance',
                 'items'=>[
                     ['t'=>'cb','p'=>'approval_queue.approval_queue.view','l'=>'View Approval Queue'],
                     ['t'=>'cb','p'=>'approval_queue.approval_queue.edit','l'=>'Approve / Reject Requests'],
                 ]],
                ['group'=>'Period Close',    'section'=>'period_close',        'icon'=>'fa-lock',              'ic'=>'icon-slate', 'cat'=>'Compliance',
                 'items'=>[
                     ['t'=>'cb','p'=>'period_close.period_close.view',   'l'=>'View Period Close'],
                     ['t'=>'cb','p'=>'period_close.period_close.execute','l'=>'Execute Period Close'],
                 ]],
                ['group'=>'Activity Log',    'section'=>'activity_log',        'icon'=>'fa-history',           'ic'=>'icon-slate', 'cat'=>'Compliance',
                 'items'=>[
                     ['t'=>'cb','p'=>'activity_log.activity_log.view','l'=>'View Activity Log'],
                 ]],

                /* ── ADMINISTRATIVE ────────────────────────────── */
                ['group'=>'Reports',         'section'=>'reports',             'icon'=>'fa-chart-bar',         'ic'=>'icon-blue',  'cat'=>'Administrative',
                 'items'=>[
                     ['t'=>'cb','p'=>'reports.reports.view','l'=>'View Reports'],
                 ]],
                ['group'=>'Branches',        'section'=>'branches',            'icon'=>'fa-code-branch',       'ic'=>'icon-indigo','cat'=>'Administrative',
                 'items'=>[
                     ['t'=>'cb','p'=>'branches.branches.view',  'l'=>'View Branches'],
                     ['t'=>'cb','p'=>'branches.branches.create','l'=>'Add Branch'],
                     ['t'=>'cb','p'=>'branches.branches.edit',  'l'=>'Edit Branch'],
                     ['t'=>'cb','p'=>'branches.branches.delete','l'=>'Delete Branch'],
                 ]],
                ['group'=>'Settings',        'section'=>'settings',            'icon'=>'fa-cog',               'ic'=>'icon-slate', 'cat'=>'Administrative',
                 'items'=>[
                     ['t'=>'cb','p'=>'settings.settings.view','l'=>'View Settings'],
                     ['t'=>'cb','p'=>'settings.settings.edit','l'=>'Edit Settings'],
                 ]],
                ['group'=>'Applications',    'section'=>'applications',        'icon'=>'fa-th-large',          'ic'=>'icon-purple','cat'=>'Administrative',
                 'items'=>[
                     ['t'=>'cb','p'=>'applications.chat.view',    'l'=>'Access Chat'],
                     ['t'=>'cb','p'=>'applications.calendar.view','l'=>'Access Calendar'],
                     ['t'=>'cb','p'=>'applications.messages.view','l'=>'Access Messages'],
                 ]],
            ]; // end $modules

            $countGranted = function(array $items) use ($permChecked) {
                $n = 0;
                foreach ($items as $i) { if (isset($i['p']) && $permChecked($i['p'])) $n++; }
                return $n;
            };
            $countTotal = function(array $items) {
                return count(array_filter($items, fn($i) => isset($i['p'])));
            };

            $categories = [];
            foreach ($modules as $m) { $categories[$m['cat']][] = $m; }

            // Build URL-safe slugs for each category
            $catSlugs = [];
            foreach (array_keys($categories) as $cat) {
                $catSlugs[$cat] = strtolower(preg_replace('/[^a-z0-9]+/i', '-', $cat));
            }
            $firstSlug = reset($catSlugs);
        @endphp

        <div class="card mb-4">
            <div class="card-body p-0">

                {{-- Tab bar (one tab per category) --}}
                <div class="perm-tab-bar">
                    @foreach ($categories as $catName => $catModules)
                        @php $slug = $catSlugs[$catName]; @endphp
                        <button type="button" class="perm-tab {{ $slug === $firstSlug ? 'active' : '' }}"
                                data-cat="{{ $slug }}">
                            {{ $catName }}
                            <span class="perm-tab-cnt">{{ count($catModules) }}</span>
                        </button>
                    @endforeach
                </div>

                {{-- Toolbar --}}
                <div class="perm-toolbar">
                    <div class="perm-toolbar-left">
                        <span class="perm-role-info">
                            <i class="fa fa-user-tag me-1" style="color:#d4a017;"></i>
                            {{ $role->name }}
                        </span>
                        <span class="perm-count-badge" id="grantedBadge">
                            {{ count(array_filter($assigned, fn($p) => $p)) }} granted
                        </span>
                    </div>
                    <div class="perm-toolbar-right">
                        <div class="perm-search-wrap">
                            <i class="fa fa-search perm-search-icon"></i>
                            <input type="text" class="perm-search-box" id="permSearch"
                                   placeholder="Search modules…" autocomplete="off">
                        </div>
                        <button type="button" class="btn-revoke-all" id="btnRevokeAll">
                            <i class="fa fa-times-circle"></i> Revoke All
                        </button>
                        <button type="button" class="btn-grant-all" id="btnGrantAll">
                            <i class="fa fa-check-circle"></i> Grant All
                        </button>
                    </div>
                </div>

                {{-- Tab panels --}}
                @foreach ($categories as $catName => $catModules)
                    @php $slug = $catSlugs[$catName]; @endphp
                    <div class="perm-panel {{ $slug === $firstSlug ? 'active' : '' }}"
                         data-panel="{{ $slug }}">

                        @foreach ($catModules as $mod)
                            @php
                                $granted = $countGranted($mod['items']);
                                $total   = $countTotal($mod['items']);
                                $isFull  = ($granted === $total && $total > 0);
                            @endphp

                            @if (!$loop->first)<hr class="perm-module-sep">@endif

                            <div class="perm-section {{ $isFull ? 'perm-section--granted' : '' }}"
                                 data-module-name="{{ strtolower($mod['group']) }}"
                                 data-section="{{ $mod['section'] }}"
                                 data-cat="{{ $slug }}">
                                <div class="perm-row">

                                    {{-- Left: module name + select all --}}
                                    <div class="perm-row-left">
                                        <div class="perm-mod-name">{{ $mod['group'] }}</div>
                                        <label class="perm-select-all-wrap">
                                            <input type="checkbox"
                                                   class="perm-select-all"
                                                   data-section="{{ $mod['section'] }}"
                                                   {{ $isFull ? 'checked' : '' }}>
                                            Select All
                                        </label>
                                        <div class="perm-sec-count" id="count-{{ $mod['section'] }}">
                                            {{ $granted }}/{{ $total }}
                                        </div>
                                    </div>

                                    {{-- Right: permissions list --}}
                                    <div class="perm-row-right">
                                        @foreach ($mod['items'] as $item)
                                            @if ($item['t'] === 'sp')
                                                <hr class="perm-sep">
                                            @elseif ($item['t'] === 'hd')
                                                <div class="perm-sub-title">{{ $item['l'] }}</div>
                                            @elseif ($item['t'] === 'cb')
                                                @php
                                                    $parts = explode('.', $item['p']);
                                                    $iname = "permissions[{$parts[0]}][{$parts[1]}][{$parts[2]}]";
                                                @endphp
                                                <label class="perm-item">
                                                    <input type="checkbox"
                                                           class="perm-input"
                                                           name="{{ $iname }}"
                                                           value="1"
                                                           data-section="{{ $mod['section'] }}"
                                                           {{ $permChecked($item['p']) ? 'checked' : '' }}>
                                                    <span>{{ $item['l'] }}</span>
                                                </label>
                                            @elseif ($item['t'] === 'rd')
                                                <label class="perm-item">
                                                    <input type="radio"
                                                           class="perm-input"
                                                           name="{{ $item['rn'] }}"
                                                           value="{{ $item['p'] }}"
                                                           data-section="{{ $mod['section'] }}"
                                                           {{ $permChecked($item['p']) ? 'checked' : '' }}>
                                                    <span>{{ $item['l'] }}</span>
                                                </label>
                                            @endif
                                        @endforeach
                                    </div>

                                </div>
                            </div>{{-- /.perm-section --}}
                        @endforeach

                    </div>{{-- /.perm-panel --}}
                @endforeach

            </div>
        </div>

        <div class="perm-action-bar">
            <a href="{{ route('roles.index') }}" class="btn-perm-cancel">Cancel</a>
            <button type="submit" class="btn-perm-save">
                <i class="fa fa-save"></i> Save Permissions
            </button>
        </div>

    </form>
</div>
</div>
@endsection

@push('scripts')
<script>
(function () {
    /* ── Helpers ─────────────────────────────────── */
    function inputs(s)    { return document.querySelectorAll('.perm-input[data-section="'+s+'"]'); }
    function selectAll(s) { return document.querySelector('.perm-select-all[data-section="'+s+'"]'); }

    function updateCount(s) {
        var ins = inputs(s), n = 0, tot = 0, rg = {};
        ins.forEach(function(i) {
            if (i.type === 'checkbox') { tot++; if (i.checked) n++; }
            else if (i.type === 'radio') {
                if (!rg[i.name]) { rg[i.name] = false; tot++; }
                if (i.checked) rg[i.name] = true;
            }
        });
        Object.values(rg).forEach(function(v){ if(v) n++; });
        var el = document.getElementById('count-'+s);
        if (el) el.textContent = n+'/'+tot;
        var sec = document.querySelector('.perm-section[data-section="'+s+'"]');
        if (sec) sec.classList.toggle('perm-section--granted', tot > 0 && n === tot);
    }

    function updateBadge() {
        var n = 0, rg = {};
        document.querySelectorAll('.perm-input').forEach(function(i) {
            if (i.type === 'checkbox' && i.checked) n++;
            if (i.type === 'radio') rg[i.name] = rg[i.name] || i.checked;
        });
        Object.values(rg).forEach(function(v){ if(v) n++; });
        var el = document.getElementById('grantedBadge');
        if (el) el.textContent = n+' granted';
    }

    function syncToggle(s) {
        var ins = inputs(s), sa = selectAll(s); if (!sa) return;
        var ok = true, rg = {};
        ins.forEach(function(i) {
            if (i.type === 'checkbox' && !i.checked) ok = false;
            if (i.type === 'radio') rg[i.name] = rg[i.name] || i.checked;
        });
        sa.checked = ok && (Object.keys(rg).length === 0 || Object.values(rg).every(Boolean));
    }

    /* ── Tab switching ─────────────────────────────────────────── */
    document.querySelectorAll('.perm-tab').forEach(function(tab) {
        tab.addEventListener('click', function() {
            // clear search when switching tabs
            var searchEl = document.getElementById('permSearch');
            if (searchEl && searchEl.value) {
                searchEl.value = '';
                exitSearchMode();
            }
            document.querySelectorAll('.perm-tab').forEach(function(t){ t.classList.remove('active'); });
            document.querySelectorAll('.perm-panel').forEach(function(p){ p.classList.remove('active'); });
            this.classList.add('active');
            var panel = document.querySelector('.perm-panel[data-panel="'+this.dataset.cat+'"]');
            if (panel) panel.classList.add('active');
        });
    });

    /* ── Select-All toggles ─────────────────────────────────── */
    document.querySelectorAll('.perm-select-all').forEach(function(sa) {
        sa.addEventListener('change', function() {
            var s = this.dataset.section, on = this.checked, rg = {};
            inputs(s).forEach(function(i) {
                if (i.type === 'checkbox') i.checked = on;
                else if (on && i.type === 'radio' && !rg[i.name]) { rg[i.name] = i; }
                else if (!on && i.type === 'radio') i.checked = false;
            });
            if (on) Object.values(rg).forEach(function(r){ r.checked = true; });
            updateCount(s); updateBadge();
        });
    });

    /* ── Individual inputs ─────────────────────────────────────── */
    document.querySelectorAll('.perm-input').forEach(function(i) {
        i.addEventListener('change', function() {
            syncToggle(this.dataset.section);
            updateCount(this.dataset.section);
            updateBadge();
        });
    });

    /* ── Grant All ─────────────────────────────────────────────── */
    document.getElementById('btnGrantAll').addEventListener('click', function() {
        var rg = {};
        document.querySelectorAll('.perm-input').forEach(function(i) {
            if (i.type === 'checkbox') i.checked = true;
            if (i.type === 'radio' && !rg[i.name]) rg[i.name] = i;
        });
        Object.values(rg).forEach(function(r){ r.checked = true; });
        document.querySelectorAll('.perm-select-all').forEach(function(sa){ sa.checked = true; });
        document.querySelectorAll('.perm-section').forEach(function(c){ updateCount(c.dataset.section); });
        updateBadge();
    });

    /* ── Revoke All ────────────────────────────────────────────── */
    document.getElementById('btnRevokeAll').addEventListener('click', function() {
        document.querySelectorAll('.perm-input').forEach(function(i){ i.checked = false; });
        document.querySelectorAll('.perm-select-all').forEach(function(sa){ sa.checked = false; });
        document.querySelectorAll('.perm-section').forEach(function(c){ updateCount(c.dataset.section); });
        updateBadge();
    });

    /* ── Search ──────────────────────────────────────────────── */
    function exitSearchMode() {
        document.getElementById('permForm').classList.remove('perm-search-active');
        var activeTab = document.querySelector('.perm-tab.active');
        if (activeTab) {
            document.querySelectorAll('.perm-panel').forEach(function(p){ p.classList.remove('active'); });
            var panel = document.querySelector('.perm-panel[data-panel="'+activeTab.dataset.cat+'"]');
            if (panel) panel.classList.add('active');
        }
        document.querySelectorAll('.perm-section').forEach(function(s){ s.classList.remove('perm-sec--hidden'); });
        document.querySelectorAll('.perm-module-sep').forEach(function(hr){ hr.style.display = ''; });
        var tabBar = document.querySelector('.perm-tab-bar');
        if (tabBar) tabBar.style.display = '';
    }

    document.getElementById('permSearch').addEventListener('input', function() {
        var q = this.value.trim().toLowerCase();
        var form = document.getElementById('permForm');
        var tabBar = document.querySelector('.perm-tab-bar');
        if (!q) { exitSearchMode(); return; }
        form.classList.add('perm-search-active');
        document.querySelectorAll('.perm-panel').forEach(function(p){ p.style.display = 'block'; });
        if (tabBar) tabBar.style.display = 'none';
        document.querySelectorAll('.perm-section').forEach(function(sec) {
            var match = sec.dataset.moduleName && sec.dataset.moduleName.includes(q);
            sec.classList.toggle('perm-sec--hidden', !match);
        });
        document.querySelectorAll('.perm-panel').forEach(function(panel) {
            var any = Array.from(panel.querySelectorAll('.perm-section')).some(function(s){
                return !s.classList.contains('perm-sec--hidden');
            });
            panel.style.display = any ? 'block' : 'none';
        });
    });

    /* ── Init ──────────────────────────────────────────────────── */
    document.querySelectorAll('.perm-section').forEach(function(c){ updateCount(c.dataset.section); });
    updateBadge();
}());
</script>
@endpush
