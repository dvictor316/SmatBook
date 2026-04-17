<?php $page = 'settings'; ?>
@extends('layout.mainlayout')
@section('content')

<style>
    /* Print Customization per User Instructions */
    @media print {
        .d-print-none, .sidebar, .header, .btn, .btn-path, .col-xl-3, .img-upload { 
            display: none !important; 
        }
        .page-wrapper { margin: 0 !important; padding: 0 !important; margin-left: 0 !important; }
        .col-xl-9 { width: 100% !important; flex: 0 0 100% !important; max-width: 100% !important; }
        .card { border: none !important; box-shadow: none !important; }
        .form-control, .select { border: 1px solid #ddd !important; -webkit-appearance: none; }
        .profile-picture { margin-bottom: 20px; }
    }
</style>

<div class="page-wrapper">
    <div class="content container-fluid">
        <div class="row">
            <div class="col-xl-3 col-md-4 d-print-none">
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
                <div class="card" id="printableArea">
                    <div class="card-body w-100">
                        <div class="content-page-header d-flex justify-content-between align-items-center">
                            <h5 class="setting-menu">Account Settings</h5>
                            <button onclick="printContent('printableArea')" class="btn btn-white btn-sm d-print-none">
                                <i class="fa fa-print"></i> Print Settings
                            </button>
                        </div>

                        <form action="{{ route('settings.update') }}" method="POST" enctype="multipart/form-data">
                            @csrf
                        <div class="row">
                            <div class="profile-picture">
                                <div class="upload-profile me-2">
                                    <div class="profile-img">
                                        <img id="blah" class="avatar"
                                            src="{{ \App\Models\Setting::mediaUrl($settings->site_logo ?? null, asset('assets/img/logos.png')) }}"
                                            alt="profile-img">
                                    </div>
                                </div>
                                <div class="img-upload">
                                    <label class="btn btn-primary">
                                        Upload new picture <input type="file" id="imgInp" name="site_logo" accept=".jpg,.jpeg,.png,.svg">
                                    </label>
                                    <p class="mt-2 mb-0">Recommended logo size: 152 x 152 or higher. Supported formats: JPG, PNG, SVG.</p>
                                </div>
                            </div>

                            <div class="col-lg-12">
                                <div class="form-title">
                                    <h5>General Information</h5>
                                </div>
                            </div>
                            <div class="col-lg-6 col-12">
                                <div class="input-block mb-3">
                                    <label>First Name</label>
                                    <input type="text" class="form-control" name="first_name" value="{{ $settings->first_name ?? '' }}">
                                </div>
                            </div>
                            <div class="col-lg-6 col-12">
                                <div class="input-block mb-3">
                                    <label>Last Name</label>
                                    <input type="text" class="form-control" name="last_name" value="{{ $settings->last_name ?? '' }}">
                                </div>
                            </div>
                            <div class="col-lg-6 col-12">
                                <div class="input-block mb-3">
                                    <label>Email</label>
                                    <input type="text" class="form-control" name="email" value="{{ $settings->email ?? '' }}">
                                </div>
                            </div>
                            <div class="col-lg-6 col-12">
                                <div class="input-block mb-3">
                                    <label>Mobile Number</label>
                                    <input type="text" class="form-control" name="mobile_number" value="{{ $settings->mobile_number ?? '' }}">
                                </div>
                            </div>
                            <div class="col-lg-6 col-12">
                                <div class="input-block mb-0">
                                    <label>Gender</label>
                                    <select class="select" name="gender">
                                        <option>Select Gender</option>
                                        <option {{ ($settings->gender ?? '') == 'Male' ? 'selected' : '' }}>Male</option>
                                        <option {{ ($settings->gender ?? '') == 'Female' ? 'selected' : '' }}>Female</option>
                                    </select>
                                </div>
                            </div>
                            <div class="col-lg-6 col-12">
                                <div class="input-block mb-3">
                                    <label>Date of Birth</label>
                                    <div class="cal-icon cal-icon-info">
                                        <input type="text" class="datetimepicker form-control" name="dob" value="{{ $settings->dob ?? '' }}">
                                    </div>
                                </div>
                            </div>

                            <div class="col-lg-12">
                                <div class="form-title">
                                    <h5>Address Information</h5>
                                </div>
                            </div>
                            <div class="col-lg-12">
                                <div class="input-block mb-3">
                                    <label>Address</label>
                                    <input type="text" class="form-control" name="address" value="{{ $settings->address ?? '' }}">
                                </div>
                            </div>
                            <div class="col-lg-6 col-12">
                                <div class="input-block mb-3">
                                    <label>Country</label>
                                    <input type="text" class="form-control" name="country" value="{{ $settings->country ?? '' }}">
                                </div>
                            </div>
                            <div class="col-lg-6 col-12">
                                <div class="input-block mb-3">
                                    <label>State</label>
                                    <input type="text" class="form-control" name="state" value="{{ $settings->state ?? '' }}">
                                </div>
                            </div>
                            <div class="col-lg-6 col-12">
                                <div class="input-block mb-3">
                                    <label>City</label>
                                    <input type="text" class="form-control" name="city" value="{{ $settings->city ?? '' }}">
                                </div>
                            </div>
                            <div class="col-lg-6 col-12">
                                <div class="input-block mb-3">
                                    <label>Postal Code</label>
                                    <input type="text" class="form-control" name="postal_code" value="{{ $settings->postal_code ?? '' }}">
                                </div>
                            </div>

                            <div class="col-lg-12 d-print-none">
                                <div class="btn-path text-end">
                                    @php
                                        $roleNormalized = strtolower((string) (auth()->user()->role ?? ''));
                                        $canRunBackfill = in_array($roleNormalized, ['super_admin', 'superadmin', 'administrator', 'admin'], true);
                                    @endphp
                                    @if($canRunBackfill)
                                        <form action="{{ route('settings.ledger-backfill') }}" method="POST" class="d-inline me-2">
                                            @csrf
                                            <input type="hidden" name="chunk" value="100">
                                            <button type="submit" class="btn btn-warning">Run Ledger Backfill</button>
                                        </form>
                                    @endif
                                    <a href="{{ route('settings.index') }}" class="btn btn-cancel bg-primary-light me-3">Cancel</a>
                                    <button type="submit" class="btn btn-primary">Save Changes</button>
                                </div>
                            </div>
                        </div>
                        </form>

                        @if(isset($emailLogs) && $emailLogs->count())
                            <hr class="my-4">
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <h6 class="mb-0">Email Notification Audit</h6>
                                <small class="text-muted">Latest 15 events</small>
                            </div>
                            <div class="table-responsive">
                                <table class="table table-sm align-middle">
                                    <thead>
                                        <tr>
                                            <th>Time</th>
                                            <th>Event</th>
                                            <th>Recipient</th>
                                            <th>Status</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($emailLogs as $log)
                                            <tr>
                                                <td>{{ optional($log->created_at)->format('d M Y H:i') }}</td>
                                                <td>{{ $log->event_type }}</td>
                                                <td>{{ $log->recipient }}</td>
                                                <td>
                                                    @if($log->status === 'sent')
                                                        <span class="badge bg-success">sent</span>
                                                    @elseif($log->status === 'failed')
                                                        <span class="badge bg-danger">failed</span>
                                                    @else
                                                        <span class="badge bg-secondary">{{ $log->status }}</span>
                                                    @endif
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    // Your requested print script
    function printContent(el) {
        var restorepage = document.body.innerHTML;
        var printcontent = document.getElementById(el).innerHTML;
        document.body.innerHTML = printcontent;
        window.print();
        document.body.innerHTML = restorepage;
        location.reload(); 
    }

    // Image preview logic
    imgInp.onchange = evt => {
        const [file] = imgInp.files
        if (file) {
            blah.src = URL.createObjectURL(file)
        }
    }
</script>
@endsection
