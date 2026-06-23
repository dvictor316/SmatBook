@once
<style>
    :root {
        --agent-navy: #062f68;
        --agent-navy-2: #0a438d;
        --agent-ink: #14213d;
        --agent-muted: #7d8aa2;
        --agent-bg: #f5f8fc;
        --agent-card: #ffffff;
        --agent-line: #e7edf5;
        --agent-green: #18bf86;
        --agent-amber: #f7a51e;
        --agent-red: #d91f5c;
        --agent-blue: #246bfe;
        --agent-purple: #5b42f3;
    }
    .agent-sidebar .sidebar-inner { background: #fff; border-right: 1px solid var(--agent-line); }
    .agent-brand { display: flex; align-items: center; gap: 12px; padding: 24px 24px 18px; color: var(--agent-navy); }
    .agent-brand-mark { width: 44px; height: 44px; border-radius: 12px; display: grid; place-items: center; background: var(--agent-navy); color: #ffc20f; font-size: 27px; font-weight: 900; box-shadow: 0 14px 28px rgba(6, 47, 104, .18); }
    .agent-brand strong { display: block; font-size: 24px; line-height: 1; }
    .agent-brand small { display: block; color: var(--agent-muted); text-transform: uppercase; letter-spacing: .12em; font-size: 10px; margin-top: 4px; }
    .agent-menu-label { margin: 12px 24px; color: #9aa6ba; font-weight: 800; text-transform: uppercase; letter-spacing: .18em; font-size: 11px; }
    .agent-sidebar-menu ul { padding: 0 16px 24px; }
    .agent-sidebar-menu li a { border-radius: 14px; color: #7d8aa2; display: flex; align-items: center; gap: 14px; padding: 13px 16px; font-weight: 700; transition: transform .18s ease, background .18s ease, color .18s ease; }
    .agent-sidebar-menu li a:hover { transform: translateX(3px); color: var(--agent-navy); background: #eef5ff; }
    .agent-sidebar-menu li a.active { background: var(--agent-navy); color: #fff; box-shadow: 0 12px 24px rgba(6, 47, 104, .18); }
    .agent-sidebar-menu li i { width: 20px; text-align: center; }
    .agent-soon small { margin-left: auto; border-radius: 999px; background: #f1f4f8; color: #95a0b3; padding: 3px 8px; font-size: 10px; text-transform: uppercase; }
    .agent-logout { border-top: 1px solid var(--agent-line); margin-top: 18px; padding-top: 12px; }
    .agent-logout a, .agent-logout a:hover { color: var(--agent-red) !important; background: #fff4f7 !important; }
    .agent-page { background: radial-gradient(circle at top left, rgba(36, 107, 254, .08), transparent 26rem), linear-gradient(180deg, #fbfdff 0, var(--agent-bg) 260px); min-height: calc(100vh - 70px); padding: 24px; color: var(--agent-ink); }
    .agent-topline { display: flex; align-items: center; justify-content: space-between; gap: 18px; margin-bottom: 22px; }
    .agent-title h1 { margin: 0; font-size: clamp(21px, 2.4vw, 30px); font-weight: 900; color: var(--agent-ink); letter-spacing: -.03em; }
    .agent-title p { margin: 5px 0 0; color: var(--agent-muted); font-size: 13px; }
    .agent-avatar { width: 52px; height: 52px; border-radius: 50%; border: 3px solid var(--agent-navy); display: grid; place-items: center; color: var(--agent-navy); background: #fff; font-weight: 900; }
    .agent-grid { display: grid; grid-template-columns: repeat(12, minmax(0, 1fr)); gap: 18px; }
    .agent-card { background: var(--agent-card); border: 1px solid var(--agent-line); border-radius: 22px; box-shadow: 0 18px 38px rgba(11, 36, 74, .08); padding: 18px; animation: agent-rise .45s ease both; font-size: 13px; }
    .agent-card h3, .agent-card h4 { margin: 0; color: var(--agent-ink); font-weight: 900; }
    .agent-card small, .agent-muted { color: var(--agent-muted); }
    .span-3 { grid-column: span 3; } .span-4 { grid-column: span 4; } .span-5 { grid-column: span 5; } .span-6 { grid-column: span 6; } .span-7 { grid-column: span 7; } .span-8 { grid-column: span 8; } .span-12 { grid-column: span 12; }
    .agent-metric { min-height: 154px; position: relative; overflow: hidden; }
    .agent-metric .label { color: #65738c; font-size: 12px; font-weight: 900; text-transform: uppercase; letter-spacing: .08em; }
    .agent-metric .value { font-size: clamp(24px, 3vw, 34px); font-weight: 900; letter-spacing: -.04em; color: var(--agent-ink); margin: 8px 0 4px; }
    .agent-metric .icon { position: absolute; right: 20px; top: 20px; width: 44px; height: 44px; border-radius: 14px; display: grid; place-items: center; background: #eef5ff; color: var(--agent-blue); }
    .agent-pill { display: inline-flex; align-items: center; gap: 7px; border-radius: 999px; padding: 7px 11px; background: #f3f7fc; color: var(--agent-navy); font-weight: 800; font-size: 13px; }
    .agent-button { border: 0; border-radius: 14px; background: var(--agent-navy); color: #fff; font-weight: 900; padding: 10px 15px; display: inline-flex; align-items: center; justify-content: center; gap: 8px; box-shadow: 0 14px 24px rgba(6, 47, 104, .2); transition: transform .18s ease, box-shadow .18s ease; font-size: 13px; }
    .agent-button:hover { color: #fff; transform: translateY(-2px); box-shadow: 0 18px 28px rgba(6, 47, 104, .26); }
    .agent-button.soft { background: #eef5ff; color: var(--agent-navy); box-shadow: none; }
    .agent-button.green { background: var(--agent-green); }
    .agent-tabs { display: flex; gap: 8px; overflow-x: auto; background: #fff; border: 1px solid var(--agent-line); border-radius: 16px; padding: 6px; box-shadow: 0 10px 26px rgba(11, 36, 74, .06); }
    .agent-tabs a, .agent-tabs button { white-space: nowrap; border: 0; border-radius: 12px; background: transparent; color: #69778e; padding: 10px 16px; font-weight: 900; }
    .agent-tabs .active { background: var(--agent-navy); color: #fff; }
    .agent-progress { height: 12px; border-radius: 999px; background: #e8eef6; overflow: hidden; }
    .agent-progress span { display: block; height: 100%; border-radius: inherit; background: linear-gradient(90deg, var(--agent-amber), #ffcf56); }
    .agent-donut { --value: 0; --color: var(--agent-green); width: 180px; height: 180px; border-radius: 50%; display: grid; place-items: center; background: conic-gradient(var(--color) calc(var(--value) * 1%), #e7edf5 0); position: relative; }
    .agent-donut::after { content: ""; position: absolute; inset: 32px; border-radius: 50%; background: #fff; }
    .agent-donut strong { position: relative; z-index: 1; font-size: 26px; }
    .agent-stat-list { display: grid; gap: 15px; }
    .agent-stat-row { display: flex; align-items: center; justify-content: space-between; gap: 16px; border-top: 1px solid var(--agent-line); padding-top: 14px; }
    .agent-dot { width: 10px; height: 10px; border-radius: 50%; display: inline-block; margin-right: 8px; background: var(--agent-green); }
    .agent-lead-card { display: grid; grid-template-columns: auto 1fr auto; gap: 14px; align-items: start; }
    .agent-initial { width: 48px; height: 48px; border-radius: 50%; display: grid; place-items: center; background: #eef2f7; color: var(--agent-navy); font-weight: 900; }
    .agent-actions { display: flex; flex-wrap: wrap; gap: 8px; margin-top: 14px; }
    .agent-actions a, .agent-actions button { border: 0; border-radius: 12px; padding: 9px 14px; background: #eef5ff; color: var(--agent-blue); font-weight: 850; }
    .agent-actions .chat { background: #eafff6; color: #0f9f72; }
    .agent-form-grid { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 14px; }
    .agent-field label { display: block; color: #65738c; font-weight: 900; font-size: 12px; text-transform: uppercase; margin-bottom: 7px; }
    .agent-field input, .agent-field select, .agent-field textarea { width: 100%; border: 1px solid #dfe7f1; border-radius: 13px; padding: 10px 12px; color: var(--agent-ink); background: #fff; font-size: 13px; }
    .agent-field input:focus, .agent-field select:focus, .agent-field textarea:focus { outline: 2px solid rgba(36, 107, 254, .17); border-color: var(--agent-blue); }
    .agent-bar-chart { display: flex; align-items: end; gap: 8px; height: 76px; }
    .agent-bar-chart span { width: 22px; border-radius: 8px 8px 2px 2px; background: linear-gradient(180deg, #5b42f3, #2d72ff); }
    .agent-heatmap { display: grid; grid-template-columns: repeat(7, 1fr); gap: 8px; }
    .agent-heat { aspect-ratio: 1; border-radius: 9px; background: #edf2f7; }
    .agent-heat.level-1 { background: #b7f4d7; } .agent-heat.level-2 { background: #79e7ba; } .agent-heat.level-3 { background: #27c98d; } .agent-heat.level-4 { background: #079b69; }
    @keyframes agent-rise { from { opacity: 0; transform: translateY(14px); } to { opacity: 1; transform: translateY(0); } }
    @media (max-width: 1199px) { .span-3, .span-4 { grid-column: span 6; } .span-5, .span-6, .span-7, .span-8 { grid-column: span 12; } }
    @media (max-width: 767px) {
        .agent-page { padding: 18px 14px 28px; }
        .agent-topline { align-items: flex-start; }
        .span-3, .span-4, .span-5, .span-6, .span-7, .span-8, .span-12 { grid-column: span 12; }
        .agent-card { border-radius: 18px; padding: 18px; }
        .agent-form-grid { grid-template-columns: 1fr; }
        .agent-lead-card { grid-template-columns: auto 1fr; }
        .agent-lead-card > .agent-actions { grid-column: 1 / -1; }
        .agent-donut { width: 150px; height: 150px; }
    }
</style>
@endonce
