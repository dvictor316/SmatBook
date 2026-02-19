<?php $page = 'cashreceipt-4'; ?>
@extends('layout.mainlayout')
@section('content')
    <div class="receipt-wrap receipt-four">
        <div class="receipt-top">
            <div class="receipt-seperator"></div>
            <div class="receipt-seperator"></div>
            <div class="company-logo">
                <img class="logo-lightmode" src="{{ URL::asset('/assets/img/logo.png') }}" alt="Logo">
                <img class="logo-darkmode" src="{{ URL::asset('/assets/img/logo-full-white.png') }}" alt="Logo">
            </div>
            <div class="receipt-seperator"></div>
            <div class="receipt-seperator"></div>
            <div class="receipt-title">
                Cash Receipt
            </div>
            <div class="company-name">
                Dreamguys Technologies Pvt Ltd.,
            </div>
            <div class="company-address">
                15 Hodges Mews, High Wycombe HP12 3JL, United Kingdom
            </div>
            <div class="company-email">
                Email: demo@example.com
            </div>
        </div>
        <div class="receipt-seperator"></div>
        <div class="receipt-seperator"></div>
        <ul class="customer-list">
            <li>
                <div class="title">Name:</div>
                <div class="desc">John Doe</div>
            </li>
            <li class="text-end me-0">
                <div class="title">Invoice No:</div>
                <div class="desc">CS132453</div>
            </li>
            <li>
                <div class="title">Customer Id:</div>
                <div class="desc">#LL93784</div>
            </li>
            <li class="text-end me-0">
                <div class="title">Date:</div>
                <div class="desc"> 01.07.2023</div>
            </li>
        </ul>
        <div class="receipt-seperator"></div>
        <div class="receipt-seperator"></div>
        <table class="receipt-table">
            <thead>
                <tr>
                    <th>SL</th>
                    <th>Item</th>
                    <th>Price</th>
                    <th>Qty</th>
                    <th>Total</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td>1.</td>
                    <td>Sugarfree</td>
                    <td>$50</td>
                    <td>3</td>
                    <td>$150</td>
                </tr>
                <tr>
                    <td>2.</td>
                    <td>Onion (Loose) (5kg)</td>
                    <td>$50</td>
                    <td>2</td>
                    <td>$100</td>
                </tr>
                <tr>
                    <td>3.</td>
                    <td>Mushrooms - Button 1 pack</td>
                    <td>$50</td>
                    <td>3</td>
                    <td>$150</td>
                </tr>
                <tr>
                    <td>4.</td>
                    <td>Tea 1kg</td>
                    <td>$50</td>
                    <td>3</td>
                    <td>$150</td>
                </tr>
                <tr>
                    <td>5.</td>
                    <td>Diet Coke Soft Drink 300ml</td>
                    <td>$50</td>
                    <td>3</td>
                    <td>$150</td>
                </tr>
            </tbody>
        </table>
        <div class="receipt-seperator"></div>
        <div class="receipt-seperator"></div>
        <div class="bill-list">
            <div class="bill_list_in">
                <div class="bill_title">Sub-Total:</div>
                <div class="bill_value">$700.00</div>
            </div>
            <div class="bill_list_in">
                <div class="bill_title">Discount: </div>
                <div class="bill_value">-$50.00</div>
            </div>
            <div class="receipt-seperator"></div>
            <div class="bill_list_in">
                <div class="bill_title">Service-charge:</div>
                <div class="bill_value">0.00</div>
            </div>
            <div class="bill_list_in">
                <div class="bill_title">Tax(5%):</div>
                <div class="bill_value">$5.00</div>
            </div>
            <div class="receipt-seperator"></div>
            <div class="bill_list_in">
                <div class="bill_title bill_focus">Total Bill:</div>
                <div class="bill_value bill_focus">$655.00</div>
            </div>
            <div class="bill_list_in">
                <div class="bill_title bill_focus">Due:</div>
                <div class="bill_value bill_focus">$0.00</div>
            </div>
            <div class="bill_list_in total-payable">
                <div class="bill_title bill_focus">Total Payable:</div>
                <div class="bill_value bill_focus">$655.00</div>
            </div>
        </div>
        <div class="receipt-seperator"></div>
        <div class="receipt-seperator"></div>
        <div class="sample_text ">
            12332345698234592384
            <img src="{{ URL::asset('/assets/img/bar.png') }}" alt="bar-img">
        </div>
        <div class="receipt-seperator"></div>
        <div class="sample_text">Thank You</div>
        <div class="receipt-seperator"></div>
    </div>
@endsection
