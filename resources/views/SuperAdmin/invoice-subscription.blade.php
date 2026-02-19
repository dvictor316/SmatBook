<?php $page = 'invoice-subscription'; ?>
@extends('layout.mainlayout')
@section('content')
    <div class="container">
        <div class="invoice-wrapper download_section subscription-invoice">
            <div class="inv-content">
                <div class="invoice-header">
                    <div class="inv-header-right text-start">
                        <a href="{{ url('/') }}">
                            <img class="logo-lightmode" src="{{ asset($settings['logo_light']) }}" alt="Logo">
                            <img class="logo-darkmode" src="{{ asset($settings['logo_dark']) }}" alt="Logo">
                        </a>
                        <span>{{ __('Original For Recipient') }}</span>
                    </div>
                    <div class="inv-header-left">
                        <h4>{{ strtoupper($invoice->type ?? 'Subscription') }} {{ __('INVOICE') }}</h4>
                        <div class="invoice-num-date">
                            <ul>
                                <li>{{ __('Date') }} : <span>{{ $invoice->created_at->format('d/m/Y') }}</span></li>
                                <li>{{ __('Invoice No') }} : <span>{{ $invoice->invoice_number }}</span></li>
                            </ul>
                        </div>
                    </div>
                </div>

                <div class="patient-infos">
                    <div class="row">
                        <div class="col-lg-6">
                            <div class="row">
                                <div class="col-sm-7">
                                    <div class="sub-invoive-detail">
                                        <h5>{{ __('Invoice To') }} :</h5>
                                        <p><strong>{{ $invoice->customer->name }}</strong><br>
                                           {!! nl2br(e($invoice->customer->address)) !!}<br>
                                           {{ $invoice->customer->email }}<br>
                                           {{ $invoice->customer->phone }}
                                        </p>
                                    </div>
                                </div>
                                <div class="col-sm-5">
                                    <div class="sub-invoive-detail">
                                        <h5>{{ __('Pay To') }} :</h5>
                                        <p><strong>{{ $settings['name'] }}</strong><br>
                                           {!! nl2br(e($settings['address'])) !!}<br>
                                           {{ $settings['email'] }}<br>
                                           {{ $settings['phone'] }}
                                        </p>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-lg-6">
                            <div class="sub-invoive-detail detail-right">
                                <h5>{{ $settings['name'] }}</h5>
                                <ul>
                                    <li>{{ __('Tax ID') }} :<br>{{ $settings['gst_number'] }}</li>
                                    <li>{{ __('Address') }} :<br>{{ $settings['address'] }}</li>
                                    <li>{{ __('Mobile') }} :<br>{{ $settings['phone'] }}</li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="invoice-table p-0">
                    <div class="table-responsive">
                        <table>
                            <thead>
                                <tr class="ecommercetable">
                                    <th class="table_width_1">#</th>
                                    <th class="table_width_2">{{ __('Item') }}</th>
                                    <th class="text-start">{{ __('Description') }}</th>
                                    <th class="text-end">{{ __('Qty') }}</th>
                                    <th class="text-end">{{ __('Price') }}</th>
                                    <th class="text-end">{{ __('Total') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($invoice->items as $index => $item)
                                    <tr>
                                        <td>{{ $index + 1 }}</td>
                                        <td class="text-start">{{ $item->name }}</td>
                                        <td class="text-start">{{ $item->description }}</td>
                                        <td class="text-end">{{ $item->qty }}</td>
                                        <td class="text-end">{{ $invoice->currency_symbol }}{{ number_format($item->price, 2) }}</td>
                                        <td class="text-end">{{ $invoice->currency_symbol }}{{ number_format($item->qty * $item->price, 2) }}</td>
                                    </tr>
                                @endforeach
                                
                                <tr>
                                    <td colspan="4"></td>
                                    <td class="text-end">
                                        {{ $invoice->tax_name ?? 'Tax' }} {{ $invoice->tax_percent }}% <br>
                                        <span class="mt-2 d-inline-flex">{{ __('Sub Total') }}</span>
                                    </td>
                                    <td class="text-end">
                                        {{ $invoice->currency_symbol }}{{ number_format($invoice->tax_amount, 2) }} <br>
                                        <span class="mt-2 d-inline-flex">{{ $invoice->currency_symbol }}{{ number_format($invoice->subtotal, 2) }}</span>
                                    </td>
                                </tr>
                                <tr>
                                    <td colspan="4"></td>
                                    <td class="text-end"><h4>{{ __('Total') }}</h4></td>
                                    <td class="text-end"><h4>{{ $invoice->currency_symbol }}{{ number_format($invoice->total_amount, 2) }}</h4></td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

                <div class="total-amountdetails ps-0 pe-0">
                    <p>{{ __('Total amount (in words)') }}: <span>{{ $invoice->currency }} {{ $invoice->amount_in_words }}</span></p>
                </div>

                <div class="bank-details p-0">
                    <div class="row w-100 align-items-center">
                        <div class="col-md-6">
                            <div class="payment-info">
                                <h5>{{ __('Bank Details') }}</h5>
                                <div class="pay-details">
                                    <div class="mb-2"><span>{{ __('Bank') }} : {{ $invoice->bank_name }}</span></div>
                                    <div class="mb-2"><span>{{ __('Account') }} # : {{ $invoice->account_number }}</span></div>
                                    <div class="mb-2"><span>{{ __('Code') }} : {{ $invoice->bank_code }}</span></div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="company-sign">
                                <span>{{ __('For') }} {{ $settings['name'] }}</span>
                                <img src="{{ asset($settings['signature']) }}" alt="signature">
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="file-link justify-content-center subscription-invoice-foot">
            <button class="download_btn" onclick="window.print()">
                <i class="feather-download-cloud me-1"></i> <span>{{ __('Download PDF') }}</span>
            </button>
            <a href="javascript:window.print()" class="print-link">
                <i class="feather-printer"></i> <span>{{ __('Print') }}</span>
            </a>
        </div>
    </div>
@endsection