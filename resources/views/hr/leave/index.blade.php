@extends('layout.app')

@section('title', 'Leave Management')

@section('content')
<div class="page-wrapper">
<div class="content container-fluid">
    <div class="page-header">
        <div class="row align-items-center">
            <div class="col">
                <h3 class="page-title">Leave Management</h3>
                <p class="text-muted mb-0">Use the dedicated leave routes for requests and leave type setup.</p>
            </div>
            <div class="col-auto">
                <a href="{{ route('hr.leave.requests') }}" class="btn btn-primary btn-sm">Open Leave Requests</a>
            </div>
        </div>
    </div>
</div>
</div>
@endsection
