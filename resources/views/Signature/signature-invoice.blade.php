<?php $page = 'signature-invoice'; ?>
@extends('layout.mainlayout')
@section('content')
    <!-- Page Wrapper -->
    <div class="page-wrapper">
        <div class="content container-fluid">
            <div class="signature-invoice">
                <div class="page-header">
                    <div class="content-page-header">
                        <h5>Create Invoice</h5>
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-12">
                        <div class="edit-card">
                            <div class="card-body">
                                <div class="form-group-item border-0 mb-0">
                                    <div class="row align-item-center">
                                        <div class="col-lg-4 col-md-6 col-sm-12">
                                            <div class="input-block mb-3">
                                                <label>Invoice Number</label>
                                                <input type="text" class="form-control"
                                                    placeholder="Enter Invoice Number">
                                            </div>
                                        </div>
                                        <div class="col-lg-4 col-md-6 col-sm-12">
                                            <div class="input-block mb-3">
                                                <label>Customer Name</label>
                                                <ul class="form-group-plus css-equal-heights">
                                                    <li>
                                                        <select class="select">
                                                            <option>Customer 1</option>
                                                            <option>Customer 2</option>
                                                            <option>Customer 3</option>
                                                        </select>
                                                    </li>
                                                    <li>
                                                        <a class="btn btn-primary form-plus-btn"
                                                            href="{{ url('add-customer') }}">
                                                            <i class="fe fe-plus-circle"></i>
                                                        </a>
                                                    </li>
                                                </ul>
                                            </div>
                                        </div>
                                        <div class="col-lg-4 col-md-6 col-sm-12">
                                            <div class="input-block mb-3">
                                                <label>Invoice Date</label>
                                                <div class="cal-icon cal-icon-info">
                                                    <input type="text" class="datetimepicker form-control"
                                                        placeholder="Select Date">
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-lg-4 col-md-6 col-sm-12">
                                            <div class="input-block mb-3">
                                                <label>Due Date</label>
                                                <div class="cal-icon cal-icon-info">
                                                    <input type="text" class="datetimepicker form-control"
                                                        placeholder="Select Date">
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-lg-4 col-md-6 col-sm-12">
                                            <div class="input-block mb-3">
                                                <label>Status</label>
                                                <select class="select">
                                                    <option>Unpaid</option>
                                                    <option>Partially paid</option>
                                                    <option>Paid</option>
                                                    <option>Overdue</option>
                                                    <option>Cancelled</option>
                                                    <option>Refunded</option>
                                                    <option>Draft</option>
                                                </select>
                                            </div>
                                        </div>
                                        <div class="col-lg-4 col-md-6 col-sm-12">
                                            <div class="input-block mb-3">
                                                <label>Reference Number</label>
                                                <input type="text" class="form-control"
                                                    placeholder="Enter Reference Number">
                                            </div>
                                        </div>
                                        <div class="col-lg-4">
                                            <div class="d-flex justify-content-between flex-wrap">
                                                <div class="input-block mb-3 recurring-tab">
                                                    <label>Recurring</label>
                                                    <ul class="nav nav-pills d-flex" id="pills-tab" role="tablist">
                                                        <li class="nav-item" role="presentation">
                                                            <button class="nav-link active yes" id="pills-home-tab"
                                                                data-bs-toggle="pill" data-bs-target="#pills-home"
                                                                type="button" role="tab" aria-controls="pills-home"
                                                                aria-selected="true">Yes</button>
                                                        </li>
                                                        <li class="nav-item" role="presentation">
                                                            <button class="nav-link no" id="pills-profile-tab"
                                                                data-bs-toggle="pill" data-bs-target="#pills-profile"
                                                                type="button" role="tab" aria-controls="pills-profile"
                                                                aria-selected="false">No</button>
                                                        </li>
                                                    </ul>
                                                </div>
                                                <div class="tab-content pt-0" id="pills-tabContent">
                                                    <div class="tab-pane fade show active" id="pills-home" role="tabpanel"
                                                        aria-labelledby="pills-home-tab">
                                                        <div class="input-block mb-3">
                                                            <label>Recurring cycle</label>
                                                            <input type="text" class="form-control"
                                                                placeholder="Enter Month">
                                                        </div>
                                                    </div>
                                                    <div class="tab-pane fade" id="pills-profile" role="tabpanel"
                                                        aria-labelledby="pills-profile-tab"></div>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-lg-8">
                                            <div class="input-block mb-3">
                                                <label>Products</label>
                                                <ul class="form-group-plus css-equal-heights">
                                                    <li>
                                                        <select class="select">
                                                            <option>Product 1</option>
                                                            <option>Product 2</option>
                                                            <option>Product 3</option>
                                                        </select>
                                                    </li>
                                                    <li>
                                                        <a class="btn btn-primary form-plus-btn"
                                                            href="{{ url('add-products') }}">
                                                            <i class="fe fe-plus-circle"></i>
                                                        </a>
                                                    </li>
                                                </ul>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="form-group-item">
                                    <div class="card-table">
                                        <div class="card-body">
                                            <div class="table-responsive">
                                                <table class="table table-center table-hover datatable">
                                                    <thead class="thead-light">
                                                        <tr>
                                                            <th>Product / Service</th>
                                                            <th>Quantity</th>
                                                            <th>Unit</th>
                                                            <th>Rate</th>
                                                            <th>Discount</th>
                                                            <th>Tax</th>
                                                            <th>Amount</th>
                                                            <th class="no-sort">Action</th>
                                                        </tr>
                                                    </thead>
                                                    <tbody>
                                                        <tr>
                                                            <td>Lorem ipsum dolor sit amet</td>
                                                            <td><input type="number" class="form-control" value="0">
                                                            </td>
                                                            <td>Pcs</td>
                                                            <td><input type="number" class="form-control"
                                                                    value="120"></td>
                                                            <td>0</td>
                                                            <td>0</td>
                                                            <td>$120.00</td>
                                                            <td class="d-flex align-items-center">
                                                                <a href="#" class="btn-action-icon me-2"
                                                                    data-bs-toggle="modal"
                                                                    data-bs-target="#add_discount"><span><i
                                                                            class="fe fe-edit"></i></span></a>
                                                                <a href="#" class="btn-action-icon"
                                                                    data-bs-toggle="modal"
                                                                    data-bs-target="#delete_discount"><span><i
                                                                            class="fe fe-trash-2"></i></span></a>
                                                            </td>
                                                        </tr>
                                                    </tbody>
                                                </table>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="col-md-4">
                                        <div class="row">
                                            <div class="col-lg-7">
                                                <div class="input-block mb-3">
                                                    <label>Discount Type</label>
                                                    <select class="select">
                                                        <option>Percentage(%)</option>
                                                        <option>Fixed</option>
                                                    </select>
                                                </div>
                                            </div>
                                            <div class="col-lg-5">
                                                <div class="input-block mb-3">
                                                    <label>Discount(%)</label>
                                                    <input type="text" class="form-control" placeholder="10">
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="input-block mb-3">
                                            <label>Tax</label>
                                            <select class="select">
                                                <option>No Tax</option>
                                                <option>IVA - (21%)</option>
                                                <option>IRPF - (-15%)</option>
                                                <option>PDV - (20%)</option>
                                            </select>
                                        </div>
                                    </div>
                                    <div class="col-md-4"></div>
                                </div>
                               @livewire('signature-invoice')
                                <div class="preview-boxs">
                                    <a href="{{ url('signature-preview-invoice') }}">Preview Invoice</a>
                                    <form action="{{ url('invoices') }}" class="add-customer-btns text-end">
                                        <button type="reset" class="btn btn-primary cancel me-2">Cancel</button>
                                        <button type="reset" class="btn btn-primary cancel me-2">Save</button>
                                        <button type="submit" class="btn btn-primary">Save & Send</button>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <!-- /Page Wrapper -->
@endsection
