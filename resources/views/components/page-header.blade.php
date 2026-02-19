<!-- Page Header -->
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
                        'purchase-transaction'
                    ]))
                    <li>
                        <a class="btn btn-filters w-auto popup-toggle" data-bs-toggle="tooltip"
                            data-bs-placement="bottom" title="Filter"><span class="me-2"><img
                                    src="{{ URL::asset('/assets/img/icons/filter-icon.svg') }}"
                                    alt="filter"></span>Filter
                        </a>
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
                            Vendors</a>
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
<!-- /Page Header -->
@endif

@if (Route::is(['companies','subscription','plans-list','packages']))
    
<!-- Page Header -->
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
<!-- /Page Header -->
@endif
