@extends('layout.landing_nav')

@section('content')
{{-- ═══════════════════════════════════════════════════════════════════
     SMATBOOK ENTERPRISE LANDING — Full Rewrite
     Design System: Deep Navy (#002347) · Gold (#c5a059) · Crimson (#bc002d)
     All sections preserved. Colors, spacing, and hierarchy unified.
═══════════════════════════════════════════════════════════════════ --}}
<style>
/* ─────────────────────────────────────────────────────────────
   1. DESIGN TOKENS
──────────────────────────────────────────────────────────────── */
@import url('https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;600;700;800;900&family=DM+Sans:wght@400;500;600;700&display=swap');

:root {
    /* Brand */
    --navy:        #002347;
    --navy-mid:    #003466;
    --navy-deep:   #001529;
    --gold:        #c5a059;
    --gold-light:  #e4c47e;
    --gold-bright: #ffdf91;
    --gold-soft:   #f8e7b8;
    --gold-bg:     #fffbf0;
    --crimson:     #bc002d;
    --crimson-dk:  #960024;

    /* Neutrals */
    --white:       #ffffff;
    --off-white:   #f8faff;
    --border:      #e4eaf4;
    --muted:       #6b7280;
    --text:        #1a2232;

    /* Surfaces */
    --surface-1:   #ffffff;
    --surface-2:   #f4f8ff;
    --surface-3:   #edf2fb;

    /* Typography */
    --font-display: 'Plus Jakarta Sans', sans-serif;
    --font-body:    'DM Sans', sans-serif;

    /* Layout */
    --nav-h:       84px;
    --radius-sm:   8px;
    --radius-md:   14px;
    --radius-lg:   20px;
    --radius-xl:   28px;

    /* Shadows */
    --shadow-sm:   0 2px 8px rgba(0,35,71,0.06);
    --shadow-md:   0 8px 28px rgba(0,35,71,0.10);
    --shadow-lg:   0 20px 56px rgba(0,35,71,0.14);
    --shadow-gold: 0 8px 24px rgba(197,160,89,0.30);
    --shadow-red:  0 8px 24px rgba(188,0,45,0.35);
}

*, *::before, *::after { box-sizing: border-box; }
html { scroll-behavior: smooth; scroll-padding-top: calc(var(--nav-h) + 8px); }
body { font-family: var(--font-body); background: var(--white); color: var(--text); overflow-x: hidden; }

/* Hide duplicate nav */
nav:not(#mainNav):not(.sb-nav) { display: none !important; }

/* ─────────────────────────────────────────────────────────────
   2. NAVIGATION
──────────────────────────────────────────────────────────────── */
nav.sb-nav {
    background: rgba(240,244,252,0.97);
    backdrop-filter: blur(16px);
    -webkit-backdrop-filter: blur(16px);
    border-bottom: 1px solid var(--border);
    height: var(--nav-h);
    z-index: 9999;
    transition: all 0.3s ease;
    font-family: var(--font-display);
}
nav.sb-nav.scrolled {
    background: rgba(255,255,255,0.98);
    box-shadow: 0 4px 24px rgba(0,35,71,0.10);
}
nav.sb-nav .container { height: var(--nav-h); display: flex; align-items: center; }
.sb-brand { display: flex; align-items: center; gap: 8px; text-decoration: none; }
.sb-brand img { height: 32px; }
.sb-brand-text { font-size: 1.55rem; font-weight: 900; color: var(--navy); letter-spacing: -0.5px; }
.sb-brand-text .b { color: #1c66e8; }
.sb-nav-link {
    font-weight: 700; font-size: 0.78rem; text-transform: uppercase;
    letter-spacing: 1px; color: var(--navy) !important; padding: 6px 14px;
    border-radius: var(--radius-sm); transition: all 0.2s;
    position: relative; white-space: nowrap;
    text-decoration: none !important;
    border: none !important;
    outline: none !important;
}
.sb-nav-link:hover, .sb-nav-link.active { color: #1c66e8 !important; background: rgba(28,102,232,0.07); }
.sb-nav-link::after,
.sb-nav-link::before { display: none !important; content: none !important; }
/* Kill Bootstrap's underline / border on all nav links */
.navbar-nav .nav-link,
.navbar-nav .nav-link:focus,
.navbar-nav .nav-link:hover,
.navbar-nav .nav-link.active {
    text-decoration: none !important;
    border-bottom: none !important;
    box-shadow: none !important;
}
.navbar-nav .nav-link::after,
.navbar-nav .nav-link::before { display: none !important; }
.btn-portal {
    background: linear-gradient(135deg, #1170ec, #19b9e6);
    color: #fff !important;
    padding: 10px 28px; border-radius: var(--radius-md);
    font-weight: 800; font-size: 0.78rem; letter-spacing: 1.2px;
    text-transform: uppercase; text-decoration: none;
    box-shadow: 0 4px 16px rgba(17,112,236,0.25);
    transition: all 0.3s; border: none; display: inline-flex; align-items: center; gap: 8px;
}
.btn-portal:hover { transform: translateY(-2px); box-shadow: 0 8px 24px rgba(17,112,236,0.35); color: #fff !important; }

/* ─────────────────────────────────────────────────────────────
   3. SHARED BUTTON SYSTEM
──────────────────────────────────────────────────────────────── */
.btn-red {
    background: var(--crimson);
    color: var(--white) !important; border: none;
    padding: 16px 44px; font-weight: 800; border-radius: var(--radius-sm);
    font-size: 0.88rem; letter-spacing: 1.5px; text-transform: uppercase;
    box-shadow: var(--shadow-red); transition: all 0.35s cubic-bezier(.175,.885,.32,1.275);
    text-decoration: none; display: inline-flex; align-items: center; gap: 10px;
    position: relative; overflow: hidden;
}
.btn-red::after {
    content: ''; position: absolute; inset: 0;
    background: linear-gradient(90deg, transparent 0%, rgba(255,255,255,0.18) 50%, transparent 100%);
    transform: translateX(-100%); transition: transform 0.5s ease;
}
.btn-red:hover::after { transform: translateX(100%); }
.btn-red:hover { transform: translateY(-3px) scale(1.015); background: var(--crimson-dk); box-shadow: 0 14px 32px rgba(188,0,45,0.50); color: var(--white) !important; }

.btn-gold {
    background: linear-gradient(135deg, var(--gold), var(--gold-bright));
    color: var(--navy) !important; border: none;
    padding: 16px 44px; font-weight: 800; border-radius: var(--radius-sm);
    font-size: 0.88rem; letter-spacing: 1.5px; text-transform: uppercase;
    box-shadow: var(--shadow-gold); transition: all 0.35s cubic-bezier(.175,.885,.32,1.275);
    text-decoration: none; display: inline-flex; align-items: center; gap: 10px;
    position: relative; overflow: hidden;
}
.btn-gold:hover { transform: translateY(-3px) scale(1.015); box-shadow: 0 14px 32px rgba(197,160,89,0.50); color: var(--navy) !important; }

.btn-ghost-gold {
    background: rgba(255,223,145,0.14);
    color: var(--gold-bright) !important;
    border: 2px solid rgba(255,223,145,0.65);
    padding: 15px 42px; font-weight: 800; border-radius: 32px;
    font-size: 0.85rem; letter-spacing: 1.2px; text-transform: uppercase;
    transition: all 0.3s; text-decoration: none;
    display: inline-flex; align-items: center; gap: 8px;
}
.btn-ghost-gold:hover { background: rgba(255,223,145,0.26); color: var(--gold-bright) !important; border-color: var(--gold-bright); }

.btn-outline-navy {
    background: transparent; color: var(--navy) !important;
    border: 2px solid var(--border);
    padding: 10px 22px; border-radius: var(--radius-sm);
    font-weight: 700; font-size: 0.82rem; transition: all 0.25s; cursor: pointer;
    display: inline-flex; align-items: center; gap: 8px; text-decoration: none;
}
.btn-outline-navy:hover { border-color: var(--gold); color: var(--gold) !important; }

/* ─────────────────────────────────────────────────────────────
   4. SECTION SCAFFOLDING
──────────────────────────────────────────────────────────────── */
.sb-section { padding: 100px 0; }
.sb-section--sm { padding: 70px 0; }
.sb-section--dark { background: linear-gradient(135deg, var(--navy-deep), var(--navy)); color: var(--white); }
.sb-section--alt { background: var(--surface-2); }
.sb-section--border { border-top: 1px solid var(--border); border-bottom: 1px solid var(--border); }

.sb-eyebrow {
    display: inline-flex; align-items: center; gap: 10px;
    font-size: 0.7rem; font-weight: 800; letter-spacing: 3px;
    text-transform: uppercase; color: var(--gold); margin-bottom: 14px;
    font-family: var(--font-display);
}
.sb-eyebrow::before { content: ''; width: 24px; height: 2px; background: var(--gold); display: block; border-radius: 2px; }

.sb-h1 { font-family: var(--font-display); font-size: clamp(1.9rem, 3.5vw, 2.7rem); font-weight: 800; line-height: 1.15; color: var(--navy); letter-spacing: -1px; margin-bottom: 18px; }
.sb-h1 .accent { color: var(--gold); }
.sb-h1-white { color: var(--white); }
.sb-lead { font-size: 15px; line-height: 1.9; color: var(--muted); max-width: 580px; }
.sb-lead-white { color: rgba(255,255,255,0.72); }

/* ─────────────────────────────────────────────────────────────
   5. ANNOUNCEMENT BAR
──────────────────────────────────────────────────────────────── */
.announce-bar {
    position: fixed; top: var(--nav-h); left: 0; right: 0; z-index: 9998;
    height: var(--announce-h); background: var(--navy);
    display: flex; align-items: center; justify-content: center;
    overflow: hidden;
}
.announce-label {
    position: absolute; left: 0; top: 0; bottom: 0;
    background: linear-gradient(135deg, var(--gold), var(--gold-bright));
    color: var(--navy); font-weight: 900; font-size: 0.62rem;
    letter-spacing: 2px; text-transform: uppercase;
    padding: 0 20px; display: flex; align-items: center; gap: 6px;
    white-space: nowrap;
}
.announce-track { position: relative; height: 100%; flex: 1; display: flex; align-items: center; justify-content: center; }
.announce-msg {
    position: absolute; left: 50%; transform: translateX(-50%) translateY(8px);
    font-size: 0.72rem; font-weight: 700; color: rgba(255,255,255,0.9);
    letter-spacing: 0.5px; white-space: nowrap;
    opacity: 0; transition: opacity 0.5s ease, transform 0.5s ease;
    display: flex; align-items: center; gap: 10px;
}
.announce-msg.active { opacity: 1; transform: translateX(-50%) translateY(0); }
.announce-msg.exit   { opacity: 0; transform: translateX(-50%) translateY(-8px); }
.announce-dot { display: inline-block; width: 5px; height: 5px; border-radius: 50%; background: var(--gold); animation: blink 1.2s infinite; }
@keyframes blink { 0%,100%{opacity:1} 50%{opacity:0.3} }

/* ─────────────────────────────────────────────────────────────
   6. HERO SECTION
──────────────────────────────────────────────────────────────── */
:root { --announce-h: 38px; }
.hero-wrap {
    position: relative;
    display: flex; align-items: center; justify-content: center;
    padding: calc(var(--nav-h) + var(--announce-h) + 0px) 16px 32px;
    background: #ffffff;
    overflow: hidden;
}
.hero-grid-bg {
    position: absolute; inset: 0; z-index: 0;
    background:
        linear-gradient(125deg, rgba(220,232,250,0.80) 0%, rgba(248,240,220,0.72) 52%, rgba(234,216,172,0.48) 100%),
        repeating-linear-gradient(0deg, rgba(197,160,89,0.12) 0 1px, transparent 1px 36px),
        repeating-linear-gradient(90deg, rgba(197,160,89,0.12) 0 1px, transparent 1px 36px);
}
.hero-inner {
    position: relative; z-index: 2;
    width: 100%; max-width: 1180px;
    display: flex; align-items: center; justify-content: space-between;
    gap: 20px; padding: 28px 28px;
    border-radius: var(--radius-xl);
    background: linear-gradient(140deg, #061f4f 0%, #0f3a83 62%, #c49a47 100%);
    box-shadow: 0 32px 80px rgba(0,22,56,0.28);
    border: 1px solid rgba(255,223,145,0.30);
}
.hero-circle {
    position: relative; order: 1; flex-shrink: 0;
    width: min(500px, 46vw); height: min(500px, 46vw);
    border-radius: 50%;
    background: radial-gradient(circle at 40% 28%, #2054b5 0%, #0d2f7d 52%, #061c56 100%);
    border: 3px solid rgba(243,206,132,0.88);
    display: flex; flex-direction: column; align-items: center; justify-content: center;
    text-align: center; padding: clamp(14px, 4%, 52px);
    box-shadow: 0 0 0 12px rgba(243,206,132,0.12), 0 0 0 24px rgba(243,206,132,0.06), 0 28px 80px rgba(0,15,50,0.50);
    animation: circleGlow 4.5s ease-in-out infinite;
    overflow: hidden;
}
.hero-eyebrow {
    font-family: var(--font-display); font-weight: 900;
    font-size: clamp(8px, 1.1vw, 12px);
    letter-spacing: clamp(2px, 0.5vw, 6px); text-transform: uppercase; color: #fce8be;
    margin-bottom: clamp(6px, 1vw, 12px);
    white-space: nowrap;
}
.hero-h1 {
    font-family: var(--font-display);
    font-size: clamp(0.9rem, 2.2vw, 2.1rem); font-weight: 900;
    color: var(--white); line-height: 1.22; margin-bottom: clamp(8px, 1vw, 14px); letter-spacing: -0.5px;
}
.hero-h1 .gold-text {
    background: linear-gradient(to right, #ffe9b9, #ffffff);
    -webkit-background-clip: text; -webkit-text-fill-color: transparent; background-clip: text;
}
.hero-body {
    font-size: clamp(9px, 1vw, 13.5px); color: rgba(255,255,255,0.90);
    line-height: 1.65; margin-bottom: clamp(12px, 1.5vw, 22px);
}
.hero-cta-stack {
    display: flex; flex-direction: column; gap: clamp(7px, 1vw, 12px);
    width: 100%; max-width: 300px;
}
.hero-btn-red {
    display: inline-flex; align-items: center; justify-content: center; gap: 7px;
    background: var(--crimson); color: #fff !important;
    padding: clamp(8px, 1.2vw, 14px) clamp(12px, 2vw, 24px);
    font-weight: 800; border-radius: 30px; font-size: clamp(0.6rem, 0.9vw, 0.82rem);
    letter-spacing: 1px; text-transform: uppercase; text-decoration: none;
    border: none; transition: all 0.3s; box-shadow: 0 6px 18px rgba(188,0,45,0.35);
}
.hero-btn-red:hover { background: var(--crimson-dk); color: #fff !important; transform: translateY(-2px); }
.hero-btn-ghost {
    display: inline-flex; align-items: center; justify-content: center; gap: 7px;
    background: rgba(255,223,145,0.14); color: #fff5dc !important;
    padding: clamp(8px, 1.2vw, 14px) clamp(12px, 2vw, 24px);
    font-weight: 800; border-radius: 30px; font-size: clamp(0.6rem, 0.9vw, 0.82rem);
    letter-spacing: 1px; text-transform: uppercase; text-decoration: none;
    border: 2px solid rgba(255,223,145,0.65); transition: all 0.3s;
}
.hero-btn-ghost:hover { background: rgba(255,223,145,0.28); color: #fff5dc !important; }

/* Hero Phones */
.hero-phones { position: relative; order: 2; width: 48%; min-height: 520px; z-index: 2; }
.hero-tablet {
    position: absolute; left: 0; bottom: 56px;
    width: 248px; height: 356px; border-radius: 24px;
    background: linear-gradient(165deg, #f7f8fa 0%, #dfe3e8 45%, #f3f4f6 100%);
    border: 2px solid #aab2bc;
    box-shadow: 0 22px 40px rgba(0,0,0,0.22), inset 0 0 0 1px rgba(255,255,255,0.44);
    animation: float2 6s ease-in-out infinite; overflow: hidden; transform: rotate(-2deg);
}
.hero-phone {
    position: absolute; right: 1%; bottom: 0;
    width: 260px; height: 508px; border-radius: 38px;
    background: linear-gradient(165deg, #fdfdfd 0%, #e7e9ed 40%, #f6f7f8 100%);
    border: 2px solid #b6bdc5;
    box-shadow: 0 28px 50px rgba(15,28,44,0.26), inset 0 0 0 1px rgba(255,255,255,0.48);
    animation: float1 4.8s ease-in-out infinite; overflow: hidden;
}

/* Floating Badges */
.float-badge {
    position: absolute; background: var(--white);
    border-radius: var(--radius-md); padding: 10px 14px;
    box-shadow: 0 10px 28px rgba(0,0,0,0.12);
    display: flex; align-items: center; gap: 10px; z-index: 10;
}
.float-badge.fb-1 { top: 0; left: -10px; animation: floatBob 4s ease-in-out infinite; }
.float-badge.fb-2 { bottom: 0; right: -10px; animation: floatBob 4s ease-in-out infinite 2s; }
.fb-icon { width: 36px; height: 36px; border-radius: 10px; display: flex; align-items: center; justify-content: center; flex-shrink: 0; }
.fb-val { font-family: var(--font-display); font-size: 15px; font-weight: 800; color: var(--navy); line-height: 1; }
.fb-lbl { font-size: 10px; color: var(--muted); font-weight: 600; margin-top: 1px; }

@keyframes float1 { 0%,100%{transform:translateY(0) rotate(0deg)} 50%{transform:translateY(-12px) rotate(0.4deg)} }
@keyframes float2 { 0%,100%{transform:translateY(0) rotate(-2deg)} 50%{transform:translateY(-18px) rotate(-2deg)} }
@keyframes floatBob { 0%,100%{transform:translateY(0)} 50%{transform:translateY(-8px)} }
@keyframes circleGlow {
    0%,100%{ box-shadow:0 0 0 12px rgba(243,206,132,0.12), 0 0 0 24px rgba(243,206,132,0.06), 0 28px 80px rgba(0,15,50,0.50); }
    50%    { box-shadow:0 0 0 18px rgba(243,206,132,0.18), 0 0 0 36px rgba(243,206,132,0.10), 0 28px 80px rgba(0,15,50,0.54); }
}
@keyframes fadeInUp { from{opacity:0;transform:translateY(24px)} to{opacity:1;transform:translateY(0)} }

/* ─────────────────────────────────────────────────────────────
   7. BENEFIT CARDS
──────────────────────────────────────────────────────────────── */
.benefit-belt { padding: 0 18px 0; background: var(--white); }
.benefit-grid { display: grid; grid-template-columns: repeat(4,1fr); gap: 14px; max-width: 1280px; margin: 0 auto; padding-bottom: 16px; }
.benefit-card {
    border: 2px solid var(--gold); border-radius: var(--radius-lg); padding: 22px 18px;
    background: linear-gradient(145deg, #f8fbff 0%, #eef4ff 100%);
    box-shadow: var(--shadow-sm); transition: transform 0.28s, box-shadow 0.28s;
    animation: fadeInUp 0.6s ease-out both;
}
.benefit-card:nth-child(2){animation-delay:.07s} .benefit-card:nth-child(3){animation-delay:.14s} .benefit-card:nth-child(4){animation-delay:.21s}
.benefit-card:hover { transform: translateY(-6px); box-shadow: var(--shadow-md); }
.benefit-card h6 { font-family: var(--font-display); font-size: 1.05rem; font-weight: 800; color: var(--navy); margin: 0 0 8px; }
.benefit-card p  { font-size: 0.9rem; line-height: 1.6; color: #1e3f77; margin: 0; }

/* ─────────────────────────────────────────────────────────────
   8. STATS ROW
──────────────────────────────────────────────────────────────── */
.stats-section { background: var(--white); border-bottom: 1px solid var(--border); padding: 50px 0; }
.stat-box h2 { font-family: var(--font-display); font-size: clamp(2rem, 4vw, 2.8rem); font-weight: 900; color: var(--navy); margin: 0; transition: transform 0.3s; }
.stat-box h2:hover { transform: scale(1.06); }
.stat-box p { font-size: 0.72rem; font-weight: 800; text-transform: uppercase; letter-spacing: 2px; color: var(--muted); margin: 6px 0 0; }

/* ─────────────────────────────────────────────────────────────
   9. FEATURE SECTIONS
──────────────────────────────────────────────────────────────── */
/* Dashboard Mockup Shell */
.db-frame {
    background: var(--white); border-radius: var(--radius-lg);
    box-shadow: var(--shadow-lg); overflow: hidden;
    transform: perspective(1200px) rotateY(-4deg) rotateX(2deg);
    transition: transform 0.5s ease, box-shadow 0.5s ease;
}
.db-frame:hover { transform: perspective(1200px) rotateY(0deg) rotateX(0deg); box-shadow: 0 40px 80px rgba(0,35,71,0.18); }
.db-frame-r { transform: perspective(1200px) rotateY(4deg) rotateX(2deg); }
.db-frame-r:hover { transform: perspective(1200px) rotateY(0deg) rotateX(0deg); }

.db-bar { background: #f5f7fa; padding: 11px 18px; display: flex; align-items: center; gap: 7px; border-bottom: 1px solid #e8ecf0; }
.db-dot { width: 11px; height: 11px; border-radius: 50%; }
.db-dot-r{background:#ff5f57} .db-dot-y{background:#ffbd2e} .db-dot-g{background:#28c840}
.db-bar-title { margin-left: 10px; font-size: 11px; font-weight: 600; color: #8a92a0; letter-spacing: 0.5px; }
.db-sidebar { background: var(--navy); width: 50px; padding: 16px 0; display: flex; flex-direction: column; align-items: center; gap: 16px; flex-shrink: 0; }
.db-icon { width: 30px; height: 30px; border-radius: 8px; background: rgba(255,255,255,0.08); display: flex; align-items: center; justify-content: center; }
.db-icon.on { background: var(--gold); }
.db-icon svg { width: 13px; height: 13px; }
.db-body { flex: 1; padding: 18px; overflow: hidden; }
.db-head { display: flex; align-items: center; justify-content: space-between; margin-bottom: 16px; }
.db-title { font-family: var(--font-display); font-size: 13px; font-weight: 700; color: var(--navy); }
.db-badge { background: var(--surface-2); color: var(--navy); font-size: 10px; font-weight: 700; padding: 4px 10px; border-radius: 20px; letter-spacing: 0.5px; }
.db-kpi-row { display: grid; grid-template-columns: repeat(3,1fr); gap: 9px; margin-bottom: 14px; }
.db-kpi { background: var(--surface-2); border: 1px solid var(--border); border-radius: 10px; padding: 11px 12px; position: relative; overflow: hidden; }
.db-kpi::before { content:''; position:absolute; top:0; left:0; width:3px; height:100%; background: var(--gold); }
.db-kpi-lbl { font-size: 8.5px; font-weight: 700; text-transform: uppercase; letter-spacing: 1px; color: var(--muted); margin-bottom: 5px; }
.db-kpi-val { font-family: var(--font-display); font-size: 17px; font-weight: 800; color: var(--navy); line-height: 1; }
.db-kpi-sub { font-size: 8.5px; color: #22c55e; font-weight: 700; margin-top: 4px; }
.db-kpi-sub.neg { color: #ef4444; }
.db-chart-2col { display: grid; grid-template-columns: 1.6fr 1fr; gap: 9px; margin-bottom: 14px; }
.db-chart-box { background: var(--white); border: 1px solid var(--border); border-radius: 10px; padding: 12px; }
.db-chart-lbl { font-size: 9.5px; font-weight: 700; color: var(--navy); margin-bottom: 10px; text-transform: uppercase; letter-spacing: 0.5px; }

.db-table { width: 100%; border-collapse: collapse; font-size: 9.5px; }
.db-table th { text-align: left; padding: 6px 8px; font-size: 8.5px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.5px; color: var(--muted); border-bottom: 1px solid var(--border); }
.db-table td { padding: 7px 8px; border-bottom: 1px solid #f0f4f8; color: #3d4a5c; font-weight: 500; }
.db-table tr:last-child td { border-bottom: none; }
.db-pill { display: inline-block; padding: 2px 8px; border-radius: 20px; font-size: 8px; font-weight: 700; }
.db-pill.paid    { background: #dcfce7; color: #15803d; }
.db-pill.pending { background: #fef9c3; color: #854d0e; }
.db-pill.due     { background: #fee2e2; color: #991b1b; }

/* Feature benefit cards */
.feat-card {
    background: var(--white); border: 1px solid var(--border);
    border-radius: var(--radius-md); padding: 14px 16px;
    transition: all 0.3s; position: relative; overflow: hidden;
}
.feat-card::before { content:''; position:absolute; top:0; left:0; right:0; height:2px; background:linear-gradient(to right, var(--navy), var(--gold)); transform:scaleX(0); transform-origin:left; transition:transform 0.3s; }
.feat-card:hover { transform: translateY(-4px); box-shadow: var(--shadow-md); }
.feat-card:hover::before { transform: scaleX(1); }
.feat-card-dark {
    background: rgba(255,255,255,0.06); border: 1px solid rgba(255,255,255,0.10);
    border-radius: var(--radius-md); padding: 16px 18px; transition: all 0.3s;
}
.feat-card-dark:hover { background: rgba(255,255,255,0.10); border-color: rgba(197,160,89,0.35); }
.feat-icon { width: 42px; height: 42px; border-radius: 11px; background: var(--surface-3); display: flex; align-items: center; justify-content: center; flex-shrink: 0; transition: 0.3s; }
.feat-card:hover .feat-icon, .feat-card-dark:hover .feat-icon { background: var(--gold); }
.feat-icon svg { width: 20px; height: 20px; }
.feat-icon-dark { background: rgba(197,160,89,0.18); }
.feat-icon-dark svg { stroke: var(--gold); }
.feat-card h6 { font-family: var(--font-display); font-size: 13.5px; font-weight: 700; color: var(--navy); margin: 0 0 3px; }
.feat-card p  { font-size: 12px; color: var(--muted); line-height: 1.65; margin: 0; }
.feat-card-dark h6 { color: var(--white); }
.feat-card-dark p  { color: rgba(255,255,255,0.62); }

/* Progress bars */
.prog-row { margin-bottom: 13px; }
.prog-labels { display: flex; justify-content: space-between; font-family: var(--font-display); font-size: 12px; font-weight: 700; color: var(--navy); margin-bottom: 6px; }
.prog-track { height: 8px; background: var(--surface-3); border-radius: 99px; overflow: hidden; }
.prog-fill { height: 100%; border-radius: 99px; background: linear-gradient(to right, var(--navy), var(--gold)); width: 0; transition: width 1.5s cubic-bezier(0.4,0,0.2,1); }
.prog-fill.go { width: var(--w); }

/* ─────────────────────────────────────────────────────────────
   10. STRIP SECTION (6 Cards)
──────────────────────────────────────────────────────────────── */
.strip-section { background: linear-gradient(135deg, var(--surface-2) 0%, var(--white) 100%); border-top: 1px solid var(--border); border-bottom: 1px solid var(--border); padding: 80px 0; }
.strip-card {
    background: var(--white); border: 1px solid var(--border);
    border-radius: var(--radius-lg); padding: 30px 26px; height: 100%;
    position: relative; overflow: hidden; transition: all 0.4s cubic-bezier(.175,.885,.32,1.275);
}
.strip-card::before { content:''; position:absolute; top:0; left:0; right:0; height:3px; background:linear-gradient(to right, var(--navy), var(--gold)); transform:scaleX(0); transform-origin:left; transition:transform 0.4s; }
.strip-card:hover { transform: translateY(-8px); box-shadow: var(--shadow-lg); }
.strip-card:hover::before { transform: scaleX(1); }
.strip-icon { width: 54px; height: 54px; border-radius: var(--radius-md); display: flex; align-items: center; justify-content: center; margin-bottom: 18px; font-size: 22px; }
.strip-card h5 { font-family: var(--font-display); font-size: 15px; font-weight: 800; color: var(--navy); margin: 0 0 10px; }
.strip-card p  { font-size: 13px; color: var(--muted); line-height: 1.75; margin: 0; }

/* ─────────────────────────────────────────────────────────────
   11. SOLUTIONS TILES
──────────────────────────────────────────────────────────────── */
.sol-grid { display: grid; grid-template-columns: repeat(auto-fit,minmax(270px,1fr)); gap: 28px; }
.sol-tile {
    background: var(--surface-2); border: 1px solid var(--border);
    border-radius: var(--radius-md); padding: 42px 24px;
    transition: all 0.5s cubic-bezier(.175,.885,.32,1.275); height: 100%;
    overflow: hidden; position: relative;
}
.sol-tile::after { content:''; position:absolute; bottom:0; left:0; width:0; height:4px; background:var(--gold); transition:width 0.4s ease; }
.sol-tile:hover { background: var(--white); transform: translateY(-10px); box-shadow: var(--shadow-lg); border-color: var(--gold); }
.sol-tile:hover::after { width: 100%; }
.sol-tile i { transition: all 0.4s; }
.sol-tile:hover i { transform: scale(1.18) rotate(4deg); color: var(--gold) !important; }

/* ─────────────────────────────────────────────────────────────
   12. CAPABILITIES
──────────────────────────────────────────────────────────────── */
.cap-img {
    padding: 12px; background: var(--white);
    border: 2px solid var(--gold); border-radius: var(--radius-md); overflow: hidden;
    transition: all 0.4s; position: relative;
}
.cap-img::before { content:''; position:absolute; top:-6px; left:-6px; right:-6px; bottom:-6px; border:1px solid rgba(197,160,89,0.28); border-radius: var(--radius-md); pointer-events:none; }
.cap-img:hover { transform: scale(1.02) rotate(0.8deg); box-shadow: var(--shadow-lg); }
.cap-img img { border-radius: 4px; transition: transform 0.4s; width: 100%; }
.cap-img:hover img { transform: scale(1.04); }

/* ─────────────────────────────────────────────────────────────
   13. TEAM / PROJECTS
──────────────────────────────────────────────────────────────── */
.project-card { border: 1px solid var(--border); background: var(--white); border-radius: var(--radius-md); overflow: hidden; transition: all 0.4s; height: 100%; box-shadow: var(--shadow-sm); }
.project-card:hover { transform: translateY(-8px); box-shadow: var(--shadow-lg); }
.project-img { height: 380px; overflow: hidden; border-bottom: 4px solid var(--gold); }
.project-img img { width: 100%; height: 100%; object-fit: cover; transition: all 0.6s; filter: grayscale(20%); }
.project-card:hover img { transform: scale(1.07); filter: grayscale(0%); }

/* ─────────────────────────────────────────────────────────────
   14. TESTIMONIALS
──────────────────────────────────────────────────────────────── */
.testi-section { padding: 100px 0; overflow: hidden; position: relative; }
.testi-track-wrap { overflow: hidden; }
.testi-track { display: flex; gap: 22px; animation: scrollInfinite 50s linear infinite; width: max-content; }
.testi-track:hover { animation-play-state: paused; }
@keyframes scrollInfinite { 0%{transform:translateX(0)} 100%{transform:translateX(-50%)} }
.testi-card {
    width: 340px; flex-shrink: 0;
    background: rgba(255,255,255,0.05); border: 1px solid rgba(197,160,89,0.35);
    padding: 32px; border-radius: var(--radius-md); backdrop-filter: blur(10px);
    transition: all 0.3s;
}
.testi-card:hover { background: rgba(255,255,255,0.08); border-color: var(--gold); transform: translateY(-5px); }
.testi-avatar { width: 44px; height: 44px; border-radius: 50%; object-fit: cover; border: 2px solid var(--gold); }

/* ─────────────────────────────────────────────────────────────
   15. PRICING / LICENSING
──────────────────────────────────────────────────────────────── */
.plan-card {
    border: 1px solid var(--border); background: var(--white);
    padding: 42px 28px; border-radius: var(--radius-md); height: 100%;
    display: flex; flex-direction: column; transition: all 0.4s;
    border-top: 3px solid transparent; box-shadow: var(--shadow-sm);
}
.plan-card:hover:not(.plan-featured) { border-top-color: var(--gold); transform: translateY(-7px); box-shadow: var(--shadow-lg); }
.plan-featured {
    border: 2px solid var(--gold); background: var(--surface-2);
    border-top-color: var(--gold); transform: scale(1.03);
    box-shadow: var(--shadow-gold);
}
.plan-name { font-family: var(--font-display); font-size: 0.7rem; font-weight: 800; text-transform: uppercase; letter-spacing: 1.5px; color: var(--muted); margin-bottom: 16px; text-align: center; }
.plan-price { font-family: var(--font-display); font-size: clamp(1.8rem,3vw,2.2rem); font-weight: 900; color: var(--navy); text-align: center; margin-bottom: 32px; }
.plan-feature { display: flex; align-items: flex-start; gap: 10px; margin-bottom: 12px; font-size: 13px; color: var(--muted); }
.plan-feature i { color: #22c55e; margin-top: 2px; flex-shrink: 0; }

/* ─────────────────────────────────────────────────────────────
   16. FOOTER
──────────────────────────────────────────────────────────────── */
.sb-footer {
    background: var(--surface-2); padding: 96px 0 40px;
    border-top: 5px solid var(--gold); position: relative; overflow: hidden;
}
.sb-footer::before {
    content: ''; position: absolute; inset: 0;
    background: repeating-linear-gradient(45deg, transparent, transparent 48px, rgba(197,160,89,0.04) 48px, rgba(197,160,89,0.04) 50px);
    pointer-events: none;
}
.footer-link { color: var(--muted); text-decoration: none; font-size: 13px; transition: all 0.2s; }
.footer-link:hover { color: var(--gold); transform: translateX(3px); display: inline-block; }
.footer-social { width: 38px; height: 38px; border-radius: 50%; background: rgba(0,35,71,0.06); display: flex; align-items: center; justify-content: center; color: var(--navy); transition: all 0.3s; text-decoration: none; }
.footer-social:hover { background: var(--gold); color: var(--white); transform: scale(1.1); }
.map-wrap { border: 10px solid var(--white); border-radius: var(--radius-md); box-shadow: var(--shadow-md); overflow: hidden; min-height: 480px; }

/* ─────────────────────────────────────────────────────────────
   17. RESPONSIVE
──────────────────────────────────────────────────────────────── */
@media (max-width: 991px) {
    :root { --nav-h: 72px; --announce-h: 36px; }
    .benefit-grid { grid-template-columns: repeat(2,1fr); }

    /* Stack hero vertically */
    .hero-inner {
        flex-direction: column;
        align-items: center;
        padding: 20px 16px 24px;
        gap: 16px;
    }
    /* Circle becomes full-width fluid square */
    .hero-circle {
        width: min(440px, 90vw) !important;
        height: min(440px, 90vw) !important;
        order: 1;
    }
    .hero-phones { width: 100%; min-height: 400px; order: 2; }
    .hero-tablet { width: 180px; height: 260px; left: 4%; bottom: 60px; }
    .hero-phone  { width: 200px; height: 390px; right: 8%; }

    /* Mobile nav dropdown — clean card */
    #mujiNav {
        background: rgba(255,255,255,0.99) !important;
        border: 1px solid var(--border) !important;
        border-radius: var(--radius-lg) !important;
        padding: 14px 10px !important;
        margin-top: 10px !important;
        box-shadow: 0 12px 40px rgba(0,35,71,0.14) !important;
        backdrop-filter: blur(20px) !important;
    }
    #mujiNav .nav-item { width: 100%; }
    #mujiNav .sb-nav-link {
        display: flex; align-items: center;
        padding: 12px 16px !important;
        border-radius: var(--radius-sm) !important;
        font-size: 0.82rem !important;
        color: var(--navy) !important;
        border-bottom: none !important;
        text-decoration: none !important;
    }
    #mujiNav .sb-nav-link:hover { background: rgba(28,102,232,0.06) !important; color: #1c66e8 !important; }
    #mujiNav .btn-portal { width: 100%; justify-content: center; margin-top: 10px; }
    #mujiNav .ms-lg-3 { margin-left: 0 !important; margin-top: 4px; }
}

@media (max-width: 768px) {
    :root { --announce-h: 34px; }
    .sb-section { padding: 70px 0; }
    .db-chart-2col { grid-template-columns: 1fr; }
    .db-kpi-row { grid-template-columns: repeat(2,1fr); }
    .db-kpi-row .db-kpi:last-child { display: none; }
    .announce-label { font-size: 0 !important; padding: 0 12px !important; gap: 0; }
    .announce-label .announce-dot { width: 7px; height: 7px; }
    .announce-msg { font-size: 0.65rem !important; white-space: nowrap; }
    /* Smaller phones on this breakpoint */
    .hero-circle {
        width: min(380px, 88vw) !important;
        height: min(380px, 88vw) !important;
    }
    .hero-phones { min-height: 340px; }
    .hero-tablet { width: 150px; height: 218px; left: 3%; bottom: 50px; }
    .hero-phone  { width: 170px; height: 330px; right: 6%; }
}

@media (max-width: 480px) {
    :root { --nav-h: 64px; --announce-h: 32px; }
    .benefit-grid { grid-template-columns: 1fr; }
    .hero-inner { padding: 14px 12px 18px; gap: 12px; border-radius: var(--radius-lg); }
    /* Circle scales to almost full screen width */
    .hero-circle {
        width: min(320px, 86vw) !important;
        height: min(320px, 86vw) !important;
    }
    .hero-phones { min-height: 280px; }
    .hero-tablet { width: 118px; height: 172px; left: 2%; bottom: 42px; }
    .hero-phone  { width: 138px; height: 270px; right: 4%; }
}

@media (max-width: 360px) {
    .hero-circle {
        width: min(288px, 84vw) !important;
        height: min(288px, 84vw) !important;
    }
    .hero-phones { min-height: 250px; }
    .hero-tablet { width: 100px; height: 148px; left: 1%; bottom: 36px; }
    .hero-phone  { width: 118px; height: 232px; right: 2%; }
}
</style>

{{-- ══════════════════════════════════════════════════════════
     NAVBAR
══════════════════════════════════════════════════════════ --}}
<nav class="navbar navbar-expand-lg fixed-top sb-nav" id="mainNav">
    <div class="container">
        <a class="sb-brand navbar-brand" href="#home">
            <img src="{{ asset('assets/img/smat12.png') }}" alt="SmatBook">
            <span class="sb-brand-text">SMAT<span class="b">BOOK</span></span>
        </a>
        <button class="navbar-toggler border-0" type="button" data-bs-toggle="collapse" data-bs-target="#mujiNav" style="color:var(--navy);">
            <i class="fas fa-bars"></i>
        </button>
        <div class="collapse navbar-collapse" id="mujiNav">
            <ul class="navbar-nav ms-auto align-items-center gap-1">
                <li class="nav-item"><a class="sb-nav-link {{ request()->routeIs('landing.index') ? 'active' : '' }}" href="{{ route('landing.index') }}#home">Home</a></li>
                <li class="nav-item"><a class="sb-nav-link {{ request()->routeIs('landing.about') ? 'active' : '' }}" href="{{ route('landing.about') }}">About</a></li>
                <li class="nav-item"><a class="sb-nav-link" href="{{ route('landing.index') }}#team">Projects</a></li>
                <li class="nav-item"><a class="sb-nav-link {{ request()->routeIs('landing.contact') ? 'active' : '' }}" href="{{ route('landing.contact') }}">Contact</a></li>
                <li class="nav-item"><a class="sb-nav-link" href="{{ route('landing.index') }}#licensing">Licensing</a></li>
                <li class="nav-item"><a class="sb-nav-link {{ request()->routeIs('landing.policy') ? 'active' : '' }}" href="{{ route('landing.policy') }}">Policy</a></li>
                <li class="nav-item ms-lg-3">
                    <a class="btn-portal" href="{{ route('saas-login') }}">
                        <i class="fas fa-lock" style="font-size:.75rem;"></i> CLIENT PORTAL
                    </a>
                </li>
            </ul>
        </div>
    </div>
</nav>

{{-- Announcement Bar --}}
<div class="announce-bar" id="announceBar">
    <div class="announce-label"><span class="announce-dot"></span> 📡 LIVE UPDATES</div>
    <div class="announce-track" id="announceTrack">
        <div class="announce-msg active" id="msg0"><i class="fas fa-star" style="color:var(--gold);font-size:.6rem;"></i> SmatBook v3.0 — Now with AI-powered payroll automation</div>
        <div class="announce-msg" id="msg1"><i class="fas fa-shield-alt" style="color:var(--gold);font-size:.6rem;"></i> ISO 27001 Certified · Your data is fully encrypted & secured</div>
        <div class="announce-msg" id="msg2"><i class="fas fa-bolt" style="color:var(--gold);font-size:.6rem;"></i> New: One-click FIRS VAT report generation · Try it today</div>
        <div class="announce-msg" id="msg3"><i class="fas fa-users" style="color:var(--gold);font-size:.6rem;"></i> Trusted by 60,000+ businesses across Africa & beyond</div>
    </div>
</div>

{{-- ══════════════════════════════════════════════════════════
     HERO
══════════════════════════════════════════════════════════ --}}
<section id="home" class="hero-wrap">
    <div class="hero-grid-bg"></div>
    <div class="hero-inner">
        {{-- Left: Circle --}}
        <div class="hero-circle">
            <div class="hero-eyebrow">SMAT BOOK</div>
            <h1 class="hero-h1">
                Run Your Business.<br>
                <span class="gold-text">Know Your Money.</span>
            </h1>
            <p class="hero-body">
                Built with an accounting-first workflow for sales, invoices, expenses, payroll and tax visibility.
            </p>
            <div class="hero-cta-stack">
                <a href="#licensing" class="hero-btn-red">
                    <i class="fas fa-shopping-cart"></i> Start Today
                </a>
                <a href="{{ route('saas-register', ['type'=>'manager']) }}" class="hero-btn-ghost">
                    <i class="fas fa-handshake"></i> Become a Partner
                </a>
            </div>
        </div>

        {{-- Right: Device mockups --}}
        <div class="hero-phones">
            {{-- Tablet --}}
            <div class="hero-tablet">
                <div style="position:absolute;top:8px;left:50%;transform:translateX(-50%);width:70px;height:7px;border-radius:10px;background:#39424c;"></div>
                <div style="position:absolute;inset:12px;border-radius:18px;overflow:hidden;border:1px solid #c8d0d9;background:#f4f9ff;">
                    <div style="height:88px;background:linear-gradient(120deg,#0f5ea7,#1f89dd);padding:10px;">
                        <div style="font-size:10px;color:#fff;font-weight:800;margin-bottom:8px;">Dashboard Highlights</div>
                        <div style="display:grid;grid-template-columns:repeat(3,1fr);gap:5px;">
                            <div style="height:22px;border-radius:7px;background:rgba(255,255,255,0.26);"></div>
                            <div style="height:22px;border-radius:7px;background:rgba(255,255,255,0.22);"></div>
                            <div style="height:22px;border-radius:7px;background:rgba(255,223,145,0.36);"></div>
                        </div>
                    </div>
                    <div style="padding:8px;">
                        <img src="{{ asset('assets/img/demo-one.png') }}"   alt="" style="width:100%;height:68px;object-fit:cover;border-radius:10px;margin-bottom:6px;">
                        <img src="{{ asset('assets/img/demo-two.png') }}"   alt="" style="width:100%;height:68px;object-fit:cover;border-radius:10px;margin-bottom:6px;">
                        <img src="{{ asset('assets/img/demo-three.png') }}" alt="" style="width:100%;height:68px;object-fit:cover;border-radius:10px;">
                    </div>
                </div>
            </div>

            {{-- Phone --}}
            <div class="hero-phone">
                <div style="position:absolute;top:10px;left:50%;transform:translateX(-50%);width:104px;height:10px;border-radius:10px;background:#1d2228;"></div>
                <div style="position:absolute;inset:16px;border-radius:33px;overflow:hidden;border:1px solid #cfd7df;background:#0c5fae;">
                    <div style="position:absolute;inset:0;background:linear-gradient(180deg,rgba(11,100,187,0.96),rgba(24,143,241,0.96) 38%,rgba(9,76,146,0.96));"></div>
                    <div style="position:absolute;top:14px;left:13px;right:13px;height:88px;border-radius:14px;background:rgba(255,255,255,0.18);padding:10px;">
                        <div style="font-size:11px;font-weight:800;color:#fff;font-family:var(--font-display);">Accounting Dashboard</div>
                        <div style="display:flex;gap:6px;margin-top:8px;"><span style="flex:1;height:5px;border-radius:6px;background:#d9ecff;display:block;"></span><span style="flex:1;height:5px;border-radius:6px;background:#b9deff;display:block;"></span></div>
                        <div style="display:flex;gap:5px;align-items:flex-end;height:28px;margin-top:9px;">
                            <span style="width:8px;height:13px;background:#e7f3ff;border-radius:5px;display:block;"></span>
                            <span style="width:8px;height:20px;background:#d7eaff;border-radius:5px;display:block;"></span>
                            <span style="width:8px;height:25px;background:#c2e1ff;border-radius:5px;display:block;"></span>
                            <span style="width:8px;height:17px;background:#a8d7ff;border-radius:5px;display:block;"></span>
                        </div>
                    </div>
                    <div style="position:absolute;left:13px;right:13px;top:115px;bottom:13px;border-radius:16px;background:#fff;overflow:hidden;padding:10px;">
                        <div style="height:70px;border-radius:12px;background:#f1f7ff;border:1px solid #d9e7f5;padding:8px;margin-bottom:8px;">
                            <div style="font-size:10px;color:#124782;font-weight:800;margin-bottom:5px;">Revenue Trend</div>
                            <svg viewBox="0 0 150 45" width="100%" height="70%">
                                <polyline points="0,34 20,30 38,32 60,22 84,24 106,16 130,20 150,12" fill="none" stroke="#0c62b7" stroke-width="3"/>
                                <polyline points="0,38 20,36 38,34 60,30 84,28 106,26 130,24 150,22" fill="none" stroke="#c5a059" stroke-width="2.5"/>
                            </svg>
                        </div>
                        <div style="display:grid;grid-template-columns:1fr 1fr;gap:7px;margin-bottom:8px;">
                            <div style="height:55px;border-radius:10px;background:#f7fbff;border:1px solid #e2edf8;padding:6px;"><div style="font-size:9px;color:#2f5c88;font-weight:700;">Invoices</div><div style="margin-top:6px;height:26px;border-radius:8px;background:#e7f2ff;"></div></div>
                            <div style="height:55px;border-radius:10px;background:#fffaf0;border:1px solid #f0dfbf;padding:6px;"><div style="font-size:9px;color:#7b5b24;font-weight:700;">Tax</div><div style="margin-top:6px;height:26px;border-radius:8px;background:#f8ebcf;"></div></div>
                        </div>
                        <div style="display:flex;flex-direction:column;gap:6px;">
                            <div style="height:20px;border-radius:8px;background:#eef6ff;"></div>
                            <div style="height:20px;border-radius:8px;background:#f5f9ff;"></div>
                            <div style="height:20px;border-radius:8px;background:#fff5df;"></div>
                            <div style="height:20px;border-radius:8px;background:#eef6ff;"></div>
                        </div>
                        <div style="display:grid;grid-template-columns:1fr 1fr;gap:6px;margin-top:8px;">
                            <img src="{{ asset('assets/img/demo-four.png') }}" alt="" style="width:100%;height:48px;object-fit:cover;border-radius:8px;">
                            <img src="{{ asset('assets/img/demo-five.png') }}" alt="" style="width:100%;height:48px;object-fit:cover;border-radius:8px;">
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

{{-- ══════════════════════════════════════════════════════════
     BENEFIT CARDS
══════════════════════════════════════════════════════════ --}}
<section class="benefit-belt">
    <div class="benefit-grid">
        <article class="benefit-card">
            <h6>Sales and Invoice Control</h6>
            <p>Track sales live, issue invoices faster, and keep receivables organized in one clear workflow.</p>
        </article>
        <article class="benefit-card">
            <h6>Expense and Cash Visibility</h6>
            <p>Capture expenses instantly and monitor cash movement with reliable accounting-first reports.</p>
        </article>
        <article class="benefit-card">
            <h6>Payroll and Tax Confidence</h6>
            <p>Run payroll accurately and generate tax-ready summaries without switching between tools.</p>
        </article>
        <article class="benefit-card">
            <h6>Multi-Device Operations</h6>
            <p>Work seamlessly across desktop, tablet, and mobile while your team stays in sync.</p>
        </article>
    </div>
</section>

{{-- ══════════════════════════════════════════════════════════
     STATS
══════════════════════════════════════════════════════════ --}}
<section class="stats-section">
    <div class="container">
        <div class="row text-center g-4">
            <div class="col-6 col-md-3"><div class="stat-box"><h2>60K+</h2><p>Entities Managed</p></div></div>
            <div class="col-6 col-md-3"><div class="stat-box"><h2>$12B+</h2><p>Annual Volume</p></div></div>
            <div class="col-6 col-md-3"><div class="stat-box"><h2>150+</h2><p>Global Nodes</p></div></div>
            <div class="col-6 col-md-3"><div class="stat-box"><h2>99.9%</h2><p>Uptime SLA</p></div></div>
        </div>
    </div>
</section>

{{-- ══════════════════════════════════════════════════════════
     FEATURES HEADER
══════════════════════════════════════════════════════════ --}}
<section class="sb-section" id="platform-features">
    <div class="container">
        <div class="text-center mb-5">
            <span class="sb-eyebrow" style="justify-content:center;display:inline-flex;">Platform Features</span>
            <h2 class="sb-h1 text-center">Everything your business needs, <span class="accent">built in.</span></h2>
            <p class="sb-lead text-center mx-auto">SmatBook is not just bookkeeping software. It's a complete business management system — from your first sale to your annual tax filing.</p>
        </div>

        {{-- FEATURE 1: Sales & Revenue --}}
        <div class="row align-items-center g-5 mb-5 pb-4">
            <div class="col-lg-7" data-aos="fade-right">
                <div class="position-relative" style="padding:24px 24px 24px 0;">
                    <div class="float-badge fb-1">
                        <div class="fb-icon" style="background:#dcfce7;"><svg width="16" height="16" fill="none" stroke="#15803d" stroke-width="2.5" viewBox="0 0 24 24"><polyline points="22 7 13.5 15.5 8.5 10.5 2 17"/><polyline points="16 7 22 7 22 13"/></svg></div>
                        <div><div class="fb-val">+24.8%</div><div class="fb-lbl">Monthly Revenue</div></div>
                    </div>
                    <div class="float-badge fb-2">
                        <div class="fb-icon" style="background:#fef9c3;"><svg width="16" height="16" fill="none" stroke="#b45309" stroke-width="2.5" viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg></div>
                        <div><div class="fb-val">Real-time</div><div class="fb-lbl">Live data sync</div></div>
                    </div>
                    <div class="db-frame">
                        <div class="db-bar"><div class="db-dot db-dot-r"></div><div class="db-dot db-dot-y"></div><div class="db-dot db-dot-g"></div><span class="db-bar-title">SmatBook — Financial Command Center</span></div>
                        <div class="d-flex" style="min-height:340px;">
                            <div class="db-sidebar">
                                <div class="db-icon on"><svg fill="none" stroke="#fff" stroke-width="2" viewBox="0 0 24 24"><rect x="3" y="3" width="7" height="7"/><rect x="14" y="3" width="7" height="7"/><rect x="3" y="14" width="7" height="7"/><rect x="14" y="14" width="7" height="7"/></svg></div>
                                <div class="db-icon"><svg fill="none" stroke="rgba(255,255,255,.45)" stroke-width="2" viewBox="0 0 24 24"><line x1="12" y1="2" x2="12" y2="22"/><path d="M17 5H9.5a3.5 3.5 0 000 7h5a3.5 3.5 0 010 7H6"/></svg></div>
                                <div class="db-icon"><svg fill="none" stroke="rgba(255,255,255,.45)" stroke-width="2" viewBox="0 0 24 24"><path d="M14 2H6a2 2 0 00-2 2v16a2 2 0 002 2h12a2 2 0 002-2V8z"/></svg></div>
                            </div>
                            <div class="db-body">
                                <div class="db-head"><span class="db-title">Financial Overview</span><span class="db-badge">LIVE · {{ date('M Y') }}</span></div>
                                <div class="db-kpi-row">
                                    <div class="db-kpi"><div class="db-kpi-lbl">Total Revenue</div><div class="db-kpi-val">₦4.2M</div><div class="db-kpi-sub">↑ 18.4% this month</div></div>
                                    <div class="db-kpi"><div class="db-kpi-lbl">Total Sales</div><div class="db-kpi-val">1,248</div><div class="db-kpi-sub">↑ 12.1% vs last</div></div>
                                    <div class="db-kpi"><div class="db-kpi-lbl">Outstanding</div><div class="db-kpi-val">₦380K</div><div class="db-kpi-sub neg">3 invoices due</div></div>
                                </div>
                                <div class="db-chart-2col">
                                    <div class="db-chart-box">
                                        <div class="db-chart-lbl">Monthly Revenue Trend</div>
                                        <svg viewBox="0 0 280 90" style="width:100%;">
                                            <defs><linearGradient id="ag" x1="0" y1="0" x2="0" y2="1"><stop offset="0%" stop-color="#002347" stop-opacity=".14"/><stop offset="100%" stop-color="#002347" stop-opacity="0"/></linearGradient></defs>
                                            <line x1="0" y1="20" x2="280" y2="20" stroke="#f0f4f8" stroke-width="1"/><line x1="0" y1="50" x2="280" y2="50" stroke="#f0f4f8" stroke-width="1"/><line x1="0" y1="75" x2="280" y2="75" stroke="#f0f4f8" stroke-width="1"/>
                                            <path d="M0,70 L35,55 L70,45 L105,50 L140,35 L175,40 L210,25 L245,30 L280,18 L280,90 L0,90 Z" fill="url(#ag)"/>
                                            <polyline points="0,70 35,55 70,45 105,50 140,35 175,40 210,25 245,30 280,18" fill="none" stroke="#002347" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"/>
                                            <circle cx="280" cy="18" r="4" fill="#c5a059" stroke="#fff" stroke-width="2"/>
                                            <text x="0" y="88" font-size="7" fill="#8a92a0">Jul</text><text x="68" y="88" font-size="7" fill="#8a92a0">Sep</text><text x="138" y="88" font-size="7" fill="#8a92a0">Nov</text><text x="208" y="88" font-size="7" fill="#8a92a0">Jan</text><text x="258" y="88" font-size="7" fill="#c5a059">Feb</text>
                                        </svg>
                                    </div>
                                    <div class="db-chart-box">
                                        <div class="db-chart-lbl">Revenue Split</div>
                                        <svg viewBox="0 0 100 100" style="width:100%;max-height:100px;">
                                            <circle cx="50" cy="50" r="36" fill="none" stroke="#f0f4f8" stroke-width="16"/>
                                            <circle cx="50" cy="50" r="36" fill="none" stroke="#002347" stroke-width="16" stroke-dasharray="113 113" stroke-dashoffset="28" transform="rotate(-90 50 50)"/>
                                            <circle cx="50" cy="50" r="36" fill="none" stroke="#c5a059" stroke-width="16" stroke-dasharray="45 181" stroke-dashoffset="-85" transform="rotate(-90 50 50)"/>
                                            <circle cx="50" cy="50" r="26" fill="white"/>
                                            <text x="50" y="47" text-anchor="middle" font-size="11" font-weight="800" fill="#002347">68%</text>
                                            <text x="50" y="56" text-anchor="middle" font-size="7" fill="#8a92a0">Sales</text>
                                        </svg>
                                    </div>
                                </div>
                                <table class="db-table">
                                    <tr><th>Customer</th><th>Amount</th><th>Status</th></tr>
                                    <tr><td>Adaobi Nwosu</td><td>₦85,000</td><td><span class="db-pill paid">Paid</span></td></tr>
                                    <tr><td>TechBridge Ltd</td><td>₦240,000</td><td><span class="db-pill pending">Pending</span></td></tr>
                                    <tr><td>Kalu Stores</td><td>₦62,500</td><td><span class="db-pill paid">Paid</span></td></tr>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-lg-5" data-aos="fade-left">
                <span class="sb-eyebrow">01 — Sales & Revenue</span>
                <h2 class="sb-h1">Know exactly <span class="accent">where every naira</span> is going</h2>
                <p class="sb-lead">Get a live, bird's-eye view of your business finances. SmatBook's revenue dashboard gives you instant clarity on sales performance, outstanding invoices, and profit trends — all on one screen.</p>
                <div class="d-flex flex-column gap-3 mt-4">
                    <div class="feat-card"><div class="d-flex align-items-start gap-3"><div class="feat-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="22 7 13.5 15.5 8.5 10.5 2 17"/><polyline points="16 7 22 7 22 13"/></svg></div><div><h6>Live Revenue Tracking</h6><p>See your sales totals update in real time as transactions happen across your business locations.</p></div></div></div>
                    <div class="feat-card"><div class="d-flex align-items-start gap-3"><div class="feat-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 00-2 2v16a2 2 0 002 2h12a2 2 0 002-2V8z"/><polyline points="14 2 14 8 20 8"/></svg></div><div><h6>Instant Invoice Management</h6><p>Generate, send, and track invoices automatically. Get notified the moment a client pays or a payment goes overdue.</p></div></div></div>
                    <div class="feat-card"><div class="d-flex align-items-start gap-3"><div class="feat-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="2" y="3" width="20" height="14" rx="2"/><line x1="8" y1="21" x2="16" y2="21"/><line x1="12" y1="17" x2="12" y2="21"/></svg></div><div><h6>Multi-Currency Support</h6><p>Record and report in NGN, USD, GBP, EUR and more — perfect for businesses with international clients.</p></div></div></div>
                </div>
            </div>
        </div>

        {{-- FEATURE 2: Inventory --}}
        <div class="row align-items-center g-5 pt-4">
            <div class="col-lg-5" data-aos="fade-right">
                <span class="sb-eyebrow">02 — Inventory Control</span>
                <h2 class="sb-h1">Never run out of <span class="accent">stock again</span></h2>
                <p class="sb-lead">SmatBook's inventory engine monitors every product in your store in real time. Set reorder thresholds, track expiry dates, and get alerts before stock runs dry.</p>
                <div class="mt-4">
                    <div class="prog-row"><div class="prog-labels"><span>Stock Accuracy</span><span>98.4%</span></div><div class="prog-track"><div class="prog-fill" style="--w:98.4%;"></div></div></div>
                    <div class="prog-row"><div class="prog-labels"><span>Waste Reduction</span><span>76%</span></div><div class="prog-track"><div class="prog-fill" style="--w:76%;"></div></div></div>
                    <div class="prog-row"><div class="prog-labels"><span>Reorder Automation</span><span>89%</span></div><div class="prog-track"><div class="prog-fill" style="--w:89%;"></div></div></div>
                </div>
                <div class="d-flex flex-column gap-3 mt-4">
                    <div class="feat-card"><div class="d-flex align-items-start gap-3"><div class="feat-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 16V8a2 2 0 00-1-1.73l-7-4a2 2 0 00-2 0l-7 4A2 2 0 003 8v8a2 2 0 001 1.73l7 4a2 2 0 002 0l7-4A2 2 0 0021 16z"/></svg></div><div><h6>Smart Reorder Alerts</h6><p>Automated low-stock notifications so your team restocks before customers notice an empty shelf.</p></div></div></div>
                    <div class="feat-card"><div class="d-flex align-items-start gap-3"><div class="feat-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 15s1-1 4-1 5 2 8 2 4-1 4-1V3s-1 1-4 1-5-2-8-2-4 1-4 1z"/><line x1="4" y1="22" x2="4" y2="15"/></svg></div><div><h6>Expiry Date Tracking</h6><p>Tag perishable items with expiry dates — SmatBook flags them before they become a liability.</p></div></div></div>
                    <div class="feat-card"><div class="d-flex align-items-start gap-3"><div class="feat-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M1 3h15v13H1z"/><path d="M16 8h4l3 3v5h-7V8z"/><circle cx="5.5" cy="18.5" r="2.5"/><circle cx="18.5" cy="18.5" r="2.5"/></svg></div><div><h6>Supplier Management</h6><p>Keep a database of all your suppliers with pricing history, lead times, and contact details for quick reordering.</p></div></div></div>
                </div>
            </div>
            <div class="col-lg-7" data-aos="fade-left">
                <div class="position-relative" style="padding:24px 0 24px 24px;">
                    <div class="float-badge" style="top:0;right:-10px;animation:floatBob 4s ease-in-out infinite;">
                        <div class="fb-icon" style="background:#fee2e2;"><svg width="16" height="16" fill="none" stroke="#dc2626" stroke-width="2.5" viewBox="0 0 24 24"><path d="M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z"/><line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg></div>
                        <div><div class="fb-val">3 Items</div><div class="fb-lbl">Low stock alert</div></div>
                    </div>
                    <div class="db-frame db-frame-r">
                        <div class="db-bar"><div class="db-dot db-dot-r"></div><div class="db-dot db-dot-y"></div><div class="db-dot db-dot-g"></div><span class="db-bar-title">SmatBook — Inventory Management</span></div>
                        <div class="d-flex" style="min-height:360px;">
                            <div class="db-sidebar">
                                <div class="db-icon"><svg fill="none" stroke="rgba(255,255,255,.45)" stroke-width="2" viewBox="0 0 24 24"><rect x="3" y="3" width="7" height="7"/><rect x="14" y="3" width="7" height="7"/><rect x="3" y="14" width="7" height="7"/><rect x="14" y="14" width="7" height="7"/></svg></div>
                                <div class="db-icon on"><svg fill="none" stroke="#fff" stroke-width="2" viewBox="0 0 24 24"><path d="M21 16V8a2 2 0 00-1-1.73l-7-4a2 2 0 00-2 0l-7 4A2 2 0 003 8v8a2 2 0 001 1.73l7 4a2 2 0 002 0l7-4A2 2 0 0021 16z"/></svg></div>
                            </div>
                            <div class="db-body">
                                <div class="db-head"><span class="db-title">Inventory Overview</span><span class="db-badge">482 SKUs</span></div>
                                <div class="db-kpi-row">
                                    <div class="db-kpi"><div class="db-kpi-lbl">Total SKUs</div><div class="db-kpi-val">482</div><div class="db-kpi-sub">↑ 12 added today</div></div>
                                    <div class="db-kpi"><div class="db-kpi-lbl">Stock Value</div><div class="db-kpi-val">₦2.1M</div><div class="db-kpi-sub">↑ 5.4% this week</div></div>
                                    <div class="db-kpi"><div class="db-kpi-lbl">Low Stock</div><div class="db-kpi-val" style="color:#ef4444;">3</div><div class="db-kpi-sub neg">Needs restocking</div></div>
                                </div>
                                <div class="db-chart-box" style="margin-bottom:10px;">
                                    <div class="db-chart-lbl">Top Products — Stock Levels</div>
                                    @php $products=[['Paracetamol 500mg',87,'#002347'],['Vitamin C Tabs',62,'#c5a059'],['Amoxicillin 250mg',18,'#ef4444'],['Ibuprofen 400mg',74,'#002347'],['Zinc Sulphate',9,'#ef4444']]; @endphp
                                    @foreach($products as $p)
                                    <div style="margin-bottom:7px;"><div style="display:flex;justify-content:space-between;font-size:10px;margin-bottom:3px;font-weight:600;color:#3d4a5c;"><span>{{ $p[0] }}</span><span>{{ $p[1] }} units</span></div><div style="height:6px;background:#f0f4f8;border-radius:99px;overflow:hidden;"><div style="width:{{ $p[1] }}%;height:100%;background:{{ $p[2] }};border-radius:99px;"></div></div></div>
                                    @endforeach
                                </div>
                                <table class="db-table">
                                    <tr><th>Product</th><th>Category</th><th>Qty</th><th>Status</th></tr>
                                    <tr><td>Paracetamol 500mg</td><td>Pharma</td><td>87</td><td><span class="db-pill paid">OK</span></td></tr>
                                    <tr><td>Zinc Sulphate</td><td>Vitamins</td><td>9</td><td><span class="db-pill due">Low</span></td></tr>
                                    <tr><td>Vitamin C Tabs</td><td>Vitamins</td><td>62</td><td><span class="db-pill paid">OK</span></td></tr>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

{{-- ══════════════════════════════════════════════════════════
     FEATURE 3: Expenses — DARK
══════════════════════════════════════════════════════════ --}}
<section class="sb-section sb-section--dark" id="expenses">
    <div class="container">
        <div class="row align-items-center g-5">
            <div class="col-lg-7" data-aos="fade-right">
                <div class="position-relative" style="padding:24px 24px 24px 0;">
                    <div class="float-badge" style="top:-10px;left:10px;animation:floatBob 4s ease-in-out infinite;">
                        <div class="fb-icon" style="background:#ede9fe;"><svg width="16" height="16" fill="none" stroke="#7c3aed" stroke-width="2.5" viewBox="0 0 24 24"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg></div>
                        <div><div class="fb-val">Auto</div><div class="fb-lbl">Bank reconciled</div></div>
                    </div>
                    <div class="db-frame">
                        <div class="db-bar"><div class="db-dot db-dot-r"></div><div class="db-dot db-dot-y"></div><div class="db-dot db-dot-g"></div><span class="db-bar-title">SmatBook — Expenses & Reports</span></div>
                        <div class="db-body">
                            <div class="db-head"><span class="db-title">P&L + Reports</span><span class="db-badge" style="background:#f0fff4;color:#15803d;">✓ AUTO-GENERATED</span></div>
                            <div class="db-kpi-row">
                                <div class="db-kpi"><div class="db-kpi-lbl">Total Expenses</div><div class="db-kpi-val">₦1.4M</div><div class="db-kpi-sub neg">↑ 6.2% vs last</div></div>
                                <div class="db-kpi"><div class="db-kpi-lbl">Net Profit</div><div class="db-kpi-val">₦2.8M</div><div class="db-kpi-sub">↑ 22.4% margin</div></div>
                                <div class="db-kpi"><div class="db-kpi-lbl">Reports Ready</div><div class="db-kpi-val">6</div><div class="db-kpi-sub">For this month</div></div>
                            </div>
                            <div class="db-chart-box" style="margin-bottom:12px;">
                                <div class="db-chart-lbl">Revenue vs Expenses — 12 Month View</div>
                                <svg viewBox="0 0 500 100" style="width:100%;">
                                    <defs>
                                        <linearGradient id="r2" x1="0" y1="0" x2="0" y2="1"><stop offset="0%" stop-color="#002347" stop-opacity=".18"/><stop offset="100%" stop-color="#002347" stop-opacity="0"/></linearGradient>
                                        <linearGradient id="e2" x1="0" y1="0" x2="0" y2="1"><stop offset="0%" stop-color="#c5a059" stop-opacity=".14"/><stop offset="100%" stop-color="#c5a059" stop-opacity="0"/></linearGradient>
                                    </defs>
                                    @foreach([15,40,65,90] as $gy)<line x1="0" y1="{{ $gy }}" x2="500" y2="{{ $gy }}" stroke="#f0f4f8" stroke-width="1"/>@endforeach
                                    <path d="M0,80 L42,65 L84,58 L126,62 L168,50 L210,45 L252,48 L294,35 L336,38 L378,28 L420,25 L462,18 L500,12 L500,100 L0,100 Z" fill="url(#r2)"/>
                                    <polyline points="0,80 42,65 84,58 126,62 168,50 210,45 252,48 294,35 336,38 378,28 420,25 462,18 500,12" fill="none" stroke="#002347" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"/>
                                    <path d="M0,95 L42,90 L84,85 L126,92 L168,82 L210,78 L252,84 L294,75 L336,79 L378,70 L420,67 L462,62 L500,58 L500,100 L0,100 Z" fill="url(#e2)"/>
                                    <polyline points="0,95 42,90 84,85 126,92 168,82 210,78 252,84 294,75 336,79 378,70 420,67 462,62 500,58" fill="none" stroke="#c5a059" stroke-width="2" stroke-dasharray="6,3" stroke-linecap="round" stroke-linejoin="round"/>
                                    @php $months=['M','A','M','J','J','A','S','O','N','D','J','F']; @endphp
                                    @foreach($months as $mi=>$ml)<text x="{{ $mi*42+4 }}" y="98" font-size="8" fill="#8a92a0">{{ $ml }}</text>@endforeach
                                    <rect x="370" y="5" width="8" height="3" fill="#002347" rx="1"/><text x="381" y="9" font-size="8" fill="#3d4a5c">Revenue</text>
                                    <rect x="370" y="14" width="8" height="2" fill="#c5a059" rx="1"/><text x="381" y="18" font-size="8" fill="#3d4a5c">Expenses</text>
                                </svg>
                            </div>
                            <table class="db-table">
                                <tr><th>Report</th><th>Period</th><th>Status</th></tr>
                                <tr><td>Monthly P&L</td><td>Jan 2026</td><td><span class="db-pill paid">PDF Ready</span></td></tr>
                                <tr><td>VAT Summary</td><td>Q4 2025</td><td><span class="db-pill paid">XLSX Ready</span></td></tr>
                                <tr><td>Payroll Sheet</td><td>Jan 2026</td><td><span class="db-pill paid">PDF Ready</span></td></tr>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-lg-5" data-aos="fade-left">
                <span class="sb-eyebrow">03 — Expenses & Reports</span>
                <h2 class="sb-h1 sb-h1-white">Board-ready reports <span class="accent">in one click</span></h2>
                <p class="sb-lead sb-lead-white">Stop spending weekends building spreadsheets. SmatBook generates polished financial reports automatically — daily, weekly, monthly, or on demand.</p>
                <div class="d-flex flex-column gap-3 mt-4">
                    <div class="feat-card-dark"><div class="d-flex align-items-start gap-3"><div class="feat-icon feat-icon-dark"><svg viewBox="0 0 24 24" fill="none" stroke="#c5a059" stroke-width="2"><rect x="2" y="5" width="20" height="14" rx="2"/><line x1="2" y1="10" x2="22" y2="10"/></svg></div><div><h6>Automatic Expense Categorization</h6><p>SmatBook learns your spending patterns and auto-tags expenses to the right accounts without manual entry.</p></div></div></div>
                    <div class="feat-card-dark"><div class="d-flex align-items-start gap-3"><div class="feat-icon feat-icon-dark"><svg viewBox="0 0 24 24" fill="none" stroke="#c5a059" stroke-width="2"><path d="M9 11l3 3L22 4"/><path d="M21 12v7a2 2 0 01-2 2H5a2 2 0 01-2-2V5a2 2 0 012-2h11"/></svg></div><div><h6>One-Click Tax Reports</h6><p>Generate VAT, PAYE, and annual tax summaries in seconds — fully formatted for FIRS submission.</p></div></div></div>
                    <div class="feat-card-dark"><div class="d-flex align-items-start gap-3"><div class="feat-icon feat-icon-dark"><svg viewBox="0 0 24 24" fill="none" stroke="#c5a059" stroke-width="2"><polyline points="23 6 13.5 15.5 8.5 10.5 1 18"/><polyline points="17 6 23 6 23 12"/></svg></div><div><h6>Bank Reconciliation</h6><p>Import your bank statements and SmatBook matches every transaction automatically — zero manual reconciliation.</p></div></div></div>
                </div>
            </div>
        </div>
    </div>
</section>

{{-- ══════════════════════════════════════════════════════════
     6-CARD STRIP
══════════════════════════════════════════════════════════ --}}
<section class="strip-section">
    <div class="container">
        <div class="text-center mb-5">
            <span class="sb-eyebrow" style="justify-content:center;display:inline-flex;">Everything included</span>
            <h2 class="sb-h1 text-center">One platform. <span class="accent">Every function.</span></h2>
            <p class="sb-lead text-center mx-auto">SmatBook brings together every tool your business needs to run — from staff management to customer records, POS to cloud backup.</p>
        </div>
        <div class="row g-4">
            @php $strips=[['icon'=>'👥','bg'=>'#f0f4ff','title'=>'Staff & Payroll','desc'=>'Manage employee records, attendance, and process accurate payroll in minutes. Automatic PAYE deductions calculated for you.'],['icon'=>'🧾','bg'=>'#fef9c3','title'=>'Receipts & POS','desc'=>'Turn any device into a point-of-sale terminal. Print or email branded receipts instantly after every sale.'],['icon'=>'📊','bg'=>'#dcfce7','title'=>'Reports & Analytics','desc'=>'From daily sales summaries to quarterly board reports — generate any report with a single click, no accountant needed.'],['icon'=>'🤝','bg'=>'#ffe4e6','title'=>'Customer CRM','desc'=>'Build detailed customer profiles, track purchase history, and send targeted promotions to your best buyers.'],['icon'=>'🔐','bg'=>'#ede9fe','title'=>'Access Control','desc'=>'Create staff accounts with role-based permissions. Your cashier sees only the POS; your manager sees everything.'],['icon'=>'☁️','bg'=>'#f0fdf4','title'=>'Cloud Backup','desc'=>'Your data is encrypted and backed up automatically every hour. Access your books from any device, anywhere.']]; @endphp
            @foreach($strips as $s)
            <div class="col-lg-4 col-md-6" data-aos="fade-up">
                <div class="strip-card">
                    <div class="strip-icon" style="background:{{ $s['bg'] }};">{{ $s['icon'] }}</div>
                    <h5>{{ $s['title'] }}</h5>
                    <p>{{ $s['desc'] }}</p>
                </div>
            </div>
            @endforeach
        </div>
    </div>
</section>

{{-- ══════════════════════════════════════════════════════════
     SOLUTIONS GRID
══════════════════════════════════════════════════════════ --}}
<section class="sb-section sb-section--alt" id="solutions">
    <div class="container">
        <div class="text-center mb-5">
            <span class="sb-eyebrow" style="justify-content:center;display:inline-flex;">Operational Utility</span>
            <h2 class="sb-h1 text-center">Engine <span class="accent">Capabilities</span></h2>
        </div>
        <div class="sol-grid">
            @php $utils=[['icon'=>'fa-brain','title'=>'Neural Ledger Engine','text'=>'Utilizes transformer-based AI to handle multi-currency reconciliations across thousands of subsidiaries. Our engine reduces manual entry errors by 99.8% through autonomous pattern matching.'],['icon'=>'fa-chart-line','title'=>'Predictive Forensics','text'=>'Execute high-fidelity Monte Carlo simulations to forecast capital requirements and mitigate liquidity risks. Transform historical data into actionable 24-month financial roadmaps.'],['icon'=>'fa-fingerprint','title'=>'Sovereign Governance','text'=>'Institutional security protocols featuring Multi-Party Computation (MPC) and ZK-Proofs. Maintain absolute data sovereignty while ensuring total transparency for the executive board.'],['icon'=>'fa-file-signature','title'=>'Autonomous Auditing','text'=>'Generate board-ready audits mapped to IFRS and GAAP standards. Real-time regulatory compliance allows for zero-latency fiscal reporting across global jurisdictions.']]; @endphp
            @foreach($utils as $u)
            <div class="sol-tile" data-aos="fade-up">
                <i class="fas {{ $u['icon'] }} mb-4" style="font-size:2rem;color:var(--navy);"></i>
                <h5 class="fw-bold mb-3" style="font-family:var(--font-display);color:var(--navy);">{{ $u['title'] }}</h5>
                <p class="mb-0" style="font-size:13.5px;color:var(--muted);line-height:1.75;">{{ $u['text'] }}</p>
            </div>
            @endforeach
        </div>
    </div>
</section>

{{-- ══════════════════════════════════════════════════════════
     CAPABILITIES
══════════════════════════════════════════════════════════ --}}
<section class="sb-section" id="capabilities">
    <div class="container">
        <div class="row align-items-center g-5 mb-5 pb-5">
            <div class="col-lg-6" data-aos="fade-right">
                <div class="cap-img"><img src="https://images.pexels.com/photos/3183150/pexels-photo-3183150.jpeg?auto=compress&cs=tinysrgb&w=800" alt="Analytics" class="img-fluid"></div>
            </div>
            <div class="col-lg-6" data-aos="fade-left">
                <span class="sb-eyebrow">01 — Engine Depth</span>
                <h2 class="sb-h1">Strategic <span class="accent">Liquidity</span> Ecosystem</h2>
                <p class="sb-lead">SmatBook's proprietary Neural Forecasting Core (NFC) transcends legacy bookkeeping systems by analyzing over 600 unique financial variables in real-time. By mapping historical account volatility against current receivables, our engine provides surgical liquidity horizon with 98.4% predictive accuracy.</p>
                <div class="p-4 rounded mt-4" style="background:var(--gold-bg);border-left:4px solid var(--gold);">
                    <p class="mb-0 fst-italic" style="font-size:13.5px;font-weight:700;color:var(--navy);">"We convert fragmented transaction streams into verified, high-definition foresight for the modern board."</p>
                </div>
            </div>
        </div>
        <div class="row align-items-center g-5 pt-5">
            <div class="col-lg-6 order-lg-2" data-aos="fade-left">
                <div class="cap-img"><img src="https://images.pexels.com/photos/669619/pexels-photo-669619.jpeg?auto=compress&cs=tinysrgb&w=800" alt="Governance" class="img-fluid"></div>
            </div>
            <div class="col-lg-6 order-lg-1" data-aos="fade-right">
                <span class="sb-eyebrow">02 — Governance</span>
                <h2 class="sb-h1">Institutional <span class="accent">Sovereignty</span> Protocols</h2>
                <p class="sb-lead">Designed for organizations with complex hierarchical needs, SmatBook implements a "Cellular Governance" model that guarantees total transparency without compromising individual business unit security. Each subsidiary operates within a fortified node, feeding into a master dashboard while maintaining SOC2 Type II compliance.</p>
            </div>
        </div>
    </div>
</section>

{{-- ══════════════════════════════════════════════════════════
     PROJECTS / TEAM
══════════════════════════════════════════════════════════ --}}
<section class="sb-section sb-section--alt" id="team">
    <div class="container">
        <div class="text-center mb-5">
            <span class="sb-eyebrow" style="justify-content:center;display:inline-flex;">Portfolio</span>
            <h2 class="sb-h1 text-center">Our <span class="accent">Other Projects</span></h2>
        </div>
        <div class="row g-4">
            @php $team=[['name'=>'Lahome Properties','role'=>'Real Estate Platform','img'=>'https://images.pexels.com/photos/323780/pexels-photo-323780.jpeg?auto=compress&cs=tinysrgb&w=1200','bio'=>'A global real estate listing ecosystem for owners, surveyors, legal advisers, agents, and every key stakeholder in the property market.','link'=>route('landing.projects.lahome')],['name'=>'Master JAMB','role'=>'CBT Examination Platform','img'=>'https://images.unsplash.com/photo-1588072432836-e10032774350?q=80&w=1200&auto=format&fit=crop','bio'=>'An online CBT platform for schools and institutions, built for exam readiness, timed assessments, and performance tracking.','link'=>route('landing.projects.master-jamb')],['name'=>'PayPlus','role'=>'Payment Gateway','img'=>'https://images.pexels.com/photos/4968634/pexels-photo-4968634.jpeg?auto=compress&cs=tinysrgb&w=1200','bio'=>'A global payment gateway designed for secure processing of everyday transactions across web, mobile, and enterprise channels.','link'=>route('landing.projects.payplus')]]; @endphp
            @foreach($team as $m)
            <div class="col-lg-4 col-md-6" data-aos="fade-up">
                <div class="project-card">
                    <div class="project-img"><img src="{{ $m['img'] }}" alt="{{ $m['name'] }}"></div>
                    <div class="p-4 text-center">
                        <h5 class="fw-bold mb-1" style="font-family:var(--font-display);color:var(--navy);">{{ $m['name'] }}</h5>
                        <p style="color:var(--gold);font-size:0.72rem;font-weight:800;text-transform:uppercase;letter-spacing:1.5px;margin-bottom:12px;">{{ $m['role'] }}</p>
                        <p class="mb-4" style="font-size:13px;color:var(--muted);line-height:1.7;padding:0 6px;">{{ $m['bio'] }}</p>
                        <div class="d-flex justify-content-center gap-2">
                            <a href="{{ $m['link'] }}" class="btn-outline-navy">Learn More</a>
                            <a href="{{ route('landing.contact') }}" class="btn-outline-navy">Request Demo</a>
                        </div>
                    </div>
                </div>
            </div>
            @endforeach
        </div>
    </div>
</section>

{{-- ══════════════════════════════════════════════════════════
     TESTIMONIALS
══════════════════════════════════════════════════════════ --}}
<section class="testi-section sb-section--dark">
    <div class="container text-center mb-5">
        <span class="sb-eyebrow" style="justify-content:center;display:inline-flex;">Global Adoption</span>
        <h2 class="sb-h1 sb-h1-white">Institutional <span class="accent">Validation</span></h2>
    </div>
    <div class="testi-track-wrap">
        <div class="testi-track">
            @php $tests=[['name'=>'Chinedu Okafor','role'=>'CFO, Lagos Holdings','img'=>'https://images.unsplash.com/photo-1507591064344-4c6ce005b128?q=80&w=300&auto=format&fit=crop'],['name'=>'Amina Bello','role'=>'Finance Director, Abuja Group','img'=>'https://images.unsplash.com/photo-1488426862026-3ee34a7d66df?q=80&w=300&auto=format&fit=crop'],['name'=>'Michael Carter','role'=>'VP Finance, New York Capital','img'=>'https://images.unsplash.com/photo-1507003211169-0a1dd7228f2d?q=80&w=300&auto=format&fit=crop'],['name'=>'Emily Johnson','role'=>'Controller, Austin Ventures','img'=>'https://images.unsplash.com/photo-1494790108377-be9c29b29330?q=80&w=300&auto=format&fit=crop'],['name'=>'Li Wei','role'=>'Treasury Lead, Shanghai Trade','img'=>'https://images.unsplash.com/photo-1521119989659-a83eee488004?q=80&w=300&auto=format&fit=crop'],['name'=>'Chen Ming','role'=>'Payments Director, Beijing Commerce','img'=>'https://images.unsplash.com/photo-1500648767791-00dcc994a43e?q=80&w=300&auto=format&fit=crop']]; $repeat=array_merge($tests,$tests,$tests); @endphp
            @foreach($repeat as $t)
            <div class="testi-card">
                <p style="font-size:0.88rem;color:rgba(255,255,255,0.88);font-style:italic;line-height:1.7;margin-bottom:22px;">"SmatBook's neural-ledgers have fundamentally changed how we manage our global hubs. Unmatched precision."</p>
                <div class="d-flex align-items-center gap-3">
                    <img src="{{ $t['img'] }}" class="testi-avatar" alt="{{ $t['name'] }}">
                    <div>
                        <div style="font-weight:700;color:#fff;font-size:0.83rem;font-family:var(--font-display);">{{ $t['name'] }}</div>
                        <div style="color:var(--gold);font-size:0.68rem;font-weight:800;letter-spacing:1px;text-transform:uppercase;">{{ $t['role'] }}</div>
                    </div>
                </div>
            </div>
            @endforeach
        </div>
    </div>
</section>

{{-- ══════════════════════════════════════════════════════════
     LICENSING
══════════════════════════════════════════════════════════ --}}
<section class="sb-section" id="licensing">
    <div class="container">
        <div class="text-center mb-5">
            <span class="sb-eyebrow" style="justify-content:center;display:inline-flex;">Service Access</span>
            <h2 class="sb-h1 text-center">Enterprise <span class="accent">Licensing</span></h2>
        </div>
        <div class="row g-4">
            @php $plans=['Basic'=>['ngn'=>3000,'feat'=>false,'benefits'=>['Centralized Ledgers','5 Core User Access','Daily Cloud Backups','Standard Email Support','Unified Reporting']],'Pro'=>['ngn'=>7000,'feat'=>true,'benefits'=>['Neural Engine Core','25 Premium User Access','Dedicated Priority Node','Real-time Analytics','Predictive Forecasting']],'Enterprise'=>['ngn'=>15000,'feat'=>false,'benefits'=>['Full Neural Automation','Unlimited Access Nodes','Advanced API Gateway','Custom Fiscal Reports','IFRS Compliance Mapping']],'Institution'=>['ngn'=>null,'feat'=>false,'benefits'=>['Private Hybrid Core','SLA Performance Guarantee','On-site Technical Support','Bespoke Integrations','Governance Training']]]; @endphp
            @foreach($plans as $name => $p)
            <div class="col-lg-3 col-md-6" data-aos="fade-up">
                <div class="plan-card {{ $p['feat'] ? 'plan-featured' : '' }}">
                    @if($p['feat'])<div style="text-align:center;margin-bottom:12px;"><span style="background:var(--gold);color:var(--navy);font-size:0.62rem;font-weight:900;letter-spacing:2px;text-transform:uppercase;padding:4px 14px;border-radius:20px;">MOST POPULAR</span></div>@endif
                    <div class="plan-name">{{ $name }}</div>
                    <div class="plan-price">
                        @if($p['ngn'])<span class="geo-price" data-ngn="{{ $p['ngn'] }}">₦{{ number_format($p['ngn']) }}</span>@else<span>Bespoke</span>@endif
                    </div>
                    <div class="flex-grow-1">
                        @foreach($p['benefits'] as $b)
                        <div class="plan-feature"><i class="fas fa-check-circle"></i><span>{{ $b }}</span></div>
                        @endforeach
                    </div>
                    <div class="mt-4">
                        <a href="{{ url('/membership-plans') }}" class="{{ $p['feat'] ? 'btn-red' : 'btn-outline-navy' }} w-100 justify-content-center" style="{{ $p['feat'] ? '' : 'border-radius:var(--radius-sm);' }}">ACQUIRE SYSTEM</a>
                    </div>
                </div>
            </div>
            @endforeach
        </div>
    </div>
</section>

{{-- ══════════════════════════════════════════════════════════
     FOOTER
══════════════════════════════════════════════════════════ --}}
<footer class="sb-footer" id="contact">
    <div class="container" style="position:relative;z-index:1;">
        <div class="row g-5 mb-5 pb-5" style="border-bottom:1px solid var(--border);">
            <div class="col-lg-5" data-aos="fade-right">
                <h2 style="font-family:var(--font-display);font-weight:800;color:var(--navy);margin-bottom:16px;">Uplink <span style="color:var(--gold);">Support</span></h2>
                <p style="color:var(--muted);line-height:1.85;margin-bottom:24px;">Technical architects are available 24/7 for organizational assessment and rapid deployment. Contact us to initialize your institutional connection.</p>
                <div class="mb-4">
                    <p class="mb-2" style="font-size:13.5px;font-weight:700;color:var(--navy);"><i class="fas fa-map-marker-alt me-3" style="color:var(--gold);"></i>12 Independence Layout, Enugu, Nigeria</p>
                    <p class="mb-2" style="font-size:13.5px;font-weight:700;color:var(--navy);"><i class="fas fa-phone-alt me-3" style="color:var(--gold);"></i>+234 646 463 06</p>
                    <p class="mb-0" style="font-size:13.5px;font-weight:700;color:var(--navy);"><i class="fas fa-envelope me-3" style="color:var(--gold);"></i><a href="mailto:donvictorlive@gmail.com" style="color:var(--navy);text-decoration:none;">donvictorlive@gmail.com</a></p>
                </div>
                @if(session('success'))<div class="alert alert-success" style="border-radius:var(--radius-sm);">{{ session('success') }}</div>@endif
                @if(session('error'))<div class="alert alert-danger" style="border-radius:var(--radius-sm);">{{ session('error') }}</div>@endif
                <form action="{{ route('contact.store') }}" method="POST">
                    @csrf
                    <div class="mb-3"><input type="text" name="company_name" class="form-control" placeholder="Organization Name" value="{{ old('company_name') }}" style="border:1.5px solid var(--border);border-radius:var(--radius-sm);padding:12px 14px;font-size:0.88rem;"></div>
                    <div class="mb-3"><input type="text" name="fullname" class="form-control" placeholder="Full Name" value="{{ old('fullname') }}" required style="border:1.5px solid var(--border);border-radius:var(--radius-sm);padding:12px 14px;font-size:0.88rem;"></div>
                    <div class="mb-3"><input type="email" name="email" class="form-control" placeholder="Work Email" value="{{ old('email') }}" required style="border:1.5px solid var(--border);border-radius:var(--radius-sm);padding:12px 14px;font-size:0.88rem;"></div>
                    <div class="mb-3"><input type="text" name="department" class="form-control" placeholder="Department (Optional)" value="{{ old('department') }}" style="border:1.5px solid var(--border);border-radius:var(--radius-sm);padding:12px 14px;font-size:0.88rem;"></div>
                    <div class="mb-4"><textarea name="message" class="form-control" rows="4" placeholder="Brief Requirements" required style="border:1.5px solid var(--border);border-radius:var(--radius-sm);padding:12px 14px;font-size:0.88rem;">{{ old('message') }}</textarea></div>
                    <button type="submit" class="btn-red w-100 justify-content-center"><i class="fas fa-paper-plane"></i> INITIALIZE CONNECTION</button>
                </form>
            </div>
            <div class="col-lg-7" data-aos="fade-left">
                <div class="map-wrap" style="height:100%;min-height:480px;">
                    <iframe src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d15858.987654321!2d7.508333!3d6.458333!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x1044a3d6f1a8e1e1%3A0x1234567890abcdef!2sIndependence%20Layout%2C%20Enugu!5e0!3m2!1sen!2sng!4v1234567890123" width="100%" height="100%" style="border:0;min-height:480px;" allowfullscreen="" loading="lazy"></iframe>
                </div>
            </div>
        </div>

        <div class="row g-4">
            <div class="col-lg-4">
                <h3 style="font-family:var(--font-display);font-weight:900;color:var(--navy);letter-spacing:0.5px;margin-bottom:12px;">SMATBOOK</h3>
                <p style="font-size:13px;color:var(--muted);max-width:300px;line-height:1.8;">Global Institutional Accounting Intelligence. Engineered for modern wealth governance and predictive capital allocation. Nigeria Hub Node: ENU-NG-12.</p>
                <div class="d-flex gap-3 mt-4">
                    <a href="{{ route('landing.about') }}" class="footer-social"><i class="fab fa-linkedin-in"></i></a>
                    <a href="{{ route('landing.contact') }}" class="footer-social"><i class="fab fa-twitter"></i></a>
                    <a href="{{ route('landing.policy') }}" class="footer-social"><i class="fab fa-facebook-f"></i></a>
                </div>
            </div>
            <div class="col-md-3 col-lg-2 ms-auto">
                <h6 style="font-family:var(--font-display);font-weight:800;font-size:0.72rem;text-transform:uppercase;letter-spacing:1.5px;color:var(--navy);margin-bottom:18px;">Platform</h6>
                <div class="d-flex flex-column gap-2">
                    <a href="#solutions" class="footer-link">Neural Engine Core</a>
                    <a href="#capabilities" class="footer-link">Security Hub</a>
                    <a href="#licensing" class="footer-link">API Documentation</a>
                </div>
            </div>
            <div class="col-md-3 col-lg-2">
                <h6 style="font-family:var(--font-display);font-weight:800;font-size:0.72rem;text-transform:uppercase;letter-spacing:1.5px;color:var(--navy);margin-bottom:18px;">Support</h6>
                <div class="d-flex flex-column gap-2">
                    <a href="{{ route('landing.contact') }}" class="footer-link">Knowledge Base</a>
                    <a href="{{ route('landing.contact') }}" class="footer-link">SLA Status</a>
                    <a href="{{ route('saas-login') }}" class="footer-link">Global Deployment</a>
                </div>
            </div>
        </div>

        <div class="mt-5 pt-4 text-center" style="border-top:1px solid var(--border);">
            <p style="font-size:13px;color:var(--muted);margin:0;">© 2026 SmatBook Intelligence Enterprise. Licensed for Global Financial Governance.</p>
        </div>
    </div>
</footer>

{{-- ══════════════════════════════════════════════════════════
     JAVASCRIPT
══════════════════════════════════════════════════════════ --}}
<script>
document.addEventListener('DOMContentLoaded', function () {

    // ── Nav height sync ─────────────────────────────────
    const syncNav = () => {
        const nav = document.getElementById('mainNav');
        if (!nav) return;
        const h = Math.ceil(nav.getBoundingClientRect().height || 84);
        document.documentElement.style.setProperty('--nav-h', h + 'px');
    };
    syncNav();
    ['load','resize','scroll'].forEach(e => window.addEventListener(e, syncNav, {passive:true}));
    setInterval(syncNav, 1200);

    // ── Navbar scroll state ──────────────────────────────
    const nav = document.getElementById('mainNav');
    window.addEventListener('scroll', () => nav.classList.toggle('scrolled', scrollY > 50));

    // ── Smooth scroll ────────────────────────────────────
    document.querySelectorAll('a[href^="#"]').forEach(a => {
        a.addEventListener('click', e => {
            const t = document.querySelector(a.getAttribute('href'));
            if (!t) return;
            e.preventDefault();
            window.scrollTo({ top: t.offsetTop - 100, behavior: 'smooth' });
            const nc = document.getElementById('mujiNav');
            if (nc?.classList.contains('show')) bootstrap.Collapse.getInstance(nc)?.hide();
        });
    });

    // ── Announcement bar rotation ────────────────────────
    const msgs = document.querySelectorAll('.announce-msg');
    let cur = 0;
    setInterval(() => {
        msgs[cur].classList.remove('active');
        msgs[cur].classList.add('exit');
        setTimeout(() => { msgs[cur].classList.remove('exit'); }, 500);
        cur = (cur + 1) % msgs.length;
        msgs[cur].classList.add('active');
    }, 3500);

    // ── Progress bar observer ────────────────────────────
    const progs = document.querySelectorAll('.prog-fill');
    new IntersectionObserver(entries => {
        entries.forEach(e => { if (e.isIntersecting) { e.target.classList.add('go'); } });
    }, { threshold: 0.3 }).observe(...progs.length ? progs : [document.body]);
    progs.forEach(p => new IntersectionObserver(entries => {
        entries.forEach(e => { if (e.isIntersecting) p.classList.add('go'); });
    }, { threshold: 0.3 }).observe(p));

    // ── Geo pricing ──────────────────────────────────────
    const countryMap = { NG:{c:'NGN',l:'en-NG'},US:{c:'USD',l:'en-US'},CN:{c:'CNY',l:'zh-CN'},GB:{c:'GBP',l:'en-GB'},EU:{c:'EUR',l:'en-IE'},CA:{c:'CAD',l:'en-CA'},IN:{c:'INR',l:'en-IN'},AE:{c:'AED',l:'en-AE'},ZA:{c:'ZAR',l:'en-ZA'},KE:{c:'KES',l:'en-KE'},GH:{c:'GHS',l:'en-GH'} };
    const fallback = {NGN:1,USD:0.00067,CNY:0.0048,GBP:0.00053,EUR:0.00062,CAD:0.00091,INR:0.056,AED:0.00246,ZAR:0.0125,KES:0.086,GHS:0.0105};
    const euCodes = ['FR','DE','ES','IT','PT','NL','BE','AT','IE','FI','SE','DK','PL','CZ','GR','RO','HU'];
    const norm = c => { const s = String(c||'').toUpperCase(); if(euCodes.includes(s)) return 'EU'; return countryMap[s]?s:'NG'; };
    const fetchRates = async () => {
        const k = 'sb_rates_v1', kt = k+'_t', cached = sessionStorage.getItem(k), ts = +sessionStorage.getItem(kt)||0;
        if (cached && Date.now()-ts < 21600000) return JSON.parse(cached);
        try { const r = await fetch('https://open.er-api.com/v6/latest/NGN',{cache:'no-store'}); const d = await r.json(); if(d?.rates){sessionStorage.setItem(k,JSON.stringify(d.rates));sessionStorage.setItem(kt,String(Date.now()));return d.rates;} } catch(e){}
        return fallback;
    };
    const renderPrices = async (code) => {
        const geo = countryMap[norm(code)]||countryMap.NG;
        const rates = await fetchRates();
        const rate = rates[geo.c]||fallback[geo.c]||1;
        document.querySelectorAll('.geo-price').forEach(n => {
            const v = +(n.dataset.ngn||0)*rate;
            try { n.textContent = new Intl.NumberFormat(geo.l,{style:'currency',currency:geo.c,maximumFractionDigits:0}).format(v); } catch(e){ n.textContent = `${geo.c} ${Math.round(v).toLocaleString()}`; }
        });
    };
    const applyCountry = code => { const n=norm(code); localStorage.setItem('sb_country',n); renderPrices(n); };
    const saved = localStorage.getItem('sb_country');
    const cookie = (document.cookie.match(/(?:^|;\s*)sb_country=([^;]+)/)||[])[1]||'';
    applyCountry(saved || cookie || @json($geoCountry ?? 'NG'));

    // ── FX Ticker ────────────────────────────────────────
    const FX_PAIRS = [
        {label:'🇺🇸 USD/NGN',base:'USD',flag:'🇺🇸'},{label:'🇬🇧 GBP/NGN',base:'GBP',flag:'🇬🇧'},
        {label:'🇪🇺 EUR/NGN',base:'EUR',flag:'🇪🇺'},{label:'🇨🇳 CNY/NGN',base:'CNY',flag:'🇨🇳'},
        {label:'🇨🇦 CAD/NGN',base:'CAD',flag:'🇨🇦'},{label:'🇮🇳 INR/NGN',base:'INR',flag:'🇮🇳'},
        {label:'🇦🇪 AED/NGN',base:'AED',flag:'🇦🇪'},{label:'🇿🇦 ZAR/NGN',base:'ZAR',flag:'🇿🇦'},
        {label:'🇰🇪 KES/NGN',base:'KES',flag:'🇰🇪'},{label:'🇬🇭 GHS/NGN',base:'GHS',flag:'🇬🇭'},
    ];
    const FX_FB = {USD:1620,GBP:2050,EUR:1740,CNY:224,CAD:1190,INR:19.4,AED:441,ZAR:88,KES:12.5,GHS:106};
    let fxR = {...FX_FB}, prevR = {...FX_FB};
    const fetchFX = async () => {
        try {
            const res = await fetch('https://open.er-api.com/v6/latest/NGN',{cache:'no-store'});
            const d = await res.json();
            if (d?.rates) FX_PAIRS.forEach(p => { const r=d.rates[p.base]; if(r&&r>0){prevR[p.base]=fxR[p.base];fxR[p.base]=+(1/r).toFixed(2);} });
        } catch(e){}
        buildTicker();
    };
    const buildTicker = () => {
        const track = document.getElementById('hero-ticker-track');
        if (!track) return;
        const items = FX_PAIRS.map(p => {
            const rate=fxR[p.base]||FX_FB[p.base], prev=prevR[p.base]||rate, up=rate>=prev;
            return `<span style="display:inline-flex;align-items:center;gap:8px;padding:0 24px;font-size:12px;font-weight:700;color:#fff;border-right:1px solid rgba(197,160,89,0.2);">
                <span>${p.flag}</span>
                <span style="color:rgba(255,255,255,0.55);font-size:11px;">${p.label.split(' ')[1]}</span>
                <span>₦${rate.toLocaleString('en-NG',{maximumFractionDigits:1})}</span>
                <span style="color:${up?'#22c55e':'#ef4444'};font-size:11px;">${up?'▲':'▼'}</span>
            </span>`;
        }).join('');
        track.innerHTML = items + items;
    };
    fetchFX();
    setInterval(fetchFX, 60000);

});
</script>
@endsection