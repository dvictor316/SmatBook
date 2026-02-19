<?php $page = 'pay-online'; ?>
@extends('layout.mainlayout')
@section('content')
    <div class="container">
        <div class="invoice-wrapper download_section pay-online-recipt">
            <div class="inv-content">
                <div class="invoice-header">
                    <div class="inv-header-left">
                        <h4>$1,815.00</h4>
                        <p>Due Date : <span>07/12/2023</span></p>
                    </div>
                </div>
                <div class="invoice-address">
                    <div class="invoice-to">
                        <ul class="pay-online-list">
                            <li>
                                <h6>Invoice To </h6> <span> : Walter Roberson</span>
                            </li>
                            <li>
                                <h6>From </h6> <span> : Heather</span>
                            </li>
                            <li>
                                <h6>Notes </h6> <span> : We are not the manufactures, company will stand for warrenty as per
                                    their terms and conditions.</span>
                            </li>
                        </ul>
                    </div>
                </div>
                <div class="more-invoice-detail">
                    <a href="javascript:;">More Invoice Details <i class="feather-chevron-up"></i></a>
                </div>
                <div class="invoice-table">
                    <h4>Invoice Items</h4>
                    <div class="table-responsive">
                        <table>
                            <thead>
                                <tr>
                                    <th class="table_width_1">#</th>
                                    <th class="table_width_2">Item</th>
                                    <th class="table_width_3">Tax Value</th>
                                    <th class="table_width_1 text-center">Qty</th>
                                    <th class="table_width_4 text-end">Price</th>
                                    <th class="table_width_4 text-end">Total</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td>1</td>
                                    <td class=""> Website Design</td>
                                    <td class="table-description">Four plus web pages design and two rivision</td>
                                    <td class="text-center">1</td>
                                    <td class="text-end">$350</td>
                                    <td class="text-end">$350</td>
                                </tr>
                                <tr>
                                    <td>2</td>
                                    <td class="table-description">Web Development</td>
                                    <td class="">Dynamic frontend design</td>
                                    <td class="text-center">1</td>
                                    <td class="text-end">$600</td>
                                    <td class="text-end">$600</td>
                                </tr>
                                <tr>
                                    <td>3</td>
                                    <td class="">App Development</td>
                                    <td class="table-description">Android and Ios App design</td>
                                    <td class="text-center">2</td>
                                    <td class="text-end">$200</td>
                                    <td class="text-end">$400</td>
                                </tr>
                                <tr>
                                    <td>4</td>
                                    <td class="">Digital Marketing</td>
                                    <td class="table-description">Facebook and instagram marketing</td>
                                    <td class="text-center">3</td>
                                    <td class="text-end">$100</td>
                                    <td class="text-end">$300</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
                <div class="pay-method-group">
                    <h6>Select a Payment Method</h6>
                    <ul class="nav nav-pills row" id="pills-tab" role="tablist">
                        <li class="nav-item col-12 col-sm-6" role="presentation">
                            <label class="custom_radio me-4 mb-0 active" id="home-tab" data-bs-toggle="tab"
                                data-bs-target="#home" type="button" role="tab" aria-controls="home"
                                aria-selected="true">
                                <input type="radio" class="form-control" name="payment" checked>
                                <span class="checkmark"></span> Manual Signature
                                <img src="{{ URL::asset('/assets/img/icons/credit-card.svg') }}" alt="img">
                            </label>
                        </li>
                        <li class="nav-item col-12 col-sm-6" role="presentation">
                            <label class="custom_radio me-2 mb-0" id="profile-tab" data-bs-toggle="tab"
                                data-bs-target="#profile" type="button" role="tab" aria-controls="profile"
                                aria-selected="false">
                                <input type="radio" class="form-control" name="payment">
                                <span class="checkmark"></span> eSignature
                                <img src="{{ URL::asset('/assets/img/icons/paypal.svg') }}" alt="img">
                            </label>
                        </li>
                    </ul>
                    <div class="tab-content">
                        <div class="card-information tab-pane fade show active" id="home" role="tabpanel"
                            aria-labelledby="home-tab">
                            <h6>Card Information1</h6>
                            <form>
                                <div class="row">
                                    <div class="col-lg-12 col-sm-12">
                                        <div class="input-block mb-3">
                                            <input type="text" class="form-control"
                                                placeholder="Name of the Card Holder">
                                        </div>
                                    </div>
                                    <div class="col-lg-12 col-sm-12">
                                        <div class="input-block mb-3">
                                            <input type="text" class="form-control" placeholder="Card Number">
                                        </div>
                                    </div>
                                    <div class="col-lg-6 col-md-6 col-sm-12">
                                        <div class="input-block mb-3">
                                            <div class="cal-icon cal-icon-info">
                                                <input type="text" class="datetimepicker form-control"
                                                    placeholder="Expiry Date">
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-lg-6 col-md-6">
                                        <div class="input-block mb-3">
                                            <input type="text" class="form-control" placeholder="CVV Number">
                                        </div>
                                    </div>
                                </div>
                            </form>
                        </div>

                        <div class="card-information tab-pane fade" id="profile" role="tabpanel"
                            aria-labelledby="profile-tab">
                            <h6>Card Information2</h6>
                            <form>
                                <div class="row">
                                    <div class="col-lg-12 col-sm-12">
                                        <div class="input-block mb-3">
                                            <input type="text" class="form-control"
                                                placeholder="Name of the Card Holder">
                                        </div>
                                    </div>
                                    <div class="col-lg-12 col-sm-12">
                                        <div class="input-block mb-3">
                                            <input type="text" class="form-control" placeholder="Card Number">
                                        </div>
                                    </div>
                                    <div class="col-lg-6 col-md-6 col-sm-12">
                                        <div class="input-block mb-3">
                                            <div class="cal-icon cal-icon-info">
                                                <input type="text" class="datetimepicker form-control"
                                                    placeholder="Expiry Date">
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-lg-6 col-md-6">
                                        <div class="input-block mb-3">
                                            <input type="text" class="form-control" placeholder="CVV Number">
                                        </div>
                                    </div>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
                <div class="add-customer-btns payment-btns text-end">
                    <a href="javascript:;" class="btn btn-primary cancel me-2">Close</a>
                    <a href="javascript:;" class="btn btn-primary" data-bs-toggle="modal"
                        data-bs-target="#success_modal">Pay $1,815.00</a>
                </div>
            </div>
        </div>
    </div>
@endsection
