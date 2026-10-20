@extends('layout.mainlayout')

@section('content')
<div class="page-wrapper" id="customer-directory-page">
    <div class="content container-fluid">
        <style>
            /* Compact directory styles stay local to the customer list. */
            body #customer-directory-page > .content.container-fluid { padding:12px 18px 16px !important; }
            #customer-directory-page .page-header { margin-bottom:10px; }
            #customer-directory-page .page-title { font-size:20px !important; margin-bottom:3px; }
            #customer-directory-page .breadcrumb { margin:0; font-size:12px; }
            #customer-directory-page .customer-page-actions { display:flex; flex-wrap:wrap; gap:6px; }
            #customer-directory-page .btn:not(.btn-close) { min-height:32px !important; padding:5px 10px !important; border-radius:6px !important; font-size:12px !important; line-height:1.5; font-weight:600; box-shadow:none !important; }
            #customer-directory-page .customer-page-actions .btn { margin:0 !important; }
            #customer-directory-page .customer-filter-card,
            #customer-directory-page .customer-table-card { border:1px solid #dce3ed; border-radius:8px !important; box-shadow:none; margin-bottom:10px !important; }
            #customer-directory-page .customer-filter-card { background:#f1f2f4; }
            #customer-directory-page .customer-filter-card > .card-body { padding:12px !important; }
            #customer-directory-page .directory-overview { display:flex; align-items:center; justify-content:space-between; flex-wrap:wrap; gap:6px 16px; margin-bottom:10px; }
            #customer-directory-page .directory-description { margin:0; color:#526176; font-size:12px; }
            #customer-directory-page .report-stamp { display:flex; flex-wrap:wrap; gap:4px 14px; color:#526176; font-size:11px; }
            #customer-directory-page .report-stamp span { color:#334155; font-weight:600; }
            #customer-directory-page .customer-summary-grid { display:grid; grid-template-columns:repeat(6,minmax(0,1fr)); gap:8px; margin-bottom:12px; }
            #customer-directory-page .summary-card { min-width:0; padding:8px 10px; background:#fff; border:1px solid #dce3ed; border-radius:6px; }
            #customer-directory-page .summary-label { display:block; color:#475569; font-size:11px; font-weight:600; line-height:1.4; }
            #customer-directory-page .summary-label i { margin-right:4px !important; }
            #customer-directory-page .summary-card strong { display:block; margin-top:4px; color:#172b4d; font-size:13px; font-weight:700; line-height:1.4; overflow-wrap:anywhere; }
            #customer-directory-page .customer-filter-grid { display:grid; grid-template-columns:minmax(170px,1.5fr) repeat(4,minmax(115px,1fr)) auto; gap:8px; align-items:end; }
            #customer-directory-page .customer-filter-grid > div { min-width:0; }
            #customer-directory-page .filter-label { display:block; margin-bottom:4px !important; color:#334155; font-size:12px; font-weight:600; }
            #customer-directory-page .customer-filter-grid .form-control,
            #customer-directory-page .customer-filter-grid .form-select { height:34px; min-height:34px; padding:5px 9px; border:1px solid #d8e0eb; border-radius:6px; background-color:#fff; color:#172b4d; font-size:12px; }
            #customer-directory-page .customer-table-card > .card-body { padding:0 !important; }
            #customer-directory-page .customer-table-scroll { max-height:max(260px,calc(100dvh - 365px)); overflow:auto; border-radius:6px; }
            #customer-directory-page .table { margin:0; }
            #customer-directory-page .table thead th { position:sticky; top:0; z-index:2; padding:9px 10px !important; background:#edf2f8; color:#334155; font-size:11px !important; font-weight:700; letter-spacing:0; white-space:nowrap; }
            #customer-directory-page .table tbody td { padding:8px 10px !important; vertical-align:middle; color:#334155; font-size:12px !important; line-height:1.45; }
            #customer-directory-page .table td.text-end { white-space:nowrap; }
            #customer-directory-page .table-avatar { display:flex; align-items:center; margin:0; font-size:12px !important; }
            #customer-directory-page .table-avatar .avatar { flex:0 0 28px; width:28px; height:28px; }
            #customer-directory-page .customer-name-stack { min-width:145px; max-width:210px; color:#172b4d; font-size:12px !important; font-weight:600 !important; white-space:normal; }
            #customer-directory-page .customer-meta,
            #customer-directory-page .table td small { display:block; color:#64748b; font-size:11px !important; font-weight:400; line-height:1.4; }
            #customer-directory-page .customer-meta span { font-size:11px; }
            #customer-directory-page .table td:nth-child(3) { min-width:150px; }
            #customer-directory-page .table td:nth-child(4) { min-width:130px; }
            #customer-directory-page .customer-chip { display:inline-flex; padding:3px 6px; border-radius:4px; font-size:11px; font-weight:600; white-space:nowrap; }
            #customer-directory-page .table tr > :last-child { position:sticky; right:0; background:#fff; box-shadow:-1px 0 #e2e8f0; }
            #customer-directory-page .table thead tr > :last-child { z-index:3; background:#edf2f8; }
            #customer-directory-page .customer-pagination { padding:8px 12px; border-top:1px solid #e2e8f0; }
            #customer-directory-page .customer-pagination nav { margin:0; }
            #customer-directory-page .customer-pagination .pagination { margin-bottom:0; }
            @media (max-width:1199.98px) {
                #customer-directory-page .customer-filter-grid { grid-template-columns:repeat(4,minmax(0,1fr)); }
                #customer-directory-page .customer-filter-search { grid-column:span 2; }
            }
            @media (max-width:767.98px) {
                body #customer-directory-page > .content.container-fluid { padding:10px !important; }
                #customer-directory-page .page-header .col-auto { width:100%; margin-top:8px; }
                #customer-directory-page .customer-summary-grid { grid-template-columns:repeat(3,minmax(0,1fr)); }
                #customer-directory-page .customer-filter-grid { grid-template-columns:repeat(2,minmax(0,1fr)); }
                #customer-directory-page .customer-filter-actions { grid-column:1 / -1; }
                #customer-directory-page .customer-table-scroll { max-height:60dvh; }
            }
            @media (max-width:419.98px) {
                #customer-directory-page .customer-summary-grid { grid-template-columns:repeat(2,minmax(0,1fr)); }
            }
            @media print {
                #customer-directory-page .customer-table-scroll { max-height:none; overflow:visible; }
                #customer-directory-page .table tr > :last-child,
                #customer-directory-page .table thead th { position:static; box-shadow:none; }
                #customer-directory-page .customer-page-actions,
                #customer-directory-page .customer-filter-grid { display:none; }
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
                    <div class="directory-overview">
                        <p class="directory-description">Find customers, review balances and manage their accounts.</p>
                        <div class="report-stamp">
                            <div><span>Coverage:</span> {{ $reportMeta['period_label'] ?? 'All customer records' }}</div>
                            <div><span>Generated:</span> {{ optional($reportMeta['generated_at'] ?? null)->format('d M Y, h:i A') }}</div>
                            <div><span>Branch:</span> {{ $reportMeta['branch_name'] ?? 'Workspace Default' }}</div>
                            <div><span>Status Scope:</span> {{ $reportMeta['status_scope'] ?? 'All statuses' }}</div>
                        </div>
                    </div>

                    <div class="customer-summary-grid">
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
                        <div class="customer-filter-search">
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
                        <div class="d-flex gap-2 justify-content-end customer-filter-actions">
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
                            <div class="table-responsive customer-table-scroll" tabindex="0" role="region" aria-label="Customer list">
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
                                                            <small class="customer-meta">
                                                                <span><i class="fas fa-id-card me-1"></i> Customer ID: CL-{{ str_pad((string) $customer->id, 4, '0', STR_PAD_LEFT) }}</span>
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
                                                    <div class="dropdown dropdown-action">
                                                        <button type="button" class="btn btn-sm btn-primary dropdown-toggle" data-bs-toggle="dropdown" data-sheet-title="Customer actions" data-spb-desktop-clone-dropdown="true" aria-expanded="false">
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
                                                            @if(auth()->user()?->isDemoUser())
                                                                <li><hr class="dropdown-divider"></li>
                                                                <li>
                                                                    <a class="dropdown-item" href="{{ route('customers.demo-preview', ['id' => $customer->id, 'plan' => 'basic']) }}">
                                                                        <i class="far fa-window-maximize me-2 text-primary"></i>Preview Demo Basic
                                                                    </a>
                                                                </li>
                                                                <li>
                                                                    <a class="dropdown-item" href="{{ route('customers.demo-preview', ['id' => $customer->id, 'plan' => 'pro']) }}">
                                                                        <i class="far fa-window-maximize me-2 text-info"></i>Preview Demo Pro
                                                                    </a>
                                                                </li>
                                                                <li>
                                                                    <a class="dropdown-item" href="{{ route('customers.demo-preview', ['id' => $customer->id, 'plan' => 'enterprise']) }}">
                                                                        <i class="far fa-window-maximize me-2 text-warning"></i>Preview Demo Enterprise
                                                                    </a>
                                                                </li>
                                                            @endif
                                                            <li>
                                                                <a class="dropdown-item" href="{{ route('customers.receive-payment', $customer->id) }}">
                                                                    <i class="far fa-credit-card me-2 text-success"></i>Receive Payment
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
                            <div class="customer-pagination">
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
@endsection
