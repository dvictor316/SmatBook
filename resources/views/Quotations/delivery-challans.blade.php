<?php $page = 'delivery-challans'; ?>
@extends('layout.mainlayout')
@section('content')
    
    <div class="page-wrapper">
        <div class="content container-fluid">

            
            @component('components.page-header')
                @slot('title')
                    Delivery Challans
                @endslot
            @endcomponent
            

            
            @component('components.search-filter')
            @endcomponent
            

            
            <div class="row">
                <div class="col-sm-12">
                    <div class="card-table">
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-stripped table-hover datatable">
                                    <thead class="thead-light">
                                        <tr>
                                            <th>#</th>
                                            <th>Challan ID</th>
                                            <th>Customer</th>
                                            <th>Amount</th>
                                            <th>Created On</th>
                                            <th class="text-start">Action</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @php
                                            $challans = [];
                                            $challansPath = public_path('assets/json/delivery-challans.json');
                                            if (is_file($challansPath)) {
                                                $decoded = json_decode((string) file_get_contents($challansPath), true);
                                                if (is_array($decoded)) {
                                                    $challans = $decoded;
                                                }
                                            }
                                            if (empty($challans)) {
                                                $challans = [
                                                    ['Id' => 1, 'ChallanID' => 'CHL-0001', 'Customer' => 'N/A', 'Phone' => '', 'Image' => 'avatar-01.jpg', 'Amount' => '0.00', 'CreatedOn' => now()->format('d M Y')],
                                                ];
                                            }
                                        @endphp
                                        @foreach ($challans as $challan)
                                            <tr>
                                                <td>{{ $challan['Id'] }}</td>
                                                <td>{{ $challan['ChallanID'] }}</td>
                                                <td>
                                                    <h2 class="table-avatar">
                                                        <a href="{{ url('profile') }}" class="avatar avatar-sm me-2"><img
                                                                class="avatar-img rounded-circle"
                                                                src="{{ URL::asset('assets/img/profiles/' . $challan['Image']) }}"
                                                                alt="User Image"></a>
                                                        <a href="{{ url('profile') }}">{{ $challan['Customer'] }}<span>{{ $challan['Phone'] }}
                                                            </span></a>
                                                    </h2>
                                                </td>
                                                <td>{{ $challan['Amount'] }}</td>
                                                <td>{{ $challan['CreatedOn'] }}</td>
                                                <td class="text-start">
                                                    <div class="dropdown dropdown-action">
                                                        <a href="#" class=" btn-action-icon "
                                                            data-bs-toggle="dropdown" aria-expanded="false"><i
                                                                class="fas fa-ellipsis-v"></i></a>
                                                        <div class="dropdown-menu dropdown-menu-right quatation-dropdown">
                                                            <ul>
                                                                <li>
                                                                    <a class="dropdown-item"
                                                                        href="{{ url('edit-delivery-challans') }}"><i
                                                                            class="far fa-edit me-2"></i>Edit</a>
                                                                </li>
                                                                <li>
                                                                    <a class="dropdown-item" href="javascript:void(0);"
                                                                        data-bs-toggle="modal"
                                                                        data-bs-target="#delete_modal"><i
                                                                            class="far fa-trash-alt me-2"></i>Delete</a>
                                                                </li>
                                                                <li>
                                                                    <a class="dropdown-item" href="javascript:void(0);"
                                                                        data-bs-toggle="modal"
                                                                        data-bs-target="#view_modal"><i
                                                                            class="fe fe-eye me-2"></i>View</a>
                                                                </li>
                                                                <li>
                                                                    <a class="dropdown-item" href="javascript:void(0);"><i
                                                                            class="fe fe-file-text me-2"></i>Convert to
                                                                        Invoice</a>
                                                                </li>
                                                                <li>
                                                                    <a class="dropdown-item" href="javascript:void(0);"><i
                                                                            class="fe fe-send me-2"></i>Send</a>
                                                                </li>
                                                                <li>
                                                                    <a class="dropdown-item" href="javascript:void(0);"><i
                                                                            class="fe fe-copy me-2"></i>Clone</a>
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
    
@endsection
