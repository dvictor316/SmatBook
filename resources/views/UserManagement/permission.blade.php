<?php $page = 'permission'; ?>
@extends('layout.mainlayout')

@section('content')
<div class="page-wrapper">
    <div class="content container-fluid">
        <div class="page-header">
            <div class="content-page-header d-flex justify-content-between align-items-center flex-wrap gap-2">
                <div>
                    <h5 class="mb-1">Permission Settings</h5>
                    <p class="text-muted mb-0">Configure what this role can view, create, edit, or delete.</p>
                </div>
                <div class="role-testing">
                    <h6 class="mb-0">Role Name: <span class="badge badge-primary ms-1">{{ $role->name }}</span></h6>
                </div>
            </div>
        </div>

        <form action="{{ route('roles.permissions.update') }}" method="POST">
            @csrf
            <input type="hidden" name="role_id" value="{{ $role->id }}">

            <div class="card-table">
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-striped table-hover">
                            <thead class="thead-light">
                                <tr>
                                    <th class="ps-3">#</th>
                                    <th>Modules</th>
                                    <th>Sub Modules</th>
                                    <th class="text-center">View</th>
                                    <th class="text-center">Create</th>
                                    <th class="text-center">Edit</th>
                                    <th class="text-center">Delete</th>
                                </tr>
                            </thead>
                            <tbody>
                                @php $rowNumber = 1; @endphp
                                @forelse($groupedPermissions as $module => $subModules)
                                    @foreach($subModules as $subModule => $actions)
                                        <tr>
                                            <td class="ps-3">{{ $rowNumber++ }}</td>
                                            <td class="role-data fw-semibold">{{ $module }}</td>
                                            <td>{{ $subModule }}</td>

                                            @foreach(['view', 'create', 'edit', 'delete'] as $action)
                                                @php
                                                    $permissionKey = \Illuminate\Support\Str::snake($module) . '.' . \Illuminate\Support\Str::snake($subModule) . '.' . $action;
                                                    $isChecked = in_array($permissionKey, $assignedPermissions ?? [], true);
                                                    $isAvailable = in_array($action, $actions, true);
                                                @endphp
                                                <td class="text-center">
                                                    @if($isAvailable)
                                                        <label class="custom_check">
                                                            <input type="checkbox"
                                                                   name="permissions[{{ \Illuminate\Support\Str::snake($module) }}][{{ \Illuminate\Support\Str::snake($subModule) }}][{{ $action }}]"
                                                                   value="1"
                                                                   {{ $isChecked ? 'checked' : '' }}>
                                                            <span class="checkmark"></span>
                                                        </label>
                                                    @else
                                                        <span class="text-muted">-</span>
                                                    @endif
                                                </td>
                                            @endforeach
                                        </tr>
                                    @endforeach
                                @empty
                                    <tr>
                                        <td colspan="7" class="text-center py-4 text-muted">No permission catalog available.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <div class="btn-center my-4 text-center">
                <a href="{{ route('roles.index') }}" class="btn btn-secondary me-2">Back to Roles</a>
                <button type="submit" class="btn btn-primary">Save Permissions</button>
            </div>
        </form>
    </div>
</div>
@endsection
