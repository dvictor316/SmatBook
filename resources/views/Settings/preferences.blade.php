<?php $page = 'preferences'; ?>
@extends('layout.mainlayout')

@section('content')
<style>
    /* Sidebar Offset Consistency */
    .page-wrapper {
        margin-left: 270px;
        transition: all 0.3s ease-in-out;
    }
    body.mini-sidebar .page-wrapper { margin-left: 80px; }

    @media (max-width: 1200px) {
        .page-wrapper { margin-left: 0 !important; }
    }

    /* Print Script Integration */
    @media print {
        .page-wrapper { margin-left: 0 !important; padding: 0 !important; }
        .col-xl-3, .sidebar, .header, .btn-path { display: none !important; }
        .col-xl-9 { width: 100% !important; }
        .card { border: 1px solid #eee !important; box-shadow: none !important; }
        select { border: none !important; appearance: none !important; color: #000 !important; }
    }
</style>

<div class="page-wrapper">
    <div class="content container-fluid">

        <div class="row">
            <div class="col-xl-3 col-md-4">
                <div class="card shadow-sm border-0">
                    <div class="card-body">
                        <div class="page-header mb-3">
                            <div class="content-page-header">
                                <h5 class="fw-bold text-primary">Settings</h5>
                            </div>
                        </div>
                        @component('components.settings-menu')
                        @endcomponent
                        </div>
                </div>
            </div>

            <div class="col-xl-9 col-md-8">
                <div class="card shadow-sm border-0">
                    <div class="card-body w-100">
                        <div class="content-page-header d-flex justify-content-between align-items-center mb-4">
                            <h5 class="fw-bold mb-0">Preference Settings</h5>
                            <button onclick="window.print()" class="btn btn-outline-secondary btn-sm d-print-none">
                                <i class="fas fa-print me-1"></i> Print Preferences
                            </button>
                        </div>

                        <form action="{{ route('settings.update') }}" method="POST">
                            @csrf
                        <div class="row">
                            <div class="col-lg-6 col-12">
                                <div class="input-block mb-3">
                                    <label class="form-label">Currency</label>
                                    <select class="select form-control" name="pref_currency">
                                        <option>Select Currency</option>
                                        @php $prefCurrency = $settings['pref_currency'] ?? 'USD - US Dollar'; @endphp
                                        <option {{ $prefCurrency === 'USD - US Dollar' ? 'selected' : '' }}>USD - US Dollar</option>
                                        <option {{ $prefCurrency === 'GBP - British Pound' ? 'selected' : '' }}>GBP - British Pound</option>
                                        <option {{ $prefCurrency === 'EUR - Euro' ? 'selected' : '' }}>EUR - Euro</option>
                                        <option {{ $prefCurrency === 'INR - Indian Rupee' ? 'selected' : '' }}>INR - Indian Rupee</option>
                                        <option {{ $prefCurrency === 'AUD - Australian Dollar' ? 'selected' : '' }}>AUD - Australian Dollar</option>
                                    </select>
                                </div>
                            </div>
                            <div class="col-lg-6 col-12">
                                <div class="input-block mb-3">
                                    <label class="form-label">Language</label>
                                    <select class="select form-control" name="pref_language">
                                        <option>Select Language</option>
                                        @php $prefLanguage = $settings['pref_language'] ?? 'English'; @endphp
                                        <option {{ $prefLanguage === 'English' ? 'selected' : '' }}>English</option>
                                        <option {{ $prefLanguage === 'French' ? 'selected' : '' }}>French</option>
                                        <option {{ $prefLanguage === 'German' ? 'selected' : '' }}>German</option>
                                        <option {{ $prefLanguage === 'Italian' ? 'selected' : '' }}>Italian</option>
                                        <option {{ $prefLanguage === 'Spanish' ? 'selected' : '' }}>Spanish</option>
                                    </select>
                                </div>
                            </div>
                            <div class="col-xl-6 col-lg-6 col-md-6 col-12">
                                <div class="input-block mb-3">
                                    <label class="form-label">Time Zone</label>
                                    <select class="select form-control" name="pref_timezone">
                                        <option>Select Time Zone</option>
                                        @php $prefTimezone = $settings['pref_timezone'] ?? '(UTC+00:00) GMT'; @endphp
                                        <option {{ $prefTimezone === '(UTC+09:00) Tokyo' ? 'selected' : '' }}>(UTC+09:00) Tokyo</option>
                                        <option {{ $prefTimezone === '(UTC+00:00) GMT' ? 'selected' : '' }}>(UTC+00:00) GMT</option>
                                        <option {{ $prefTimezone === '(UTC+11:00) INR' ? 'selected' : '' }}>(UTC+11:00) INR</option>
                                    </select>
                                </div>
                            </div>
                            <div class="col-xl-6 col-lg-6 col-md-6 col-12">
                                <div class="input-block mb-3">
                                    <label class="form-label">Date Format</label>
                                    <select class="select form-control" name="pref_date_format">
                                        <option>Select Date Format</option>
                                        @php $prefDate = $settings['pref_date_format'] ?? '09 Nov 2023'; @endphp
                                        <option {{ $prefDate === '2023 Nov 09' ? 'selected' : '' }}>2023 Nov 09</option>
                                        <option {{ $prefDate === '09 Nov 2023' ? 'selected' : '' }}>09 Nov 2023</option>
                                        <option {{ $prefDate === '09/11/2023' ? 'selected' : '' }}>09/11/2023</option>
                                    </select>
                                </div>
                            </div>
                            <div class="col-xl-6 col-lg-6 col-md-6 col-12">
                                <div class="input-block mb-3">
                                    <label class="form-label">Time Format</label>
                                    <select class="select form-control" name="pref_time_format">
                                        <option>Select Time Format</option>
                                        @php $prefTime = $settings['pref_time_format'] ?? '12:00 AM - 12:00 PM'; @endphp
                                        <option {{ $prefTime === '12:00 AM - 12:00 PM' ? 'selected' : '' }}>12:00 AM - 12:00 PM</option>
                                        <option {{ $prefTime === '24 Hours' ? 'selected' : '' }}>24 Hours</option>
                                    </select>
                                </div>
                            </div>
                            <div class="col-xl-6 col-lg-6 col-md-6 col-12">
                                <div class="input-block mb-3">
                                    <label class="form-label">Financial Year </label>
                                    <select class="select form-control" name="pref_financial_year">
                                        <option>Select Financial Year </option>
                                        @php $prefYear = $settings['pref_financial_year'] ?? 'January-December'; @endphp
                                        <option {{ $prefYear === 'January-December' ? 'selected' : '' }}>January-December</option>
                                        <option {{ $prefYear === 'February-January' ? 'selected' : '' }}>February-January</option>
                                        <option {{ $prefYear === 'March-February' ? 'selected' : '' }}>March-February</option>
                                    </select>
                                </div>
                            </div>

                            <div class="col-lg-12 d-print-none">
                                <div class="btn-path text-end border-top pt-4 mt-2">
                                    <a href="javascript:void(0);" class="btn btn-cancel bg-primary-light me-3">Cancel</a>
                                    <button type="submit" class="btn btn-primary">Save Changes</button>
                                </div>
                            </div>
                        </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
