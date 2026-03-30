<?php $page = 'units'; ?>
@extends('layout.mainlayout')
@section('content')
    <div class="page-wrapper">
        <div class="content container-fluid">

            @component('components.page-header')
                @slot('title')
                Units
                @endslot
            @endcomponent
            @component('components.search-filter')
            @endcomponent
            @component('components.products-header')
            @endcomponent
            <div class="row">
                <div class="col-sm-12">
                    <div class="card-table">
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-center table-hover datatable">
                                    <thead class="thead-light">
                                        <tr>
                                            <th>#</th>
                                            <th>Unit Name</th>
                                            <th>Short Name</th>
                                            <th class="no-sort text-center">Action</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @php
                                            // Manual backup units since the JSON file is missing
                                            $units = [
                                                ['id' => 1, 'name' => 'Carton', 'short' => 'ctn'],
                                                ['id' => 2, 'name' => 'Roll', 'short' => 'rl'],
                                                ['id' => 3, 'name' => 'Unit/Piece', 'short' => 'pc'],
                                            ];
                                        @endphp

                                        @foreach ($units as $unit)
                                            <tr>
                                                {{-- Fixed the keys to lowercase to match the array above --}}
                                                <td>{{ $unit['id'] }}</td>
                                                <td>{{ $unit['name'] }}</td>
                                                <td>{{ $unit['short'] }}</td>
                                                <td class="d-flex align-items-center justify-content-center gap-2">
                                                    <a class="btn-action-icon" href="javascript:void(0);"
                                                        data-bs-toggle="modal" data-bs-target="#edit_unit" title="Edit" aria-label="Edit unit">
                                                        <i class="fa-solid fa-pen-to-square"></i>
                                                        <span class="visually-hidden">Edit</span>
                                                    </a>
                                                    <a class="btn-action-icon" href="javascript:void(0);"
                                                        data-bs-toggle="modal" data-bs-target="#delete_modal" title="Delete" aria-label="Delete unit">
                                                        <i class="fa-solid fa-trash-can"></i>
                                                        <span class="visually-hidden">Delete</span>
                                                    </a>
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
