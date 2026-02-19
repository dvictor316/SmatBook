@extends('layout.mainlayout')

@section('content')
<div class="page-wrapper">
    <div class="content container-fluid">
        
        <div class="page-header">
            <div class="row align-items-center">
                <div class="col">
                    <h3 class="page-title">Customers</h3>
                    <ul class="breadcrumb">
                        <li class="breadcrumb-item"><a href="{{ url('/') }}">Dashboard</a></li>
                        <li class="breadcrumb-item active">Customers</li>
                    </ul>
                </div>
                <div class="col-auto">
                    <a href="{{ route('customers.create') }}" class="btn btn-primary me-1">
                        <i class="fas fa-plus"></i> Add Customer
                    </a>
                    <a href="#" onclick="window.print()" class="btn btn-primary me-1">
                        <i class="fas fa-print"></i> Print
                    </a>
                    <a href="{{ route('api.customers') }}" class="btn btn-primary me-1">
                        <i class="fas fa-download"></i> Export
                    </a>
                </div>
            </div>
        </div>
        @component('components.search-filter')
        @endcomponent
        <div class="row">
            <div class="col-sm-12">
                <div class="card-table">
                    <div class="card-body">

                        @if($customers->isEmpty())
                            <div class="alert alert-info text-center">
                                No customers found in the database.
                            </div>
                        @else
                            <div class="table-responsive">
                                <table class="table table-center table-hover datatable">
                                    <thead class="thead-light">
                                        <tr>
                                            <th>#</th>
                                            <th>Customer Name</th>
                                            <th>Phone</th>
                                            <th>Address</th>
                                            <th>Balance</th>
                                            <th>Created</th>
                                            <th>Status</th>
                                            <th class="no-sort text-end">Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach ($customers as $customer)
                                            <tr>
                                                <td>{{ $customer->id }}</td>
                                                <td>
                                                    <h2 class="table-avatar">
                                                        @php
                                                            // Logic for profile image based on your 'image' field
                                                            $imagePath = 'assets/img/profiles/avatar-01.jpg'; // Default
                                                            if ($customer->image && file_exists(public_path('storage/' . $customer->image))) {
                                                                $imagePath = 'storage/' . $customer->image;
                                                            }
                                                        @endphp
                                                        
                                                        <a href="{{ route('customers.show', $customer->id) }}" class="avatar avatar-md me-2">
                                                            <img class="avatar-img rounded-circle"
                                                                src="{{ asset($imagePath) }}"
                                                                alt="Customer Image">
                                                        </a>
                                                        <a href="{{ route('customers.show', $customer->id) }}">
                                                            {{ $customer->customer_name }} {{-- Updated to match schema --}}
                                                            <span>{{ $customer->email ?? 'No Email' }}</span>
                                                        </a>
                                                    </h2>
                                                </td>
                                                <td>{{ $customer->phone ?? 'N/A' }}</td>
                                                <td>{{ \Illuminate\Support\Str::limit($customer->address ?? $customer->billing_address_line1, 30) ?? 'N/A' }}</td>
                                                <td><strong>₦{{ number_format($customer->balance, 2) }}</strong></td>
                                                <td>{{ $customer->created_at ? $customer->created_at->format('d M Y') : 'N/A' }}</td>
                                                <td>
                                                    @php
                                                        $statusClass = (strtolower($customer->status) == 'active') ? 'badge-success' : 'badge-danger';
                                                    @endphp
                                                    <span class="badge badge-pill {{ $statusClass }}">
                                                        {{ ucfirst($customer->status) }}
                                                    </span>
                                                </td>
                                                <td class="text-end">
                                                    <div class="dropdown dropdown-action">
                                                        <a href="#" class="btn-action-icon" data-bs-toggle="dropdown" aria-expanded="false">
                                                            <i class="fas fa-ellipsis-v"></i>
                                                        </a>
                                                        <div class="dropdown-menu dropdown-menu-end">
                                                            <a class="dropdown-item" href="{{ route('customers.edit', $customer->id) }}">
                                                                <i class="far fa-edit me-2"></i>Edit
                                                            </a>
                                                            <a class="dropdown-item" href="{{ route('customers.show', $customer->id) }}">
                                                                <i class="far fa-eye me-2"></i>View Details
                                                            </a>
                                                            <div class="dropdown-divider"></div>
                                                            <form action="{{ route('customers.destroy', $customer->id) }}" method="POST" onsubmit="return confirm('Delete this customer?');">
                                                                @csrf
                                                                @method('DELETE')
                                                                <button type="submit" class="dropdown-item text-danger">
                                                                    <i class="far fa-trash-alt me-2"></i>Delete
                                                                </button>
                                                            </form>
                                                        </div>
                                                    </div>
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                            <div class="mt-3">
                                {{ $customers->links() }}
                            </div>
                        @endif

                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
