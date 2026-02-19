<?php $page = 'user_view'; ?>
@extends('layout.mainlayout')

@section('content')
@php
    $isSuperAdminRoute = request()->routeIs('super_admin.*');
    $indexRouteName = $isSuperAdminRoute && app('router')->has('super_admin.users.index') ? 'super_admin.users.index' : 'users.index';
    $editRouteName = $isSuperAdminRoute && app('router')->has('super_admin.users.edit') ? 'super_admin.users.edit' : 'users.edit';
@endphp

<div class="page-wrapper">
    <div class="content container-fluid">
        <div class="page-header">
            <div class="content-page-header">
                <h5>User Details</h5>
                <div class="list-btn">
                    <a href="{{ route($indexRouteName) }}" class="btn btn-light me-2">Back</a>
                    <a href="{{ route($editRouteName, $user->id) }}" class="btn btn-primary">Edit User</a>
                </div>
            </div>
        </div>

        <div class="card">
            <div class="card-body">
                <div class="d-flex align-items-center">
                    <span class="avatar avatar-lg me-3">
                        <img class="avatar-img rounded-circle" src="{{ $user->profile_photo ? asset('storage/' . $user->profile_photo) : asset('assets/img/profiles/avatar-01.jpg') }}">
                    </span>
                    <div>
                        <h4 class="mb-1">{{ $user->name }}</h4>
                        <p class="text-muted mb-1">{{ $user->email }}</p>
                        <span class="badge bg-light text-dark">{{ ucwords(str_replace('_', ' ', $user->role ?? 'user')) }}</span>
                    </div>
                </div>

                <hr>
                <div class="row">
                    <div class="col-md-4 mb-3">
                        <small class="text-muted d-block">Status</small>
                        <strong>{{ ucfirst($user->status ?? 'active') }}</strong>
                    </div>
                    <div class="col-md-4 mb-3">
                        <small class="text-muted d-block">Verified</small>
                        <strong>{{ $user->is_verified ? 'Yes' : 'No' }}</strong>
                    </div>
                    <div class="col-md-4 mb-3">
                        <small class="text-muted d-block">Created</small>
                        <strong>{{ optional($user->created_at)->format('d M Y, h:i A') }}</strong>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

