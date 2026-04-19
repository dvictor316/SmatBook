<?php $page = 'purchases-orders'; ?>
@extends('layout.mainlayout')
@section('content')
    <style>
        .purchase-orders-page .card-table {
            border: 1px solid #e8eef8;
            border-radius: 18px;
            overflow: hidden;
            box-shadow: 0 12px 28px rgba(15, 23, 42, 0.05);
            background: #fff;
        }

        .purchase-orders-page .card-body {
            padding: 0.9rem !important;
        }

        .purchase-orders-page .table {
            margin-bottom: 0;
        }

        .purchase-orders-page .table thead th {
            font-size: 0.72rem !important;
            font-weight: 800 !important;
            letter-spacing: 0.06em;
            text-transform: uppercase;
            color: #64748b;
            padding: 0.9rem 0.8rem !important;
            white-space: nowrap;
        }

        .purchase-orders-page .table tbody td {
            font-size: 0.82rem !important;
            color: #243b63;
            padding: 0.95rem 0.8rem !important;
            vertical-align: middle;
        }

        .purchase-orders-page .table-avatar {
            margin: 0;
            line-height: 1.2;
        }

        .purchase-orders-page .table-avatar a {
            color: #334155;
        }

        .purchase-orders-page .table-avatar span {
            display: block;
            margin-top: 0.18rem;
            font-size: 12px !important;
            line-height: 1.2;
            color: #7c8799;
            font-weight: 500;
        }

        .purchase-orders-page .btn-action-icon {
            font-size: 0.85rem !important;
        }

        .purchase-orders-page .dropdown-menu .dropdown-item {
            font-size: 0.82rem !important;
        }

        .purchase-orders-page .badge,
        .purchase-orders-page span.badge,
        .purchase-orders-page [class*="badge bg-"] {
            font-size: 0.7rem !important;
            font-weight: 700;
            padding: 0.38rem 0.58rem;
        }

        .purchase-orders-page .dataTables_length label,
        .purchase-orders-page .dataTables_filter label,
        .purchase-orders-page .dataTables_info,
        .purchase-orders-page .pagination {
            font-size: 0.82rem !important;
        }

        .purchase-orders-page .dataTables_length select,
        .purchase-orders-page .dataTables_filter input {
            font-size: 0.82rem !important;
            min-height: 38px;
        }

        @media (max-width: 767.98px) {
            .purchase-orders-page .table thead th {
                font-size: 0.66rem !important;
                padding: 0.75rem 0.65rem !important;
            }

            .purchase-orders-page .table tbody td {
                font-size: 0.76rem !important;
                padding: 0.8rem 0.65rem !important;
            }

            .purchase-orders-page .table-avatar {
                font-size: 0.84rem !important;
            }

            .purchase-orders-page .table-avatar span {
                font-size: 0.72rem !important;
            }
        }
    </style>
    <div class="page-wrapper">
        <div class="content container-fluid purchase-orders-page">

            @component('components.page-header')
                @slot('title')
                    Purchase Orders
                @endslot
            @endcomponent
            @component('components.search-filter')
            @endcomponent
            <div class="row">
                <div class="col-sm-12">
                    <div class="card-table">
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-stripped table-hover datatable">
                                    <thead class="thead-light">
                                        <tr>
                                            <th>#</th>
                                            <th>Purchase ID</th>
                                            <th>Supplier</th>
                                            <th>Amount</th>
                                            <th>Payment Mode</th>
                                            <th>Date</th>
                                            <th>Status</th>
                                            <th class="no-sort">Action</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @forelse ($purchase_orders as $order)
                                            <tr>
                                                <td>{{ $order['Id'] ?? $loop->iteration }}</td>
                                                <td>{{ $order['PurchaseID'] ?? 'N/A' }}</td>
                                                <td>
                                                    @php
                                                        $vendorName = $order['Vendor'] ?? 'Unknown Supplier';
                                                    @endphp
                                                    <h2 class="table-avatar">
                                                        <a href="{{ url('profile') }}">
                                                            {{ $vendorName }}
                                                            <span>{{ $order['Phone'] ?? '' }}</span>
                                                        </a>
                                                    </h2>
                                                </td>
                                                <td>{{ $order['Amount'] ?? '0.00' }}</td>
                                                <td>{{ $order['PaymentMode'] ?? 'N/A' }}</td>
                                                <td>{{ $order['Date'] ?? 'N/A' }}</td>
                                                <td>
                                                    <span class="{{ $order['Class'] ?? 'badge bg-secondary' }}">
                                                        {{ $order['Status'] ?? 'Pending' }}
                                                    </span>
                                                </td>
                                                <td class="d-flex align-items-center">
                                                    <div class="dropdown dropdown-action">
                                                        <a href="#" class="btn-action-icon" data-bs-toggle="dropdown" aria-expanded="false">
                                                            <i class="fas fa-ellipsis-v"></i>
                                                        </a>
                                                        <div class="dropdown-menu dropdown-menu-right credit-note-dropdown">
                                                            <ul>
                                                                <li>
                                                                    <a class="dropdown-item" href="{{ route('edit-purchases-order', $order['Id']) }}">
                                                                        <i class="far fa-edit me-2"></i>Edit
                                                                    </a>
                                                                </li>
                                                                <li>
                                                                    <a class="dropdown-item" href="javascript:void(0);" data-bs-toggle="modal" data-bs-target="#delete_modal">
                                                                        <i class="far fa-trash-alt me-2"></i>Delete
                                                                    </a>
                                                                </li>
                                                                <li>
                                                                    <a class="dropdown-item" href="{{ route('purchase-details', $order['Id']) }}">
                                                                        <i class="far fa-eye me-2"></i>View
                                                                    </a>
                                                                </li>
                                                                <li>
                                                                    <a class="dropdown-item" href="{{ url('add-purchase-return') }}">
                                                                        <i class="fe fe-repeat me-2"></i>Convert To Purchase
                                                                    </a>
                                                                </li>
                                                            </ul>
                                                        </div>
                                                    </div>
                                                </td>
                                            </tr>
                                        @empty
                                            <tr>
                                                <td colspan="8" class="text-center">No purchase orders found.</td>
                                            </tr>
                                        @endforelse
                                    </tbody>
                                </table>
                            </div>
                            @if(isset($orders) && method_exists($orders, 'links'))
                                <div class="mt-3">
                                    {{ $orders->links() }}
                                </div>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
            </div>
    </div>
    <div class="modal custom-modal fade" id="delete_modal" role="dialog">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-body">
                    <div class="form-header">
                        <h3>Delete Purchase Order</h3>
                        <p>Are you sure want to delete?</p>
                    </div>
                    <div class="modal-btn delete-action">
                        <div class="row">
                            <div class="col-6">
                                <a href="javascript:void(0);" class="btn btn-primary paid-continue-btn">Delete</a>
                            </div>
                            <div class="col-6">
                                <a href="javascript:void(0);" data-bs-dismiss="modal" class="btn btn-primary paid-cancel-btn">Cancel</a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <script>
        function printPage() {
            window.print();
        }
    </script>
@endsection
