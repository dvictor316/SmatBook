<?php $page = 'ticket-details'; ?>
@extends('layout.mainlayout')
@section('content')
    <!-- Page Wrapper -->
    <div class="page-wrapper">
        <div class="content container-fluid">
            <!-- Page Header -->
            @component('components.page-header')
                @slot('title')
                    Ticket Overview
                @endslot
            @endcomponent
            <!-- /Page Header -->

            <!-- Search Filter -->
            @component('components.search-filter')
            @endcomponent
            <!-- /Search Filter -->

            <div class="ticket-details-group">
                <div class="row align-items-center">
                    <div class="col-lg-6 col-md-6 col-12">
                        <div class="customer-details">
                            <div class="d-flex align-items-center">
                                <span class="ticket-widget-img rounded-circle d-inline-flex">
                                    <img src="{{ URL::asset('/assets/img/icons/ticket.svg') }}" alt="ticket">
                                </span>
                                <div class="ticket-details-cont">
                                    <p>TK-105</p>
                                    <h6>New Support Ticket</h6>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-lg-6 col-md-6 col-12">
                        <div class="ticket-details text-lg-end text-md-end">
                            <span class="badge bg-warning-light text-warning-light">Medium</span>
                            <span class="badge bg-success-light ms-2">Paid</span>
                        </div>
                    </div>
                </div>
            </div>
            <div class="ticket-description-group">
                <h6>Description</h6>
                <p>Lorem ipsum dolor sit amet consectetur adipiscing elit sed do eiusmod tempor incididunt ut labore et
                    dolore magna aliqua. Ut enim ad minim veniam, quis nostrud exercitation ullamco laboris nisi ut aliquip
                    ex ea commodo consequat.</p>
                <p class="mb-0">Lorem ipsum dolor sit amet consectetur adipiscing elit sed do eiusmod tempor incididunt ut
                    labore et dolore magna aliqua. Ut enim ad minim veniam, quis nostrud exercitation ullamco laboris nisi
                    ut aliquip ex ea commodo consequat.</p>
            </div>
            <div class="ticket-information">
                <div class="row align-items-center">
                    <div class="col-lg-4 col-md-4 col-12">
                        <div class="customer-details">
                            <div class="d-flex align-items-center">
                                <span class="customer-widget-img d-inline-flex">
                                    <img class="rounded-circle" src="{{ URL::asset('/assets/img/profiles/avatar-15.jpg') }}"
                                        alt="avatar-img">
                                </span>
                                <div class="customer-details-cont">
                                    <h6>Requester</h6>
                                    <p>John Smith</p>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-lg-4 col-md-4 col-12">
                        <div class="customer-details">
                            <div class="d-flex align-items-center">
                                <span class="customer-widget-icon rounded-circle d-inline-flex">
                                    <i class="fe fe-calendar"></i>
                                </span>
                                <div class="customer-details-cont">
                                    <h6>Requested Date</h6>
                                    <p>30 Dec, 2023</p>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-lg-4 col-md-4 col-12">
                        <div class="customer-details border-0">
                            <div class="d-flex align-items-center">
                                <span class="customer-widget-icon rounded-circle d-inline-flex">
                                    <i class="fe fe-file-text"></i>
                                </span>
                                <div class="customer-details-cont">
                                    <h6>Subject</h6>
                                    <p>Support Ticket</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            @livewire('ticket-details-history')
            <!-- comments -->
            @livewire('ticket-details-comments')
            <!-- /comments -->
            <div class="comments">
                <div class="row">
                    <div class="col-lg-12">
                        <div class="input-block mb-3">
                            <label>Leave a comments</label>
                            <input type="text" class="form-control" placeholder="Enter Comments">
                        </div>
                    </div>
                    <div class="col-lg-12">
                        <div class="text-end">
                            <button type="submit" class="btn btn-primary">Post Comments</button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <!-- /Page Wrapper -->
@endsection
