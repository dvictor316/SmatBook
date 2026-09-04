@extends('layout.mainlayout')

@section('style')
<style>
    .sa-hotel { background:#eef3f8; color:#09213d; }
    .sa-hero { background:linear-gradient(135deg,#06264a,#0b5fb8 58%,#0f766e); color:#fff; border-radius:18px; padding:22px; margin-bottom:16px; display:flex; justify-content:space-between; gap:16px; flex-wrap:wrap; box-shadow:0 18px 36px rgba(8,47,73,.18); }
    .page-wrapper.sa-hotel .sa-hero h2, body.spb-super-admin-theme .page-wrapper.sa-hotel .sa-hero h2, body:not(.login-body):not(.landing-page-body) .page-wrapper.sa-hotel .sa-hero h2 { color:#f5c451 !important; -webkit-text-fill-color:#f5c451 !important; margin:0; font-size:31px; font-weight:700; text-shadow:0 2px 16px rgba(0,0,0,.22); }
    .page-wrapper.sa-hotel .sa-hero p, body.spb-super-admin-theme .page-wrapper.sa-hotel .sa-hero p, body:not(.login-body):not(.landing-page-body) .page-wrapper.sa-hotel .sa-hero p { color:#fff !important; -webkit-text-fill-color:#fff !important; }
    .page-wrapper.sa-hotel .sa-hero small, body.spb-super-admin-theme .page-wrapper.sa-hotel .sa-hero small, body:not(.login-body):not(.landing-page-body) .page-wrapper.sa-hotel .sa-hero small { color:#f7d777 !important; -webkit-text-fill-color:#f7d777 !important; text-transform:uppercase; letter-spacing:.14em; font-weight:700; }
    .sa-panel, .sa-card, .sa-filter { background:#fff; border:1px solid #d8e2ee; border-radius:14px; box-shadow:0 10px 28px rgba(15,23,42,.06); }
    .sa-tabs { display:flex; gap:8px; overflow:auto; padding:12px; margin-bottom:16px; }
    .sa-tabs a { white-space:nowrap; border:1px solid #cbd8e8; border-radius:999px; padding:8px 13px; color:#0b2f54; text-decoration:none; font-weight:600; }
    .sa-tabs a.active { background:#0b5fb8; color:#fff; border-color:#0b5fb8; }
    .sa-filter { padding:14px; margin-bottom:16px; }
    .sa-kpis { display:grid; grid-template-columns:repeat(4,minmax(0,1fr)); gap:14px; margin-bottom:16px; }
    .sa-kpi { padding:16px; position:relative; overflow:hidden; min-height:120px; }
    .sa-kpi:before { content:''; position:absolute; inset:0 auto 0 0; width:5px; background:#0b5fb8; }
    .sa-kpi.green:before { background:#16a34a; } .sa-kpi.gold:before { background:#d4a23a; } .sa-kpi.red:before { background:#dc2626; }
    .sa-kpi strong { display:block; font-size:32px; line-height:1; margin:8px 0 5px; }
    .sa-workspace { display:grid; grid-template-columns:230px minmax(0,1fr); gap:16px; }
    .sa-rail { background:#0b2f54; color:#fff; border-radius:14px; padding:14px; align-self:start; }
    .sa-rail h5 { color:#fff; font-size:14px; text-transform:uppercase; letter-spacing:.1em; }
    .sa-rail a, .sa-rail div { display:block; color:#dbeafe; text-decoration:none; padding:10px 8px; border-bottom:1px solid rgba(255,255,255,.12); }
    .sa-rail strong { color:#fff; }
    .sa-grid { display:grid; grid-template-columns:repeat(3,minmax(0,1fr)); gap:14px; }
    .sa-card { padding:16px; color:#09213d; text-decoration:none; min-height:145px; }
    .sa-card .label { color:#d4a23a; text-transform:uppercase; letter-spacing:.12em; font-size:12px; font-weight:700; }
    .sa-empty { grid-column:1 / -1; border:1px dashed #d4a23a; border-radius:12px; padding:22px; background:#fff8e1; color:#5a3d00; font-weight:600; }
    .sa-sample { border-style:dashed; background:#fffdf5; }
    .sa-room-rack { display:grid; grid-template-columns:repeat(auto-fill,minmax(132px,1fr)); gap:10px; }
    .sa-room { min-height:138px; border-radius:8px; border:1px solid #d8e2ee; background:#fff; padding:10px; position:relative; overflow:hidden; }
    .sa-room.available { background:#ecfdf3; } .sa-room.occupied { background:#e8f2ff; } .sa-room.reserved { background:#fff8e5; } .sa-room.maintenance, .sa-room.out_of_order { background:#fff1f2; }
    .sa-room-num { font-size:35px; font-weight:300; color:#0b5fb8; line-height:1; margin:7px 0; }
    .sa-calendar { overflow:auto; }
    .sa-calendar table { min-width:950px; border-collapse:separate; border-spacing:0; }
    .sa-calendar th { background:#0c3f70; color:#fff; border:0; font-size:12px; text-transform:uppercase; }
    .sa-calendar td { vertical-align:top; min-width:120px; height:78px; border-color:#dbe4ef; }
    .sa-event { border-radius:8px; padding:7px; color:#fff; font-size:12px; background:#0b5fb8; }
    .sa-event.gold { background:#d4a23a; color:#111827; } .sa-event.green { background:#16a34a; } .sa-event.red { background:#dc2626; }
    .sa-board-row { display:grid; grid-template-columns:150px minmax(0,1fr) 150px 160px; gap:12px; align-items:center; padding:13px 16px; border-bottom:1px solid #edf1f6; }
    .sa-board-row:last-child { border-bottom:0; }
    .sa-table th { background:#0c3f70; color:#fff; border:0; text-transform:uppercase; font-size:12px; }
    .sa-profile-grid { display:grid; grid-template-columns:repeat(2,minmax(0,1fr)); gap:12px; }
    .sa-profile { display:grid; grid-template-columns:58px minmax(0,1fr); gap:12px; padding:14px; border:1px solid #dbe4ef; border-radius:12px; background:#fff; }
    .sa-avatar { width:58px; height:58px; border-radius:50%; background:#0b2f54; color:#fff; display:flex; align-items:center; justify-content:center; font-weight:700; font-size:22px; }
    .sa-kanban { display:grid; grid-template-columns:repeat(4,minmax(0,1fr)); gap:12px; }
    .sa-lane { background:#f8fafc; border:1px solid #dbe4ef; border-radius:12px; padding:10px; min-height:250px; }
    .sa-lane h5 { font-size:14px; text-transform:uppercase; letter-spacing:.08em; color:#475569; }
    .sa-ticket { background:#fff; border:1px solid #e5eaf2; border-left:5px solid #0b5fb8; border-radius:10px; padding:10px; margin-bottom:9px; }
    .sa-ticket.danger { border-left-color:#dc2626; } .sa-ticket.gold { border-left-color:#d4a23a; } .sa-ticket.green { border-left-color:#16a34a; }
    .sa-cashier { display:grid; grid-template-columns:280px minmax(0,1fr) 260px; gap:14px; }
    .sa-cashier-side { background:#24333a; color:#fff; border-radius:14px; padding:16px; }
    .sa-cashier-side h3 { color:#fff; font-size:38px; }
    .sa-pad { display:grid; grid-template-columns:repeat(2,minmax(0,1fr)); gap:8px; }
    .sa-pad div { min-height:74px; border:1px solid #d8e2ee; border-radius:10px; display:flex; align-items:center; justify-content:center; text-align:center; font-weight:700; background:#fff; }
    .sa-service-grid { display:grid; grid-template-columns:repeat(4,minmax(0,1fr)); gap:14px; margin-bottom:16px; }
    .sa-service { min-height:132px; padding:16px; border-radius:14px; border:1px solid #d8e2ee; background:#fff; color:#09213d; text-decoration:none; box-shadow:0 10px 28px rgba(15,23,42,.06); }
    .sa-service span { color:#d4a23a; text-transform:uppercase; letter-spacing:.12em; font-size:12px; font-weight:700; }
    .sa-action-disabled { opacity:.7; cursor:not-allowed; }
    .sa-report-grid { display:grid; grid-template-columns:repeat(3,minmax(0,1fr)); gap:14px; }
    .sa-report { min-height:160px; background:#082f55; color:#fff; border-radius:14px; padding:18px; text-decoration:none; display:flex; flex-direction:column; justify-content:space-between; }
    .sa-report span { color:#f1c15c; letter-spacing:.12em; text-transform:uppercase; font-size:12px; font-weight:700; }
    .sa-report h4, .sa-report p, .sa-cashier-side h3, .sa-cashier-side p, .sa-cashier-side small { color:#fff !important; }
    .sa-health { display:grid; grid-template-columns:repeat(2,minmax(0,1fr)); gap:12px; }
    .sa-health-row { display:flex; justify-content:space-between; align-items:center; padding:14px; border:1px solid #dbe4ef; border-radius:12px; background:#fff; }
    .sa-room-admin { display:grid; grid-template-columns:220px minmax(0,1fr); gap:14px; }
    .sa-room-admin .sa-room-rack { grid-template-columns:repeat(auto-fill,minmax(150px,1fr)); }
    .sa-room-admin .sa-room { min-height:165px; border-radius:6px; }
    .sa-room-admin .sa-room-num { font-size:42px; font-weight:300; }
    .sa-folio-register { display:grid; grid-template-columns:260px minmax(0,1fr); gap:14px; }
    .sa-folio-rail { background:#24333a; color:#fff; border-radius:6px; padding:14px; }
    .sa-folio-rail h3 { color:#fff; font-size:34px; margin:8px 0; }
    .sa-folio-rail a, .sa-folio-rail div { display:flex; justify-content:space-between; color:#fff; text-decoration:none; border-bottom:1px solid rgba(255,255,255,.14); padding:11px 0; font-weight:600; }
    .sa-maint-desk { display:grid; grid-template-columns:300px minmax(0,1fr); gap:14px; }
    .sa-maint-side { background:#111827; color:#fff; border-left:8px solid #d4a23a; border-radius:14px; padding:16px; }
    .sa-maint-side h4, .sa-maint-side p { color:#fff !important; }
    .sa-maint-ticket { display:grid; grid-template-columns:120px minmax(0,1fr) 120px 140px; gap:12px; align-items:center; padding:13px; border:1px solid #e5edf6; border-left:6px solid #94a3b8; border-radius:14px; background:#fff; margin-bottom:10px; }
    .sa-maint-ticket.danger { border-left-color:#dc2626; background:#fff7f7; }
    .sa-maint-room { font-size:30px; color:#0b5fb8; font-weight:300; line-height:1; }
    .sa-audit-command { background:#071d35; border-radius:18px; padding:16px; color:#dbeafe; }
    .sa-audit-command h4, .sa-audit-command p { color:#fff !important; }
    .sa-audit-grid { display:grid; grid-template-columns:repeat(4,minmax(0,1fr)); gap:12px; margin-top:12px; }
    .sa-audit-check { background:#fff; color:#172033; border:1px solid #d9e4ef; border-left:5px solid #0b5fb8; border-radius:14px; padding:14px; }
    .sa-audit-check.warning { border-left-color:#d4a23a; }
    .sa-audit-check.danger { border-left-color:#dc2626; }
    .sa-report-hub { background:#061d36; color:#fff; border-radius:18px; padding:16px; }
    .sa-report-hub h4, .sa-report-hub p { color:#fff !important; }
    .sa-report-hub .sa-report { background:#102f4d; }


    .sa-dash-grid { display:grid; grid-template-columns:1.45fr .9fr; gap:16px; margin-bottom:16px; }
    .sa-dash-panel { background:#fff; border:1px solid #d8e2ee; border-radius:18px; padding:16px; box-shadow:0 14px 32px rgba(15,23,42,.07); }
    .sa-dash-panel h4 { margin:0; color:#061b33; font-weight:700; }
    .sa-dash-panel p { color:#64748b; margin:4px 0 0; }
    .sa-mini-kpis { display:grid; grid-template-columns:repeat(4,minmax(0,1fr)); gap:12px; margin-bottom:16px; }
    .sa-mini-kpi { background:#fff; border:1px solid #d8e2ee; border-radius:16px; padding:14px; min-height:104px; box-shadow:0 12px 28px rgba(15,23,42,.06); }
    .sa-mini-kpi span { color:#64748b; font-weight:600; font-size:12px; text-transform:uppercase; letter-spacing:.04em; }
    .sa-mini-kpi strong { display:block; color:#061b33; font-size:30px; line-height:1; margin:8px 0 5px; }
    .sa-mini-kpi small { color:#64748b; }
    .sa-chart-bars { display:flex; align-items:end; gap:12px; min-height:188px; padding-top:18px; }
    .sa-chart-bar { flex:1; min-width:34px; border-radius:12px 12px 4px 4px; background:linear-gradient(180deg,#3b82f6,#0b5fb8); position:relative; box-shadow:inset 0 -14px 20px rgba(255,255,255,.18); }
    .sa-chart-bar span { position:absolute; left:50%; top:-22px; transform:translateX(-50%); color:#061b33; font-size:11px; font-weight:800; white-space:nowrap; }
    .sa-chart-bar:after { content:attr(data-label); position:absolute; left:50%; bottom:-24px; transform:translateX(-50%); color:#64748b; font-size:11px; font-weight:600; }
    .sa-chart-bar.gold { background:linear-gradient(180deg,#f5c451,#d4a23a); }
    .sa-room-status { display:grid; grid-template-columns:150px minmax(0,1fr); gap:16px; align-items:center; }
    .sa-donut { width:140px; height:140px; border-radius:50%; background:conic-gradient(#16a34a 0 40%, #2563eb 40% 68%, #d4a23a 68% 84%, #dc2626 84% 100%); display:grid; place-items:center; box-shadow:inset 0 0 0 18px #fff, 0 12px 28px rgba(15,23,42,.12); }
    .sa-donut strong { color:#061b33; font-size:26px; }
    .sa-status-list { display:grid; gap:10px; }
    .sa-status-item { display:flex; justify-content:space-between; gap:10px; align-items:center; padding:9px 11px; border:1px solid #e5edf6; border-radius:12px; background:#f8fafc; font-weight:600; }
    .sa-dot { width:10px; height:10px; display:inline-block; border-radius:50%; margin-right:7px; }
    .sa-dot.green { background:#16a34a; } .sa-dot.blue { background:#2563eb; } .sa-dot.gold { background:#d4a23a; } .sa-dot.red { background:#dc2626; }
    .sa-dash-bottom { display:grid; grid-template-columns:1fr 1fr 1fr; gap:16px; margin-bottom:16px; }
    .sa-ops-list { display:grid; gap:10px; margin-top:14px; }
    .sa-ops-row { display:grid; grid-template-columns:1fr auto; gap:10px; align-items:center; padding:11px; border:1px solid #e5edf6; border-radius:12px; background:#f8fafc; }
    .sa-ops-row strong { color:#061b33; }
    .sa-alert-line { border-left:5px solid #d4a23a; background:#fff8e1; border-radius:12px; padding:11px; margin-top:10px; }
    .sa-alert-line.danger { border-left-color:#dc2626; background:#fff1f2; }
    .sa-dashboard-services { display:grid; grid-template-columns:repeat(4,minmax(0,1fr)); gap:12px; }
    .sa-dashboard-service { display:flex; justify-content:space-between; gap:12px; align-items:center; padding:13px; border:1px solid #d8e2ee; border-radius:14px; background:#fff; color:#09213d; text-decoration:none; box-shadow:0 10px 24px rgba(15,23,42,.05); }
    .sa-dashboard-service:hover { color:#09213d; border-color:#0b5fb8; transform:translateY(-1px); }
    .sa-dashboard-service small { color:#d4a23a; font-weight:700; text-transform:uppercase; letter-spacing:.09em; }
    .sa-dashboard-service strong { color:#061b33; font-size:16px; }
    .sa-dashboard-service b { display:block; color:#0b5fb8; font-size:14px; margin-top:4px; }
    .sa-calendar-pulse { display:grid; grid-template-columns:repeat(7,minmax(0,1fr)); gap:10px; margin-bottom:14px; }
    .sa-calendar-day { border:1px solid #d8e2ee; border-radius:14px; background:#f8fbff; padding:12px; min-height:132px; }
    .sa-calendar-day.today { border-color:#0b5fb8; box-shadow:0 0 0 3px rgba(11,95,184,.12); }
    .sa-calendar-day strong { display:block; color:#061b33; margin-bottom:9px; }
    .sa-calendar-day div { display:flex; justify-content:space-between; gap:8px; color:#64748b; font-size:12px; margin-top:5px; }
    .sa-calendar-day span { color:#061b33; font-weight:900; }
    .sa-progress-grid { display:grid; grid-template-columns:repeat(auto-fill,minmax(260px,1fr)); gap:14px; }
    .sa-progress-card { border:1px solid #d8e2ee; border-left:6px solid #16a34a; border-radius:14px; background:#fff; padding:16px; box-shadow:0 10px 24px rgba(15,23,42,.05); }
    .sa-progress-card.pending { border-left-color:#d4a23a; background:#fffaf0; }
    .sa-command-grid { display:grid; grid-template-columns:repeat(4,minmax(0,1fr)); gap:12px; margin-bottom:16px; }
    .sa-command-link { min-height:92px; display:flex; gap:12px; align-items:center; color:#09213d; text-decoration:none; background:#fff; border:1px solid #d8e2ee; border-radius:14px; padding:13px; box-shadow:0 10px 24px rgba(15,23,42,.05); }
    .sa-command-link:hover { color:#09213d; border-color:#0b5fb8; }
    .sa-command-link i { width:38px; height:38px; display:grid; place-items:center; border-radius:10px; background:#0b5fb8; color:#fff; flex:0 0 auto; }
    .sa-command-link strong { display:block; color:#061b33; line-height:1.15; }
    .sa-command-link span { color:#64748b; font-size:12px; line-height:1.25; display:block; margin-top:3px; }
    .sa-upgrade-banner { background:#fff; border:2px solid #d7a928; border-radius:14px; padding:14px; margin-bottom:16px; box-shadow:0 12px 28px rgba(6,26,68,.08); }
    .sa-upgrade-banner-head { display:flex; justify-content:space-between; align-items:center; gap:12px; flex-wrap:wrap; margin-bottom:12px; }
    .sa-upgrade-banner h4 { margin:0; color:#061b33; font-weight:900; }
    .sa-upgrade-banner p { margin:3px 0 0; color:#64748b; }
    .sa-upgrade-actions { display:grid; grid-template-columns:repeat(6,minmax(0,1fr)); gap:8px; }
    .sa-upgrade-actions a { min-height:58px; display:flex; align-items:center; gap:8px; text-decoration:none; color:#061b33; background:#f8fbff; border:1px solid #d8e2ee; border-radius:10px; padding:9px; font-weight:800; font-size:12px; }
    .sa-upgrade-actions a:hover, .sa-upgrade-actions a.active { background:#0b5fb8; color:#fff; border-color:#0b5fb8; }
    .sa-upgrade-actions a i { color:#d7a928; font-size:16px; }
    .sa-upgrade-actions a:hover i, .sa-upgrade-actions a.active i { color:#ffe8a3; }
    .sa-management-console { background:#fff; border:1px solid #d8e2ee; border-radius:14px; padding:14px; margin-bottom:14px; box-shadow:0 10px 24px rgba(15,23,42,.05); }
    .sa-management-actions { display:flex; gap:8px; flex-wrap:wrap; align-items:center; }
    .sa-room-manager-card { border:1px solid #d8e2ee; border-radius:14px; background:#fff; overflow:hidden; box-shadow:0 10px 24px rgba(15,23,42,.05); }
    .sa-room-manager-media { height:168px; background:#eef5ff; display:grid; place-items:center; overflow:hidden; }
    .sa-room-manager-media img { width:100%; height:100%; object-fit:cover; object-position:center 48%; display:block; }
    .sa-room-manager-media.empty { color:#64748b; }
    .sa-room-manager-body { padding:11px 13px 13px; }
    .sa-room-manager-body h5 { color:#061b33; font-weight:900; margin:0; font-size:20px; }
    .sa-room-manager-body .rate { color:#0b5fb8; font-weight:900; }
    .sa-room-manager-actions { display:grid; grid-template-columns:repeat(4,minmax(0,1fr)); gap:7px; margin-top:9px; }
    .sa-room-manager-actions .btn { min-height:36px; display:flex; align-items:center; justify-content:center; gap:5px; white-space:nowrap; padding-right:7px; padding-left:7px; font-size:13px; }
    .sa-room-manager-card .badge { white-space:normal; text-align:center; line-height:1.2; }
    .sa-room-meta-line { color:#64748b; line-height:1.35; overflow-wrap:anywhere; }
    .sa-room-location-line { color:#64748b; line-height:1.35; overflow-wrap:anywhere; }
    .sa-room-writeup { margin:6px 0 0; color:#40536b; font-size:13px; line-height:1.3; min-height:34px; display:-webkit-box; -webkit-line-clamp:2; -webkit-box-orient:vertical; overflow:hidden; }
    .sa-room-rate-row { align-items:center; margin-top:7px; }
    .sa-room-rate-row span:first-child { color:#061b33; }
    .sa-room-lock-note { margin-top:7px; padding:7px 9px; border-radius:8px; background:#fff1f2; color:#9f1239; font-size:12px; font-weight:800; }
    .sa-ops-action-strip { display:grid; grid-template-columns:repeat(auto-fit,minmax(180px,1fr)); gap:10px; margin-bottom:14px; }
    .sa-ops-action-strip a, .sa-ops-action-strip button { min-height:54px; display:flex; align-items:center; justify-content:center; gap:8px; border-radius:10px; font-weight:900; text-decoration:none; }
    .hotel-panorama-stage { position:relative; width:100%; height:100%; overflow:hidden; background:#030b14; }
    .hotel-panorama-image { width:150%; height:112%; max-width:none; object-fit:cover !important; object-position:center; animation:saPanoramaDrift 28s ease-in-out infinite; transform-origin:center; }
    .hotel-panorama-badge { position:absolute; top:14px; left:14px; z-index:2; display:inline-flex; align-items:center; gap:8px; padding:8px 11px; border-radius:999px; background:rgba(6,21,38,.82); color:#fff; font-weight:900; box-shadow:0 10px 26px rgba(0,0,0,.28); }
    @keyframes saPanoramaDrift { 0% { transform:scale(1.06) translate(-16%,-2%); } 24% { transform:scale(1.14) translate(8%,2%); } 50% { transform:scale(1.2) translate(16%,-1%); } 76% { transform:scale(1.12) translate(-7%,2%); } 100% { transform:scale(1.06) translate(-16%,-2%); } }
    .sa-room-gallery-strip { display:grid; grid-template-columns:repeat(auto-fill,minmax(92px,1fr)); gap:8px; }
    .sa-room-gallery-strip article { border:1px solid #d8e2ee; border-radius:8px; overflow:hidden; background:#f8fbff; }
    .sa-room-gallery-strip img { width:100%; height:68px; object-fit:cover; display:block; }
    .sa-room-gallery-strip form { padding:6px; }
    .sa-form-grid { display:grid; grid-template-columns:repeat(2,minmax(0,1fr)); gap:12px; }
    .sa-form-grid .full { grid-column:1 / -1; }
    .page-wrapper.sa-hotel .modal,
    .page-wrapper.sa-hotel .modal-dialog,
    .page-wrapper.sa-hotel .modal-content,
    .page-wrapper.sa-hotel .modal-body { overflow:visible; }
    .page-wrapper.sa-hotel #saServiceChargeModal .modal-dialog { max-height:calc(100vh - 28px); margin-top:14px; margin-bottom:14px; align-items:flex-start; }
    .page-wrapper.sa-hotel #saServiceChargeModal .modal-content { max-height:calc(100vh - 28px); display:flex; flex-direction:column; overflow:hidden; }
    .page-wrapper.sa-hotel #saServiceChargeModal .modal-body { overflow-y:auto; overflow-x:hidden; padding-bottom:24px; }
    .page-wrapper.sa-hotel #saServiceChargeModal .modal-footer { flex:0 0 auto; background:#fff; box-shadow:0 -8px 18px rgba(15,23,42,.08); }
    .sa-choice-list { display:grid; gap:8px; max-height:210px; overflow:auto; padding-right:4px; }
    .sa-choice-grid { display:grid; grid-template-columns:repeat(auto-fit,minmax(170px,1fr)); gap:8px; }
    .sa-choice { position:relative; margin:0; }
    .sa-choice input { position:absolute; opacity:0; pointer-events:none; }
    .sa-choice span { display:block; border:1px solid #cbd8e8; border-radius:10px; background:#fff; color:#09213d; padding:11px 12px; font-weight:700; line-height:1.28; cursor:pointer; }
    .sa-choice small { display:block; color:#64748b; font-weight:600; margin-top:3px; }
    .sa-choice input:checked + span { border-color:#0b5fb8; background:#e8f2ff; box-shadow:inset 0 0 0 2px rgba(11,95,184,.2); }
    .sa-tenant-lock { border:1px dashed #cbd8e8; border-radius:10px; background:#f8fbff; padding:11px 12px; color:#09213d; }
    .sa-form-grid > div { position:relative; }
    .sa-form-grid > div:focus-within { z-index:50; }
    .sa-dropdown-stack { grid-column:1 / -1; z-index:25; }
    .page-wrapper.sa-hotel select.form-select { position:relative; z-index:2; background-color:#fff; color:#061b33; }
    .page-wrapper.sa-hotel select.form-select:focus { z-index:90; }
    .page-wrapper.sa-hotel select.form-select option { background:#fff; color:#061b33; padding:10px; }


    .sa-section-head { display:flex; justify-content:space-between; align-items:flex-end; gap:12px; flex-wrap:wrap; margin-bottom:14px; }
    .sa-section-head h4 { margin:0; color:#061b33; font-weight:700; }
    .sa-section-head p { margin:4px 0 0; color:#64748b; }
    .sa-directory-grid { display:grid; grid-template-columns:repeat(auto-fill,minmax(260px,1fr)); gap:14px; }
    .sa-directory-card { min-height:180px; border:1px solid #d8e2ee; border-radius:18px; background:#fff; padding:16px; box-shadow:0 12px 28px rgba(15,23,42,.06); color:#09213d; }
    .sa-directory-card.feature { background:linear-gradient(135deg,#082f55,#0b5fb8); color:#fff; }
    .sa-directory-card.feature h5, .sa-directory-card.feature p, .sa-directory-card.feature small { color:#fff !important; }
    .sa-directory-card .eyebrow { color:#d4a23a; text-transform:uppercase; letter-spacing:.12em; font-size:12px; font-weight:700; }
    .sa-pms-board { display:block; }
    .sa-pms-sidebar { background:#082f55; color:#fff; border-radius:18px; padding:16px; box-shadow:0 14px 32px rgba(15,23,42,.12); margin-bottom:16px; }
    .sa-pms-sidebar h4, .sa-pms-sidebar p, .sa-pms-sidebar small { color:#fff !important; }
    .sa-pms-sidebar .sa-pms-intro { display:flex; justify-content:space-between; align-items:flex-end; gap:16px; flex-wrap:wrap; margin-bottom:14px; }
    .sa-pms-sidebar .sa-pms-intro p { max-width:720px; }
    .sa-pms-metrics { display:grid; grid-template-columns:repeat(auto-fit,minmax(145px,1fr)); gap:10px; }
    .sa-pms-sidebar a, .sa-pms-sidebar div.metric { display:block; color:#dbeafe; text-decoration:none; border:1px solid rgba(255,255,255,.14); border-radius:12px; padding:12px; background:rgba(255,255,255,.06); min-height:82px; }
    .sa-pms-sidebar div.metric strong { display:block; font-size:24px; line-height:1; margin-bottom:6px; }
    .sa-pms-sidebar strong { color:#fff; }
    .sa-room-wall { display:grid; grid-template-columns:repeat(2,minmax(0,1fr)); gap:16px; align-items:stretch; }
    .sa-room-wall > form { display:none !important; }
    .sa-room-wall > .sa-room-manager-card { min-width:0; }
    body.hotel-workspace .page-wrapper.sa-hotel .sa-pms-board { display:block !important; grid-template-columns:none !important; width:100% !important; max-width:none !important; }
    body.hotel-workspace .page-wrapper.sa-hotel .sa-dash-panel { width:100% !important; max-width:none !important; }
    body.hotel-workspace .page-wrapper.sa-hotel .sa-pms-sidebar { display:block !important; width:100% !important; max-width:none !important; min-height:auto !important; margin:0 0 16px !important; }
    body.hotel-workspace .page-wrapper.sa-hotel .sa-pms-sidebar .sa-pms-intro { display:flex !important; justify-content:space-between !important; align-items:flex-end !important; gap:16px !important; flex-wrap:wrap !important; margin-bottom:14px !important; }
    body.hotel-workspace .page-wrapper.sa-hotel .sa-pms-metrics { display:grid !important; grid-template-columns:repeat(auto-fit,minmax(145px,1fr)) !important; gap:10px !important; }
    body.hotel-workspace .page-wrapper.sa-hotel .sa-room-wall { display:grid !important; grid-template-columns:repeat(2,minmax(0,1fr)) !important; gap:16px !important; width:100% !important; }
    body.hotel-workspace .page-wrapper.sa-hotel .sa-room-wall > form { display:none !important; }
    .sa-room-tile { min-height:160px; border-radius:14px; padding:12px; border:2px solid #d8e2ee; background:#fff; display:flex; flex-direction:column; justify-content:space-between; }
    .sa-room-tile.available { border-color:#16a34a; background:#ecfdf3; } .sa-room-tile.occupied { border-color:#2563eb; background:#eff6ff; } .sa-room-tile.reserved { border-color:#d4a23a; background:#fff8e1; } .sa-room-tile.maintenance, .sa-room-tile.out_of_order { border-color:#dc2626; background:#fff1f2; }
    .sa-room-tile .room-no { font-size:38px; font-weight:700; color:#061b33; line-height:1; }
    .sa-timeline { overflow:auto; border:1px solid #d8e2ee; border-radius:16px; background:#fff; }
    .sa-timeline table { min-width:1050px; margin:0; }
    .sa-timeline th { background:#061b33; color:#fff; border:0; text-transform:uppercase; font-size:12px; }
    .sa-timeline td { height:64px; vertical-align:middle; border-color:#e5edf6; }
    .sa-pill-event { display:inline-flex; align-items:center; border-radius:999px; padding:7px 11px; color:#fff; background:#2563eb; font-weight:700; font-size:12px; }
    .sa-pill-event.green { background:#16a34a; } .sa-pill-event.gold { background:#d4a23a; color:#111827; } .sa-pill-event.red { background:#dc2626; }
    .sa-desk { display:grid; grid-template-columns:minmax(0,1fr) 300px; gap:16px; }
    .sa-desk-card { border:1px solid #e3ebf5; border-radius:16px; padding:14px; background:#fff; margin-bottom:10px; box-shadow:0 8px 20px rgba(15,23,42,.04); }
    .sa-desk-card.warn { border-left:6px solid #d4a23a; } .sa-desk-card.danger { border-left:6px solid #dc2626; } .sa-desk-card.good { border-left:6px solid #16a34a; }
    .sa-guest-ledger { display:grid; grid-template-columns:280px minmax(0,1fr); gap:16px; }
    .sa-profile-card { display:grid; grid-template-columns:64px minmax(0,1fr); gap:13px; align-items:center; border:1px solid #d8e2ee; border-radius:18px; background:#fff; padding:15px; box-shadow:0 10px 24px rgba(15,23,42,.05); }
    .sa-big-avatar { width:64px; height:64px; border-radius:50%; background:#0b2f54; color:#fff; display:grid; place-items:center; font-size:25px; font-weight:700; }
    .sa-cashier-grid { display:grid; grid-template-columns:285px minmax(0,1fr) 270px; gap:16px; }
    .sa-payment-pad { display:grid; grid-template-columns:repeat(2,minmax(0,1fr)); gap:10px; }
    .sa-payment-pad div, .sa-payment-pad a { min-height:82px; border:1px solid #d8e2ee; border-radius:14px; background:#fff; display:grid; place-items:center; text-align:center; font-weight:700; color:#09213d; text-decoration:none; }
    .sa-payment-pad a:hover { border-color:#0b5fb8; color:#0b5fb8; }

    .sa-hk-command { background:#f8fbff; border:1px solid #d8e2ee; border-radius:18px; padding:16px; box-shadow:0 12px 28px rgba(15,23,42,.06); }
    .sa-hk-head { display:flex; justify-content:space-between; gap:12px; align-items:flex-end; flex-wrap:wrap; margin-bottom:14px; }
    .sa-hk-head h4 { margin:0; color:#061b33; font-weight:700; }
    .sa-hk-head p { margin:4px 0 0; color:#64748b; }
    .sa-hk-chips { display:flex; gap:8px; flex-wrap:wrap; margin-bottom:14px; }
    .sa-hk-chip { border-radius:999px; padding:8px 12px; font-weight:700; background:#fff; border:1px solid #dbe4ef; color:#0f172a; }
    .sa-hk-chip.dirty { background:#fee2e2; color:#991b1b; }
    .sa-hk-chip.assigned { background:#ffedd5; color:#9a3412; }
    .sa-hk-chip.cleaning { background:#dbeafe; color:#1d4ed8; }
    .sa-hk-chip.clean { background:#dcfce7; color:#166534; }
    .sa-hk-grid { display:grid; grid-template-columns:repeat(auto-fill,minmax(215px,1fr)); gap:12px; }
    .sa-hk-room { min-height:178px; border-radius:16px; border:2px solid #2563eb; background:#eff6ff; padding:14px; display:flex; flex-direction:column; justify-content:space-between; }
    .sa-hk-room.dirty { border-color:#dc2626; background:#fff1f2; }
    .sa-hk-room.assigned { border-color:#d97706; background:#fffbeb; }
    .sa-hk-room.cleaning { border-color:#2563eb; background:#eff6ff; }
    .sa-hk-room.clean { border-color:#16a34a; background:#ecfdf3; }
    .sa-hk-room-no { font-size:34px; line-height:1; font-weight:700; color:#061b33; }
    .sa-hk-status { display:inline-flex; width:max-content; border-radius:8px; padding:5px 8px; font-size:12px; font-weight:700; background:#fff; color:#0f172a; margin-top:7px; }
    .sa-hk-table { margin-top:14px; overflow:auto; }
    @media(min-width:1700px){.sa-room-wall,body.hotel-workspace .page-wrapper.sa-hotel .sa-room-wall{grid-template-columns:repeat(3,minmax(0,1fr)) !important}}
    @media(max-width:1199px){.sa-room-wall,body.hotel-workspace .page-wrapper.sa-hotel .sa-room-wall{grid-template-columns:repeat(2,minmax(0,1fr)) !important}.sa-room-manager-actions{grid-template-columns:repeat(2,minmax(0,1fr))}.sa-upgrade-actions{grid-template-columns:repeat(3,1fr)}.sa-command-grid,.sa-kpis,.sa-grid,.sa-kanban,.sa-report-grid,.sa-service-grid,.sa-profile-grid,.sa-health,.sa-audit-grid,.sa-directory-grid,.sa-dashboard-services,.sa-calendar-pulse{grid-template-columns:repeat(2,1fr)}.sa-workspace,.sa-cashier,.sa-room-admin,.sa-folio-register,.sa-maint-desk,.sa-desk,.sa-guest-ledger,.sa-cashier-grid{grid-template-columns:1fr}.sa-board-row,.sa-maint-ticket{grid-template-columns:1fr}}
    @media(max-width:767px){.sa-room-wall,body.hotel-workspace .page-wrapper.sa-hotel .sa-room-wall,.sa-form-grid,.sa-upgrade-actions,.sa-command-grid,.sa-kpis,.sa-grid,.sa-kanban,.sa-report-grid,.sa-service-grid,.sa-profile-grid,.sa-health,.sa-audit-grid,.sa-directory-grid,.sa-dashboard-services,.sa-calendar-pulse{grid-template-columns:1fr !important}.page-wrapper.sa-hotel .sa-hero h2{font-size:23px}}
</style>
@endsection

@section('content')
@php
    $isPaginator = $panelData instanceof \Illuminate\Pagination\LengthAwarePaginator;
    $panelRows = $isPaginator ? collect($panelData->items()) : collect($panelData);
    $rowArray = function ($row) {
        return $row instanceof \Illuminate\Database\Eloquent\Model ? $row->getAttributes() : (array) $row;
    };
    $money = fn($value) => number_format((float) ($value ?? 0), 2);
    $panelTitle = $panels[$panel] ?? ucfirst($panel);
    $statusBadge = function ($status) {
        return match((string) $status) {
            'active', 'available', 'completed', 'resolved', 'checked_in' => 'bg-success',
            'reserved', 'confirmed', 'paid' => 'bg-primary',
            'dirty', 'high', 'critical', 'failed', 'cancelled', 'out_of_order' => 'bg-danger',
            'pending', 'maintenance', 'open' => 'bg-warning text-dark',
            default => 'bg-secondary',
        };
    };
@endphp
<div class="page-wrapper sa-hotel">
    <div class="content container-fluid">
        <section class="sa-hero">
            <div>
                <small>SmartProbook Hotel PMS · Super Admin Enterprise Monitor</small>
                <h2>{{ $panelTitle }}</h2>
                <p class="mb-0">Room rack, reservations, folios, housekeeping, maintenance and audit monitoring across hotel tenants.</p>
                @if(!empty($hotelDemoSeedPresent))
                    <div class="mt-2 small" style="color:#fff">Demo hotel data is active. Remove after review with <code style="color:#f7d777">php artisan hotel:demo-data --cleanup</code>.</div>
                @endif
            </div>
            <form method="GET" action="{{ route('super_admin.hotels.index') }}" class="sa-panel-switcher align-self-start">
                @if($selectedCompanyId)<input type="hidden" name="company_id" value="{{ $selectedCompanyId }}">@endif
                @if($selectedServiceCenter !== 'all')<input type="hidden" name="service" value="{{ $selectedServiceCenter }}">@endif
                <label class="form-label">Hotel View</label>
                <select name="panel" class="form-select" onchange="this.form.submit()">
                    @foreach($panels as $panelKey => $panelLabel)
                        <option value="{{ $panelKey }}" {{ $panel === $panelKey ? 'selected' : '' }}>{{ $panelLabel }}</option>
                    @endforeach
                </select>
            </form>
        </section>

        <section class="sa-upgrade-banner">
            <div class="sa-upgrade-banner-head">
                <div>
                    <span class="badge bg-warning text-dark">Hotel Operations Upgrade Live</span>
                    <h4 class="mt-2">Super Admin can now monitor the upgraded hotel workflow.</h4>
                    <p>Use these shortcuts to inspect rooms, galleries, reservations, folios, housekeeping, maintenance and service-center progress.</p>
                </div>
                <a href="{{ route('super_admin.hotels.index', array_merge($routeParams ?? [], ['panel' => 'progress'])) }}" class="btn btn-primary">Open Progress Board</a>
            </div>
            <div class="sa-upgrade-actions">
                @foreach([
                    'progress' => ['Upgrade Progress', 'fa-list-check'],
                    'rooms' => ['Room Board', 'fa-bed'],
                    'room_gallery' => ['Room Gallery', 'fa-images'],
                    'reservations' => ['Reservations', 'fa-calendar-check'],
                    'folios' => ['Guest Folios', 'fa-file-invoice-dollar'],
                    'housekeeping' => ['Housekeeping', 'fa-broom'],
                    'maintenance' => ['Maintenance', 'fa-screwdriver-wrench'],
                    'service_bar' => ['Bar', 'fa-martini-glass-citrus'],
                    'service_spa' => ['Spa', 'fa-spa'],
                    'service_gym' => ['Gym', 'fa-dumbbell'],
                    'service_ticketing' => ['Ticketing', 'fa-ticket'],
                    'reports' => ['Reports', 'fa-chart-line'],
                ] as $quickPanel => $quick)
                    <a href="{{ route('super_admin.hotels.index', array_merge($routeParams ?? [], ['panel' => $quickPanel] + ($selectedCompanyId ? ['company_id' => $selectedCompanyId] : []))) }}" class="{{ $panel === $quickPanel ? 'active' : '' }}">
                        <i class="fas {{ $quick[1] }}"></i>
                        <span>{{ $quick[0] }}</span>
                    </a>
                @endforeach
            </div>
        </section>

        @if(session('success'))
            <div class="alert alert-success">{{ session('success') }}</div>
        @endif
        @if($errors->any())
            <div class="alert alert-danger">
                <strong>Please check the hotel form.</strong>
                <div>{{ $errors->first() }}</div>
            </div>
        @endif

        <form method="GET" action="{{ route('super_admin.hotels.index') }}" class="sa-filter d-flex flex-wrap gap-2 align-items-end">
            <input type="hidden" name="panel" value="{{ $panel }}">
            @if($selectedServiceCenter !== 'all')<input type="hidden" name="service" value="{{ $selectedServiceCenter }}">@endif
            <div style="min-width:260px"><label class="form-label">Hotel Tenant</label><select name="company_id" class="form-control"><option value="">All Hotel Tenants</option>@foreach($hotelCompanies as $company)<option value="{{ $company->id }}" {{ (int) $selectedCompanyId === (int) $company->id ? 'selected' : '' }}>{{ $company->name }}</option>@endforeach</select></div>
            <button class="btn btn-primary">Apply Filter</button>
        </form>

        @php
            $opsRooms = collect($roomManagement['rooms'] ?? []);
            $opsLockRoom = $opsRooms->first();
            $opsLockModalId = $opsLockRoom ? 'saOpsLockRoom'.$opsLockRoom->id : null;
        @endphp
        @if($panel !== 'overview')
            <div class="sa-ops-action-strip">
                <a class="btn btn-outline-primary" href="{{ route('super_admin.hotels.index', array_merge($routeParams ?? [], ['panel' => 'rooms'] + ($selectedCompanyId ? ['company_id' => $selectedCompanyId] : []))) }}"><i class="fas fa-bed"></i> Room Board</a>
                <a class="btn btn-outline-primary" href="{{ route('super_admin.hotels.index', array_merge($routeParams ?? [], ['panel' => 'room_gallery'] + ($selectedCompanyId ? ['company_id' => $selectedCompanyId] : []))) }}"><i class="fas fa-images"></i> Room Gallery</a>
                <a class="btn btn-outline-warning" href="{{ route('super_admin.hotels.index', array_merge($routeParams ?? [], ['panel' => 'maintenance'] + ($selectedCompanyId ? ['company_id' => $selectedCompanyId] : []))) }}"><i class="fas fa-screwdriver-wrench"></i> Maintenance</a>
                <a class="btn btn-outline-secondary" href="{{ route('super_admin.hotels.index', array_merge($routeParams ?? [], ['panel' => 'service_room_service'] + ($selectedCompanyId ? ['company_id' => $selectedCompanyId] : []))) }}"><i class="fas fa-bell-concierge"></i> Room Service</a>
                @if($opsLockRoom)
                    <button type="button" class="btn btn-outline-dark" data-bs-toggle="modal" data-bs-target="#saOpsHousekeepingTask"><i class="fas fa-broom"></i> Open HK Task</button>
                    <button type="button" class="btn btn-outline-danger" data-bs-toggle="modal" data-bs-target="#saOpsMaintenanceTicket"><i class="fas fa-toolbox"></i> Maintenance Ticket</button>
                    <button type="button" class="btn btn-warning" data-bs-toggle="modal" data-bs-target="#{{ $opsLockModalId }}"><i class="fas fa-lock"></i> Lock Room</button>
                @else
                    <button type="button" class="btn btn-outline-secondary disabled"><i class="fas fa-broom"></i> Open HK Task</button>
                    <button type="button" class="btn btn-outline-secondary disabled"><i class="fas fa-toolbox"></i> Maintenance Ticket</button>
                    <button type="button" class="btn btn-outline-secondary disabled"><i class="fas fa-lock"></i> Lock Room</button>
                @endif
                <a class="btn btn-outline-dark" href="{{ route('super_admin.hotels.index', array_merge($routeParams ?? [], ['panel' => 'reports'] + ($selectedCompanyId ? ['company_id' => $selectedCompanyId] : []))) }}"><i class="fas fa-chart-line"></i> Reports</a>
            </div>
            @if($opsLockRoom)
                <div class="modal fade" id="saOpsHousekeepingTask" tabindex="-1" aria-hidden="true">
                    <div class="modal-dialog modal-lg modal-dialog-centered">
                        <form method="POST" action="{{ route('super_admin.hotels.housekeeping.tasks.store') }}" class="modal-content">
                            @csrf
                            <div class="modal-header"><h5 class="modal-title">Open Housekeeping Task</h5><button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button></div>
                            <div class="modal-body">
                                <div class="sa-form-grid">
                                    <div><label class="form-label">Room</label><select name="room_id" class="form-select" required>@foreach($opsRooms as $opsRoom)<option value="{{ $opsRoom->id }}">{{ $opsRoom->property?->name ?? ('Property '.$opsRoom->property_id) }} - Room {{ $opsRoom->room_number }}</option>@endforeach</select></div>
                                    <div><label class="form-label">Task Type</label><select name="task_type" class="form-select" required><option value="departure_clean">Departure clean</option><option value="stayover">Stayover</option><option value="deep_clean">Deep clean</option><option value="inspection">Inspection</option><option value="rush_clean">Rush clean</option><option value="room_service_cleanup">Room service cleanup</option></select></div>
                                    <div><label class="form-label">Priority</label><select name="priority" class="form-select" required><option value="normal">Normal</option><option value="high">High</option><option value="urgent">Urgent</option><option value="low">Low</option></select></div>
                                    <div class="full"><label class="form-label">Task Note</label><input name="note" class="form-control" placeholder="Cleaning instruction, guest request, room service cleanup note"></div>
                                </div>
                            </div>
                            <div class="modal-footer"><button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Close</button><button class="btn btn-primary">Create Task</button></div>
                        </form>
                    </div>
                </div>
                <div class="modal fade" id="saOpsMaintenanceTicket" tabindex="-1" aria-hidden="true">
                    <div class="modal-dialog modal-lg modal-dialog-centered">
                        <form method="POST" action="{{ route('super_admin.hotels.maintenance.tickets.store') }}" class="modal-content">
                            @csrf
                            <div class="modal-header"><h5 class="modal-title">Open Maintenance Ticket</h5><button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button></div>
                            <div class="modal-body">
                                <div class="sa-form-grid">
                                    <div><label class="form-label">Room</label><select name="room_id" class="form-select" required>@foreach($opsRooms as $opsRoom)<option value="{{ $opsRoom->id }}">{{ $opsRoom->property?->name ?? ('Property '.$opsRoom->property_id) }} - Room {{ $opsRoom->room_number }}</option>@endforeach</select></div>
                                    <div><label class="form-label">Severity</label><select name="severity" class="form-select" required><option value="medium">Medium</option><option value="high">High</option><option value="critical">Critical</option><option value="low">Low</option></select></div>
                                    <div class="full"><label class="form-label">Issue Title</label><input name="title" class="form-control" required placeholder="AC fault, water leak, door lock issue"></div>
                                    <div class="full"><label class="form-label">Description</label><textarea name="description" class="form-control" rows="3" placeholder="Internal maintenance details"></textarea></div>
                                </div>
                            </div>
                            <div class="modal-footer"><button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Close</button><button class="btn btn-danger">Create Ticket</button></div>
                        </form>
                    </div>
                </div>
                <div class="modal fade" id="{{ $opsLockModalId }}" tabindex="-1" aria-hidden="true">
                    <div class="modal-dialog modal-lg modal-dialog-centered">
                        <form method="POST" action="{{ route('super_admin.hotels.rooms.blocks.store', $opsLockRoom) }}" class="modal-content">
                            @csrf
                            <div class="modal-header"><h5 class="modal-title">Lock Room {{ $opsLockRoom->room_number }}</h5><button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button></div>
                            <div class="modal-body">
                                <div class="sa-form-grid">
                                    <div><label class="form-label">Room</label><select class="form-select" onchange="if(this.value){this.form.action=this.value;}">@foreach($opsRooms as $opsRoom)<option value="{{ route('super_admin.hotels.rooms.blocks.store', $opsRoom) }}">{{ $opsRoom->property?->name ?? ('Property '.$opsRoom->property_id) }} - Room {{ $opsRoom->room_number }}</option>@endforeach</select></div>
                                    <div><label class="form-label">Lock Reason</label><select name="block_type" class="form-select" required><option value="room_service_hold">Room service hold</option><option value="maintenance">Maintenance</option><option value="out_of_order">Out of order</option><option value="housekeeping_hold">Housekeeping hold</option><option value="vip_hold">VIP hold</option><option value="admin_hold">Admin hold</option></select></div>
                                    <div><label class="form-label">Start Date</label><input type="date" name="start_date" class="form-control" value="{{ now()->toDateString() }}" required></div>
                                    <div><label class="form-label">End Date</label><input type="date" name="end_date" class="form-control" value="{{ now()->addDay()->toDateString() }}" required></div>
                                    <div class="full"><label class="form-label">Internal Reason</label><input name="reason" class="form-control" placeholder="Reason for locking this room from sale or operations"></div>
                                </div>
                            </div>
                            <div class="modal-footer"><button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Close</button><button class="btn btn-warning">Save Room Lock</button></div>
                        </form>
                    </div>
                </div>
            @endif
        @endif

        @if($panel === 'progress')
            <section class="sa-dash-panel">
                <div class="sa-section-head">
                    <div>
                        <small class="text-warning fw-semibold">HOTEL MODULE PROGRESS</small>
                        <h4>Implementation Visibility</h4>
                        <p>What has changed in the hotel management module and where Super Admin can monitor it.</p>
                    </div>
                    <span class="btn btn-success disabled">{{ $panelRows->where('status', 'completed')->count() }} completed</span>
                </div>
                <div class="sa-command-grid">
                    @foreach([
                        ['panel' => 'overview', 'icon' => 'fa-gauge-high', 'label' => 'Hotel Dashboard', 'hint' => 'Portfolio KPIs, revenue, occupancy'],
                        ['panel' => 'rooms', 'icon' => 'fa-bed', 'label' => 'Room Board', 'hint' => 'Room status and readiness'],
                        ['panel' => 'room_gallery', 'icon' => 'fa-images', 'label' => 'Room Gallery', 'hint' => 'Uploaded cover and panorama media'],
                        ['panel' => 'reservations', 'icon' => 'fa-calendar-check', 'label' => 'Reservations', 'hint' => 'Booking pipeline and deposits'],
                        ['panel' => 'folios', 'icon' => 'fa-file-invoice-dollar', 'label' => 'Guest Folios', 'hint' => 'Charges, payments, balances'],
                        ['panel' => 'housekeeping', 'icon' => 'fa-broom', 'label' => 'Housekeeping', 'hint' => 'Dirty, assigned, cleaning, inspected'],
                        ['panel' => 'maintenance', 'icon' => 'fa-screwdriver-wrench', 'label' => 'Maintenance', 'hint' => 'Room tickets and conflicts'],
                        ['panel' => 'service_ticketing', 'icon' => 'fa-ticket', 'label' => 'Ticketing / Events', 'hint' => 'Event revenue monitor'],
                    ] as $action)
                        <a class="sa-command-link" href="{{ route('super_admin.hotels.index', array_merge($routeParams ?? [], ['panel' => $action['panel']] + ($selectedCompanyId ? ['company_id' => $selectedCompanyId] : []))) }}">
                            <i class="fas {{ $action['icon'] }}"></i>
                            <span><strong>{{ $action['label'] }}</strong><span>{{ $action['hint'] }}</span></span>
                        </a>
                    @endforeach
                </div>
                <div class="sa-progress-grid">
                    @foreach($panelRows as $row)
                        @php $r = $rowArray($row); @endphp
                        <article class="sa-progress-card {{ ($r['status'] ?? '') === 'completed' ? '' : 'pending' }}">
                            <span class="badge {{ ($r['status'] ?? '') === 'completed' ? 'bg-success' : 'bg-warning text-dark' }}">{{ ucfirst(str_replace('_', ' ', (string)($r['status'] ?? 'pending'))) }}</span>
                            <h5 class="mt-3 mb-2">{{ $r['area'] ?? 'Hotel Area' }}</h5>
                            <p class="text-muted mb-0">{{ $r['evidence'] ?? '-' }}</p>
                        </article>
                    @endforeach
                </div>
            </section>
        @elseif($panel === 'overview')
            @php
                $occupancyRate = $totalRooms > 0 ? round(($occupiedRooms / max($totalRooms, 1)) * 100) : 0;
                $availableRate = $totalRooms > 0 ? round(($availableRooms / max($totalRooms, 1)) * 100) : 0;
                $reservedRate = $totalRooms > 0 ? round(($reservedRooms / max($totalRooms, 1)) * 100) : 0;
                $trendRows = collect($revenueTrend ?? []);
                $trendMax = max((float) $trendRows->max('amount'), 1);
                $arrivalRows = $todayReservations > 0 ? collect(range(1, min($todayReservations, 4))) : collect([1, 2, 3]);
            @endphp

            <div class="sa-mini-kpis">
                <div class="sa-mini-kpi"><span>Occupancy</span><strong>{{ $occupancyRate }}%</strong><small>{{ $occupiedRooms }} of {{ $totalRooms }} rooms</small></div>
                <div class="sa-mini-kpi"><span>Available Rooms</span><strong>{{ $availableRooms }}</strong><small>{{ $availableRate }}% of inventory</small></div>
                <div class="sa-mini-kpi"><span>In-House Guests</span><strong>{{ $currentInHouseGuests }}</strong><small>Checked-in stays</small></div>
                <div class="sa-mini-kpi"><span>Today's Revenue</span><strong>{{ $money($hotelRevenueToday) }}</strong><small>This month {{ $money($hotelRevenueThisMonth) }}</small></div>
            </div>

            <div class="sa-dash-grid">
                <section class="sa-dash-panel">
                    <div class="d-flex justify-content-between align-items-start flex-wrap gap-2">
                        <div><h4>Revenue & Occupancy Trend</h4><p>Seven-day operational pulse across hotel tenants.</p></div>
                        <span class="badge bg-light text-dark">7 days</span>
                    </div>
                    <div class="sa-chart-bars">
                        @foreach($trendRows as $index => $trend)
                            @php
                                $trendAmount = (float) ($trend['amount'] ?? 0);
                                $trendHeight = 45 + round(($trendAmount / $trendMax) * 125);
                            @endphp
                            <div class="sa-chart-bar {{ $index % 3 === 1 ? 'gold' : '' }}" style="height:{{ $trendHeight }}px" data-label="{{ $trend['label'] ?? '-' }}"><span>{{ $money($trendAmount) }}</span></div>
                        @endforeach
                    </div>
                </section>

                <section class="sa-dash-panel">
                    <h4>Room Status</h4>
                    <p>Availability, occupied, reserved and exception rooms.</p>
                    <div class="sa-room-status mt-3">
                        <div class="sa-donut"><strong>{{ $totalRooms }}</strong></div>
                        <div class="sa-status-list">
                            <div class="sa-status-item"><span><i class="sa-dot green"></i>Available</span><strong>{{ $availableRooms }}</strong></div>
                            <div class="sa-status-item"><span><i class="sa-dot blue"></i>Occupied</span><strong>{{ $occupiedRooms }}</strong></div>
                            <div class="sa-status-item"><span><i class="sa-dot gold"></i>Reserved</span><strong>{{ $reservedRooms }}</strong></div>
                            <div class="sa-status-item"><span><i class="sa-dot red"></i>Review</span><strong>{{ max($totalRooms - $availableRooms - $occupiedRooms - $reservedRooms, 0) }}</strong></div>
                        </div>
                    </div>
                </section>
            </div>

            <div class="sa-dash-bottom">
                <section class="sa-dash-panel">
                    <div class="d-flex justify-content-between align-items-center gap-2"><h4>Today's Arrivals</h4><span class="badge bg-light text-dark">Front office</span></div>
                    <div class="sa-ops-list">
                        @foreach($arrivalRows as $i)
                            <div class="sa-ops-row"><div><strong>{{ $todayReservations ? 'Reservation queue' : 'No live arrival yet' }}</strong><div class="small text-muted">{{ $todayReservations ? 'Guest arrival awaiting room flow' : 'Tenant arrivals will appear here' }}</div></div><span class="badge bg-primary">{{ $todayReservations ? 'Due' : 'Preview' }}</span></div>
                        @endforeach
                    </div>
                </section>

                <section class="sa-dash-panel">
                    <div class="d-flex justify-content-between align-items-center gap-2"><h4>Departures</h4><span class="badge bg-light text-dark">Settlement</span></div>
                    <div class="sa-ops-list">
                        <div class="sa-ops-row"><div><strong>Checkout queue</strong><div class="small text-muted">Open stays ready for settlement</div></div><span class="badge bg-success">{{ $currentInHouseGuests }}</span></div>
                        <div class="sa-ops-row"><div><strong>Outstanding receivables</strong><div class="small text-muted">Guest balances needing follow-up</div></div><span class="badge bg-warning text-dark">{{ $money($outstandingReceivables) }}</span></div>
                        <div class="sa-ops-row"><div><strong>Night audit readiness</strong><div class="small text-muted">Close-day control checkpoint</div></div><span class="badge bg-light text-dark">Monitor</span></div>
                    </div>
                </section>

                <section class="sa-dash-panel">
                    <h4>Alerts</h4>
                    <div class="sa-alert-line {{ $reservedRooms > $availableRooms ? 'danger' : '' }}"><strong>{{ $reservedRooms > $availableRooms ? 'Reservation pressure' : 'Room balance stable' }}</strong><div class="small text-muted">Reserved {{ $reservedRooms }} vs available {{ $availableRooms }}.</div></div>
                    <div class="sa-alert-line"><strong>Housekeeping watch</strong><div class="small text-muted">Dirty and inspection rooms are monitored in the housekeeping board.</div></div>
                    <div class="sa-alert-line"><strong>Service centres</strong><div class="small text-muted">Restaurant, bar, spa, gym and laundry revenue are audit-ready below.</div></div>
                </section>
            </div>

            <section class="sa-dash-panel">
                <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
                    <div><h4>Service Centre Monitor</h4><p>Hotel revenue centres summarized for this dashboard.</p></div>
                    <span class="badge bg-light text-dark">Service summary</span>
                </div>
                <div class="sa-dashboard-services">
                    @foreach($serviceCenters as $serviceKey => $serviceMeta)
                        @continue($serviceKey === 'all')
                        @php $serviceStats = $serviceSummary[$serviceKey] ?? ['count' => 0, 'total' => 0]; @endphp
                        <a class="sa-dashboard-service" href="{{ route('super_admin.hotels.index', array_merge($routeParams ?? [], ['panel' => 'service_'.$serviceKey] + ($selectedCompanyId ? ['company_id' => $selectedCompanyId] : []))) }}">
                            <div><small>{{ strtoupper(str_replace('_',' ', $serviceKey)) }}</small><br><strong>{{ $serviceMeta['label'] }}</strong><b>{{ $money($serviceStats['total'] ?? 0) }}</b></div>
                            <span class="badge bg-light text-dark">{{ $serviceStats['count'] ?? 0 }} lines</span>
                        </a>
                    @endforeach
                </div>
            </section>
        @elseif(in_array($panel, ['tenants','properties'], true))
            @php
                $directoryRoomCounts = collect($roomManagement['rooms'] ?? [])->groupBy($panel === 'tenants' ? 'company_id' : 'property_id')->map->count();
            @endphp
            <section class="sa-dash-panel">
                <div class="sa-section-head">
                    <div>
                        <small class="text-warning fw-semibold">{{ $panel === 'tenants' ? 'TENANT CONTROL' : 'PROPERTY DIRECTORY' }}</small>
                        <h4>{{ $panel === 'tenants' ? 'Hotel Tenant Command' : 'Property Operations Directory' }}</h4>
                        <p>{{ $panel === 'tenants' ? 'Hotel-enabled companies, plan readiness and operating footprint.' : 'Branches and hotel properties connected to tenant operations.' }}</p>
                    </div>
                    <span class="btn btn-outline-primary disabled">{{ $panelRows->count() }} records</span>
                </div>
                <div class="sa-mini-kpis">
                    <div class="sa-mini-kpi"><span>{{ $panel === 'tenants' ? 'Tenants' : 'Properties' }}</span><strong>{{ $panelRows->count() }}</strong><small>Visible records</small></div>
                    <div class="sa-mini-kpi"><span>Rooms</span><strong>{{ $totalRooms }}</strong><small>Inventory in scope</small></div>
                    <div class="sa-mini-kpi"><span>Available</span><strong>{{ $availableRooms }}</strong><small>Ready for sale</small></div>
                    <div class="sa-mini-kpi"><span>Revenue</span><strong>{{ $money($hotelRevenueThisMonth) }}</strong><small>This month</small></div>
                </div>
                <div class="sa-directory-grid">
                    @forelse($panelRows as $row)
                        @php $r=$rowArray($row); $title=$r['name'] ?? ('Record #'.($r['id'] ?? '-')); @endphp
                        <article class="sa-directory-card {{ $loop->first ? 'feature' : '' }}">
                            <span class="eyebrow">{{ $panel === 'tenants' ? 'Hotel Tenant' : 'Property' }}</span>
                            <h5 class="mt-2 mb-2">{{ $title }}</h5>
                            <p class="mb-3 {{ $loop->first ? '' : 'text-muted' }}">Company {{ $r['company_id'] ?? $r['id'] ?? '-' }} - Branch {{ $r['branch_id'] ?? 'All' }}</p>
                            <div class="d-flex justify-content-between gap-2"><span>Status</span><strong>{{ ucfirst((string)($r['status'] ?? 'active')) }}</strong></div>
                            <div class="d-flex justify-content-between gap-2"><span>Record</span><strong>#{{ $r['id'] ?? '-' }}</strong></div>
                            <div class="d-flex justify-content-between gap-2"><span>Rooms</span><strong>{{ $directoryRoomCounts[$r['id'] ?? 0] ?? 0 }}</strong></div>
                            <div class="d-flex gap-2 mt-3 flex-wrap">
                                <a class="btn btn-sm btn-outline-primary" href="{{ route('super_admin.hotels.index', ['panel' => 'room_gallery', 'company_id' => $r['company_id'] ?? $r['id'] ?? null]) }}">Rooms</a>
                                <a class="btn btn-sm btn-outline-secondary" href="{{ route('super_admin.hotels.index', ['panel' => 'room_types', 'company_id' => $r['company_id'] ?? $r['id'] ?? null]) }}">Rates</a>
                                <a class="btn btn-sm btn-outline-dark" href="{{ route('super_admin.hotels.index', ['panel' => 'reports', 'company_id' => $r['company_id'] ?? $r['id'] ?? null]) }}">Reports</a>
                            </div>
                        </article>
                    @empty
                        <div class="sa-empty">No {{ strtolower($panelTitle) }} found yet. Once a tenant enables hotel setup, this becomes a rich operations directory.</div>
                    @endforelse
                </div>
            </section>
        @elseif(in_array($panel, ['rooms', 'room_gallery'], true))
            @php
                $managedRooms = collect($roomManagement['rooms'] ?? []);
                $managedProperties = collect($roomManagement['properties'] ?? []);
                $managedRoomTypes = collect($roomManagement['roomTypes'] ?? []);
                $managedCompanies = collect($roomManagement['companies'] ?? []);
                $roomTotalLabel = $isPaginator ? $panelData->total() : $managedRooms->count();
            @endphp
            <div class="sa-pms-board">
                <section class="sa-dash-panel">
                    <div class="sa-pms-sidebar">
                        <div class="sa-pms-intro">
                            <div><small>{{ $panel === 'room_gallery' ? 'ROOM GALLERY' : 'ROOM BOARD' }}</small><h4>{{ $panel === 'room_gallery' ? 'Room Media & Pricing' : 'Room Inventory Manager' }}</h4></div>
                            <p class="mb-0">Create rooms, set rate overrides, edit status, and upload room photos without leaving Super Admin.</p>
                        </div>
                        <div class="sa-pms-metrics">
                            <div class="metric"><strong>{{ $totalRooms }}</strong><br>Total active rooms</div>
                            <div class="metric"><strong>{{ $availableRooms }}</strong><br>Available</div>
                            <div class="metric"><strong>{{ $occupiedRooms }}</strong><br>Occupied</div>
                            <div class="metric"><strong>{{ $reservedRooms }}</strong><br>Reserved</div>
                            <div class="metric"><strong>{{ $maintenanceRooms + $outOfOrderRooms }}</strong><br>Maintenance / out of order</div>
                            <div class="metric"><strong>{{ $dirtyRooms }}</strong><br>Dirty rooms</div>
                            <div class="metric"><strong>{{ $roomManagement['mediaCount'] ?? 0 }}</strong><br>Uploaded media files</div>
                        </div>
                    </div>
                    <div class="sa-management-console">
                        <div class="sa-section-head mb-0">
                            <div>
                                <small class="text-warning fw-semibold">LIVE ROOM MANAGEMENT</small>
                                <h4>{{ $panel === 'room_gallery' ? 'Room Media Gallery' : 'Room State Grid' }}</h4>
                                <p>{{ $roomTotalLabel }} rooms found. Add rooms, update prices/status, upload cover images, panoramas and gallery photos from here.</p>
                            </div>
                            <div class="sa-management-actions">
                                <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#saAddRoomModal"><i class="fas fa-square-plus me-1"></i> Add Room</button>
                                <button type="button" class="btn btn-outline-primary" data-bs-toggle="modal" data-bs-target="#saAddPropertyModal"><i class="fas fa-hotel me-1"></i> Add Property</button>
                                <a href="{{ route('super_admin.hotels.index', array_merge($routeParams ?? [], ['panel' => 'room_types'] + ($selectedCompanyId ? ['company_id' => $selectedCompanyId] : []))) }}" class="btn btn-outline-secondary"><i class="fas fa-tags me-1"></i> Room Types & Prices</a>
                            </div>
                        </div>
                    </div>

                    <div class="sa-room-wall">
                        @forelse($managedRooms as $room)
                            @php
                                $state=(string)($room->operational_status ?? 'available');
                                $roomNo = $room->room_number ?? ('#'.$room->id);
                                $cardImage = $room->room_image ?: $room->panorama_image;
                                $panoramaImage = $room->panorama_image ?: $room->room_image;
                                $cardUrl = $cardImage ? asset('storage/'.$cardImage) : null;
                                $previewUrl = $panoramaImage ? asset('storage/'.$panoramaImage) : null;
                                $modalId = 'saRoomPreview'.$room->id;
                                $editModalId = 'saRoomEdit'.$room->id;
                                $mediaModalId = 'saRoomMedia'.$room->id;
                                $lockModalId = 'saRoomLock'.$room->id;
                                $roomRate = $room->base_rate_override ?? $room->type?->base_rate;
                                $roomImages = $room->relationLoaded('images') ? $room->images : collect();
                                $roomProperties = $managedProperties->where('company_id', $room->company_id);
                                $roomWriteUp = trim((string) ($room->notes ?? ''));
                                $activeBlock = collect($roomManagement['activeBlocks'] ?? [])->get($room->id);
                            @endphp
                            <article class="sa-room-manager-card">
                                <div class="sa-room-manager-media {{ $cardUrl ? '' : 'empty' }}">
                                    @if($cardUrl)
                                        <img src="{{ $cardUrl }}" alt="Room {{ $roomNo }} preview" loading="lazy" decoding="async">
                                    @else
                                        <div class="text-center"><i class="fas fa-image fa-2x mb-2"></i><div>No image uploaded</div></div>
                                    @endif
                                </div>
                                <div class="sa-room-manager-body">
                                    <div class="d-flex justify-content-between gap-2">
                                        <span class="badge {{ $statusBadge($state) }}">{{ ucfirst(str_replace('_',' ', $state)) }}</span>
                                        <span class="badge bg-light text-dark">{{ ucfirst((string)($room->housekeeping_status ?? 'clean')) }}</span>
                                    </div>
                                    @if($activeBlock)
                                        <div class="sa-room-lock-note"><i class="fas fa-lock me-1"></i>{{ ucfirst(str_replace('_', ' ', $activeBlock->block_type)) }} until {{ optional($activeBlock->end_date)->format('M d, Y') }}</div>
                                    @endif
                                    <h5 class="mt-2">Room {{ $roomNo }}</h5>
                                    <div class="small sa-room-meta-line">{{ $room->property?->name ?? ('Property '.$room->property_id) }} - {{ $room->type?->name ?? 'No room type' }}</div>
                                    <p class="sa-room-writeup">{{ $roomWriteUp !== '' ? \Illuminate\Support\Str::limit($roomWriteUp, 135) : 'Add a room write-up, amenities, view details or guest-facing selling points.' }}</p>
                                    <div class="d-flex justify-content-between sa-room-rate-row"><span>Rate</span><span class="rate">{{ $money($roomRate ?? 0) }}</span></div>
                                    <div class="small sa-room-location-line">Company {{ $room->company_id }} - Floor {{ $room->floor ?: '-' }} - Wing {{ $room->wing ?: '-' }}</div>
                                    <div class="sa-room-manager-actions">
                                        <button type="button" class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#{{ $editModalId }}"><i class="fas fa-pen me-1"></i> Edit</button>
                                        <button type="button" class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#{{ $mediaModalId }}"><i class="fas fa-upload me-1"></i> Upload</button>
                                        <button type="button" class="btn btn-sm btn-outline-dark" data-bs-toggle="modal" data-bs-target="#{{ $modalId }}"><i class="fas fa-eye me-1"></i> Preview</button>
                                        <button type="button" class="btn btn-sm {{ $activeBlock ? 'btn-warning' : 'btn-outline-secondary' }}" data-bs-toggle="modal" data-bs-target="#{{ $lockModalId }}"><i class="fas fa-lock me-1"></i> {{ $activeBlock ? 'Locked' : 'Lock' }}</button>
                                    </div>
                                </div>
                            </article>

                            <div class="modal fade" id="{{ $lockModalId }}" tabindex="-1" aria-hidden="true">
                                <div class="modal-dialog modal-lg modal-dialog-centered">
                                    <div class="modal-content">
                                        <div class="modal-header"><h5 class="modal-title">Lock Room {{ $roomNo }}</h5><button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button></div>
                                        <div class="modal-body">
                                            @if($activeBlock)
                                                <div class="alert alert-warning d-flex justify-content-between gap-3 align-items-center flex-wrap">
                                                    <div><strong>Active lock:</strong> {{ ucfirst(str_replace('_', ' ', $activeBlock->block_type)) }} from {{ optional($activeBlock->start_date)->format('M d, Y') }} to {{ optional($activeBlock->end_date)->format('M d, Y') }}<br><span>{{ $activeBlock->reason }}</span></div>
                                                    <form method="POST" action="{{ route('super_admin.hotels.rooms.blocks.release', $activeBlock) }}">
                                                        @csrf
                                                        @method('DELETE')
                                                        <button class="btn btn-outline-danger">Release Lock</button>
                                                    </form>
                                                </div>
                                            @endif
                                            <form method="POST" action="{{ route('super_admin.hotels.rooms.blocks.store', $room) }}" id="saRoomLockForm{{ $room->id }}">
                                                @csrf
                                                <div class="sa-form-grid">
                                                    <div><label class="form-label">Lock Reason</label><select name="block_type" class="form-select" required><option value="maintenance">Maintenance</option><option value="out_of_order">Out of order</option><option value="housekeeping_hold">Housekeeping hold</option><option value="room_service_hold">Room service hold</option><option value="vip_hold">VIP hold</option><option value="admin_hold">Admin hold</option></select></div>
                                                    <div><label class="form-label">Start Date</label><input type="date" name="start_date" class="form-control" value="{{ now()->toDateString() }}" required></div>
                                                    <div><label class="form-label">End Date</label><input type="date" name="end_date" class="form-control" value="{{ now()->addDay()->toDateString() }}" required></div>
                                                    <div><label class="form-label">Room</label><input class="form-control" value="{{ $room->property?->name ?? ('Property '.$room->property_id) }} - Room {{ $roomNo }}" disabled></div>
                                                    <div class="full"><label class="form-label">Internal Reason</label><input name="reason" class="form-control" placeholder="Example: AC repair, deep cleaning, VIP preparation, room service incident"></div>
                                                </div>
                                            </form>
                                        </div>
                                        <div class="modal-footer"><button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Close</button><button form="saRoomLockForm{{ $room->id }}" class="btn btn-warning">Save Room Lock</button></div>
                                    </div>
                                </div>
                            </div>

                            <div class="modal fade" id="{{ $editModalId }}" tabindex="-1" aria-hidden="true">
                                <div class="modal-dialog modal-lg modal-dialog-centered">
                                    <form method="POST" action="{{ route('super_admin.hotels.rooms.update', $room) }}" enctype="multipart/form-data" class="modal-content">
                                        @csrf
                                        @method('PUT')
                                        <div class="modal-header"><h5 class="modal-title">Edit Room {{ $roomNo }}</h5><button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button></div>
                                        <div class="modal-body">
                                            <div class="sa-form-grid">
                                                <input type="hidden" name="company_id" value="{{ $room->company_id }}">
                                                <div><label class="form-label">Property</label><select name="property_id" class="form-select" required>@forelse($roomProperties as $property)<option value="{{ $property->id }}" @selected((int)$room->property_id === (int)$property->id)>{{ $property->name }}</option>@empty<option value="{{ $room->property_id }}">{{ $room->property?->name ?? ('Property '.$room->property_id) }}</option>@endforelse</select></div>
                                                <div><label class="form-label">Room Number</label><input name="room_number" class="form-control" value="{{ $room->room_number }}" required></div>
                                                <div><label class="form-label">Room Type / Rate Class</label><select name="room_type_id" class="form-select"><option value="">No type</option>@foreach($managedRoomTypes->where('company_id', $room->company_id) as $type)<option value="{{ $type->id }}" @selected((int)$room->room_type_id === (int)$type->id)>{{ $type->name }} - {{ $money($type->base_rate ?? 0) }}</option>@endforeach</select></div>
                                                <div><label class="form-label">Update Type Base Price</label><input name="new_room_type_base_rate" type="number" step="0.01" class="form-control" value="{{ $room->type?->base_rate }}"></div>
                                                <div><label class="form-label">Floor</label><input name="floor" class="form-control" value="{{ $room->floor }}"></div>
                                                <div><label class="form-label">Wing</label><input name="wing" class="form-control" value="{{ $room->wing }}"></div>
                                                <div><label class="form-label">Room Rate Override</label><input name="base_rate_override" type="number" step="0.01" class="form-control" value="{{ $room->base_rate_override }}"></div>
                                                <div><label class="form-label">Operational Status</label><select name="operational_status" class="form-select">@foreach(['available','occupied','reserved','maintenance','out_of_order'] as $option)<option value="{{ $option }}" @selected($state === $option)>{{ ucfirst(str_replace('_',' ', $option)) }}</option>@endforeach</select></div>
                                                <div><label class="form-label">Housekeeping</label><select name="housekeeping_status" class="form-select">@foreach(['clean','dirty','inspection','cleaning'] as $option)<option value="{{ $option }}" @selected((string)$room->housekeeping_status === $option)>{{ ucfirst($option) }}</option>@endforeach</select></div>
                                                <div><label class="form-label">Active</label><select name="is_active" class="form-select"><option value="1" @selected($room->is_active)>Active</option><option value="0" @selected(!$room->is_active)>Inactive</option></select></div>
                                                <div><label class="form-label">Cover Image</label><input type="file" name="room_image" class="form-control" accept="image/*"></div>
                                                <div><label class="form-label">Panorama Image</label><input type="file" name="panorama_image" class="form-control" accept="image/*"></div>
                                                <div class="full"><label class="form-label">Gallery Photos</label><input type="file" name="gallery_images[]" class="form-control" accept="image/*" multiple></div>
                                                <div class="full"><label class="form-label">Room Information / Notes</label><textarea name="notes" class="form-control" rows="3">{{ $room->notes }}</textarea></div>
                                            </div>
                                        </div>
                                        <div class="modal-footer"><button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Close</button><button class="btn btn-primary">Save Room</button></div>
                                    </form>
                                </div>
                            </div>

                            <div class="modal fade" id="{{ $mediaModalId }}" tabindex="-1" aria-hidden="true">
                                <div class="modal-dialog modal-lg modal-dialog-centered">
                                    <form method="POST" action="{{ route('super_admin.hotels.rooms.images.store', $room) }}" enctype="multipart/form-data" class="modal-content">
                                        @csrf
                                        <div class="modal-header"><h5 class="modal-title">Upload Media For Room {{ $roomNo }}</h5><button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button></div>
                                        <div class="modal-body">
                                            <div class="sa-form-grid mb-3">
                                                <div><label class="form-label">Cover Image</label><input type="file" name="room_image" class="form-control" accept="image/*"></div>
                                                <div><label class="form-label">Panorama Image</label><input type="file" name="panorama_image" class="form-control" accept="image/*"></div>
                                                <div class="full"><label class="form-label">Gallery Photos</label><input type="file" name="gallery_images[]" class="form-control" accept="image/*" multiple></div>
                                            </div>
                                            <div class="sa-room-gallery-strip">
                                                @forelse($roomImages as $image)
                                                    <article>
                                                        <img src="{{ asset('storage/'.$image->path) }}" alt="Room {{ $roomNo }} gallery image">
                                                        <div class="p-1"><button type="submit" form="saDeleteRoomImage{{ $image->id }}" class="btn btn-sm btn-outline-danger w-100">Delete</button></div>
                                                    </article>
                                                @empty
                                                    <div class="sa-empty">No gallery images yet. Upload cover, panorama or gallery photos above.</div>
                                                @endforelse
                                            </div>
                                        </div>
                                        <div class="modal-footer"><button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Close</button><button class="btn btn-primary">Upload Media</button></div>
                                    </form>
                                </div>
                            </div>
                            @foreach($roomImages as $image)
                                <form id="saDeleteRoomImage{{ $image->id }}" method="POST" action="{{ route('super_admin.hotels.rooms.images.destroy', [$room, $image]) }}" onsubmit="return confirm('Delete this room image?')">
                                    @csrf
                                    @method('DELETE')
                                </form>
                            @endforeach

                            <div class="modal fade sa-room-preview-modal hotel-preview-modal sa-panorama-fullscreen" id="{{ $modalId }}" tabindex="-1" aria-hidden="true">
                                <div class="modal-dialog modal-fullscreen">
                                    <div class="modal-content">
                                        <div class="modal-header hotel-preview-header">
                                            <div>
                                                <small class="hotel-preview-eyebrow">Customer room preview</small>
                                                <h5 class="modal-title">Room {{ $roomNo }}</h5>
                                                <span>{{ $room->property?->name ?? ('Property '.$room->property_id) }} - Company {{ $room->company_id }} - {{ ucfirst(str_replace('_',' ', $state)) }}</span>
                                            </div>
                                            <div class="d-flex gap-2 align-items-center">
                                                @if($previewUrl)
                                                    <a href="{{ $previewUrl }}" target="_blank" rel="noopener" class="btn btn-sm btn-outline-light">Open original</a>
                                                @endif
                                                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                                            </div>
                                        </div>
                                        <div class="modal-body">
                                            <div class="hotel-preview-viewer {{ $previewUrl ? 'has-image' : 'is-empty' }}" @if($previewUrl) style="--hotel-preview-image:url('{{ $previewUrl }}')" @endif>
                                                @if($previewUrl)
                                                    <div class="hotel-preview-media">
                                                        <div class="hotel-panorama-stage">
                                                            <span class="hotel-panorama-badge"><i class="fas fa-vr-cardboard"></i> Panorama tour</span>
                                                            <img class="hotel-panorama-image" src="{{ $previewUrl }}" alt="Room {{ $roomNo }} panorama preview" loading="eager" decoding="async">
                                                        </div>
                                                    </div>
                                                @else
                                                    <div class="sa-room-preview-empty">
                                                        <i class="fas fa-bed fa-3x mb-3"></i>
                                                        <h4>No room image uploaded yet</h4>
                                                        <p>Upload a room photo or panorama from the tenant hotel room form so customers can inspect the room before arrival.</p>
                                                    </div>
                                                @endif
                                                <div class="hotel-preview-controls">
                                                    <div>
                                                        <strong>Room {{ $roomNo }}</strong>
                                                        <span>{{ $room->notes ?: 'Panorama preview for customer-facing room inspection.' }}</span>
                                                    </div>
                                                    <div class="hotel-preview-status">
                                                        <span>{{ ucfirst(str_replace('_',' ', $state)) }}</span>
                                                        <i class="fas fa-circle-play"></i>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        @empty
                            <div class="sa-empty"><strong>No hotel rooms found yet.</strong><br>Click Add Room to create the room number, price, status and images for the selected hotel tenant.</div>
                        @endforelse
                    </div>
                </section>
            </div>
            <div class="modal fade" id="saAddRoomModal" tabindex="-1" aria-hidden="true">
                <div class="modal-dialog modal-lg modal-dialog-centered">
                    <form method="POST" action="{{ route('super_admin.hotels.rooms.store') }}" enctype="multipart/form-data" class="modal-content">
                        @csrf
                        <div class="modal-header"><h5 class="modal-title">Add Hotel Room</h5><button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button></div>
                        <div class="modal-body">
                            @if($managedProperties->isEmpty())
                                <div class="alert alert-warning">No hotel property is available yet. Create a property first, then add rooms under that property.</div>
                            @endif
                            <div class="sa-form-grid">
                                <div><label class="form-label">Hotel Tenant</label><select name="company_id" class="form-select" required><option value="">Select tenant</option>@foreach($managedCompanies as $company)<option value="{{ $company->id }}" @selected((int)$selectedCompanyId === (int)$company->id)>{{ $company->name }}</option>@endforeach</select></div>
                                <div><label class="form-label">Property</label><select name="property_id" class="form-select" required><option value="">Select property</option>@foreach($managedProperties as $property)<option value="{{ $property->id }}">{{ $property->name }} - Company {{ $property->company_id }}</option>@endforeach</select></div>
                                <div><label class="form-label">Room Number</label><input name="room_number" class="form-control" required placeholder="101"></div>
                                <div><label class="form-label">Existing Room Type</label><select name="room_type_id" class="form-select"><option value="">Create/use no type</option>@foreach($managedRoomTypes as $type)<option value="{{ $type->id }}">{{ $type->name }} - {{ $money($type->base_rate ?? 0) }} - Company {{ $type->company_id }}</option>@endforeach</select></div>
                                <div><label class="form-label">New Room Type Name</label><input name="new_room_type_name" class="form-control" placeholder="Deluxe King"></div>
                                <div><label class="form-label">Type Base Price</label><input name="new_room_type_base_rate" type="number" step="0.01" class="form-control" placeholder="25000"></div>
                                <div><label class="form-label">Floor</label><input name="floor" class="form-control" placeholder="2"></div>
                                <div><label class="form-label">Wing</label><input name="wing" class="form-control" placeholder="East"></div>
                                <div><label class="form-label">Room Rate Override</label><input name="base_rate_override" type="number" step="0.01" class="form-control" placeholder="Optional room-specific price"></div>
                                <div><label class="form-label">Operational Status</label><select name="operational_status" class="form-select">@foreach(['available','occupied','reserved','maintenance','out_of_order'] as $option)<option value="{{ $option }}">{{ ucfirst(str_replace('_',' ', $option)) }}</option>@endforeach</select></div>
                                <div><label class="form-label">Housekeeping</label><select name="housekeeping_status" class="form-select">@foreach(['clean','dirty','inspection','cleaning'] as $option)<option value="{{ $option }}">{{ ucfirst($option) }}</option>@endforeach</select></div>
                                <div><label class="form-label">Active</label><select name="is_active" class="form-select"><option value="1">Active</option><option value="0">Inactive</option></select></div>
                                <div><label class="form-label">Cover Image</label><input type="file" name="room_image" class="form-control" accept="image/*"></div>
                                <div><label class="form-label">Panorama Image</label><input type="file" name="panorama_image" class="form-control" accept="image/*"></div>
                                <div class="full"><label class="form-label">Gallery Photos</label><input type="file" name="gallery_images[]" class="form-control" accept="image/*" multiple></div>
                                <div class="full"><label class="form-label">Room Information / Notes</label><textarea name="notes" class="form-control" rows="3" placeholder="Amenities, bed setup, view, smoking policy, accessibility, minibar, cancellation note"></textarea></div>
                            </div>
                        </div>
                        <div class="modal-footer"><button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Close</button><button class="btn btn-primary">Create Room</button></div>
                    </form>
                </div>
            </div>
            <div class="modal fade" id="saAddPropertyModal" tabindex="-1" aria-hidden="true">
                <div class="modal-dialog modal-lg modal-dialog-centered">
                    <form method="POST" action="{{ route('super_admin.hotels.properties.store') }}" class="modal-content">
                        @csrf
                        <div class="modal-header"><h5 class="modal-title">Add Hotel Property</h5><button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button></div>
                        <div class="modal-body">
                            <div class="sa-form-grid">
                                <div><label class="form-label">Hotel Tenant</label><select name="company_id" class="form-select" required><option value="">Select tenant</option>@foreach($managedCompanies as $company)<option value="{{ $company->id }}" @selected((int)$selectedCompanyId === (int)$company->id)>{{ $company->name }}</option>@endforeach</select></div>
                                <div><label class="form-label">Property Name</label><input name="name" class="form-control" required placeholder="SmartPro Hotel"></div>
                                <div><label class="form-label">Code</label><input name="code" class="form-control" placeholder="SPH"></div>
                                <div><label class="form-label">Phone</label><input name="phone" class="form-control" placeholder="+234..."></div>
                                <div><label class="form-label">Email</label><input name="email" type="email" class="form-control" placeholder="hotel@example.com"></div>
                                <div><label class="form-label">City</label><input name="city" class="form-control" placeholder="Lagos"></div>
                                <div><label class="form-label">State</label><input name="state" class="form-control" placeholder="Lagos"></div>
                                <div><label class="form-label">Country</label><input name="country" class="form-control" placeholder="Nigeria"></div>
                                <div class="full"><label class="form-label">Address</label><input name="address" class="form-control" placeholder="Street address"></div>
                            </div>
                        </div>
                        <div class="modal-footer"><button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Close</button><button class="btn btn-primary">Create Property</button></div>
                    </form>
                </div>
            </div>
        @elseif($panel === 'room_types')
            @php
                $typeCompanies = collect($roomManagement['companies'] ?? []);
                $typeProperties = collect($roomManagement['properties'] ?? []);
            @endphp
            <section class="sa-dash-panel">
                <div class="sa-section-head">
                    <div><small class="text-warning fw-semibold">ROOM CATALOGUE</small><h4>Room Types & Rate Classes</h4><p>Capacity, base rates and tenant room-type setup.</p></div>
                    <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#saAddRoomTypeModal"><i class="fas fa-tags me-1"></i> Add Room Type</button>
                </div>
                <div class="sa-mini-kpis">
                    <div class="sa-mini-kpi"><span>Rate Classes</span><strong>{{ $panelRows->count() }}</strong><small>Visible room types</small></div>
                    <div class="sa-mini-kpi"><span>Lowest Rate</span><strong>{{ $money($panelRows->min(fn($row) => (float)($rowArray($row)['base_rate'] ?? 0)) ?? 0) }}</strong><small>Current page</small></div>
                    <div class="sa-mini-kpi"><span>Highest Rate</span><strong>{{ $money($panelRows->max(fn($row) => (float)($rowArray($row)['base_rate'] ?? 0)) ?? 0) }}</strong><small>Current page</small></div>
                    <div class="sa-mini-kpi"><span>Rooms</span><strong>{{ $totalRooms }}</strong><small>Attached inventory</small></div>
                </div>
                <div class="sa-directory-grid">
                    @forelse($panelRows as $row)
                        @php $r=$rowArray($row); $editTypeModal = 'saEditRoomType'.($r['id'] ?? ''); @endphp
                        <article class="sa-directory-card">
                            <span class="eyebrow">Room Type</span>
                            <h5>{{ $r['name'] ?? ('Type #'.($r['id'] ?? '-')) }}</h5>
                            <p class="text-muted">Company {{ $r['company_id'] ?? '-' }} - Property {{ $r['property_id'] ?? '-' }}</p>
                            <div class="d-flex justify-content-between"><span>Occupancy</span><strong>{{ $r['max_occupancy'] ?? '-' }}</strong></div>
                            <div class="d-flex justify-content-between"><span>Base Rate</span><strong>{{ $money($r['base_rate'] ?? 0) }}</strong></div>
                            <button type="button" class="btn btn-sm btn-outline-primary w-100 mt-3" data-bs-toggle="modal" data-bs-target="#{{ $editTypeModal }}">Edit Type & Price</button>
                        </article>
                        <div class="modal fade" id="{{ $editTypeModal }}" tabindex="-1" aria-hidden="true">
                            <div class="modal-dialog modal-lg modal-dialog-centered">
                                <form method="POST" action="{{ route('super_admin.hotels.room_types.update', $r['id']) }}" class="modal-content">
                                    @csrf
                                    @method('PUT')
                                    <div class="modal-header"><h5 class="modal-title">Edit {{ $r['name'] ?? 'Room Type' }}</h5><button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button></div>
                                    <div class="modal-body"><div class="sa-form-grid">
                                        <div><label class="form-label">Name</label><input name="name" class="form-control" value="{{ $r['name'] ?? '' }}" required></div>
                                        <div><label class="form-label">Code</label><input name="code" class="form-control" value="{{ $r['code'] ?? '' }}"></div>
                                        <div><label class="form-label">Base Rate</label><input name="base_rate" type="number" step="0.01" class="form-control" value="{{ $r['base_rate'] ?? 0 }}" required></div>
                                        <div><label class="form-label">Max Occupancy</label><input name="max_occupancy" type="number" class="form-control" value="{{ $r['max_occupancy'] ?? 2 }}"></div>
                                        <div><label class="form-label">Adults</label><input name="max_adults" type="number" class="form-control" value="{{ $r['max_adults'] ?? 2 }}"></div>
                                        <div><label class="form-label">Children</label><input name="max_children" type="number" class="form-control" value="{{ $r['max_children'] ?? 0 }}"></div>
                                        <div><label class="form-label">Active</label><select name="is_active" class="form-select"><option value="1" @selected((bool)($r['is_active'] ?? true))>Active</option><option value="0" @selected(!(bool)($r['is_active'] ?? true))>Inactive</option></select></div>
                                        <div class="full"><label class="form-label">Description</label><textarea name="description" class="form-control" rows="3">{{ $r['description'] ?? '' }}</textarea></div>
                                    </div></div>
                                    <div class="modal-footer"><button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Close</button><button class="btn btn-primary">Save Room Type</button></div>
                                </form>
                            </div>
                        </div>
                    @empty
                        <div class="sa-empty"><strong>No room types found yet.</strong><br>Use Add Room Type to set prices, bed setup and capacity before adding rooms.</div>
                    @endforelse
                </div>
            </section>
            <div class="modal fade" id="saAddRoomTypeModal" tabindex="-1" aria-hidden="true">
                <div class="modal-dialog modal-lg modal-dialog-centered">
                    <form method="POST" action="{{ route('super_admin.hotels.room_types.store') }}" class="modal-content">
                        @csrf
                        <div class="modal-header"><h5 class="modal-title">Add Room Type</h5><button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button></div>
                        <div class="modal-body"><div class="sa-form-grid">
                            <div><label class="form-label">Hotel Tenant</label><select name="company_id" class="form-select" required><option value="">Select tenant</option>@foreach($typeCompanies as $company)<option value="{{ $company->id }}" @selected((int)$selectedCompanyId === (int)$company->id)>{{ $company->name }}</option>@endforeach</select></div>
                            <div><label class="form-label">Property</label><select name="property_id" class="form-select"><option value="">Auto assign first property</option>@foreach($typeProperties as $property)<option value="{{ $property->id }}">{{ $property->name }} - Company {{ $property->company_id }}</option>@endforeach</select></div>
                            <div><label class="form-label">Name</label><input name="name" class="form-control" required placeholder="Deluxe Suite"></div>
                            <div><label class="form-label">Code</label><input name="code" class="form-control" placeholder="DLX"></div>
                            <div><label class="form-label">Base Rate</label><input name="base_rate" type="number" step="0.01" class="form-control" required placeholder="75000"></div>
                            <div><label class="form-label">Max Occupancy</label><input name="max_occupancy" type="number" class="form-control" value="2"></div>
                            <div><label class="form-label">Adults</label><input name="max_adults" type="number" class="form-control" value="2"></div>
                            <div><label class="form-label">Children</label><input name="max_children" type="number" class="form-control" value="0"></div>
                            <div class="full"><label class="form-label">Description</label><textarea name="description" class="form-control" rows="3" placeholder="Bed setup, amenities, view, cancellation or sale notes"></textarea></div>
                        </div></div>
                        <div class="modal-footer"><button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Close</button><button class="btn btn-primary">Create Room Type</button></div>
                    </form>
                </div>
            </div>
        @elseif(in_array($panel, ['room_calendar','reservations'], true))
            @php
                $reservationTotals = [
                    'confirmed' => $panelRows->filter(fn($row) => in_array((string)($rowArray($row)['status'] ?? ''), ['confirmed', 'reserved'], true))->count(),
                    'checked_in' => $panelRows->filter(fn($row) => (string)($rowArray($row)['status'] ?? '') === 'checked_in')->count(),
                    'cancelled' => $panelRows->filter(fn($row) => (string)($rowArray($row)['status'] ?? '') === 'cancelled')->count(),
                    'deposit' => $panelRows->sum(fn($row) => (float)($rowArray($row)['deposit_received'] ?? 0)),
                ];
            @endphp
            <section class="sa-dash-panel">
                <div class="sa-section-head"><div><small class="text-warning fw-semibold">{{ $panel === 'room_calendar' ? 'ROOM CALENDAR' : 'RESERVATION REGISTER' }}</small><h4>{{ $panel === 'room_calendar' ? 'Reservation Timeline' : 'Bookings Operations Board' }}</h4><p>Arrival, departure, room assignment, booking status and deposits.</p></div><span class="badge bg-light text-dark">Reservation monitor</span></div>
                <div class="sa-mini-kpis">
                    <div class="sa-mini-kpi"><span>Bookings</span><strong>{{ $panelRows->count() }}</strong><small>Visible rows</small></div>
                    <div class="sa-mini-kpi"><span>Confirmed</span><strong>{{ $reservationTotals['confirmed'] }}</strong><small>Reserved pipeline</small></div>
                    <div class="sa-mini-kpi"><span>Checked In</span><strong>{{ $reservationTotals['checked_in'] }}</strong><small>Active arrivals</small></div>
                    <div class="sa-mini-kpi"><span>Deposits</span><strong>{{ $money($reservationTotals['deposit']) }}</strong><small>Collected</small></div>
                </div>
                <div class="sa-ops-list mb-3">
                    <div class="sa-ops-row"><div><strong>Room assignment</strong><div class="small text-muted">Open Room Board to update room availability and resolve assignment pressure.</div></div><a class="btn btn-sm btn-outline-primary" href="{{ route('super_admin.hotels.index', array_merge($routeParams ?? [], ['panel' => 'rooms'] + ($selectedCompanyId ? ['company_id' => $selectedCompanyId] : []))) }}">Room Board</a></div>
                    <div class="sa-ops-row"><div><strong>Deposit follow-up</strong><div class="small text-muted">Review cashier rows and folios linked to reservation payments.</div></div><a class="btn btn-sm btn-outline-secondary" href="{{ route('super_admin.hotels.index', array_merge($routeParams ?? [], ['panel' => 'deposits'] + ($selectedCompanyId ? ['company_id' => $selectedCompanyId] : []))) }}">Deposits</a></div>
                </div>
                @if($panel === 'room_calendar')
                    <div class="sa-calendar-pulse">
                        @foreach($roomCalendarPulse ?? [] as $day)
                            <article class="sa-calendar-day {{ ($day['date'] ?? '') === now()->toDateString() ? 'today' : '' }}">
                                <strong>{{ $day['label'] ?? '-' }}</strong>
                                <div>Arrivals <span>{{ $day['arrivals'] ?? 0 }}</span></div>
                                <div>Departures <span>{{ $day['departures'] ?? 0 }}</span></div>
                                <div>In stay <span>{{ $day['stays'] ?? 0 }}</span></div>
                                <div>Locked <span>{{ $day['locks'] ?? 0 }}</span></div>
                            </article>
                        @endforeach
                    </div>
                @endif
                <div class="sa-timeline"><table class="table table-bordered align-middle"><thead><tr><th>Reservation</th><th>Guest</th><th>Room</th><th>Arrival</th><th>Departure</th><th>Nights</th><th>Status</th><th>Deposit</th></tr></thead><tbody>@forelse($panelRows as $row)@php $r=$rowArray($row); @endphp<tr><td><span class="sa-pill-event {{ ($r['status'] ?? '') === 'confirmed' ? 'green' : 'gold' }}">{{ $r['reservation_number'] ?? ('#'.($r['id'] ?? '-')) }}</span></td><td>Guest #{{ $r['customer_id'] ?? '-' }}</td><td>{{ $r['room_id'] ?? 'Unassigned' }}</td><td>{{ $r['arrival_date'] ?? '-' }}</td><td>{{ $r['departure_date'] ?? '-' }}</td><td>{{ $r['nights'] ?? '-' }}</td><td><span class="badge {{ $statusBadge($r['status'] ?? 'reserved') }}">{{ ucfirst(str_replace('_',' ', (string)($r['status'] ?? 'reserved'))) }}</span></td><td>{{ $money($r['deposit_received'] ?? 0) }}</td></tr>@empty<tr><td colspan="8"><div class="sa-empty"><strong>No reservations found yet.</strong><br>Use tenant Hotel > Availability or New Reservation to create bookings. This register will then show guest, room, arrival, departure, status and deposit.</div></td></tr>@endforelse</tbody></table></div>
            </section>
        @elseif($panel === 'availability')
            <section class="sa-dash-panel">
                <div class="sa-section-head">
                    <div>
                        <small class="text-warning fw-semibold">AVAILABILITY SEARCH</small>
                        <h4>Rooms Available For Sale</h4>
                        <p>Live saleable rooms for the selected hotel tenant scope, excluding maintenance and locked rooms.</p>
                    </div>
                    <span class="btn btn-success disabled">{{ $availableRooms }} available</span>
                </div>
                <div class="sa-directory-grid">
                    @forelse($panelRows as $row)
                        @php
                            $room = $row;
                            $roomRate = $room->base_rate_override ?? $room->type?->base_rate;
                            $roomBlock = collect($roomManagement['activeBlocks'] ?? [])->get($room->id);
                        @endphp
                        <article class="sa-directory-card">
                            <span class="eyebrow">Available Room</span>
                            <h5 class="mt-2 mb-2">Room {{ $room->room_number }}</h5>
                            <p class="text-muted">{{ $room->property?->name ?? ('Property '.$room->property_id) }} - {{ $room->type?->name ?? 'No room type' }}</p>
                            <div class="d-flex justify-content-between"><span>Rate</span><strong>{{ $money($roomRate ?? 0) }}</strong></div>
                            <div class="d-flex justify-content-between"><span>Housekeeping</span><strong>{{ ucfirst((string)($room->housekeeping_status ?? 'clean')) }}</strong></div>
                            <div class="d-flex gap-2 mt-3 flex-wrap">
                                <a class="btn btn-sm btn-outline-primary" href="{{ route('super_admin.hotels.index', ['panel' => 'room_gallery', 'company_id' => $room->company_id]) }}">Open Gallery</a>
                                <button type="button" class="btn btn-sm btn-warning" data-bs-toggle="modal" data-bs-target="#saAvailLock{{ $room->id }}">Lock Room</button>
                            </div>
                        </article>
                        <div class="modal fade" id="saAvailLock{{ $room->id }}" tabindex="-1" aria-hidden="true">
                            <div class="modal-dialog modal-lg modal-dialog-centered">
                                <form method="POST" action="{{ route('super_admin.hotels.rooms.blocks.store', $room) }}" class="modal-content">
                                    @csrf
                                    <div class="modal-header"><h5 class="modal-title">Lock Room {{ $room->room_number }}</h5><button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button></div>
                                    <div class="modal-body">
                                        @if($roomBlock)<div class="alert alert-warning">This room already has an active lock: {{ $roomBlock->reason }}</div>@endif
                                        <div class="sa-form-grid">
                                            <div><label class="form-label">Lock Reason</label><select name="block_type" class="form-select" required><option value="room_service_hold">Room service hold</option><option value="maintenance">Maintenance</option><option value="out_of_order">Out of order</option><option value="housekeeping_hold">Housekeeping hold</option><option value="vip_hold">VIP hold</option><option value="admin_hold">Admin hold</option></select></div>
                                            <div><label class="form-label">Start Date</label><input type="date" name="start_date" class="form-control" value="{{ now()->toDateString() }}" required></div>
                                            <div><label class="form-label">End Date</label><input type="date" name="end_date" class="form-control" value="{{ now()->addDay()->toDateString() }}" required></div>
                                            <div class="full"><label class="form-label">Internal Reason</label><input name="reason" class="form-control" placeholder="Why this room should be removed from sale"></div>
                                        </div>
                                    </div>
                                    <div class="modal-footer"><button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Close</button><button class="btn btn-warning">Save Room Lock</button></div>
                                </form>
                            </div>
                        </div>
                    @empty
                        <div class="sa-empty"><strong>No available rooms found.</strong><br>Open Room Gallery to add rooms, update room status, upload images or release maintenance locks.</div>
                    @endforelse
                </div>
            </section>
        @elseif(in_array($panel, ['check_in','stays','checkout'], true))
            @php
                $deskTitle = $panel === 'check_in' ? 'Check-In Desk' : ($panel === 'checkout' ? 'Checkout Settlement Desk' : 'In-House Guest Desk');
                $deskAmount = $panelRows->sum(fn($row) => (float)($rowArray($row)['balance'] ?? $rowArray($row)['deposit_received'] ?? $rowArray($row)['total_amount'] ?? 0));
            @endphp
            <div class="sa-mini-kpis">
                <div class="sa-mini-kpi"><span>Queue Rows</span><strong>{{ $panelRows->count() }}</strong><small>{{ $deskTitle }}</small></div>
                <div class="sa-mini-kpi"><span>In-House</span><strong>{{ $currentInHouseGuests }}</strong><small>Checked-in stays</small></div>
                <div class="sa-mini-kpi"><span>Balance Watch</span><strong>{{ $money($deskAmount) }}</strong><small>Current page</small></div>
                <div class="sa-mini-kpi"><span>Dirty Rooms</span><strong>{{ $dirtyRooms }}</strong><small>Housekeeping dependency</small></div>
            </div>
            <div class="sa-desk">
                <section class="sa-dash-panel"><div class="sa-section-head"><div><small class="text-warning fw-semibold">FRONT OFFICE</small><h4>{{ $deskTitle }}</h4><p>Operational queue for arrivals, in-house guests and departures.</p></div><span class="btn btn-outline-primary disabled">{{ $panelRows->count() }} rows</span></div>@forelse($panelRows as $row)@php $r=$rowArray($row); @endphp<div class="sa-desk-card {{ in_array(($r['status'] ?? ''), ['cancelled','failed'], true) ? 'danger' : 'good' }}"><div class="d-flex justify-content-between gap-2"><div><strong>{{ $r['reservation_number'] ?? ('Stay #'.($r['id'] ?? '-')) }}</strong><div class="text-muted small">Guest #{{ $r['customer_id'] ?? '-' }} - Room {{ $r['room_id'] ?? 'Unassigned' }}</div></div><span class="badge {{ $statusBadge($r['status'] ?? 'open') }}">{{ ucfirst(str_replace('_',' ', (string)($r['status'] ?? 'open'))) }}</span></div><div class="row mt-2 small"><div class="col">Arrival<br><strong>{{ $r['arrival_date'] ?? $r['checkin_at'] ?? '-' }}</strong></div><div class="col">Departure<br><strong>{{ $r['departure_date'] ?? $r['expected_checkout_at'] ?? '-' }}</strong></div><div class="col">Deposit<br><strong>{{ $money($r['deposit_received'] ?? 0) }}</strong></div></div></div>@empty<div class="sa-empty">No records in this desk yet. Tenant front-office actions will populate here.</div>@endforelse</section>
                <aside class="sa-pms-sidebar"><small>QUEUE SUMMARY</small><h4>{{ $deskTitle }}</h4><p>Monitor arrivals, stays, checkout balances and room-readiness dependencies across tenants.</p><div class="metric"><strong>{{ $panelRows->count() }}</strong><br>Rows loaded</div><div class="metric"><strong>{{ $money($outstandingReceivables) }}</strong><br>Receivables watched</div><a href="{{ route('super_admin.hotels.index', array_merge($routeParams ?? [], ['panel' => 'housekeeping'] + ($selectedCompanyId ? ['company_id' => $selectedCompanyId] : []))) }}">Housekeeping readiness</a><a href="{{ route('super_admin.hotels.index', array_merge($routeParams ?? [], ['panel' => 'folios'] + ($selectedCompanyId ? ['company_id' => $selectedCompanyId] : []))) }}">Guest folios</a></aside>
            </div>
        @elseif($panel === 'guests')
            <section class="sa-dash-panel">
                <div class="sa-section-head"><div><small class="text-warning fw-semibold">GUEST CRM</small><h4>Guest Profiles</h4><p>Guest contact records linked to hotel reservations, stays and folios.</p></div><span class="btn btn-outline-primary disabled">{{ $panelRows->count() }} guests</span></div>
                <div class="sa-mini-kpis">
                    <div class="sa-mini-kpi"><span>Guests</span><strong>{{ $panelRows->count() }}</strong><small>Visible profiles</small></div>
                    <div class="sa-mini-kpi"><span>In-House</span><strong>{{ $currentInHouseGuests }}</strong><small>Active stays</small></div>
                    <div class="sa-mini-kpi"><span>Reservations</span><strong>{{ $todayReservations }}</strong><small>Today window</small></div>
                    <div class="sa-mini-kpi"><span>Receivables</span><strong>{{ $money($outstandingReceivables) }}</strong><small>Guest balances</small></div>
                </div>
                <div class="sa-directory-grid">@forelse($panelRows as $row)@php $r=$rowArray($row); $name=$r['customer_name'] ?? $r['name'] ?? 'Guest'; @endphp<div class="sa-profile-card"><div class="sa-big-avatar">{{ strtoupper(substr((string)$name,0,1)) }}</div><div><h5 class="mb-1">{{ $name }}</h5><div class="text-muted small">{{ $r['phone'] ?? 'No phone' }} - {{ $r['email'] ?? 'No email' }}</div><div class="d-flex gap-2 flex-wrap mt-2"><span class="badge bg-light text-dark">Guest #{{ $r['id'] ?? '-' }}</span><a class="btn btn-sm btn-outline-primary" href="{{ route('super_admin.hotels.index', array_merge($routeParams ?? [], ['panel' => 'folios'] + ($selectedCompanyId ? ['company_id' => $selectedCompanyId] : []))) }}">Folios</a><a class="btn btn-sm btn-outline-secondary" href="{{ route('super_admin.hotels.index', array_merge($routeParams ?? [], ['panel' => 'reservations'] + ($selectedCompanyId ? ['company_id' => $selectedCompanyId] : []))) }}">Bookings</a></div></div></div>@empty<div class="sa-empty">No guest profiles found yet. Hotel guest CRM will appear here after tenant bookings, walk-ins or stays are created.</div>@endforelse</div>
            </section>
        @elseif($panel === 'services' || str_starts_with($panel, 'service_'))
            @php
                $servicePanelKey = str_starts_with($panel, 'service_') ? str_replace('service_', '', $panel) : $selectedServiceCenter;
                $visibleServices = $servicePanelKey === 'all'
                    ? collect($serviceCenters)->except('all')
                    : collect($serviceCenters)->only($servicePanelKey);
                $serviceHeading = $servicePanelKey === 'all'
                    ? 'Service Centre Register'
                    : ($serviceCenters[$servicePanelKey]['label'] ?? $panelTitle);
                $serviceTotal = $panelRows->sum(fn($row) => (float) (($rowArray($row)['amount'] ?? $rowArray($row)['total_amount'] ?? 0)));
                $serviceLockCount = collect($roomManagement['activeBlocks'] ?? [])->filter(fn($block) => $servicePanelKey === 'room_service' ? $block->block_type === 'room_service_hold' : false)->count();
                $serviceFolios = collect($roomManagement['openFolios'] ?? []);
            @endphp
            <section class="sa-dash-panel">
                <div class="sa-section-head">
                    <div>
                        <small class="text-warning fw-semibold">SERVICE CENTRES</small>
                        <h4>{{ $serviceHeading }}</h4>
                        <p>Revenue-centre monitor for the selected hotel service only.</p>
                    </div>
                    <form method="GET" action="{{ route('super_admin.hotels.index') }}" class="sa-inline-select">
                        <input type="hidden" name="panel" value="services">
                        @if($selectedCompanyId)<input type="hidden" name="company_id" value="{{ $selectedCompanyId }}">@endif
                        <select name="service" class="form-select" onchange="this.form.submit()">
                            @foreach($serviceCenters as $serviceKey => $serviceMeta)
                                <option value="{{ $serviceKey }}" {{ $selectedServiceCenter === $serviceKey ? 'selected' : '' }}>{{ $serviceMeta['label'] }}</option>
                            @endforeach
                        </select>
                    </form>
                </div>
                <div class="sa-mini-kpis">
                    <div class="sa-mini-kpi"><span>Postings</span><strong>{{ $panelRows->count() }}</strong><small>{{ $serviceHeading }}</small></div>
                    <div class="sa-mini-kpi"><span>Service Revenue</span><strong>{{ $money($serviceTotal) }}</strong><small>Visible tenant charges</small></div>
                    <div class="sa-mini-kpi"><span>Room Holds</span><strong>{{ $serviceLockCount }}</strong><small>{{ $servicePanelKey === 'room_service' ? 'Room service holds' : 'Service watch' }}</small></div>
                    <div class="sa-mini-kpi"><span>Active Rooms</span><strong>{{ $totalRooms }}</strong><small>{{ $availableRooms }} available</small></div>
                </div>
                <div class="sa-dashboard-services mb-3">
                    @foreach($visibleServices as $serviceKey => $serviceMeta)
                        <div class="sa-dashboard-service active">
                            <div><small>{{ strtoupper(str_replace('_',' ', $serviceKey)) }}</small><br><strong>{{ $serviceMeta['label'] }}</strong></div>
                            <span class="badge bg-warning text-dark">{{ $panelRows->count() }} postings</span>
                        </div>
                    @endforeach
                </div>
                <div class="sa-directory-grid mb-3">
                    <article class="sa-directory-card feature"><span class="eyebrow">Operations</span><h5 class="mt-2">Live Service Monitor</h5><p>Track charges by tenant, amount and service date. Room Service can also lock rooms from the action strip above.</p></article>
                    <article class="sa-directory-card"><span class="eyebrow">Action</span><h5 class="mt-2">Post Service Sale</h5><p class="text-muted">Post restaurant, bar, spa, gym, ticketing, minibar, laundry, room-service or conference sales into an open guest folio and print receipt.</p><button type="button" class="btn btn-sm btn-primary mt-2" data-bs-toggle="modal" data-bs-target="#saServiceChargeModal"><i class="fas fa-cash-register me-1"></i> Post Sale</button></article>
                    <article class="sa-directory-card"><span class="eyebrow">Control</span><h5 class="mt-2">Exceptions</h5><p class="text-muted">Use room locks for spills, safety issues, room-service cleanup, VIP preparation or temporary sales blocks.</p><a class="btn btn-sm btn-outline-warning mt-2" href="{{ route('super_admin.hotels.index', array_merge($routeParams ?? [], ['panel' => 'maintenance'] + ($selectedCompanyId ? ['company_id' => $selectedCompanyId] : []))) }}">Open Locks</a></article>
                </div>
                <div class="sa-timeline"><table class="table table-sm align-middle"><thead><tr><th>Posting</th><th>Company</th><th>Service</th><th>Type</th><th>Amount</th><th>Date</th><th>Action</th></tr></thead><tbody>@forelse($panelRows as $row)@php $r=$rowArray($row); @endphp<tr><td>{{ $r['description'] ?? $r['folio_number'] ?? ('#'.($r['id'] ?? '-')) }}</td><td>{{ $r['company_id'] ?? '-' }}</td><td><span class="badge bg-warning text-dark">{{ $r['service_code'] ?? $r['department'] ?? strtoupper(str_replace('_', ' ', $servicePanelKey)) }}</span></td><td>{{ $r['type'] ?? $r['payment_method'] ?? '-' }}</td><td>{{ $money($r['amount'] ?? $r['total_amount'] ?? 0) }}</td><td>{{ $r['service_date'] ?? $r['created_at'] ?? $r['business_date'] ?? '-' }}</td><td>@if(!empty($r['id']) && !empty($r['folio_id']))<a class="btn btn-sm btn-outline-dark" target="_blank" rel="noopener" href="{{ route('super_admin.hotels.receipts.show', $r['id']) }}"><i class="fas fa-print me-1"></i> Receipt</a>@else<span class="text-muted">Report row</span>@endif</td></tr>@empty<tr><td colspan="7"><div class="sa-empty">No {{ strtolower($serviceHeading) }} postings found yet. Tenant charges for this service will appear here.</div></td></tr>@endforelse</tbody></table></div>
            </section>
            <div class="modal fade" id="saServiceChargeModal" tabindex="-1" aria-hidden="true">
                <div class="modal-dialog modal-lg modal-dialog-centered">
                    <form method="POST" action="{{ route('super_admin.hotels.service_charges.store') }}" class="modal-content" id="saServiceChargeForm">
                        @csrf
                        <input type="hidden" name="print_receipt" value="1">
                        <div class="modal-header"><h5 class="modal-title">Post Hotel Service Sale</h5><button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button></div>
                        <div class="modal-body">
                            @if($serviceFolios->isEmpty())
                                <div class="alert alert-warning mb-0">No open guest folios are available. Create/check in a guest first, then post service sales here.</div>
                            @else
                                <div class="sa-form-grid">
                                    <div class="full">
                                        <label class="form-label">Hotel Tenant</label>
                                        <div class="sa-tenant-lock">
                                            Tenant is selected automatically from the guest folio below.
                                            @if($selectedCompanyId)
                                                <input type="hidden" name="company_id" value="{{ $selectedCompanyId }}">
                                            @endif
                                        </div>
                                    </div>
                                    <div class="full">
                                        <label class="form-label">Guest / Room Folio</label>
                                        <div class="sa-choice-list">
                                            @foreach($serviceFolios as $folio)
                                                <label class="sa-choice">
                                                    <input type="radio" name="folio_id" value="{{ $folio->id }}" required @checked($loop->first)>
                                                    <span>{{ $folio->customer?->customer_name ?? $folio->customer?->name ?? 'Guest' }} - Room {{ $folio->stay?->room?->room_number ?? 'N/A' }}<small>{{ $folio->folio_number }} - Company {{ $folio->company_id }}</small></span>
                                                </label>
                                            @endforeach
                                        </div>
                                    </div>
                                    <div class="full">
                                        <label class="form-label">Service Center</label>
                                        <div class="sa-choice-grid">
                                            @foreach(collect($serviceCenters)->except('all') as $serviceKey => $serviceMeta)
                                                <label class="sa-choice">
                                                    <input type="radio" name="service_center" value="{{ $serviceKey }}" required @checked($servicePanelKey === $serviceKey || ($servicePanelKey === 'all' && $loop->first))>
                                                    <span>{{ $serviceMeta['label'] }}</span>
                                                </label>
                                            @endforeach
                                        </div>
                                    </div>
                                    <div><label class="form-label">Item / Ticket / Service</label><input name="description" class="form-control" placeholder="Dinner ticket, spa session, laundry order, minibar item" required></div>
                                    <div><label class="form-label">Quantity</label><input name="quantity" type="number" min="0.001" step="0.001" value="1" class="form-control"></div>
                                    <div><label class="form-label">Unit Price</label><input name="unit_price" type="number" min="0.01" step="0.01" class="form-control" required></div>
                                    <div><label class="form-label">Discount</label><input name="discount" type="number" min="0" step="0.01" value="0" class="form-control"></div>
                                    <div><label class="form-label">Tax</label><input name="tax" type="number" min="0" step="0.01" value="0" class="form-control"></div>
                                    <div class="full">
                                        <label class="form-label">Payment</label>
                                        <div class="sa-choice-grid">
                                            @foreach(['charge_to_room' => 'Charge to Room', 'cash' => 'Cash Paid', 'card' => 'Card / POS Paid', 'transfer' => 'Transfer Paid', 'other' => 'Other Paid'] as $paymentKey => $paymentLabel)
                                                <label class="sa-choice">
                                                    <input type="radio" name="payment_mode" value="{{ $paymentKey }}" required @checked($loop->first)>
                                                    <span>{{ $paymentLabel }}</span>
                                                </label>
                                            @endforeach
                                        </div>
                                    </div>
                                    <div><label class="form-label">Date</label><input name="service_date" type="date" value="{{ now()->toDateString() }}" class="form-control"></div>
                                    <div class="full"><label class="form-label">Internal Note</label><textarea name="note" class="form-control" rows="2" placeholder="Server, ticket batch, guest request, package note"></textarea></div>
                                </div>
                            @endif
                        </div>
                        <div class="modal-footer"><button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Close</button><button type="submit" class="btn btn-primary" form="saServiceChargeForm" @disabled($serviceFolios->isEmpty())><i class="fas fa-receipt me-1"></i> Post Sale & Print Receipt</button></div>
                    </form>
                </div>
            </div>
        @elseif(in_array($panel, ['folios','deposits'], true))
            @php
                $cashierTotal = $panelRows->sum(fn($row) => (float) (($rowArray($row)['balance'] ?? $rowArray($row)['amount'] ?? $rowArray($row)['deposit_received'] ?? $rowArray($row)['total_amount'] ?? 0)));
                $openCashierRows = $panelRows->filter(fn($row) => in_array((string)($rowArray($row)['status'] ?? 'open'), ['open', 'active', 'pending'], true))->count();
            @endphp
            <div class="sa-mini-kpis">
                <div class="sa-mini-kpi"><span>Rows Loaded</span><strong>{{ $panelRows->count() }}</strong><small>{{ $panelTitle }}</small></div>
                <div class="sa-mini-kpi"><span>Amount Watched</span><strong>{{ $money($cashierTotal) }}</strong><small>Current page total</small></div>
                <div class="sa-mini-kpi"><span>Open Records</span><strong>{{ $openCashierRows }}</strong><small>Pending cashier attention</small></div>
                <div class="sa-mini-kpi"><span>Receivables</span><strong>{{ $money($outstandingReceivables) }}</strong><small>Guest folio exposure</small></div>
            </div>
            <div class="sa-cashier-grid"><aside class="sa-pms-sidebar"><small>{{ strtoupper($panelTitle) }}</small><h4>Cashier Register</h4><p>Guest balances, deposits and folio exposure across hotel tenants.</p><div class="metric"><strong>{{ $money($outstandingReceivables) }}</strong><br>Outstanding receivables</div></aside><section class="sa-dash-panel"><div class="sa-section-head"><div><h4>Folio & Deposit Ledger</h4><p>Platform cashier activity by tenant, guest, stay and settlement status.</p></div><span class="badge bg-light text-dark">{{ $panelRows->count() }} loaded</span></div><div class="sa-timeline"><table class="table table-sm align-middle"><thead><tr><th>Record</th><th>Company</th><th>Guest/Stay</th><th>Status/Type</th><th>Amount</th><th>Date</th><th>Action</th></tr></thead><tbody>@forelse($panelRows as $row)@php $r=$rowArray($row); @endphp<tr><td><strong>{{ $r['folio_number'] ?? $r['reservation_number'] ?? ('#'.($r['id'] ?? '-')) }}</strong></td><td>{{ $r['company_id'] ?? '-' }}</td><td>{{ $r['customer_id'] ?? $r['stay_id'] ?? '-' }}</td><td><span class="badge {{ $statusBadge($r['status'] ?? $r['type'] ?? 'open') }}">{{ ucfirst(str_replace('_',' ', (string)($r['status'] ?? $r['type'] ?? 'record'))) }}</span></td><td><strong>{{ $money($r['balance'] ?? $r['amount'] ?? $r['deposit_received'] ?? $r['total_amount'] ?? 0) }}</strong></td><td>{{ $r['created_at'] ?? $r['service_date'] ?? $r['business_date'] ?? '-' }}</td><td>@if($panel === 'folios' && !empty($r['id']))<a class="btn btn-sm btn-outline-primary" href="{{ route('super_admin.hotels.index', array_merge($routeParams ?? [], ['panel' => 'services'] + ($selectedCompanyId ? ['company_id' => $selectedCompanyId] : []))) }}">Post Sale</a>@else<span class="text-muted">Monitor</span>@endif</td></tr>@empty<tr><td colspan="7"><div class="sa-empty">No {{ strtolower($panelTitle) }} found yet. Deposits, payments and folio charges will appear once tenant front-office users post them.</div></td></tr>@endforelse</tbody></table></div></section><aside class="sa-dash-panel"><h4>Cashier Actions</h4><p>Use the related monitors to trace the guest journey behind each cashier item.</p><div class="sa-payment-pad"><a href="{{ route('super_admin.hotels.index', array_merge($routeParams ?? [], ['panel' => 'reservations'] + ($selectedCompanyId ? ['company_id' => $selectedCompanyId] : []))) }}">Reservations</a><a href="{{ route('super_admin.hotels.index', array_merge($routeParams ?? [], ['panel' => 'stays'] + ($selectedCompanyId ? ['company_id' => $selectedCompanyId] : []))) }}">In-House</a><a href="{{ route('super_admin.hotels.index', array_merge($routeParams ?? [], ['panel' => 'service_room_service'] + ($selectedCompanyId ? ['company_id' => $selectedCompanyId] : []))) }}">Services</a><a href="{{ route('super_admin.hotels.index', array_merge($routeParams ?? [], ['panel' => 'reports'] + ($selectedCompanyId ? ['company_id' => $selectedCompanyId] : []))) }}">Reports</a></div></aside></div>
        @elseif($panel === 'maintenance')
            @php
                $activeRoomBlocks = collect($roomManagement['activeBlocks'] ?? []);
            @endphp
            <section class="sa-dash-panel">
                <div class="sa-section-head">
                    <div>
                        <small class="text-warning fw-semibold">ENGINEERING</small>
                        <h4>Maintenance & Room Locks</h4>
                        <p>Room issues, lock reasons, unavailable-room risk and internal service holds.</p>
                    </div>
                    <div class="d-flex gap-2 flex-wrap">
                        <span class="btn btn-outline-primary disabled">{{ $panelRows->count() }} tickets</span>
                        <span class="btn btn-warning disabled text-dark">{{ $activeRoomBlocks->count() }} active locks</span>
                    </div>
                </div>
                <div class="sa-directory-grid mb-3">
                    @forelse($activeRoomBlocks as $block)
                        @php $lockedRoom = $opsRooms->firstWhere('id', $block->room_id); @endphp
                        <article class="sa-directory-card">
                            <span class="eyebrow">Locked Room</span>
                            <h5 class="mt-2 mb-2">Room {{ $lockedRoom?->room_number ?? $block->room_id }}</h5>
                            <p class="text-muted mb-2">{{ ucfirst(str_replace('_', ' ', $block->block_type)) }} - {{ $block->reason ?: 'No reason entered' }}</p>
                            <div class="d-flex justify-content-between gap-2"><span>Until</span><strong>{{ optional($block->end_date)->format('M d, Y') }}</strong></div>
                            <form method="POST" action="{{ route('super_admin.hotels.rooms.blocks.release', $block) }}" class="mt-3">
                                @csrf
                                @method('DELETE')
                                <button class="btn btn-sm btn-outline-danger w-100">Release Lock</button>
                            </form>
                        </article>
                    @empty
                        <div class="sa-empty">No active room locks. Use Lock Room above to block a room for maintenance, room service, housekeeping or VIP preparation.</div>
                    @endforelse
                </div>
                @forelse($panelRows as $row)
                    @php $r=$rowArray($row); @endphp
                    <div class="sa-desk-card {{ in_array(($r['severity'] ?? $r['status'] ?? ''), ['high','critical','failed'], true) ? 'danger' : 'warn' }}">
                        <div class="d-flex justify-content-between">
                            <div><strong>{{ $r['ticket_no'] ?? ('Ticket #'.($r['id'] ?? '-')) }}</strong><div class="text-muted small">Room {{ $r['room_id'] ?? '-' }} - Company {{ $r['company_id'] ?? '-' }}</div></div>
                            <span class="badge {{ $statusBadge($r['severity'] ?? 'open') }}">{{ ucfirst((string)($r['severity'] ?? 'normal')) }}</span>
                        </div>
                        <div class="mt-2">{{ $r['title'] ?? $r['description'] ?? 'Maintenance record' }}</div>
                        @if(!in_array((string)($r['status'] ?? ''), ['resolved', 'closed'], true))
                            <form method="POST" action="{{ route('super_admin.hotels.maintenance.tickets.status', $r['id']) }}" class="sa-form-grid mt-3">
                                @csrf
                                <div><label class="form-label">Ticket Status</label><select name="status" class="form-select"><option value="in_progress">In progress</option><option value="resolved">Resolved</option><option value="closed">Closed</option></select></div>
                                <div><label class="form-label">Resolution Note</label><input name="resolution_note" class="form-control" placeholder="Work done or next action"></div>
                                <div class="full"><button class="btn btn-sm btn-primary">Update Ticket</button></div>
                            </form>
                        @else
                            <div class="mt-2 small text-muted">Resolved: {{ $r['resolution_note'] ?? 'No note entered' }}</div>
                        @endif
                    </div>
                @empty
                    <div class="sa-empty">No maintenance tickets found yet. Active room locks above still protect rooms from sale and operations.</div>
                @endforelse
            </section>
        @elseif($panel === 'night_audits')
            @php $auditTotal = $panelRows->sum(fn($row) => (float)($rowArray($row)['total_amount'] ?? 0)); @endphp
            <section class="sa-audit-command"><div class="d-flex justify-content-between align-items-center flex-wrap gap-2"><div><h4 class="mb-1">Night Audit Command Center</h4><p class="mb-0">Close-day history, room-status health and financial audit watch across hotel tenants.</p></div><span class="btn btn-warning disabled text-dark">{{ $panelRows->count() }} audit rows</span></div><div class="sa-mini-kpis mt-3"><div class="sa-mini-kpi"><span>Audits</span><strong>{{ $panelRows->count() }}</strong><small>Close-day rows</small></div><div class="sa-mini-kpi"><span>Posted</span><strong>{{ $money($auditTotal) }}</strong><small>Audit amount</small></div><div class="sa-mini-kpi"><span>Receivables</span><strong>{{ $money($outstandingReceivables) }}</strong><small>Before close watch</small></div><div class="sa-mini-kpi"><span>Exceptions</span><strong>{{ $maintenanceRooms + $outOfOrderRooms + $dirtyRooms }}</strong><small>Rooms to review</small></div></div><div class="sa-audit-grid">@forelse($panelRows->take(8) as $row)@php $r=$rowArray($row); @endphp<div class="sa-audit-check {{ in_array(($r['status'] ?? ''), ['failed','pending'], true) ? 'danger' : '' }}"><span class="text-muted small">Business Date</span><h5>{{ $r['audit_date'] ?? $r['business_date'] ?? ('#'.($r['id'] ?? '-')) }}</h5><div class="d-flex justify-content-between"><span>Status</span><strong>{{ ucfirst((string)($r['status'] ?? 'completed')) }}</strong></div><div class="d-flex justify-content-between"><span>Total</span><strong>{{ $money($r['total_amount'] ?? 0) }}</strong></div><small class="text-muted">Company {{ $r['company_id'] ?? '-' }}</small></div>@empty<div class="sa-empty">No night audits have been run yet. Tenant night audit runs will appear here with totals, skipped charges and status.</div>@endforelse</div></section>
        @elseif($panel === 'housekeeping')
            @php
                $sampleHousekeeping = collect([
                    (object) ['id' => 'preview-101', 'room_id' => '101', 'company_id' => 'Preview', 'status' => 'open', 'priority' => 'high', 'task_type' => 'departure_clean', 'note' => 'Departure cleaning - guest arriving today'],
                    (object) ['id' => 'preview-102', 'room_id' => '102', 'company_id' => 'Preview', 'status' => 'assigned', 'priority' => 'normal', 'task_type' => 'stayover', 'note' => 'Stayover service assigned to cleaner'],
                    (object) ['id' => 'preview-103', 'room_id' => '103', 'company_id' => 'Preview', 'status' => 'cleaning', 'priority' => 'normal', 'task_type' => 'deep_clean', 'note' => 'Deep clean currently in progress'],
                    (object) ['id' => 'preview-104', 'room_id' => '104', 'company_id' => 'Preview', 'status' => 'completed', 'priority' => 'normal', 'task_type' => 'inspection', 'note' => 'Inspected and ready for check-in'],
                    (object) ['id' => 'preview-105', 'room_id' => '105', 'company_id' => 'Preview', 'status' => 'open', 'priority' => 'high', 'task_type' => 'rush_clean', 'note' => 'Rush room - front desk waiting'],
                ]);
                $hkRows = $panelRows->isEmpty() ? $sampleHousekeeping : $panelRows;
                $hkIsPreview = $panelRows->isEmpty();
                $hkLane = function ($status) {
                    $status = (string) $status;
                    if (in_array($status, ['completed', 'resolved', 'clean'], true)) return 'clean';
                    if (in_array($status, ['cleaning', 'inspection', 'in_progress'], true)) return 'cleaning';
                    if (in_array($status, ['assigned'], true)) return 'assigned';
                    return 'dirty';
                };
                $hkCounts = [
                    'dirty' => $hkRows->filter(fn($row) => $hkLane($rowArray($row)['status'] ?? 'open') === 'dirty')->count(),
                    'assigned' => $hkRows->filter(fn($row) => $hkLane($rowArray($row)['status'] ?? 'open') === 'assigned')->count(),
                    'cleaning' => $hkRows->filter(fn($row) => $hkLane($rowArray($row)['status'] ?? 'open') === 'cleaning')->count(),
                    'clean' => $hkRows->filter(fn($row) => $hkLane($rowArray($row)['status'] ?? 'open') === 'clean')->count(),
                ];
            @endphp
            <section class="sa-hk-command">
                <div class="sa-hk-head">
                    <div>
                        <small class="text-warning fw-semibold">HOUSEKEEPING CONTROL</small>
                        <h4>Room Readiness Board</h4>
                        <p>Super Admin mirror of dirty rooms, assigned cleaners, cleaning progress and inspected rooms across hotel tenants.</p>
                    </div>
                    <span class="btn btn-outline-secondary disabled">Read-only platform monitor</span>
                </div>
                @if($hkIsPreview)
                    <div class="sa-empty mb-3">No live housekeeping tasks found yet. This preview shows the exact board layout that will populate from tenant rooms and tasks.</div>
                @endif
                <div class="sa-hk-chips">
                    <span class="sa-hk-chip dirty">Dirty: {{ $hkCounts['dirty'] }}</span>
                    <span class="sa-hk-chip assigned">Assigned: {{ $hkCounts['assigned'] }}</span>
                    <span class="sa-hk-chip cleaning">Cleaning: {{ $hkCounts['cleaning'] }}</span>
                    <span class="sa-hk-chip clean">Clean: {{ $hkCounts['clean'] }}</span>
                </div>
                <div class="sa-hk-grid">
                    @foreach($hkRows->take(15) as $row)
                        @php
                            $r = $rowArray($row);
                            $lane = $hkLane($r['status'] ?? 'open');
                            $roomNo = $r['room_number'] ?? $r['room_id'] ?? ('#'.($r['id'] ?? '-'));
                        @endphp
                        <article class="sa-hk-room {{ $lane }}">
                            <div>
                                <div class="d-flex justify-content-between align-items-start gap-2">
                                    <div class="sa-hk-room-no">{{ $roomNo }}</div>
                                    <span class="badge bg-light text-dark">{{ $hkIsPreview ? 'Preview' : 'Tenant' }}</span>
                                </div>
                                <span class="sa-hk-status">{{ ucfirst(str_replace('_', ' ', (string)($r['status'] ?? 'open'))) }}</span>
                                <div class="mt-2 fw-semibold">{{ ucfirst(str_replace('_', ' ', (string)($r['task_type'] ?? 'housekeeping'))) }}</div>
                                <div class="text-muted small">{{ $r['note'] ?? $r['description'] ?? 'Housekeeping record' }}</div>
                            </div>
                            <div class="d-flex justify-content-between align-items-center mt-3 small">
                                <span>Company {{ $r['company_id'] ?? '-' }}</span>
                                <span class="badge {{ ($r['priority'] ?? '') === 'high' ? 'bg-danger' : 'bg-secondary' }}">{{ ucfirst((string)($r['priority'] ?? 'normal')) }}</span>
                            </div>
                        </article>
                    @endforeach
                </div>
                <div class="sa-hk-table">
                    <table class="table table-sm sa-table align-middle mb-0">
                        <thead><tr><th>Task</th><th>Room</th><th>Status</th><th>Priority</th><th>Company</th><th>Note</th><th>Action</th></tr></thead>
                        <tbody>
                            @foreach($hkRows->take(10) as $row)
                                @php $r = $rowArray($row); @endphp
                                <tr>
                                    <td>{{ $r['task_no'] ?? ('Task #'.($r['id'] ?? '-')) }}</td>
                                    <td>{{ $r['room_number'] ?? $r['room_id'] ?? '-' }}</td>
                                    <td><span class="badge {{ $statusBadge($r['status'] ?? 'open') }}">{{ ucfirst(str_replace('_', ' ', (string)($r['status'] ?? 'open'))) }}</span></td>
                                    <td>{{ ucfirst((string)($r['priority'] ?? 'normal')) }}</td>
                                    <td>{{ $r['company_id'] ?? '-' }}</td>
                                    <td>{{ $r['note'] ?? $r['description'] ?? '-' }}</td>
                                    <td>
                                        @if(!$hkIsPreview && !in_array((string)($r['status'] ?? ''), ['completed', 'resolved'], true))
                                            <form method="POST" action="{{ route('super_admin.hotels.housekeeping.tasks.status', $r['id']) }}" class="d-flex gap-1">
                                                @csrf
                                                <select name="status" class="form-select form-select-sm" style="min-width:120px">
                                                    @foreach(['assigned','cleaning','inspection','completed'] as $option)<option value="{{ $option }}">{{ ucfirst($option) }}</option>@endforeach
                                                </select>
                                                <button class="btn btn-sm btn-primary">Save</button>
                                            </form>
                                        @else
                                            <span class="text-muted small">No action</span>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </section>
        @elseif($panel === 'reports')
            @php
                $reportTotal = $panelRows->sum(fn($row) => (float)($rowArray($row)['total_amount'] ?? 0));
                $financialLinks = [
                    ['route' => 'general-ledger', 'label' => 'General Ledger', 'hint' => 'Hotel folio charges and payments post here through LedgerService.'],
                    ['route' => 'reports.profit-loss', 'label' => 'Profit & Loss', 'hint' => 'Room, service, ticketing and POS revenue report into income.'],
                    ['route' => 'trial-balance', 'label' => 'Trial Balance', 'hint' => 'Receivables, cash receipts and revenue balances.'],
                    ['route' => 'balance-sheet', 'label' => 'Balance Sheet', 'hint' => 'Guest receivables and cash impact after posting.'],
                    ['route' => 'reports.sales', 'label' => 'Sales Report', 'hint' => 'POS hotel sales and receipt-based sales review.'],
                ];
            @endphp
            <section class="sa-report-hub">
                <div class="d-flex justify-content-between align-items-end flex-wrap gap-2 mb-3">
                    <div><small class="text-warning fw-semibold">HOTEL REPORTS · SUPER ADMIN</small><h4 class="mb-1">Platform PMS Reports Centre</h4><p class="mb-0">Dynamic report summaries for reservations, cashier, room readiness, engineering and audit oversight.</p></div>
                    <span class="btn btn-warning disabled text-dark">{{ $money($reportTotal) }} reported</span>
                </div>
                <div class="sa-report-grid">
                    <a class="sa-report" href="{{ route('super_admin.hotels.index', array_merge($routeParams ?? [], ['panel' => 'reservations'] + ($selectedCompanyId ? ['company_id' => $selectedCompanyId] : []))) }}"><span>Front Office</span><h4>Reservation Register</h4><p>{{ $todayReservations }} current reservation signals.</p></a>
                    <a class="sa-report" href="{{ route('super_admin.hotels.index', array_merge($routeParams ?? [], ['panel' => 'folios'] + ($selectedCompanyId ? ['company_id' => $selectedCompanyId] : []))) }}"><span>Finance</span><h4>Folio Receivables</h4><p>{{ $money($outstandingReceivables) }} guest balances watched.</p></a>
                    <a class="sa-report" href="{{ route('super_admin.hotels.index', array_merge($routeParams ?? [], ['panel' => 'services'] + ($selectedCompanyId ? ['company_id' => $selectedCompanyId] : []))) }}"><span>Services</span><h4>Service Centers</h4><p>Restaurant, bar, spa, gym, room service and events.</p></a>
                    <a class="sa-report" href="{{ route('super_admin.hotels.index', array_merge($routeParams ?? [], ['panel' => 'housekeeping'] + ($selectedCompanyId ? ['company_id' => $selectedCompanyId] : []))) }}"><span>Rooms</span><h4>Housekeeping</h4><p>{{ $dirtyRooms }} dirty rooms in scope.</p></a>
                    <a class="sa-report" href="{{ route('super_admin.hotels.index', array_merge($routeParams ?? [], ['panel' => 'maintenance'] + ($selectedCompanyId ? ['company_id' => $selectedCompanyId] : []))) }}"><span>Engineering</span><h4>Maintenance</h4><p>{{ $maintenanceRooms + $outOfOrderRooms }} unavailable rooms.</p></a>
                    <a class="sa-report" href="{{ route('super_admin.hotels.index', array_merge($routeParams ?? [], ['panel' => 'night_audits'] + ($selectedCompanyId ? ['company_id' => $selectedCompanyId] : []))) }}"><span>Audit</span><h4>Night Audits</h4><p>Business day close history.</p></a>
                </div>
            </section>
            <section class="sa-dash-panel mt-3">
                <div class="sa-section-head">
                    <div><small class="text-warning fw-semibold">GENERAL FINANCIAL REPORTING</small><h4>Accounting Report Links</h4><p>Hotel sales, ticketing, room-service, folio charges and receipts use accounting ledger entries, so they flow into these reports.</p></div>
                    <button type="button" class="btn btn-outline-dark" onclick="window.print()"><i class="fas fa-print me-1"></i> Print Report Page</button>
                </div>
                <div class="sa-dashboard-services">
                    @foreach($financialLinks as $financialLink)
                        @if(\Illuminate\Support\Facades\Route::has($financialLink['route']))
                            <a class="sa-dashboard-service" href="{{ route($financialLink['route']) }}">
                                <div><small>FINANCE</small><br><strong>{{ $financialLink['label'] }}</strong><b>{{ $financialLink['hint'] }}</b></div>
                                <span class="badge bg-light text-dark">Open</span>
                            </a>
                        @endif
                    @endforeach
                </div>
            </section>
        @elseif($panel === 'settings')
            <section class="sa-dash-panel"><div class="sa-section-head"><div><small class="text-warning fw-semibold">SYSTEM HEALTH</small><h4>Hotel Feature Status</h4><p>Database dependencies and operational readiness for the platform hotel module.</p></div><span class="btn btn-outline-primary disabled">{{ $panelRows->where('status', 'available')->count() }} available</span></div><div class="sa-health">@forelse($panelRows as $row)@php $r=$rowArray($row); @endphp<div class="sa-health-row"><div><strong>{{ str_replace('_',' ', $r['setting'] ?? 'Setting') }}</strong><div class="small text-muted">Tenant PMS dependency</div></div><span class="badge {{ ($r['status'] ?? '') === 'available' ? 'bg-success' : 'bg-danger' }}">{{ ucfirst((string)($r['status'] ?? 'missing')) }}</span></div>@empty<div class="sa-empty">No hotel settings found.</div>@endforelse</div></section>
        @else
            <section class="sa-panel p-3"><h4>{{ $panelTitle }}</h4>@if($panelRows->isEmpty())<div class="sa-empty">No {{ strtolower($panelTitle) }} found.</div>@else<div class="table-responsive"><table class="table table-sm sa-table align-middle"><thead><tr>@foreach(array_keys($rowArray($panelRows->first())) as $col)<th>{{ str_replace('_',' ', $col) }}</th>@endforeach</tr></thead><tbody>@foreach($panelRows as $row)@php $r=$rowArray($row); @endphp<tr>@foreach($rowArray($panelRows->first()) as $col => $_)<td>{{ is_scalar($r[$col] ?? null) || is_null($r[$col] ?? null) ? ($r[$col] ?? '') : json_encode($r[$col]) }}</td>@endforeach</tr>@endforeach</tbody></table></div>@endif</section>
        @endif

        @if($isPaginator && $panel !== 'overview')<div class="mt-3">{{ $panelData->links() }}</div>@endif
    </div>
</div>
@endsection
