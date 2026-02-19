<?php $page = 'blog-comments'; ?>
@extends('layout.mainlayout')
@section('content')
    <!-- Page Wrapper -->
    <div class="page-wrapper">
        <div class="content container-fluid">

            <!-- Page Header -->
            @component('components.page-header')
                @slot('title')
                    Blog Comments
                @endslot
            @endcomponent
            <!-- /Page Header -->

            <!-- Search Filter -->
            @component('components.search-filter')
            @endcomponent
            <!-- /Search Filter -->

            <div class="row">
                <div class="col-sm-12">
                    <div class=" card-table">
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-center table-hover datatable">
                                    <thead class="thead-light">
                                        <tr>
                                            <th>#</th>
                                            <th>User Name</th>
                                            <th>Email</th>
                                            <th>Content</th>
                                            <th>Created On</th>
                                            <th Class="no-sort">Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @php
                                            $json = file_get_contents(public_path('../public/assets/json/blog-comments.json'));
                                            $blogs = json_decode($json, true);
                                        @endphp
                                        @foreach ($blogs as $blog)
                                            <tr>
                                                <td>{{ $blog['Id'] }}</td>
                                                <td>
                                                    <h2 class="table-avatar">
                                                        <a href="{{ url('profile') }}" class="avatar avatar-sm me-2"><img
                                                                class="avatar-img rounded-circle"
                                                                src="{{ URL::asset('/assets/img/profiles/' . $blog['Image']) }}"
                                                                alt="User Image"></a>
                                                        <a href="{{ url('profile') }}">{{ $blog['UserName'] }} <span>
                                                                {{ $blog['Phone'] }}</span></a>
                                                    </h2>
                                                </td>
                                                <td>{{ $blog['Email'] }}</td>
                                                <td>{{ $blog['Content'] }} </td>
                                                <td>{{ $blog['CreatedOn'] }}</td>
                                                <td class="d-flex align-items-center">
                                                    <a class=" btn-action-icon" href="javascript:void(0);" download=""><i
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
