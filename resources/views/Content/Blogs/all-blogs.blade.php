<?php $page = 'all-blogs'; ?>
@extends('layout.mainlayout')
@section('content')
    <!-- Page Wrapper -->
    <div class="page-wrapper">
        <div class="content container-fluid">
            <!-- Page Header -->
            @component('components.page-header')
                @slot('title')
                    Blog
                @endslot
            @endcomponent
            <!-- /Page Header -->

            <!-- Search Filter -->
            @component('components.search-filter')
            @endcomponent
            <!-- /Search Filter -->

            <!-- Blogs-->
            <div class="card invoices-tabs-card">
                <div class="invoices-main-tabs">
                    <div class="row align-items-center">
                        <div class="col-lg-12">
                            <div class="invoices-tabs">
                                <ul>
                                    <li><a href="{{ url('blog') }}" class="active">Active Blog</a></li>
                                    <li><a href="{{ url('inactive-blog') }}">Inactive Blog</a></li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <!-- /Blogs-->

            <div class="row">
                <!-- Blog Post -->
                @php
                    $json = file_get_contents(public_path('../public/assets/json/all-blogs.json'));
                    $allblogs = json_decode($json, true);
                @endphp
                @foreach ($allblogs as $allblog)
                    <div class="col-md-6 col-xl-4 col-sm-12 d-md-flex d-sm-block">
                        <div class="blog grid-blog flex-fill">
                            <div class="blog-image">
                                <a href="#"><img class="img-fluid"
                                        src="{{ URL::asset('assets/img/category/' . $allblog['Image']) }}"
                                        alt="Post Image"></a>
                                <div class="blog-views">
                                    <p>{{ $allblog['Id'] }}</p>
                                    <span>{{ $allblog['Month'] }}</span>
                                </div>

                            </div>
                            <div class="blog-content">
                                <ul class="entry-meta meta-item">
                                    <li>
                                        <div class="post-group d-flex align-items-center">
                                            <div class="post-widget">
                                                <span>{{ $allblog['Post'] }}</span>
                                            </div>
                                            <div class="post-author">
                                                <a href="{{ url('profile') }}">
                                                    <img src="{{ URL::asset('assets/img/profiles/' . $allblog['Image1']) }}"
                                                        alt="Post Author">
                                                    <span>
                                                        <span class="post-title">{{ $allblog['Posttitle'] }}</span>
                                                    </span>
                                                </a>
                                            </div>
                                        </div>
                                    </li>
                                </ul>
                                <h3 class="blog-title"><a href="#">
                                        {{ $allblog['Blogtitle'] }}</a>
                                </h3>
                                <p>{{ $allblog['Blogtitle1'] }}</p>
                                <div class="edit-options">
                                    <div class= "edit-delete-btn">
                                        <a href="#" data-bs-toggle="modal" data-bs-target="#edit_blog"
                                            class="text-muted"><i class="fe fe-edit me-1"></i> Edit</a>
                                        <a href="#" class="text-muted" data-bs-toggle="modal"
                                            data-bs-target="#deleteModal"><i class="fe fe-trash-2 me-1"></i> Delete</a>
                                    </div>
                                    <div class="text-end inactive-style">
                                        <a href="javascript:void(0);" class="text-muted"><i class="fe fe-alert-circle me-1"></i>
                                            Active</a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                @endforeach
                <!-- /Blog Post -->
            </div>
            <!-- Pagination -->
            <div class="row ">
                <div class="col-md-12">
                    <div class="pagination-tab  d-flex justify-content-center">
                        <ul class="pagination mb-0">
                            <li class="page-item disabled">
                                <a class="page-link" href="#" tabindex="-1"><i
                                        class="feather-chevron-left me-2"></i>Previous</a>
                            </li>
                            <li class="page-item"><a class="page-link" href="#">1</a></li>
                            <li class="page-item active">
                                <a class="page-link" href="#">2 <span class="visually-hidden">(current)</span></a>
                            </li>
                            <li class="page-item"><a class="page-link" href="#">3</a></li>
                            <li class="page-item"><a class="page-link" href="#">4</a></li>
                            <li class="page-item">
                                <a class="page-link" href="#">Next<i class="feather-chevron-right ms-2"></i></a>
                            </li>
                        </ul>
                    </div>
                </div>
            </div>
            <!-- /Pagination -->
        </div>
    </div>
    <!-- /Page Wrapper -->
@endsection
