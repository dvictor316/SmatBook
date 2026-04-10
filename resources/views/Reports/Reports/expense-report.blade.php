<?php $page = 'expense-report'; ?>
@extends('layout.mainlayout')

@section('content')
<div class="page-wrapper">
    <div class="content container-fluid">

        {{-- 1. Page Header --}}
        @component('components.page-header')
            @slot('title') {{ __('Expense Report') }} @endslot
        @endcomponent
        @include('Reports.partials.context-strip', [
            'reportLabel' => 'Expense Report',
            'periodLabel' => request('start_date') || request('end_date')
                ? 'Filtered Expense Window'
                : 'Current Expense Ledger',
        ])

        {{-- 2. Functional Filter Section --}}
        <div class="card shadow-sm border-0 mb-4">
            <div class="card-body">
                <form action="{{ url()->current() }}" method="GET" id="filter-form">
                    <div class="row align-items-end">
                        <div class="col-md-3">
                            <label class="form-label small fw-bold">{{ __('Start Date') }}</label>
                            <input type="date" name="start_date" class="form-control" value="{{ request('start_date') }}">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label small fw-bold">{{ __('End Date') }}</label>
                            <input type="date" name="end_date" class="form-control" value="{{ request('end_date') }}">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label small fw-bold">{{ __('Status') }}</label>
                            <select name="status" class="form-control select">
                                <option value="">{{ __('All Status') }}</option>
                                <option value="paid" {{ request('status') == 'paid' ? 'selected' : '' }}>Paid</option>
                                <option value="pending" {{ request('status') == 'pending' ? 'selected' : '' }}>Pending</option>
                                <option value="overdue" {{ request('status') == 'overdue' ? 'selected' : '' }}>Overdue</option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <div class="d-flex gap-2">
                                <button type="submit" class="btn btn-primary w-100">
                                    <i class="feather-filter"></i> {{ __('Filter') }}
                                </button>
                                <a href="{{ url()->current() }}" class="btn btn-secondary w-100">
                                    {{ __('Reset') }}
                                </a>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        {{-- 3. Summary Widget --}}
        <div class="row">
            <div class="col-md-4">
                <div class="card bg-white shadow-sm border-0 mb-4 border-start border-danger border-4 expense-total-card">
                    <div class="card-body">
                        <div class="d-flex align-items-center">
                            <div class="avatar avatar-lg bg-danger-light me-3">
                                <i class="feather-trending-down text-danger"></i>
                            </div>
                            <div>
                                <p class="text-muted mb-1 small">{{ __('Total Amount (Filtered)') }}</p>
                                @php
                                    $totalExpense = 0;
                                    foreach(($expenses ?? []) as $item) {
                                        $totalExpense += (float) ($item->amount ?? 0);
                                    }
                                @endphp
                                <h3 class="mb-0 fw-bold">₦{{ number_format($totalExpense, 2) }}</h3>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- 4. Report Table --}}
        <div class="row">
            <div class="col-sm-12">
                <div class="card shadow-sm border-0">
                    <div class="card-header bg-white border-bottom-0">
                        <div class="row align-items-center">
                            <div class="col">
                                <h5 class="card-title fw-bold">{{ __('Expense Data') }}</h5>
                            </div>
                            <div class="col-auto">
                                {{-- Target for DataTables Buttons --}}
                                <div id="btn_export_group"></div>
                            </div>
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-hover" id="expense-report-table">
                                <thead class="thead-light">
                                    <tr>
                                        <th>#</th>
                                        <th>{{ __('Company') }}</th>
                                        <th>{{ __('Amount') }}</th>
                                        <th>{{ __('Status') }}</th>
                                        <th>{{ __('Category') }}</th>
                                        <th>{{ __('User') }}</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse (($expenses ?? []) as $expense)
                                        @php
                                            $status = strtolower($expense->payment_status ?? 'pending');
                                            $statusClass = match($status) {
                                                'paid'    => 'bg-success-light text-success',
                                                'pending' => 'bg-warning-light text-warning',
                                                'unpaid'  => 'bg-danger-light text-danger',
                                                default   => 'bg-info-light text-info',
                                            };
                                        @endphp
                                        <tr>
                                            <td>{{ method_exists($expenses, 'firstItem') ? $expenses->firstItem() + $loop->index : $loop->iteration }}</td>
                                            <td class="fw-bold text-dark">
                                                {{ $expense->company_name ?? 'N/A' }}
                                                <small class="text-muted d-block">{{ $expense->email ?? '' }}</small>
                                            </td>
                                            <td><span class="fw-bold">₦{{ number_format((float)($expense->amount ?? 0), 2) }}</span></td>
                                            <td><span class="badge {{ $statusClass }}">{{ ucfirst($status) }}</span></td>
                                            <td>{{ $expense->category_name ?? 'General' }}</td>
                                            <td>{{ $expense->user_name ?? 'System' }}</td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="6" class="text-center text-muted py-4">No expense records found for this filter.</td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                            @if(method_exists($expenses, 'links'))
                                <div class="mt-3">{{ $expenses->links() }}</div>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<style>
    .expense-total-card h3 {
        font-size: clamp(0.92rem, 1.7vw, 1.05rem);
        line-height: 1.2;
        font-variant-numeric: tabular-nums;
        overflow-wrap: anywhere;
        word-break: break-word;
    }
</style>
@endsection

@section('script')
{{-- DataTables Buttons Dependencies --}}
<script src="https://cdn.datatables.net/buttons/2.3.6/js/dataTables.buttons.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jszip/3.1.3/jszip.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.1.53/pdfmake.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.1.53/vfs_fonts.js"></script>
<script src="https://cdn.datatables.net/buttons/2.3.6/js/buttons.html5.min.js"></script>
<script src="https://cdn.datatables.net/buttons/2.3.6/js/buttons.print.min.js"></script>

<script>
    $(document).ready(function() {
        if ($('#expense-report-table').length > 0) {
            var table = $('#expense-report-table').DataTable({
                "bFilter": true,
                "destroy": true,
                "ordering": true,
                "dom": 'lBfrtip', // Defines the placement of the buttons (B)
                "buttons": [
                    {
                        extend: 'print',
                        className: 'btn btn-primary btn-sm me-1',
                        text: '<i class="feather-printer me-1"></i> Print',
                        exportOptions: { columns: ':visible' }
                    },
                    {
                        extend: 'excel',
                        className: 'btn btn-success btn-sm me-1',
                        text: '<i class="feather-file-text me-1"></i> Excel',
                        exportOptions: { columns: ':visible' }
                    },
                    {
                        extend: 'pdf',
                        className: 'btn btn-danger btn-sm',
                        text: '<i class="feather-file me-1"></i> PDF',
                        exportOptions: { columns: ':visible' }
                    }
                ],
                "language": {
                    search: ' ',
                    searchPlaceholder: "Search report...",
                    paginate: {
                        next: 'Next <i class="fas fa-chevron-right ms-2"></i>',
                        previous: '<i class="fas fa-chevron-left me-2"></i> Previous'
                    }
                }
            });

            // Reposition the buttons into the designated card-header div
            table.buttons().container().appendTo('#btn_export_group');
        }
    });
</script>
@endsection
