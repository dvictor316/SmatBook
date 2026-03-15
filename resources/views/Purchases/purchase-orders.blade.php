<?php $page = 'purchases-orders'; ?>
@extends('layout.mainlayout')
@section('content')
    <style>
        .vendor-order-avatar {
            width: 38px;
            height: 38px;
            border-radius: 50%;
            overflow: hidden;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            background: linear-gradient(135deg, #dbeafe 0%, #eff6ff 100%);
            color: #1d4ed8;
            font-weight: 800;
            font-size: 0.85rem;
            border: 1px solid #bfdbfe;
            flex-shrink: 0;
        }
        .vendor-order-avatar img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            display: block;
        }
        .vendor-order-avatar-fallback {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 100%;
            height: 100%;
        }
    </style>
    <div class="page-wrapper">
        <div class="content container-fluid">

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
                                            <th>Vendor</th>
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
                                                        $vendorName = $order['Vendor'] ?? 'Unknown Vendor';
                                                        $vendorInitials = \Illuminate\Support\Str::of($vendorName)
                                                            ->explode(' ')
                                                            ->filter()
                                                            ->take(2)
                                                            ->map(fn ($part) => \Illuminate\Support\Str::upper(\Illuminate\Support\Str::substr($part, 0, 1)))
                                                            ->implode('');
                                                        $vendorImage = trim((string) ($order['Image'] ?? ''));
                                                        $vendorImageUrl = $vendorImage !== ''
                                                            ? asset('assets/img/profiles/' . ltrim($vendorImage, '/'))
                                                            : null;
                                                    @endphp
                                                    <h2 class="table-avatar">
                                                        <a href="{{ url('profile') }}" class="vendor-order-avatar me-2">
                                                            @if($vendorImageUrl)
                                                                <img src="{{ $vendorImageUrl }}"
                                                                    alt="{{ $vendorName }}"
                                                                    onerror="this.style.display='none'; this.nextElementSibling.style.display='inline-flex';">
                                                            @endif
                                                            <span class="vendor-order-avatar-fallback" @if($vendorImageUrl) style="display:none;" @endif>
                                                                {{ $vendorInitials !== '' ? $vendorInitials : 'VN' }}
                                                            </span>
                                                        </a>
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
