<style>
    :root {
        --sb-sidebar-w: 270px;
        --sb-sidebar-deploy-w: 270px;
        --sb-sidebar-collapsed: 80px;
        --sb-sidebar-icon-collapsed: 70px;
        --sb-header-h: 70px;
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
        padding: 110px 2rem 3rem;
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
            padding-top: 90px;
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
        overflow-x: hidden;
        overflow-y: visible;
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

    /* Force jQuery slimscroll wrappers to allow actual inner scrolling. */
    .sidebar .slimScrollDiv,
    #sidebar .slimScrollDiv {
        height: calc(100vh - var(--sb-header-h)) !important;
        max-height: calc(100vh - var(--sb-header-h)) !important;
        overflow: visible !important;
    }

    .sidebar .slimScrollDiv > .sidebar-inner,
    #sidebar .slimScrollDiv > .sidebar-inner {
        height: 100% !important;
        max-height: 100% !important;
        overflow-y: auto !important;
        overflow-x: hidden !important;
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
</style>
