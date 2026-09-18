@extends('layout.mainlayout')

@section('content')
<div class="page-wrapper">
    <div class="content container-fluid remote-support-page">
        <div class="remote-support-head">
            <div>
                <span class="remote-support-kicker"><i class="fas fa-shield-alt"></i> Assisted support</span>
                <h1>Remote Support</h1>
                <p>Let an approved SmartProbook technician help with receipt printers, barcode scanners, browser setup, or other device-specific problems.</p>
            </div>
            <a href="{{ route('landing.contact') }}" class="btn btn-outline-primary">
                <i class="fas fa-envelope me-1"></i> Contact Support
            </a>
        </div>

        <div class="remote-support-grid">
            <section class="remote-support-main">
                <div class="remote-support-step">
                    <span>1</span>
                    <div>
                        <h2>Contact support first</h2>
                        <p>Only begin a session after receiving confirmation from an approved SmartProbook technician.</p>
                    </div>
                </div>
                <div class="remote-support-step">
                    <span>2</span>
                    <div>
                        <h2>Download from the official source</h2>
                        <p>Use the official AnyDesk download page and select the version for your device.</p>
                    </div>
                </div>
                <div class="remote-support-step">
                    <span>3</span>
                    <div>
                        <h2>Approve only the current session</h2>
                        <p>Share the session address only with the confirmed technician and remain at the device while support is in progress.</p>
                    </div>
                </div>

                <label class="remote-support-consent">
                    <input type="checkbox" id="remoteSupportConsent">
                    <span>I understand that I must approve the session and should never share passwords, OTPs, card details, or payment authorization.</span>
                </label>

                <a
                    id="remoteSupportDownload"
                    href="https://anydesk.com/en/downloads"
                    target="_blank"
                    rel="noopener noreferrer"
                    class="btn btn-primary remote-support-download disabled"
                    aria-disabled="true"
                >
                    <i class="fas fa-external-link-alt me-2"></i> Open Official Download
                </a>
            </section>

            <aside class="remote-support-safety">
                <div class="remote-support-icon"><i class="fas fa-lock"></i></div>
                <h2>Stay in control</h2>
                <ul>
                    <li><i class="fas fa-check-circle"></i><span>Approve each connection manually.</span></li>
                    <li><i class="fas fa-check-circle"></i><span>Keep unattended access disabled.</span></li>
                    <li><i class="fas fa-check-circle"></i><span>Close private payroll and banking screens.</span></li>
                    <li><i class="fas fa-check-circle"></i><span>End the session immediately after support.</span></li>
                    <li><i class="fas fa-times-circle danger"></i><span>Reject unexpected remote-access requests.</span></li>
                </ul>
                <div class="remote-support-note">
                    SmartProbook does not need your password or one-time verification codes to provide technical support.
                </div>
            </aside>
        </div>
    </div>
</div>

<style>
.remote-support-page { max-width: 1180px; padding-top: 28px; padding-bottom: 48px; }
.remote-support-head { display: flex; justify-content: space-between; align-items: end; gap: 24px; margin-bottom: 24px; }
.remote-support-kicker { display: inline-flex; align-items: center; gap: 8px; color: #08734f; font-size: .76rem; font-weight: 800; text-transform: uppercase; }
.remote-support-head h1 { margin: 8px 0; color: #061a44; font-size: 2rem; font-weight: 800; letter-spacing: 0; }
.remote-support-head p { max-width: 720px; margin: 0; color: #5b6b82; line-height: 1.6; }
.remote-support-grid { display: grid; grid-template-columns: minmax(0, 1.45fr) minmax(300px, .75fr); gap: 20px; }
.remote-support-main, .remote-support-safety { border: 1px solid #dbe5f4; border-radius: 8px; background: #fff; padding: 28px; box-shadow: 0 16px 34px -28px rgba(6, 26, 68, .45); }
.remote-support-step { display: grid; grid-template-columns: 38px minmax(0, 1fr); gap: 14px; padding: 16px 0; border-bottom: 1px solid #edf2f8; }
.remote-support-step > span { width: 34px; height: 34px; display: grid; place-items: center; border-radius: 50%; background: #eaf2ff; color: #0f3a8a; font-weight: 800; }
.remote-support-step h2, .remote-support-safety h2 { margin: 0 0 6px; color: #102a56; font-size: 1rem; font-weight: 800; }
.remote-support-step p { margin: 0; color: #68778d; line-height: 1.55; }
.remote-support-consent { display: flex; align-items: flex-start; gap: 10px; margin: 22px 0 16px; padding: 14px; border: 1px solid #f0cf77; border-radius: 6px; background: #fff9e9; color: #4d3b0c; cursor: pointer; }
.remote-support-consent input { margin-top: 4px; }
.remote-support-download { min-height: 44px; display: inline-flex; align-items: center; justify-content: center; }
.remote-support-download.disabled { pointer-events: none; opacity: .5; }
.remote-support-safety { background: #f7fbff; }
.remote-support-icon { width: 46px; height: 46px; display: grid; place-items: center; margin-bottom: 16px; border-radius: 8px; background: #e7f7ef; color: #08734f; font-size: 1.2rem; }
.remote-support-safety ul { list-style: none; padding: 0; margin: 18px 0; display: grid; gap: 14px; }
.remote-support-safety li { display: flex; align-items: flex-start; gap: 10px; color: #43536a; }
.remote-support-safety li i { margin-top: 3px; color: #119666; }
.remote-support-safety li i.danger { color: #cc3344; }
.remote-support-note { padding: 14px; border-left: 3px solid #cc3344; background: #fff; color: #5b2630; font-size: .86rem; line-height: 1.55; }
@media (max-width: 900px) { .remote-support-grid { grid-template-columns: 1fr; } }
@media (max-width: 640px) {
    .remote-support-page { padding-top: 18px; }
    .remote-support-head { align-items: flex-start; flex-direction: column; }
    .remote-support-main, .remote-support-safety { padding: 20px; }
    .remote-support-download { width: 100%; }
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const consent = document.getElementById('remoteSupportConsent');
    const download = document.getElementById('remoteSupportDownload');
    consent?.addEventListener('change', function () {
        download.classList.toggle('disabled', !this.checked);
        download.setAttribute('aria-disabled', this.checked ? 'false' : 'true');
    });
    download?.addEventListener('click', function (event) {
        if (!consent?.checked) event.preventDefault();
    });
});
</script>
@endsection
