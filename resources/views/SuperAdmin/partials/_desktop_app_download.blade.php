@if(isset($company) && $company)
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-body d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-3">
            <div>
                <h5 class="mb-1"><i class="fas fa-laptop me-2 text-primary" aria-hidden="true"></i>Use SmartProbook offline</h5>
                <p class="text-muted mb-0">Install the Windows app, then sign in and sync your product catalogue while online before using offline sales.</p>
            </div>
            <a
                href="https://github.com/dvictor316/SmatBook/releases/latest"
                class="btn btn-primary flex-shrink-0"
                target="_blank"
                rel="noopener noreferrer"
            >
                <i class="fas fa-download me-2" aria-hidden="true"></i>Download Windows app
            </a>
        </div>
    </div>
@endif
