<?php $page = 'roles-permission'; ?>
@extends('layout.mainlayout')

@section('content')
<div class="page-wrapper">
    <div class="content container-fluid">
        <div class="page-header">
            <div class="row align-items-center">
                <div class="col">
                    <h3 class="page-title">Roles & Permission</h3>
                    <ul class="breadcrumb">
                        <li class="breadcrumb-item"><a href="{{ url('dashboard') }}">Dashboard</a></li>
                        <li class="breadcrumb-item active">Roles & Permission</li>
                    </ul>
                </div>
                <div class="col-auto">
                    <a href="javascript:void(0);" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#add_role">
                        <i class="fas fa-plus"></i> Add Role
                    </a>
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-sm-12">
                <div class="card-table">
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-center table-hover datatable">
                                <thead class="thead-light">
                                    <tr>
                                        <th>ID</th>
                                        <th>Role Name</th>
                                        <th>Created at</th>
                                        <th class="no-sort text-end">Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse ($roles ?? [] as $role)
                                        <tr>
                                            <td>{{ $role->id }}</td>
                                            <td>
                                                <div class="fw-semibold">{{ $role->name }}</div>
                                                <small class="text-muted">{{ $role->role_group ?? 'Staff' }}</small>
                                            </td>
                                            <td>
                                                <div>{{ $role->created_at ? $role->created_at->format('d M Y') : 'N/A' }}</div>
                                                <small class="text-muted">{{ number_format($role->permissions_count ?? 0) }} permission(s)</small>
                                            </td>
                                            <td class="d-flex align-items-center justify-content-end">
                                                <a href="javascript:void(0);" class="btn btn-greys me-2"
                                                   data-bs-toggle="modal" data-bs-target="#edit_role{{ $role->id }}">
                                                    <i class="fa fa-edit me-1"></i> Edit
                                                </a>

                                                <a href="{{ route('roles.permissions', $role->id) }}" class="btn btn-greys me-2">
                                                    <i class="fa fa-shield me-1"></i> Permissions
                                                </a>

                                                <form action="{{ route('roles.destroy', $role->id) }}" method="POST" onsubmit="return confirm('Delete this role? This cannot be undone.')">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="btn btn-danger btn-sm" {{ ($role->name == 'Administrator' || !empty($role->is_system_role)) ? 'disabled' : '' }}>
                                                        <i class="fa fa-trash"></i>
                                                    </button>
                                                </form>
                                            </td>
                                        </tr>

                                        <div class="modal fade" id="edit_role{{ $role->id }}" tabindex="-1" aria-hidden="true">
                                            <div class="modal-dialog modal-dialog-centered">
                                                <div class="modal-content">
                                                    <form action="{{ route('roles.update', $role->id) }}" method="POST">
                                                        @csrf
                                                        @method('PUT')
                                                        <div class="modal-header">
                                                            <h5 class="modal-title">Edit Role: {{ $role->name }}</h5>
                                                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                                        </div>
                                                        <div class="modal-body">
                                                            <div class="form-group">
                                                                <label>Role Name <span class="text-danger">*</span></label>
                                                                <input type="text" name="name" class="form-control" value="{{ $role->name }}" required>
                                                            </div>
                                                            <div class="form-group mt-3">
                                                                <label>Role Group</label>
                                                                <input type="text" name="role_group" class="form-control" value="{{ $role->role_group }}" placeholder="e.g. Sales, Finance, Operations">
                                                            </div>
                                                            <div class="form-group mt-3">
                                                                <label>Description</label>
                                                                <textarea name="description" class="form-control" rows="3" placeholder="What this role is responsible for">{{ $role->description }}</textarea>
                                                            </div>
                                                        </div>
                                                        <div class="modal-footer">
                                                            <button type="submit" class="btn btn-primary">Update</button>
                                                        </div>
                                                    </form>
                                                </div>
                                            </div>
                                        </div>
                                    @empty
                                        <tr>
                                            <td colspan="4" class="text-center">No roles found.</td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="add_role" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <form action="{{ route('roles.store') }}" method="POST">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title">Add Role</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="form-group">
                        <label>Role Name <span class="text-danger">*</span></label>
                        <input type="text" name="name" class="form-control" placeholder="Enter role name" required>
                    </div>
                    <div class="form-group mt-3">
                        <label>Role Group</label>
                        <input type="text" name="role_group" class="form-control" placeholder="e.g. Sales, Finance, Operations">
                    </div>
                    <div class="form-group mt-3">
                        <label>Description</label>
                        <textarea name="description" class="form-control" rows="3" placeholder="What this role is responsible for"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="submit" class="btn btn-primary w-100">Save Role</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
