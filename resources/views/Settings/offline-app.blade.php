<?php $page = 'offline-app'; ?>
@extends('layout.mainlayout')
@section('content')
<div class="page-wrapper">
    <div class="content container-fluid">
        <div class="page-header">
            <div class="content-page-header">
                <h5>Offline App</h5>
                <p class="text-muted mb-0">Install SmartProBook for Windows and sync your product catalogue while online before making offline sales.</p>
            </div>
        </div>

        @include('SuperAdmin.partials._desktop_app_download', ['company' => auth()->user()?->company])
    </div>
</div>
@endsection
