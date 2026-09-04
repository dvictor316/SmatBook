@once
<style>
    .hotel-type-page { border-radius: 8px; padding: 18px; background: #f6f8fb; }
    .hotel-type-header { display: flex; justify-content: space-between; align-items: flex-end; gap: 14px; flex-wrap: wrap; margin-bottom: 16px; }
    .hotel-type-header h2 { color: #06264b; font-size: 28px; font-weight: 800; line-height: 1.08; margin: 0; }
    .hotel-type-header p { color: #667085; margin: 8px 0 0; max-width: 760px; }
    .hotel-type-header .btn,
    .hotel-type-page .btn {
        min-height: 34px;
        padding: 6px 12px;
        border-radius: 8px;
        font-size: 13px;
        font-weight: 800;
        line-height: 1.2;
        width: auto;
        white-space: nowrap;
    }
    .hotel-type-page .btn-sm { min-height: 30px; padding: 4px 9px; font-size: 12px; }
    .hotel-type-page .btn-light:hover,
    .hotel-type-page .btn-outline-primary:hover,
    .hotel-type-page .btn-outline-dark:hover,
    .hotel-type-page .btn-outline-secondary:hover {
        color: #102033;
        background: #fff;
        border-color: #17456f;
    }
    .hotel-type-label { display: inline-flex; gap: 8px; align-items: center; color: #9a6700; font-size: 12px; font-weight: 700; letter-spacing: .16em; text-transform: uppercase; margin-bottom: 10px; }
    .hotel-type-panel { background: #fff; border: 1px solid #e7eef7; border-radius: 8px; box-shadow: 0 10px 24px rgba(10,45,83,.06); }
    .hotel-type-panel-header { padding: 14px 16px; border-bottom: 1px solid #edf2f8; }
    .hotel-type-panel-body { padding: 16px; }
    .hotel-type-table { border-collapse: separate; border-spacing: 0 8px; }
    .hotel-type-table thead th { border: 0; color: #77859a; font-size: 12px; letter-spacing: .08em; text-transform: uppercase; }
    .hotel-type-table tbody tr { background: #fff; box-shadow: 0 8px 22px rgba(10,45,83,.05); }
    .hotel-type-table tbody td { border-top: 1px solid #edf2f8; border-bottom: 1px solid #edf2f8; vertical-align: middle; }
    .hotel-type-table tbody td:first-child { border-left: 1px solid #edf2f8; border-radius: 8px 0 0 8px; }
    .hotel-type-table tbody td:last-child { border-right: 1px solid #edf2f8; border-radius: 0 8px 8px 0; }
    .hotel-status-chip { display: inline-flex; align-items: center; border-radius: 999px; padding: 5px 10px; background: #eef6ff; color: #0b4f91; font-size: 12px; font-weight: 600; white-space: nowrap; }
    .hotel-status-chip.green { background: #dcfce7; color: #166534; }
    .hotel-status-chip.gold { background: #fef3c7; color: #92400e; }
    .hotel-status-chip.red { background: #fee2e2; color: #991b1b; }
    .hotel-service-workflow { background: linear-gradient(135deg, #fffaf0 0%, #f5fbff 100%); }
    .hotel-service-layout { display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 18px; align-items: start; }
    .hotel-service-rail { border-left: 5px solid #d9a441; }
    .hotel-service-step { display: flex; gap: 12px; padding: 12px; border-radius: 8px; background: #fff; border: 1px solid #edf2f8; margin-bottom: 10px; }
    .hotel-service-step span { width: 32px; height: 32px; border-radius: 50%; background: #052247; color: #fff; display: inline-flex; align-items: center; justify-content: center; font-weight: 700; flex: 0 0 auto; }
    .hotel-ledger-page { background: linear-gradient(135deg, #f8fbff 0%, #eef7f1 100%); }
    .hotel-ledger-strip { display: flex; gap: 12px; flex-wrap: wrap; margin-bottom: 16px; }
    .hotel-ledger-strip span { background: #fff; border: 1px solid #dbe8f5; border-radius: 999px; padding: 8px 12px; font-weight: 600; color: #06264b; }
    .hotel-directory-page { background: #f7f9fc; }
    .hotel-directory-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(260px, 1fr)); gap: 14px; }
    .hotel-directory-card { background: #fff; border: 1px solid #e7eef7; border-radius: 8px; padding: 16px; box-shadow: 0 10px 22px rgba(10,45,83,.05); }
    .hotel-active-stay-page { background: linear-gradient(135deg, #f4f8ff 0%, #fff7ed 100%); }
    .hotel-active-layout { display: grid; grid-template-columns: minmax(0, 1.4fr) minmax(240px, .6fr); gap: 18px; }
    .hotel-room-stack { display: grid; gap: 10px; }
    .hotel-room-stack a, .hotel-room-stack div { border-radius: 8px; padding: 12px; background: #fff; border: 1px solid #e7eef7; }
    .hotel-report-page { background: #061d36; color: #fff; }
    .hotel-report-page .hotel-type-header h2, .hotel-report-page .hotel-type-header p { color: #fff; }
    .hotel-report-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(230px, 1fr)); gap: 14px; }
    .hotel-report-tile { background: rgba(255,255,255,.08); border: 1px solid rgba(255,255,255,.16); border-radius: 8px; padding: 16px; color: #fff; min-height: 150px; }
    .hotel-config-page { background: linear-gradient(135deg, #f8fafc 0%, #fff7ed 100%); }
    .hotel-config-layout { display: grid; grid-template-columns: minmax(260px, .85fr) minmax(0, 1.15fr); gap: 18px; }
    .hotel-config-list { display: grid; gap: 10px; }
    .hotel-config-list div { padding: 14px; border-radius: 8px; background: #fff; border: 1px solid #e7eef7; }
    @media (max-width: 991.98px) {
        .hotel-service-layout, .hotel-active-layout, .hotel-config-layout { grid-template-columns: 1fr; }
    }
</style>
@endonce
