<?php $page = 'two-factor'; ?>
@extends('layout.mainlayout')
@section('content')
    <div class="page-wrapper">
        <div class="content container-fluid">

            <div class="row">
                <div class="col-xl-3 col-md-4">
                    <div class="card">
                        <div class="card-body">
                            <div class="page-header">
                                <div class="content-page-header">
                                    <h5>Settings</h5>
                                </div>
                            </div>
                            @component('components.settings-menu')
                            @endcomponent
                            </div>
                    </div>
                </div>

                <div class="col-xl-9 col-md-8">
                    <div class="card">
                        <div class="card-body two-factor w-100" id="printableArea">
                            <form action="{{ route('settings.update') }}" method="POST">
                                @csrf
                            <div class="content-page-header factor">
                                <h5 class="setting-menu">Two-Factor Authentication Options</h5>
                            </div>
                            <div class="row">
                                <div class="col-sm-9">
                                    <div class="two-factor content p-0">
                                        <h5>Text Message</h5>
                                        <p>Use your mobile phone to receive security PIN.</p>
                                    </div>
                                </div>
                                <div class="col-sm-3 text-end">
                                    <div class="factor-checkbox">
                                        <div class="status-toggle">
                                            <input type="hidden" name="two_factor_sms_enabled" value="0">
                                            <input id="rating_1" class="check" type="checkbox" name="two_factor_sms_enabled" value="1" {{ !empty($settings['two_factor_sms_enabled']) ? 'checked' : '' }}>
                                            <label for="rating_1" class="checktoggle checkbox-bg factor">checkbox</label>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="two-factor icon">
                                <h5><img src="{{ URL::asset('/assets/img/two-factor-icon.svg') }}" alt="Icon">
                                    {{ !empty($settings['two_factor_sms_enabled']) ? 'Enabled' : 'Disabled' }}</h5>
                            </div>
                            <div class="text-end mt-4">
                                <button type="submit" class="btn btn-primary">Save Changes</button>
                            </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <script>
        function printContent(el) {
            var restorepage = document.body.innerHTML;
            var printcontent = document.getElementById(el).innerHTML;
            document.body.innerHTML = printcontent;
            window.print();
            document.body.innerHTML = restorepage;
            location.reload(); 
        }
    </script>
@endsection
