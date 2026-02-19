<?php $page = 'users'; ?>
@extends('layout.mainlayout')

@section('content')
@php
    $isSuperAdminRoute = request()->routeIs('super_admin.*');
    $storeRouteName = $isSuperAdminRoute && app('router')->has('super_admin.users.store') ? 'super_admin.users.store' : 'users.store';
    $showRouteName = $isSuperAdminRoute && app('router')->has('super_admin.users.show') ? 'super_admin.users.show' : 'users.show';
    $editRouteName = $isSuperAdminRoute && app('router')->has('super_admin.users.edit') ? 'super_admin.users.edit' : 'users.edit';
    $deleteRouteName = $isSuperAdminRoute && app('router')->has('super_admin.users.destroy') ? 'super_admin.users.destroy' : 'users.destroy';
@endphp
<div class="page-wrapper">
    <div class="content container-fluid">
        <div class="page-header">
            <div class="content-page-header">
                <h5>User Management</h5>
                <div class="list-btn">
                    {{-- This button triggers the modal --}}
                    <a class="btn btn-primary" href="javascript:void(0);" data-bs-toggle="modal" data-bs-target="#add_user">
                        <i class="fa fa-plus-circle me-2"></i>Add User
                    </a>
                </div>
            </div>
        </div>

        @if(session('success'))
            <div class="alert alert-success">{{ session('success') }}</div>
        @endif

        <div class="row">
            <div class="col-sm-12">
                <div class="card-table">
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-center table-hover datatable">
                                <thead class="thead-light">
                                    <tr>
                                        <th>ID</th>
                                        <th>User Name</th>
                                        <th>Rank / Role</th>
                                        <th>Created</th>
                                        <th class="text-end">Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse ($users as $user)
                                    <tr>
                                        <td>{{ $user->id }}</td>
                                        <td>
                                            <h2 class="table-avatar">
                                                <span class="avatar avatar-sm me-2">
                                                    <img class="avatar-img rounded-circle" src="{{ $user->profile_photo ? asset('storage/' . $user->profile_photo) : asset('assets/img/profiles/avatar-01.jpg') }}">
                                                </span>
                                                {{ $user->name }}
                                            </h2>
                                        </td>
                                        <td>
                                            @php
                                                $color = match($user->role) {
                                                    'super_admin' => 'bg-danger',
                                                    'administrator' => 'bg-primary',
                                                    'store_manager' => 'bg-success',
                                                    'accountant' => 'bg-info',
                                                    'deployment_manager' => 'bg-warning text-dark',
                                                    'cashier' => 'bg-secondary',
                                                    default => 'bg-light text-dark',
                                                };
                                            @endphp
                                            <span class="badge {{ $color }}">
                                                {{ ucwords(str_replace('_', ' ', $user->role)) }}
                                            </span>
                                        </td>
                                        <td>{{ optional($user->created_at)->format('d M Y') }}</td>
                                        <td class="text-end">
                                            <div class="dropdown dropdown-action">
                                                <a href="#" class="btn-action-icon" data-bs-toggle="dropdown"><i class="fas fa-ellipsis-v"></i></a>
                                                <div class="dropdown-menu dropdown-menu-right">
                                                    <a class="dropdown-item" href="{{ route($showRouteName, $user->id) }}"><i class="far fa-eye me-2 text-primary"></i>View</a>
                                                    <a class="dropdown-item" href="{{ route($editRouteName, $user->id) }}"><i class="far fa-edit me-2 text-success"></i>Edit</a>
                                                    <a class="dropdown-item" href="javascript:void(0);" data-bs-toggle="modal" data-bs-target="#delete_user_{{ $user->id }}"><i class="far fa-trash-alt me-2 text-danger"></i>Delete</a>
                                                </div>
                                            </div>
                                        </td>
                                    </tr>
                                    @empty
                                        <tr><td colspan="5" class="text-center py-4 text-muted">No users found.</td></tr>
                                    @endforelse
                                </tbody>
                            </table>
                            @if(method_exists($users, 'links'))
                                <div class="mt-3">{{ $users->links() }}</div>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- MODAL: ADD USER (Re-added based on your DB fields) --}}
<div class="modal fade" id="add_user" tabindex="-1" aria-labelledby="addUserLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="addUserLabel">Add New User</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="{{ route($storeRouteName) }}" method="POST" enctype="multipart/form-data">
                @csrf
                <div class="modal-body">
                    <div class="row">
                        <div class="col-lg-12">
                            <div class="mb-3">
                                <label class="form-label">Full Name</label>
                                <input type="text" name="name" class="form-control" placeholder="Enter Name" required>
                            </div>
                        </div>
                        <div class="col-lg-12">
                            <div class="mb-3">
                                <label class="form-label">Email Address</label>
                                <input type="email" name="email" class="form-control" placeholder="Enter Email" required>
                            </div>
                        </div>
                        <div class="col-lg-12">
                            <div class="mb-3">
                                <label class="form-label">Role</label>
                                <select name="role" class="form-select" required>
                                    @foreach(($roles ?? []) as $role)
                                        <option value="{{ $role }}">{{ ucwords(str_replace('_', ' ', $role)) }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                        <div class="col-lg-12">
                            <div class="mb-3">
                                <label class="form-label">Password</label>
                                <input type="password" name="password" class="form-control" placeholder="Set Password" required>
                            </div>
                        </div>
                        <div class="col-lg-12">
                            <div class="mb-0">
                                <label class="form-label">Profile Photo</label>
                                <input type="file" name="profile_photo" class="form-control">
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Save User</button>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- MODALS: DELETE USER --}}
@foreach ($users as $user)
<div class="modal fade" id="delete_user_{{ $user->id }}" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <form action="{{ route($deleteRouteName, $user->id) }}" method="POST">
                @csrf 
                @method('DELETE')
                <div class="modal-body text-center">
                    <i class="fas fa-exclamation-triangle fa-3x text-warning mb-3"></i>
                    <h3>Delete User?</h3>
                    <p>Are you sure you want to delete <strong>{{ $user->name }}</strong>?</p>
                </div>
                <div class="modal-footer justify-content-center">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-danger">Confirm Delete</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endforeach

@endsection
