<?php $page = 'states'; ?>
@extends('layout.mainlayout')
@section('content')
    <!-- Page Wrapper -->
    <div class="page-wrapper">
        <div class="content container-fluid">

            <!-- Page Header -->
            @component('components.page-header')
                @slot('title')
                    State
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
                                            <th>Country Code</th>
                                            <th>Country Name</th>
                                            <th>State Name</th>
                                            <th class="no-sort">Action</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @php
                                            $json = file_get_contents(public_path('../public/assets/json/states.json'));
                                            $states = json_decode($json, true);
                                        @endphp
                                        @foreach ($states as $state)
                                            <tr>
                                                <td>{{ $state['Id'] }}</td>
                                                <td>{{ $state['CountryCode'] }}</td>
                                                <td>
                                                    <h2>
                                                        <img class="avatar-img rounded me-2" width="30" height="30"
                                                            src="{{ URL::asset('/assets/img/flags/' . $state['Image']) }}"
                                                            alt="User Image">
                                                    </h2>{{ $state['CountryName'] }}
                                                </td>
                                                <td>{{ $state['StateName'] }}</td>
                                                <td class="d-flex align-items-center">
                                                    <a class=" btn-action-icon me-2" href="javascript:void(0);"
                                                        data-bs-toggle="modal" data-bs-target="#edit_state"><i
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
            <!-- /Table -->

        </div>
    </div>
    <!-- /Page Wrapper -->
@endsection
