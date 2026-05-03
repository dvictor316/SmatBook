@extends('layout.mainlayout')

@section('title', 'Super Admin Command Center')

@section('content')
@php
    $recentUsers = $recentUsers ?? collect();
@endphp

<style>
    @import url('https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@500;600;700;800&family=Space+Grotesk:wght@500;700&display=swap');

    :root {
        --dash-ink: #0f172a;
        --dash-muted: #5f6b7a;
        --dash-line: #dbe5f0;
        --dash-surface: #ffffff;
        --dash-surface-soft: #f8fbff;
        --kpi-display-font: 'Space Grotesk', 'Plus Jakarta Sans', 'Inter', sans-serif;
        --kpi-ui-font: 'Plus Jakarta Sans', 'Inter', sans-serif;
        --kpi-indigo-start: #312e81;
        --kpi-indigo-end: #4f46e5;
        --kpi-emerald-start: #065f46;
        --kpi-emerald-end: #10b981;
        --kpi-cyan-start: #164e63;
        --kpi-cyan-end: #06b6d4;
        --kpi-amber-start: #92400e;
        --kpi-amber-end: #f59e0b;
        --kpi-rose-start: #881337;
        --kpi-rose-end: #f43f5e;
        --kpi-slate-start: #0f172a;
        --kpi-slate-end: #334155;
    }

    #main-content-wrapper,
    #main-content-wrapper * {
        -webkit-font-smoothing: antialiased;
        -moz-osx-font-smoothing: grayscale;
        text-rendering: optimizeLegibility;
    }

    /* Base wrapper transition for smooth toggling */
    #main-content-wrapper {
        transition: margin-left 0.3s ease, width 0.3s ease;
        width: 100%;
        overflow-x: hidden;
        padding-top: 16px;
        color: var(--dash-ink);
        background:
            radial-gradient(1200px 320px at 4% 0%, rgba(96, 165, 250, 0.12) 0%, rgba(96, 165, 250, 0) 58%),
            radial-gradient(880px 260px at 96% 4%, rgba(251, 191, 36, 0.12) 0%, rgba(251, 191, 36, 0) 54%),
            radial-gradient(760px 240px at 50% 100%, rgba(244, 114, 182, 0.06) 0%, rgba(244, 114, 182, 0) 58%),
            linear-gradient(180deg, #f8fbff 0%, #f5f9ff 46%, #fffdf7 100%);
        border-radius: 28px 0 0 0;
    }

    /* DESKTOP: Fixed 250px Sidebar Offset */
    @media (min-width: 992px) {
        #main-content-wrapper {
            margin-left: 250px;
            width: calc(100% - 250px);
        }

        /* State when sidebar is toggled closed */
        body.sidebar-collapsed #main-content-wrapper,
        body.sidebar-icon-only #main-content-wrapper,
        body.mini-sidebar #main-content-wrapper {
            margin-left: 70px;
            width: calc(100% - 70px);
        }
    }

    /* MOBILE: Full width, no offset, adjust padding */
    @media (max-width: 991.98px) {
        #main-content-wrapper {
            margin-left: 0;
            width: 100%;
            padding-top: 12px;
            border-radius: 0;
        }
    }

    /* Responsive Header Flex */
    .header-responsive-flex {
        display: flex;
        align-items: center;
        justify-content: space-between;
        flex-wrap: wrap;
        gap: 15px;
    }
    .header-responsive-flex h3 {
        font-size: clamp(1.35rem, 2vw, 1.8rem);
        line-height: 1.05;
        letter-spacing: -0.03em;
        color: var(--dash-ink) !important;
    }
    .header-responsive-flex p {
        color: var(--dash-muted) !important;
    }

    /* Heatmap Grid Styles */
    .heatmap-grid {
        display: grid;
        grid-template-columns: 40px repeat(24, 1fr);
        gap: 2px;
        font-size: 10px;
    }
    .heatmap-cell {
        height: 25px;
        border-radius: 2px;
        background-color: #ebedf2;
        transition: all 0.2s;
    }
    .heatmap-cell:hover {
        transform: scale(1.2);
        border: 1px solid #333;
        z-index: 10;
        cursor: pointer;
    }
    .heatmap-label {
        display: flex;
        align-items: center;
        justify-content: flex-end;
        padding-right: 5px;
        font-weight: bold;
        color: #6c757d;
    }
    .heatmap-legend {
        display: flex;
        gap: 10px;
        align-items: center;
        font-size: 12px;
        margin-top: 10px;
    }
    .legend-box { width: 15px; height: 15px; border-radius: 2px; }

    /* Map Container Styles */
    #regionMap {
        height: 400px;
        width: 100%;
        border-radius: 8px;
    }
    #regionMap.map-interactive {
        cursor: grab;
    }
    #regionMap.map-interactive:active {
        cursor: grabbing;
    }

    /* Chart height consistency */
    .chart-container {
        position: relative;
        height: 350px;
    }
    .chart-container.chart-container-sm {
        height: 220px;
    }

    /* Pulse animation for live indicators */
    @keyframes pulse {
        0%, 100% { opacity: 1; }
        50% { opacity: 0.5; }
    }
    .animate-pulse {
        animation: pulse 2s cubic-bezier(0.4, 0, 0.6, 1) infinite;
    }

    /* Dashboard density improvements */
    .dashboard-tight .row {
        --bs-gutter-y: 0.7rem;
        --bs-gutter-x: 0.7rem;
    }
    .dashboard-tight .grid-margin { margin-bottom: 0.7rem !important; }
    .dashboard-tight .stretch-card > .card { height: 100%; }
    .dashboard-tight .card-subtitle { margin-bottom: 0.65rem !important; }
    .dashboard-tight .card {
        border-radius: 18px !important;
        border: 1px solid var(--dash-line);
        background: linear-gradient(180deg, var(--dash-surface) 0%, #fcfdff 100%);
        box-shadow: 0 14px 34px rgba(15, 23, 42, 0.05);
    }
    .dashboard-tight .card-body {
        padding: 0.95rem 1rem;
    }
    .dashboard-tight .card-header,
    .dashboard-tight .card-title {
        margin-bottom: 0.6rem !important;
    }
    .card-title.card-title-dash,
    .card-title-dash {
        font-size: 1.02rem;
        font-weight: 800;
        letter-spacing: -0.02em;
        color: var(--dash-ink) !important;
    }
    .card-subtitle,
    .card-subtitle-dash {
        color: var(--dash-muted) !important;
        font-size: 0.8rem !important;
        line-height: 1.5;
    }
    .live-badge-soft {
        font-size: 10px;
        font-weight: 800;
        letter-spacing: .4px;
        text-transform: uppercase;
        padding: 4px 8px;
        border-radius: 999px;
        background: rgba(25, 135, 84, 0.12);
        color: #198754;
    }
    .kpi-compact {
        border: 1px solid #dce8f4;
        border-radius: 14px;
        background: #ffffff;
        padding: 12px 13px;
        box-shadow: inset 0 1px 0 rgba(255,255,255,0.8);
    }
    .kpi-compact .label {
        font-size: 10px;
        font-weight: 800;
        color: #5b6676;
        text-transform: uppercase;
        letter-spacing: .08em;
        margin-bottom: 6px;
    }
    .kpi-compact .value {
        font-weight: 800;
        color: var(--dash-ink);
        font-size: 1.02rem;
        line-height: 1.1;
        letter-spacing: -0.02em;
    }
    .kpi-grid-dense {
        border: 1px solid #e5edf8;
        border-radius: 16px;
        background: linear-gradient(135deg, rgba(255,255,255,0.98) 0%, rgba(246,250,255,0.96) 100%);
        padding: 12px;
    }
    .kpi-dense-item {
        border: 1px solid #e8eff8;
        border-radius: 12px;
        background: #fff;
        padding: 10px 12px;
        height: 100%;
    }
    .kpi-dense-item .label {
        font-size: 10px;
        font-weight: 800;
        color: #5f6b7a;
        text-transform: uppercase;
        letter-spacing: .08em;
        margin-bottom: 6px;
    }
    .kpi-dense-item .value {
        font-size: 0.98rem;
        font-weight: 800;
        color: var(--dash-ink);
        line-height: 1.1;
        letter-spacing: -0.02em;
    }
    .metric-wall {
        border: 1px solid #e5edf8;
        border-radius: 18px;
        background: linear-gradient(135deg, rgba(255,255,255,0.98) 0%, rgba(240,247,255,0.96) 100%);
        padding: 14px;
    }
    .metric-wall-item {
        border: 1px solid #e8eff8;
        border-radius: 14px;
        background: #fff;
        padding: 12px 14px;
        height: 100%;
    }
    .metric-wall-item .label {
        font-size: 10px;
        font-weight: 800;
        color: #5f6b7a;
        text-transform: uppercase;
        letter-spacing: .08em;
        margin-bottom: 6px;
    }
    .metric-wall-item .value {
        font-size: 0.98rem;
        font-weight: 800;
        color: var(--dash-ink);
        line-height: 1.1;
        letter-spacing: -0.02em;
    }
    .dashboard-row-balanced {
        align-items: flex-start;
    }
    .dashboard-row-balanced .dashboard-stack {
        height: auto;
    }
    .dashboard-row-balanced .dashboard-stack > .card:last-child {
        flex: 0 0 auto;
    }
    .dashboard-stack {
        display: flex;
        flex-direction: column;
        gap: 0.7rem;
        min-width: 0;
    }
    .dashboard-stack > .card {
        margin-bottom: 0 !important;
        height: auto !important;
        flex: 0 0 auto;
    }
    .chartjs-wrapper {
        position: relative;
        height: 250px;
        min-height: 250px;
    }
    .chartjs-wrapper canvas,
    .chart-container canvas {
        width: 100% !important;
        height: 100% !important;
    }
    .leaflet-container {
        z-index: 0;
    }
    .dashboard-split-card {
        display: flex;
        flex-direction: column;
        gap: 0.7rem;
    }
    .workspace-readiness-board {
        gap: 0.5rem;
    }
    .workspace-readiness-board .summary-fill {
        padding: 9px 11px;
        border-radius: 12px;
    }
    .workspace-readiness-board .summary-fill .label {
        font-size: 9px;
        margin-bottom: 0.18rem;
    }
    .workspace-readiness-board .summary-fill .value {
        font-size: 0.92rem;
        line-height: 1.15;
    }
    .workspace-readiness-board .summary-fill .small {
        font-size: 0.72rem;
    }
    .dashboard-split-card .table-responsive,
    .dashboard-split-card .list-wrapper {
        max-height: 260px !important;
        overflow-x: auto;
        overflow-y: auto;
        min-height: 0;
    }
    .table-wrap-lock {
        overflow-x: hidden !important;
    }
    .table-wrap-lock table {
        width: 100%;
        table-layout: fixed;
    }
    .table-wrap-lock th,
    .table-wrap-lock td {
        white-space: normal;
        vertical-align: top;
    }
    .dashboard-micro-grid {
        display: grid;
        grid-template-columns: repeat(3, minmax(0, 1fr));
        gap: 0.6rem;
    }
    .dashboard-micro-grid .summary-fill {
        height: 100%;
    }
    .dashboard-side-fill {
        display: grid;
        grid-template-columns: repeat(2, minmax(0, 1fr));
        gap: 0.6rem;
    }
    .summary-fill {
        border: 1px solid #dce7f3;
        border-radius: 14px;
        background: linear-gradient(135deg, #ffffff 0%, #f8fbff 100%);
        padding: 12px 13px;
        box-shadow: inset 0 1px 0 rgba(255,255,255,0.75);
    }
    .summary-fill .label {
        font-size: 10px;
        font-weight: 800;
        color: #5f6b7a;
        text-transform: uppercase;
        letter-spacing: .08em;
        margin-bottom: 6px;
    }
    .summary-fill .value {
        font-size: 0.98rem;
        font-weight: 800;
        color: var(--dash-ink);
        line-height: 1.1;
        letter-spacing: -0.02em;
    }
    .tone-card {
        border: 1px solid transparent;
        border-radius: 16px;
        box-shadow: 0 18px 36px rgba(15, 23, 42, 0.14);
        position: relative;
        overflow: hidden;
    }
    .tone-card::after {
        content: "";
        position: absolute;
        inset: auto -28px -38px auto;
        width: 140px;
        height: 140px;
        border-radius: 50%;
        background: rgba(255,255,255,0.12);
        pointer-events: none;
    }
    .tone-card .mini-label,
    .tone-card small,
    .tone-card .tone-note {
        font-family: var(--kpi-ui-font);
        color: inherit;
        opacity: 0.82;
        letter-spacing: 0.04em;
        text-transform: uppercase;
        font-weight: 700;
    }
    .tone-card .tone-value,
    .tone-card h5,
    .tone-card h3,
    .tone-card .mdi {
        font-family: var(--kpi-display-font);
        color: inherit;
    }
    .tone-card .tone-value {
        font-size: clamp(1.45rem, 2vw, 2rem);
        letter-spacing: -0.04em;
    }
    .tone-card .card-body {
        padding: 1rem 1.05rem !important;
    }
    .tone-card .mdi {
        opacity: 0.9;
    }
    .tone-card.tone-rose {
        background: linear-gradient(135deg, #fff1f5 0%, #ffe4eb 100%);
        border-color: rgba(244, 114, 182, 0.28);
        color: #9f1239;
    }
    .tone-card.tone-violet {
        background: linear-gradient(135deg, #f5f3ff 0%, #ede9fe 100%);
        border-color: rgba(139, 92, 246, 0.24);
        color: #5b21b6;
    }
    .tone-card.tone-cobalt {
        background: linear-gradient(135deg, #eff6ff 0%, #dbeafe 100%);
        border-color: rgba(59, 130, 246, 0.24);
        color: #1d4ed8;
    }
    .tone-card.tone-mint {
        background: linear-gradient(135deg, #ecfdf5 0%, #d1fae5 100%);
        border-color: rgba(16, 185, 129, 0.22);
        color: #047857;
    }
    .tone-card.tone-gold {
        background: linear-gradient(135deg, #fff8e7 0%, #fef3c7 100%);
        border-color: rgba(245, 158, 11, 0.26);
        color: #b45309;
    }
    .tone-card.tone-slate {
        background: linear-gradient(135deg, #f8fafc 0%, #e2e8f0 100%);
        border-color: rgba(100, 116, 139, 0.22);
        color: #334155;
    }
    .tone-card.tone-rose .mini-label,
    .tone-card.tone-violet .mini-label,
    .tone-card.tone-cobalt .mini-label,
    .tone-card.tone-mint .mini-label,
    .tone-card.tone-gold .mini-label,
    .tone-card.tone-slate .mini-label,
    .tone-card.tone-rose small,
    .tone-card.tone-violet small,
    .tone-card.tone-cobalt small,
    .tone-card.tone-mint small,
    .tone-card.tone-gold small,
    .tone-card.tone-slate small {
        opacity: 0.88;
    }
    .tone-card.tone-rose .tone-value,
    .tone-card.tone-violet .tone-value,
    .tone-card.tone-cobalt .tone-value,
    .tone-card.tone-mint .tone-value,
    .tone-card.tone-gold .tone-value,
    .tone-card.tone-slate .tone-value,
    .tone-card.tone-rose h5,
    .tone-card.tone-violet h5,
    .tone-card.tone-cobalt h5,
    .tone-card.tone-mint h5,
    .tone-card.tone-gold h5,
    .tone-card.tone-slate h5,
    .tone-card.tone-rose .mdi,
    .tone-card.tone-violet .mdi,
    .tone-card.tone-cobalt .mdi,
    .tone-card.tone-mint .mdi,
    .tone-card.tone-gold .mdi,
    .tone-card.tone-slate .mdi {
        color: inherit !important;
    }
    .tone-indigo {
        background:
            radial-gradient(circle at top right, rgba(255,255,255,0.18), transparent 34%),
            linear-gradient(135deg, var(--kpi-indigo-start) 0%, var(--kpi-indigo-end) 100%);
        border-color: rgba(165, 180, 252, 0.34);
        color: #f8fafc;
    }
    .tone-cobalt {
        background:
            radial-gradient(circle at top right, rgba(255,255,255,0.18), transparent 34%),
            linear-gradient(135deg, #1d4ed8 0%, #38bdf8 100%);
        border-color: rgba(125, 211, 252, 0.34);
        color: #f8fbff;
    }
    .tone-sky {
        background:
            radial-gradient(circle at top right, rgba(255,255,255,0.18), transparent 34%),
            linear-gradient(135deg, var(--kpi-cyan-start) 0%, var(--kpi-cyan-end) 100%);
        border-color: rgba(103, 232, 249, 0.34);
        color: #ecfeff;
    }
    .tone-emerald {
        background:
            radial-gradient(circle at top right, rgba(255,255,255,0.18), transparent 34%),
            linear-gradient(135deg, var(--kpi-emerald-start) 0%, var(--kpi-emerald-end) 100%);
        border-color: rgba(110, 231, 183, 0.34);
        color: #ecfdf5;
    }
    .tone-mint {
        background:
            radial-gradient(circle at top right, rgba(255,255,255,0.18), transparent 34%),
            linear-gradient(135deg, #0f766e 0%, #34d399 100%);
        border-color: rgba(110, 231, 183, 0.32);
        color: #ecfdf5;
    }
    .tone-amber {
        background:
            radial-gradient(circle at top right, rgba(255,255,255,0.18), transparent 34%),
            linear-gradient(135deg, var(--kpi-amber-start) 0%, var(--kpi-amber-end) 100%);
        border-color: rgba(253, 230, 138, 0.36);
        color: #fffbeb;
    }
    .tone-gold {
        background:
            radial-gradient(circle at top right, rgba(255,255,255,0.20), transparent 34%),
            linear-gradient(135deg, #b45309 0%, #fbbf24 100%);
        border-color: rgba(252, 211, 77, 0.34);
        color: #fffaf0;
    }
    .tone-rose {
        background:
            radial-gradient(circle at top right, rgba(255,255,255,0.18), transparent 34%),
            linear-gradient(135deg, var(--kpi-rose-start) 0%, var(--kpi-rose-end) 100%);
        border-color: rgba(253, 164, 175, 0.34);
        color: #fff1f2;
    }
    .tone-slate {
        background:
            radial-gradient(circle at top right, rgba(255,255,255,0.14), transparent 34%),
            linear-gradient(135deg, var(--kpi-slate-start) 0%, var(--kpi-slate-end) 100%);
        border-color: rgba(148, 163, 184, 0.32);
        color: #f8fafc;
    }
    .tone-violet {
        background:
            radial-gradient(circle at top right, rgba(255,255,255,0.18), transparent 34%),
            linear-gradient(135deg, #4c1d95 0%, #7c3aed 100%);
        border-color: rgba(196, 181, 253, 0.34);
        color: #f5f3ff;
    }
    .executive-kpi {
        border: none !important;
        border-radius: 22px !important;
        min-height: 122px;
        overflow: hidden;
        position: relative;
        box-shadow: 0 14px 28px rgba(15, 23, 42, 0.12) !important;
    }
    .executive-kpi::before {
        content: "";
        position: absolute;
        inset: 0;
        background: linear-gradient(180deg, rgba(255,255,255,0.08) 0%, transparent 32%);
        pointer-events: none;
    }
    .executive-kpi .card-body {
        position: relative;
        z-index: 1;
        padding: 0.82rem 0.88rem;
    }
    .executive-kpi .kpi-icon-shell {
        width: 40px;
        height: 40px;
        border-radius: 12px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        background: rgba(255,255,255,0.14);
        border: 1px solid rgba(255,255,255,0.16);
        box-shadow: inset 0 1px 0 rgba(255,255,255,0.12);
        color: #fff;
    }
    .executive-kpi .kpi-icon-shell svg,
    .executive-kpi .kpi-icon-shell i {
        width: 18px;
        height: 18px;
        stroke: currentColor;
        fill: none;
        stroke-width: 2;
        stroke-linecap: round;
        stroke-linejoin: round;
    }
    .executive-kpi .kpi-icon-shell i {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        width: auto;
        height: auto;
        font-size: 0.95rem;
        stroke: none;
        fill: currentColor;
    }
    .executive-kpi .kpi-kicker {
        font-family: var(--kpi-ui-font);
        font-size: 0.54rem;
        font-weight: 800;
        letter-spacing: 0.08em;
        text-transform: uppercase;
        color: rgba(255,255,255,0.72) !important;
    }
    .executive-kpi .kpi-value {
        font-family: var(--kpi-display-font);
        font-size: clamp(1.12rem, 1.35vw, 1.65rem);
        line-height: 1;
        letter-spacing: -0.05em;
        color: #fff !important;
    }
    .executive-kpi .kpi-note {
        font-family: var(--kpi-ui-font);
        color: rgba(255,255,255,0.78) !important;
        font-size: 0.64rem;
        line-height: 1.35;
    }
    .executive-kpi .kpi-badge {
        font-family: var(--kpi-ui-font);
        border-radius: 999px;
        padding: 0.22rem 0.48rem;
        font-size: 0.52rem;
        font-weight: 800;
        letter-spacing: 0.06em;
        text-transform: uppercase;
        background: rgba(255,255,255,0.16);
        color: #fff;
        border: 1px solid rgba(255,255,255,0.14);
    }
    .executive-kpi.kpi-revenue {
        background:
            radial-gradient(circle at top right, rgba(255,255,255,0.18), transparent 30%),
            linear-gradient(135deg, #0f172a 0%, #1d4ed8 52%, #38bdf8 100%);
    }
    .executive-kpi.kpi-subscriptions {
        background:
            radial-gradient(circle at top right, rgba(255,255,255,0.18), transparent 30%),
            linear-gradient(135deg, #052e16 0%, #059669 55%, #34d399 100%);
    }
    .executive-kpi.kpi-companies {
        background:
            radial-gradient(circle at top right, rgba(255,255,255,0.18), transparent 30%),
            linear-gradient(135deg, #083344 0%, #0891b2 52%, #67e8f9 100%);
    }
    .executive-kpi.kpi-users {
        background:
            radial-gradient(circle at top right, rgba(255,255,255,0.18), transparent 30%),
            linear-gradient(135deg, #7c2d12 0%, #f59e0b 52%, #fde68a 100%);
    }
    .summary-fill.tone-indigo .label,
    .summary-fill.tone-cobalt .label,
    .summary-fill.tone-sky .label,
    .summary-fill.tone-emerald .label,
    .summary-fill.tone-mint .label,
    .summary-fill.tone-amber .label,
    .summary-fill.tone-gold .label,
    .summary-fill.tone-rose .label,
    .summary-fill.tone-slate .label,
    .summary-fill.tone-violet .label,
    .kpi-compact.tone-indigo .label,
    .kpi-compact.tone-cobalt .label,
    .kpi-compact.tone-sky .label,
    .kpi-compact.tone-emerald .label,
    .kpi-compact.tone-mint .label,
    .kpi-compact.tone-amber .label,
    .kpi-compact.tone-gold .label,
    .kpi-compact.tone-rose .label,
    .kpi-compact.tone-slate .label,
    .kpi-compact.tone-violet .label {
        color: inherit;
        opacity: 0.82;
    }
    .summary-fill.tone-indigo .value,
    .summary-fill.tone-cobalt .value,
    .summary-fill.tone-sky .value,
    .summary-fill.tone-emerald .value,
    .summary-fill.tone-mint .value,
    .summary-fill.tone-amber .value,
    .summary-fill.tone-gold .value,
    .summary-fill.tone-rose .value,
    .summary-fill.tone-slate .value,
    .summary-fill.tone-violet .value,
    .kpi-compact.tone-indigo .value,
    .kpi-compact.tone-cobalt .value,
    .kpi-compact.tone-sky .value,
    .kpi-compact.tone-emerald .value,
    .kpi-compact.tone-mint .value,
    .kpi-compact.tone-amber .value,
    .kpi-compact.tone-gold .value,
    .kpi-compact.tone-rose .value,
    .kpi-compact.tone-slate .value,
    .kpi-compact.tone-violet .value {
        color: inherit;
    }
    .card-body,
    .table-responsive,
    .list-wrapper,
    .metric-wall,
    .kpi-grid-dense {
        min-width: 0;
    }
    .table-responsive table td,
    .table-responsive table th,
    .metric-wall-item,
    .kpi-dense-item,
    .kpi-compact,
    .summary-fill {
        word-break: break-word;
        overflow-wrap: anywhere;
    }
    .table {
        color: var(--dash-ink);
    }
    .table thead th {
        font-size: 10px;
        font-weight: 800;
        letter-spacing: 0.08em;
        text-transform: uppercase;
        color: #607086;
    }
    .table tbody td {
        font-size: 0.83rem;
        line-height: 1.45;
        color: #1e293b;
    }
    .btn {
        border-radius: 12px !important;
        font-weight: 700;
        letter-spacing: -0.01em;
    }
    .btn-outline-secondary {
        border-color: #d5e1ee;
        color: #42556b;
    }
    .btn-outline-secondary:hover {
        background: #f5f8fc;
        color: #163047;
        border-color: #c2d3e4;
    }
    .badge {
        letter-spacing: 0.03em;
    }
    .pulse-chart-shell {
        display: grid;
        grid-template-columns: minmax(220px, 250px) minmax(0, 1fr);
        gap: 18px;
        align-items: center;
    }
    .pulse-chart-wrap {
        position: relative;
        min-height: 220px;
        display: grid;
        place-items: center;
    }
    .pulse-chart-wrap canvas {
        max-width: 220px;
        max-height: 220px;
    }
    .pulse-center-badge {
        position: absolute;
        inset: 50% auto auto 50%;
        transform: translate(-50%, -50%);
        width: 104px;
        height: 104px;
        border-radius: 50%;
        background: linear-gradient(180deg, #ffffff 0%, #f7faff 100%);
        border: 1px solid #e5edf8;
        box-shadow: 0 12px 28px rgba(15, 23, 42, 0.08);
        display: grid;
        place-items: center;
        text-align: center;
        pointer-events: none;
    }
    .pulse-center-badge .value {
        font-size: 1.2rem;
        font-weight: 800;
        color: #0f172a;
        line-height: 1;
    }
    .pulse-center-badge .label {
        margin-top: 4px;
        font-size: 10px;
        font-weight: 800;
        text-transform: uppercase;
        letter-spacing: 0.08em;
        color: #64748b;
    }
    .pulse-legend-grid {
        display: grid;
        grid-template-columns: repeat(2, minmax(0, 1fr));
        gap: 12px;
    }
    .pulse-legend-item {
        border: 1px solid #e5edf8;
        border-radius: 14px;
        background: #fff;
        padding: 12px;
        min-height: 86px;
    }
    .pulse-legend-item .topline {
        display: flex;
        align-items: center;
        gap: 8px;
        margin-bottom: 8px;
    }
    .pulse-legend-item .swatch {
        width: 10px;
        height: 10px;
        border-radius: 50%;
        flex: 0 0 auto;
    }
    .pulse-legend-item .label {
        font-size: 11px;
        font-weight: 700;
        color: #64748b;
        text-transform: uppercase;
        letter-spacing: 0.06em;
    }
    .pulse-legend-item .value {
        font-size: 1.1rem;
        font-weight: 800;
        color: #0f172a;
        line-height: 1.1;
    }
    .pulse-legend-item .note {
        margin-top: 4px;
        font-size: 12px;
        color: #64748b;
    }

    @media (max-width: 1199.98px) {
        .chart-container {
            height: 300px;
        }
        .chartjs-wrapper {
            height: 280px;
            min-height: 280px;
        }
        #regionMap {
            height: 340px;
        }
    }

    @media (max-width: 991.98px) {
        #main-content-wrapper {
            padding-left: 12px !important;
            padding-right: 12px !important;
        }
        .header-responsive-flex > div,
        .btn-wrapper {
            width: 100%;
        }
        .btn-wrapper {
            justify-content: flex-start;
        }
        .chart-container {
            height: 260px;
        }
        .chartjs-wrapper {
            height: 240px;
            min-height: 240px;
        }
        #regionMap {
            height: 280px;
        }
        .dashboard-micro-grid {
            grid-template-columns: repeat(2, minmax(0, 1fr));
        }
        .dashboard-side-fill {
            grid-template-columns: repeat(2, minmax(0, 1fr));
        }
        .table-responsive[style*="max-height"] {
            max-height: none !important;
            overflow-y: visible !important;
        }
        .list-wrapper[style*="max-height"] {
            max-height: none !important;
            overflow-y: visible !important;
        }
        .sticky-top {
            position: static !important;
        }
        .pulse-chart-shell {
            grid-template-columns: 1fr;
        }
        .pulse-legend-grid {
            grid-template-columns: 1fr;
        }
    }

    @media (max-width: 767.98px) {
        .chart-container {
            height: 220px;
        }
        .chartjs-wrapper {
            height: 220px;
            min-height: 220px;
        }
        #regionMap {
            height: 240px;
        }
        .dashboard-micro-grid {
            grid-template-columns: 1fr;
        }
        .dashboard-side-fill {
            grid-template-columns: 1fr;
        }
        .card-body {
            padding: 1rem !important;
        }
        .header-responsive-flex {
            align-items: flex-start;
        }
        .header-responsive-flex h3 {
            font-size: 1.2rem;
        }
        .btn-wrapper .btn {
            width: 100%;
            justify-content: center;
        }
        .kpi-compact .value,
        .kpi-dense-item .value,
        .metric-wall-item .value,
        .summary-fill .value {
            font-size: 0.88rem;
        }
        .executive-kpi {
            min-height: 126px;
        }
        .executive-kpi .kpi-value {
            font-size: 1.32rem;
        }
        .executive-kpi .kpi-note {
            font-size: 0.68rem;
        }
        .tone-card .tone-value {
            font-size: 1.3rem;
        }
        .badge,
        .live-badge-soft {
            white-space: normal;
        }
    }

    @media (max-width: 575.98px) {
        #main-content-wrapper {
            padding-left: 8px !important;
            padding-right: 8px !important;
        }
        .chart-container {
            height: 200px;
        }
        .chartjs-wrapper {
            height: 200px;
            min-height: 200px;
        }
        #regionMap {
            height: 220px;
        }
        .card-title.card-title-dash,
        .card-title-dash {
            font-size: 1rem;
            line-height: 1.25;
        }
        .card-subtitle,
        .card-subtitle-dash {
            font-size: 0.75rem !important;
        }
    }
</style>

<div id="main-content-wrapper" class="container-fluid px-4 pb-4">

    <div class="row">
        <div class="col-sm-12">
            <div class="home-tab">

                <div class="header-responsive-flex border-bottom pb-3 mb-4">
                    <div class="d-flex align-items-center">
                        <div>
                            <h3 class="fw-bold text-dark mb-1">Master Command Center</h3>
                            <p class="text-muted mb-0">
                                System-wide overview for domain: 
                                <span class="text-primary fw-bold">{{ env('SESSION_DOMAIN', 'Primary Cluster') }}</span>
                            </p>
                        </div>
                    </div>

                    @if(auth()->user()->role === 'deployment_manager')
                        @php
                            $isSynced = auth()->user()->is_verified == 1 && 
                                        auth()->user()->deploymentProfile?->status === 'active';
                        @endphp
                        <div class="px-0 px-md-4 py-2">
                            <div class="d-flex align-items-center p-2 rounded {{ $isSynced ? 'bg-soft-success border border-success' : 'bg-soft-danger border border-danger animate-pulse' }}">
                                <div class="flex-shrink-0">
                                    <i class="mdi {{ $isSynced ? 'mdi-check-circle text-success' : 'mdi-alert-circle text-danger' }} fs-5"></i>
                                </div>
                                <div class="ms-3">
                                    <p class="mb-0 fw-bold" style="font-size: 0.75rem; color: {{ $isSynced ? '#0a5d1a' : '#842029' }}">
                                        {{ $isSynced ? 'ACCOUNT VERIFIED' : 'SYNC REQUIRED' }}
                                    </p>
                                </div>
                            </div>
                        </div>
                    @endif

                    <div class="btn-wrapper d-flex gap-2 flex-wrap">
                        <button type="button" onclick="shareDashboard()" class="btn btn-outline-secondary btn-icon-text">
                            <i class="mdi mdi-share-variant me-1"></i> Share
                        </button>
                        <button onclick="window.print()" class="btn btn-outline-secondary btn-icon-text">
                            <i class="mdi mdi-printer me-1"></i> Print
                        </button>
                        <a href="{{ route('super_admin.dashboard.export') }}" class="btn btn-primary text-white btn-icon-text me-0">
                            <i class="mdi mdi-download me-1"></i> Export Reports
                        </a>
                    </div>
                </div>

                <div class="tab-content tab-content-basic dashboard-tight">
                    <div class="tab-pane fade show active" id="overview" role="tabpanel">

                        <div class="row">
                            <div class="col-lg-3 col-md-6 grid-margin stretch-card">
                                <div class="card executive-kpi kpi-revenue border-0">
                                    <div class="card-body">
                                        <div class="d-flex justify-content-between align-items-start">
                                            <div class="kpi-icon-shell">
                                                <i class="fas fa-sack-dollar" aria-hidden="true"></i>
                                            </div>
                                            <div class="kpi-badge">Owners</div>
                                        </div>
                                        <div class="mt-3">
                                            <h6 class="kpi-kicker mb-2">Total Plan Revenue</h6>
                                            <h3 class="fw-bold mb-0 kpi-value">₦{{ number_format($metrics['owner_subscription_revenue'] ?? 0, 2) }}</h3>
                                            <p class="kpi-note mb-0 mt-2">All verified subscription income to platform owners</p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-lg-3 col-md-6 grid-margin stretch-card">
                                <div class="card executive-kpi kpi-subscriptions border-0">
                                    <div class="card-body">
                                        <div class="d-flex justify-content-between align-items-start">
                                            <div class="kpi-icon-shell">
                                                <i class="fas fa-chart-line" aria-hidden="true"></i>
                                            </div>
                                            <div class="kpi-badge">Month</div>
                                        </div>
                                        <div class="mt-3">
                                            <h6 class="kpi-kicker mb-2">Monthly Plan Revenue</h6>
                                            <h3 class="fw-bold mb-0 kpi-value">₦{{ number_format($metrics['plan_sales_value_month'] ?? 0, 2) }}</h3>
                                            <p class="kpi-note mb-0 mt-2">Income collected from plan buyers this month</p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-lg-3 col-md-6 grid-margin stretch-card">
                                <div class="card executive-kpi kpi-companies border-0">
                                    <div class="card-body">
                                        <div class="d-flex justify-content-between align-items-start">
                                            <div class="kpi-icon-shell">
                                                <i class="fas fa-building" aria-hidden="true"></i>
                                            </div>
                                            <div class="kpi-badge">Buyers</div>
                                        </div>
                                        <div class="mt-3">
                                            <h6 class="kpi-kicker mb-2">Paid Plan Buyers</h6>
                                            <h3 class="fw-bold mb-0 kpi-value">{{ number_format($metrics['paid_subs'] ?? 0) }}</h3>
                                            <p class="kpi-note mb-0 mt-2">Direct and deployment-created buyers combined</p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-lg-3 col-md-6 grid-margin stretch-card">
                                <div class="card executive-kpi kpi-users border-0">
                                    <div class="card-body">
                                        <div class="d-flex justify-content-between align-items-start">
                                            <div class="kpi-icon-shell">
                                                <i class="fas fa-users" aria-hidden="true"></i>
                                            </div>
                                            <div class="kpi-badge">Ticket</div>
                                        </div>
                                        <div class="mt-3">
                                            <h6 class="kpi-kicker mb-2">Average Plan Sale</h6>
                                            <h3 class="fw-bold mb-0 kpi-value">₦{{ number_format($metrics['avg_plan_sale'] ?? 0, 2) }}</h3>
                                            <p class="kpi-note mb-0 mt-2">Average income per verified plan purchase</p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        {{-- Platform Treasury --}}
                        @if(session('payout_success'))
                            <div class="alert alert-success alert-dismissible fade show" role="alert">
                                <i class="fas fa-check-circle me-2"></i>{{ session('payout_success') }}
                                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                            </div>
                        @endif
                        <div class="row mb-3">
                            <div class="col-12">
                                <div class="card border-0 shadow-sm">
                                    <div class="card-body p-4">
                                        <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
                                            <div>
                                                <h5 class="fw-bold mb-0 text-dark"><i class="fas fa-vault me-2 text-warning"></i>Platform Treasury</h5>
                                                <small class="text-muted">Gross revenue, investor payouts, and net balance</small>
                                            </div>
                                            <button class="btn btn-sm btn-warning fw-semibold" data-bs-toggle="modal" data-bs-target="#recordPayoutModal">
                                                <i class="fas fa-plus me-1"></i> Record Payout
                                            </button>
                                        </div>
                                        <div class="row g-3">
                                            <div class="col-md-4">
                                                <div class="p-3 rounded-3 bg-light text-center">
                                                    <div class="text-muted small mb-1">Gross Revenue</div>
                                                    <div class="fw-bold fs-5 text-success">₦{{ number_format($metrics['owner_subscription_revenue'] ?? 0, 2) }}</div>
                                                    <div class="text-muted" style="font-size:0.75rem;">Total subscription income</div>
                                                </div>
                                            </div>
                                            <div class="col-md-4">
                                                <div class="p-3 rounded-3 bg-light text-center">
                                                    <div class="text-muted small mb-1">Total Paid Out</div>
                                                    <div class="fw-bold fs-5 text-danger">₦{{ number_format($metrics['total_payouts'] ?? 0, 2) }}</div>
                                                    <div class="text-muted" style="font-size:0.75rem;">Dividends, commissions &amp; more</div>
                                                </div>
                                            </div>
                                            <div class="col-md-4">
                                                <div class="p-3 rounded-3 {{ ($metrics['net_platform_balance'] ?? 0) >= 0 ? 'bg-success bg-opacity-10' : 'bg-danger bg-opacity-10' }} text-center">
                                                    <div class="text-muted small mb-1">Net Platform Balance</div>
                                                    <div class="fw-bold fs-5 {{ ($metrics['net_platform_balance'] ?? 0) >= 0 ? 'text-success' : 'text-danger' }}">
                                                        ₦{{ number_format($metrics['net_platform_balance'] ?? 0, 2) }}
                                                    </div>
                                                    <div class="text-muted" style="font-size:0.75rem;">Gross minus all payouts</div>
                                                </div>
                                            </div>
                                        </div>

                                        {{-- Recent Payouts --}}
                                        @php
                                            $recentPayouts = \Illuminate\Support\Facades\Schema::hasTable('platform_payouts')
                                                ? \App\Models\PlatformPayout::latest()->limit(5)->get()
                                                : collect();
                                        @endphp
                                        @if($recentPayouts->isNotEmpty())
                                            <div class="mt-4">
                                                <h6 class="text-muted mb-2 small fw-semibold text-uppercase">Recent Payouts</h6>
                                                <div class="table-responsive">
                                                    <table class="table table-sm align-middle mb-0">
                                                        <thead class="table-light">
                                                            <tr>
                                                                <th>Recipient</th>
                                                                <th>Type</th>
                                                                <th>Amount</th>
                                                                <th>Description</th>
                                                                <th>Date</th>
                                                            </tr>
                                                        </thead>
                                                        <tbody>
                                                            @foreach($recentPayouts as $payout)
                                                                <tr>
                                                                    <td class="fw-semibold">{{ $payout->recipient_name }}</td>
                                                                    <td><span class="badge bg-secondary text-capitalize">{{ $payout->payout_type }}</span></td>
                                                                    <td class="text-danger fw-semibold">₦{{ number_format($payout->amount, 2) }}</td>
                                                                    <td class="text-muted small">{{ $payout->description ?: '—' }}</td>
                                                                    <td class="text-muted small">{{ $payout->paid_at ? $payout->paid_at->format('d M Y') : $payout->created_at->format('d M Y') }}</td>
                                                                </tr>
                                                            @endforeach
                                                        </tbody>
                                                    </table>
                                                </div>
                                                <div class="mt-2 text-end">
                                                    <a href="{{ route('super_admin.platform_payouts.index') }}" class="text-decoration-none small text-primary">View all payouts &rarr;</a>
                                                </div>
                                            </div>
                                        @else
                                            <p class="text-muted small mt-3 mb-0">No payouts recorded yet. Use "Record Payout" to log investor dividends or commissions.</p>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        </div>

                        {{-- Record Payout Modal --}}
                        <div class="modal fade" id="recordPayoutModal" tabindex="-1" aria-labelledby="recordPayoutModalLabel" aria-hidden="true">
                            <div class="modal-dialog modal-dialog-centered">
                                <div class="modal-content">
                                    <form method="POST" action="{{ route('super_admin.platform_payouts.store') }}">
                                        @csrf
                                        <div class="modal-header">
                                            <h5 class="modal-title fw-bold" id="recordPayoutModalLabel"><i class="fas fa-money-bill-wave me-2 text-warning"></i>Record Payout</h5>
                                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                        </div>
                                        <div class="modal-body">
                                            <div class="mb-3">
                                                <label class="form-label fw-semibold">Recipient Name <span class="text-danger">*</span></label>
                                                <input type="text" name="recipient_name" class="form-control" placeholder="e.g. John Investor" required maxlength="255">
                                            </div>
                                            <div class="mb-3">
                                                <label class="form-label fw-semibold">Amount (₦) <span class="text-danger">*</span></label>
                                                <input type="number" name="amount" class="form-control" placeholder="0.00" step="0.01" min="0.01" required>
                                            </div>
                                            <div class="mb-3">
                                                <label class="form-label fw-semibold">Payout Type <span class="text-danger">*</span></label>
                                                <select name="payout_type" class="form-select" required>
                                                    <option value="dividend">Dividend</option>
                                                    <option value="commission">Commission</option>
                                                    <option value="salary">Salary</option>
                                                    <option value="refund">Refund</option>
                                                    <option value="other">Other</option>
                                                </select>
                                            </div>
                                            <div class="mb-3">
                                                <label class="form-label fw-semibold">Description</label>
                                                <input type="text" name="description" class="form-control" placeholder="Brief description (optional)" maxlength="500">
                                            </div>
                                            <div class="mb-3">
                                                <label class="form-label fw-semibold">Payment Date</label>
                                                <input type="date" name="paid_at" class="form-control" value="{{ date('Y-m-d') }}">
                                            </div>
                                            <div class="mb-1">
                                                <label class="form-label fw-semibold">Notes</label>
                                                <textarea name="notes" class="form-control" rows="2" placeholder="Additional notes (optional)" maxlength="1000"></textarea>
                                            </div>
                                        </div>
                                        <div class="modal-footer">
                                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                            <button type="submit" class="btn btn-warning fw-semibold">
                                                <i class="fas fa-save me-1"></i> Record Payout
                                            </button>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>

                        <div class="row mt-2">
                            <div class="col-12 grid-margin stretch-card">
                                <div class="card card-rounded shadow-sm border-0">
                                    <div class="card-body p-3">
                                        <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
                                            <div>
                                                <h5 class="mb-0 fw-bold text-dark">Customer Source Snapshot</h5>
                                                <small class="text-muted">Inclusive super admin counts for direct registrations and deployment-manager-created accounts</small>
                                            </div>
                                            <span class="live-badge-soft">Cross-checked</span>
                                        </div>
                                        <div class="row g-2">
                                            @foreach([
                                                ['label' => 'Registered User Revenue', 'value' => '₦' . number_format($metrics['registered_user_revenue'] ?? 0, 2), 'tone' => 'tone-cobalt'],
                                                ['label' => 'Customer Users', 'value' => number_format($metrics['total_users'] ?? 0), 'tone' => 'tone-cobalt'],
                                                ['label' => 'Direct Signup Users', 'value' => number_format($metrics['direct_customer_users'] ?? 0), 'tone' => 'tone-emerald'],
                                                ['label' => 'Deployment Signup Users', 'value' => number_format($metrics['deployment_customer_users'] ?? 0), 'tone' => 'tone-violet'],
                                                ['label' => 'Direct Plan Revenue', 'value' => '₦' . number_format($metrics['direct_subscription_revenue'] ?? 0, 2), 'tone' => 'tone-amber'],
                                                ['label' => 'Deployment Plan Revenue', 'value' => '₦' . number_format($metrics['deployment_subscription_revenue'] ?? 0, 2), 'tone' => 'tone-rose'],
                                            ] as $sourceKpi)
                                                <div class="col-sm-6 col-xl">
                                                    <div class="kpi-compact w-100 {{ $sourceKpi['tone'] }}">
                                                        <div class="d-flex justify-content-between align-items-start">
                                                            <div class="label">{{ $sourceKpi['label'] }}</div>
                                                            <span class="live-badge-soft">Live</span>
                                                        </div>
                                                        <div class="value">{{ $sourceKpi['value'] }}</div>
                                                    </div>
                                                </div>
                                            @endforeach
                                        </div>
                                        @php
                                            $directPaidSubs = (int) ($metrics['direct_paid_subs'] ?? 0);
                                            $deploymentPaidSubs = (int) ($metrics['deployment_paid_subs'] ?? 0);
                                            $directRevenue = (float) ($metrics['direct_subscription_revenue'] ?? 0);
                                            $deploymentRevenue = (float) ($metrics['deployment_subscription_revenue'] ?? 0);
                                        @endphp
                                        <div class="row g-2 mt-3">
                                            @foreach([
                                                ['label' => 'Direct Paid Buyers', 'value' => number_format($directPaidSubs)],
                                                ['label' => 'Deployment Paid Buyers', 'value' => number_format($deploymentPaidSubs)],
                                                ['label' => 'Direct Avg Revenue', 'value' => '₦' . number_format($directPaidSubs > 0 ? ($directRevenue / $directPaidSubs) : 0, 2)],
                                                ['label' => 'Deployment Avg Revenue', 'value' => '₦' . number_format($deploymentPaidSubs > 0 ? ($deploymentRevenue / $deploymentPaidSubs) : 0, 2)],
                                                ['label' => 'Verified Users', 'value' => number_format($metrics['verified_users'] ?? 0)],
                                                ['label' => 'Recent Signups (30d)', 'value' => number_format($metrics['recent_signups'] ?? 0)],
                                            ] as $sourceMini)
                                                <div class="col-sm-6 col-xl-2">
                                                    <div class="summary-fill h-100">
                                                        <div class="label">{{ $sourceMini['label'] }}</div>
                                                        <div class="value">{{ $sourceMini['value'] }}</div>
                                                    </div>
                                                </div>
                                            @endforeach
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="row mt-2">
                            <div class="col-12 grid-margin stretch-card">
                                <div class="card card-rounded shadow-sm border-0">
                                    <div class="card-body p-3">
                                        <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
                                            <div>
                                                <h5 class="mb-0 fw-bold text-dark">Business Item Sales Snapshot</h5>
                                                <small class="text-muted">Secondary business-sales metrics kept separate from platform plan income</small>
                                            </div>
                                            <span class="live-badge-soft">Business Sales</span>
                                        </div>
                                        <div class="row g-2">
                                            @foreach([
                                                ['label' => 'Item Sales Revenue', 'value' => '₦' . number_format($metrics['item_sales_revenue'] ?? 0, 2), 'tone' => 'tone-cobalt'],
                                                ['label' => 'Item Sales Today', 'value' => '₦' . number_format($metrics['item_sales_today_revenue'] ?? 0, 2), 'tone' => 'tone-emerald'],
                                                ['label' => 'Item Orders', 'value' => number_format($metrics['item_sales_orders'] ?? 0), 'tone' => 'tone-amber'],
                                                ['label' => 'Item Units Sold', 'value' => number_format($metrics['item_sales_units'] ?? 0), 'tone' => 'tone-rose'],
                                            ] as $salesKpi)
                                                <div class="col-sm-6 col-xl-3">
                                                    <div class="kpi-compact w-100 {{ $salesKpi['tone'] }}">
                                                        <div class="d-flex justify-content-between align-items-start">
                                                            <div class="label">{{ $salesKpi['label'] }}</div>
                                                            <span class="live-badge-soft">Live</span>
                                                        </div>
                                                        <div class="value">{{ $salesKpi['value'] }}</div>
                                                    </div>
                                                </div>
                                            @endforeach
                                        </div>
                                        <div class="row g-2 mt-3">
                                            @foreach([
                                                ['label' => 'Stock Value', 'value' => '₦' . number_format($metrics['total_stock_val'] ?? 0, 2)],
                                                ['label' => 'Low Stock Items', 'value' => number_format($metrics['low_stock_items'] ?? 0)],
                                                ['label' => 'Orders per User', 'value' => number_format(((int) ($metrics['total_users'] ?? 0)) > 0 ? ((float) ($metrics['item_sales_orders'] ?? 0) / max(1, (int) ($metrics['total_users'] ?? 0))) : 0, 2)],
                                                ['label' => 'Revenue per Order', 'value' => '₦' . number_format(((int) ($metrics['item_sales_orders'] ?? 0)) > 0 ? ((float) ($metrics['item_sales_revenue'] ?? 0) / max(1, (int) ($metrics['item_sales_orders'] ?? 0))) : 0, 2)],
                                            ] as $salesMini)
                                                <div class="col-sm-6 col-xl-3">
                                                    <div class="summary-fill h-100">
                                                        <div class="label">{{ $salesMini['label'] }}</div>
                                                        <div class="value">{{ $salesMini['value'] }}</div>
                                                    </div>
                                                </div>
                                            @endforeach
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="row mt-2">
                            <div class="col-md-3 grid-margin stretch-card">
                                <div class="card card-rounded tone-card tone-rose shadow-sm">
                                    <div class="card-body p-3">
                                        <div class="d-flex align-items-center">
                                            <i class="mdi mdi-account-alert fs-2 me-3"></i>
                                            <div>
                                                <h5 class="fw-bold mb-0 tone-value">{{ $metrics['pending_managers'] }}</h5>
                                                <small class="mini-label">Pending Managers</small>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-3 grid-margin stretch-card">
                                <div class="card card-rounded tone-card tone-violet shadow-sm">
                                    <div class="card-body p-3">
                                        <div class="d-flex align-items-center">
                                            <i class="mdi mdi-account-tie fs-2 me-3"></i>
                                            <div>
                                                <h5 class="fw-bold mb-0 tone-value">{{ $metrics['active_managers'] }}</h5>
                                                <small class="mini-label">Active Managers</small>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-3 grid-margin stretch-card">
                                <div class="card card-rounded tone-card tone-cobalt shadow-sm">
                                    <div class="card-body p-3">
                                        <div class="d-flex align-items-center">
                                            <i class="mdi mdi-server-network-off fs-2 me-3"></i>
                                            <div>
                                                <h5 class="fw-bold mb-0 tone-value">{{ $metrics['pending_setups'] }}</h5>
                                                <small class="mini-label">Provisioning Queue</small>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-3 grid-margin stretch-card">
                                <div class="card card-rounded tone-card tone-mint shadow-sm">
                                    <div class="card-body p-3">
                                        <div class="d-flex align-items-center">
                                            <i class="mdi mdi-package-variant-closed fs-2 me-3"></i>
                                            <div>
                                                <h5 class="fw-bold mb-0 tone-value">₦{{ number_format($metrics['total_stock_val']) }}</h5>
                                                <small class="mini-label">Stock Valuation</small>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="row mt-2">
                            <div class="col-md-3 grid-margin stretch-card">
                                <div class="card card-rounded tone-card tone-cobalt shadow-sm">
                                    <div class="card-body p-3">
                                        <div class="d-flex align-items-center">
                                            <i class="mdi mdi-cash-check fs-2 me-3"></i>
                                            <div>
                                                <h5 class="fw-bold mb-0 tone-value">{{ number_format($metrics['paid_subs'] ?? 0) }}</h5>
                                                <small class="mini-label">Paid Subscriptions</small>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-3 grid-margin stretch-card">
                                <div class="card card-rounded tone-card tone-gold shadow-sm">
                                    <div class="card-body p-3">
                                        <div class="d-flex align-items-center">
                                            <i class="mdi mdi-credit-card-outline fs-2 me-3"></i>
                                            <div>
                                                <h5 class="fw-bold mb-0 tone-value">{{ number_format($metrics['total_subs'] ?? 0) }}</h5>
                                                <small class="mini-label">Total Subscriptions</small>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-3 grid-margin stretch-card">
                                <div class="card card-rounded tone-card tone-rose shadow-sm">
                                    <div class="card-body p-3">
                                        <div class="d-flex align-items-center">
                                            <i class="mdi mdi-timer-sand fs-2 me-3"></i>
                                            <div>
                                                <h5 class="fw-bold mb-0 tone-value">{{ number_format($metrics['expiring_soon_subs'] ?? 0) }}</h5>
                                                <small class="mini-label">Expiring in 7 Days</small>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-3 grid-margin stretch-card">
                                <div class="card card-rounded tone-card tone-slate shadow-sm">
                                    <div class="card-body p-3">
                                        <div class="d-flex align-items-center">
                                            <i class="mdi mdi-close-octagon-outline fs-2 me-3"></i>
                                            <div>
                                                <h5 class="fw-bold mb-0 tone-value">{{ number_format($metrics['expired_subs'] ?? 0) }}</h5>
                                                <small class="mini-label">Expired Subscriptions</small>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        @if(($metrics['expiring_soon_subs'] ?? 0) > 0 || ($metrics['expired_subs'] ?? 0) > 0)
                        <div class="row mt-2">
                            <div class="col-12 grid-margin stretch-card">
                                <div class="card card-rounded shadow-sm border-0 bg-light-warning">
                                    <div class="card-body p-3">
                                        <div class="d-flex align-items-center justify-content-between flex-wrap gap-2 mb-2">
                                            <div>
                                                <h6 class="fw-bold mb-0 text-dark">
                                                    <i class="mdi mdi-alert-outline me-1 text-warning"></i> Subscription Expiry Monitor
                                                </h6>
                                                <small class="text-muted">
                                                    {{ $metrics['expiring_soon_subs'] ?? 0 }} expiring within 7 days, {{ $metrics['expired_subs'] ?? 0 }} expired.
                                                </small>
                                            </div>
                                        </div>
                                        @if(isset($expiringSubscriptions) && $expiringSubscriptions->count() > 0)
                                        <div class="row g-2 mt-1">
                                            @foreach($expiringSubscriptions->take(6) as $expiringSub)
                                            <div class="col-md-6 col-lg-4">
                                                <div class="bg-white border rounded p-2 small d-flex justify-content-between">
                                                    <span>{{ $expiringSub->company->company_name ?? $expiringSub->user->name ?? 'Tenant' }}</span>
                                                    <span class="fw-bold text-danger">{{ \Carbon\Carbon::parse($expiringSub->end_date)->format('M d, Y') }}</span>
                                                </div>
                                            </div>
                                            @endforeach
                                        </div>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        </div>
                        @endif

                        <div class="row mt-2">
                            <div class="col-12 grid-margin stretch-card">
                                <div class="card card-rounded shadow-sm border-0">
                                    <div class="card-body p-3">
                                        <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
                                            <div>
                                                <h5 class="mb-0 fw-bold text-dark">Plan-Based Business Modules</h5>
                                                <small class="text-muted">Basic ₦3,000 • Professional ₦7,000 • Enterprise ₦15,000</small>
                                            </div>
                                            <a href="{{ route('projects.index') }}" class="btn btn-sm btn-outline-primary">Open Project Workspace</a>
                                        </div>

                                        <div class="row g-2">
                                            @foreach([
                                                ['title' => 'Project Management', 'tier' => 'Pro', 'href' => route('projects.index', ['module' => 'project-management'])],
                                                ['title' => 'Project Profitability', 'tier' => 'Pro', 'href' => route('projects.index', ['module' => 'profitability'])],
                                                ['title' => 'Reputation Management', 'tier' => 'Pro', 'href' => route('projects.index', ['module' => 'reputation-management'])],
                                                ['title' => 'Lead Management', 'tier' => 'Pro', 'href' => route('projects.index', ['module' => 'lead-management'])],
                                                ['title' => 'Appointment Scheduling', 'tier' => 'Pro', 'href' => route('projects.index', ['module' => 'appointment-scheduling'])],
                                                ['title' => 'Contract Upload & E-Signature', 'tier' => 'Enterprise', 'href' => route('projects.index', ['module' => 'contract-esignature'])],
                                                ['title' => 'Proposals', 'tier' => 'Enterprise', 'href' => route('projects.index', ['module' => 'proposals'])],
                                                ['title' => 'AI Anomaly Detection', 'tier' => 'Enterprise', 'href' => route('projects.index', ['module' => 'ai-anomaly-detection'])],
                                                ['title' => 'Project Management AI', 'tier' => 'Enterprise', 'href' => route('projects.index', ['module' => 'project-management-ai'])],
                                                ['title' => 'Payroll', 'tier' => 'Enterprise', 'href' => route('payroll.index')],
                                            ] as $module)
                                                <div class="col-md-6 col-xl-3">
                                                    <a href="{{ $module['href'] }}" class="d-flex align-items-center justify-content-between p-2 rounded border bg-light text-decoration-none">
                                                        <span class="small fw-semibold text-dark">{{ $module['title'] }}</span>
                                                        <span class="badge {{ $module['tier'] === 'Enterprise' ? 'bg-warning text-dark' : 'bg-info text-dark' }}">{{ $module['tier'] }}</span>
                                                    </a>
                                                </div>
                                            @endforeach
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="row mt-2">
                            @foreach([
                                ['label' => 'Registered User Revenue', 'value' => '₦' . number_format($metrics['registered_user_revenue'] ?? 0, 0), 'tone' => 'tone-indigo'],
                                ['label' => 'Plan Sales Today', 'value' => number_format($metrics['plan_sales_today'] ?? 0), 'tone' => 'tone-sky'],
                                ['label' => 'Plan Sales This Month', 'value' => number_format($metrics['plan_sales_month'] ?? 0), 'tone' => 'tone-emerald'],
                                ['label' => 'Monthly Plan Value', 'value' => '₦' . number_format($metrics['plan_sales_value_month'] ?? 0, 0), 'tone' => 'tone-violet'],
                                ['label' => 'Average Plan Sale', 'value' => '₦' . number_format($metrics['avg_plan_sale'] ?? 0, 0), 'tone' => 'tone-amber'],
                                ['label' => 'Paid Subscriptions', 'value' => number_format($metrics['paid_subs'] ?? 0), 'tone' => 'tone-rose'],
                            ] as $kpi)
                                <div class="col-md-4 col-xl-2 grid-margin stretch-card">
                                    <div class="kpi-compact w-100 {{ $kpi['tone'] }}">
                                        <div class="d-flex justify-content-between align-items-start">
                                            <div class="label">{{ $kpi['label'] }}</div>
                                            <span class="live-badge-soft animate-pulse">Live</span>
                                        </div>
                                        <div class="value">{{ $kpi['value'] }}</div>
                                    </div>
                                </div>
                            @endforeach
                        </div>

                        <div class="row mt-4">

                            <div class="col-lg-4 grid-margin stretch-card">
                                <div class="card card-rounded shadow-sm">
                                    <div class="card-body">
                                        <div class="d-flex justify-content-between align-items-center mb-2">
                                            <h4 class="card-title card-title-dash mb-0">Revenue by Plan</h4>
                                            <span class="live-badge-soft">Live</span>
                                        </div>
                                        <p class="card-subtitle card-subtitle-dash text-muted mb-4">Paid plan sales distribution</p>
                                        <div class="chart-container">
                                            <canvas id="planStatsChart"></canvas>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="col-lg-4 grid-margin stretch-card">
                                <div class="card card-rounded shadow-sm">
                                    <div class="card-body">
                                        <div class="d-flex justify-content-between align-items-center mb-2">
                                            <h4 class="card-title card-title-dash mb-0">User Traffic & Engagement</h4>
                                            <span class="live-badge-soft">Live</span>
                                        </div>
                                        <p class="card-subtitle card-subtitle-dash text-muted mb-4">New companies vs new users</p>
                                        <div class="chart-container">
                                            <canvas id="trafficLineChart"></canvas>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="col-lg-4 grid-margin stretch-card">
                                <div class="card card-rounded shadow-sm">
                                    <div class="card-body">
                                        <div class="d-flex justify-content-between align-items-center mb-2">
                                            <h4 class="card-title card-title-dash mb-0">Monthly Sales Volume</h4>
                                            <span class="live-badge-soft">Live</span>
                                        </div>
                                        <p class="card-subtitle card-subtitle-dash text-muted mb-4">Paid subscriptions count per month</p>
                                        <div class="chart-container">
                                            <canvas id="salesBarChart"></canvas>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="row mt-2">
                            <div class="col-12 grid-margin stretch-card">
                                <div class="kpi-grid-dense">
                                    <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
                                        <div>
                                            <h5 class="mb-0 fw-bold text-dark">Live Operations Strip</h5>
                                            <small class="text-muted">Additional command-center density under analytics</small>
                                        </div>
                                        <span class="live-badge-soft animate-pulse">Live</span>
                                    </div>
                                    <div class="row g-2">
                                        @foreach([
                                            ['label' => 'Registered Revenue', 'value' => '₦' . number_format($metrics['registered_user_revenue'] ?? 0, 0)],
                                            ['label' => 'Paid Subs', 'value' => number_format($metrics['paid_subs'] ?? 0)],
                                            ['label' => 'Plan Sales Today', 'value' => number_format($metrics['plan_sales_today'] ?? 0)],
                                            ['label' => 'Plan Sales Month', 'value' => number_format($metrics['plan_sales_month'] ?? 0)],
                                            ['label' => 'Monthly Plan Value', 'value' => '₦' . number_format($metrics['plan_sales_value_month'] ?? 0, 0)],
                                            ['label' => 'Average Plan Sale', 'value' => '₦' . number_format($metrics['avg_plan_sale'] ?? 0, 0)],
                                            ['label' => 'Expiring Soon', 'value' => number_format($metrics['expiring_soon_subs'] ?? 0)],
                                            ['label' => 'Expired', 'value' => number_format($metrics['expired_subs'] ?? 0)],
                                            ['label' => 'Verified Users', 'value' => number_format($metrics['verified_users'] ?? 0)],
                                            ['label' => 'Low Stock', 'value' => number_format($metrics['low_stock_items'] ?? 0)],
                                            ['label' => 'New Signups (30d)', 'value' => number_format($metrics['recent_signups'] ?? 0)],
                                            ['label' => 'Users', 'value' => number_format($metrics['total_users'] ?? 0)],
                                            ['label' => 'Active Managers', 'value' => number_format($metrics['active_managers'] ?? 0)],
                                        ] as $denseKpi)
                                            <div class="col-sm-6 col-xl-3">
                                                <div class="kpi-dense-item">
                                                    <div class="label">{{ $denseKpi['label'] }}</div>
                                                    <div class="value">{{ $denseKpi['value'] }}</div>
                                                </div>
                                            </div>
                                        @endforeach
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="row mt-2">
                            <div class="col-12 grid-margin stretch-card">
                                <div class="metric-wall">
                                    <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
                                        <div>
                                            <h5 class="mb-0 fw-bold text-dark">Heavy Metrics Wall</h5>
                                            <small class="text-muted">Live platform depth, plan mix, and approval pressure</small>
                                        </div>
                                        <span class="live-badge-soft animate-pulse">Live</span>
                                    </div>
                                    <div class="row g-2">
                                        @php
                                            $verifiedUsers = (int) ($metrics['verified_users'] ?? 0);
                                            $totalUsers = max(1, (int) ($metrics['total_users'] ?? 0));
                                            $activeSubs = (int) ($metrics['active_subs'] ?? 0);
                                            $totalSubs = max(1, (int) ($metrics['total_subs'] ?? 0));
                                            $companies = (int) ($metrics['total_companies'] ?? 0);
                                            $tenants = max(1, (int) ($metrics['total_tenants'] ?? 0));
                                            $paidSubs = (int) ($metrics['paid_subs'] ?? 0);
                                            $pendingManagers = (int) ($metrics['pending_managers'] ?? 0);
                                            $activeManagers = (int) ($metrics['active_managers'] ?? 0);
                                            $managerBase = max(1, $pendingManagers + $activeManagers + (int) ($metrics['suspended_managers'] ?? 0));
                                            $basicPlans = (int) ($planStats['Basic'] ?? $planStats['basic'] ?? 0);
                                            $proPlans = (int) ($planStats['Pro'] ?? $planStats['Professional'] ?? $planStats['professional'] ?? 0);
                                            $enterprisePlans = (int) ($planStats['Enterprise'] ?? $planStats['enterprise'] ?? 0);
                                        @endphp
                                        @foreach([
                                            ['label' => 'Verification Ratio', 'value' => number_format(($verifiedUsers / $totalUsers) * 100, 1) . '%'],
                                            ['label' => 'Subscription Coverage', 'value' => number_format(($activeSubs / $totalSubs) * 100, 1) . '%'],
                                            ['label' => 'Paid Subscriptions', 'value' => number_format($paidSubs)],
                                            ['label' => 'Tenant Footprint', 'value' => number_format(($companies / $tenants) * 100, 1) . '%'],
                                            ['label' => 'Manager Approval Load', 'value' => number_format(($pendingManagers / $managerBase) * 100, 1) . '%'],
                                            ['label' => 'Basic Plan Nodes', 'value' => number_format($basicPlans)],
                                            ['label' => 'Pro Plan Nodes', 'value' => number_format($proPlans)],
                                            ['label' => 'Enterprise Nodes', 'value' => number_format($enterprisePlans)],
                                        ] as $wallItem)
                                            <div class="col-sm-6 col-xl-3">
                                                <div class="metric-wall-item">
                                                    <div class="label">{{ $wallItem['label'] }}</div>
                                                    <div class="value">{{ $wallItem['value'] }}</div>
                                                </div>
                                            </div>
                                        @endforeach
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="row g-3 mt-4">
                            <div class="col-12 col-xl-6">
                                <div class="card card-rounded shadow-sm h-100">
                                    <div class="card-body">
                                        <div class="d-flex justify-content-between align-items-center mb-3">
                                            <div>
                                                <h5 class="mb-0 fw-bold text-dark">Plan Sales Snapshot</h5>
                                                <small class="text-muted">Live subscription sales and approval pulse</small>
                                            </div>
                                            <span class="live-badge-soft animate-pulse">Live</span>
                                        </div>
                                        <div class="dashboard-side-fill">
                                            <div class="summary-fill">
                                                <div class="label">Plan Sales Today</div>
                                                <div class="value">{{ number_format($metrics['plan_sales_today'] ?? 0) }}</div>
                                            </div>
                                            <div class="summary-fill">
                                                <div class="label">This Month</div>
                                                <div class="value">{{ number_format($metrics['plan_sales_month'] ?? 0) }}</div>
                                            </div>
                                            <div class="summary-fill">
                                                <div class="label">Monthly Value</div>
                                                <div class="value">₦{{ number_format($metrics['plan_sales_value_month'] ?? 0, 0) }}</div>
                                            </div>
                                            <div class="summary-fill">
                                                <div class="label">Awaiting Setup</div>
                                                <div class="value">{{ number_format($metrics['pending_setups'] ?? 0) }}</div>
                                            </div>
                                        </div>
                                        <div class="mt-3">
                                            <div class="d-flex justify-content-between align-items-center mb-2">
                                                <h6 class="mb-0 fw-semibold">Recent Paid Plan Sales</h6>
                                                <span class="small text-muted">{{ number_format($platformActivity->count() ?? 0) }} items</span>
                                            </div>
                                            <div class="list-wrapper" style="max-height: 220px; overflow-y: auto;">
                                                <ul class="bullet-line-list mb-0">
                                                    @forelse($platformActivity->take(5) as $activity)
                                                        <li class="mb-2">
                                                            <div class="d-flex justify-content-between align-items-start gap-2">
                                                                <div class="small">
                                                                    <div class="fw-semibold text-dark">{{ $activity->company->name ?? $activity->subscriber_name ?? 'Plan sale' }}</div>
                                                                    <div class="text-muted">{{ $activity->plan_name ?? $activity->plan ?? 'Subscription' }}</div>
                                                                </div>
                                                                <div class="small text-end">
                                                                    <div class="fw-bold text-primary">₦{{ number_format((float) ($activity->amount ?? 0), 0) }}</div>
                                                                    <div class="text-muted">{{ optional($activity->created_at)->diffForHumans() }}</div>
                                                                </div>
                                                            </div>
                                                        </li>
                                                    @empty
                                                        <li class="small text-muted">No paid plan sales captured yet.</li>
                                                    @endforelse
                                                </ul>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="col-12 col-xl-6">
                                <div class="card card-rounded shadow-sm h-100">
                                    <div class="card-body">
                                        <div class="d-flex justify-content-between align-items-center mb-3">
                                            <div>
                                                <h5 class="mb-0 fw-bold text-dark">Approval Conversion Radar</h5>
                                                <small class="text-muted">A compact view of manager pipeline and tenant readiness</small>
                                            </div>
                                            <span class="live-badge-soft animate-pulse">Live</span>
                                        </div>
                                        @php
                                            $pendingManagers = (int) ($metrics['pending_managers'] ?? 0);
                                            $activeManagers = (int) ($metrics['active_managers'] ?? 0);
                                            $suspendedManagers = (int) ($metrics['suspended_managers'] ?? 0);
                                            $pendingSetups = (int) ($metrics['pending_setups'] ?? 0);
                                            $paidSubs = (int) ($metrics['paid_subs'] ?? 0);
                                            $expiredSubs = (int) ($metrics['expired_subs'] ?? 0);
                                            $approvalPool = max(1, $pendingManagers + $activeManagers + $suspendedManagers);
                                            $setupPool = max(1, $pendingSetups + $paidSubs + $expiredSubs);
                                            $approvalMix = [
                                                ['label' => 'Pending Review', 'value' => $pendingManagers, 'color' => '#f59e0b', 'pct' => round(($pendingManagers / $approvalPool) * 100)],
                                                ['label' => 'Approved', 'value' => $activeManagers, 'color' => '#2563eb', 'pct' => round(($activeManagers / $approvalPool) * 100)],
                                                ['label' => 'Suspended', 'value' => $suspendedManagers, 'color' => '#ef4444', 'pct' => round(($suspendedManagers / $approvalPool) * 100)],
                                                ['label' => 'Pending Setups', 'value' => $pendingSetups, 'color' => '#8b5cf6', 'pct' => round(($pendingSetups / $setupPool) * 100)],
                                                ['label' => 'Paid Subs', 'value' => $paidSubs, 'color' => '#10b981', 'pct' => round(($paidSubs / $setupPool) * 100)],
                                                ['label' => 'Expired Plans', 'value' => $expiredSubs, 'color' => '#64748b', 'pct' => round(($expiredSubs / $setupPool) * 100)],
                                            ];
                                        @endphp
                                        <div class="d-grid gap-3">
                                            @foreach($approvalMix as $mix)
                                                <div class="p-3 rounded border bg-light-subtle">
                                                    <div class="d-flex justify-content-between align-items-center mb-2">
                                                        <div class="small fw-semibold text-dark">{{ $mix['label'] }}</div>
                                                        <div class="small text-muted">{{ number_format($mix['value']) }} • {{ $mix['pct'] }}%</div>
                                                    </div>
                                                    <div class="progress" style="height: 10px;">
                                                        <div class="progress-bar" role="progressbar" style="width: {{ max(6, $mix['pct']) }}%; background: {{ $mix['color'] }};" aria-valuenow="{{ $mix['pct'] }}" aria-valuemin="0" aria-valuemax="100"></div>
                                                    </div>
                                                </div>
                                            @endforeach
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="row mt-4 dashboard-row-balanced">

                            <div class="col-12 col-xl-7 dashboard-stack">

                                <div class="card card-rounded shadow-sm">
                                    <div class="card-body">
                                        <div class="d-flex justify-content-between align-items-center mb-2">
                                            <div>
                                                <h4 class="card-title card-title-dash">Revenue Growth Trajectory</h4>
                                                <p class="card-subtitle card-subtitle-dash">Year-over-year financial performance</p>
                                            </div>
                                            <div class="dropdown">
                                                <button class="btn btn-light dropdown-toggle" type="button" id="yearFilter" data-bs-toggle="dropdown" aria-expanded="false">
                                                    {{ date('Y') }}
                                                </button>
                                            </div>
                                        </div>
                                        <div class="chartjs-wrapper mt-4">
                                            <canvas id="revenueTrendChart"></canvas>
                                        </div>
                                        @php
                                            $revenueSeries = collect($dashboardChartSeries['revenue'] ?? []);
                                            $revenueLabels = collect($dashboardChartSeries['labels'] ?? []);
                                            $peakRevenue = (float) $revenueSeries->max();
                                            $peakRevenueIndex = $revenueSeries->search($peakRevenue);
                                            $peakRevenueMonth = $peakRevenueIndex !== false ? ($revenueLabels->get($peakRevenueIndex) ?? 'N/A') : 'N/A';
                                            $activeRevenueMonths = $revenueSeries->filter(fn ($value) => (float) $value > 0)->count();
                                        @endphp
                                        <div class="dashboard-micro-grid mt-3">
                                            <div class="summary-fill">
                                                <div class="label">Year Revenue</div>
                                                <div class="value">₦{{ number_format((float) $revenueSeries->sum(), 2) }}</div>
                                            </div>
                                            <div class="summary-fill">
                                                <div class="label">Peak Month</div>
                                                <div class="value">{{ $peakRevenueMonth }}</div>
                                            </div>
                                            <div class="summary-fill">
                                                <div class="label">Active Months</div>
                                                <div class="value">{{ number_format($activeRevenueMonths) }}</div>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div class="card card-rounded shadow-sm">
                                    <div class="card-body">
                                        <h4 class="card-title card-title-dash mb-4">System Capacity & Health</h4>

                                        @php
                                            $activeTenantPct = $systemHealth['company_provisioning_rate'] ?? 0;
                                            $managerHealthPct = $systemHealth['manager_verification_rate'] ?? 0;
                                            $paymentSuccessPct = $systemHealth['payment_success_rate'] ?? 0;
                                            $verifiedUsersPct = $systemHealth['user_verification_rate'] ?? 0;
                                        @endphp

                                        <div class="mb-3">
                                            <div class="d-flex justify-content-between align-items-center mb-1">
                                                <span class="text-muted small">Company Provisioning Rate</span>
                                                <span class="fw-bold text-dark small">{{ $activeTenantPct }}%</span>
                                            </div>
                                            <div class="progress progress-md" style="height: 8px;">
                                                <div class="progress-bar bg-success" role="progressbar" style="width: {{ $activeTenantPct }}%" aria-valuenow="{{ $activeTenantPct }}" aria-valuemin="0" aria-valuemax="100"></div>
                                            </div>
                                        </div>

                                        <div class="mb-3">
                                            <div class="d-flex justify-content-between align-items-center mb-1">
                                                <span class="text-muted small">Deployment Manager Verification</span>
                                                <span class="fw-bold text-dark small">{{ $managerHealthPct }}%</span>
                                            </div>
                                            <div class="progress progress-md" style="height: 8px;">
                                                <div class="progress-bar bg-warning" role="progressbar" style="width: {{ $managerHealthPct }}%" aria-valuenow="{{ $managerHealthPct }}" aria-valuemin="0" aria-valuemax="100"></div>
                                            </div>
                                        </div>

                                        <div class="mb-3">
                                            <div class="d-flex justify-content-between align-items-center mb-1">
                                                <span class="text-muted small">Payment Success Rate</span>
                                                <span class="fw-bold text-dark small">{{ $paymentSuccessPct }}%</span>
                                            </div>
                                            <div class="progress progress-md" style="height: 8px;">
                                                <div class="progress-bar bg-primary" role="progressbar" style="width: {{ $paymentSuccessPct }}%" aria-valuenow="{{ $paymentSuccessPct }}" aria-valuemin="0" aria-valuemax="100"></div>
                                            </div>
                                        </div>

                                        <div class="mb-3">
                                            <div class="d-flex justify-content-between align-items-center mb-1">
                                                <span class="text-muted small">Verified Users</span>
                                                <span class="fw-bold text-dark small">{{ $verifiedUsersPct }}%</span>
                                            </div>
                                            <div class="progress progress-md" style="height: 8px;">
                                                <div class="progress-bar bg-info" role="progressbar" style="width: {{ $verifiedUsersPct }}%" aria-valuenow="{{ $verifiedUsersPct }}" aria-valuemin="0" aria-valuemax="100"></div>
                                            </div>
                                        </div>

                                        <div class="mb-1">
                                            <div class="d-flex justify-content-between align-items-center mb-1">
                                                <span class="text-muted small">Subscription Activation</span>
                                                <span class="fw-bold text-dark small">{{ $activeTenantPct }}%</span>
                                            </div>
                                            <div class="progress progress-md" style="height: 8px;">
                                                <div class="progress-bar bg-secondary" role="progressbar" style="width: {{ $activeTenantPct }}%" aria-valuenow="{{ $activeTenantPct }}" aria-valuemin="0" aria-valuemax="100"></div>
                                            </div>
                                        </div>

                                    </div>
                                </div>

                                <div class="card card-rounded shadow-sm">
                                    <div class="card-body">
                                        <div class="d-flex justify-content-between align-items-center mb-3">
                                            <h5 class="mb-0 fw-bold text-dark">Platform Pulse</h5>
                                            <span class="badge bg-success-subtle text-success">Realtime</span>
                                        </div>
                                        <p class="text-muted small mb-3">Manager approval health and verification pressure at a glance.</p>
                                        @php
                                            $pendingManagers = (int) ($metrics['pending_managers'] ?? 0);
                                            $activeManagers = (int) ($metrics['active_managers'] ?? 0);
                                            $suspendedManagers = (int) ($metrics['suspended_managers'] ?? 0);
                                            $verifiedUsers = (int) ($metrics['verified_users'] ?? 0);
                                            $totalRegisteredUsers = (int) ($metrics['total_users'] ?? 0);
                                            $managerUniverse = $pendingManagers + $activeManagers + $suspendedManagers;
                                        @endphp
                                        <div class="pulse-chart-shell">
                                            <div class="pulse-chart-wrap">
                                                <canvas id="managerStatusPieChart"></canvas>
                                                <div class="pulse-center-badge">
                                                    <div>
                                                        <div class="value">{{ number_format($managerUniverse) }}</div>
                                                        <div class="label">Managers</div>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="pulse-legend-grid">
                                                <div class="pulse-legend-item">
                                                    <div class="topline">
                                                        <span class="swatch" style="background:#f59e0b;"></span>
                                                        <span class="label">Pending</span>
                                                    </div>
                                                    <div class="value">{{ number_format($pendingManagers) }}</div>
                                                    <div class="note">Awaiting superadmin approval.</div>
                                                </div>
                                                <div class="pulse-legend-item">
                                                    <div class="topline">
                                                        <span class="swatch" style="background:#2563eb;"></span>
                                                        <span class="label">Approved</span>
                                                    </div>
                                                    <div class="value">{{ number_format($activeManagers) }}</div>
                                                    <div class="note">Currently active deployment managers.</div>
                                                </div>
                                                <div class="pulse-legend-item">
                                                    <div class="topline">
                                                        <span class="swatch" style="background:#ef4444;"></span>
                                                        <span class="label">Suspended</span>
                                                    </div>
                                                    <div class="value">{{ number_format($suspendedManagers) }}</div>
                                                    <div class="note">Managers under restricted access.</div>
                                                </div>
                                                <div class="pulse-legend-item">
                                                    <div class="topline">
                                                        <span class="swatch" style="background:#10b981;"></span>
                                                        <span class="label">Verified Users</span>
                                                    </div>
                                                    <div class="value">{{ number_format($verifiedUsers) }}</div>
                                                    <div class="note">{{ number_format($totalRegisteredUsers) }} registered users currently tracked.</div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div class="card card-rounded shadow-sm">
                                    <div class="card-body">
                                        <div class="d-flex justify-content-between align-items-center mb-3">
                                            <div>
                                                <h5 class="mb-0 fw-bold text-dark">Subscription Intake Monitor</h5>
                                                <small class="text-muted">Live onboarding and plan conversion status</small>
                                            </div>
                                            <span class="live-badge-soft animate-pulse">Live</span>
                                        </div>
                                        <div class="dashboard-side-fill">
                                            <div class="summary-fill">
                                                <div class="label">Recent Signups (30d)</div>
                                                <div class="value">{{ number_format($metrics['recent_signups'] ?? 0) }}</div>
                                            </div>
                                            <div class="summary-fill">
                                                <div class="label">Pending Setups</div>
                                                <div class="value">{{ number_format($metrics['pending_setups'] ?? 0) }}</div>
                                            </div>
                                            <div class="summary-fill">
                                                <div class="label">Paid Subscriptions</div>
                                                <div class="value">{{ number_format($metrics['paid_subs'] ?? 0) }}</div>
                                            </div>
                                            <div class="summary-fill">
                                                <div class="label">Expired Plans</div>
                                                <div class="value">{{ number_format($metrics['expired_subs'] ?? 0) }}</div>
                                            </div>
                                        </div>
                                        <div class="row g-2 mt-3">
                                            @foreach([
                                                ['label' => 'Average Plan Sale', 'value' => '₦' . number_format($metrics['avg_plan_sale'] ?? 0, 0), 'tone' => 'tone-amber'],
                                                ['label' => 'Monthly Plan Value', 'value' => '₦' . number_format($metrics['plan_sales_value_month'] ?? 0, 0), 'tone' => 'tone-violet'],
                                                ['label' => 'Users', 'value' => number_format($metrics['total_users'] ?? 0), 'tone' => 'tone-sky'],
                                                ['label' => 'Verified Users', 'value' => number_format($metrics['verified_users'] ?? 0), 'tone' => 'tone-emerald'],
                                            ] as $monitorStat)
                                                <div class="col-sm-6">
                                                    <div class="summary-fill {{ $monitorStat['tone'] }}">
                                                        <div class="label">{{ $monitorStat['label'] }}</div>
                                                        <div class="value">{{ $monitorStat['value'] }}</div>
                                                    </div>
                                                </div>
                                            @endforeach
                                        </div>
                                    </div>
                                </div>

                                <div class="card card-rounded shadow-sm">
                                    <div class="card-body">
                                        <div class="d-flex justify-content-between align-items-center mb-2">
                                            <div>
                                                <h5 class="mb-0 fw-bold text-dark">Subscriber Momentum</h5>
                                                <small class="text-muted">Active tenants vs registered users trend</small>
                                            </div>
                                            <span class="live-badge-soft">Live</span>
                                        </div>
                                        <div class="chart-container chart-container-sm mt-3">
                                            <canvas id="subscriberMomentumChart"></canvas>
                                        </div>
                                        <div class="row g-2 mt-2">
                                            <div class="col-sm-6">
                                                <div class="summary-fill">
                                                    <div class="label">Active Tenants</div>
                                                    <div class="value">{{ number_format($metrics['total_tenants'] ?? 0) }}</div>
                                                </div>
                                            </div>
                                            <div class="col-sm-6">
                                                <div class="summary-fill">
                                                    <div class="label">Total Users</div>
                                                    <div class="value">{{ number_format($metrics['total_users'] ?? 0) }}</div>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="row g-2 mt-3">
                                            @foreach([
                                                ['label' => 'Verified Users', 'value' => number_format($metrics['verified_users'] ?? 0)],
                                                ['label' => 'Active Subs', 'value' => number_format($metrics['active_subs'] ?? 0)],
                                                ['label' => 'Direct Paid Buyers', 'value' => number_format($metrics['direct_paid_subs'] ?? 0)],
                                                ['label' => 'Deployment Paid Buyers', 'value' => number_format($metrics['deployment_paid_subs'] ?? 0)],
                                            ] as $momentumStat)
                                                <div class="col-sm-6 col-xl-3">
                                                    <div class="summary-fill h-100">
                                                        <div class="label">{{ $momentumStat['label'] }}</div>
                                                        <div class="value">{{ $momentumStat['value'] }}</div>
                                                    </div>
                                                </div>
                                            @endforeach
                                        </div>
                                    </div>
                                </div>

                                <div class="card card-rounded shadow-sm">
                                    <div class="card-body">
                                        <div class="d-flex justify-content-between align-items-center mb-3">
                                            <div>
                                                <h5 class="mb-0 fw-bold text-dark">Subscription Side Snapshot</h5>
                                                <small class="text-muted">Compact companion metrics beside health mix</small>
                                            </div>
                                            <span class="live-badge-soft">Live</span>
                                        </div>
                                        <div class="row g-2">
                                            @foreach([
                                                ['label' => 'Direct Revenue', 'value' => '₦' . number_format($metrics['direct_subscription_revenue'] ?? 0, 0), 'tone' => 'tone-amber'],
                                                ['label' => 'Deployment Revenue', 'value' => '₦' . number_format($metrics['deployment_subscription_revenue'] ?? 0, 0), 'tone' => 'tone-violet'],
                                                ['label' => 'Pending Setups', 'value' => number_format($metrics['pending_setups'] ?? 0), 'tone' => 'tone-rose'],
                                                ['label' => 'Expiring Soon', 'value' => number_format($metrics['expiring_soon_subs'] ?? 0), 'tone' => 'tone-sky'],
                                                ['label' => 'Registered Revenue', 'value' => '₦' . number_format($metrics['registered_user_revenue'] ?? 0, 0), 'tone' => 'tone-emerald'],
                                                ['label' => 'Avg Plan Sale', 'value' => '₦' . number_format($metrics['avg_plan_sale'] ?? 0, 0), 'tone' => 'tone-cobalt'],
                                            ] as $sideMini)
                                                <div class="col-sm-6 col-xl-4">
                                                    <div class="summary-fill {{ $sideMini['tone'] }} h-100">
                                                        <div class="label">{{ $sideMini['label'] }}</div>
                                                        <div class="value">{{ $sideMini['value'] }}</div>
                                                    </div>
                                                </div>
                                            @endforeach
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="col-12 col-xl-5 grid-margin dashboard-stack">
                                <div class="card card-rounded shadow-sm">
                                    <div class="card-body">
                                        <div class="d-flex justify-content-between align-items-center mb-2">
                                            <div>
                                                <h5 class="mb-0 fw-bold text-dark">Subscription Health Mix</h5>
                                                <small class="text-muted">Real-time onboarding and renewal balance</small>
                                            </div>
                                            <span class="badge bg-primary-subtle text-primary">Live</span>
                                        </div>
                                        @php
                                            $pendingSetups = (int) ($metrics['pending_setups'] ?? 0);
                                            $paidSubs = (int) ($metrics['paid_subs'] ?? 0);
                                            $expiredSubs = (int) ($metrics['expired_subs'] ?? 0);
                                            $recentSignups = (int) ($metrics['recent_signups'] ?? 0);
                                        @endphp
                                        <div class="row g-3 align-items-center">
                                            <div class="col-12">
                                                <div style="height: 220px;">
                                                    <canvas id="subscriptionMixChart"></canvas>
                                                </div>
                                            </div>
                                            <div class="col-12">
                                                <div class="d-flex flex-wrap gap-3 small text-muted">
                                                    <div><span class="badge-dot bg-primary me-2"></span>Paid: {{ number_format($paidSubs) }}</div>
                                                    <div><span class="badge-dot bg-warning me-2"></span>Pending: {{ number_format($pendingSetups) }}</div>
                                                    <div><span class="badge-dot bg-danger me-2"></span>Expired: {{ number_format($expiredSubs) }}</div>
                                                    <div><span class="badge-dot bg-info me-2"></span>New (30d): {{ number_format($recentSignups) }}</div>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="row g-2 mt-3">
                                            @foreach([
                                                ['label' => 'Direct Revenue', 'value' => '₦' . number_format($metrics['direct_subscription_revenue'] ?? 0, 0)],
                                                ['label' => 'Deployment Revenue', 'value' => '₦' . number_format($metrics['deployment_subscription_revenue'] ?? 0, 0)],
                                                ['label' => 'Direct Buyers', 'value' => number_format($metrics['direct_paid_subs'] ?? 0)],
                                                ['label' => 'Deployment Buyers', 'value' => number_format($metrics['deployment_paid_subs'] ?? 0)],
                                            ] as $mixMini)
                                                <div class="col-sm-6">
                                                    <div class="summary-fill h-100">
                                                        <div class="label">{{ $mixMini['label'] }}</div>
                                                        <div class="value">{{ $mixMini['value'] }}</div>
                                                    </div>
                                                </div>
                                            @endforeach
                                        </div>
                                    </div>
                                </div>

                                <div class="card card-rounded shadow-sm">
                                    <div class="card-body">
                                        <div class="d-flex justify-content-between align-items-center mb-3">
                                            <h4 class="card-title card-title-dash">Deployment Manager Authorization</h4>
                                            @if($metrics['pending_managers'] > 0)
                                                <span class="badge rounded-pill bg-danger">{{ $metrics['pending_managers'] }} Pending</span>
                                            @endif
                                        </div>
                                        <p class="card-subtitle card-subtitle-dash text-muted mb-3">Approve or decline deployment manager access</p>
                                        <div class="row g-2 mb-3">
                                            @foreach([
                                                ['label' => 'Approved Managers', 'value' => number_format($metrics['active_managers'] ?? 0)],
                                                ['label' => 'Suspended Managers', 'value' => number_format($metrics['suspended_managers'] ?? 0)],
                                                ['label' => 'Pending Managers', 'value' => number_format($metrics['pending_managers'] ?? 0)],
                                                ['label' => 'Approval Rate', 'value' => number_format(((int) (($metrics['active_managers'] ?? 0) + ($metrics['pending_managers'] ?? 0) + ($metrics['suspended_managers'] ?? 0))) > 0 ? (((float) ($metrics['active_managers'] ?? 0)) / max(1, (int) (($metrics['active_managers'] ?? 0) + ($metrics['pending_managers'] ?? 0) + ($metrics['suspended_managers'] ?? 0)))) * 100 : 0, 1) . '%'],
                                            ] as $managerStat)
                                                <div class="col-sm-6 col-xl-3">
                                                    <div class="summary-fill h-100">
                                                        <div class="label">{{ $managerStat['label'] }}</div>
                                                        <div class="value">{{ $managerStat['value'] }}</div>
                                                    </div>
                                                </div>
                                            @endforeach
                                        </div>
                                        <div class="table-responsive" style="max-height: 520px; overflow-y: auto;">
                                            <table class="table table-hover align-middle">
                                                <thead class="bg-light sticky-top">
                                                    <tr>
                                                        <th>Manager Profile</th>
                                                        <th>Status</th>
                                                        <th class="text-center">Action</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    @forelse($deployments as $manager)
                                                    <tr>
                                                        <td>
                                                            <div class="d-flex align-items-center">
                                                                <div class="avatar-sm me-2 bg-soft-primary rounded-circle text-center fw-bold d-flex align-items-center justify-content-center" style="width:32px; height:32px;">
                                                                    {{ strtoupper(substr($manager->manager_name, 0, 1)) }}
                                                                </div>
                                                                <div>
                                                                    <p class="fw-bold mb-0" style="font-size: 0.85rem;">{{ $manager->manager_name }}</p>
                                                                    <small class="text-muted">{{ $manager->email }}</small>
                                                                </div>
                                                            </div>
                                                        </td>
                                                        <td>
                                                            @if(in_array($manager->status, ['pending', 'pending_info']))
                                                                <span class="badge badge-dot bg-warning me-1"></span> Pending
                                                            @elseif($manager->status == 'active')
                                                                <span class="badge badge-dot bg-success me-1"></span> Active
                                                            @else
                                                                <span class="badge bg-secondary">{{ ucfirst($manager->status) }}</span>
                                                            @endif
                                                        </td>
                                                        <td class="text-center">
                                                            @if(in_array($manager->status, ['pending', 'pending_info']))
                                                                <form action="{{ route('super_admin.managers.approve', $manager->id) }}" method="POST" class="d-inline">
                                                                    @csrf
                                                                    <button type="submit" class="btn btn-inverse-success btn-icon btn-sm" title="Approve"><i class="mdi mdi-check"></i></button>
                                                                </form>
                                                                <button type="button" class="btn btn-inverse-danger btn-icon btn-sm ms-1" data-bs-toggle="modal" data-bs-target="#rejectModal{{$manager->id}}" title="Decline"><i class="mdi mdi-close"></i></button>

                                                                <div class="modal fade" id="rejectModal{{$manager->id}}" tabindex="-1">
                                                                    <div class="modal-dialog">
                                                                        <form class="modal-content" action="{{ route('super_admin.managers.reject', $manager->id) }}" method="POST">
                                                                            @csrf
                                                                            <div class="modal-header">
                                                                                <h5 class="modal-title">Decline Manager</h5>
                                                                                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                                                            </div>
                                                                            <div class="modal-body">
                                                                                <label class="form-label">Reason for Rejection</label>
                                                                                <textarea name="reason" class="form-control" rows="3" required></textarea>
                                                                            </div>
                                                                            <div class="modal-footer">
                                                                                <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                                                                                <button type="submit" class="btn btn-danger text-white">Confirm</button>
                                                                            </div>
                                                                        </form>
                                                                    </div>
                                                                </div>
                                                            @else
                                                                <i class="mdi mdi-shield-check text-primary" title="Verified"></i>
                                                            @endif
                                                        </td>
                                                    </tr>
                                                    @empty
                                                    <tr><td colspan="3" class="text-center py-4 text-muted">No pending manager authorizations.</td></tr>
                                                    @endforelse
                                                </tbody>
                                            </table>
                                        </div>
                                        <div class="mt-3 p-3 rounded border bg-light">
                                            <div class="d-flex justify-content-between align-items-center mb-2">
                                                <h6 class="mb-0 fw-semibold">Top Performing Managers (Top 3)</h6>
                                                <span class="badge bg-primary-subtle text-primary">Live Rank</span>
                                            </div>
                                            @php
                                                $managerRows = $managerPerformance['rows'] ?? [];
                                                $managerMax = (float) ($managerPerformance['max'] ?? 1);
                                                $barColors = ['success', 'primary', 'info', 'warning', 'danger'];
                                            @endphp
                                            @if(!empty($managerRows))
                                                <div class="d-flex flex-column gap-2">
                                                    @foreach($managerRows as $i => $m)
                                                        @php
                                                            $score = (float) ($m['score'] ?? 0);
                                                            $pct = max(3, min(100, $managerMax > 0 ? ($score / $managerMax) * 100 : 0));
                                                            $c = $barColors[$i % count($barColors)];
                                                        @endphp
                                                        <div>
                                                            <div class="d-flex justify-content-between align-items-center small mb-1">
                                                                <span class="text-dark fw-semibold">{{ \Illuminate\Support\Str::limit($m['name'] ?? 'Manager', 28) }}</span>
                                                                <span class="text-muted">
                                                                    @if((float) ($m['revenue'] ?? 0) > 0)
                                                                        ₦{{ number_format((float) ($m['revenue'] ?? 0), 0) }}
                                                                    @else
                                                                        {{ number_format((int) ($m['plan_sales'] ?? 0)) }} plans
                                                                    @endif
                                                                </span>
                                                            </div>
                                                            <div class="progress" style="height: 9px;">
                                                                <div class="progress-bar bg-{{ $c }}" role="progressbar" style="width: {{ $pct }}%" aria-valuenow="{{ (int) $pct }}" aria-valuemin="0" aria-valuemax="100"></div>
                                                            </div>
                                                            <div class="small text-muted mt-1">
                                                                {{ number_format((int) ($m['plan_sales'] ?? 0)) }} plan sales
                                                                • {{ number_format((int) ($m['deployments'] ?? 0)) }} deployments
                                                                • {{ ucfirst((string) ($m['status'] ?? 'pending')) }}
                                                            </div>
                                                        </div>
                                                    @endforeach
                                                </div>
                                            @else
                                                <div class="small text-muted mt-2">No manager performance data yet.</div>
                                            @endif
                                        </div>
                                    </div>
                                </div>

                                <div class="card card-rounded shadow-sm">
                                    <div class="card-body">
                                        <div class="d-flex justify-content-between align-items-center mb-2">
                                            <div>
                                                <h5 class="mb-0 fw-bold text-dark">Growth Comparison</h5>
                                                <small class="text-muted">Companies, users, and paid plans moving together</small>
                                            </div>
                                            <span class="live-badge-soft">Live</span>
                                        </div>
                                        <div class="chart-container chart-container-sm mt-3">
                                            <canvas id="growthComparisonChart"></canvas>
                                        </div>
                                        <div class="row g-2 mt-2">
                                            <div class="col-sm-4">
                                                <div class="summary-fill">
                                                    <div class="label">Companies</div>
                                                    <div class="value">{{ number_format(array_sum($dashboardChartSeries['companies'] ?? [])) }}</div>
                                                </div>
                                            </div>
                                            <div class="col-sm-4">
                                                <div class="summary-fill">
                                                    <div class="label">Users</div>
                                                    <div class="value">{{ number_format(array_sum($dashboardChartSeries['users'] ?? [])) }}</div>
                                                </div>
                                            </div>
                                            <div class="col-sm-4">
                                                <div class="summary-fill">
                                                    <div class="label">Paid Plans</div>
                                                    <div class="value">{{ number_format(array_sum($dashboardChartSeries['orders'] ?? [])) }}</div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div class="card card-rounded shadow-sm">
                                    <div class="card-body">
                                        <div class="d-flex justify-content-between align-items-center mb-2">
                                            <div>
                                                <h5 class="mb-0 fw-bold text-dark">Subscription Health Mix</h5>
                                                <small class="text-muted">Readiness, paid conversions, and renewal pressure</small>
                                            </div>
                                            <span class="live-badge-soft">Live</span>
                                        </div>
                                        <div class="row g-3 align-items-center mt-1">
                                            <div class="col-md-6">
                                                <div class="chart-container chart-container-sm">
                                                    <canvas id="subscriptionHealthMixChart"></canvas>
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <div class="d-grid gap-2">
                                                    @foreach([
                                                        ['label' => 'Paid', 'value' => number_format($metrics['paid_subs'] ?? 0), 'tone' => 'tone-emerald'],
                                                        ['label' => 'Pending Setup', 'value' => number_format($metrics['pending_setups'] ?? 0), 'tone' => 'tone-violet'],
                                                        ['label' => 'Expiring Soon', 'value' => number_format($metrics['expiring_soon_subs'] ?? 0), 'tone' => 'tone-amber'],
                                                        ['label' => 'Expired', 'value' => number_format($metrics['expired_subs'] ?? 0), 'tone' => 'tone-rose'],
                                                    ] as $subMix)
                                                        <div class="summary-fill {{ $subMix['tone'] }}">
                                                            <div class="label">{{ $subMix['label'] }}</div>
                                                            <div class="value">{{ $subMix['value'] }}</div>
                                                        </div>
                                                    @endforeach
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                            </div>
                        </div>

                        <div class="row dashboard-row-balanced">

                            <div class="col-12 col-xl-5 grid-margin dashboard-stack">

                                <div class="card card-rounded shadow-sm">
                                    <div class="card-body">
                                        <div class="dashboard-split-card">
                                        <div class="d-flex justify-content-between align-items-center mb-3">
                                            <div>
                                                <h4 class="card-title card-title-dash mb-1">New Company Registrations</h4>
                                                <p class="card-subtitle card-subtitle-dash mb-0 small">Latest organizations that purchased plans</p>
                                            </div>
                                            <a href="#" class="btn btn-link text-primary fw-bold text-decoration-none small">View All</a>
                                        </div>
                                        <div class="table-responsive table-wrap-lock" style="max-height: 320px; overflow-y: auto;">
                                            <table class="table table-striped table-borderless table-sm">
                                                <thead class="sticky-top bg-white">
                                                    <tr class="text-muted border-bottom">
                                                        <th class="small">Company</th>
                                                        <th class="small">Owner</th>
                                                        <th class="small">Plan</th>
                                                        <th class="small">Status</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    @forelse($recentTenants as $tenant)
                                                    <tr>
                                                        <td class="fw-bold text-dark small">{{ $tenant->name }}</td>
                                                        <td class="small">{{ $tenant->user->name ?? 'N/A' }}</td>
                                                        <td>
                                                            <span class="badge bg-soft-primary text-primary small">
                                                                {{ $tenant->subscription->plan_name ?? $tenant->subscription->plan ?? $tenant->plan ?? 'Basic' }}
                                                            </span>
                                                        </td>
                                                        <td>
                                                            <span class="badge {{ $tenant->status == 'active' ? 'bg-soft-success text-success' : 'bg-soft-warning text-warning' }} small">
                                                                {{ strtoupper($tenant->status) }}
                                                            </span>
                                                        </td>
                                                    </tr>
                                                    @empty
                                                    <tr><td colspan="4" class="text-center p-4 text-muted small">No new registrations yet.</td></tr>
                                                    @endforelse
                                                </tbody>
                                            </table>
                                        </div>
                                        <div class="row g-2 mt-2">
                                            <div class="col-sm-6">
                                                <div class="summary-fill">
                                                    <div class="label">Latest Company Count</div>
                                                    <div class="value">{{ number_format($recentTenants->count() ?? 0) }}</div>
                                                </div>
                                            </div>
                                            <div class="col-sm-6">
                                                <div class="summary-fill">
                                                    <div class="label">Active Registrations</div>
                                                    <div class="value">{{ number_format(($recentTenants ?? collect())->where('status', 'active')->count()) }}</div>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="row g-2 mt-2">
                                            @foreach([
                                                ['label' => 'Deployment-Sourced', 'value' => number_format(($recentTenants ?? collect())->filter(fn($tenant) => !empty($tenant->deployed_by))->count())],
                                                ['label' => 'Direct-Sourced', 'value' => number_format(($recentTenants ?? collect())->filter(fn($tenant) => empty($tenant->deployed_by))->count())],
                                                ['label' => 'Paid Nodes', 'value' => number_format($metrics['paid_subs'] ?? 0)],
                                                ['label' => 'Pending Setup', 'value' => number_format($metrics['pending_setups'] ?? 0)],
                                            ] as $tenantMini)
                                                <div class="col-sm-6 col-xl-3">
                                                    <div class="summary-fill h-100">
                                                        <div class="label">{{ $tenantMini['label'] }}</div>
                                                        <div class="value">{{ $tenantMini['value'] }}</div>
                                                    </div>
                                                </div>
                                            @endforeach
                                        </div>
                                        </div>
                                    </div>
                                </div>

                                <div class="card card-rounded shadow-sm">
                                    <div class="card-body">
                                        <div class="dashboard-split-card">
                                        <div class="d-flex justify-content-between align-items-center mb-3">
                                            <div>
                                                <h4 class="card-title card-title-dash mb-1">Latest User Registrations</h4>
                                                <p class="card-subtitle card-subtitle-dash mb-0 small">Newest normal signups and plan users</p>
                                            </div>
                                            <span class="live-badge-soft">Live</span>
                                        </div>
                                        <div class="table-responsive table-wrap-lock" style="max-height: 320px; overflow-y: auto;">
                                            <table class="table table-striped table-borderless table-sm">
                                                <thead class="sticky-top bg-white">
                                                    <tr class="text-muted border-bottom">
                                                        <th class="small">User</th>
                                                        <th class="small">Plan</th>
                                                        <th class="small">Company</th>
                                                        <th class="small">Joined</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    @forelse($recentUsers as $recentUser)
                                                    <tr>
                                                        <td class="small">
                                                            <div class="fw-bold text-dark">{{ $recentUser->name ?? 'N/A' }}</div>
                                                            <div class="text-muted">{{ $recentUser->email ?? ($recentUser->phone ?? 'N/A') }}</div>
                                                        </td>
                                                        <td class="small">
                                                            <span class="badge bg-soft-primary text-primary">
                                                                {{ $recentUser->subscription->plan_name ?? $recentUser->subscription->plan ?? 'Pending' }}
                                                            </span>
                                                        </td>
                                                        <td class="small">{{ $recentUser->company->name ?? 'Not setup yet' }}</td>
                                                        <td class="small text-muted">{{ optional($recentUser->created_at)->diffForHumans() }}</td>
                                                    </tr>
                                                    @empty
                                                    <tr><td colspan="4" class="text-center p-4 text-muted small">No recent user registrations yet.</td></tr>
                                                    @endforelse
                                                </tbody>
                                            </table>
                                        </div>
                                        <div class="row g-2 mt-2">
                                            <div class="col-sm-6">
                                                <div class="summary-fill">
                                                    <div class="label">Recent Users</div>
                                                    <div class="value">{{ number_format($recentUsers->count()) }}</div>
                                                </div>
                                            </div>
                                            <div class="col-sm-6">
                                                <div class="summary-fill">
                                                    <div class="label">Awaiting Setup</div>
                                                    <div class="value">{{ number_format(($recentUsers ?? collect())->filter(fn($item) => empty(optional($item->company)->id))->count()) }}</div>
                                                </div>
                                            </div>
                                        </div>
                                        </div>
                                    </div>
                                </div>

                                <div class="card card-rounded shadow-sm">
                                    <div class="card-body">
                                        <div class="dashboard-split-card">
                                        <div class="d-flex justify-content-between mb-3">
                                            <div>
                                                <h4 class="card-title card-title-dash mb-1">Live Platform Activity</h4>
                                                <p class="card-subtitle card-subtitle-dash mb-0 small">Real-time transaction stream</p>
                                            </div>
                                            <span class="small">
                                                <i class="mdi mdi-circle-medium text-success animate-pulse"></i> Live
                                            </span>
                                        </div>
                                        <div class="list-wrapper" style="max-height: 220px; overflow-y: auto;">
                                            <ul class="bullet-line-list">
                                                @forelse($platformActivity as $activity)
                                                <li class="mb-2">
                                                    <div class="d-flex justify-content-between align-items-start">
                                                        <div>
                                                            <span class="text-primary fw-bold small">{{ $activity->company->name ?? $activity->subscriber_name ?? 'System' }}</span>
                                                            <span class="text-dark small">completed a transaction</span>
                                                        </div>
                                                        <small class="text-muted" style="font-size: 0.7rem;">{{ $activity->created_at->diffForHumans() }}</small>
                                                    </div>
                                                    <p class="text-muted mb-0" style="font-size: 0.75rem;">Ref: #{{ str_pad($activity->id, 6, '0', STR_PAD_LEFT) }} | Amount: ₦{{ number_format($activity->amount ?? 0, 2) }}</p>
                                                </li>
                                                @empty
                                                <p class="text-center py-4 text-muted small">No recent activity detected.</p>
                                                @endforelse
                                            </ul>
                                        </div>
                                        <div class="row g-2 mt-2">
                                            <div class="col-sm-6">
                                                <div class="summary-fill">
                                                    <div class="label">Activity Events</div>
                                                    <div class="value">{{ number_format($platformActivity->count() ?? 0) }}</div>
                                                </div>
                                            </div>
                                            <div class="col-sm-6">
                                                <div class="summary-fill">
                                                    <div class="label">Latest Stream Value</div>
                                                    <div class="value">₦{{ number_format((float) optional($platformActivity->first())->amount, 2) }}</div>
                                                </div>
                                            </div>
                                        </div>
                                        </div>
                                    </div>
                                </div>

                                <div class="card card-rounded shadow-sm">
                                    <div class="card-body">
                                        <div class="dashboard-split-card workspace-readiness-board">
                                            <div class="d-flex justify-content-between align-items-center mb-3">
                                                <div>
                                                    <h4 class="card-title card-title-dash mb-1">Workspace Readiness Board</h4>
                                                    <p class="card-subtitle card-subtitle-dash mb-0 small">Fresh tenant health, activation pace, and setup momentum</p>
                                                </div>
                                                <span class="live-badge-soft">Live</span>
                                            </div>

                                            <div class="row g-2 mb-2">
                                                @foreach([
                                                    ['label' => 'Recent Tenants', 'value' => number_format($recentTenants->count() ?? 0)],
                                                    ['label' => 'Paid Nodes', 'value' => number_format($metrics['paid_subs'] ?? 0)],
                                                    ['label' => 'Pending Setup', 'value' => number_format($metrics['pending_setups'] ?? 0)],
                                                    ['label' => 'Expiring Soon', 'value' => number_format($metrics['expiring_soon_subs'] ?? 0)],
                                                ] as $readinessItem)
                                                    <div class="col-sm-6">
                                                        <div class="summary-fill h-100">
                                                            <div class="label">{{ $readinessItem['label'] }}</div>
                                                            <div class="value">{{ $readinessItem['value'] }}</div>
                                                        </div>
                                                    </div>
                                                @endforeach
                                            </div>

                                            <div class="small text-muted fw-semibold mb-1">Newest Company Nodes</div>
                                            <div class="row g-2">
                                                @forelse(($recentTenants ?? collect())->take(4) as $tenant)
                                                    <div class="col-md-6">
                                                        <div class="summary-fill h-100">
                                                            <div class="label">{{ \Illuminate\Support\Str::limit($tenant->name ?? 'Tenant', 22) }}</div>
                                                            <div class="value">{{ $tenant->subscription->plan_name ?? $tenant->subscription->plan ?? 'Pending Plan' }}</div>
                                                            <div class="small text-muted mt-1">
                                                                {{ optional($tenant->created_at)->diffForHumans() ?? 'Recently' }}
                                                            </div>
                                                        </div>
                                                    </div>
                                                @empty
                                                    <div class="col-12">
                                                        <div class="summary-fill">
                                                            <div class="label">No recent companies</div>
                                                            <div class="value">Waiting for new onboarding</div>
                                                        </div>
                                                    </div>
                                                @endforelse
                                                @if(($recentTenants ?? collect())->count() < 2)
                                                    <div class="col-md-6">
                                                        <div class="summary-fill h-100">
                                                            <div class="label">Direct vs Deployment</div>
                                                            <div class="value">{{ number_format($metrics['direct_paid_subs'] ?? 0) }} / {{ number_format($metrics['deployment_paid_subs'] ?? 0) }}</div>
                                                            <div class="small text-muted mt-1">Paid buyers split</div>
                                                        </div>
                                                    </div>
                                                @endif
                                                @if(($recentTenants ?? collect())->count() < 3)
                                                    <div class="col-md-6">
                                                        <div class="summary-fill h-100">
                                                            <div class="label">Revenue Pressure</div>
                                                            <div class="value">₦{{ number_format((float) (($metrics['direct_subscription_revenue'] ?? 0) + ($metrics['deployment_subscription_revenue'] ?? 0)), 0) }}</div>
                                                            <div class="small text-muted mt-1">Current paid plan income</div>
                                                        </div>
                                                    </div>
                                                @endif
                                                @if(($recentTenants ?? collect())->count() < 4)
                                                    <div class="col-md-6">
                                                        <div class="summary-fill h-100">
                                                            <div class="label">Readiness Coverage</div>
                                                            <div class="value">{{ number_format($metrics['active_subs'] ?? 0) }}</div>
                                                            <div class="small text-muted mt-1">Active subscription nodes</div>
                                                        </div>
                                                    </div>
                                                @endif
                                            </div>
                                            <div class="row g-2 mt-2">
                                                @foreach([
                                                    ['label' => 'Active Recent', 'value' => number_format(($recentTenants ?? collect())->where('status', 'active')->count())],
                                                    ['label' => 'Deployment Recent', 'value' => number_format(($recentTenants ?? collect())->filter(fn($tenant) => !empty($tenant->deployed_by))->count())],
                                                    ['label' => 'Direct Recent', 'value' => number_format(($recentTenants ?? collect())->filter(fn($tenant) => empty($tenant->deployed_by))->count())],
                                                    ['label' => 'Verified Users', 'value' => number_format($metrics['verified_users'] ?? 0)],
                                                    ['label' => 'Recent Signups', 'value' => number_format($metrics['recent_signups'] ?? 0)],
                                                    ['label' => 'Direct Buyers', 'value' => number_format($metrics['direct_paid_subs'] ?? 0)],
                                                ] as $readinessMini)
                                                    <div class="col-sm-6 col-xl-4">
                                                        <div class="summary-fill h-100">
                                                            <div class="label">{{ $readinessMini['label'] }}</div>
                                                            <div class="value">{{ $readinessMini['value'] }}</div>
                                                        </div>
                                                    </div>
                                                @endforeach
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="col-12 col-xl-7 grid-margin dashboard-stack">
                                <div class="card card-rounded shadow-sm">
                                    <div class="card-body">
                                        <div class="d-flex justify-content-between align-items-center mb-3">
                                            <div>
                                                <h4 class="card-title card-title-dash">Regional Distribution</h4>
                                                <p class="card-subtitle card-subtitle-dash mb-0">Real-time geographic presence</p>
                                            </div>
                                            <span class="badge bg-opacity-10 bg-success text-success">
                                                <i class="mdi mdi-circle-medium animate-pulse"></i> Live
                                            </span>
                                        </div>
                                        <div id="regionMap"></div>
                                        <div class="table-responsive mt-3">
                                            <table class="table table-sm table-borderless">
                                                <thead>
                                                    <tr class="text-muted">
                                                        <th class="small">Region</th>
                                                        <th class="text-end small">Companies</th>
                                                        <th class="text-end small">Market %</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    @foreach($countryData as $country => $count)
                                                    @php $percent = $metrics['total_tenants'] > 0 ? ($count / $metrics['total_tenants']) * 100 : 0; @endphp
                                                    <tr>
                                                        <td class="small">
                                                            <i class="mdi mdi-map-marker text-danger me-1"></i>
                                                            {{ $country }}
                                                        </td>
                                                        <td class="text-end fw-bold small">{{ $count }}</td>
                                                        <td class="text-end small">{{ round($percent, 1) }}%</td>
                                                    </tr>
                                                    @endforeach
                                                </tbody>
                                            </table>
                                        </div>
                                        @php
                                            $topRegion = !empty($countryData) ? collect($countryData)->sortDesc()->keys()->first() : 'N/A';
                                            $regionsCount = !empty($countryData) ? count($countryData) : 0;
                                        @endphp
                                        <div class="mt-3 p-3 rounded-3 border bg-light">
                                            <div class="d-flex justify-content-between align-items-center mb-2">
                                                <h6 class="mb-0 fw-bold text-dark">Regional Snapshot</h6>
                                                <span class="badge bg-primary-subtle text-primary">Live</span>
                                            </div>
                                            <div class="row g-2">
                                                @foreach([
                                                    ['label' => 'Top Region', 'value' => $topRegion],
                                                    ['label' => 'Regions Covered', 'value' => number_format($regionsCount)],
                                                    ['label' => 'Active Tenants', 'value' => number_format($metrics['total_tenants'] ?? 0)],
                                                    ['label' => 'Active Subs', 'value' => number_format($metrics['active_subs'] ?? 0)],
                                                ] as $item)
                                                    <div class="col-6">
                                                        <div class="small text-muted">{{ $item['label'] }}</div>
                                                        <div class="fw-semibold text-dark">{{ $item['value'] }}</div>
                                                    </div>
                                                @endforeach
                                            </div>
                                            <div class="mt-3">
                                                <div class="small text-muted mb-1">Coverage Strength</div>
                                                @php
                                                    $coverageBase = max(1, (int)($metrics['total_tenants'] ?? 0));
                                                    $coveragePct = min(100, (int) round(($regionsCount / $coverageBase) * 100));
                                                @endphp
                                                <div class="progress" style="height: 8px;">
                                                    <div class="progress-bar bg-primary" role="progressbar" style="width: {{ $coveragePct }}%" aria-valuenow="{{ $coveragePct }}" aria-valuemin="0" aria-valuemax="100"></div>
                                                </div>
                                                <div class="small text-muted mt-2">{{ $coveragePct }}% region-to-tenant spread</div>
                                            </div>
                                            <div class="row g-2 mt-2">
                                                <div class="col-sm-6">
                                                    <div class="summary-fill">
                                                        <div class="label">Pending Setups</div>
                                                        <div class="value">{{ number_format($metrics['pending_setups'] ?? 0) }}</div>
                                                    </div>
                                                </div>
                                                <div class="col-sm-6">
                                                    <div class="summary-fill">
                                                        <div class="label">Paid Nodes</div>
                                                        <div class="value">{{ number_format($metrics['paid_subs'] ?? 0) }}</div>
                                                    </div>
                                                </div>
                                            </div>
                                                <div class="row g-2 mt-2">
                                                    @foreach([
                                                        ['label' => 'Verified Users', 'value' => number_format($metrics['verified_users'] ?? 0)],
                                                        ['label' => 'Direct Buyers', 'value' => number_format($metrics['direct_paid_subs'] ?? 0)],
                                                        ['label' => 'Deployment Buyers', 'value' => number_format($metrics['deployment_paid_subs'] ?? 0)],
                                                        ['label' => 'Registered Revenue', 'value' => '₦' . number_format($metrics['registered_user_revenue'] ?? 0, 0)],
                                                    ] as $regionMini)
                                                        <div class="col-sm-6 col-xl-3">
                                                            <div class="summary-fill h-100">
                                                                <div class="label">{{ $regionMini['label'] }}</div>
                                                                <div class="value">{{ $regionMini['value'] }}</div>
                                                            </div>
                                                        </div>
                                                    @endforeach
                                                </div>
                                        </div>
                                    </div>
                                </div>

                                <div class="card card-rounded shadow-sm">
                                    <div class="card-body">
                                        <div class="d-flex justify-content-between align-items-center mb-3">
                                            <div>
                                                <h5 class="mb-0 fw-bold text-dark">Registration Health</h5>
                                                <small class="text-muted">User onboarding, setup completion, and expiry pressure</small>
                                            </div>
                                            <span class="live-badge-soft">Live</span>
                                        </div>
                                        <div class="dashboard-side-fill">
                                            <div class="summary-fill">
                                                <div class="label">Recent Users</div>
                                                <div class="value">{{ number_format($recentUsers->count()) }}</div>
                                            </div>
                                            <div class="summary-fill">
                                                <div class="label">Recent Companies</div>
                                                <div class="value">{{ number_format($recentTenants->count() ?? 0) }}</div>
                                            </div>
                                            <div class="summary-fill">
                                                <div class="label">Awaiting Setup</div>
                                                <div class="value">{{ number_format(($recentUsers ?? collect())->filter(fn($item) => empty(optional($item->company)->id))->count()) }}</div>
                                            </div>
                                            <div class="summary-fill">
                                                <div class="label">Expiring Soon</div>
                                                <div class="value">{{ number_format($metrics['expiring_soon_subs'] ?? 0) }}</div>
                                            </div>
                                        </div>
                                        <div class="mt-3 pt-2 border-top">
                                            <div class="small text-muted mb-2 fw-semibold">Latest Setup Queue</div>
                                            <div class="row g-2">
                                                @forelse(($recentUsers ?? collect())->take(4) as $recentUser)
                                                    <div class="col-md-6">
                                                        <div class="summary-fill">
                                                            <div class="label">{{ $recentUser->company->name ?? 'Setup Pending' }}</div>
                                                            <div class="value">{{ \Illuminate\Support\Str::limit($recentUser->name ?? 'User', 22) }}</div>
                                                        </div>
                                                    </div>
                                                @empty
                                                    <div class="col-12">
                                                        <div class="small text-muted">No new users in queue.</div>
                                                    </div>
                                                @endforelse
                                            </div>
                                        </div>
                                        @php
                                            $paidSubs = (int) ($metrics['paid_subs'] ?? 0);
                                            $pendingSetups = (int) ($metrics['pending_setups'] ?? 0);
                                            $expiredSubs = (int) ($metrics['expired_subs'] ?? 0);
                                            $paidTone = 'bg-success';
                                            $pendingTone = 'bg-warning';
                                            $expiredTone = 'bg-danger';
                                            $paidDim = $paidSubs > 0 ? '' : 'opacity-25';
                                            $pendingDim = $pendingSetups > 0 ? '' : 'opacity-25';
                                            $expiredDim = $expiredSubs > 0 ? '' : 'opacity-25';
                                        @endphp
                                        <div class="mt-3 pt-2 border-top">
                                            <div class="d-flex justify-content-between align-items-center mb-2">
                                                <div class="small text-muted fw-semibold">Traffic Light Status</div>
                                                <span class="small text-muted">Auto</span>
                                            </div>
                                            <div class="d-flex flex-wrap gap-3 small text-muted">
                                                <div class="d-flex align-items-center">
                                                    <span class="me-2" style="width:10px;height:10px;border-radius:999px;display:inline-block;background:#22c55e;opacity:{{ $paidSubs > 0 ? '1' : '0.25' }};"></span>
                                                    Paid Subs: {{ number_format($paidSubs) }}
                                                </div>
                                                <div class="d-flex align-items-center">
                                                    <span class="me-2" style="width:10px;height:10px;border-radius:999px;display:inline-block;background:#f59e0b;opacity:{{ $pendingSetups > 0 ? '1' : '0.25' }};"></span>
                                                    Pending Setups: {{ number_format($pendingSetups) }}
                                                </div>
                                                <div class="d-flex align-items-center">
                                                    <span class="me-2" style="width:10px;height:10px;border-radius:999px;display:inline-block;background:#ef4444;opacity:{{ $expiredSubs > 0 ? '1' : '0.25' }};"></span>
                                                    Expired Plans: {{ number_format($expiredSubs) }}
                                                </div>
                                            </div>
                                        </div>
                                        <div class="row g-2 mt-3">
                                            @foreach([
                                                ['label' => 'Verified Recent Users', 'value' => number_format(($recentUsers ?? collect())->filter(fn($item) => !empty($item->is_verified))->count())],
                                                ['label' => 'Users With Company', 'value' => number_format(($recentUsers ?? collect())->filter(fn($item) => !empty(optional($item->company)->id))->count())],
                                                ['label' => 'Users Awaiting Setup', 'value' => number_format(($recentUsers ?? collect())->filter(fn($item) => empty(optional($item->company)->id))->count())],
                                                ['label' => 'Avg Plan Revenue', 'value' => '₦' . number_format($metrics['avg_plan_sale'] ?? 0, 0)],
                                                ['label' => 'Registered Revenue', 'value' => '₦' . number_format($metrics['registered_user_revenue'] ?? 0, 0)],
                                                ['label' => 'Expiry Pressure', 'value' => number_format(max(0, ((int) ($metrics['expiring_soon_subs'] ?? 0)) + ((int) ($metrics['expired_subs'] ?? 0))))],
                                            ] as $healthMini)
                                                <div class="col-sm-6 col-xl-4">
                                                    <div class="summary-fill h-100">
                                                        <div class="label">{{ $healthMini['label'] }}</div>
                                                        <div class="value">{{ $healthMini['value'] }}</div>
                                                    </div>
                                                </div>
                                            @endforeach
                                        </div>
                                    </div>
                                </div>

                                <div class="card card-rounded shadow-sm">
                                    <div class="card-body">
                                        <div class="d-flex justify-content-between align-items-center mb-3">
                                            <div>
                                                <h5 class="mb-0 fw-bold text-dark">Readiness Crosscheck</h5>
                                                <small class="text-muted">Compact balance panel for onboarding and revenue readiness</small>
                                            </div>
                                            <span class="live-badge-soft">Live</span>
                                        </div>
                                        <div class="row g-2">
                                            @foreach([
                                                ['label' => 'Recent Users', 'value' => number_format($recentUsers->count() ?? 0), 'tone' => 'tone-sky'],
                                                ['label' => 'Recent Companies', 'value' => number_format($recentTenants->count() ?? 0), 'tone' => 'tone-emerald'],
                                                ['label' => 'Pending Setup', 'value' => number_format($metrics['pending_setups'] ?? 0), 'tone' => 'tone-violet'],
                                                ['label' => 'Paid Nodes', 'value' => number_format($metrics['paid_subs'] ?? 0), 'tone' => 'tone-amber'],
                                                ['label' => 'Direct Revenue', 'value' => '₦' . number_format($metrics['direct_subscription_revenue'] ?? 0, 0), 'tone' => 'tone-cobalt'],
                                                ['label' => 'Deployment Revenue', 'value' => '₦' . number_format($metrics['deployment_subscription_revenue'] ?? 0, 0), 'tone' => 'tone-rose'],
                                            ] as $crossStat)
                                                <div class="col-sm-6 col-xl-4">
                                                    <div class="summary-fill {{ $crossStat['tone'] }} h-100">
                                                        <div class="label">{{ $crossStat['label'] }}</div>
                                                        <div class="value">{{ $crossStat['value'] }}</div>
                                                    </div>
                                                </div>
                                            @endforeach
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-12 grid-margin stretch-card">
                                <div class="card card-rounded shadow-sm">
                                    <div class="card-body">
                                        <div class="d-flex justify-content-between align-items-center mb-4">
                                            <div>
                                                <h4 class="card-title card-title-dash">Transaction Density Heatmap</h4>
                                                <p class="card-subtitle card-subtitle-dash">System activity distribution (Day vs Hour UTC)</p>
                                            </div>
                                            <span class="badge bg-opacity-10 bg-info text-info"><i class="mdi mdi-information-outline"></i> Live Data</span>
                                        </div>

                                        <div class="heatmap-container overflow-auto">
                                            <div class="heatmap-grid">

                                                <div></div> 
                                                @for($h=0; $h<24; $h++)
                                                    <div class="text-center text-muted small" style="font-size:9px;">{{ str_pad($h, 2, '0', STR_PAD_LEFT) }}</div>
                                                @endfor

                                                @php $days = ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun']; @endphp
                                                @foreach($days as $day)
                                                    <div class="heatmap-label">{{ $day }}</div>
                                                    @for($h=0; $h<24; $h++)
                                                        <div class="heatmap-cell" id="hm-{{$day}}-{{$h}}" title="{{$day}} {{str_pad($h, 2, '0', STR_PAD_LEFT)}}:00"></div>
                                                    @endfor
                                                @endforeach
                                            </div>
                                        </div>

                                        <div class="heatmap-legend">
                                            <span>Intensity:</span>
                                            <div class="d-flex align-items-center gap-1">
                                                <div class="legend-box" style="background:#ebedf2"></div> <span class="text-muted">Low</span>
                                                <div class="legend-box" style="background:#b1d3fa"></div>
                                                <div class="legend-box" style="background:#52a2f5"></div>
                                                <div class="legend-box" style="background:#1F3BB3"></div> <span class="fw-bold">High</span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

@if(view()->exists('scripts.print_handler'))
    @include('scripts.print_handler')
@else
    <script>function printPage() { window.print(); }</script>
@endif

@endsection

@push('scripts')

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>

@php
    $dashboardChartSeries = $chartSeries ?? [
        'labels' => [],
        'revenue' => [],
        'orders' => [],
        'companies' => [],
        'users' => [],
    ];
    $dashboardActivityHeatmap = $activityHeatmap ?? [];
@endphp

<script>
    /**
     * Dashboard Analytics Handler & Sidebar Toggle Logic
     */
    function toggleSidebarMobile() {
        document.body.classList.toggle('sidebar-icon-only'); 
        window.dispatchEvent(new Event('resize'));
    }

    function shareDashboard() {
        const title = 'SmartProbook Master Dashboard';
        const text = 'Live platform analytics';
        const url = window.location.href;

        if (navigator.share) {
            navigator.share({ title, text, url }).catch(() => {
                fallbackCopy(url);
            });
            return;
        }

        fallbackCopy(url);
    }

    function fallbackCopy(url) {
        if (navigator.clipboard && window.isSecureContext) {
            navigator.clipboard.writeText(url).then(() => {
                alert('Dashboard link copied to clipboard.');
            }).catch(() => {
                window.prompt('Copy dashboard link:', url);
            });
            return;
        }

        const temp = document.createElement('input');
        temp.value = url;
        document.body.appendChild(temp);
        temp.select();
        temp.setSelectionRange(0, temp.value.length);

        try {
            const copied = document.execCommand('copy');
            if (copied) {
                alert('Dashboard link copied to clipboard.');
            } else {
                window.prompt('Copy dashboard link:', url);
            }
        } catch (e) {
            window.prompt('Copy dashboard link:', url);
        }

        document.body.removeChild(temp);
    }

    document.addEventListener("DOMContentLoaded", function() {
        const chartSeries = @json($dashboardChartSeries);
        const activityHeatmap = @json($dashboardActivityHeatmap);

        // --- 1. REVENUE TREND CHART (Line) ---
        const revenueCtx = document.getElementById('revenueTrendChart');
        if (revenueCtx) {
            const ctx = revenueCtx.getContext("2d");
            const gradient = ctx.createLinearGradient(0, 0, 0, 300);
            gradient.addColorStop(0, 'rgba(31, 59, 179, 0.2)');
            gradient.addColorStop(1, 'rgba(31, 59, 179, 0.0)');

            new Chart(ctx, {
                type: 'line',
                data: {
                    labels: chartSeries.labels,
                    datasets: [{
                        label: 'Gross Revenue',
                        data: chartSeries.revenue,
                        borderColor: '#1F3BB3',
                        backgroundColor: gradient,
                        pointBackgroundColor: '#1F3BB3',
                        pointBorderColor: '#fff',
                        borderWidth: 3,
                        fill: true,
                        tension: 0.4
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: { 
                        legend: { display: false },
                        tooltip: {
                            callbacks: {
                                label: function(context) {
                                    return '₦' + context.parsed.y.toLocaleString();
                                }
                            }
                        }
                    },
                    scales: {
                        y: { 
                            beginAtZero: true, 
                            grid: { color: '#f0f0f0', drawBorder: false },
                            ticks: {
                                callback: function(value) {
                                    return '₦' + value.toLocaleString();
                                }
                            }
                        },
                        x: { grid: { display: false } }
                    }
                }
            });
        }

        // --- 2. PLAN REVENUE DISTRIBUTION (Doughnut/Pie) ---
        const planCtx = document.getElementById('planStatsChart');
        if (planCtx) {
            new Chart(planCtx.getContext("2d"), {
                type: 'doughnut',
                data: {
                    labels: {!! json_encode(array_keys($planStats)) !!},
                    datasets: [{
                        data: {!! json_encode(array_values($planStats)) !!},
                        backgroundColor: ['#1F3BB3', '#52CDFF', '#FFAB00', '#F95F53', '#7978E9'],
                        borderWidth: 0
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    cutout: '65%',
                    plugins: { 
                        legend: { 
                            position: 'bottom', 
                            labels: { padding: 15, usePointStyle: true } 
                        }
                    }
                }
            });
        }

        // --- 2B. MANAGER STATUS MIX (Doughnut) ---
        const managerPulseCtx = document.getElementById('managerStatusPieChart');
        if (managerPulseCtx) {
            const managerPulseValues = [
                {{ (int) ($metrics['pending_managers'] ?? 0) }},
                {{ (int) ($metrics['active_managers'] ?? 0) }},
                {{ (int) ($metrics['suspended_managers'] ?? 0) }}
            ];
            const hasManagerPulseData = managerPulseValues.some(value => Number(value) > 0);

            new Chart(managerPulseCtx.getContext("2d"), {
                type: 'doughnut',
                data: {
                    labels: hasManagerPulseData ? ['Pending', 'Approved', 'Suspended'] : ['No manager data yet'],
                    datasets: [{
                        data: hasManagerPulseData ? managerPulseValues : [1],
                        backgroundColor: hasManagerPulseData
                            ? ['#f59e0b', '#2563eb', '#ef4444']
                            : ['#dbe4f0'],
                        borderWidth: 0,
                        hoverOffset: 8
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    cutout: '68%',
                    plugins: {
                        legend: { display: false },
                        tooltip: {
                            enabled: hasManagerPulseData,
                            callbacks: {
                                label: function(context) {
                                    return `${context.label}: ${Number(context.parsed).toLocaleString()}`;
                                }
                            }
                        }
                    }
                }
            });
        }

        // --- 2C. SUBSCRIPTION HEALTH MIX (Doughnut) ---
        const subscriptionMixCtx = document.getElementById('subscriptionMixChart');
        if (subscriptionMixCtx) {
            const mixValues = [
                {{ (int) ($metrics['paid_subs'] ?? 0) }},
                {{ (int) ($metrics['pending_setups'] ?? 0) }},
                {{ (int) ($metrics['expired_subs'] ?? 0) }},
                {{ (int) ($metrics['recent_signups'] ?? 0) }}
            ];
            const hasMixData = mixValues.some(value => Number(value) > 0);

            new Chart(subscriptionMixCtx.getContext("2d"), {
                type: 'doughnut',
                data: {
                    labels: hasMixData ? ['Paid', 'Pending Setup', 'Expired', 'New (30d)'] : ['No data yet'],
                    datasets: [{
                        data: hasMixData ? mixValues : [1],
                        backgroundColor: hasMixData
                            ? ['#2563eb', '#f59e0b', '#ef4444', '#0ea5e9']
                            : ['#dbe4f0'],
                        borderWidth: 0,
                        hoverOffset: 8
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    cutout: '68%',
                    plugins: {
                        legend: { display: false },
                        tooltip: {
                            enabled: hasMixData,
                            callbacks: {
                                label: function(context) {
                                    return `${context.label}: ${Number(context.parsed).toLocaleString()}`;
                                }
                            }
                        }
                    }
                }
            });
        }

        // --- 3. TRAFFIC LINE CHART ---
        const trafficCtx = document.getElementById('trafficLineChart');
        if (trafficCtx) {
            new Chart(trafficCtx.getContext("2d"), {
                type: 'line',
                data: {
                    labels: chartSeries.labels,
                    datasets: [
                        {
                            label: 'New Companies',
                            data: chartSeries.companies,
                            borderColor: '#52CDFF',
                            backgroundColor: 'rgba(82, 205, 255, 0.1)',
                            borderWidth: 2,
                            tension: 0.3,
                            fill: true,
                            pointRadius: 4,
                            pointHoverRadius: 6
                        },
                        {
                            label: 'New Users',
                            data: chartSeries.users,
                            borderColor: '#1F3BB3',
                            borderWidth: 2,
                            tension: 0.3,
                            pointRadius: 4,
                            fill: false
                        }
                    ]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: { 
                        legend: { 
                            position: 'top',
                            labels: { usePointStyle: true }
                        }
                    },
                    scales: {
                        y: { 
                            beginAtZero: true, 
                            grid: { color: '#f0f0f0', drawBorder: false }
                        },
                        x: { grid: { display: false } }
                    }
                }
            });
        }

        // --- 4. SALES BAR CHART ---
        const salesCtx = document.getElementById('salesBarChart');
        if (salesCtx) {
            new Chart(salesCtx.getContext("2d"), {
                type: 'bar',
                data: {
                    labels: chartSeries.labels,
                    datasets: [{
                        label: 'Paid Subscriptions',
                        data: chartSeries.orders,
                        backgroundColor: '#1F3BB3',
                        borderRadius: 6,
                        barPercentage: 0.5
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: { 
                        legend: { display: false },
                    },
                    scales: {
                        y: { 
                            beginAtZero: true, 
                            grid: { display: true, color: '#f0f0f0', drawBorder: false }
                        },
                        x: { grid: { display: false } }
                    }
                }
            });
        }

        const growthComparisonCtx = document.getElementById('growthComparisonChart');
        if (growthComparisonCtx) {
            new Chart(growthComparisonCtx.getContext("2d"), {
                type: 'bar',
                data: {
                    labels: chartSeries.labels,
                    datasets: [
                        {
                            label: 'Companies',
                            data: chartSeries.companies,
                            backgroundColor: 'rgba(37, 99, 235, 0.78)',
                            borderRadius: 6,
                            barPercentage: 0.7,
                            categoryPercentage: 0.58
                        },
                        {
                            label: 'Users',
                            data: chartSeries.users,
                            backgroundColor: 'rgba(14, 165, 233, 0.72)',
                            borderRadius: 6,
                            barPercentage: 0.7,
                            categoryPercentage: 0.58
                        },
                        {
                            label: 'Paid Plans',
                            data: chartSeries.orders,
                            type: 'line',
                            borderColor: '#10b981',
                            backgroundColor: 'rgba(16, 185, 129, 0.12)',
                            borderWidth: 3,
                            tension: 0.35,
                            fill: false,
                            pointRadius: 3,
                            pointHoverRadius: 5
                        }
                    ]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            position: 'top',
                            labels: { usePointStyle: true, boxWidth: 10 }
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            grid: { color: '#eef3f8', drawBorder: false }
                        },
                        x: {
                            grid: { display: false }
                        }
                    }
                }
            });
        }

        const subscriptionHealthMixCtx = document.getElementById('subscriptionHealthMixChart');
        if (subscriptionHealthMixCtx) {
            const subscriptionHealthValues = [
                {{ (int) ($metrics['paid_subs'] ?? 0) }},
                {{ (int) ($metrics['pending_setups'] ?? 0) }},
                {{ (int) ($metrics['expiring_soon_subs'] ?? 0) }},
                {{ (int) ($metrics['expired_subs'] ?? 0) }}
            ];
            const hasSubscriptionHealthData = subscriptionHealthValues.some(value => Number(value) > 0);

            new Chart(subscriptionHealthMixCtx.getContext("2d"), {
                type: 'doughnut',
                data: {
                    labels: hasSubscriptionHealthData ? ['Paid', 'Pending Setup', 'Expiring Soon', 'Expired'] : ['No subscription health data yet'],
                    datasets: [{
                        data: hasSubscriptionHealthData ? subscriptionHealthValues : [1],
                        backgroundColor: hasSubscriptionHealthData
                            ? ['#10b981', '#8b5cf6', '#f59e0b', '#ef4444']
                            : ['#dbe4f0'],
                        borderWidth: 0,
                        hoverOffset: 8
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    cutout: '66%',
                    plugins: {
                        legend: {
                            position: 'bottom',
                            labels: { usePointStyle: true, boxWidth: 10, padding: 14 }
                        },
                        tooltip: {
                            enabled: hasSubscriptionHealthData,
                            callbacks: {
                                label: function(context) {
                                    return `${context.label}: ${Number(context.parsed).toLocaleString()}`;
                                }
                            }
                        }
                    }
                }
            });
        }

        // --- 5. SUBSCRIBER MOMENTUM (Mini Area) ---
        const subscriberMomentumCtx = document.getElementById('subscriberMomentumChart');
        if (subscriberMomentumCtx) {
            const ctx = subscriberMomentumCtx.getContext("2d");
            const companyGradient = ctx.createLinearGradient(0, 0, 0, 200);
            companyGradient.addColorStop(0, 'rgba(37, 99, 235, 0.25)');
            companyGradient.addColorStop(1, 'rgba(37, 99, 235, 0)');
            const userGradient = ctx.createLinearGradient(0, 0, 0, 200);
            userGradient.addColorStop(0, 'rgba(16, 185, 129, 0.25)');
            userGradient.addColorStop(1, 'rgba(16, 185, 129, 0)');

            new Chart(ctx, {
                type: 'line',
                data: {
                    labels: chartSeries.labels,
                    datasets: [
                        {
                            label: 'Companies',
                            data: chartSeries.companies,
                            borderColor: '#2563eb',
                            backgroundColor: companyGradient,
                            fill: true,
                            tension: 0.4,
                            borderWidth: 2,
                            pointRadius: 0
                        },
                        {
                            label: 'Users',
                            data: chartSeries.users,
                            borderColor: '#10b981',
                            backgroundColor: userGradient,
                            fill: true,
                            tension: 0.4,
                            borderWidth: 2,
                            pointRadius: 0
                        }
                    ]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: { legend: { display: false } },
                    scales: {
                        y: { beginAtZero: true, grid: { color: '#eef2f7', drawBorder: false } },
                        x: { grid: { display: false } }
                    }
                }
            });
        }

        // --- 6. REAL-TIME REGIONAL MAP (Leaflet) ---
        const mapElement = document.getElementById('regionMap');
        if (mapElement) {
            // Initialize map (unlock interactions on click)
            const regionMap = L.map('regionMap', {
                scrollWheelZoom: false,
                doubleClickZoom: false,
                touchZoom: false,
                dragging: false,
            }).setView([20, 0], 2);

            regionMap.scrollWheelZoom.disable();
            regionMap.doubleClickZoom.disable();
            regionMap.touchZoom.disable();
            regionMap.dragging.disable();

            const setMapInteraction = (enabled) => {
                if (enabled) {
                    regionMap.scrollWheelZoom.enable();
                    regionMap.doubleClickZoom.enable();
                    regionMap.touchZoom.enable();
                    regionMap.dragging.enable();
                    mapElement.classList.add('map-interactive');
                } else {
                    regionMap.scrollWheelZoom.disable();
                    regionMap.doubleClickZoom.disable();
                    regionMap.touchZoom.disable();
                    regionMap.dragging.disable();
                    mapElement.classList.remove('map-interactive');
                }
            };

            mapElement.addEventListener('click', () => setMapInteraction(true));
            mapElement.addEventListener('mouseleave', () => setMapInteraction(false));

            // Add tile layer
            L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                attribution: '&copy; OpenStreetMap contributors',
                maxZoom: 18
            }).addTo(regionMap);

            // Country data with approximate coordinates
            const countryCoordinates = {
                @foreach($countryData as $country => $count)
                '{{ $country }}': { 
                    @php
                        // Approximate coordinates for common countries
                        $coords = [
                            'United States' => ['lat' => 37.0902, 'lng' => -95.7129],
                            'Nigeria' => ['lat' => 9.0820, 'lng' => 8.6753],
                            'United Kingdom' => ['lat' => 55.3781, 'lng' => -3.4360],
                            'Canada' => ['lat' => 56.1304, 'lng' => -106.3468],
                            'Australia' => ['lat' => -25.2744, 'lng' => 133.7751],
                            'Germany' => ['lat' => 51.1657, 'lng' => 10.4515],
                            'France' => ['lat' => 46.2276, 'lng' => 2.2137],
                            'India' => ['lat' => 20.5937, 'lng' => 78.9629],
                            'China' => ['lat' => 35.8617, 'lng' => 104.1954],
                            'Japan' => ['lat' => 36.2048, 'lng' => 138.2529],
                            'Brazil' => ['lat' => -14.2350, 'lng' => -51.9253],
                            'South Africa' => ['lat' => -30.5595, 'lng' => 22.9375],
                        ];
                        $lat = $coords[$country]['lat'] ?? 0;
                        $lng = $coords[$country]['lng'] ?? 0;
                    @endphp
                    lat: {{ $lat }}, 
                    lng: {{ $lng }},
                    count: {{ $count }}
                },
                @endforeach
            };

            // Add markers for each country
            Object.keys(countryCoordinates).forEach(country => {
                const data = countryCoordinates[country];
                if (data.lat !== 0 && data.lng !== 0) {
                    const marker = L.circleMarker([data.lat, data.lng], {
                        radius: Math.max(8, Math.min(data.count * 2, 30)),
                        fillColor: '#1F3BB3',
                        color: '#fff',
                        weight: 2,
                        opacity: 1,
                        fillOpacity: 0.7
                    }).addTo(regionMap);

                    marker.bindPopup(`
                        <div style="text-align: center;">
                            <strong>${country}</strong><br>
                            Companies: <strong>${data.count}</strong>
                        </div>
                    `);
                }
            });

            // Auto-refresh markers every 30 seconds (simulated real-time)
            setInterval(() => {
                // In production, fetch new data via AJAX and update markers
                console.log('Map data refresh triggered');
            }, 30000);

            setTimeout(() => regionMap.invalidateSize(), 250);
            window.addEventListener('resize', () => {
                setTimeout(() => regionMap.invalidateSize(), 100);
            });
        }

        // --- 6. HEATMAP VISUALIZATION ---
        const days = ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'];
        days.forEach(day => {
            for(let h=0; h<24; h++) {
                const cell = document.getElementById(`hm-${day}-${h}`);
                if(cell) {
                    const value = ((activityHeatmap[day] || {})[h]) || 0;
                    let intensity = 0;
                    if (value > 0 && value <= 2) intensity = 1;
                    else if (value > 2 && value <= 5) intensity = 2;
                    else if (value > 5) intensity = 3;

                    if (intensity === 1) cell.style.backgroundColor = '#b1d3fa'; 
                    if (intensity === 2) cell.style.backgroundColor = '#52a2f5'; 
                    if (intensity === 3) cell.style.backgroundColor = '#1F3BB3'; 
                    if (!intensity) cell.style.backgroundColor = '#ebedf2';
                }
            }
        });

    });
</script>
@endpush
