
@php
    $routeName = Route::currentRouteName();
    $knownFilterKeys = ['q', 'status', 'type', 'source_type', 'reimbursement_status', 'period_type', 'party_type', 'month', 'from_date', 'to_date'];
    $filterConfig = [
        'search_label' => 'Search',
        'search_placeholder' => 'Search records',
        'extra_fields' => [],
    ];

    $filterConfigs = [
        'expenses.index' => [
            'search_label' => 'Expense Search',
            'search_placeholder' => 'Expense ID, supplier, reference',
            'extra_fields' => [
                ['name' => 'status', 'label' => 'Status', 'options' => ['Paid' => 'Paid', 'Pending' => 'Pending', 'Overdue' => 'Overdue']],
            ],
        ],
        'finance.expense-claims.index' => [
            'search_label' => 'Claim Search',
            'search_placeholder' => 'Claim, employee, project',
            'extra_fields' => [
                ['name' => 'status', 'label' => 'Claim Status', 'options' => ['pending' => 'Pending', 'approved' => 'Approved', 'rejected' => 'Rejected', 'reimbursed' => 'Reimbursed']],
                ['name' => 'reimbursement_status', 'label' => 'Reimbursement', 'options' => ['unpaid' => 'Unpaid', 'paid' => 'Paid']],
            ],
        ],
        'finance.approvals.index' => [
            'search_label' => 'Approval Search',
            'search_placeholder' => 'Reference, title, requester',
            'extra_fields' => [
                ['name' => 'status', 'label' => 'Status', 'options' => ['pending' => 'Pending', 'approved' => 'Approved', 'rejected' => 'Rejected']],
                ['name' => 'type', 'label' => 'Type', 'options' => ['expense' => 'Expense', 'purchase' => 'Purchase', 'payment' => 'Payment']],
            ],
        ],
        'payments.index' => [
            'search_label' => 'Payment Search',
            'search_placeholder' => 'Receipt, reference, method, customer',
            'extra_fields' => [
                ['name' => 'status', 'label' => 'Status', 'options' => ['Completed' => 'Completed', 'Pending' => 'Pending', 'Pending Approval' => 'Pending Approval', 'Rejected' => 'Rejected']],
            ],
        ],
        'finance.recurring.index' => [
            'search_label' => 'Template Search',
            'search_placeholder' => 'Template, source ref, source name',
            'extra_fields' => [
                ['name' => 'status', 'label' => 'Status', 'options' => ['active' => 'Active', 'paused' => 'Paused']],
                ['name' => 'source_type', 'label' => 'Source Type', 'options' => ['expense' => 'Expense', 'purchase' => 'Purchase']],
            ],
        ],
        'finance.fixed-assets.index' => [
            'search_label' => 'Asset Search',
            'search_placeholder' => 'Asset, code, notes',
            'extra_fields' => [
                ['name' => 'status', 'label' => 'Status', 'options' => ['active' => 'Active', 'fully_depreciated' => 'Fully Depreciated', 'disposed' => 'Disposed', 'archived' => 'Archived']],
            ],
        ],
        'finance.budgets.index' => [
            'search_label' => 'Budget Search',
            'search_placeholder' => 'Budget, account, note',
            'extra_fields' => [
                ['name' => 'status', 'label' => 'Status', 'options' => ['active' => 'Active', 'archived' => 'Archived']],
                ['name' => 'period_type', 'label' => 'Period Type', 'options' => ['monthly' => 'Monthly', 'quarterly' => 'Quarterly', 'yearly' => 'Yearly', 'custom' => 'Custom']],
            ],
        ],
        'finance.collections.index' => [
            'search_label' => 'Receivables / Payables Search',
            'search_placeholder' => 'Customer, supplier, invoice, purchase',
            'extra_fields' => [],
        ],
        'finance.follow-ups.index' => [
            'search_label' => 'Follow-Up Search',
            'search_placeholder' => 'Party, reminder, note',
            'extra_fields' => [
                ['name' => 'status', 'label' => 'Status', 'options' => ['open' => 'Open', 'completed' => 'Completed', 'cancelled' => 'Cancelled']],
                ['name' => 'party_type', 'label' => 'Party Type', 'options' => ['customer' => 'Customer', 'supplier' => 'Supplier']],
            ],
        ],
        'inventory.transfer-audit' => [
            'search_label' => 'Transfer Search',
            'search_placeholder' => 'Product, branch, initiated by',
            'extra_fields' => [],
        ],
    ];

    if (isset($filterConfigs[$routeName])) {
        $filterConfig = array_merge($filterConfig, $filterConfigs[$routeName]);
    }

    $activeFilterCount = 0;
    foreach ($knownFilterKeys as $key) {
        $value = request()->query($key);
        if (is_array($value)) {
            $activeFilterCount += count(array_filter($value, fn ($item) => filled($item)));
        } elseif (filled($value)) {
            $activeFilterCount++;
        }
    }
@endphp
@if(!Route::is(['companies','subscription','packages','plans-list']))
<div class="page-header">

    <div class="content-page-header">
        <h5>{{ $title }}</h5>
        @if (Route::is(['custom-filed', 'profit-loss-list', 'sales-return-report', 'stock-report']))
            <div class="page-content">
        @endif
        <div class="list-btn">
            <ul class="filter-list">
                @if (Route::is([
                        'roles-permission',
                        'tickets',
                        'tickets-list-open',
                        'tickets-list-resolved',
                        'tickets-list-pending',
                        'tickets-list-closed',
                        'tickets-list',
                        'tickets-resolved',
                        'tickets-pending',
                        'tickets-open',
                        'tickets-closed',
                    ]))
                    <li>
                        <div class="short-filter">
                            <img class="me-2" src="{{ URL::asset('/assets/img/icons/sort.svg') }}" alt="Sort by select">
                            <div class="sort-by sort-by-ticket">
                                <select class="sort select">
                                    <option>Sort by: Date</option>
                                    <option>Sort by: Date 1</option>
                                    <option>Sort by: Date 2</option>
                                </select>
                            </div>
                        </div>
                    </li>
                @endif
                @if (Route::is(['tickets-kanban']))
                    <li>
                        <div class="short-filter">
                            <img class="me-2" src="{{ URL::asset('/assets/img/icons/filter-icon-2.svg') }}"
                                alt="Sort by select">
                            <div class="sort-by sort-by-ticket">
                                <select class="sort select">
                                    <option>Sort by: Date</option>
                                    <option>Sort by: Date 1</option>
                                    <option>Sort by: Date 2</option>
                                </select>
                            </div>
                        </div>
                    </li>
                @endif
                @if (Route::is(['ticket-details']))
                    <li>
                        <a class="btn btn-primary popup-toggle rounded-circle d-inline-flex p-2"
                            href="javascript:void(0);"><i class="fe fe-filter" aria-hidden="true"></i></a>
                    </li>
                @endif
                @if (
                    !Route::is([
                        'units',
                        'signature-list',
                        'tax-purchase',
                        'tax-sales',
                        'membership-plans',
                        'ticket-details',
                        'packages',
                        'purchases-details',
                        'ledger',
                        'calendar',
                        'customers-ledger',
                        'domain',
                        'purchase-transaction',
                        'reports.*'
                    ]))
                    <li>
                        <div class="dropdown">
                            <button class="btn btn-filters w-auto dropdown-toggle border-0" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                                <span class="me-2"><img src="{{ URL::asset('/assets/img/icons/filter-icon.svg') }}" alt="filter"></span>Filter
                                @if($activeFilterCount > 0)
                                    <span class="badge bg-primary ms-2">{{ $activeFilterCount }}</span>
                                @endif
                            </button>
                            <div class="dropdown-menu dropdown-menu-end p-3 shadow border-0" style="min-width: 340px;">
                                <form method="GET" action="{{ url()->current() }}" class="shared-page-filter-form">
                                    @foreach(request()->query() as $key => $value)
                                        @if(!in_array($key, $knownFilterKeys, true) && !is_array($value))
                                            <input type="hidden" name="{{ $key }}" value="{{ $value }}">
                                        @endif
                                    @endforeach

                                    <div class="mb-3">
                                        <label class="form-label fw-semibold">{{ $filterConfig['search_label'] }}</label>
                                        <input type="text" name="q" class="form-control" value="{{ request('q') }}" placeholder="{{ $filterConfig['search_placeholder'] }}">
                                    </div>

                                    @foreach($filterConfig['extra_fields'] as $field)
                                        <div class="mb-3">
                                            <label class="form-label fw-semibold">{{ $field['label'] }}</label>
                                            <select name="{{ $field['name'] }}" class="form-select">
                                                <option value="">All</option>
                                                @foreach($field['options'] as $optionValue => $optionLabel)
                                                    <option value="{{ $optionValue }}" {{ (string) request($field['name']) === (string) $optionValue ? 'selected' : '' }}>{{ $optionLabel }}</option>
                                                @endforeach
                                            </select>
                                        </div>
                                    @endforeach

                                    <div class="mb-3">
                                        <label class="form-label fw-semibold">Month</label>
                                        <input type="month" name="month" class="form-control" value="{{ request('month') }}">
                                    </div>

                                    <div class="row g-2">
                                        <div class="col-6">
                                            <label class="form-label fw-semibold">From Date</label>
                                            <input type="date" name="from_date" class="form-control" value="{{ request('from_date') }}">
                                        </div>
                                        <div class="col-6">
                                            <label class="form-label fw-semibold">To Date</label>
                                            <input type="date" name="to_date" class="form-control" value="{{ request('to_date') }}">
                                        </div>
                                    </div>

                                    <div class="d-flex gap-2 mt-3">
                                        <button type="submit" class="btn btn-primary flex-fill">Apply Filter</button>
                                        <a href="{{ url()->current() }}" class="btn btn-light border flex-fill">Reset</a>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </li>
                @endif
                @if(Route::is(['purchase-transaction']))
                <li>
                    <a class="btn btn-filters w-auto popup-toggle" data-bs-toggle="tooltip"
                        data-bs-placement="bottom" data-bs-original-title="filter"><span
                            class="me-2"><img src="{{ URL::asset('/assets/img/icons/filter-icon.svg')}}"
                                alt="filter"></span>Filter </a>
                </li>
                @endif
                @if (Route::is(['payment-report', 'income-report']))
                    <li class="daterangepicker-wrap cal-icon cal-icon-info">
                        <input type="text" class="btn-filters" name="datetimes" placeholder="From Date - To Date" />
                    </li>
                @endif
                @if (Route::is(['tax-purchase', 'tax-sales']))
                    <li>
                        <a class="btn btn-filters w-auto popup-toggle" data-bs-toggle="tooltip"
                            data-bs-placement="bottom" title="Filter"><span class="me-2 filter-img"><img
                                    src="{{ URL::asset('/assets/img/icons/filter-icon.svg') }}" alt="filter"
                                    class="filter-img-top"></span>Filter </a>
                    </li>
                    <li class="daterangepicker-wrap cal-icon cal-icon-info">
                        <input type="text" class="btn-filters" name="datetimes" placeholder="From Date - To Date" />
                    </li>
                @endif
                @if (
                    !Route::is([
                        'signature-list',
                        'reports.*',
                        'invoices',
                        'invoices-paid',
                        'invoices-overdue',
                        'invoices-cancelled',
                        'invoices-recurring',
                        'invoices-unpaid',
                        'invoices-refunded',
                        'invoices-draft',
                        'recurring-invoices',
                        'credit-notes',
                        'purchases',
                        'purchase-orders',
                        'debit-notes',
                        'expenses',
                        'payments',
                        'quotations',
                        'delivery-challans',
                        'payment-summary',
                        'users',
                        'roles-permission',
                        'delete-account-request',
                        'membership-plans',
                        'subscribers',
                        'transactions',
                        'pages',
                        'all-blogs',
                        'inactive-blog',
                        'categories',
                        'blog-comments',
                        'countries',
                        'states',
                        'cities',
                        'testimonials',
                        'faq',
                        'contact-messages',
                        'tickets',
                        'tickets-list-open',
                        'tickets-list-resolved',
                        'tickets-list-pending',
                        'tickets-list-closed',
                        'tickets-kanban',
                        'tickets-resolved',
                        'tickets-pending',
                        'tickets-open',
                        'tickets-closed',
                        'tickets-list',
                        'ticket-details',
                      'domain',
                        'custom-filed',
                        'purchases-details',
                        'ledger',
                        'deactive-customers',
                        'active-customers',
                        'calendar',
                        'customers-ledger'
                    ]))
                    <li>
                        <div class="dropdown dropdown-action" data-bs-toggle="tooltip" data-bs-placement="bottom"
                            title="Download">
                            <a href="#" class="btn-filters" data-bs-toggle="dropdown"
                                aria-expanded="false"><span><i class="fe fe-download"></i></span></a>
                            <div class="dropdown-menu dropdown-menu-end">
                                <ul class="d-block">
                                    <li>
                                        <a class="d-flex align-items-center download-item" href="javascript:void(0);"
                                            download><i class="far fa-file-pdf me-2"></i>PDF</a>
                                    </li>
                                    <li>
                                        <a class="d-flex align-items-center download-item" href="javascript:void(0);"
                                            download><i class="far fa-file-text me-2"></i>CSV</a>
                                    </li>
                                </ul>
                            </div>
                        </div>
                    </li>
                    <li>
                        <a class="btn-filters" href="javascript:void(0);" data-bs-toggle="tooltip"
                            data-bs-placement="bottom" title="Print"><span><i class="fe fe-printer"></i></span> </a>
                    </li>
                @endif
                @if (Route::is(['customers']))
                    <li>
                        <a class="btn btn-import" href="javascript:void(0);"><span><i
                                    class="fe fe-check-square me-2"></i>Import Customer</span></a>
                    </li>
                    <li>
                        <a class="btn btn-primary" href="{{ url('add-customer') }}"><i class="fa fa-plus-circle me-2"
                                aria-hidden="true"></i>Add Customer</a>
                    </li>
                @endif
                @if (Route::is(['vendors']))
                    <li>
                        <a class="btn btn-import" href="javascript:void(0);"><span><i
                                    class="fe fe-check-square me-2"></i>Import</span></a>
                    </li>
                    <li>
                        <a class="btn btn-primary" href="javascript:void(0);" data-bs-toggle="modal"
                            data-bs-target="#add_vendor"><i class="fa fa-plus-circle me-2" aria-hidden="true"></i>Add
                            Suppliers</a>
                    </li>
                @endif
                @if (Route::is(['product-list']))
                    <li>
                        <a class="btn btn-primary" href="{{ url('add-products') }}"><i class="fa fa-plus-circle me-2"
                                aria-hidden="true"></i>Add Product</a>
                    </li>
                @endif
                @if (Route::is(['category']))
                    <li>
                        <a class="btn btn-primary" href="javascript:void(0);" data-bs-toggle="modal"
                            data-bs-target="#add_category"><i class="fa fa-plus-circle me-2" aria-hidden="true"></i>Add
                            Category</a>
                    </li>
                @endif
                @if (Route::is(['units']))
                    <li>
                        <a class="btn btn-primary" href="javascript:void(0);" data-bs-toggle="modal"
                            data-bs-target="#add_unit"><i class="fa fa-plus-circle me-2" aria-hidden="true"></i>Add
                            Unit</a>
                    </li>
                @endif
                @if (Route::is(['signature-list']))
                    <li>
                        <a class="btn btn-primary" href="javascript:void(0);" data-bs-toggle="modal"
                            data-bs-target="#add_modal"><i class="fa fa-plus-circle me-2" aria-hidden="true"></i>Add
                            Signature</a>
                    </li>
                @endif
                @if (Route::is(['invoices']))
                    <li>
                        <a class="btn-filters" href="javascript:void(0);" data-bs-toggle="tooltip"
                            data-bs-placement="bottom" title="Settings"><span><i class="fe fe-settings"></i></span>
                        </a>
                    </li>
                    <li>
                        <a class="btn btn-primary" href="{{ url('add-invoice') }}"><i class="fa fa-plus-circle me-2"
                                aria-hidden="true"></i>New Invoice</a>
                    </li>
                @endif
                @if (Route::is([
                        'invoices-paid',
                        'invoices-overdue',
                        'invoices-cancelled',
                        'invoices-recurring',
                        'invoices-unpaid',
                        'invoices-refunded',
                        'invoices-draft',
                    ]))
                    <li>
                        <a class="btn-filters" href="javascript:void(0);" data-bs-toggle="tooltip"
                            data-bs-placement="bottom" title="Settings"><span><i class="fe fe-settings"></i></span>
                        </a>
                    </li>
                    <li>
                        <a class="btn-filters" href="javascript:void(0);" data-bs-toggle="tooltip"
                            data-bs-placement="bottom" title="Grid-View"><span><i class="fe fe-grid"></i></span>
                        </a>
                    </li>
                    <li>
                        <a class="active btn-filters" href="javascript:void(0);" data-bs-toggle="tooltip"
                            data-bs-placement="bottom" title="List-View"><span><i class="fe fe-list"></i></span>
                        </a>
                    </li>
                    <li>
                        <a class="btn btn-primary" href="{{ url('add-invoice') }}"><i class="fa fa-plus-circle me-2"
                                aria-hidden="true"></i>New Invoice</a>
                    </li>
                @endif
                @if (Route::is(['recurring-invoices']))
                    <li>
                        <a class="btn-filters" href="javascript:void(0);" data-bs-toggle="tooltip"
                            data-bs-placement="bottom" data-bs-original-title="Settings"><span><i
                                    class="fe fe-settings"></i></span> </a>
                    </li>
                    <li>
                        <a class="btn btn-primary" href="{{ url('add-invoice') }}"><i class="fa fa-plus-circle me-2"
                                aria-hidden="true"></i>New Invoice</a>
                    </li>
                @endif
                @if (Route::is(['credit-notes']))
                    <li>
                        <a class="btn-filters" href="javascript:void(0);" data-bs-toggle="tooltip"
                            data-bs-placement="bottom" data-bs-original-title="Settings"><span><i
                                    class="fe fe-settings"></i></span> </a>
                    </li>
                    <li>
                        <a class="btn btn-primary" href="{{ url('add-credit-notes') }}"><i
                                class="fa fa-plus-circle me-2" aria-hidden="true"></i>Credit Notes</a>
                    </li>
                @endif
                @if (Route::is(['purchases']))
                    <li>
                        <a class="btn-filters" href="javascript:void(0);" data-bs-toggle="tooltip"
                            data-bs-placement="bottom" data-bs-original-title="Settings"><span><i
                                    class="fe fe-settings"></i></span> </a>
                    </li>

                    <li>
                        <a class="btn btn-primary" href="{{ url('add-purchases') }}"><i
                                class="fa fa-plus-circle me-2" aria-hidden="true"></i>Add Purchases</a>
                    </li>
                @endif
                @if (Route::is(['purchase-orders']))
                    <li>
                        <a class="btn-filters" href="javascript:void(0);" data-bs-toggle="tooltip"
                            data-bs-placement="bottom" data-bs-original-title="Settings"><span><i
                                    class="fe fe-settings"></i></span> </a>
                    </li>
                    <li>
                        <a class="btn btn-primary" href="{{ url('add-purchases-order') }}"><i
                                class="fa fa-plus-circle me-2" aria-hidden="true"></i>Add Purchases Order</a>
                    </li>
                @endif
                @if (Route::is(['debit-notes']))
                    <li>
                        <a class="btn-filters" href="javascript:void(0);" data-bs-toggle="tooltip"
                            data-bs-placement="bottom" title="Settings"><span><i class="fe fe-settings"></i></span>
                        </a>
                    </li>
                    <li>
                        <a class="btn btn-primary" href="{{ url('add-purchase-return') }}"><i
                                class="fa fa-plus-circle me-2" aria-hidden="true"></i>Add Purchase Returns / Debit
                            Notes</a>
                    </li>
                @endif
                @if (Route::is(['expenses']))
                    <li>
                        <a class="btn-filters" href="javascript:void(0);" data-bs-toggle="tooltip"
                            data-bs-placement="bottom" title="Settings"><span><i class="fe fe-settings"></i></span>
                        </a>
                    </li>
                    <li>
                        <a class="btn btn-primary" href="javascript:void(0);" data-bs-toggle="modal"
                            data-bs-target="#add_expenses"><i class="fa fa-plus-circle me-2"
                                aria-hidden="true"></i>Add Expenses</a>
                    </li>
                @endif
                @if (Route::is(['payments']))
                    <li>
                        <a class="btn-filters" href="javascript:void(0);" data-bs-toggle="tooltip"
                            data-bs-placement="bottom" data-bs-original-title="Grid-View"><span><i
                                    class="fe fe-grid"></i></span> </a>
                    </li>
                    <li>
                        <a class="active btn-filters" href="javascript:void(0);" data-bs-toggle="tooltip"
                            data-bs-placement="bottom" data-bs-original-title="List-View"><span><i
                                    class="fe fe-list"></i></span> </a>
                    </li>
                @endif
                @if (Route::is(['quotations']))
                    <li>
                        <a class="btn-filters" href="javascript:void(0);" data-bs-toggle="tooltip"
                            data-bs-placement="bottom" title="Settings"><span><i class="fe fe-settings"></i></span>
                        </a>
                    </li>
                    <li>
                        <a class="btn btn-primary" href="{{ url('add-quotations') }}"><i
                                class="fa fa-plus-circle me-2" aria-hidden="true"></i>Create Quotation</a>
                    </li>
                @endif
                @if (Route::is(['delivery-challans']))
                    <li>
                        <a class="btn-filters" href="javascript:void(0);" data-bs-toggle="tooltip"
                            data-bs-placement="bottom" title="Settings"><span><i class="fe fe-settings"></i></span>
                        </a>
                    </li>
                    <li>
                        <a class="btn btn-primary" href="{{ url('add-delivery-challans') }}"><i
                                class="fa fa-plus-circle me-2" aria-hidden="true"></i>Create Delivery Challan</a>
                    </li>
                @endif
                @if (Route::is(['payment-summary']))
                    <li>
                        <a class="btn-filters" href="javascript:void(0);" data-bs-toggle="tooltip"
                            data-bs-placement="bottom" data-bs-original-title="setting"><span><i
                                    class="fe fe-settings"></i></span> </a>
                    </li>
                    <li>
                        <a class="btn-filters" href="javascript:void(0);" data-bs-toggle="tooltip"
                            data-bs-placement="bottom" data-bs-original-title="grid-view"><span><i
                                    class="fe fe-grid"></i></span> </a>
                    </li>
                    <li>
                        <a class="active btn-filters" href="javascript:void(0);" data-bs-toggle="tooltip"
                            data-bs-placement="bottom" data-bs-original-title="list-view"><span><i
                                    class="fe fe-list"></i></span> </a>
                    </li>
                @endif
                @if (Route::is(['users']))
                    <li>
                        <a class="btn btn-primary" href="javascript:void(0);" data-bs-toggle="modal"
                            data-bs-target="#add_user"><i class="fa fa-plus-circle me-2" aria-hidden="true"></i>Add
                            user</a>
                    </li>
                @endif
                @if (Route::is(['roles-permission']))
                    <li>
                        <a class="btn btn-primary" href="#" data-bs-toggle="modal"
                            data-bs-target="#add_role"><i class="fa fa-plus-circle me-2" aria-hidden="true"></i>Add
                            Roles</a>
                    </li>
                @endif
                @if (Route::is(['membership-plans']))
                    <li>
                        <a class="btn btn-primary" href="javascript:void(0);" data-bs-toggle="modal"
                            data-bs-target="#add_membership"><i class="fa fa-plus-circle me-2"
                                aria-hidden="true"></i>Add Membership</a>
                    </li>
                @endif
                @if (Route::is(['pages']))
                    <li>
                        <a class="btn-filters" href="javascript:void(0);" data-bs-toggle="tooltip"
                            data-bs-placement="bottom" title="Settings"><span><i class="fe fe-settings"></i></span>
                        </a>
                    </li>
                    <li>
                        <a class="btn btn-primary" href="javascript:void(0);" data-bs-toggle="modal"
                            data-bs-target="#add_page"><i class="fa fa-plus-circle me-2" aria-hidden="true"></i>Add
                            Pages</a>
                    </li>
                @endif
                @if (Route::is(['all-blogs']))
                    <li>
                        <a class="btn-filters" href="javascript:void(0);" data-bs-toggle="tooltip"
                            data-bs-placement="bottom" data-bs-original-title="Settings"><span><i
                                    class="fe fe-settings"></i></span> </a>
                    </li>
                    <li>
                        <a class="btn btn-primary" href="javascript:void(0);" data-bs-toggle="modal"
                            data-bs-target="#add_blog"><i class="fa fa-plus-circle me-2" aria-hidden="true"></i>Add
                            Blog</a>
                    </li>
                @endif
                @if (Route::is(['inactive-blog']))
                    <li>
                        <a class="btn-filters" href="javascript:void(0);" data-bs-toggle="tooltip"
                            data-bs-placement="bottom" data-bs-original-title="Settings"><span><i
                                    class="fe fe-settings"></i></span> </a>
                    </li>
                    <li>
                        <a class="btn-filters" href="javascript:void(0);" data-bs-toggle="tooltip"
                            data-bs-placement="bottom" data-bs-original-title="Grid-View"><span><i
                                    class="fe fe-grid"></i></span> </a>
                    </li>
                    <li>
                        <a class="active btn-filters" href="javascript:void(0);" data-bs-toggle="tooltip"
                            data-bs-placement="bottom" data-bs-original-title="List-View"><span><i
                                    class="fe fe-list"></i></span> </a>
                    </li>
                    <li>
                        <a class="btn btn-primary" href="javascript:void(0);" data-bs-toggle="modal"
                        data-bs-target="#add_blog"><i class="fa fa-plus-circle me-2"
                                aria-hidden="true"></i>Add Blog</a>
                    </li>
                @endif
                @if (Route::is(['categories']))
                    <li>
                        <a class="btn-filters" href="javascript:void(0);" data-bs-toggle="tooltip"
                            data-bs-placement="bottom" data-bs-original-title="Settings"><span><i
                                    class="fe fe-settings"></i></span> </a>
                    </li>
                    <li>
                        <a class="btn btn-primary" href="javascript:void(0);" data-bs-toggle="modal"
                            data-bs-target="#blog-categories"><i class="fa fa-plus-circle me-2"
                                aria-hidden="true"></i>Add Categories</a>
                    </li>
                @endif
                @if (Route::is(['blog-comments']))
                    <li>
                        <a class="btn-filters" href="javascript:void(0);" data-bs-toggle="tooltip"
                            data-bs-placement="bottom" data-bs-original-title="Grid-View"><span><i
                                    class="fe fe-grid"></i></span> </a>
                    </li>
                    <li>
                        <a class="btn-filters" href="javascript:void(0);" data-bs-toggle="tooltip"
                            data-bs-placement="bottom" data-bs-original-title="Setting"><span><i
                                    class="fe fe-settings"></i></span> </a>
                    </li>
                    <li>
                        <a class="btn-filters active" href="javascript:void(0);" data-bs-toggle="tooltip"
                            data-bs-placement="bottom" data-bs-original-title="List-View"><span><i
                                    class="fe fe-list"></i></span> </a>
                    </li>
                @endif
                @if (Route::is(['countries']))
                    <li>
                        <a class="btn-filters" href="javascript:void(0);" data-bs-toggle="tooltip"
                            data-bs-placement="bottom" data-bs-original-title="Settings"><span><i
                                    class="fe fe-settings"></i></span> </a>
                    </li>
                    <li>
                        <a class="btn btn-primary" href="javascript:void(0);" data-bs-toggle="modal"
                            data-bs-target="#add_country">Add Countries</a>
                    </li>
                @endif
                @if (Route::is(['states']))
                    <li>
                        <a class="btn-filters" href="javascript:void(0);" data-bs-toggle="tooltip"
                            data-bs-placement="bottom" data-bs-original-title="Settings"><span><i
                                    class="fe fe-settings"></i></span> </a>
                    </li>
                    <li>
                        <a class="btn btn-primary" href="javascript:void(0);" data-bs-toggle="modal"
                            data-bs-target="#add_state"><i class="fa fa-plus-circle me-2" aria-hidden="true"></i>Add
                            State</a>
                    </li>
                @endif
                @if (Route::is(['cities']))
                    <li>
                        <a class="btn-filters" href="javascript:void(0);" data-bs-toggle="tooltip"
                            data-bs-placement="bottom" data-bs-original-title="Settings"><span><i
                                    class="fe fe-settings"></i></span> </a>
                    </li>
                    <li>
                        <a class="btn btn-primary" href="javascript:void(0);" data-bs-toggle="modal"
                            data-bs-target="#add_city"><i class="fa fa-plus-circle me-2" aria-hidden="true"></i>Add
                            City</a>
                    </li>
                @endif
                @if (Route::is(['testimonials']))
                    <li>
                        <a class="btn btn-primary" href="javascript:void(0);" data-bs-toggle="modal"
                            data-bs-target="#add_testimonial"><i class="fa fa-plus-circle me-2"
                                aria-hidden="true"></i>Add Testimonials</a>
                    </li>
                @endif
                @if (Route::is(['faq']))
                    <li>
                        <a class="btn btn-primary" href="javascript:void(0);" data-bs-toggle="modal"
                            data-bs-target="#add_faq"><i class="fa fa-plus-circle me-2" aria-hidden="true"></i>Add
                            FAQ</a>
                    </li>
                @endif
                @if (Route::is(['contact-messages']))
                    <li>
                        <a class="btn-filters" href="javascript:void(0);" data-bs-toggle="tooltip"
                            data-bs-placement="bottom" data-bs-original-title="Grid-View"><span><i
                                    class="fe fe-grid"></i></span> </a>
                    </li>
                    <li>
                        <a class="active btn-filters" href="javascript:void(0);" data-bs-toggle="tooltip"
                            data-bs-placement="bottom" data-bs-original-title="List-View"><span><i
                                    class="fe fe-list"></i></span> </a>
                    </li>
                @endif
                @if (Route::is(['tickets', 'tickets-list']))
                    <li>
                        <a class="btn btn-primary" href="#" data-bs-toggle="modal"
                            data-bs-target="#add_ticket"><i class="fa fa-plus-circle me-2" aria-hidden="true"></i>New
                            Tickets</a>
                    </li>
                @endif
                @if (Route::is([
                        'tickets-list-open',
                        'tickets-list-resolved',
                        'tickets-list-pending',
                        'tickets-list-closed',
                        'tickets-kanban',
                        'tickets-resolved',
                        'tickets-pending',
                        'tickets-open',
                        'tickets-closed',
                    ]))
                    <li>
                        <a class="btn-filters" href="javascript:void(0);" data-bs-toggle="tooltip"
                            data-bs-placement="bottom" data-bs-original-title="Grid-View"><span><i
                                    class="fe fe-grid"></i></span> </a>
                    </li>
                    <li>
                        <a class="active btn-filters" href="javascript:void(0);" data-bs-toggle="tooltip"
                            data-bs-placement="bottom" data-bs-original-title="List-View"><span><i
                                    class="fe fe-list"></i></span> </a>
                    </li>
                    <li>
                        <a class="btn btn-primary" href="#" data-bs-toggle="modal"
                            data-bs-target="#add_ticket"><i class="fa fa-plus-circle me-2" aria-hidden="true"></i>New
                            Tickets</a>
                    </li>
                @endif

                @if (Route::is(['custom-filed']))
                    <li>
                        <a class="btn btn-primary" href="javascript:void(0);" data-bs-toggle="modal"
                            data-bs-target="#add_custom"><i class="fa-solid fa-plus" aria-hidden="true"></i>Add
                            New</a>
                    </li>
                @endif
                @if (Route::is(['purchases-details']))
                    <li>
                        <a class="btn btn-import me-2" href="javascript:void(0);"><span><i
                                    class="fe fe-printer me-2"></i>Print</span></a>
                    </li>
                    <li>
                        <a href="#" class="btn btn-primary" download><i class="fa fa-download me-2"
                                aria-hidden="true"></i>Download</a>
                    </li>
                @endif
                @if (Route::is(['ledger','customers-ledger']))
                    <li>
                        <a class="btn btn-primary" href="#" data-bs-toggle="modal"
                            data-bs-target="#add_ledger"><i class="fa fa-plus-circle me-2"
                                aria-hidden="true"></i>Create Ledger</a>
                    </li>
                @endif
                @if (Route::is(['deactive-customers', 'active-customers']))
                    <li>
                        <a class="active btn-filters" href="javascript:void(0);" data-bs-toggle="tooltip"
                            data-bs-placement="bottom" title="List-View"><span><i class="fe fe-list"></i></span>
                        </a>
                    </li>
                    <li>
                        <a class="btn-filters" href="javascript:void(0);" data-bs-toggle="tooltip"
                            data-bs-placement="bottom" title="Grid-View"><span><i class="fe fe-grid"></i></span>
                        </a>
                    </li>
                    <li>
                        <div class="dropdown dropdown-action" data-bs-toggle="tooltip" data-bs-placement="top"
                            title="Download">
                            <a href="#" class="btn-filters" data-bs-toggle="dropdown"
                                aria-expanded="false"><span><i class="fe fe-download"></i></span></a>
                            <div class="dropdown-menu dropdown-menu-end">
                                <ul class="d-block">
                                    <li>
                                        <a class="d-flex align-items-center download-item" href="javascript:void(0);"
                                            download><i class="far fa-file-pdf me-2"></i>PDF</a>
                                    </li>
                                    <li>
                                        <a class="d-flex align-items-center download-item" href="javascript:void(0);"
                                            download><i class="far fa-file-text me-2"></i>CSV</a>
                                    </li>
                                </ul>
                            </div>
                        </div>
                    </li>
                    <li>
                        <a class="btn-filters" href="javascript:void(0);" data-bs-toggle="tooltip"
                            data-bs-placement="bottom" title="Print"><span><i class="fe fe-printer"></i></span>
                        </a>
                    </li>
                    <li>
                        <a class="btn btn-import" href="javascript:void(0);"><span><i
                                    class="fe fe-inbox me-2"></i>Import Customer</span></a>
                    </li>
                    <li>
                        <a class="btn btn-primary" href="{{ url('add-invoice') }}"><i class="fa fa-plus-circle me-2"
                                aria-hidden="true"></i>New Invoice</a>
                    </li>
                @endif
                @if(Route::is('calendar'))
                <li>
                    <a href="#" class="btn btn-primary" data-bs-toggle="modal"
                        data-bs-target="#add_event">Create Event</a>
                </li>
                @endif
                @if(Route::is('domain'))
                <li>
                    <a class="btn-filters" href="javascript:void(0);" data-bs-toggle="tooltip"
                        data-bs-placement="bottom" title="Refresh"><span><i
                                class="fe fe-refresh-ccw"></i></span></a>
                </li>
                <li>
                    <a class="btn btn-filters w-auto popup-toggle" data-bs-toggle="tooltip"
                        data-bs-placement="bottom" title="Filter"><span class="me-2"><img
                                src="{{ URL::asset('/assets/img/icons/filter-icon.svg')}}" alt="filter"></span>Filter
                    </a>
                </li>
                <li>
                    <div class="dropdown dropdown-action" data-bs-toggle="tooltip"
                        data-bs-placement="bottom" title="Download">
                        <a href="#" class="btn btn-filters" data-bs-toggle="dropdown"
                            aria-expanded="false"><span class="me-2"><i class="fe fe-download"></i></span>Export</a>
                        <div class="dropdown-menu dropdown-menu-end">
                            <ul class="d-block">
                                <li>
                                    <a class="d-flex align-items-center download-item"
                                        href="javascript:void(0);" download><i
                                            class="far fa-file-pdf me-2"></i>Export as PDF</a>
                                </li>
                                <li>
                                    <a class="d-flex align-items-center download-item"
                                        href="javascript:void(0);" download><i
                                            class="far fa-file-text me-2"></i>Export as Excel</a>
                                </li>
                            </ul>
                        </div>
                    </div>
                </li>
                <li>
                    <a class="btn btn-filters" href="javascript:void(0);" data-bs-toggle="tooltip"
                        data-bs-placement="bottom" title="Print"><span class="me-2"><i
                                class="fe fe-printer"></i></span> Print
                    </a>
                </li>
                @endif
            </ul>
        </div>
        @if (Route::is(['custom-filed', 'profit-loss-list', 'sales-return-report', 'stock-report']))
    </div>
    @endif
</div>

</div>

@endif

@if (Route::is(['companies','subscription','plans-list','packages']))


<div class="page-header">
    <div class="content-page-header">
        <h5>{{$title}}</h5>
        <div class="page-content">
            <div class="list-btn">
                <ul class="filter-list">
                    @if(Route::is(['plans-list','packages']))
                    <li>
                        <a class="btn-filters {{ Request::is('packages') ? 'active' : '' }}" href="{{url('packages')}}" data-bs-toggle="tooltip"
                            data-bs-placement="bottom" title="Grid-View"><span><i
                                    class="fe fe-grid"></i></span></a>
                    </li>
                    <li>
                        <a class="btn-filters {{ Request::is('plans-list') ? 'active' : '' }}" href="{{url('plans-list')}}" data-bs-toggle="tooltip"
                            data-bs-placement="bottom" title="List-View"><span><i
                                    class="fe fe-list"></i></span></a>
                    </li>
                    @endif
                    <li>
                        <a class="btn-filters" href="javascript:void(0);" data-bs-toggle="tooltip"
                            data-bs-placement="bottom" title="Refresh"><span><i
                                    class="fe fe-refresh-ccw"></i></span></a>
                    </li>
                    <li>
                        <a class="btn btn-filters w-auto popup-toggle" data-bs-toggle="tooltip"
                            data-bs-placement="bottom" title="Filter"><span class="me-2"><img
                                    src="{{URL::asset('/assets/img/icons/filter-icon.svg')}}" alt="filter"></span>Filter
                        </a>
                    </li>

                    <li>
                        <div class="dropdown dropdown-action" data-bs-toggle="tooltip"
                            data-bs-placement="bottom" title="Download">
                            <a href="#" class="btn btn-filters" data-bs-toggle="dropdown"
                                aria-expanded="false"><span class="me-2"><i class="fe fe-download"></i></span>Export</a>
                            <div class="dropdown-menu dropdown-menu-end">
                                <ul class="d-block">
                                    <li>
                                        <a class="d-flex align-items-center download-item"
                                            href="javascript:void(0);" download><i
                                                class="far fa-file-pdf me-2"></i>Export as PDF</a>
                                    </li>
                                    <li>
                                        <a class="d-flex align-items-center download-item"
                                            href="javascript:void(0);" download><i
                                                class="far fa-file-text me-2"></i>Export as Excel</a>
                                    </li>
                                </ul>
                            </div>
                        </div>
                    </li>
                    <li>
                        <a class="btn btn-filters" href="javascript:void(0);" data-bs-toggle="tooltip"
                            data-bs-placement="bottom" title="Print"><span class="me-2"><i
                                    class="fe fe-printer"></i></span> Print
                        </a>
                    </li>

                    @if (Route::is(['companies']))
                    <li>
                        <a class="btn btn-primary" href="#" data-bs-toggle="modal" data-bs-target="#add_companies"><i class="fa fa-plus-circle me-2" aria-hidden="true"></i>Add Company</a>
                    </li>
                    @endif
                    @if (Route::is(['plans-list']))
                    <li>
                        <a class="btn btn-primary" href="#" data-bs-toggle="modal" data-bs-target="#add_newpackage"><i class="fa fa-plus-circle me-2" aria-hidden="true"></i>Add Plan</a>
                    </li>
                    @endif
                    @if(Route::is(['packages']))
                    <li>
                        <a class="btn btn-primary" href="#" data-bs-toggle="modal" data-bs-target="#add_newpackage"><i class="fa fa-plus-circle me-2" aria-hidden="true"></i>Add Plan</a>
                    </li>
                    @endif
                </ul>
            </div>
        </div>
    </div>
</div>

@endif
