// session domain logic
// domain => env('SESSION_DOMAIN', null)

@extends('layout.mainlayout')

@section('content')
@php $page = 'companies'; @endphp

<div class="page-wrapper">
    <div class="content container-fluid">

        <div class="page-header d-print-none">
            <div class="row align-items-center">
                <div class="col">
                    <h3 class="page-title">Companies Management</h3>
                    <ul class="breadcrumb">
                        <li class="breadcrumb-item"><a href="{{ route('super_admin.dashboard') }}">Dashboard</a></li>
                        <li class="breadcrumb-item active">Companies</li>
                    </ul>
                </div>
                <div class="col-auto float-right ml-auto">
                    <button onclick="window.print();" class="btn btn-white text-muted mr-2">
                        <i class="fas fa-print"></i> Print Directory
                    </button>
                    <a href="{{ route('super_admin.companies.create') }}" class="btn btn-primary">
                        <i class="fas fa-plus"></i> Add Company
                    </a>
                </div>
            </div>
        </div>
        <div class="row">
            <div class="col-xl-3 col-sm-6 col-12">
                <div class="card bg-comman w-100">
                    <div class="card-body">
                        <div class="db-widgets d-flex justify-content-between align-items-center">
                            <div class="db-info">
                                <h6>Total Companies</h6>
                                <h3>{{ $totalCompanies }}</h3>
                            </div>
                            <div class="db-icon bg-primary-light">
                                <i class="fas fa-building text-primary"></i>
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
                                <h6>Active</h6>
                                <h3 class="text-success">{{ $activeCompanies }}</h3>
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
                                <h6>Inactive</h6>
                                <h3 class="text-danger">{{ $inactiveCompanies }}</h3>
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
                                <h6>With Address</h6>
                                <h3>{{ $companiesWithAddress }}</h3>
                            </div>
                            <div class="db-icon bg-warning-light">
                                <i class="fas fa-map-marker-alt text-warning"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-sm-12">
                <div class="card card-table">
                    <div class="card-header">
                        <h4 class="card-title">Detailed Company List</h4>
                    </div>
                    <div class="card-body">
                        @if (session('success'))
                            <div class="alert alert-success alert-dismissible fade show" role="alert">
                                <strong>Success!</strong> {{ session('success') }}
                                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                            </div>
                        @endif

                        <div class="table-responsive">
                            <table class="datatable table table-hover table-center mb-0">
                                <thead class="thead-light">
                                    <tr>
                                        <th>Company Name</th>
                                        <th>Subdomain</th>
                                        <th>Primary Email</th>
                                        <th>Plan</th>
                                        <th>Status</th>
                                        <th>Join Date</th>
                                        <th class="text-end d-print-none">Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($companies as $company)
                                        <tr>
                                            <td>
                                                <h2 class="table-avatar">
                                                    @if($company->logo)
                                                        <img class="avatar-img rounded-circle me-2" src="{{ asset('storage/'.$company->logo) }}" width="30" alt="Logo">
                                                    @endif
                                                    <a href="{{ route('super_admin.companies.edit', $company->id) }}" class="text-primary font-weight-bold">{{ $company->name }}</a>
                                                </h2>
                                            </td>
                                            <td><span class="badge bg-light text-dark">{{ $company->subdomain ?? 'N/A' }}</span></td>
                                            <td>{{ $company->email ?? 'N/A' }}</td>
                                            <td><span class="text-uppercase small font-weight-bold">{{ $company->plan }}</span></td>
                                            <td>
                                                @if(strtolower($company->status) == 'active')
                                                    <span class="badge badge-success">Active</span>
                                                @else
                                                    <span class="badge badge-danger">Inactive</span>
                                                @endif
                                            </td>
                                            <td>{{ $company->created_at->format('d M Y') }}</td>
                                            <td class="text-end d-print-none">
                                                <div class="actions">

                                                    <form action="{{ route('super_admin.companies.impersonate', $company->id) }}" method="POST" class="d-inline">
                                                        @csrf
                                                        <button type="submit" class="btn btn-sm bg-info-light me-2" title="Login as Admin">
                                                            <i class="fas fa-user-secret"></i>
                                                        </button>
                                                    </form>

                                                    <a href="{{ route('super_admin.companies.edit', $company->id) }}" class="btn btn-sm bg-success-light me-2">
                                                        <i class="fas fa-edit"></i>
                                                    </a>

                                                    <a href="#" class="btn btn-sm bg-danger-light" data-bs-toggle="modal" data-bs-target="#delete_modal_{{ $company->id }}">
                                                        <i class="fas fa-trash"></i>
                                                    </a>
                                                </div>
                                            </td>
                                        </tr>

                                        <div class="modal fade" id="delete_modal_{{ $company->id }}" tabindex="-1" role="dialog" aria-hidden="true">
                                            <div class="modal-dialog modal-dialog-centered" role="document">
                                                <div class="modal-content">
                                                    <div class="modal-header border-0">
                                                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                                    </div>
                                                    <div class="modal-body text-center">
                                                        <div class="form-content p-2">
                                                            <i class="fas fa-exclamation-triangle fa-3x text-danger mb-3"></i>
                                                            <h4 class="modal-title">Confirm Deletion</h4>
                                                            <p class="mb-4 text-muted">Are you sure you want to remove <strong>{{ $company->name }}</strong>? This action will affect linked chat and email data.</p>
                                                            <form action="{{ route('super_admin.companies.destroy', $company->id) }}" method="POST">
                                                                @csrf
                                                                @method('DELETE')
                                                                <button type="submit" class="btn btn-danger">Confirm Delete</button>
                                                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                                            </form>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    // Universal printing script 
    window.onbeforeprint = function() {
        console.log("Printing Company Directory context for session: {{ env('SESSION_DOMAIN', 'null') }}");
    };

    // Sidebar Active State Sync
    document.addEventListener("DOMContentLoaded", function() {
        const activeLink = document.querySelector('.sidebar-menu a[href*="companies"]');
        if (activeLink) {
            activeLink.closest('li').classList.add('active');
        }
    });
</script>
@endsection
