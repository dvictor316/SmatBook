<?php $page = 'mail-pay-invoice'; ?>
@extends('layout.mainlayout')
@section('content')
    <div class="receipt-pay-mail">
        <div class="company-logo">
            <img src="{{ URL::asset('/assets/img/logos.png') }}" alt="Logo">
        </div>
        <ul>
            <li><span>Hi Company Name,</span></li>
            <li>I’m just getting in touch to follow up on our invoice.</li>
            <li>The invoice is scheduled to be paid on or before (15 Aug 2023).</li>
            <li>I look forward to hearing from you and receiving payment shortly. This will ensure we can continue to work
                together.</li>
        </ul>
        <div class="click-invoice-btn">
            <a href="{{ url('pay-online') }}" class="btn btn-primary">Click Here to pay Invoice</a>
        </div>
        <p>Many thanks,</p>
        <p>Finance team</p>
    </div>
@endsection
