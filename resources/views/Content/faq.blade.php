<?php $page = 'faq'; ?>
@extends('layout.mainlayout')
@section('content')
    <!-- Page Wrapper -->
    <div class="page-wrapper">
        <div class="content container-fluid">
            <!-- Page Header -->
            @component('components.page-header')
                @slot('title')
                    FAQ
                @endslot
            @endcomponent
            <!-- /Page Header -->

            <!-- Search Filter -->
            @component('components.search-filter')
            @endcomponent
            <!-- /Search Filter -->

            <div class="row">
                <div class="col-sm-12">
                    <div class="card-table">
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-center table-hover datatable">
                                    <thead class="thead-light">
                                        <tr>
                                            <th>#</th>
                                            <th>Title</th>
                                            <th>Category</th>
                                            <th>Details</th>
                                            <th class="no-sort">Action</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @php
                                            $json = file_get_contents(public_path('../public/assets/json/faq.json'));
                                            $faqs = json_decode($json, true);
                                        @endphp
                                        @foreach ($faqs as $faq)
                                            <tr>
                                                <td>{{ $faq['Id'] }}</td>
                                                <td>{{ $faq['Title'] }}</td>
                                                <td>{{ $faq['Category'] }}</td>
                                                <td>{{ $faq['Details'] }} </td>
                                                <td class="d-flex align-items-center">
                                                    <a class="btn-action-icon me-2" href="javascript:void(0);"
                                                        data-bs-toggle="modal" data-bs-target="#edit_faq"><i
                                                            class="fe fe-edit"></i></a>
                                                    <a class="btn-action-icon" href="javascript:void(0);"
                                                        data-bs-toggle="modal" data-bs-target="#delete_modal"><i
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
        </div>
    </div>
    <!-- /Page Wrapper -->
@endsection
