<?php $page = 'email-settings'; ?>
@extends('layout.mainlayout')

@section('content')
    <style>
        /* Sidebar Offset Consistency */
        .page-wrapper {
            margin-left: 270px;
            transition: all 0.3s ease-in-out;
            background-color: #f8fafc;
        }
        body.mini-sidebar .page-wrapper { margin-left: 80px; }

        @media (max-width: 1200px) {
            .page-wrapper { margin-left: 0 !important; }
        }

        /* Email Specific Styling */
        .mail-provider {
            border: 1px solid #e2e8f0;
            border-radius: 12px;
            padding: 20px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            background: #fff;
            transition: all 0.3s;
            margin-bottom: 20px;
        }
        .mail-provider:hover { border-color: #0369a1; box-shadow: 0 4px 12px rgba(3, 105, 161, 0.08); }
        .mail-provider h4 { margin-bottom: 0; font-size: 16px; font-weight: 700; color: #1e293b; }

        .mail-setting { display: flex; align-items: center; gap: 15px; }
        .mail-setting i { font-size: 18px; color: #64748b; transition: color 0.2s; }
        .mail-setting i:hover { color: #0369a1; }

        .mail-title { font-size: 14px; font-weight: 700; color: #64748b; text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 15px; }

        /* Print Styles */
        @media print {
            .page-wrapper { margin-left: 0 !important; padding: 0 !important; }
            .col-xl-3, .btn, .sidebar, .header, .status-toggle, .btn-path { display: none !important; }
            .col-xl-9 { width: 100% !important; }
            .card { border: 1px solid #eee !important; box-shadow: none !important; }
            .mail-provider { border: 1px solid #ddd !important; }
            input.form-control { border: none !important; font-weight: bold; padding: 0 !important; }
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
                                    <h5 class="fw-bold text-primary"><i class="fas fa-envelope-open-text me-2"></i>Settings</h5>
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
                                <h5 class="fw-bold mb-0">Email Settings</h5>
                                <button onclick="window.print()" class="btn btn-outline-secondary btn-sm rounded-pill d-print-none">
                                    <i class="fas fa-print me-1"></i> Print Config
                                </button>
                            </div>

                            <form action="{{ route('settings.update') }}" method="POST">
                                @csrf
                            <div class="row">
                                <div class="col-12">
                                    <h5 class="mail-title">Mail Providers</h5>
                                </div>

                                <div class="col-lg-6 col-12">
                                    <div class="mail-provider">
                                        <div class="d-flex align-items-center">
                                            <div class="bg-soft-primary p-2 rounded me-3 d-print-none">
                                                <i class="fab fa-php fa-lg text-primary"></i>
                                            </div>
                                            <h4>PHP Mail</h4>
                                        </div>
                                        <div class="mail-setting">
                                            <a href="javascript:void(0);" data-bs-toggle="modal" data-bs-target="#php_mail_config">
                                                <i class="fe fe-settings"></i>
                                            </a>
                                            <div class="status-toggle">
                                                <input id="php_mail" class="check" type="checkbox" name="mail_php_enabled" value="1" {{ !empty($settings['mail_php_enabled']) ? 'checked' : '' }}>
                                                <label for="php_mail" class="checktoggle checkbox-bg">checkbox</label>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div class="col-lg-6 col-12">
                                    <div class="mail-provider">
                                        <div class="d-flex align-items-center">
                                            <div class="bg-soft-info p-2 rounded me-3 d-print-none">
                                                <i class="fas fa-server fa-lg text-info"></i>
                                            </div>
                                            <h4>SMTP</h4>
                                        </div>
                                        <div class="mail-setting">
                                            <a href="javascript:void(0);" data-bs-toggle="modal" data-bs-target="#smtp_config">
                                                <i class="fe fe-settings"></i>
                                            </a>
                                            <div class="status-toggle">
                                                <input id="smtp_mail" class="check" type="checkbox" name="mail_smtp_enabled" value="1" {{ !empty($settings['mail_smtp_enabled']) ? 'checked' : '' }}>
                                                <label for="smtp_mail" class="checktoggle checkbox-bg">checkbox</label>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <hr class="my-4 opacity-25">

                                <div class="col-lg-6 col-12">
                                    <div class="input-block mb-3">
                                        <label class="form-label fw-semibold">Email From Name</label>
                                        <input type="text" class="form-control" name="mail_from_name" value="{{ $settings['mail_from_name'] ?? 'SmartProbook' }}" placeholder="e.g. Billing Department">
                                    </div>
                                </div>
                                <div class="col-lg-6 col-12">
                                    <div class="input-block mb-3">
                                        <label class="form-label fw-semibold">Email From Address</label>
                                        <input type="email" class="form-control" name="mail_from_address" value="{{ $settings['mail_from_address'] ?? 'contact@smartprobook.com' }}" placeholder="contact@smartprobook.com">
                                        <div class="form-text">Recommended branded sender: <strong>contact@smartprobook.com</strong>. If your SMTP account is Gmail, use that Gmail address instead.</div>
                                    </div>
                                </div>
                                <div class="col-lg-12 col-12">
                                    <div class="input-block mb-3">
                                        <label class="form-label fw-semibold">Email Global Footer</label>
                                        <textarea class="form-control" rows="3" name="mail_global_footer" placeholder="Enter Email Global Footer">{{ $settings['mail_global_footer'] ?? '' }}</textarea>
                                    </div>
                                </div>

                                <div class="col-lg-12 col-12 mt-3">
                                    <div class="bg-light p-3 rounded-3 mb-4">
                                        <label class="form-label fw-bold text-primary small">SEND TEST EMAIL</label>
                                        <div class="input-group">
                                            <input type="text" class="form-control" id="mail_test_address_input" placeholder="Enter email address to test">
                                            <button class="btn btn-outline-primary" type="button" id="sendTestEmailBtn">Send Test</button>
                                        </div>
                                        <div id="testEmailResult" class="mt-2 small d-none"></div>
                                    </div>
                                </div>

                                <div class="col-lg-12">
                                    <div class="btn-path text-end">
                                        <button type="button" class="btn btn-cancel bg-primary-light me-3 px-4">Cancel</button>
                                        <button type="submit" class="btn btn-primary px-4 shadow-sm">Save Changes</button>
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
    <style>
        .bg-soft-primary { background-color: rgba(3, 105, 161, 0.1); }
        .bg-soft-info { background-color: rgba(14, 165, 233, 0.1); }
    </style>

{{-- ===================== SMTP CONFIG MODAL ===================== --}}
<div class="modal fade" id="smtp_config" tabindex="-1" aria-labelledby="smtpConfigLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <form action="{{ route('settings.update') }}" method="POST" id="smtpConfigForm">
            @csrf
            <div class="modal-content border-0 shadow">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title" id="smtpConfigLabel"><i class="fas fa-server me-2"></i>SMTP Configuration</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="alert alert-info py-2 small mb-3">
                        <i class="fas fa-info-circle me-1"></i>
                        For Gmail, use <strong>smtp.gmail.com</strong>, port <strong>587</strong>, TLS encryption, and a <a href="https://myaccount.google.com/apppasswords" target="_blank" rel="noopener noreferrer">16-character App Password</a>.
                    </div>
                    <div class="row g-3">
                        <div class="col-8">
                            <label class="form-label fw-semibold">SMTP Host</label>
                            <input type="text" class="form-control" name="mail_smtp_host"
                                value="{{ $settings['mail_smtp_host'] ?? $settings['mail_host'] ?? '' }}"
                                placeholder="smtp.gmail.com">
                        </div>
                        <div class="col-4">
                            <label class="form-label fw-semibold">Port</label>
                            <input type="number" class="form-control" name="mail_smtp_port"
                                value="{{ $settings['mail_smtp_port'] ?? $settings['mail_port'] ?? '587' }}"
                                placeholder="587">
                        </div>
                        <div class="col-12">
                            <label class="form-label fw-semibold">Username (Email)</label>
                            <input type="email" class="form-control" name="mail_smtp_username"
                                value="{{ $settings['mail_smtp_username'] ?? $settings['mail_username'] ?? '' }}"
                                placeholder="you@gmail.com">
                        </div>
                        <div class="col-12">
                            <label class="form-label fw-semibold">App Password</label>
                            <input type="password" class="form-control" name="mail_smtp_password"
                                placeholder="Leave blank to keep current password" autocomplete="new-password">
                            @if(!empty($settings['mail_smtp_password']))
                                <div class="form-text text-success"><i class="fas fa-check-circle me-1"></i>Password is saved. Enter a new one to change it.</div>
                            @else
                                <div class="form-text text-muted">No password saved yet.</div>
                            @endif
                        </div>
                        <div class="col-12">
                            <label class="form-label fw-semibold">Encryption</label>
                            <select class="form-select" name="mail_smtp_encryption">
                                @foreach(['tls' => 'TLS (Recommended)', 'ssl' => 'SSL', '' => 'None'] as $val => $label)
                                    <option value="{{ $val }}" {{ ($settings['mail_smtp_encryption'] ?? $settings['mail_encryption'] ?? 'tls') == $val ? 'selected' : '' }}>{{ $label }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-12">
                            <label class="form-label fw-semibold">Enable SMTP</label>
                            <div class="form-check form-switch mt-1">
                                <input class="form-check-input" type="checkbox" name="mail_smtp_enabled" value="1" id="smtpEnabledModal"
                                    {{ !empty($settings['mail_smtp_enabled']) ? 'checked' : '' }}>
                                <label class="form-check-label" for="smtpEnabledModal">Use SMTP for outgoing email</label>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary"><i class="fas fa-save me-1"></i>Save SMTP Config</button>
                </div>
            </div>
        </form>
    </div>
</div>

{{-- =================== PHP MAIL CONFIG MODAL =================== --}}
<div class="modal fade" id="php_mail_config" tabindex="-1" aria-labelledby="phpMailConfigLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <form action="{{ route('settings.update') }}" method="POST">
            @csrf
            <div class="modal-content border-0 shadow">
                <div class="modal-header">
                    <h5 class="modal-title" id="phpMailConfigLabel"><i class="fab fa-php me-2 text-primary"></i>PHP Mail Configuration</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="alert alert-warning py-2 small mb-3">
                        <i class="fas fa-exclamation-triangle me-1"></i>
                        PHP Mail uses your server's sendmail. For most production apps, SMTP is more reliable.
                    </div>
                    <div class="row g-3">
                        <div class="col-12">
                            <label class="form-label fw-semibold">Enable PHP Mail</label>
                            <div class="form-check form-switch mt-1">
                                <input class="form-check-input" type="checkbox" name="mail_php_enabled" value="1" id="phpMailEnabledModal"
                                    {{ !empty($settings['mail_php_enabled']) ? 'checked' : '' }}>
                                <label class="form-check-label" for="phpMailEnabledModal">Use PHP sendmail for outgoing email</label>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary"><i class="fas fa-save me-1"></i>Save</button>
                </div>
            </div>
        </form>
    </div>
</div>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const btn = document.getElementById('sendTestEmailBtn');
    if (!btn) return;

    btn.addEventListener('click', function () {
        const input = document.getElementById('mail_test_address_input');
        const resultDiv = document.getElementById('testEmailResult');
        const email = input ? input.value.trim() : '';

        if (!email) {
            resultDiv.className = 'mt-2 small text-danger';
            resultDiv.textContent = 'Please enter an email address.';
            resultDiv.classList.remove('d-none');
            return;
        }

        btn.disabled = true;
        btn.textContent = 'Sending…';

        fetch('{{ route("settings.send-test-email") }}', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                'Accept': 'application/json',
            },
            body: JSON.stringify({ mail_test_address: email }),
        })
        .then(r => r.json())
        .then(data => {
            resultDiv.className = 'mt-2 small ' + (data.success ? 'text-success' : 'text-danger');
            resultDiv.textContent = data.message;
            resultDiv.classList.remove('d-none');
        })
        .catch(() => {
            resultDiv.className = 'mt-2 small text-danger';
            resultDiv.textContent = 'An unexpected error occurred.';
            resultDiv.classList.remove('d-none');
        })
        .finally(() => {
            btn.disabled = false;
            btn.textContent = 'Send Test';
        });
    });
});
</script>
@endpush
@endsection
