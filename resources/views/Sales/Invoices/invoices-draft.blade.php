<?php $page = 'invoices-draft'; ?>
@extends('layout.mainlayout')
@section('content')
    <!-- Page Wrapper -->
    <div class="page-wrapper">
        <div class="content container-fluid">

            <!-- Page Header -->
            @component('components.page-header')
                @slot('title')
                    Invoices Draft
                @endslot
            @endcomponent
            <!-- /Page Header -->

            <!-- Search Filter -->
            @component('components.search-filter')
            @endcomponent
            <!-- /Search Filter -->

            <!-- Inovices card -->
            @component('components.invoices-card')
            @endcomponent
            <!-- /Inovices card -->

            <!-- All Invoice -->
            @component('components.invoices-tab')
            @endcomponent
            <!-- /All Invoice -->

            <!-- Table -->
            <div class="row">
                <div class="col-sm-12">
                    <div class="card-table">
                        <div class="card-body">
                            <div class="table-responsive no-pagination">
                                <table class="table table-stripped table-hover datatable">
                                    <thead class="thead-light">
                                        <tr>
                                            <th>
                                                <label class="custom_check">
                                                    <input type="checkbox" name="invoice">
                                                    <span class="checkmark"></span>
                                                </label>Invoice ID
                                            </th>
                                            <th>Category</th>
                                            <th>Created On</th>
                                            <th>Invoice To</th>
                                            <th>Total</th>
                                            <th>Paid</th>
                                            <th>Payment Mode</th>
                                            <th>Balance</th>
                                            <th>Due Date</th>
                                            <th>Status</th>
                                            <th class="text-end">Action</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @php
                                            $json = file_get_contents(public_path('../public/assets/json/invoices-draft.json'));
                                            $invoices = json_decode($json, true);
                                        @endphp
                                        @foreach ($invoices as $invoice)
                                            <tr>
                                                <td>
                                                    <label class="custom_check">
                                                        <input type="checkbox" name="invoice">
                                                        <span class="checkmark"></span>
                                                    </label>
                                                    <a href="{{ url('invoice-details') }}"
                                                        class="invoice-link">{{ $invoice['InvoiceID'] }}</a>
                                                </td>
                                                <td>{{ $invoice['Category'] }}</td>
                                                <td>{{ $invoice['CreatedOn'] }}</td>
                                                <td>
                                                    <h2 class="table-avatar">
                                                        <a href="{{ url('profile') }}" class="avatar avatar-sm me-2"><img
                                                                class="avatar-img rounded-circle"
                                                                src="{{ URL::asset('assets/img/profiles/' . $invoice['Image']) }}"
                                                                alt="User Image"></a>
                                                        <a href="{{ url('profile') }}">{{ $invoice['InvoiceTo'] }}
                                                            <span>{{ $invoice['Phone'] }}</span></a>
                                                    </h2>
                                                </td>
                                                <td>{{ $invoice['Total'] }}</td>
                                                <td>{{ $invoice['Paid'] }}</td>
                                                <td>{{ $invoice['PaymentMode'] }}</td>
                                                <td>{{ $invoice['Balance'] }}</td>
                                                <td>{{ $invoice['DueDate'] }}</td>
                                                <td><span class="{{ $invoice['Class'] }}">{{ $invoice['Status'] }}</span>
                                                </td>
                                                <td class="text-end">
                                                    <div class="dropdown dropdown-action">
                                                        <a href="#" class="btn-action-icon" data-bs-toggle="dropdown"
                                                            aria-expanded="false"><i class="fas fa-ellipsis-v"></i></a>
                                                        <div class="dropdown-menu dropdown-menu-end customer-dropdown">
                                                            <a class="dropdown-item" href="{{ url('edit-invoice') }}"><i
                                                                    class="far fa-edit me-2"></i>Edit</a>
                                                            <a class="dropdown-item" href="{{ url('invoice-details') }}"><i
                                                                    class="far fa-eye me-2"></i>View</a>
                                                            <a class="dropdown-item" href="javascript:void(0);"
                                                                data-bs-toggle="modal" data-bs-target="#delete_modal"><i
                                                                    class="far fa-trash-alt me-2"></i>Delete</a>
                                                            <a class="dropdown-item" href=""><i
                                                                    class="fe fe-send me-2"></i>Send</a>
                                                            <a class="dropdown-item" href=""><i
                                                                    class="fe fe-download me-2"></i>Download</a>
                                                            <a class="dropdown-item"
                                                                href="{{ url('add-credit-notes') }}"><i
                                                                    class="fe fe-file-text me-2"></i>Convert to Sales
                                                                Return</a>
                                                            <a class="dropdown-item" href=""><i
                                                                    class="fe fe-copy me-2"></i>Clone as Invoice</a>
                                                        </div>
                                                    </div>
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <!-- /Table -->

        </div>
    </div>
    <!-- /Page Wrapper -->
@endsection
