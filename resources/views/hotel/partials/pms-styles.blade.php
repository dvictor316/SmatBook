@once
<style>
    .hotel-pms-shell {
        background: linear-gradient(135deg, #f5f8ff 0%, #eef6ff 46%, #fffaf0 100%);
        border-radius: 28px;
        padding: 22px;
        box-shadow: inset 0 1px 0 rgba(255,255,255,.8);
    }
    .hotel-pms-hero {
        background: linear-gradient(135deg, #052247 0%, #0a3b71 58%, #12628f 100%);
        color: #fff;
        border-radius: 24px;
        padding: 22px 24px;
        margin-bottom: 18px;
        box-shadow: 0 22px 50px rgba(5,34,71,.18);
        position: relative;
        overflow: hidden;
    }
    .hotel-pms-hero:after {
        content: "";
        position: absolute;
        inset: auto -70px -120px auto;
        width: 260px;
        height: 260px;
        background: radial-gradient(circle, rgba(218,170,74,.35), transparent 68%);
        pointer-events: none;
    }
    .hotel-pms-eyebrow {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        border-radius: 999px;
        background: rgba(255,255,255,.12);
        color: #f7d486;
        font-size: 12px;
        font-weight: 800;
        letter-spacing: .18em;
        padding: 8px 12px;
        text-transform: uppercase;
    }
    .hotel-pms-hero h2 {
        font-size: clamp(24px, 3vw, 40px);
        font-weight: 900;
        line-height: 1.04;
        margin: 14px 0 8px;
        color: #fff;
    }
    .hotel-pms-hero p {
        color: rgba(255,255,255,.78);
        margin: 0;
        max-width: 760px;
    }
    .hotel-pms-actionbar {
        display: flex;
        flex-wrap: wrap;
        gap: 10px;
        margin-top: 16px;
    }
    .hotel-pms-kpis {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(170px, 1fr));
        gap: 14px;
        margin-bottom: 18px;
    }
    .hotel-pms-kpi,
    .hotel-pms-card {
        background: rgba(255,255,255,.94);
        border: 1px solid rgba(13,55,102,.12);
        border-radius: 22px;
        box-shadow: 0 16px 38px rgba(15,49,88,.08);
    }
    .hotel-pms-kpi {
        padding: 18px;
    }
    .hotel-pms-kpi small {
        display: block;
        color: #6b7a90;
        font-weight: 800;
        letter-spacing: .08em;
        text-transform: uppercase;
    }
    .hotel-pms-kpi strong {
        display: block;
        color: #06264b;
        font-size: 28px;
        line-height: 1;
        margin-top: 8px;
    }
    .hotel-pms-card {
        padding: 18px;
    }
    .hotel-pms-card-title {
        color: #06264b;
        font-size: 18px;
        font-weight: 900;
        margin: 0 0 12px;
    }
    .hotel-pms-table {
        border-collapse: separate;
        border-spacing: 0 10px;
    }
    .hotel-pms-table thead th {
        color: #71809a;
        border: 0;
        font-size: 12px;
        letter-spacing: .08em;
        text-transform: uppercase;
    }
    .hotel-pms-table tbody tr {
        background: #fff;
        box-shadow: 0 10px 24px rgba(10,45,83,.06);
    }
    .hotel-pms-table tbody td {
        border-top: 1px solid #edf2f8;
        border-bottom: 1px solid #edf2f8;
        vertical-align: middle;
    }
    .hotel-pms-table tbody td:first-child {
        border-left: 1px solid #edf2f8;
        border-radius: 14px 0 0 14px;
    }
    .hotel-pms-table tbody td:last-child {
        border-right: 1px solid #edf2f8;
        border-radius: 0 14px 14px 0;
    }
    .hotel-pms-pill {
        display: inline-flex;
        align-items: center;
        border-radius: 999px;
        padding: 5px 10px;
        background: #eaf4ff;
        color: #0b4f91;
        font-size: 12px;
        font-weight: 800;
        white-space: nowrap;
    }
    .hotel-pms-pill.gold { background: #fff4d9; color: #9a6700; }
    .hotel-pms-pill.green { background: #dff8eb; color: #08703d; }
    .hotel-pms-pill.red { background: #ffe7e7; color: #b42318; }
    .hotel-pms-board {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
        gap: 14px;
    }
    .hotel-pms-lane {
        background: rgba(255,255,255,.72);
        border: 1px solid rgba(13,55,102,.12);
        border-radius: 20px;
        padding: 14px;
        min-height: 220px;
    }
    .hotel-pms-lane h5 {
        color: #06264b;
        font-weight: 900;
        margin-bottom: 12px;
    }
    .hotel-pms-ticket {
        background: #fff;
        border: 1px solid #e7eef7;
        border-radius: 16px;
        padding: 12px;
        margin-bottom: 10px;
        box-shadow: 0 10px 24px rgba(10,45,83,.06);
    }
    .hotel-pms-muted { color: #6b7a90; }
    @media (max-width: 767.98px) {
        .hotel-pms-shell { padding: 14px; border-radius: 20px; }
        .hotel-pms-hero { padding: 18px; border-radius: 20px; }
        .hotel-pms-actionbar .btn { width: 100%; }
        .hotel-pms-table { border-spacing: 0 8px; }
    }
</style>
@endonce
