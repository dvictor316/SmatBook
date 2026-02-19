<?php $page = 'signature-list'; ?>
@extends('layout.mainlayout')
@section('content')
    <!-- Page Wrapper -->
    <div class="page-wrapper">
        <div class="content container-fluid">

            <!-- Page Header -->
            @component('components.page-header')
                @slot('title')
                Signature
                @endslot
            @endcomponent
            <!-- /Page Header -->

            <!-- Table -->
            <div class="row">
                <div class="col-sm-12">
                    <div class=" card-table">
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-center datatable signature-table">
                                    <thead class="thead-light">
                                        <tr>
                                            <th>#</th>
                                            <th>Signature Name</th>
                                            <th>Signature</th>
                                            <th>Status</th>
                                            <th class="no-sort">Action</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @php
                                            $json = file_get_contents(public_path('../public/assets/json/signature-list.json'));
                                            $signatures = json_decode($json, true);
                                        @endphp
                                        @foreach ($signatures as $signature)
                                            <tr>
                                                <td>{{ $signature['Id'] }}</td>
                                                <td>{{ $signature['SignatureName'] }}</td>
                                                <td>
                                                    <div class="table-avatar">
                                                        <img class="img-fluid light-color-logo" width="80"
                                                            height="30"
                                                            src="{{ URL::asset('/assets/img/' . $signature['Signature']) }}"
                                                            alt="User Image">
                                                        <img class="img-fluid dark-white-logo" width="80" height="30"
                                                            src="{{ URL::asset('/assets/img/' . $signature['Image']) }}"
                                                            alt="User Image">
                                                    </div>
                                                </td>
                                                <td>
                                                    <div class="status-toggle">
                                                        <input id="{{ $signature['CheckboxId'] }}" class="check"
                                                            type="checkbox"
                                                            @if ($signature['checked']) checked @endif>
                                                        <label for="{{ $signature['CheckboxFor'] }}"
                                                            class="checktoggle checkbox-bg">{{ $signature['Status'] }}</label>
                                                    </div>
                                                </td>
                                                <td class="d-flex align-items-center">
                                                    <a class=" btn-action-icon active me-2" href="javascript:void(0);"
                                                        data-bs-toggle="tooltip" title="Remove default"
                                                        data-bs-placement="left"><i class="fe fe-star"></i></a>
                                                    <a class=" btn-action-icon me-2" href="javascript:void(0);"
                                                        data-bs-toggle="modal" data-bs-target="#edit_modal"><i
                                                            class="fe fe-edit"></i></a>
                                                    <a class=" btn-action-icon" href="javascript:void(0);"
                                                        data-bs-toggle="modal" data-bs-target="#warning_modal"><i
                                                            class="fe fe-trash-2"></i></a>
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
