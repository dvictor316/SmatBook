@extends('layout.mainlayout')

@section('content')
<div class="page-wrapper">
    <div class="content container-fluid">
        <style>
            @media (max-width: 767.98px) {
                .customer-page-actions {
                    display: flex;
                    flex-wrap: wrap;
                    gap: 0.5rem;
                    justify-content: flex-start;
                }

                .customer-page-actions > * {
                    flex: 1 1 calc(50% - 0.5rem);
                    min-width: 0;
                }

                .customer-mobile-full {
                    flex-basis: 100%;
                }
            }
        </style>
        
        <div class="page-header">
            <div class="row align-items-center">
                <div class="col">
                    <h3 class="page-title">Customers</h3>
                    <ul class="breadcrumb">
                        <li class="breadcrumb-item"><a href="{{ url('/') }}">Dashboard</a></li>
                        <li class="breadcrumb-item active">Customers</li>
                    </ul>
                </div>
                <div class="col-auto customer-page-actions">
                    <a href="{{ route('customers.create') }}" class="btn btn-primary me-1">
                        <i class="fas fa-plus"></i> Add Customer
                    </a>
                    <button type="button" class="btn btn-outline-primary me-1 customer-mobile-full" data-bs-toggle="modal" data-bs-target="#importCustomersModal">
                        <i class="fas fa-file-upload"></i> Import Customers
                    </button>
                    <a href="{{ route('customers.import.template') }}" class="btn btn-outline-secondary me-1">
                        <i class="fas fa-file-download"></i> Import Template
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
        <div class="alert alert-info border-0 shadow-sm">
            <strong>Total Customer Receivables:</strong>
            ₦{{ number_format($totalReceivables ?? 0, 2) }}
            <span class="text-muted">across all customer balances and unpaid invoices.</span>
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
                                            <th>Balance Date</th>
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
                                                <td><strong>₦{{ number_format($customer->computed_balance ?? $customer->balance, 2) }}</strong></td>
                                                <td>{{ $customer->opening_balance_date ?? '-' }}</td>
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
                                                            <a class="dropdown-item" href="{{ route('reports.customer-statement', $customer->id) }}">
                                                                <i class="far fa-file-alt me-2"></i>Customer Statement
                                                            </a>
                                                            <a class="dropdown-item" href="{{ route('payments.index', ['customer_id' => $customer->id, 'open_payment' => 1]) }}">
                                                                <i class="far fa-credit-card me-2"></i>Receive Payment
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

<div class="modal fade" id="importCustomersModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST" action="{{ route('customers.import') }}" enctype="multipart/form-data">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title">Import Customers</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p class="text-muted mb-3">Upload CSV or Excel files with customer opening balances. Imported balances will reflect on customer credit reporting.</p>
                    <div class="mb-3">
                        <label class="form-label">Spreadsheet File</label>
                        <input type="file" name="import_file" class="form-control" accept=".csv,.txt,.xls,.xlsx,text/csv,application/vnd.ms-excel,application/vnd.openxmlformats-officedocument.spreadsheetml.sheet" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label d-flex align-items-center gap-2">
                            <input type="checkbox" name="update_existing" value="1">
                            <span>Update existing customers when duplicates are found</span>
                        </label>
                        <small class="text-muted">When enabled, imports will update matching customers instead of skipping them.</small>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light border" data-bs-dismiss="modal">Close</button>
                    <button type="submit" class="btn btn-primary">Import Customers</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
