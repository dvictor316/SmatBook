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
    .hotel-type-panel-body { padding: 16px; overflow: visible; }
    .hotel-type-page select.form-select { position: relative; z-index: 2; background: #fff; color: #061b33; }
    .hotel-type-page select.form-select:focus { z-index: 60; }
    .hotel-type-page select.form-select option { background: #fff; color: #061b33; padding: 10px; }
    .hotel-dropdown-field { position: relative; z-index: 25; }
    .hotel-dropdown-field:focus-within { z-index: 60; }
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
    .hotel-op-kpis { display: grid; grid-template-columns: repeat(auto-fit, minmax(170px, 1fr)); gap: 12px; margin-bottom: 16px; }
    .hotel-op-kpi { background: #fff; border: 1px solid #e2ebf6; border-radius: 8px; padding: 14px; box-shadow: 0 8px 20px rgba(10,45,83,.05); }
    .hotel-op-kpi span { display: block; color: #667085; font-size: 12px; font-weight: 800; letter-spacing: .08em; text-transform: uppercase; }
    .hotel-op-kpi strong { display: block; color: #06264b; font-size: 25px; line-height: 1; margin-top: 8px; }
    .hotel-op-board { display: grid; grid-template-columns: minmax(0, 1.15fr) minmax(300px, .85fr); gap: 18px; align-items: start; }
    .hotel-op-cards { display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 12px; }
    .hotel-op-card { background: #fff; border: 1px solid #e2ebf6; border-radius: 8px; padding: 14px; box-shadow: 0 8px 20px rgba(10,45,83,.05); }
    .hotel-op-card h5 { color: #06264b; font-weight: 800; margin: 0 0 6px; }
    .hotel-op-card p { color: #667085; margin: 0; line-height: 1.35; }
    .hotel-op-actions { display: flex; gap: 8px; flex-wrap: wrap; margin-top: 12px; }
    .hotel-op-lanes { display: grid; grid-template-columns: repeat(auto-fit, minmax(170px, 1fr)); gap: 12px; }
    .hotel-op-lane { min-height: 150px; border-radius: 8px; border: 1px solid #e2ebf6; background: #fff; padding: 13px; box-shadow: 0 8px 20px rgba(10,45,83,.05); }
    .hotel-op-lane strong { display: block; color: #06264b; margin-bottom: 8px; }
    .hotel-op-lane .lane-count { display: inline-flex; align-items: center; justify-content: center; min-width: 38px; height: 34px; border-radius: 8px; background: #052247; color: #fff; font-weight: 900; }
    .hotel-op-split { display: grid; grid-template-columns: minmax(0, 1fr) 360px; gap: 18px; align-items: start; }
    .hotel-op-room-card { display: grid; grid-template-columns: 74px minmax(0, 1fr); gap: 12px; align-items: center; border: 1px solid #e2ebf6; border-radius: 8px; background: #fff; padding: 12px; box-shadow: 0 8px 20px rgba(10,45,83,.05); }
    .hotel-op-room-no { width: 74px; height: 74px; border-radius: 8px; background: #052247; color: #fff; display: grid; place-items: center; font-size: 24px; font-weight: 900; }
    .hotel-sale-actions { display: flex; gap: 7px; flex-wrap: wrap; min-width: 230px; }
    .hotel-sale-actions form { margin: 0; }
    .hotel-sale-actions .btn { white-space: nowrap; }
    .hotel-op-alert { border-left: 5px solid #d9a441; background: #fff8e5; border-radius: 8px; padding: 12px; color: #533600; }
    .hotel-op-alert.green { border-left-color: #15803d; background: #ecfdf3; color: #14532d; }
    .hotel-op-alert.blue { border-left-color: #0b5fb8; background: #eef6ff; color: #0b3767; }
    [class*="hotel-service-theme-"] { position: relative; overflow: hidden; background-color: #082f55; background-image: linear-gradient(90deg, rgba(4,16,31,.88), rgba(4,16,31,.48)), url('/assets/img/hotel-keto/banner2.jpg'); background-size: cover; background-position: center; color: #fff; }
    [class*="hotel-service-theme-"] > * { position: relative; z-index: 1; }
    .hotel-service-theme-bar { background-image: linear-gradient(90deg, rgba(4,16,31,.88), rgba(4,16,31,.48)), url('/assets/img/hotel-keto/gallery8.jpg'); color: #fff; }
    .hotel-service-theme-gym { background-image: linear-gradient(90deg, rgba(4,16,31,.88), rgba(4,16,31,.48)), url('/assets/img/hotel-keto/gallery6.jpg'); color: #fff; }
    .hotel-service-theme-spa { background-image: linear-gradient(90deg, rgba(4,16,31,.88), rgba(4,16,31,.48)), url('/assets/img/hotel-keto/gallery7.jpg'); color: #fff; }
    .hotel-service-theme-ticketing { background-image: linear-gradient(90deg, rgba(4,16,31,.88), rgba(4,16,31,.48)), url('/assets/img/hotel-keto/banner1.jpg'); color: #fff; }
    .hotel-service-theme-room_service { background-image: linear-gradient(90deg, rgba(4,16,31,.88), rgba(4,16,31,.48)), url('/assets/img/hotel-keto/room2.jpg'); color: #fff; }
    .hotel-service-theme-minibar { background-image: linear-gradient(90deg, rgba(4,16,31,.88), rgba(4,16,31,.48)), url('/assets/img/hotel-keto/room3.jpg'); color: #fff; }
    .hotel-service-theme-laundry { background-image: linear-gradient(90deg, rgba(4,16,31,.88), rgba(4,16,31,.48)), url('/assets/img/hotel-keto/room4.jpg'); color: #fff; }
    .hotel-service-theme-conference { background-image: linear-gradient(90deg, rgba(4,16,31,.88), rgba(4,16,31,.48)), url('/assets/img/hotel-keto/banner3.jpg'); color: #fff; }
    .hotel-service-theme-bar h5, .hotel-service-theme-bar p,
    .hotel-service-theme-gym h5, .hotel-service-theme-gym p,
    .hotel-service-theme-spa h5, .hotel-service-theme-spa p,
    .hotel-service-theme-ticketing h5, .hotel-service-theme-ticketing p,
    .hotel-service-theme-room_service h5, .hotel-service-theme-room_service p,
    .hotel-service-theme-minibar h5, .hotel-service-theme-minibar p,
    .hotel-service-theme-laundry h5, .hotel-service-theme-laundry p,
    .hotel-service-theme-conference h5, .hotel-service-theme-conference p { color: #fff !important; text-shadow: 0 2px 10px rgba(0,0,0,.45); }
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
        .hotel-service-layout, .hotel-active-layout, .hotel-config-layout, .hotel-op-board, .hotel-op-split { grid-template-columns: 1fr; }
    }
</style>
@endonce
