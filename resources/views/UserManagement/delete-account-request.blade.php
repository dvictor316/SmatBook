<?php $page = 'delete-account-request'; ?>
@extends('layout.mainlayout')

@section('content')
    <div class="page-wrapper">
        <div class="content container-fluid">
            @component('components.page-header')
                @slot('title') Delete Account Request @endslot
            @endcomponent

            @if(session('success'))
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    {{ session('success') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            @endif

            <div class="row">
                <div class="col-sm-12">
                    <div class="card-table shadow-sm">
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-center table-hover datatable" id="requests-table">
                                    <thead class="thead-light">
                                        <tr>
                                            <th>#</th>
                                            <th>User Name</th>
                                            <th>Requisition Date</th>
                                            <th>Delete Request Date</th>
                                            <th class="no-sort text-end">Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @php
                                            $path = public_path('assets/json/delete-account-request.json');
                                            $accounts = file_exists($path) ? (json_decode(file_get_contents($path), true) ?? []) : [];
                                        @endphp

                                        @forelse ($accounts as $account)
                                            <tr>
                                                <td>{{ $account['Id'] }}</td>
                                                <td>
                                                    <h2 class="table-avatar">
                                                        <a href="#" class="avatar avatar-sm me-2">
                                                            <img class="avatar-img rounded-circle" src="{{ asset('assets/img/profiles/' . ($account['Image'] ?? 'default.jpg')) }}">
                                                        </a>
                                                        <a href="#">{{ $account['UserName'] }}<span>{{ $account['Email'] }}</span></a>
                                                    </h2>
                                                </td>
                                                <td>{{ $account['RequisitionDate'] }}</td>
                                                <td>{{ $account['DeleteRequestDate'] }}</td>
                                                <td class="text-end">

                                                    <button type="button" class="btn btn-greys btn-sm trigger-delete" 
                                                            data-id="{{ $account['Id'] }}" 
                                                            data-name="{{ $account['UserName'] }}">
                                                        Confirm
                                                    </button>

                                                    <div class="dropdown dropdown-action d-inline-block">
                                                        <a href="javascript:void(0);" class="btn-action-icon" data-bs-toggle="dropdown" aria-expanded="false">
                                                            <i class="fas fa-ellipsis-v"></i>
                                                        </a>
                                                        <div class="dropdown-menu dropdown-menu-right">
                                                            <a class="dropdown-item trigger-delete" href="javascript:void(0);" 
                                                               data-id="{{ $account['Id'] }}" 
                                                               data-name="{{ $account['UserName'] }}">
                                                                <i class="far fa-trash-alt me-2"></i>Delete
                                                            </a>
                                                        </div>
                                                    </div>
                                                </td>
                                            </tr>
                                        @empty
                                            <tr><td colspan="5" class="text-center py-4">No requests found.</td></tr>
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

    <div class="modal fade" id="delete_modal" tabindex="-1" aria-hidden="true" style="z-index: 1060;">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <form action="{{ route('roles.delete-user') }}" method="POST" id="delete_account_form">
                    @csrf
                    @method('DELETE')
                    <input type="hidden" name="user_id" id="modal_user_id">

                    <div class="modal-body text-center pt-4">
                        <div class="mb-3">
                            <i class="fas fa-exclamation-triangle fa-4x text-danger"></i>
                        </div>
                        <h3>Confirm Deletion</h3>
                        <p>Are you sure you want to delete <strong id="modal_user_name"></strong>?</p>
                        <p class="text-muted small">This action will remove the record from the database and the JSON file.</p>
                    </div>
                    <div class="modal-footer justify-content-center pb-4 border-0">
                        <button type="button" class="btn btn-secondary me-2" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-danger">Yes, Delete Account</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection

@section('script')
<script>
$(document).ready(function() {
    /**
     * Using Event Delegation: This ensures that even after searching or 
     * changing pages in DataTables, the click still works.
     */
    $(document).on('click', '.trigger-delete', function(e) {
        e.preventDefault();

        // Fetch data from the clicked element
        var userId = $(this).data('id');
        var userName = $(this).data('name');

        // Populate the modal fields
        $('#modal_user_id').val(userId);
        $('#modal_user_name').text(userName);

        // Manually trigger the bootstrap modal
        var myModal = new bootstrap.Modal(document.getElementById('delete_modal'));
        myModal.show();
    });
});
</script>
@endsection