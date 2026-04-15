@extends('layout.mainlayout')

@section('content')
<div class="page-wrapper">
    <div class="content container-fluid">
        <style>
            .customer-report-shell .report-stamp {
                font-size: 0.78rem;
                color: #64748b;
                background: linear-gradient(135deg, #eff6ff, #f8fafc);
                border: 1px solid #dbeafe;
                border-radius: 18px;
                padding: 0.95rem 1rem;
                min-width: 280px;
            }

            .customer-report-shell .report-stamp span,
            .customer-report-shell .summary-label,
            .customer-report-shell .filter-label {
                color: #334155;
                font-weight: 700;
            }

            .customer-report-shell .customer-summary-grid {
                display: grid;
                grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
                gap: 0.85rem;
            }

            .customer-report-shell .summary-card {
                border: 1px solid #e2e8f0;
                border-radius: 18px;
                background: #fff;
                box-shadow: 0 10px 24px rgba(15, 23, 42, 0.05);
                padding: 1rem 1.05rem;
            }

            .customer-report-shell .summary-card strong {
                display: block;
                margin-top: 0.3rem;
                color: #0f172a;
                font-size: 1rem;
            }

            .customer-report-shell .customer-filter-card,
            .customer-report-shell .customer-table-card {
                border: 0;
                border-radius: 22px;
                box-shadow: 0 16px 34px rgba(15, 23, 42, 0.06);
            }

            .customer-report-shell .customer-filter-grid {
                display: grid;
                grid-template-columns: minmax(0, 1.7fr) repeat(4, minmax(0, 1fr));
                gap: 0.85rem;
                align-items: end;
            }

            .customer-report-shell .customer-filter-grid .form-control,
            .customer-report-shell .customer-filter-grid .form-select {
                min-height: 46px;
                border-radius: 14px;
            }

            .customer-report-shell .table thead th {
                background: #f8fafc;
                font-size: 0.73rem;
                text-transform: uppercase;
                letter-spacing: 0.05em;
                color: #334155;
                white-space: nowrap;
            }

            .customer-report-shell .table tbody td {
                vertical-align: top;
                font-size: 0.9rem;
            }

            .customer-report-shell .customer-name-stack {
                min-width: 220px;
            }

            .customer-report-shell .customer-meta {
                display: grid;
                gap: 0.15rem;
                color: #64748b;
                font-size: 0.78rem;
            }

            .customer-report-shell .customer-chip {
                display: inline-flex;
                align-items: center;
                gap: 0.45rem;
                padding: 0.4rem 0.7rem;
                border-radius: 999px;
                background: #eef2ff;
                color: #3730a3;
                font-size: 0.74rem;
                font-weight: 700;
                white-space: nowrap;
            }

            .customer-report-shell .customer-actions {
                min-width: 270px;
            }

            .customer-report-shell .customer-action-btn {
                border-radius: 8px;
                white-space: nowrap;
            }

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

                .customer-report-shell .customer-filter-grid {
                    grid-template-columns: 1fr;
                }

                .customer-report-shell .report-stamp {
                    min-width: auto;
                }

                .customer-report-shell .customer-actions {
                    min-width: 0;
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
        <div class="customer-report-shell">
            <div class="card customer-filter-card mb-4">
                <div class="card-body">
                    <div class="d-flex flex-column flex-xl-row justify-content-between gap-3 mb-4">
                        <div>
                            <h4 class="mb-1">{{ $reportMeta['title'] ?? 'Customers' }} Directory</h4>
                            <p class="text-muted mb-0">Search, segment and review customers by contact quality, onboarding date, status, receivables exposure and branch coverage.</p>
                        </div>
                        <div class="report-stamp">
                            <div><span>Coverage:</span> {{ $reportMeta['period_label'] ?? 'All customer records' }}</div>
                            <div><span>Generated:</span> {{ optional($reportMeta['generated_at'] ?? null)->format('d M Y, h:i A') }}</div>
                            <div><span>Branch:</span> {{ $reportMeta['branch_name'] ?? 'Workspace Default' }}</div>
                            <div><span>Status Scope:</span> {{ $reportMeta['status_scope'] ?? 'All statuses' }}</div>
                        </div>
                    </div>

                    <div class="customer-summary-grid mb-4">
                        <div class="summary-card">
                            <span class="summary-label"><i class="fas fa-users me-2"></i>Total Customers</span>
                            <strong>{{ number_format($summary['total_customers'] ?? 0) }}</strong>
                        </div>
                        <div class="summary-card">
                            <span class="summary-label"><i class="fas fa-user-check me-2"></i>Active Customers</span>
                            <strong>{{ number_format($summary['active_customers'] ?? 0) }}</strong>
                        </div>
                        <div class="summary-card">
                            <span class="summary-label"><i class="fas fa-address-book me-2"></i>Contacts On File</span>
                            <strong>{{ number_format($summary['contacts_on_file'] ?? 0) }}</strong>
                        </div>
                        <div class="summary-card">
                            <span class="summary-label"><i class="fas fa-wallet me-2"></i>Opening Balance Total</span>
                            <strong>₦{{ number_format($summary['total_opening_balance'] ?? 0, 2) }}</strong>
                        </div>
                        <div class="summary-card">
                            <span class="summary-label"><i class="fas fa-file-invoice-dollar me-2"></i>Invoice Due Total</span>
                            <strong>₦{{ number_format($summary['total_invoice_due'] ?? 0, 2) }}</strong>
                        </div>
                        <div class="summary-card">
                            <span class="summary-label"><i class="fas fa-scale-balanced me-2"></i>Total Receivables</span>
                            <strong>₦{{ number_format($summary['total_receivables'] ?? 0, 2) }}</strong>
                        </div>
                    </div>

                    <form method="GET" action="{{ url()->current() }}" class="customer-filter-grid">
                        <div>
                            <label class="filter-label mb-2">Search Customer</label>
                            <input type="text" name="search" class="form-control" value="{{ request('search') }}" placeholder="Search business name, email, phone, or contact">
                        </div>
                        <div>
                            <label class="filter-label mb-2">Status</label>
                            <select name="status" class="form-select">
                                <option value="">All statuses</option>
                                <option value="active" @selected(request('status') === 'active')>Active</option>
                                <option value="deactive" @selected(request('status') === 'deactive')>Inactive</option>
                            </select>
                        </div>
                        <div>
                            <label class="filter-label mb-2">Quick Range</label>
                            <select name="quick_range" class="form-select">
                                <option value="">All time</option>
                                <option value="today" @selected(request('quick_range') === 'today')>Today</option>
                                <option value="yesterday" @selected(request('quick_range') === 'yesterday')>Yesterday</option>
                                <option value="this_week" @selected(request('quick_range') === 'this_week')>This Week</option>
                                <option value="last_week" @selected(request('quick_range') === 'last_week')>Last Week</option>
                                <option value="last_7_days" @selected(request('quick_range') === 'last_7_days')>Last 7 Days</option>
                                <option value="last_30_days" @selected(request('quick_range') === 'last_30_days')>Last 30 Days</option>
                                <option value="this_month" @selected(request('quick_range') === 'this_month')>This Month</option>
                                <option value="last_month" @selected(request('quick_range') === 'last_month')>Last Month</option>
                                <option value="this_year" @selected(request('quick_range') === 'this_year')>This Year</option>
                                <option value="last_year" @selected(request('quick_range') === 'last_year')>Last Year</option>
                            </select>
                        </div>
                        <div>
                            <label class="filter-label mb-2">From Date</label>
                            <input type="date" name="start_date" class="form-control" value="{{ request('start_date') }}">
                        </div>
                        <div>
                            <label class="filter-label mb-2">To Date</label>
                            <input type="date" name="end_date" class="form-control" value="{{ request('end_date') }}">
                        </div>
                        <div class="d-flex gap-2 justify-content-end">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-filter me-1"></i> Apply
                            </button>
                            <a href="{{ url()->current() }}" class="btn btn-outline-secondary">
                                <i class="fas fa-rotate-left me-1"></i> Reset
                            </a>
                        </div>
                    </form>
                </div>
            </div>

        <div class="row">
            <div class="col-sm-12">
                <div class="card-table customer-table-card">
                    <div class="card-body">

                        @if($customers->isEmpty())
                            <div class="alert alert-info text-center">
                                No customers found in the database.
                            </div>
                        @else
                            <div class="table-responsive">
                                <table class="table table-center table-hover">
                                    <thead class="thead-light">
                                        <tr>
                                            <th>#</th>
                                            <th>Customer / Business</th>
                                            <th>Contact Details</th>
                                            <th>Location</th>
                                            <th class="text-end">Opening Balance</th>
                                            <th class="text-end">Invoice Due</th>
                                            <th class="text-end">Total Due</th>
                                            <th class="text-end">Credit Limit</th>
                                            <th>Onboarded</th>
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
                                                        <a href="{{ route('customers.show', $customer->id) }}" class="customer-name-stack">
                                                            {{ $customer->customer_name }}
                                                            <span>{{ $customer->email ?? 'No Email' }}</span>
                                                            <small class="customer-meta">
                                                                <span><i class="fas fa-id-card me-1"></i> Customer ID: CL-{{ str_pad((string) $customer->id, 4, '0', STR_PAD_LEFT) }}</span>
                                                                <span><i class="fas fa-calendar-plus me-1"></i> Created {{ $customer->created_at ? $customer->created_at->format('d M Y') : 'N/A' }}</span>
                                                            </small>
                                                        </a>
                                                    </h2>
                                                </td>
                                                <td>
                                                    <div><i class="fas fa-phone-alt me-2 text-muted"></i>{{ $customer->phone ?: 'No phone saved' }}</div>
                                                    <div><i class="fas fa-envelope me-2 text-muted"></i>{{ $customer->email ?: 'No email saved' }}</div>
                                                </td>
                                                <td>
                                                    <div>{{ \Illuminate\Support\Str::limit($customer->address ?? $customer->billing_address_line1 ?? $customer->billing_city ?? 'No address saved', 40) }}</div>
                                                    <small class="text-muted">{{ $customer->billing_city ?? $customer->shipping_city ?? 'No city' }}</small>
                                                </td>
                                                <td class="text-end fw-semibold">₦{{ number_format((float) ($customer->balance ?? 0), 2) }}</td>
                                                <td class="text-end fw-semibold">₦{{ number_format((float) ($customer->sales_balance_sum ?? 0), 2) }}</td>
                                                <td class="text-end fw-bold text-danger">₦{{ number_format((float) ($customer->computed_balance ?? $customer->balance), 2) }}</td>
                                                <td class="text-end">₦{{ number_format((float) ($customer->credit_limit ?? 0), 2) }}</td>
                                                <td>
                                                    <div>{{ $customer->created_at ? $customer->created_at->format('d M Y') : 'N/A' }}</div>
                                                    <small class="text-muted">Balance date: {{ $customer->opening_balance_date ?? '—' }}</small>
                                                </td>
                                                <td>
                                                    @php
                                                        $statusClass = (strtolower($customer->status) == 'active') ? 'badge-success' : 'badge-danger';
                                                    @endphp
                                                    <span class="badge badge-pill {{ $statusClass }} customer-chip">
                                                        {{ ucfirst($customer->status) }}
                                                    </span>
                                                </td>
                                                <td class="text-end">
                                                    <div class="dropdown">
                                                        <button type="button" class="btn btn-sm btn-primary dropdown-toggle" data-bs-toggle="dropdown" aria-expanded="false">
                                                            Action
                                                        </button>
                                                        <ul class="dropdown-menu dropdown-menu-end">
                                                            <li>
                                                                <a class="dropdown-item" href="{{ route('customers.edit', $customer->id) }}">
                                                                    <i class="far fa-edit me-2 text-primary"></i>Edit
                                                                </a>
                                                            </li>
                                                            <li>
                                                                <a class="dropdown-item" href="{{ route('customers.show', $customer->id) }}">
                                                                    <i class="far fa-eye me-2 text-info"></i>View
                                                                </a>
                                                            </li>
                                                            <li>
                                                                <a class="dropdown-item" href="{{ route('reports.customer-statement', $customer->id) }}">
                                                                    <i class="far fa-file-alt me-2 text-secondary"></i>Statement
                                                                </a>
                                                            </li>
                                                            <li>
                                                                <a class="dropdown-item" href="{{ route('customers.receive-payment', $customer->id) }}">
                                                                    <i class="far fa-credit-card me-2 text-success"></i>Receive
                                                                </a>
                                                            </li>
                                                            <li><hr class="dropdown-divider"></li>
                                                            <li>
                                                                <form action="{{ route('customers.destroy', $customer->id) }}" method="POST" onsubmit="return confirm('Delete this customer?');">
                                                                    @csrf
                                                                    @method('DELETE')
                                                                    <button type="submit" class="dropdown-item text-danger">
                                                                        <i class="far fa-trash-alt me-2"></i>Delete
                                                                    </button>
                                                                </form>
                                                            </li>
                                                        </ul>
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
@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    // Each time a dropdown is about to show, move its menu to <body>
    // so it is never clipped by overflow:auto on .table-responsive
    document.querySelectorAll('.dropdown [data-bs-toggle="dropdown"]').forEach(function (toggle) {
        var menu = toggle.closest('.dropdown').querySelector('.dropdown-menu');
        if (!menu) return;

        toggle.addEventListener('show.bs.dropdown', function () {
            var rect = toggle.getBoundingClientRect();
            menu.style.position   = 'fixed';
            menu.style.zIndex     = '9999';
            menu.style.top        = (rect.bottom + window.scrollY) + 'px';
            menu.style.left       = 'auto';
            menu.style.right      = (window.innerWidth - rect.right) + 'px';
            menu.style.minWidth   = '160px';
            document.body.appendChild(menu);
        });

        toggle.addEventListener('hide.bs.dropdown', function () {
            toggle.closest('.dropdown').appendChild(menu);
            menu.removeAttribute('style');
        });
    });
});
</script>
@endpush

@endsection
