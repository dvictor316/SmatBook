<?php $page = 'pages'; ?>
@extends('layout.mainlayout')
@section('content')
    <!-- Page Wrapper -->
    <div class="page-wrapper">
        <div class="content container-fluid">
            <!-- Page Header -->
            @component('components.page-header')
                @slot('title')
                    Pages list
                @endslot
            @endcomponent
            <!-- /Page Header -->

            <div class="row">
                <div class="col-md-12">
                    <div class="card-table">
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-stripped table-hover datatable">
                                    <thead class="thead-light">
                                        <tr>
                                            <th>#</th>
                                            <th>Pages</th>
                                            <th>Pages Slug</th>
                                            <th>Status</th>
                                            <th class="no-sort">Action</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @php
                                            $json = file_get_contents(public_path('../public/assets/json/pages.json'));
                                            $pages = json_decode($json, true);
                                        @endphp
                                        @foreach ($pages as $page)
                                            <tr>
                                                <td>{{ $page['Id'] }}</td>
                                                <td>{{ $page['Pages'] }}</td>
                                                <td>{{ $page['PagesSlug'] }}</td>
                                                <td>
                                                    <div class="status-toggle">
                                                        <input id="{{ $page['StatusId'] }}" class="check" type="checkbox"
                                                            checked="">
                                                        <label for="{{ $page['StatusFor'] }}"
                                                            class="checktoggle checkbox-bg">{{ $page['Status'] }}</label>
                                                    </div>
                                                </td>
                                                <td class="d-flex align-items-center">
                                                    <a href="#" class="btn-action-icon me-2" data-bs-toggle="modal"
                                                        data-bs-target="#pages_edit"><span><i
                                                                class="fe fe-edit"></i></span></a>
                                                    <a href="#" class="btn-action-icon" data-bs-toggle="modal"
                                                        data-bs-target="#page_delete"><span><i
                                                                class="fe fe-trash-2"></i></span></a>
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
        </div>
    </div>
    <!-- /Page Wrapper -->
@endsection
