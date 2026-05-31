@extends('layout.mainlayout')

@section('content')
@php $page = 'demo_requests'; @endphp

<div class="page-wrapper">
    <div class="content container-fluid">

        <div class="page-header d-print-none">
            <div class="row align-items-center">
                <div class="col">
                    <h3 class="page-title">Demo Requests</h3>
                    <ul class="breadcrumb">
                        <li class="breadcrumb-item"><a href="{{ route('super_admin.dashboard') }}">Dashboard</a></li>
                        <li class="breadcrumb-item active">Demo Requests</li>
                    </ul>
                </div>
            </div>
        </div>

        {{-- Flash messages --}}
        @if (session('success'))
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                {{ session('success') }}
                <button type="button" class="close" data-dismiss="alert"><span>&times;</span></button>
            </div>
        @endif
        @if (session('error'))
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                {{ session('error') }}
                <button type="button" class="close" data-dismiss="alert"><span>&times;</span></button>
            </div>
        @endif

        {{-- Summary cards --}}
        <div class="row">
            <div class="col-xl-3 col-sm-6 col-12">
                <div class="card bg-comman w-100">
                    <div class="card-body">
                        <div class="db-widgets d-flex justify-content-between align-items-center">
                            <div class="db-info">
                                <h6>Pending</h6>
                                <h3 class="text-warning">{{ $counts['pending'] }}</h3>
                            </div>
                            <div class="db-icon bg-warning-light">
                                <i class="fas fa-hourglass-half text-warning"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-xl-3 col-sm-6 col-12">
                <div class="card bg-comman w-100">
                    <div class="card-body">
                        <div class="db-widgets d-flex justify-content-between align-items-center">
                            <div class="db-info">
                                <h6>Approved</h6>
                                <h3 class="text-success">{{ $counts['approved'] }}</h3>
                            </div>
                            <div class="db-icon bg-success-light">
                                <i class="fas fa-check-circle text-success"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-xl-3 col-sm-6 col-12">
                <div class="card bg-comman w-100">
                    <div class="card-body">
                        <div class="db-widgets d-flex justify-content-between align-items-center">
                            <div class="db-info">
                                <h6>Rejected</h6>
                                <h3 class="text-danger">{{ $counts['rejected'] }}</h3>
                            </div>
                            <div class="db-icon bg-danger-light">
                                <i class="fas fa-times-circle text-danger"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-xl-3 col-sm-6 col-12">
                <div class="card bg-comman w-100">
                    <div class="card-body">
                        <div class="db-widgets d-flex justify-content-between align-items-center">
                            <div class="db-info">
                                <h6>Expired</h6>
                                <h3 class="text-secondary">{{ $counts['expired'] }}</h3>
                            </div>
                            <div class="db-icon bg-secondary-light">
                                <i class="fas fa-calendar-times text-secondary"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Filter tabs --}}
        <div class="card mb-0">
            <div class="card-header pt-3 pb-0">
                <ul class="nav nav-tabs nav-tabs-bottom">
                    @foreach(['', 'pending', 'approved', 'rejected', 'expired'] as $tab)
                        @php
                            $label = $tab === '' ? 'All' : ucfirst($tab);
                            $active = request('status', '') === $tab;
                            $url = $tab === '' ? route('super_admin.demo_requests.index') : route('super_admin.demo_requests.index', ['status' => $tab]);
                        @endphp
                        <li class="nav-item">
                            <a class="nav-link {{ $active ? 'active' : '' }}" href="{{ $url }}">
                                {{ $label }}
                                @if($tab !== '' && $counts[$tab] > 0)
                                    <span class="badge badge-pill
                                        {{ $tab === 'pending' ? 'badge-warning' : ($tab === 'approved' ? 'badge-success' : ($tab === 'rejected' ? 'badge-danger' : 'badge-secondary')) }}
                                        ml-1">{{ $counts[$tab] }}</span>
                                @endif
                            </a>
                        </li>
                    @endforeach
                </ul>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover table-center mb-0">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Full Name</th>
                                <th>Company</th>
                                <th>Email</th>
                                <th>Phone</th>
                                <th>Country</th>
                                <th>Status</th>
                                <th>Submitted</th>
                                <th>Expires</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($demoRequests as $req)
                                <tr>
                                    <td>{{ $loop->iteration }}</td>
                                    <td>{{ $req->full_name }}</td>
                                    <td>{{ $req->company_name }}</td>
                                    <td>{{ $req->email }}</td>
                                    <td>{{ $req->phone }}</td>
                                    <td>{{ $req->country }}</td>
                                    <td>
                                        <span class="badge {{ $req->statusBadgeClass() }}">{{ ucfirst($req->status) }}</span>
                                    </td>
                                    <td>{{ $req->created_at->format('d M Y') }}</td>
                                    <td>
                                        @if($req->expires_at)
                                            {{ $req->expires_at->format('d M Y H:i') }}
                                        @else
                                            <span class="text-muted">—</span>
                                        @endif
                                    </td>
                                    <td>
                                        <a href="{{ route('super_admin.demo_requests.show', $req->id) }}" class="btn btn-sm btn-info">
                                            <i class="fas fa-eye"></i> View
                                        </a>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="10" class="text-center text-muted py-4">No demo requests found.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                <div class="p-3">
                    {{ $demoRequests->appends(request()->query())->links() }}
                </div>
            </div>
        </div>

    </div>
</div>
@endsection
