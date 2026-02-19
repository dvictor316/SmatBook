<?php $page = 'testimonials'; ?>
@extends('layout.mainlayout')
@section('content')
    <!-- Page Wrapper -->
    <div class="page-wrapper">
        <div class="content container-fluid">
            <!-- Page Header -->
            @component('components.page-header')
                @slot('title')
                    Testimonials
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
                                            <th>User Name</th>
                                            <th>Rating</th>
                                            <th>Content</th>
                                            <th>Created On</th>
                                            <th>Status</th>
                                            <th Class="no-sort">Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @php
                                            $json = file_get_contents(public_path('../public/assets/json/testimonials.json'));
                                            $testimonials = json_decode($json, true);
                                        @endphp
                                        @foreach ($testimonials as $testimonial)
                                            <tr>
                                                <td>{{ $testimonial['Id'] }}</td>
                                                <td>
                                                    <h2 class="table-avatar">
                                                        <a href="{{ url('profile') }}" class="avatar avatar-md me-2"><img
                                                                class="avatar-img rounded-circle"
                                                                src="{{ URL::asset('/assets/img/profiles/' . $testimonial['Image']) }}"
                                                                alt="User Image"></a>
                                                        <a href="{{ url('profile') }}">{{ $testimonial['UserName'] }}
                                                            <span>{{ $testimonial['Email'] }}</span></a>
                                                    </h2>
                                                </td>
                                                <td>
                                                    <span class="mail-important"><i
                                                            class="fas fa-star starred text-warning"></i></span>
                                                    <span class="mail-important"><i
                                                            class="fas fa-star starred text-warning"></i></span>
                                                    <span class="mail-important"><i
                                                            class="fas fa-star starred text-warning"></i></span>
                                                    <span class="mail-important"><i
                                                            class="fas fa-star starred text-warning"></i></span>
                                                    <span class="mail-important"><i
                                                            class="fas fa-star starred text-warning"></i></span>
                                                </td>
                                                <td>{{ $testimonial['Content'] }}</td>
                                                <td>{{ $testimonial['CreatedOn'] }}</td>
                                                <td><span
                                                        class="{{ $testimonial['Class'] }}">{{ $testimonial['Status'] }}</span>
                                                </td>
                                                <td class="d-flex align-items-center">
                                                    <a class=" btn-action-icon me-2" href="javascript:void(0);"
                                                        data-bs-toggle="modal" data-bs-target="#edit_testimonial"><i
                                                            class="fe fe-edit"></i></a>
                                                    <a class=" btn-action-icon" href="javascript:void(0);"
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
