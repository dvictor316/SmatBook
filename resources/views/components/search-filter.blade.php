@php
    $routeName = Route::currentRouteName();
    $query = request()->query();
    $hasActiveFilters = collect($query)->contains(fn ($value) => filled($value));

    $configs = [
        'customers.index' => [
            'mode' => 'server',
            'route_name' => 'customers.index',
            'heading' => 'Refine customers',
            'description' => 'Search by customer name, email, or phone and narrow the list by status.',
            'fields' => ['search', 'status'],
            'search_placeholder' => 'Search name, email, or phone',
            'status_options' => [
                '' => 'All statuses',
                'active' => 'Active',
                'deactive' => 'Inactive',
            ],
        ],
        'active-customers' => [
            'mode' => 'server',
            'route_name' => 'active-customers',
            'heading' => 'Refine active customers',
            'description' => 'Search within active customers without losing the active-only view.',
            'fields' => ['search'],
            'search_placeholder' => 'Search active customers',
            'scope_badge' => 'Active only',
        ],
        'deactive-customers' => [
            'mode' => 'server',
            'route_name' => 'deactive-customers',
            'heading' => 'Refine inactive customers',
            'description' => 'Search within inactive customers and keep the current scope locked.',
            'fields' => ['search'],
            'search_placeholder' => 'Search inactive customers',
            'scope_badge' => 'Inactive only',
        ],
        'vendors.index' => [
            'mode' => 'server',
            'route_name' => 'vendors.index',
            'heading' => 'Find vendors quickly',
            'description' => 'Search vendor names, email addresses, phone numbers, and addresses.',
            'fields' => ['search'],
            'search_placeholder' => 'Search name, email, phone, or address',
        ],
        'suppliers.index' => [
            'mode' => 'server',
            'route_name' => 'suppliers.index',
            'heading' => 'Find suppliers quickly',
            'description' => 'Search supplier names, email addresses, phone numbers, and addresses.',
            'fields' => ['search'],
            'search_placeholder' => 'Search name, email, phone, or address',
        ],
        'invoices.index' => [
            'mode' => 'server',
            'route_name' => 'invoices.index',
            'heading' => 'Filter invoice activity',
            'description' => 'Search invoice numbers or customers, then narrow by payment status or date range.',
            'fields' => ['search', 'status', 'date_from', 'date_to'],
            'search_placeholder' => 'Search invoice number or customer',
            'status_options' => [
                '' => 'All statuses',
                'paid' => 'Paid',
                'unpaid' => 'Unpaid',
                'partial' => 'Partial',
                'pending' => 'Pending',
                'cancelled' => 'Cancelled',
                'overdue' => 'Overdue',
                'draft' => 'Draft',
                'refunded' => 'Refunded',
            ],
        ],
        'invoices-paid' => [
            'mode' => 'server',
            'route_name' => 'invoices-paid',
            'heading' => 'Filter paid invoices',
            'description' => 'Search paid invoices by number or customer and narrow by date range.',
            'fields' => ['search', 'date_from', 'date_to'],
            'search_placeholder' => 'Search paid invoices',
            'scope_badge' => 'Paid only',
        ],
        'invoices-unpaid' => [
            'mode' => 'server',
            'route_name' => 'invoices-unpaid',
            'heading' => 'Filter unpaid invoices',
            'description' => 'Search unpaid invoices by number or customer and narrow by date range.',
            'fields' => ['search', 'date_from', 'date_to'],
            'search_placeholder' => 'Search unpaid invoices',
            'scope_badge' => 'Unpaid only',
        ],
        'invoices-cancelled' => [
            'mode' => 'server',
            'route_name' => 'invoices-cancelled',
            'heading' => 'Filter cancelled invoices',
            'description' => 'Review cancelled invoices with a focused search and date range.',
            'fields' => ['search', 'date_from', 'date_to'],
            'search_placeholder' => 'Search cancelled invoices',
            'scope_badge' => 'Cancelled only',
        ],
        'invoices-draft' => [
            'mode' => 'server',
            'route_name' => 'invoices-draft',
            'heading' => 'Filter draft invoices',
            'description' => 'Track draft invoices quickly with search and date controls.',
            'fields' => ['search', 'date_from', 'date_to'],
            'search_placeholder' => 'Search draft invoices',
            'scope_badge' => 'Draft only',
        ],
        'invoices-overdue' => [
            'mode' => 'server',
            'route_name' => 'invoices-overdue',
            'heading' => 'Filter overdue invoices',
            'description' => 'Review overdue invoices by customer, invoice number, and date range.',
            'fields' => ['search', 'date_from', 'date_to'],
            'search_placeholder' => 'Search overdue invoices',
            'scope_badge' => 'Overdue only',
        ],
        'invoices-refunded' => [
            'mode' => 'server',
            'route_name' => 'invoices-refunded',
            'heading' => 'Filter refunded invoices',
            'description' => 'Search refunded invoices cleanly and narrow the view by date.',
            'fields' => ['search', 'date_from', 'date_to'],
            'search_placeholder' => 'Search refunded invoices',
            'scope_badge' => 'Refunded only',
        ],
        'pos.reports' => [
            'mode' => 'server',
            'route_name' => 'pos.reports',
            'heading' => 'Filter POS sales activity',
            'description' => 'Filter by branch, staff, payment status, and date range.',
            'fields' => ['search', 'branch_id', 'staff_id', 'payment_status', 'date_from', 'date_to'],
            'search_placeholder' => 'Search product, SKU, or category',
            'branch_options' => $branchOptions ?? [],
            'staff_options' => $staffOptions ?? [],
            'payment_status_options' => [
                '' => 'All payment statuses',
                'paid' => 'Paid',
                'partial' => 'Partial',
                'unpaid' => 'Unpaid',
                'pending' => 'Pending',
            ],
        ],
    ];

    $config = $configs[$routeName] ?? [
        'mode' => 'client',
        'action' => url()->current(),
        'heading' => 'Filter this page',
        'description' => 'Search the visible results instantly on this page.',
        'fields' => ['search'],
        'search_placeholder' => 'Search visible results',
    ];

    $action = $config['action'] ?? (
        !empty($config['route_name']) && Route::has($config['route_name'])
            ? route($config['route_name'])
            : url()->current()
    );

    $searchValue = trim((string) request('search', ''));
    $statusValue = trim((string) request('status', ''));
    $branchValue = trim((string) request('branch_id', ''));
    $staffValue = trim((string) request('staff_id', ''));
    $paymentStatusValue = trim((string) request('payment_status', ''));
    $dateFrom = trim((string) request('date_from', ''));
    $dateTo = trim((string) request('date_to', ''));
@endphp

@once
    <style>
        .smart-filter-card {
            border: 1px solid rgba(18, 38, 63, 0.08);
            border-radius: 22px;
            box-shadow: 0 18px 40px rgba(15, 23, 42, 0.06);
            overflow: hidden;
        }

        .smart-filter-card .card-body {
            padding: 1.35rem 1.35rem 1rem;
        }

        .smart-filter-header {
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            gap: 1rem;
            margin-bottom: 1rem;
        }

        .smart-filter-title {
            margin: 0;
            color: #0f2d5c;
            font-size: 1.05rem;
            font-weight: 700;
            line-height: 1.2;
        }

        .smart-filter-copy {
            margin: 0.35rem 0 0;
            color: #64748b;
            font-size: 0.92rem;
        }

        .smart-filter-badge {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            padding: 0.45rem 0.75rem;
            border-radius: 999px;
            background: rgba(79, 70, 229, 0.08);
            color: #4f46e5;
            font-size: 0.82rem;
            font-weight: 700;
            white-space: nowrap;
        }

        .smart-filter-grid {
            display: grid;
            grid-template-columns: minmax(0, 1.8fr) repeat(3, minmax(0, 1fr));
            gap: 0.9rem;
            align-items: end;
        }

        .smart-filter-field label {
            display: block;
            margin-bottom: 0.45rem;
            color: #334155;
            font-size: 0.82rem;
            font-weight: 700;
            letter-spacing: 0.01em;
        }

        .smart-filter-field .form-control,
        .smart-filter-field .form-select {
            min-height: 46px;
            border-radius: 14px;
            border-color: rgba(148, 163, 184, 0.35);
            box-shadow: none;
        }

        .smart-filter-field .form-control:focus,
        .smart-filter-field .form-select:focus {
            border-color: rgba(79, 70, 229, 0.45);
            box-shadow: 0 0 0 4px rgba(79, 70, 229, 0.08);
        }

        .smart-filter-actions {
            display: flex;
            gap: 0.75rem;
            align-items: center;
            justify-content: flex-end;
        }

        .smart-filter-actions .btn {
            min-height: 46px;
            border-radius: 14px;
            padding-inline: 1rem;
            font-weight: 600;
        }

        .smart-filter-actions .btn-primary {
            background: linear-gradient(135deg, #274cbb 0%, #4f46e5 100%);
            border-color: transparent;
        }

        .smart-filter-actions .btn-light {
            border: 1px solid rgba(148, 163, 184, 0.32);
            background: #fff;
            color: #334155;
        }

        @media (max-width: 991.98px) {
            .smart-filter-grid {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }

            .smart-filter-actions {
                justify-content: stretch;
                grid-column: 1 / -1;
            }

            .smart-filter-actions .btn,
            .smart-filter-actions a {
                flex: 1 1 0;
            }
        }

        @media (max-width: 575.98px) {
            .smart-filter-card .card-body {
                padding: 1rem;
            }

            .smart-filter-header {
                flex-direction: column;
            }

            .smart-filter-grid {
                grid-template-columns: 1fr;
            }

            .smart-filter-actions {
                flex-direction: column;
            }

            .smart-filter-actions .btn,
            .smart-filter-actions a {
                width: 100%;
            }
        }
    </style>

    @push('scripts')
        <script>
            document.addEventListener('DOMContentLoaded', function () {
                const filterForms = document.querySelectorAll('[data-smart-filter-form]');

                const applyClientFilter = function (form) {
                    const search = (form.querySelector('[name="search"]')?.value || '').trim().toLowerCase();
                    const status = (form.querySelector('[name="status"]')?.value || '').trim().toLowerCase();
                    const table = document.querySelector('.table');

                    if (!table) {
                        return;
                    }

                    const rows = table.querySelectorAll('tbody tr');

                    rows.forEach((row) => {
                        const rowText = row.textContent.toLowerCase().replace(/\s+/g, ' ').trim();
                        const badgeText = Array.from(row.querySelectorAll('.badge'))
                            .map((badge) => badge.textContent.toLowerCase().trim())
                            .join(' ');

                        const matchesSearch = !search || rowText.includes(search);
                        const matchesStatus = !status || badgeText.includes(status) || rowText.includes(status);

                        row.style.display = matchesSearch && matchesStatus ? '' : 'none';
                    });
                };

                filterForms.forEach((form) => {
                    if (form.dataset.filterMode !== 'client') {
                        return;
                    }

                    form.addEventListener('submit', function (event) {
                        event.preventDefault();
                        applyClientFilter(form);
                    });

                    const searchField = form.querySelector('[name="search"]');
                    const statusField = form.querySelector('[name="status"]');
                    const resetButton = form.querySelector('[data-smart-filter-reset]');

                    if (searchField) {
                        searchField.addEventListener('input', function () {
                            applyClientFilter(form);
                        });
                    }

                    if (statusField) {
                        statusField.addEventListener('change', function () {
                            applyClientFilter(form);
                        });
                    }

                    if (resetButton) {
                        resetButton.addEventListener('click', function () {
                            if (searchField) {
                                searchField.value = '';
                            }

                            if (statusField) {
                                statusField.value = '';
                            }

                            applyClientFilter(form);
                        });
                    }
                });
            });
        </script>
    @endpush
@endonce

<div class="card smart-filter-card mb-4">
    <div class="card-body">
        <div class="smart-filter-header">
            <div>
                <h4 class="smart-filter-title">{{ $config['heading'] }}</h4>
                <p class="smart-filter-copy">{{ $config['description'] }}</p>
            </div>

            @if(!empty($config['scope_badge']))
                <span class="smart-filter-badge">{{ $config['scope_badge'] }}</span>
            @endif
        </div>

        <form
            method="GET"
            action="{{ $action }}"
            data-smart-filter-form
            data-filter-mode="{{ $config['mode'] }}"
        >
            <div class="smart-filter-grid">
                @if(in_array('search', $config['fields'], true))
                    <div class="smart-filter-field">
                        <label for="smart-filter-search">Search</label>
                        <input
                            id="smart-filter-search"
                            type="text"
                            name="search"
                            class="form-control"
                            value="{{ $searchValue }}"
                            placeholder="{{ $config['search_placeholder'] }}"
                        >
                    </div>
                @endif

                @if(in_array('status', $config['fields'], true))
                    <div class="smart-filter-field">
                        <label for="smart-filter-status">Status</label>
                        <select id="smart-filter-status" name="status" class="form-select">
                            @foreach(($config['status_options'] ?? []) as $value => $label)
                                <option value="{{ $value }}" @selected($statusValue === (string) $value)>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                @endif

                @if(in_array('branch_id', $config['fields'], true))
                    <div class="smart-filter-field">
                        <label for="smart-filter-branch">Branch</label>
                        <select id="smart-filter-branch" name="branch_id" class="form-select">
                            <option value="">All branches</option>
                            @foreach(($config['branch_options'] ?? []) as $value => $label)
                                <option value="{{ $value }}" @selected($branchValue === (string) $value)>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                @endif

                @if(in_array('staff_id', $config['fields'], true))
                    <div class="smart-filter-field">
                        <label for="smart-filter-staff">Staff</label>
                        <select id="smart-filter-staff" name="staff_id" class="form-select">
                            <option value="">All staff</option>
                            @foreach(($config['staff_options'] ?? []) as $value => $label)
                                <option value="{{ $value }}" @selected($staffValue === (string) $value)>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                @endif

                @if(in_array('payment_status', $config['fields'], true))
                    <div class="smart-filter-field">
                        <label for="smart-filter-payment-status">Payment Status</label>
                        <select id="smart-filter-payment-status" name="payment_status" class="form-select">
                            @foreach(($config['payment_status_options'] ?? []) as $value => $label)
                                <option value="{{ $value }}" @selected($paymentStatusValue === (string) $value)>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                @endif

                @if(in_array('date_from', $config['fields'], true))
                    <div class="smart-filter-field">
                        <label for="smart-filter-date-from">From</label>
                        <input
                            id="smart-filter-date-from"
                            type="date"
                            name="date_from"
                            class="form-control"
                            value="{{ $dateFrom }}"
                        >
                    </div>
                @endif

                @if(in_array('date_to', $config['fields'], true))
                    <div class="smart-filter-field">
                        <label for="smart-filter-date-to">To</label>
                        <input
                            id="smart-filter-date-to"
                            type="date"
                            name="date_to"
                            class="form-control"
                            value="{{ $dateTo }}"
                        >
                    </div>
                @endif

                <div class="smart-filter-actions">
                    <button type="submit" class="btn btn-primary">
                        <i class="fa-solid fa-filter me-2"></i>Apply
                    </button>

                    @if($config['mode'] === 'server')
                        <a href="{{ $action }}" class="btn btn-light">
                            <i class="fa-solid fa-rotate me-2"></i>{{ $hasActiveFilters ? 'Clear Filters' : 'Reset' }}
                        </a>
                    @else
                        <button type="button" class="btn btn-light" data-smart-filter-reset>
                            <i class="fa-solid fa-rotate me-2"></i>{{ $hasActiveFilters ? 'Clear Filters' : 'Reset' }}
                        </button>
                    @endif
                </div>
            </div>
        </form>
    </div>
</div>
