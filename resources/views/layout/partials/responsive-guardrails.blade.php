<style>
    html,
    body {
        max-width: 100%;
        overflow-x: hidden;
        overflow-x: clip;
    }

    img,
    video,
    canvas,
    svg {
        max-width: 100%;
    }

    .main-wrapper,
    .page-wrapper,
    .page-wrapper .content,
    .container,
    .container-fluid,
    .row,
    .row > *,
    .card,
    .card-body,
    .tab-content,
    .tab-pane {
        min-width: 0;
    }

    .table-responsive,
    .dataTables_wrapper,
    .dataTables_scroll,
    .dataTables_scrollBody {
        max-width: 100%;
    }

    .table-responsive,
    .dataTables_scrollBody {
        overflow-x: auto;
        overscroll-behavior-inline: contain;
        -webkit-overflow-scrolling: touch;
    }

    .nav-tabs,
    .nav-pills.responsive-scroll,
    .page-tabs {
        max-width: 100%;
        overflow-x: auto;
        overflow-y: hidden;
        flex-wrap: nowrap;
        scrollbar-width: thin;
        -webkit-overflow-scrolling: touch;
    }

    .nav-tabs .nav-item,
    .page-tabs > * {
        flex: 0 0 auto;
    }

    .select2-container,
    .select2-container--default,
    .choices,
    .choices__inner {
        max-width: 100%;
    }

    .select2-container {
        width: 100% !important;
    }

    .dropdown-menu {
        max-width: min(94vw, 420px);
        overflow-wrap: anywhere;
    }

    .page-wrapper :where(h1, h2, h3, h4, h5, h6, p, label, a, button, th, td, .badge) {
        overflow-wrap: anywhere;
    }

    @media (max-width: 991.98px) {
        .page-wrapper,
        .sb-shell,
        #main-content-wrapper,
        .page-content-wrapper,
        .report-page-wrapper,
        .pos-content-area,
        .pos-full-page-wrapper {
            margin-left: 0 !important;
            width: 100% !important;
            max-width: 100% !important;
        }

        .page-header .row,
        .page-header .d-flex,
        .card-header.d-flex,
        .filter-row,
        .filter-actions,
        .toolbar,
        .page-actions {
            row-gap: 0.75rem;
        }

        .modal-dialog:not(.modal-fullscreen) {
            width: auto;
            max-width: calc(100vw - 24px);
            margin: 12px auto;
        }
    }

    @media (max-width: 767.98px) {
        .page-wrapper .content,
        .page-wrapper .content.container-fluid,
        .sb-shell,
        .page-content-wrapper {
            padding-left: 12px !important;
            padding-right: 12px !important;
        }

        .page-header .row,
        .page-header .d-flex,
        .card-header.d-flex,
        .filter-row,
        .filter-actions,
        .toolbar,
        .page-actions,
        .dataTables_wrapper .row {
            flex-wrap: wrap !important;
        }

        .page-header .col-auto,
        .page-actions,
        .filter-actions {
            max-width: 100%;
        }

        .page-actions .btn,
        .filter-actions .btn {
            white-space: normal;
        }

        .dataTables_wrapper .dataTables_length,
        .dataTables_wrapper .dataTables_filter,
        .dataTables_wrapper .dataTables_info,
        .dataTables_wrapper .dataTables_paginate {
            width: 100%;
            max-width: 100%;
            margin: 0.35rem 0;
            text-align: left !important;
        }

        .dataTables_wrapper .dataTables_filter label,
        .dataTables_wrapper .dataTables_filter input {
            width: 100%;
            margin-left: 0 !important;
        }

        .pagination {
            max-width: 100%;
            justify-content: flex-start;
            overflow-x: auto;
            flex-wrap: nowrap;
            padding-bottom: 4px;
            -webkit-overflow-scrolling: touch;
        }

        input:not([type="checkbox"]):not([type="radio"]):not([type="range"]),
        select,
        textarea {
            max-width: 100%;
            font-size: max(16px, 1em);
        }

        .modal-body,
        .modal-header,
        .modal-footer {
            padding-left: 1rem;
            padding-right: 1rem;
        }
    }

    @media (max-width: 479.98px) {
        .page-wrapper .card-body,
        .page-wrapper .card-header,
        .page-wrapper .card-footer {
            padding-left: 0.875rem;
            padding-right: 0.875rem;
        }

        .btn-group-responsive,
        .page-actions,
        .filter-actions {
            width: 100%;
        }

        .btn-group-responsive > .btn,
        .page-actions > .btn,
        .filter-actions > .btn {
            flex: 1 1 100%;
        }
    }
</style>
