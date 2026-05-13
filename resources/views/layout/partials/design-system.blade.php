<style>
    :root {
        --sb-sidebar-w: 270px;
        --sb-sidebar-deploy-w: 270px;
        --sb-sidebar-collapsed: 80px;
        --sb-sidebar-icon-collapsed: 70px;
        --sb-header-h: 76px;
        --sb-bg: #f8fafc;
        --sb-surface: #ffffff;
        --sb-border: #e2e8f0;
        --sb-border-soft: #f1f5f9;
        --sb-text: #1f2937;
        --sb-muted: #64748b;
        --sb-primary: #1e40af;
        --sb-primary-2: #3b82f6;
        --sb-success: #10b981;
        --sb-warning: #f59e0b;
        --sb-danger: #ef4444;
    }

    .sb-shell {
        margin-left: var(--sb-sidebar-w);
        width: calc(100% - var(--sb-sidebar-w));
        padding: 84px 1.25rem 1.5rem;
        min-height: 100vh;
        background: var(--sb-bg);
        transition: margin-left 0.3s, width 0.3s;
    }

    body.sidebar-icon-only .sb-shell {
        margin-left: var(--sb-sidebar-collapsed);
        width: calc(100% - var(--sb-sidebar-collapsed));
    }

    @media (max-width: 991.98px) {
        .sb-shell {
            margin-left: 0;
            width: 100%;
            padding-top: 78px;
        }
    }

    /* Global standard sidebar-aware wrappers (250px). */
    #main-content-wrapper,
    .pos-content-area,
    .report-page-wrapper,
    .pos-full-page-wrapper {
        margin-left: var(--sb-sidebar-w) !important;
        width: calc(100% - var(--sb-sidebar-w)) !important;
    }

    body.sidebar-collapsed #main-content-wrapper,
    body.sidebar-collapsed .pos-content-area,
    body.sidebar-collapsed .report-page-wrapper,
    body.sidebar-collapsed .pos-full-page-wrapper,
    body.mini-sidebar #main-content-wrapper,
    body.mini-sidebar .pos-content-area,
    body.mini-sidebar .report-page-wrapper,
    body.mini-sidebar .pos-full-page-wrapper,
    body.sidebar-icon-only #main-content-wrapper,
    body.sidebar-icon-only .pos-content-area,
    body.sidebar-icon-only .report-page-wrapper,
    body.sidebar-icon-only .pos-full-page-wrapper {
        margin-left: var(--sb-sidebar-collapsed) !important;
        width: calc(100% - var(--sb-sidebar-collapsed)) !important;
    }

    /* Deployment wrappers (270px). */
    #deployment-wrapper,
    #profile-wrapper,
    #subscription-wrapper,
    #companies-wrapper,
    #commissions-wrapper,
    #settings-wrapper,
    #payments-wrapper,
    #register-wrapper,
    #layout-wrapper,
    #profile-content,
    .page-content-wrapper {
        margin-left: var(--sb-sidebar-deploy-w) !important;
        width: calc(100% - var(--sb-sidebar-deploy-w)) !important;
    }

    body.sidebar-collapsed #deployment-wrapper,
    body.sidebar-collapsed #profile-wrapper,
    body.sidebar-collapsed #subscription-wrapper,
    body.sidebar-collapsed #companies-wrapper,
    body.sidebar-collapsed #commissions-wrapper,
    body.sidebar-collapsed #settings-wrapper,
    body.sidebar-collapsed #payments-wrapper,
    body.sidebar-collapsed #register-wrapper,
    body.sidebar-collapsed #layout-wrapper,
    body.sidebar-collapsed #profile-content,
    body.sidebar-collapsed .page-content-wrapper,
    body.mini-sidebar #deployment-wrapper,
    body.mini-sidebar #profile-wrapper,
    body.mini-sidebar #subscription-wrapper,
    body.mini-sidebar #companies-wrapper,
    body.mini-sidebar #commissions-wrapper,
    body.mini-sidebar #settings-wrapper,
    body.mini-sidebar #payments-wrapper,
    body.mini-sidebar #register-wrapper,
    body.mini-sidebar #layout-wrapper,
    body.mini-sidebar #profile-content,
    body.mini-sidebar .page-content-wrapper {
        margin-left: var(--sb-sidebar-collapsed) !important;
        width: calc(100% - var(--sb-sidebar-collapsed)) !important;
    }

    body.sidebar-icon-only #deployment-wrapper,
    body.sidebar-icon-only #profile-wrapper,
    body.sidebar-icon-only #subscription-wrapper,
    body.sidebar-icon-only #companies-wrapper,
    body.sidebar-icon-only #commissions-wrapper,
    body.sidebar-icon-only #settings-wrapper,
    body.sidebar-icon-only #payments-wrapper,
    body.sidebar-icon-only #register-wrapper,
    body.sidebar-icon-only #layout-wrapper,
    body.sidebar-icon-only #profile-content,
    body.sidebar-icon-only .page-content-wrapper {
        margin-left: var(--sb-sidebar-icon-collapsed) !important;
        width: calc(100% - var(--sb-sidebar-icon-collapsed)) !important;
    }

    @media (max-width: 991.98px) {
        #main-content-wrapper,
        .pos-content-area,
        .report-page-wrapper,
        .pos-full-page-wrapper,
        #deployment-wrapper,
        #profile-wrapper,
        #subscription-wrapper,
        #companies-wrapper,
        #commissions-wrapper,
        #settings-wrapper,
        #payments-wrapper,
        #register-wrapper,
        #layout-wrapper,
        #profile-content,
        .page-content-wrapper {
            margin-left: 0 !important;
            width: 100% !important;
        }
    }

    .sb-shell,
    #main-content-wrapper,
    .pos-content-area,
    .report-page-wrapper,
    .pos-full-page-wrapper,
    #deployment-wrapper,
    #profile-wrapper,
    #subscription-wrapper,
    #companies-wrapper,
    #commissions-wrapper,
    #settings-wrapper,
    #payments-wrapper,
    #register-wrapper,
    #layout-wrapper,
    #profile-content,
    .page-content-wrapper {
        box-sizing: border-box;
        max-width: 100%;
        min-width: 0;
        overflow-x: hidden;
        overflow-y: visible;
        margin-top: 0 !important;
        padding-top: 0 !important;
        transition: margin-left 0.3s ease, width 0.3s ease, max-width 0.3s ease, padding 0.3s ease;
    }

    /* Global sidebar scroll behavior (all roles, all pages). */
    #sidebar,
    .sidebar,
    #deploymentSidebar,
    .deployment-sidebar {
        max-height: 100vh;
    }

    #sidebar,
    .sidebar {
        width: var(--sb-sidebar-w) !important;
    }

    /* Respect collapsed state for all dashboard sidebars */
    body.sidebar-collapsed #sidebar,
    body.sidebar-collapsed .sidebar,
    body.mini-sidebar #sidebar,
    body.mini-sidebar .sidebar,
    body.sidebar-icon-only #sidebar,
    body.sidebar-icon-only .sidebar {
        width: var(--sb-sidebar-collapsed) !important;
    }

    @media (min-width: 992px) {
        #sidebar,
        .sidebar,
        #deploymentSidebar,
        .deployment-sidebar {
            top: var(--sb-header-h) !important;
            height: calc(100vh - var(--sb-header-h)) !important;
            max-height: calc(100vh - var(--sb-header-h)) !important;
        }
    }

    #sidebar .sidebar-inner,
    .sidebar .sidebar-inner,
    #sidebar-menu,
    #deploymentSidebar,
    .deployment-sidebar {
        overflow-y: auto !important;
        overflow-x: hidden !important;
        -webkit-overflow-scrolling: touch;
        scrollbar-width: thin;
    }

    #sidebar .sidebar-inner,
    .sidebar .sidebar-inner {
        height: calc(100vh - var(--sb-header-h)) !important;
        max-height: calc(100vh - var(--sb-header-h)) !important;
    }

    #deploymentSidebar,
    .deployment-sidebar {
        height: calc(100vh - var(--sb-header-h)) !important;
        max-height: calc(100vh - var(--sb-header-h)) !important;
    }

    .sidebar .sidebar-inner.slimscroll,
    #sidebar .sidebar-inner.slimscroll {
        scrollbar-width: thin;
    }

    .sb-card {
        background: var(--sb-surface);
        border-radius: 14px;
        border: 1px solid var(--sb-border);
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.04);
    }

    .sb-card-header {
        padding: 1.1rem 1.4rem;
        border-bottom: 1px solid var(--sb-border);
        background: var(--sb-surface);
    }

    .sb-label {
        font-size: 12px;
        font-weight: 600;
        color: #475569;
        margin-bottom: 6px;
        text-transform: uppercase;
        letter-spacing: 0.3px;
    }

    .sb-input,
    .sb-select {
        border-radius: 8px;
        border-color: var(--sb-border);
        padding: 0.62rem 0.9rem;
        font-size: 14px;
    }

    .sb-input:focus,
    .sb-select:focus {
        border-color: var(--sb-primary);
        box-shadow: 0 0 0 3px rgba(30, 64, 175, 0.08);
    }

    .sb-btn-primary {
        background: var(--sb-primary);
        color: #fff;
        border: 0;
        border-radius: 9px;
        padding: 10px 26px;
        font-weight: 600;
        font-size: 14px;
        transition: all 0.2s;
    }

    .sb-btn-primary:hover {
        background: #1d3a9f;
        color: #fff;
        transform: translateY(-1px);
        box-shadow: 0 4px 12px rgba(30, 64, 175, 0.25);
    }

    .sb-btn-primary:disabled {
        background: #94a3b8;
        color: #fff;
        cursor: not-allowed;
        transform: none;
        box-shadow: none;
    }

    .sb-btn-outline {
        background: #fff;
        color: var(--sb-muted);
        border: 1.5px solid var(--sb-border);
        border-radius: 9px;
        padding: 10px 22px;
        font-weight: 600;
        font-size: 14px;
        transition: all 0.2s;
    }

    .sb-btn-outline:hover {
        background: #f8fafc;
        border-color: #94a3b8;
        color: #374151;
    }

    .sb-promo {
        border-radius: 10px;
        padding: 13px 16px;
        font-size: 13px;
        border-left: 4px solid var(--sb-primary-2);
        background: linear-gradient(135deg, #eff6ff, #dbeafe);
    }

    .sb-promo-warning {
        border-left-color: var(--sb-warning);
        background: linear-gradient(135deg, #fefce8, #fef9c3);
    }

    .sb-summary-row {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 10px 0;
        border-bottom: 1px solid var(--sb-border-soft);
        font-size: 13px;
    }

    .sb-summary-row:last-child {
        border-bottom: 0;
    }

    /* Global button contrast guard:
       many pages mix shared .btn hover styles with helper classes like text-white.
       When a hover flips a button to a light surface, force a readable foreground too. */
    .btn:not(.btn-close):not(.btn-link) {
        --spb-btn-hover-bg: initial;
        --spb-btn-hover-border: initial;
        --spb-btn-hover-color: inherit;
    }

    .btn:not(.btn-close):not(.btn-link):hover,
    .btn:not(.btn-close):not(.btn-link):focus-visible,
    .btn:not(.btn-close):not(.btn-link):active {
        color: var(--spb-btn-hover-color) !important;
    }

    .btn:not(.btn-close):not(.btn-link):hover *,
    .btn:not(.btn-close):not(.btn-link):focus-visible *,
    .btn:not(.btn-close):not(.btn-link):active * {
        color: inherit !important;
    }

    .btn-primary,
    .btn-upload,
    .btn-primary.tax.active,
    .btn-primary.taxs,
    .btn-primary.loss,
    .btn-primary.profit-loss {
        --spb-btn-hover-bg: #ffffff;
        --spb-btn-hover-border: #1e40af;
        --spb-btn-hover-color: #1e3a8a;
    }

    .btn-primary:hover,
    .btn-primary:focus-visible,
    .btn-primary:active,
    .btn-upload:hover,
    .btn-upload:focus-visible,
    .btn-upload:active,
    .btn-primary.tax.active:hover,
    .btn-primary.taxs:hover,
    .btn-primary.loss:hover,
    .btn-primary.profit-loss:hover {
        background: var(--spb-btn-hover-bg) !important;
        background-color: var(--spb-btn-hover-bg) !important;
        border-color: var(--spb-btn-hover-border) !important;
        color: var(--spb-btn-hover-color) !important;
        box-shadow: none !important;
    }

    .btn-outline-primary,
    .btn-outline-secondary,
    .btn-outline-success,
    .btn-outline-danger,
    .btn-outline-warning,
    .btn-outline-dark {
        --spb-btn-hover-color: #ffffff;
    }

    .btn-outline-primary:hover,
    .btn-outline-primary:focus-visible,
    .btn-outline-primary:active {
        background: #1e40af !important;
        background-color: #1e40af !important;
        border-color: #1e40af !important;
        color: #ffffff !important;
    }

    .btn-outline-secondary:hover,
    .btn-outline-secondary:focus-visible,
    .btn-outline-secondary:active {
        background: #475569 !important;
        background-color: #475569 !important;
        border-color: #475569 !important;
        color: #ffffff !important;
    }

    .btn-outline-success:hover,
    .btn-outline-success:focus-visible,
    .btn-outline-success:active {
        background: #15803d !important;
        background-color: #15803d !important;
        border-color: #15803d !important;
        color: #ffffff !important;
    }

    .btn-outline-danger:hover,
    .btn-outline-danger:focus-visible,
    .btn-outline-danger:active {
        background: #b91c1c !important;
        background-color: #b91c1c !important;
        border-color: #b91c1c !important;
        color: #ffffff !important;
    }

    .btn-outline-warning:hover,
    .btn-outline-warning:focus-visible,
    .btn-outline-warning:active {
        background: #b45309 !important;
        background-color: #b45309 !important;
        border-color: #b45309 !important;
        color: #ffffff !important;
    }

    .btn-outline-dark:hover,
    .btn-outline-dark:focus-visible,
    .btn-outline-dark:active {
        background: #111827 !important;
        background-color: #111827 !important;
        border-color: #111827 !important;
        color: #ffffff !important;
    }

    .btn-white,
    .btn-white-outline,
    .btn-light,
    .btn.btn-light.border,
    .btn.btn-white.border {
        --spb-btn-hover-bg: #ffffff;
        --spb-btn-hover-border: #cbd5e1;
        --spb-btn-hover-color: #1f2937;
    }

    .btn-white:hover,
    .btn-white:focus-visible,
    .btn-white:active,
    .btn-white-outline:hover,
    .btn-white-outline:focus-visible,
    .btn-white-outline:active,
    .btn-light:hover,
    .btn-light:focus-visible,
    .btn-light:active,
    .btn.btn-light.border:hover,
    .btn.btn-white.border:hover {
        background: var(--spb-btn-hover-bg) !important;
        background-color: var(--spb-btn-hover-bg) !important;
        border-color: var(--spb-btn-hover-border) !important;
        color: var(--spb-btn-hover-color) !important;
        box-shadow: none !important;
    }

    .btn-secondary {
        --spb-btn-hover-bg: #334155;
        --spb-btn-hover-border: #334155;
        --spb-btn-hover-color: #ffffff;
    }

    .btn-secondary:hover,
    .btn-secondary:focus-visible,
    .btn-secondary:active {
        background: var(--spb-btn-hover-bg) !important;
        background-color: var(--spb-btn-hover-bg) !important;
        border-color: var(--spb-btn-hover-border) !important;
        color: var(--spb-btn-hover-color) !important;
    }

    [data-layout-mode=dark] .btn-primary,
    [data-layout-mode=dark] .btn-upload,
    [data-layout-mode=dark] .btn-white,
    [data-layout-mode=dark] .btn-white-outline,
    [data-layout-mode=dark] .btn-light {
        --spb-btn-hover-bg: #f8fafc;
        --spb-btn-hover-border: #cbd5e1;
        --spb-btn-hover-color: #111827;
    }

    /* Filled button hover inversion:
       blue -> white/blue, dark -> white/dark, green -> white/green, etc.
       This keeps hover text readable across business, plan, and admin pages. */
    .btn-primary,
    .btn-upload,
    .btn-primary.tax.active,
    .btn-primary.taxs,
    .btn-primary.loss,
    .btn-primary.profit-loss,
    .sb-btn-primary {
        --spb-btn-hover-bg: #ffffff;
        --spb-btn-hover-border: #1e40af;
        --spb-btn-hover-color: #1e40af;
    }

    .btn-secondary,
    .btn-dark {
        --spb-btn-hover-bg: #ffffff;
        --spb-btn-hover-border: #334155;
        --spb-btn-hover-color: #1f2937;
    }

    .btn-success {
        --spb-btn-hover-bg: #ffffff;
        --spb-btn-hover-border: #16a34a;
        --spb-btn-hover-color: #15803d;
    }

    .btn-danger {
        --spb-btn-hover-bg: #ffffff;
        --spb-btn-hover-border: #dc2626;
        --spb-btn-hover-color: #b91c1c;
    }

    .btn-warning {
        --spb-btn-hover-bg: #ffffff;
        --spb-btn-hover-border: #d97706;
        --spb-btn-hover-color: #92400e;
    }

    .btn-info {
        --spb-btn-hover-bg: #ffffff;
        --spb-btn-hover-border: #0284c7;
        --spb-btn-hover-color: #0369a1;
    }

    .btn-primary:hover,
    .btn-primary:focus-visible,
    .btn-primary:active,
    .btn-upload:hover,
    .btn-upload:focus-visible,
    .btn-upload:active,
    .btn-primary.tax.active:hover,
    .btn-primary.taxs:hover,
    .btn-primary.loss:hover,
    .btn-primary.profit-loss:hover,
    .sb-btn-primary:hover,
    .sb-btn-primary:focus-visible,
    .sb-btn-primary:active,
    .btn-secondary:hover,
    .btn-secondary:focus-visible,
    .btn-secondary:active,
    .btn-dark:hover,
    .btn-dark:focus-visible,
    .btn-dark:active,
    .btn-success:hover,
    .btn-success:focus-visible,
    .btn-success:active,
    .btn-danger:hover,
    .btn-danger:focus-visible,
    .btn-danger:active,
    .btn-warning:hover,
    .btn-warning:focus-visible,
    .btn-warning:active,
    .btn-info:hover,
    .btn-info:focus-visible,
    .btn-info:active {
        background: var(--spb-btn-hover-bg) !important;
        background-color: var(--spb-btn-hover-bg) !important;
        border-color: var(--spb-btn-hover-border) !important;
        color: var(--spb-btn-hover-color) !important;
        -webkit-text-fill-color: var(--spb-btn-hover-color) !important;
        box-shadow: 0 0 0 1px var(--spb-btn-hover-border) inset !important;
    }

    .btn-primary:hover *,
    .btn-primary:focus-visible *,
    .btn-primary:active *,
    .btn-upload:hover *,
    .btn-upload:focus-visible *,
    .btn-upload:active *,
    .btn-secondary:hover *,
    .btn-secondary:focus-visible *,
    .btn-secondary:active *,
    .btn-dark:hover *,
    .btn-dark:focus-visible *,
    .btn-dark:active *,
    .btn-success:hover *,
    .btn-success:focus-visible *,
    .btn-success:active *,
    .btn-danger:hover *,
    .btn-danger:focus-visible *,
    .btn-danger:active *,
    .btn-warning:hover *,
    .btn-warning:focus-visible *,
    .btn-warning:active *,
    .btn-info:hover *,
    .btn-info:focus-visible *,
    .btn-info:active *,
    .sb-btn-primary:hover *,
    .sb-btn-primary:focus-visible *,
    .sb-btn-primary:active * {
        color: inherit !important;
        -webkit-text-fill-color: currentColor !important;
    }
</style>
