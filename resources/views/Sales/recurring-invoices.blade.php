<?php $page = 'recurring-invoices'; ?>
@extends('layout.mainlayout')

@section('content')
    <div class="page-wrapper">
        <div class="content container-fluid">

            @component('components.page-header')
                @slot('title') Recurring Invoices @endslot
            @endcomponent

            @component('components.search-filter')
            @endcomponent

            @component('components.invoices-card', ['invoicescards' => $invoicescards])
            @endcomponent

            <div class="row">
                <div class="col-sm-12">
                    <div class="card-table">
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-stripped table-hover datatable">
                                    <thead class="thead-light">
                                        <tr>
                                            <th>
                                                <label class="custom_check">
                                                    <input type="checkbox" name="invoice">
                                                    <span class="checkmark"></span>
                                                </label> Invoice ID
                                            </th>
                                            <th>Category</th>
                                            <th>Issued On</th>
                                            <th>Invoice To</th>
                                            <th>Total Amount</th>
                                            <th>Paid Amount</th>
                                            <th>Payment Mode</th>
                                            <th>Balance</th>
                                            <th>Due Date</th>
                                            <th>Status</th>
                                            <th class="text-end">Action</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @forelse ($invoices as $invoice)
                                            <tr>
                                                <td>
                                                    <label class="custom_check">
                                                        <input type="checkbox" name="invoice">
                                                        <span class="checkmark"></span>
                                                    </label>
                                                    <a href="{{ url('invoice-details/'.$invoice['id']) }}" class="invoice-link">
                                                        {{ $invoice['InvoiceID'] }}
                                                    </a>
                                                </td>
                                                <td>{{ $invoice['Category'] }}</td>
                                                <td>{{ $invoice['IssuedOn'] }}</td>
                                                <td>
                                                    <div class="fw-semibold text-dark">{{ $invoice['InvoiceTo'] }}</div>
                                                    <div class="text-muted small">{{ $invoice['Email'] ?? 'No customer email' }}</div>
                                                </td>
                                                <td>₦{{ $invoice['TotalAmount'] }}</td>
                                                <td>₦{{ $invoice['PaidAmount'] }}</td>
                                                <td>{{ $invoice['PaymentMode'] }}</td>
                                                <td>₦{{ $invoice['Balance'] }}</td>
                                                <td>{{ $invoice['DueDate'] }}</td>
                                                <td>
                                                    <span class="badge {{ $invoice['Class'] }}">
                                                        {{ $invoice['Status'] }}
                                                    </span>
                                                </td>
                                                <td class="text-end">
                                                    <div class="dropdown dropdown-action">
                                                        <a href="#" class="btn-action-icon" data-bs-toggle="dropdown" aria-expanded="false">
                                                            <i class="fas fa-ellipsis-v"></i>
                                                        </a>
                                                        <div class="dropdown-menu dropdown-menu-end">
                                                            <a class="dropdown-item" href="{{ route('invoices.edit', $invoice['id']) }}">
                                                                <i class="far fa-edit me-2"></i>Edit
                                                            </a>
                                                            <a class="dropdown-item" href="{{ url('invoice-details/'.$invoice['id']) }}">
                                                                <i class="far fa-eye me-2"></i>View
                                                            </a>
                                                            <a class="dropdown-item" href="javascript:void(0);" 
                                                               data-bs-toggle="modal" 
                                                               data-bs-target="#delete_modal" 
                                                               onclick="prepareDelete('{{ route('invoices.destroy', $invoice['id']) }}')">
                                                                <i class="far fa-trash-alt me-2"></i>Delete
                                                            </a>
                                                            <a class="dropdown-item" href="{{ route('sales.send', $invoice['id']) }}">
                                                                <i class="fe fe-send me-2"></i>Send
                                                            </a>
                                                            <a class="dropdown-item" href="{{ route('sales.clone', $invoice['id']) }}">
                                                                <i class="fe fe-file-text me-2"></i>Clone Invoice
                                                            </a>
                                                        </div>
                                                    </div>
                                                </td>
                                            </tr>
                                        @empty
                                            <tr>
                                                <td colspan="11" class="text-center">No recurring invoices found.</td>
                                            </tr>
                                        @endforelse
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Delete Confirmation Modal --}}
    <div class="modal custom-modal fade" id="delete_modal" role="dialog">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-body">
                    <div class="form-header">
                        <h3>Delete Invoice</h3>
                        <p>Are you sure you want to delete this invoice?</p>
                    </div>
                    <div class="modal-btn delete-action">
                        <div class="row">
                            <div class="col-6">
                                <form id="delete_form" method="POST">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-primary w-100">Delete</button>
                                </form>
                            </div>
                            <div class="col-6">
                                <button data-bs-dismiss="modal" class="btn btn-secondary w-100">Cancel</button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        function prepareDelete(actionUrl) {
            document.getElementById('delete_form').action = actionUrl;
        }

        {{-- Mandatory Printing Script --}}
        document.addEventListener('click', function(e) {
            if (e.target.classList.contains('fa-print') || e.target.closest('.btn-print')) {
                window.print();
            }
        });
    </script>
@endsection
