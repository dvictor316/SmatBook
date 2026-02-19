<?php $page = 'edit_user'; ?>
@extends('layout.mainlayout')

@section('content')
@php
    $isSuperAdminRoute = request()->routeIs('super_admin.*');
    $updateRouteName = $isSuperAdminRoute && app('router')->has('super_admin.users.update') ? 'super_admin.users.update' : 'users.update';
    $indexRouteName = $isSuperAdminRoute && app('router')->has('super_admin.users.index') ? 'super_admin.users.index' : 'users.index';
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
                        {{-- Form points back to your update method --}}
                        <form action="{{ route($updateRouteName, $user->id) }}" method="POST" enctype="multipart/form-data">
                            @csrf
                            @method('PUT')
                            {{-- We use hidden input or route param depending on your controller --}}
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
                                <select name="role" class="form-select" required>
                                    @foreach($roles as $role)
                                        <option value="{{ $role }}" {{ $user->role == $role ? 'selected' : '' }}>
                                            {{ ucwords(str_replace('_', ' ', $role)) }}
                                        </option>
                                    @endforeach
                                </select>
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
@endsection
