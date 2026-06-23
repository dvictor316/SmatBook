<?php $page = 'edit_user'; ?>
@extends('layout.mainlayout')

@section('content')
@php
    $isSuperAdminRoute = request()->routeIs('super_admin.*');
    $updateRouteName = $isSuperAdminRoute && app('router')->has('super_admin.users.update') ? 'super_admin.users.update' : 'users.update';
    $indexRouteName = $isSuperAdminRoute && app('router')->has('super_admin.users.index') ? 'super_admin.users.index' : 'users.index';
    $regions = $regionOptions ?? [];
    $selectedCountry = old('country', $user->country ?? $managerProfile->country ?? '');
    $selectedState = old('state_region', $user->state_region ?? $managerProfile->state_region ?? '');
    $selectedCouncil = old('local_council', $user->local_council ?? $managerProfile->local_council ?? '');
@endphp
<div class="page-wrapper">
    <div class="content container-fluid">
        <div class="page-header">
            <div class="content-page-header">
                <h5>Edit User: {{ $user->name }}</h5>
            </div>
        </div>

        <div class="row">
            <div class="col-md-8 offset-md-2">
                <div class="card">
                    <div class="card-body">

                        <form action="{{ route($updateRouteName, $user->id) }}" method="POST" enctype="multipart/form-data">
                            @csrf
                            @method('PUT')

                            <input type="hidden" name="user_id" value="{{ $user->id }}">

                            <div class="mb-3">
                                <label class="form-label">Full Name</label>
                                <input type="text" name="name" class="form-control" value="{{ old('name', $user->name) }}" required>
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Email Address</label>
                                <input type="email" name="email" class="form-control" value="{{ old('email', $user->email) }}" required>
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Role</label>
                                <select name="role" id="roleSelect" class="form-select" required>
                                    @foreach($roles as $role)
                                        <option value="{{ $role }}" {{ $user->role == $role ? 'selected' : '' }}>
                                            {{ ucwords(str_replace('_', ' ', $role)) }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="mb-3" id="stateAssignmentBlock" style="display:none;">
                                <div class="p-3 rounded-3" style="background:#f8fbff;border:1px solid #dbeafe;">
                                    <div class="fw-bold text-primary mb-1">State Assignment & Targets</div>
                                    <div class="text-muted mb-3" style="font-size:.85rem;">Only super admin can create or move state managers. One active manager is allowed per country and state/county.</div>

                                    <div class="row g-3">
                                        <div class="col-md-4">
                                            <label class="form-label">Country</label>
                                            <select name="country" id="countrySelect" class="form-select">
                                                <option value="">Select country</option>
                                            </select>
                                        </div>
                                        <div class="col-md-4">
                                            <label class="form-label">State / County / Region</label>
                                            <input type="text" name="state_region" id="stateRegionSelect" list="stateRegionOptions" class="form-control" value="{{ $selectedState }}" placeholder="Type or select state/county">
                                            <datalist id="stateRegionOptions"></datalist>
                                        </div>
                                        <div class="col-md-4">
                                            <label class="form-label">Local Government / Council</label>
                                            <input type="text" name="local_council" id="localCouncilSelect" list="localCouncilOptions" class="form-control" value="{{ $selectedCouncil }}" placeholder="Type or select local council">
                                            <datalist id="localCouncilOptions"></datalist>
                                        </div>
                                        <div class="col-md-6 state-manager-target">
                                            <label class="form-label">Revenue Target</label>
                                            <input type="number" min="0" step="0.01" name="state_revenue_target" class="form-control" value="{{ old('state_revenue_target', $managerProfile->state_revenue_target ?? '') }}">
                                        </div>
                                        <div class="col-md-6 state-manager-target">
                                            <label class="form-label">Customer Target</label>
                                            <input type="number" min="0" step="1" name="state_customer_target" class="form-control" value="{{ old('state_customer_target', $managerProfile->state_customer_target ?? '') }}">
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Password <small class="text-muted">(Leave blank to keep current)</small></label>
                                <input type="password" name="password" class="form-control">
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Profile Photo</label>
                                <input type="file" name="profile_photo" class="form-control">
                                @if($user->profile_photo)
                                    <div class="mt-2">
                                        <img src="{{ asset('storage/' . $user->profile_photo) }}" width="50" class="rounded-circle">
                                    </div>
                                @endif
                            </div>

                            <div class="text-end mt-4">
                                <a href="{{ route($indexRouteName) }}" class="btn btn-secondary me-2">Cancel</a>
                                <button type="submit" class="btn btn-primary">Update User</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
(function () {
    'use strict';

    var regions = @json($regions);
    var oldCountry = @json($selectedCountry);
    var oldState = @json($selectedState);
    var oldCouncil = @json($selectedCouncil);
    var roleSelect = document.getElementById('roleSelect');
    var block = document.getElementById('stateAssignmentBlock');
    var countrySelect = document.getElementById('countrySelect');
    var stateSelect = document.getElementById('stateRegionSelect');
    var councilSelect = document.getElementById('localCouncilSelect');
    var stateOptions = document.getElementById('stateRegionOptions');
    var councilOptions = document.getElementById('localCouncilOptions');

    function normalizeRole(role) {
        return (role || '').toLowerCase().replace(/[\s-]+/g, '_');
    }

    function fillSelect(select, values, placeholder, selected) {
        if (!select) return;
        if (select.tagName === 'DATALIST') {
            select.innerHTML = values.map(function (value) {
                return '<option value="' + value + '"></option>';
            }).join('');
            return;
        }
        select.innerHTML = '<option value="">' + placeholder + '</option>';
        values.forEach(function (value) {
            var option = document.createElement('option');
            option.value = value;
            option.textContent = value;
            if (selected && selected === value) option.selected = true;
            select.appendChild(option);
        });
    }

    function syncStates() {
        var country = countrySelect ? countrySelect.value : '';
        fillSelect(stateOptions, country && regions[country] ? Object.keys(regions[country]) : [], 'Select state/county', oldState);
        if (oldState && stateSelect && !stateSelect.value) stateSelect.value = oldState;
        syncCouncils();
    }

    function syncCouncils() {
        var country = countrySelect ? countrySelect.value : '';
        var state = stateSelect ? stateSelect.value : '';
        fillSelect(councilOptions, country && state && regions[country] && regions[country][state] ? regions[country][state] : [], 'Select local council', oldCouncil);
        if (oldCouncil && councilSelect && !councilSelect.value) councilSelect.value = oldCouncil;
    }

    function syncBlock() {
        var role = normalizeRole(roleSelect ? roleSelect.value : '');
        var show = ['state_manager', 'deployment_manager', 'manager', 'agent'].includes(role);
        var isManager = ['state_manager', 'deployment_manager', 'manager'].includes(role);
        if (block) block.style.display = show ? '' : 'none';
        document.querySelectorAll('.state-manager-target').forEach(function (el) {
            el.style.display = isManager ? '' : 'none';
        });
        [countrySelect, stateSelect].forEach(function (select) {
            if (select) select.required = isManager;
        });
    }

    fillSelect(countrySelect, Object.keys(regions), 'Select country', oldCountry);
    syncStates();
    syncBlock();

    if (countrySelect) countrySelect.addEventListener('change', function () {
        oldState = '';
        oldCouncil = '';
        syncStates();
    });
    if (stateSelect) stateSelect.addEventListener('change', function () {
        oldCouncil = '';
        syncCouncils();
    });
    if (stateSelect) stateSelect.addEventListener('input', function () {
        oldCouncil = '';
        syncCouncils();
    });
    if (roleSelect) roleSelect.addEventListener('change', syncBlock);
})();
</script>
@endpush
@endsection
