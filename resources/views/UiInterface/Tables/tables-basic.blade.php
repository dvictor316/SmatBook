<?php $page = 'tables-basic'; ?>
@extends('layout.mainlayout')
@section('content')
    <!-- Page Wrapper -->
    <div class="page-wrapper">
        <div class="content container-fluid">

            <!-- Page Header -->
            <div class="page-header">
                <div class="content-page-header">
                    <h5>Basic Tables</h5>
                </div>
            </div>
            <!-- /Page Header -->

            <div class="row">
                <div class="col-lg-6">
                    <div class="card">
                        <div class="card-header">
                            <h4 class="card-title">Basic Table</h4>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table mb-0">
                                    <thead>
                                        <tr>
                                            <th>Firstname</th>
                                            <th>Lastname</th>
                                            <th>Email</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @php
                                            $json = file_get_contents(public_path('../public/assets/json/tables-basic.json'));
                                            $basics = json_decode($json, true);
                                        @endphp
                                        @foreach ($basics as $basic)
                                            <tr>
                                                <td>{{ $basic['Firstname'] }}</td>
                                                <td>{{ $basic['Lastname'] }}</td>
                                                <td>{{ $basic['Email'] }}</td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-lg-6">
                    <div class="card">
                        <div class="card-header">
                            <h5 class="card-title">Striped Rows</h5>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-striped mb-0">
                                    <thead>
                                        <tr>
                                            <th>Firstname</th>
                                            <th>Lastname</th>
                                            <th>Email</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @php
                                            $json = file_get_contents(public_path('../public/assets/json/tables-basic.json'));
                                            $basics = json_decode($json, true);
                                        @endphp
                                        @foreach ($basics as $basic)
                                            <tr>
                                                <td>{{ $basic['Firstname'] }}</td>
                                                <td>{{ $basic['Lastname'] }}</td>
                                                <td>{{ $basic['Email'] }}</td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="row">
                <div class="col-lg-6">
                    <div class="card">
                        <div class="card-header">
                            <h5 class="card-title">Bordered Table</h5>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-bordered mb-0">
                                    <thead>
                                        <tr>
                                            <th>Firstname</th>
                                            <th>Lastname</th>
                                            <th>Email</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @php
                                            $json = file_get_contents(public_path('../public/assets/json/tables-basic.json'));
                                            $basics = json_decode($json, true);
                                        @endphp
                                        @foreach ($basics as $basic)
                                            <tr>
                                                <td>{{ $basic['Firstname'] }}</td>
                                                <td>{{ $basic['Lastname'] }}</td>
                                                <td>{{ $basic['Email'] }}</td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-lg-6">
                    <div class="card">
                        <div class="card-header">
                            <h5 class="card-title">Hover Rows</h5>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-hover mb-0">
                                    <thead>
                                        <tr>
                                            <th>Firstname</th>
                                            <th>Lastname</th>
                                            <th>Email</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @php
                                            $json = file_get_contents(public_path('../public/assets/json/tables-basic.json'));
                                            $basics = json_decode($json, true);
                                        @endphp
                                        @foreach ($basics as $basic)
                                            <tr>
                                                <td>{{ $basic['Firstname'] }}</td>
                                                <td>{{ $basic['Lastname'] }}</td>
                                                <td>{{ $basic['Email'] }}</td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="row">
                <div class="col-lg-6">
                    <div class="card">
                        <div class="card-header">
                            <h5 class="card-title">Contextual Classes</h5>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table mb-0">
                                    <thead>
                                        <tr>
                                            <th>Firstname</th>
                                            <th>Lastname</th>
                                            <th>Email</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @php
                                            $json = file_get_contents(public_path('../public/assets/json/basic-tables.json'));
                                            $basics = json_decode($json, true);
                                        @endphp
                                        @foreach ($basics as $basic)
                                            <tr class="{{ $basic['Class'] }}">
                                                <td>{{ $basic['Firstname'] }}</td>
                                                <td>{{ $basic['Lastname'] }}</td>
                                                <td>{{ $basic['Email'] }}</td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-lg-6">
                    <div class="card">
                        <div class="card-header">
                            <h5 class="card-title">Responsive Tables</h5>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-nowrap mb-0">
                                    <thead>
                                        <tr>
                                            <th>#</th>
                                            <th>Firstname</th>
                                            <th>Lastname</th>
                                            <th>Age</th>
                                            <th>City</th>
                                            <th>Country</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @php
                                            $json = file_get_contents(public_path('../public/assets/json/tables-responsive.json'));
                                            $basics = json_decode($json, true);
                                        @endphp
                                        @foreach ($basics as $basic)
                                            <tr>
                                                <td>{{ $basic['#'] }}</td>
                                                <td>{{ $basic['Firstname'] }}</td>
                                                <td>{{ $basic['Lastname'] }}</td>
                                                <td>{{ $basic['Age'] }}</td>
                                                <td>{{ $basic['City'] }}</td>
                                                <td>{{ $basic['Country'] }}</td>
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
