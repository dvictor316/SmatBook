<?php $page = 'profile'; ?>
@extends('layout.mainlayout')
@section('content')

    @php
        $user = auth()->user();
        $profilePhoto = $user?->profile_photo_url ?? asset('assets/img/profiles/avatar-02.jpg');
        $coverPhoto = $user?->cover_photo_url ?? asset('assets/img/profiles/avatar-02.jpg');

        // 2. Calculate Profile Completeness based on actual Database Schema
        $profileFields = ['name', 'email', 'profile_photo', 'cover_photo', 'role']; 
        $filledFields = 0;
        foreach ($profileFields as $field) {
            if (!empty($user->$field)) $filledFields++;
        }
        $completeness = ($filledFields / count($profileFields)) * 100;
    @endphp

    <div class="page-wrapper">
        <div class="content container-fluid">
            <div class="row justify-content-lg-center">
                <div class="col-lg-10">

                    <div class="page-header">
                        <div class="content-page-header">
                            <h5>My Profile</h5>
                        </div>
                    </div>

                    <form action="{{ route('profile.update.images') }}" method="POST" enctype="multipart/form-data" id="imageUploadForm">
                        @csrf
                        <div class="profile-cover">
                            <div class="profile-cover-wrap">
                                <img class="profile-cover-img" id="cover-preview" src="{{ $coverPhoto }}" alt="Cover Image" onerror="this.src='{{ asset('assets/img/profiles/avatar-02.jpg') }}'">

                                <div class="cover-content">
                                    <div class="custom-file-btn">

                                        <input type="file" name="cover_photo" class="custom-file-btn-input" id="cover_upload" accept="image/*" onchange="this.form.submit()">
                                        <label class="custom-file-btn-label btn btn-sm btn-white" for="cover_upload">
                                            <i class="fas fa-camera"></i>
                                            <span class="d-none d-sm-inline-block ms-1">Update Cover</span>
                                        </label>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="text-center mb-5">
                            <label class="avatar avatar-xxl profile-cover-avatar" for="avatar_upload">
                                <img class="avatar-img" id="avatar-preview" src="{{ $profilePhoto }}" alt="Profile Picture" onerror="this.src='{{ asset('assets/img/profiles/avatar-02.jpg') }}'">

                                <input type="file" name="profile_photo" id="avatar_upload" accept="image/*" hidden onchange="this.form.submit()">
                                <span class="avatar-edit">
                                    <i class="fe fe-edit avatar-uploader-icon shadow-soft"></i>
                                </span>
                            </label>
                            <h2 class="mt-2">{{ $user->name }} <i class="fas fa-certificate text-primary small" title="Verified Account"></i></h2>
                            <ul class="list-inline">
                                <li class="list-inline-item text-muted">
                                    <i class="far fa-calendar-alt me-1"></i> <span>Joined {{ $user->created_at->format('F Y') }}</span>
                                </li>
                                <li class="list-inline-item text-muted ms-2">
                                    <i class="fas fa-user-tag me-1"></i> <span>{{ ucfirst($user->role) }}</span>
                                </li>
                            </ul>
                        </div>
                    </form>

                    <div class="row">

                        <div class="col-lg-4">
                            <div class="card card-body mb-4">
                                <h5 class="card-title">Profile Completeness</h5>
                                <div class="d-flex justify-content-between align-items-center">
                                    <div class="progress progress-md flex-grow-1" style="height: 8px;">
                                        <div class="progress-bar bg-primary" role="progressbar" style="width: {{ $completeness }}%" aria-valuenow="{{ $completeness }}" aria-valuemin="0" aria-valuemax="100"></div>
                                    </div>
                                    <span class="ms-3 fw-bold">{{ round($completeness) }}%</span>
                                </div>
                            </div>

                            <div class="card">
                                <div class="card-header d-flex justify-content-between align-items-center">
                                    <h5 class="card-title mb-0">Basic Info</h5>
                                    <a class="btn btn-sm btn-white" href="{{ url('settings') }}">Edit</a>
                                </div>
                                <div class="card-body">
                                    <ul class="list-unstyled mb-0">
                                        <li class="mb-3">
                                            <span class="text-muted d-block small fw-bold text-uppercase">Full Name</span>
                                            <span class="h6">{{ $user->name }}</span>
                                        </li>
                                        <li class="mb-3">
                                            <span class="text-muted d-block small fw-bold text-uppercase">Email Address</span>
                                            <span class="h6">{{ $user->email }}</span>
                                        </li>
                                        <li>
                                            <span class="text-muted d-block small fw-bold text-uppercase">Account Role</span>
                                            <span class="h6 text-primary">{{ ucfirst($user->role) }}</span>
                                        </li>
                                    </ul>
                                </div>
                            </div>
                        </div>

                        <div class="col-lg-8">
                            <div class="card">
                                <div class="card-header">
                                    <h5 class="card-title">Recent Activity</h5>
                                </div>
                                <div class="card-body card-body-height">
                                    <ul class="activity-feed">
                                        <li class="feed-item">
                                            <div class="feed-date">{{ now()->format('M d, Y') }}</div>
                                            <span class="feed-text">You accessed your account from <strong>{{ request()->ip() }}</strong>.</span>
                                        </li>
                                        <li class="feed-item">
                                            <div class="feed-date">{{ $user->created_at->format('M d, Y') }}</div>
                                            <span class="feed-text">Welcome to SmartProbook! Your account was successfully created.</span>
                                        </li>
                                    </ul>
                                </div>
                            </div>
                        </div>
                    </div>

                </div>
            </div>
        </div>
    </div>
@endsection
