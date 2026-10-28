@if(isset($company) && $company)
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-body d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-3">
            <div>
                <h5 class="mb-1"><i class="fas fa-laptop me-2 text-primary" aria-hidden="true"></i>Install the offline app</h5>
                <p class="text-muted mb-0">Download the Windows installer. Sign in and sync your product catalogue while online before using offline sales.</p>
            </div>
            <div class="d-flex flex-column flex-sm-row gap-2 flex-shrink-0">
                <a
                    href="https://github.com/dvictor316/SmatBook/releases/latest/download/SmartProbook-Offline-Installer.msi"
                    class="btn btn-primary"
                >
                    <i class="fas fa-download me-2" aria-hidden="true"></i>Install offline app
                </a>
                <a
                    href="smartprobook://open/pos"
                    class="btn btn-outline-primary"
                >
                    <i class="fas fa-arrow-up-right-from-square me-2" aria-hidden="true"></i>Open offline app
                </a>
            </div>
        </div>
    </div>
@endif
