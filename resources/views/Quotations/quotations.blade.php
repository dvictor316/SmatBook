<?php $page = 'quotations'; ?>
@extends('layout.mainlayout')
@section('content')
    <!-- Page Wrapper -->
    <div class="page-wrapper">
        <div class="content container-fluid">

            <!-- Page Header -->
            @component('components.page-header')
                @slot('title')
                Quotations
                @endslot
            @endcomponent
            <!-- /Page Header -->

            <!-- Search Filter -->
            @component('components.search-filter')
            @endcomponent
            <!-- /Search Filter -->

            <!-- Table -->
            <div class="row">
                <div class="col-sm-12">
                    <div class="card-table">
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-stripped table-hover datatable">
                                    <thead class="thead-light">
                                        <tr>
                                            <th>#</th>
                                            <th>Quotation ID</th>
                                            <th>Customer</th>
                                            <th>Created On</th>
                                            <th>Status</th>
                                            <th class="text-end">Action</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @forelse (($quotations ?? collect()) as $quotation)
                                            <tr>
                                                <td>{{ $quotation->id }}</td>
                                                <td>{{ $quotation->quotation_id }}</td>
                                                <td>
                                                    <h2 class="table-avatar">
                                                        <a href="{{ url('profile') }}" class="avatar avatar-sm me-2"><img
                                                                class="avatar-img rounded-circle"
                                                                src="{{ URL::asset('assets/img/profiles/avatar-01.jpg') }}"
                                                                alt="User Image"></a>
                                                        <a href="{{ url('profile') }}">{{ $quotation->customer->name ?? 'Walk-in Customer' }} <span>
                                                                {{ $quotation->customer->phone ?? '' }}</span></a>
                                                    </h2>
                                                </td>
                                                <td>{{ optional($quotation->created_at)->format('d M Y') }}</td>
                                                <td><span
                                                        class="badge {{ strtolower((string) $quotation->status) === 'sent' ? 'bg-info-light text-info' : 'bg-warning-light text-warning' }}">{{ $quotation->status }}</span>
                                                </td>
                                                <td class="d-flex align-items-center">
                                                    <div class="dropdown dropdown-action">
                                                        <a href="#" class=" btn-action-icon "
                                                            data-bs-toggle="dropdown" aria-expanded="false"><i
                                                                class="fas fa-ellipsis-v"></i></a>
                                                        <div class="dropdown-menu dropdown-menu-right quatation-dropdown">
                                                            <ul>
                                                                <li>
                                                                    <a class="dropdown-item"
                                                                        href="{{ route('edit-quotations', $quotation->id) }}"><i
                                                                            class="far fa-edit me-2"></i>Edit</a>
                                                                </li>
                                                                <li>
                                                                    <form action="{{ route('quotations.destroy', $quotation->id) }}" method="POST" class="d-inline">
                                                                        @csrf
                                                                        @method('DELETE')
                                                                        <button class="dropdown-item text-danger" onclick="return confirm('Delete this quotation?')">
                                                                            <i class="far fa-trash-alt me-2"></i>Delete
                                                                        </button>
                                                                    </form>
                                                                </li>
                                                                <li>
                                                                    <a class="dropdown-item" href="javascript:void(0);"><i
                                                                            class="fe fe-eye me-2"></i>View</a>
                                                                </li>
                                                                <li>
                                                                    <a class="dropdown-item"
                                                                        href="{{ url('add-invoice') }}"><i
                                                                            class="fe fe-file-text me-2"></i>Convert to
                                                                        Invoice</a>
                                                                </li>
                                                                <li>
                                                                    <a class="dropdown-item" href="javascript:void(0);"><i
                                                                            class="fe fe-arrow-right-circle me-2"></i>Mark
                                                                        as
                                                                        Sent</a>
                                                                </li>
                                                                <li>
                                                                    <a class="dropdown-item" href="javascript:void(0);"><i
                                                                            class="fe fe-send me-2"></i>Send</a>
                                                                </li>
                                                                <li>
                                                                    <a class="dropdown-item" href="javascript:void(0);"><i
                                                                            class="fe fe-copy me-2"></i>Clone as Invoice</a>
                                                                </li>
                                                                <li>
                                                                    <a class="dropdown-item" href="javascript:void(0);"><i
                                                                            class="fe fe-download me-2"></i>Download</a>
                                                                </li>
                                                            </ul>
                                                        </div>
                                                    </div>
                                                </td>
                                            </tr>
                                        @empty
                                            <tr><td colspan="6" class="text-center py-4 text-muted">No quotations found.</td></tr>
                                        @endforelse
                                    </tbody>
                                </table>
                                @if(isset($quotations) && method_exists($quotations, 'links'))
                                    <div class="mt-3">{{ $quotations->links() }}</div>
                                @endif
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
