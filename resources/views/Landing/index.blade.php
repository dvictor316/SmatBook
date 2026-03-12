


@extends('layout.landing_nav')

@section('content')
<style>
@import url('https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;600;700;800;900&family=DM+Sans:wght@400;500;600;700&display=swap');

:root {
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
    --white:       #ffffff;
    --border:      #e4eaf4;
    --muted:       #6b7280;
    --text:        #1a2232;
    --surface-2:   #f4f8ff;
    --surface-3:   #edf2fb;
    --font-display:'Plus Jakarta Sans', sans-serif;
    --font-body:   'DM Sans', sans-serif;
    --nav-h:       68px;
    --announce-h:  38px;
    --radius-sm:   8px;
    --radius-md:   14px;
    --radius-lg:   20px;
    --shadow-sm:   0 2px 8px rgba(0,35,71,0.06);
    --shadow-md:   0 8px 28px rgba(0,35,71,0.10);
    --shadow-lg:   0 20px 56px rgba(0,35,71,0.14);
    --shadow-gold: 0 8px 24px rgba(197,160,89,0.30);
    --shadow-red:  0 8px 24px rgba(188,0,45,0.35);
}

*, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
html { scroll-behavior: smooth; scroll-padding-top: calc(var(--nav-h) + var(--announce-h) + 8px); }
body { font-family: var(--font-body); background: var(--white); color: var(--text); overflow-x: hidden; }
nav:not(#mainNav):not(.sb-nav) { display: none !important; }

/* ── NAV ── */
nav.sb-nav {
    background: rgba(240,244,252,0.97);
    backdrop-filter: blur(16px); -webkit-backdrop-filter: blur(16px);
    border-bottom: 1px solid var(--border);
    height: var(--nav-h); z-index: 9999; transition: all 0.3s ease;
    font-family: var(--font-display);
}
nav.sb-nav.scrolled { background: rgba(255,255,255,0.98); box-shadow: 0 4px 24px rgba(0,35,71,0.10); }
nav.sb-nav .container { height: var(--nav-h); display: flex; align-items: center; }
.sb-brand { display: flex; align-items: center; gap: 8px; text-decoration: none; }
.sb-brand img { width: auto; display: block; }
.sb-brand-logo { height: 68px; }
.spb-nav-wordmark{
    font-size: 1.2rem;
    font-weight: 800;
    color: #0b2a63;
    letter-spacing: -0.3px;
    line-height: 1;
    white-space: nowrap;
}
.spb-nav-wordmark .book{ color: #dc2626; }
.sb-nav-link {
    font-weight: 700; font-size: 0.78rem; text-transform: uppercase; letter-spacing: 1px;
    color: var(--navy) !important; padding: 6px 14px; border-radius: var(--radius-sm);
    transition: all 0.2s; white-space: nowrap;
    text-decoration: none !important; border: none !important; outline: none !important;
}
.sb-nav-link:hover, .sb-nav-link.active { color: #1c66e8 !important; background: rgba(28,102,232,0.07); }
.sb-nav-link::after, .sb-nav-link::before { display: none !important; content: none !important; }
.navbar-nav .nav-link, .navbar-nav .nav-link:focus, .navbar-nav .nav-link:hover, .navbar-nav .nav-link.active {
    text-decoration: none !important; border-bottom: none !important; box-shadow: none !important;
}
.navbar-nav .nav-link::after, .navbar-nav .nav-link::before { display: none !important; }

/* Hamburger toggle */
.navbar-toggler {
    border: none !important; outline: none !important; box-shadow: none !important;
    padding: 8px 10px; border-radius: 8px;
    background: transparent; cursor: pointer; transition: background 0.2s;
    display: flex; flex-direction: column; justify-content: center; align-items: center; gap: 0;
}
.navbar-toggler:hover { background: rgba(28,102,232,0.07); }
.navbar-toggler:focus { outline: none !important; box-shadow: none !important; }
.tog-bar {
    display: block; width: 22px; height: 2px;
    background: var(--navy); border-radius: 2px;
    margin: 3px 0; transition: all 0.3s ease;
}
.navbar-toggler[aria-expanded="true"] .tog-bar:nth-child(1) { transform: translateY(8px) rotate(45deg); }
.navbar-toggler[aria-expanded="true"] .tog-bar:nth-child(2) { opacity: 0; transform: scaleX(0); }
.navbar-toggler[aria-expanded="true"] .tog-bar:nth-child(3) { transform: translateY(-8px) rotate(-45deg); }

.btn-portal {
    background: linear-gradient(135deg, #1170ec, #19b9e6); color: #fff !important;
    padding: 10px 28px; border-radius: var(--radius-md); font-weight: 800; font-size: 0.78rem;
    letter-spacing: 1.2px; text-transform: uppercase; text-decoration: none;
    box-shadow: 0 4px 16px rgba(17,112,236,0.25); transition: all 0.3s; border: none;
    display: inline-flex; align-items: center; gap: 8px;
}
.btn-portal:hover { transform: translateY(-2px); box-shadow: 0 8px 24px rgba(17,112,236,0.35); color: #fff !important; }

@media (max-width: 767.98px) {
    .sb-brand-logo { height: 58px; }
}

/* ── BUTTONS ── */
.btn-red {
    background: var(--crimson); color: #fff !important; border: none;
    padding: 16px 44px; font-weight: 800; border-radius: var(--radius-sm);
    font-size: 0.88rem; letter-spacing: 1.5px; text-transform: uppercase;
    box-shadow: var(--shadow-red); transition: all 0.35s cubic-bezier(.175,.885,.32,1.275);
    text-decoration: none; display: inline-flex; align-items: center; gap: 10px;
    position: relative; overflow: hidden;
}
.btn-red::after { content:''; position:absolute; inset:0; background:linear-gradient(90deg,transparent,rgba(255,255,255,0.18),transparent); transform:translateX(-100%); transition:transform 0.5s; }
.btn-red:hover::after { transform: translateX(100%); }
.btn-red:hover { transform: translateY(-3px) scale(1.015); background: var(--crimson-dk); box-shadow: 0 14px 32px rgba(188,0,45,0.50); color: #fff !important; }
.btn-outline-navy {
    background: transparent; color: var(--navy) !important; border: 2px solid var(--border);
    padding: 10px 22px; border-radius: var(--radius-sm); font-weight: 700; font-size: 0.82rem;
    transition: all 0.25s; cursor: pointer; display: inline-flex; align-items: center; gap: 8px; text-decoration: none;
}
.btn-outline-navy:hover { border-color: var(--gold); color: var(--gold) !important; }

/* ── SECTIONS ── */
.sb-section { padding: 100px 0; }
.sb-section--dark { background: linear-gradient(135deg, var(--navy-deep), var(--navy)); color: #fff; }
.sb-section--alt { background: var(--surface-2); }
.sb-eyebrow {
    display: inline-flex; align-items: center; gap: 10px; font-size: 0.7rem; font-weight: 800;
    letter-spacing: 3px; text-transform: uppercase; color: var(--gold); margin-bottom: 14px;
    font-family: var(--font-display);
}
.sb-eyebrow::before { content: ''; width: 24px; height: 2px; background: var(--gold); display: block; border-radius: 2px; }
.sb-h1 { font-family: var(--font-display); font-size: clamp(1.9rem,3.5vw,2.7rem); font-weight: 800; line-height: 1.15; color: var(--navy); letter-spacing: -1px; margin-bottom: 18px; }
.sb-h1 .accent { color: var(--gold); }
.sb-h1-white { color: #fff; }
.sb-lead { font-size: 15px; line-height: 1.9; color: var(--muted); max-width: 580px; }
.sb-lead-white { color: rgba(255,255,255,0.72); }

/* ── ANNOUNCE BAR ── */
.announce-bar {
    position: fixed; top: var(--nav-h); left: 0; right: 0; z-index: 9998;
    height: var(--announce-h); background: var(--navy);
    display: flex; align-items: center; justify-content: center; overflow: hidden;
}
.announce-label {
    position: absolute; left: 0; top: 0; bottom: 0;
    background: linear-gradient(135deg, var(--gold), var(--gold-bright));
    color: var(--navy); font-weight: 900; font-size: 0.62rem; letter-spacing: 2px;
    text-transform: uppercase; padding: 0 20px; display: flex; align-items: center; gap: 6px; white-space: nowrap;
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

/* ══════════════════════════════════════════════════════════════
   HERO
══════════════════════════════════════════════════════════════ */
.hero-wrap {
    position: relative; width: 100%;
    padding-top: calc(var(--nav-h) + var(--announce-h) - 22px);
    background: linear-gradient(135deg, #000c1e 0%, #001240 30%, #061d6b 60%, #0a2fa8 100%);
    overflow: hidden;
    min-height: calc(100vh - var(--nav-h) - var(--announce-h));
    display: flex; flex-direction: column;
}
.hero-wrap::before {
    content: ''; position: absolute; inset: 0; z-index: 0; pointer-events: none;
    background-image:
        radial-gradient(1px 1px at 18% 22%, rgba(255,255,255,0.55) 0%, transparent 100%),
        radial-gradient(1px 1px at 72% 14%, rgba(255,255,255,0.40) 0%, transparent 100%),
        radial-gradient(1.5px 1.5px at 44% 58%, rgba(255,255,255,0.35) 0%, transparent 100%),
        radial-gradient(1px 1px at 88% 42%, rgba(255,255,255,0.50) 0%, transparent 100%),
        radial-gradient(1px 1px at 8%  76%, rgba(255,255,255,0.30) 0%, transparent 100%),
        radial-gradient(1px 1px at 62% 82%, rgba(255,255,255,0.40) 0%, transparent 100%),
        radial-gradient(1.5px 1.5px at 55% 6%,  rgba(255,255,255,0.45) 0%, transparent 100%),
        radial-gradient(1px 1px at 94% 68%, rgba(255,255,255,0.35) 0%, transparent 100%),
        radial-gradient(1px 1px at 3%  40%, rgba(255,255,255,0.30) 0%, transparent 100%);
}
.hero-wrap::after {
    content: ''; position: absolute; width: 1000px; height: 1000px;
    background: radial-gradient(circle, rgba(28,102,232,0.18) 0%, transparent 65%);
    top: -200px; left: -200px; z-index: 0; pointer-events: none;
}
.hero-orb2 {
    position: absolute; width: 600px; height: 600px; border-radius: 50%;
    background: radial-gradient(circle, rgba(197,160,89,0.10) 0%, transparent 65%);
    bottom: -100px; right: 0; z-index: 0; pointer-events: none;
}

.hero-content {
    position: relative; z-index: 2; flex: 1;
    display: flex; align-items: flex-start; justify-content: center;
    padding: 0 40px 18px;
    max-width: 1400px; margin: 0 auto; width: 100%;
    gap: 0;
}

/* circle zone */
.hero-left { flex: 0 0 auto; display: flex; align-items: center; justify-content: center; }

/* THE CIRCLE */
.hero-circle-wrapper {
    position: relative;
    display: flex;
    align-items: center;
    justify-content: center;
    z-index: 10;
}
.hero-circle {
    width: clamp(380px, 42vw, 580px);
    height: clamp(380px, 42vw, 580px);
    border-radius: 50%;
    background: radial-gradient(circle at 35% 28%,
        #2a5cde 0%, #1438b0 20%, #081890 38%,
        #040e6a 56%, #01083d 76%, #000210 100%);
    border: 2.5px solid rgba(243,206,132,0.80);
    display: flex; flex-direction: column; align-items: center; justify-content: center;
    text-align: center; padding: clamp(48px,9%,80px);
    position: relative; flex-shrink: 0; overflow: hidden;
    box-shadow:
        0 0 0 16px rgba(243,206,132,0.10), 0 0 0 32px rgba(243,206,132,0.06),
        0 0 0 52px rgba(243,206,132,0.03), 0 0 0 80px rgba(243,206,132,0.015),
        0 48px 120px rgba(0,4,30,0.85);
    animation: heroCircleGlow 5s ease-in-out infinite;
    z-index: 4;
}
.hero-circle::before {
    content: ''; position: absolute; inset: 18px; border-radius: 50%;
    border: 1px solid rgba(243,206,132,0.18); pointer-events: none; z-index: 0;
}
.hero-circle::after {
    content: ''; position: absolute; inset: 0; border-radius: 50%;
    background: conic-gradient(from 200deg,transparent 0%,rgba(255,255,255,0.03) 15%,transparent 30%);
    pointer-events: none; z-index: 0; animation: circleRotate 12s linear infinite;
}
@keyframes heroCircleGlow {
    0%,100% { box-shadow: 0 0 0 14px rgba(243,206,132,0.10),0 0 0 28px rgba(243,206,132,0.06),0 0 0 46px rgba(243,206,132,0.03),0 48px 120px rgba(0,4,30,0.85); }
    50%      { box-shadow: 0 0 0 20px rgba(243,206,132,0.16),0 0 0 38px rgba(243,206,132,0.09),0 0 0 58px rgba(243,206,132,0.04),0 48px 120px rgba(0,4,30,0.90); }
}
@keyframes circleRotate { from{transform:rotate(0deg)} to{transform:rotate(360deg)} }

.hero-circle-orbit {
    position: absolute; inset: -2px; border-radius: 50%;
    animation: orbitSpin 8s linear infinite; pointer-events: none; z-index: 1;
}
.hero-circle-orbit::before {
    content: ''; position: absolute; top: 12px; left: 50%; transform: translateX(-50%);
    width: 8px; height: 8px; border-radius: 50%; background: var(--gold-bright);
    box-shadow: 0 0 10px rgba(255,223,145,0.9), 0 0 20px rgba(255,223,145,0.5);
}
@keyframes orbitSpin { from{transform:rotate(0deg)} to{transform:rotate(360deg)} }
.hero-circle > * { position: relative; z-index: 2; }

.hero-eyebrow {
    font-family: var(--font-display); font-weight: 900;
    font-size: clamp(8px,0.75vw,11px); letter-spacing: clamp(4px,0.6vw,8px);
    text-transform: uppercase; color: #fce8be;
    margin-bottom: clamp(12px,1.4vw,22px); white-space: nowrap;
}
.hero-h1 {
    font-family: var(--font-display); font-size: clamp(1.35rem,2.4vw,2.6rem);
    font-weight: 900; color: #fff; line-height: 1.13; letter-spacing: -1px;
    margin-bottom: clamp(10px,1.2vw,18px);
}
.hero-h1 .gold-text {
    background: linear-gradient(135deg, #ffd98a 0%, #fff7cc 55%, #ffd98a 100%);
    -webkit-background-clip: text; -webkit-text-fill-color: transparent; background-clip: text;
}
.hero-body {
    font-size: clamp(11px,0.95vw,13.5px); color: rgba(255,255,255,0.78);
    line-height: 1.75; margin-bottom: clamp(20px,2.2vw,36px); max-width: 28ch;
}
.hero-cta-stack {
    display: flex; flex-direction: column; gap: clamp(10px,1vw,14px);
    width: 100%; max-width: clamp(200px,21vw,270px);
}
.hero-btn-red {
    display: flex; align-items: center; justify-content: center; gap: 9px;
    background: var(--crimson); color: #fff !important;
    padding: clamp(12px,1.1vw,15px) clamp(20px,2.2vw,28px);
    font-weight: 800; border-radius: 40px; font-size: clamp(0.70rem,0.78vw,0.84rem);
    letter-spacing: 1.4px; text-transform: uppercase; text-decoration: none; border: none;
    transition: all 0.3s; box-shadow: 0 8px 24px rgba(188,0,45,0.45);
    white-space: nowrap; position: relative; overflow: hidden;
}
.hero-btn-red::after { content:''; position:absolute; inset:0; background:linear-gradient(90deg,transparent,rgba(255,255,255,0.15),transparent); transform:translateX(-100%); transition:transform 0.5s; }
.hero-btn-red:hover::after { transform: translateX(100%); }
.hero-btn-red:hover { background: var(--crimson-dk); color: #fff !important; transform: translateY(-2px); }
.hero-btn-ghost {
    display: flex; align-items: center; justify-content: center; gap: 9px;
    background: rgba(255,223,145,0.08); color: #fff8e0 !important;
    padding: clamp(11px,1vw,14px) clamp(20px,2.2vw,28px);
    font-weight: 800; border-radius: 40px; font-size: clamp(0.70rem,0.78vw,0.84rem);
    letter-spacing: 1.4px; text-transform: uppercase; text-decoration: none;
    border: 1.5px solid rgba(255,223,145,0.50); transition: all 0.3s; white-space: nowrap;
}
.hero-btn-ghost:hover { background: rgba(255,223,145,0.18); border-color: var(--gold-bright); color: #fff8e0 !important; transform: translateY(-2px); }
.hero-trust { display: flex; align-items: center; gap: 10px; margin-top: clamp(14px,1.5vw,22px); }
.trust-dot { width: 6px; height: 6px; border-radius: 50%; background: #22c55e; animation: blink 1.5s infinite; }
.trust-text { font-size: clamp(9px,0.72vw,11px); color: rgba(255,255,255,0.55); font-weight: 600; }

/* ── FLOATING CIRCLE BADGES (FLANKING THE CIRCLE) ── */
.circle-badge {
    position: absolute;
    background: rgba(255,255,255,0.97);
    border-radius: 16px;
    padding: 9px 12px;
    box-shadow: 0 12px 32px rgba(0,0,0,0.28);
    display: flex; align-items: center; gap: 9px;
    backdrop-filter: blur(8px);
    z-index: 25;
    white-space: nowrap;
    width: clamp(142px, 10.8vw, 166px);
    max-width: 166px;
    pointer-events: none;
    transform: translateY(-50%);
}

/* Left & Right Flanking Base Positioning */
.cb-left { right: calc(100% - 18px); }
.cb-right { left: calc(100% - 18px); }

/* Keep every badge touching the same gold ring */
.cb-left.cb-1, .cb-left.cb-2, .cb-left.cb-3, .cb-left.cb-4 { right: calc(100% - 18px); }
.cb-right.cb-5, .cb-right.cb-6, .cb-right.cb-7, .cb-right.cb-8 { left: calc(100% - 18px); }

/* Equal vertical spacing from top to bottom */
.cb-1, .cb-5 { top: 20%; }
.cb-2, .cb-6 { top: 40%; }
.cb-3, .cb-7 { top: 60%; }
.cb-4, .cb-8 { top: 80%; }

/* Unique Blink Animations */
.cb-1 { animation: badgeBlinkA 8s ease-in-out infinite; }
.cb-2 { animation: badgeBlinkB 8.8s ease-in-out infinite .4s; }
.cb-3 { animation: badgeBlinkC 9.4s ease-in-out infinite .8s; }
.cb-4 { animation: badgeBlinkD 10s ease-in-out infinite 1.2s; }
.cb-5 { animation: badgeBlinkB 8.4s ease-in-out infinite .2s; }
.cb-6 { animation: badgeBlinkC 9.1s ease-in-out infinite .6s; }
.cb-7 { animation: badgeBlinkD 9.7s ease-in-out infinite 1s; }
.cb-8 { animation: badgeBlinkA 10.3s ease-in-out infinite 1.4s; }

@keyframes badgeBlinkA { 0%,18%,100%{opacity:1} 28%,38%{opacity:.15} 48%{opacity:1} }
@keyframes badgeBlinkB { 0%,22%,100%{opacity:1} 32%,44%{opacity:.2} 56%{opacity:1} }
@keyframes badgeBlinkC { 0%,16%,100%{opacity:1} 26%,40%{opacity:.18} 52%{opacity:1} }
@keyframes badgeBlinkD { 0%,20%,100%{opacity:1} 30%,42%{opacity:.12} 54%{opacity:1} }

.pb-icon { width: 24px; height: 24px; border-radius: 7px; display: flex; align-items: center; justify-content: center; flex-shrink: 0; }
.pb-main { font-family: var(--font-display); font-size: 10px; font-weight: 800; color: var(--navy); line-height: 1; }
.pb-sub { font-size: 7px; color: var(--muted); font-weight: 600; margin-top: 2px; }


/* phone zone */
.hero-right {
    flex: 0 0 auto; margin-left: clamp(84px,8vw,150px);
    display: flex; align-items: center; justify-content: center; position: relative;
    overflow: visible;
    z-index: 18;
}

.hero-phone {
    width: clamp(240px, 24vw, 320px);
    border-radius: 36px; background: #08091a;
    border: 2.5px solid rgba(255,255,255,0.14);
    box-shadow: 0 32px 80px rgba(0,0,0,0.70), 0 0 0 1px rgba(255,255,255,0.06),
                inset 0 0 30px rgba(255,255,255,0.02);
    overflow: hidden; position: relative;
    animation: phoneFloat 5s ease-in-out infinite; z-index: 2;
}
@keyframes phoneFloat { 0%,100%{transform:translateY(0) rotate(1.5deg)} 50%{transform:translateY(-18px) rotate(1.5deg)} }

.phone-notch-bar {
    position: absolute; top: 0; left: 50%; transform: translateX(-50%);
    width: 90px; height: 24px; background: #08091a;
    border-radius: 0 0 18px 18px; z-index: 10;
    display: flex; align-items: center; justify-content: center; gap: 6px;
}
.notch-cam { width: 8px; height: 8px; border-radius: 50%; background: #1a1b2e; border: 1px solid rgba(255,255,255,0.08); }

.phone-screen {
    width: 100%;
    background: linear-gradient(175deg, #000f3a 0%, #001260 30%, #000830 70%, #00041a 100%);
    padding: 30px 14px 14px; display: flex; flex-direction: column; gap: 9px; min-height: 580px;
}
.phone-topbar { display: flex; align-items: center; justify-content: space-between; margin-bottom: 4px; }
.phone-greeting { font-size: 10px; color: rgba(255,255,255,0.45); font-weight: 600; }
.phone-live-badge {
    background: rgba(34,197,94,0.18); color: #22c55e; font-size: 8px; font-weight: 800;
    letter-spacing: 1px; padding: 3px 8px; border-radius: 20px;
    border: 1px solid rgba(34,197,94,0.30); display: flex; align-items: center; gap: 4px;
}
.live-dot { width: 5px; height: 5px; border-radius: 50%; background: #22c55e; animation: blink 1.2s infinite; }
.phone-brand { font-family: var(--font-display); font-size: 16px; font-weight: 900; color: #fff; letter-spacing: -0.5px; }
.phone-brand span { color: var(--gold-bright); }
.phone-balance-card {
    background: linear-gradient(135deg, rgba(255,255,255,0.10), rgba(255,255,255,0.05));
    border: 1px solid rgba(255,255,255,0.10); border-radius: 16px; padding: 14px 14px 12px; position: relative; overflow: hidden;
}
.phone-balance-card::before {
    content: ''; position: absolute; top: -40px; right: -40px; width: 100px; height: 100px;
    background: radial-gradient(circle, rgba(197,160,89,0.22) 0%, transparent 70%); pointer-events: none;
}
.pbc-label { font-size: 8px; color: rgba(255,255,255,0.45); font-weight: 700; text-transform: uppercase; letter-spacing: 1.2px; margin-bottom: 6px; }
.pbc-value { font-family: var(--font-display); font-size: 24px; font-weight: 900; color: #fff; line-height: 1; letter-spacing: -1px; margin-bottom: 6px; }
.pbc-change { display: inline-flex; align-items: center; gap: 4px; background: rgba(34,197,94,0.15); color: #22c55e; font-size: 9px; font-weight: 700; padding: 2px 8px; border-radius: 20px; }
.phone-mini-stats { display: grid; grid-template-columns: 1fr 1fr; gap: 8px; }
.phone-mini-stat { background: rgba(255,255,255,0.06); border: 1px solid rgba(255,255,255,0.08); border-radius: 12px; padding: 10px 11px; }
.pms-label { font-size: 8px; color: rgba(255,255,255,0.40); font-weight: 700; text-transform: uppercase; letter-spacing: 0.8px; margin-bottom: 5px; }
.pms-value { font-family: var(--font-display); font-size: 14px; font-weight: 800; color: #fff; }
.pms-value.gold { color: var(--gold-bright); }
.pms-value.green { color: #22c55e; }
.pms-value.red { color: #ef4444; }
.phone-chart-card { background: rgba(255,255,255,0.05); border: 1px solid rgba(255,255,255,0.07); border-radius: 12px; padding: 10px 12px; }
.pcc-header { display: flex; align-items: center; justify-content: space-between; margin-bottom: 8px; }
.pcc-title { font-size: 8px; color: rgba(255,255,255,0.45); font-weight: 700; text-transform: uppercase; letter-spacing: 0.8px; }
.pcc-val { font-size: 9px; color: var(--gold-bright); font-weight: 800; }
.phone-bars { display: flex; align-items: flex-end; gap: 4px; height: 44px; }
.pbar { flex: 1; border-radius: 3px 3px 0 0; }
.phone-txn-list { display: flex; flex-direction: column; gap: 6px; }
.phone-txn { display: flex; align-items: center; gap: 9px; background: rgba(255,255,255,0.06); border: 1px solid rgba(255,255,255,0.07); border-radius: 10px; padding: 8px 10px; }
.txn-icon { width: 28px; height: 28px; border-radius: 8px; display: flex; align-items: center; justify-content: center; font-size: 12px; flex-shrink: 0; }
.txn-info { flex: 1; min-width: 0; }
.txn-name { font-size: 9px; font-weight: 700; color: rgba(255,255,255,0.85); white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
.txn-time { font-size: 7.5px; color: rgba(255,255,255,0.35); font-weight: 600; margin-top: 1px; }
.txn-amt { font-family: var(--font-display); font-size: 10px; font-weight: 800; white-space: nowrap; }
.txn-amt.pos { color: #22c55e; }
.txn-amt.neg { color: #ef4444; }
.txn-amt.neu { color: rgba(255,255,255,0.6); }
.phone-bottom-nav {
    display: flex; align-items: center; justify-content: space-around;
    background: rgba(0,0,0,0.45); border-top: 1px solid rgba(255,255,255,0.07);
    padding: 10px 0 8px; margin: 4px -14px -14px; backdrop-filter: blur(10px);
}
.pbn-item { display: flex; flex-direction: column; align-items: center; gap: 3px; opacity: 0.4; }
.pbn-item.active { opacity: 1; }
.pbn-item svg { width: 14px; height: 14px; }
.pbn-label { font-size: 7px; font-weight: 700; color: rgba(255,255,255,0.7); }
.pbn-item.active .pbn-label { color: var(--gold-bright); }
.pbn-item.active svg { stroke: var(--gold-bright) !important; }

/* FX ticker */
.hero-ticker {
    position: relative; z-index: 3; background: rgba(0,0,0,0.42);
    border-top: 1px solid rgba(197,160,89,0.25); padding: 9px 0;
    overflow: hidden; margin-top: auto; backdrop-filter: blur(8px);
}
.ticker-label {
    position: absolute; left: 0; top: 0; bottom: 0;
    background: linear-gradient(135deg, var(--gold), var(--gold-bright));
    color: var(--navy); font-weight: 900; font-size: 0.58rem; letter-spacing: 2px;
    text-transform: uppercase; padding: 0 16px; display: flex; align-items: center; gap: 5px;
    z-index: 2; white-space: nowrap;
}
.ticker-track-wrap { overflow: hidden; padding-left: 110px; }
.ticker-track { display: flex; animation: tickerScroll 40s linear infinite; width: max-content; }
.ticker-track:hover { animation-play-state: paused; }
@keyframes tickerScroll { 0%{transform:translateX(0)} 100%{transform:translateX(-50%)} }

.benefit-belt {
    padding: 40px 18px 0;
    background: #fff;
}
.benefit-grid {
    display: grid; grid-template-columns: repeat(4,1fr); gap: 14px;
    max-width: 1280px; margin: 0 auto; padding-bottom: 16px;
}
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
@keyframes fadeInUp { from{opacity:0;transform:translateY(24px)} to{opacity:1;transform:translateY(0)} }
@keyframes floatBob { 0%,100%{transform:translateY(0)} 50%{transform:translateY(-8px)} }

/* ── STATS ── */
.stats-section { background: #fff; border-bottom: 1px solid var(--border); padding: 50px 0; }
.stat-box h2 { font-family: var(--font-display); font-size: clamp(2rem,4vw,2.8rem); font-weight: 900; color: var(--navy); margin: 0; transition: transform 0.3s; }
.stat-box h2:hover { transform: scale(1.06); }
.stat-box p { font-size: 0.72rem; font-weight: 800; text-transform: uppercase; letter-spacing: 2px; color: var(--muted); margin: 6px 0 0; }

/* ── DASHBOARD MOCKUP ── */
.db-frame {
    background: #fff; border-radius: var(--radius-lg); box-shadow: var(--shadow-lg); overflow: hidden;
    transform: perspective(1200px) rotateY(-4deg) rotateX(2deg);
    transition: transform 0.5s ease, box-shadow 0.5s ease;
}
.db-frame:hover { transform: perspective(1200px) rotateY(0deg) rotateX(0deg); box-shadow: 0 40px 80px rgba(0,35,71,0.18); }
.db-frame-r { transform: perspective(1200px) rotateY(4deg) rotateX(2deg); }
.db-frame-r:hover { transform: perspective(1200px) rotateY(0deg) rotateX(0deg); }
.db-bar { background: #f5f7fa; padding: 11px 18px; display: flex; align-items: center; gap: 7px; border-bottom: 1px solid #e8ecf0; }
.db-dot { width: 11px; height: 11px; border-radius: 50%; }
.db-dot-r{background:#ff5f57} .db-dot-y{background:#ffbd2e} .db-dot-g{background:#28c840}
.db-bar-title { margin-left: 10px; font-size: 11px; font-weight: 600; color: #8a92a0; }
.db-sidebar { background: var(--navy); width: 50px; padding: 16px 0; display: flex; flex-direction: column; align-items: center; gap: 16px; flex-shrink: 0; }
.db-icon { width: 30px; height: 30px; border-radius: 8px; background: rgba(255,255,255,0.08); display: flex; align-items: center; justify-content: center; }
.db-icon.on { background: var(--gold); }
.db-icon svg { width: 13px; height: 13px; }
.db-body { flex: 1; padding: 18px; overflow: hidden; }
.db-head { display: flex; align-items: center; justify-content: space-between; margin-bottom: 16px; }
.db-title { font-family: var(--font-display); font-size: 13px; font-weight: 700; color: var(--navy); }
.db-badge { background: var(--surface-2); color: var(--navy); font-size: 10px; font-weight: 700; padding: 4px 10px; border-radius: 20px; }
.db-kpi-row { display: grid; grid-template-columns: repeat(3,1fr); gap: 9px; margin-bottom: 14px; }
.db-kpi { background: var(--surface-2); border: 1px solid var(--border); border-radius: 10px; padding: 11px 12px; position: relative; overflow: hidden; }
.db-kpi::before { content:''; position:absolute; top:0; left:0; width:3px; height:100%; background: var(--gold); }
.db-kpi-lbl { font-size: 8.5px; font-weight: 700; text-transform: uppercase; letter-spacing: 1px; color: var(--muted); margin-bottom: 5px; }
.db-kpi-val { font-family: var(--font-display); font-size: 17px; font-weight: 800; color: var(--navy); line-height: 1; }
.db-kpi-sub { font-size: 8.5px; color: #22c55e; font-weight: 700; margin-top: 4px; }
.db-kpi-sub.neg { color: #ef4444; }
.db-chart-2col { display: grid; grid-template-columns: 1.6fr 1fr; gap: 9px; margin-bottom: 14px; }
.db-chart-box { background: #fff; border: 1px solid var(--border); border-radius: 10px; padding: 12px; }
.db-chart-lbl { font-size: 9.5px; font-weight: 700; color: var(--navy); margin-bottom: 10px; text-transform: uppercase; letter-spacing: 0.5px; }
.db-table { width: 100%; border-collapse: collapse; font-size: 9.5px; }
.db-table th { text-align: left; padding: 6px 8px; font-size: 8.5px; font-weight: 700; text-transform: uppercase; color: var(--muted); border-bottom: 1px solid var(--border); }
.db-table td { padding: 7px 8px; border-bottom: 1px solid #f0f4f8; color: #3d4a5c; font-weight: 500; }
.db-table tr:last-child td { border-bottom: none; }
.db-pill { display: inline-block; padding: 2px 8px; border-radius: 20px; font-size: 8px; font-weight: 700; }
.db-pill.paid    { background: #dcfce7; color: #15803d; }
.db-pill.pending { background: #fef9c3; color: #854d0e; }
.db-pill.due     { background: #fee2e2; color: #991b1b; }

.gadget-stage {
    position: relative;
    background: linear-gradient(145deg, rgba(255,255,255,0.96), rgba(241,246,253,0.96));
    border: 1px solid rgba(0,35,71,0.08);
    border-radius: 30px;
    padding: 24px 18px 18px;
    box-shadow: 0 30px 70px rgba(0,35,71,0.14);
    overflow: hidden;
}
.gadget-stage::before {
    content: '';
    position: absolute;
    inset: 12px;
    border: 1px solid rgba(0,35,71,0.06);
    border-radius: 24px;
    pointer-events: none;
}
.gadget-grid {
    position: relative;
    z-index: 1;
    display: grid;
    grid-template-columns: 1.3fr 0.95fr;
    gap: 14px;
}
.gadget-stack {
    display: grid;
    gap: 14px;
}
.gadget-card {
    background: rgba(255,255,255,0.97);
    border: 1px solid rgba(214,224,236,0.95);
    border-radius: 22px;
    padding: 16px;
    box-shadow: 0 16px 34px rgba(15,23,42,0.08);
}
.gadget-topline {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 10px;
    margin-bottom: 14px;
}
.gadget-title {
    font-family: var(--font-display);
    font-size: 12px;
    font-weight: 800;
    color: var(--navy);
}
.gadget-chip {
    display: inline-flex;
    align-items: center;
    gap: 7px;
    padding: 7px 11px;
    border-radius: 999px;
    background: rgba(0,35,71,0.06);
    color: var(--navy);
    font-size: 10px;
    font-weight: 800;
    letter-spacing: 0.5px;
    text-transform: uppercase;
}
.gadget-dot {
    width: 7px;
    height: 7px;
    border-radius: 50%;
    background: #22c55e;
    box-shadow: 0 0 0 4px rgba(34,197,94,0.16);
}
.gadget-card.accent-gold {
    background: linear-gradient(180deg, #fffdf7, #fff7e8);
}
.gadget-card.accent-mint {
    background: linear-gradient(180deg, #f6fffb, #ebfbf4);
}
.gadget-card.accent-rose {
    background: linear-gradient(180deg, #fff8f8, #fff0f1);
}
.gadget-card.accent-blue {
    background: linear-gradient(180deg, #f8fbff, #edf4ff);
}
.gadget-stat-grid {
    display: grid;
    grid-template-columns: repeat(3, minmax(0, 1fr));
    gap: 10px;
}
.gadget-stat {
    background: rgba(240,244,252,0.92);
    border: 1px solid rgba(219,228,239,0.95);
    border-radius: 16px;
    padding: 12px;
}
.gadget-stat-label {
    font-size: 9px;
    font-weight: 800;
    text-transform: uppercase;
    letter-spacing: 0.8px;
    color: #718096;
    margin-bottom: 6px;
}
.gadget-stat-value {
    font-family: var(--font-display);
    font-size: 18px;
    font-weight: 800;
    line-height: 1.05;
    color: var(--navy);
}
.gadget-stat-meta {
    margin-top: 5px;
    font-size: 9px;
    font-weight: 700;
    color: #16a34a;
}
.gadget-stat-meta.neg { color: #ef4444; }
.gadget-mini-list {
    display: grid;
    gap: 10px;
}
.gadget-mini-row {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 10px;
    padding-bottom: 10px;
    border-bottom: 1px solid rgba(226,232,240,0.85);
}
.gadget-mini-row:last-child {
    padding-bottom: 0;
    border-bottom: none;
}
.gadget-mini-label {
    font-size: 11px;
    font-weight: 700;
    color: #334155;
}
.gadget-mini-value {
    font-family: var(--font-display);
    font-size: 13px;
    font-weight: 800;
    color: var(--navy);
}
.gadget-bars {
    display: grid;
    gap: 10px;
}
.gadget-bar-label {
    display: flex;
    align-items: center;
    justify-content: space-between;
    margin-bottom: 4px;
    font-size: 10px;
    font-weight: 700;
    color: #475569;
}
.gadget-bar-track {
    height: 9px;
    border-radius: 999px;
    background: #e9eef5;
    overflow: hidden;
}
.gadget-bar-fill {
    height: 100%;
    border-radius: inherit;
    background: linear-gradient(90deg, var(--navy), var(--gold));
}
.gadget-donut {
    min-height: 154px;
    display: grid;
    place-items: center;
}
.gadget-donut svg {
    width: 126px;
    height: 126px;
}
.gadget-orbit {
    position: absolute;
    right: -8px;
    bottom: -18px;
    width: 146px;
    padding: 12px 14px;
    border-radius: 20px;
    background: rgba(255,255,255,0.95);
    border: 1px solid rgba(0,35,71,0.08);
    box-shadow: 0 18px 34px rgba(15,23,42,0.14);
    transform: rotate(-8deg);
    z-index: 2;
}
.gadget-orbit .label {
    font-size: 9px;
    font-weight: 800;
    text-transform: uppercase;
    letter-spacing: 0.8px;
    color: #64748b;
}
.gadget-orbit .value {
    margin-top: 4px;
    font-family: var(--font-display);
    font-size: 20px;
    font-weight: 900;
    color: var(--navy);
}
.gadget-orbit .meta {
    margin-top: 4px;
    font-size: 9px;
    color: #16a34a;
    font-weight: 700;
}

/* Feature cards */
.feat-card { background: #fff; border: 1px solid var(--border); border-radius: var(--radius-md); padding: 14px 16px; transition: all 0.3s; position: relative; overflow: hidden; }
.feat-card::before { content:''; position:absolute; top:0; left:0; right:0; height:2px; background:linear-gradient(to right, var(--navy), var(--gold)); transform:scaleX(0); transform-origin:left; transition:transform 0.3s; }
.feat-card:hover { transform: translateY(-4px); box-shadow: var(--shadow-md); }
.feat-card:hover::before { transform: scaleX(1); }
.feat-card-dark { background: rgba(255,255,255,0.06); border: 1px solid rgba(255,255,255,0.10); border-radius: var(--radius-md); padding: 16px 18px; transition: all 0.3s; }
.feat-card-dark:hover { background: rgba(255,255,255,0.10); border-color: rgba(197,160,89,0.35); }
.feat-icon { width: 42px; height: 42px; border-radius: 11px; background: var(--surface-3); display: flex; align-items: center; justify-content: center; flex-shrink: 0; transition: 0.3s; }
.feat-card:hover .feat-icon, .feat-card-dark:hover .feat-icon { background: var(--gold); }
.feat-icon svg { width: 20px; height: 20px; }
.feat-icon-dark { background: rgba(197,160,89,0.18); }
.feat-icon-dark svg { stroke: var(--gold); }
.feat-card h6 { font-family: var(--font-display); font-size: 13.5px; font-weight: 700; color: var(--navy); margin: 0 0 3px; }
.feat-card p  { font-size: 12px; color: var(--muted); line-height: 1.65; margin: 0; }
.feat-card-dark h6 { color: #fff; }
.feat-card-dark p  { color: rgba(255,255,255,0.62); }

/* Float badges */
.float-badge { position: absolute; background: #fff; border-radius: var(--radius-md); padding: 10px 14px; box-shadow: 0 10px 28px rgba(0,0,0,0.12); display: flex; align-items: center; gap: 10px; z-index: 10; }
.float-badge.fb-1 { top: 0; left: -10px; animation: floatBob 4s ease-in-out infinite; }
.float-badge.fb-2 { bottom: 0; right: -10px; animation: floatBob 4s ease-in-out infinite 2s; }
.fb-icon { width: 36px; height: 36px; border-radius: 10px; display: flex; align-items: center; justify-content: center; flex-shrink: 0; }
.fb-val { font-family: var(--font-display); font-size: 15px; font-weight: 800; color: var(--navy); line-height: 1; }
.fb-lbl { font-size: 10px; color: var(--muted); font-weight: 600; margin-top: 1px; }

/* Progress bars */
.prog-row { margin-bottom: 13px; }
.prog-labels { display: flex; justify-content: space-between; font-family: var(--font-display); font-size: 12px; font-weight: 700; color: var(--navy); margin-bottom: 6px; }
.prog-track { height: 8px; background: var(--surface-3); border-radius: 99px; overflow: hidden; }
.prog-fill { height: 100%; border-radius: 99px; background: linear-gradient(to right, var(--navy), var(--gold)); width: 0; transition: width 1.5s cubic-bezier(0.4,0,0.2,1); }
.prog-fill.go { width: var(--w); }

/* Strip */
.strip-section { background: linear-gradient(135deg, var(--surface-2) 0%, #fff 100%); border-top: 1px solid var(--border); border-bottom: 1px solid var(--border); padding: 80px 0; }
.strip-card { background: #fff; border: 1px solid var(--border); border-radius: var(--radius-lg); padding: 30px 26px; height: 100%; position: relative; overflow: hidden; transition: all 0.4s cubic-bezier(.175,.885,.32,1.275); }
.strip-card::before { content:''; position:absolute; top:0; left:0; right:0; height:3px; background:linear-gradient(to right, var(--navy), var(--gold)); transform:scaleX(0); transform-origin:left; transition:transform 0.4s; }
.strip-card:hover { transform: translateY(-8px); box-shadow: var(--shadow-lg); }
.strip-card:hover::before { transform: scaleX(1); }
.strip-icon { width: 54px; height: 54px; border-radius: var(--radius-md); display: flex; align-items: center; justify-content: center; margin-bottom: 18px; font-size: 22px; }
.strip-card h5 { font-family: var(--font-display); font-size: 15px; font-weight: 800; color: var(--navy); margin: 0 0 10px; }
.strip-card p  { font-size: 13px; color: var(--muted); line-height: 1.75; margin: 0; }

/* Solutions */
.sol-grid { display: grid; grid-template-columns: repeat(auto-fit,minmax(270px,1fr)); gap: 28px; }
.sol-tile { background: var(--surface-2); border: 1px solid var(--border); border-radius: var(--radius-md); padding: 42px 24px; transition: all 0.5s cubic-bezier(.175,.885,.32,1.275); height: 100%; overflow: hidden; position: relative; }
.sol-tile::after { content:''; position:absolute; bottom:0; left:0; width:0; height:4px; background:var(--gold); transition:width 0.4s ease; }
.sol-tile:hover { background: #fff; transform: translateY(-10px); box-shadow: var(--shadow-lg); border-color: var(--gold); }
.sol-tile:hover::after { width: 100%; }
.sol-tile i { transition: all 0.4s; }
.sol-tile:hover i { transform: scale(1.18) rotate(4deg); color: var(--gold) !important; }

/* Capabilities */
.cap-img { padding: 12px; background: #fff; border: 2px solid var(--gold); border-radius: var(--radius-md); overflow: hidden; transition: all 0.4s; position: relative; }
.cap-img::before { content:''; position:absolute; top:-6px; left:-6px; right:-6px; bottom:-6px; border:1px solid rgba(197,160,89,0.28); border-radius: var(--radius-md); pointer-events:none; }
.cap-img:hover { transform: scale(1.02) rotate(0.8deg); box-shadow: var(--shadow-lg); }
.cap-img img { border-radius: 4px; transition: transform 0.4s; width: 100%; }
.cap-img:hover img { transform: scale(1.04); }

/* Projects */
.project-card { border: 1px solid var(--border); background: #fff; border-radius: var(--radius-md); overflow: hidden; transition: all 0.4s; height: 100%; box-shadow: var(--shadow-sm); }
.project-card:hover { transform: translateY(-8px); box-shadow: var(--shadow-lg); }
.project-img { height: 380px; overflow: hidden; border-bottom: 4px solid var(--gold); }
.project-img img { width: 100%; height: 100%; object-fit: cover; transition: all 0.6s; filter: grayscale(20%); }
.project-card:hover img { transform: scale(1.07); filter: grayscale(0%); }

/* Testimonials */
.testi-section { padding: 100px 0; overflow: hidden; position: relative; }
.testi-track-wrap { overflow: hidden; }
.testi-track { display: flex; gap: 22px; animation: scrollInfinite 50s linear infinite; width: max-content; }
.testi-track:hover { animation-play-state: paused; }
@keyframes scrollInfinite { 0%{transform:translateX(0)} 100%{transform:translateX(-50%)} }
.testi-card { width: 340px; flex-shrink: 0; background: rgba(255,255,255,0.05); border: 1px solid rgba(197,160,89,0.35); padding: 32px; border-radius: var(--radius-md); backdrop-filter: blur(10px); transition: all 0.3s; }
.testi-card:hover { background: rgba(255,255,255,0.08); border-color: var(--gold); transform: translateY(-5px); }
.testi-avatar { width: 44px; height: 44px; border-radius: 50%; object-fit: cover; border: 2px solid var(--gold); }

/* Pricing */
.plan-card { border: 1px solid var(--border); background: #fff; padding: 42px 28px; border-radius: var(--radius-md); height: 100%; display: flex; flex-direction: column; transition: all 0.4s; border-top: 3px solid transparent; box-shadow: var(--shadow-sm); }
.plan-card:hover:not(.plan-featured) { border-top-color: var(--gold); transform: translateY(-7px); box-shadow: var(--shadow-lg); }
.plan-featured { border: 2px solid var(--gold); background: var(--surface-2); border-top-color: var(--gold); transform: scale(1.03); box-shadow: var(--shadow-gold); }
.plan-name { font-family: var(--font-display); font-size: 0.7rem; font-weight: 800; text-transform: uppercase; letter-spacing: 1.5px; color: var(--muted); margin-bottom: 16px; text-align: center; }
.plan-price { font-family: var(--font-display); font-size: clamp(1.8rem,3vw,2.2rem); font-weight: 900; color: var(--navy); text-align: center; margin-bottom: 32px; }
.plan-feature { display: flex; align-items: flex-start; gap: 10px; margin-bottom: 12px; font-size: 13px; color: var(--muted); }
.plan-feature i { color: #22c55e; margin-top: 2px; flex-shrink: 0; }

/* Footer */
.sb-footer { background: var(--surface-2); padding: 96px 0 40px; border-top: 5px solid var(--gold); position: relative; overflow: hidden; }
.sb-footer::before { content:''; position:absolute; inset:0; background:repeating-linear-gradient(45deg,transparent,transparent 48px,rgba(197,160,89,0.04) 48px,rgba(197,160,89,0.04) 50px); pointer-events:none; }
.footer-link { color: var(--muted); text-decoration: none; font-size: 13px; transition: all 0.2s; }
.footer-link:hover { color: var(--gold); transform: translateX(3px); display: inline-block; }
.footer-social { width: 38px; height: 38px; border-radius: 50%; background: rgba(0,35,71,0.06); display: flex; align-items: center; justify-content: center; color: var(--navy); transition: all 0.3s; text-decoration: none; }
.footer-social:hover { background: var(--gold); color: #fff; transform: scale(1.1); }
.map-wrap { border: 10px solid #fff; border-radius: var(--radius-md); box-shadow: var(--shadow-md); overflow: hidden; min-height: 480px; }

/* Floating Support Widget */
.spb-support {
    position: fixed;
    right: 20px;
    bottom: 18px;
    z-index: 10020;
    font-family: var(--font-display);
}
.spb-support-bubble {
    width: 64px;
    height: 64px;
    border-radius: 50%;
    border: none;
    background: linear-gradient(135deg, #ef4444 0%, #b91c1c 100%);
    color: #ffffff;
    box-shadow: 0 10px 28px rgba(185, 28, 28, 0.42);
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 24px;
    cursor: pointer;
}
.spb-support-floating {
    position: absolute;
    right: 0;
    bottom: 78px;
    width: min(320px, calc(100vw - 24px));
    pointer-events: none;
}
.spb-float-msg {
    background: #fff;
    border: 1px solid #d3deeb;
    color: #0b2a63;
    border-radius: 14px;
    padding: 12px 14px;
    box-shadow: 0 8px 18px rgba(15, 23, 42, 0.12);
    font-weight: 700;
    opacity: 0;
    transform: translateY(8px);
    transition: opacity .35s ease, transform .35s ease;
}
.spb-float-msg + .spb-float-msg { margin-top: 10px; }
.spb-float-msg.show {
    opacity: 1;
    transform: translateY(0);
}
.spb-support-panel {
    position: absolute;
    right: 0;
    bottom: 78px;
    width: min(410px, calc(100vw - 24px));
    background: #fff;
    border: 1px solid #d3deeb;
    border-radius: 16px;
    box-shadow: 0 20px 40px rgba(15, 23, 42, 0.18);
    overflow: hidden;
    opacity: 0;
    transform: translateY(10px) scale(.98);
    pointer-events: none;
    transition: all .3s ease;
    max-height: min(72vh, 460px);
    overflow-y: auto;
}
.spb-support.open .spb-support-panel {
    opacity: 1;
    transform: translateY(0) scale(1);
    pointer-events: auto;
}
.spb-support.open .spb-support-floating { display: none; }
.spb-panel-head {
    background: #0b2a63;
    color: #fff;
    padding: 14px 16px;
    font-weight: 800;
    display: flex;
    align-items: center;
    justify-content: space-between;
}
.spb-close-x {
    width: 30px;
    height: 30px;
    border-radius: 50%;
    border: none;
    background: #14d676;
    color: #083f35;
    font-weight: 900;
}
.spb-panel-body { padding: 14px 16px; }
.spb-panel-note { color: #475569; margin-bottom: 12px; }
.spb-support-btn {
    width: 100%;
    border: 1px solid #d3deeb;
    border-radius: 10px;
    background: #fff;
    color: #0b2a63;
    font-weight: 700;
    padding: 11px 12px;
    margin-bottom: 10px;
}
.spb-support-btn.primary {
    background: #0ea05d;
    color: #fff;
    border-color: #0ea05d;
}
.spb-support-mini {
    font-size: 12px;
    color: #64748b;
}
.spb-panel-form {
    border-top: 1px solid #e2e8f0;
    margin-top: 12px;
    padding-top: 12px;
}
.spb-panel-form input, .spb-panel-form textarea {
    width: 100%;
    border: 1px solid #d8e2f0;
    border-radius: 10px;
    padding: 9px 10px;
    font-size: 13px;
    margin-bottom: 8px;
}
.spb-panel-form textarea { min-height: 78px; resize: vertical; }
@media (max-width: 575px) {
    .spb-support { right: 8px; bottom: 12px; }
    .spb-support-floating { width: min(300px, calc(100vw - 16px)); }
    .spb-support-panel { right: 0; bottom: 76px; width: min(360px, calc(100vw - 16px)); }
}

/* ═══════════════════════════════════════════════════════════
   RESPONSIVE
═══════════════════════════════════════════════════════════ */
@media (max-width: 1100px) {
    .hero-right { margin-left: clamp(42px,4vw,72px); }
    .hero-phone { width: clamp(220px,22vw,280px); }
}
@media (max-width: 991px) {
    :root { --nav-h: 64px; --announce-h: 34px; }
    .benefit-grid { grid-template-columns: repeat(2,1fr); }
    .hero-content { flex-direction: column; align-items: center; padding: 24px 20px 28px; gap: 40px; }
    .hero-right { margin-left: 0; }
    .hero-circle { width: clamp(320px,82vw,460px) !important; height: clamp(320px,82vw,460px) !important; }
    .hero-phone { width: clamp(220px,50vw,300px); }
    .sb-brand img { height: 52px; }
    
    #mujiNav {
        position: fixed;
        top: var(--nav-h);
        left: -100%;
        width: 100%;
        height: calc(100vh - var(--nav-h));
        background: #fff;
        padding: 24px 16px;
        overflow-y: auto;
        overflow-x: hidden;
        transition: left .35s ease;
        box-shadow: inset 0 2px 14px rgba(0,0,0,.04);
        z-index: 9998;
    }
    #mujiNav.show {
        left: 0 !important;
        display: block !important;
    }
    #mujiNav .navbar-nav { align-items: stretch !important; gap: 0 !important; }
    #mujiNav .nav-item { width: 100%; border-bottom: 1px solid #eef3fb; }
    #mujiNav .nav-item:last-child { border-bottom: none; }
    #mujiNav .sb-nav-link { display: flex; align-items: center; padding: 14px 12px !important; border-radius: var(--radius-sm) !important; font-size: 0.82rem !important; color: var(--navy) !important; width: 100%; }
    #mujiNav .sb-nav-link:hover { background: rgba(28,102,232,0.06) !important; color: #1c66e8 !important; }
    #mujiNav .btn-portal { width: 100%; justify-content: center; margin-top: 12px; }
    #mujiNav .ms-lg-3 { margin-left: 0 !important; margin-top: 4px; }
}
@media (max-width: 768px) {
    :root { --announce-h: 34px; }
    .sb-section { padding: 70px 0; }
    .db-chart-2col { grid-template-columns: 1fr; }
    .db-kpi-row { grid-template-columns: repeat(2,1fr); }
    .db-kpi-row .db-kpi:last-child { display: none; }
    .gadget-grid { grid-template-columns: 1fr; }
    .gadget-orbit { position: static; transform: none; width: 100%; margin-top: 14px; }
    .announce-label { font-size: 0 !important; padding: 0 12px !important; }
    .announce-msg { font-size: 0.65rem !important; }
    .hero-circle { width: min(90vw,400px) !important; height: min(90vw,400px) !important; padding: min(58px,14%) !important; }
    .hero-phone { width: clamp(230px,62vw,300px); }
    .hero-h1 { font-size: clamp(1.3rem,5.5vw,1.9rem) !important; }
    .hero-body { font-size: clamp(11px,3.2vw,13px) !important; }
    .hero-btn-red, .hero-btn-ghost { padding: 13px 24px !important; font-size: clamp(0.70rem,3vw,0.80rem) !important; }
    .hero-cta-stack { max-width: min(240px,62vw) !important; gap: 10px !important; }
    
    .circle-badge { display: none !important; } /* Hide flanking badges on mobile */
    
    .benefit-card { border-color: var(--border); background: #fff; }
    .benefit-belt { padding: 32px 12px 0; }
}
@media (max-width: 640px) {
    .hero-content { padding: 16px 16px 24px; gap: 28px; }
    .hero-circle { width: min(90vw,340px) !important; height: min(90vw,340px) !important; padding: clamp(52px,16%,72px) !important; }
    .hero-phone { width: clamp(220px,72vw,280px); }
    .hero-h1 { font-size: clamp(1rem,4.5vw,1.45rem) !important; }
    .hero-body { font-size: 11px !important; }
    .hero-cta-stack { max-width: min(196px,56vw) !important; }
    .hero-trust { display: none !important; }
    .hero-eyebrow { margin-bottom: 10px !important; }
    .hero-body { margin-bottom: 16px !important; }
    .gadget-stage { padding: 20px 14px 14px; border-radius: 24px; }
    .gadget-stat-grid { grid-template-columns: 1fr; }
    .benefit-card { border: 1px solid var(--border); box-shadow: none; }
    .benefit-card:hover { transform: none; box-shadow: none; }
}
@media (max-width: 480px) {
    :root { --nav-h: 60px; --announce-h: 32px; }
    .sb-brand img { height: 44px; }
    .benefit-grid { grid-template-columns: 1fr; }
    .ticker-label { font-size: 0 !important; width: 34px; }
    .ticker-track-wrap { padding-left: 34px; }
    .benefit-belt { padding: 28px 12px 0; }
    .spb-nav-wordmark { font-size: .84rem; letter-spacing: -0.2px; }
}
</style>

{{-- NAVBAR --}}
<nav class="navbar navbar-expand-lg fixed-top sb-nav" id="mainNav">
    <div class="container">
        <a class="sb-brand navbar-brand" href="#home">
            <img src="{{ asset('assets/img/logos.png') }}" alt="SmartProbook" class="sb-brand-logo">
            <span class="spb-nav-wordmark">SmartPro<span class="book">book</span></span>
        </a>
        <button class="navbar-toggler border-0" type="button" aria-controls="mujiNav" aria-expanded="false" style="color:var(--navy);">
            <span class="tog-bar"></span><span class="tog-bar"></span><span class="tog-bar"></span>
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
                    <a class="btn-portal" href="{{ route('login', ['portal' => 1]) }}">
                        <i class="fas fa-lock" style="font-size:.75rem;"></i> CLIENT PORTAL
                    </a>
                </li>
            </ul>
        </div>
    </div>
</nav>

{{-- ANNOUNCEMENT BAR --}}
<div class="announce-bar" id="announceBar">
    <div class="announce-label"><span class="announce-dot"></span> 📡 LIVE UPDATES</div>
    <div class="announce-track" id="announceTrack">
        <div class="announce-msg active" id="msg0"><i class="fas fa-star" style="color:var(--gold);font-size:.6rem;"></i> SmartProbook v3.0 — Now with AI-powered payroll automation</div>
        <div class="announce-msg" id="msg1"><i class="fas fa-shield-alt" style="color:var(--gold);font-size:.6rem;"></i> ISO 27001 Certified · Your data is fully encrypted &amp; secured</div>
        <div class="announce-msg" id="msg2"><i class="fas fa-bolt" style="color:var(--gold);font-size:.6rem;"></i> New: One-click FIRS VAT report generation · Try it today</div>
        <div class="announce-msg" id="msg3"><i class="fas fa-users" style="color:var(--gold);font-size:.6rem;"></i> Trusted by 60,000+ businesses across Africa &amp; beyond</div>
    </div>
</div>

{{-- HERO --}}
<section id="home" class="hero-wrap">
    <div class="hero-orb2"></div>
    <div class="hero-content">

        {{-- CIRCLE WITH FLANKING BADGES --}}
        <div class="hero-left">
            <div class="hero-circle-wrapper">
                
                {{-- 4 Left Badges --}}
                <div class="circle-badge cb-left cb-1">
                    <div class="pb-icon" style="background:#fee2e2;"><svg width="14" height="14" fill="none" stroke="#b91c1c" stroke-width="2.5" viewBox="0 0 24 24"><path d="M4 19h16"/><path d="M7 15l3-4 3 2 4-6"/></svg></div>
                    <div><div class="pb-main">Cash Flow</div><div class="pb-sub">Forecast updated</div></div>
                </div>
                <div class="circle-badge cb-left cb-2">
                    <div class="pb-icon" style="background:#f3e8ff;"><svg width="14" height="14" fill="none" stroke="#7e22ce" stroke-width="2.5" viewBox="0 0 24 24"><circle cx="12" cy="8" r="3"/><path d="M5 21c1.5-3 4-5 7-5s5.5 2 7 5"/></svg></div>
                    <div><div class="pb-main">Payroll Reports</div><div class="pb-sub">Ready to export</div></div>
                </div>
                <div class="circle-badge cb-left cb-3">
                    <div class="pb-icon" style="background:#dcfce7;"><svg width="14" height="14" fill="none" stroke="#166534" stroke-width="2.5" viewBox="0 0 24 24"><path d="M3 6h18"/><path d="M3 12h18"/><path d="M3 18h18"/></svg></div>
                    <div><div class="pb-main">Balance Sheet</div><div class="pb-sub">Current period</div></div>
                </div>
                <div class="circle-badge cb-left cb-4">
                    <div class="pb-icon" style="background:#e0f2fe;"><svg width="14" height="14" fill="none" stroke="#0369a1" stroke-width="2.5" viewBox="0 0 24 24"><path d="M12 20V10"/><path d="M18 20V4"/><path d="M6 20v-6"/></svg></div>
                    <div><div class="pb-main">Live Reports</div><div class="pb-sub">Updated hourly</div></div>
                </div>

                {{-- The Circle --}}
                <div class="hero-circle">
                    <div class="hero-circle-orbit"></div>
                    <div class="hero-eyebrow">SmartProbook</div>
                    <h1 class="hero-h1">Run Your Business.<br><span class="gold-text">Know Your Money.</span></h1>
                    <p class="hero-body">Accounting-first workflow for sales, invoices, expenses, payroll and tax — all in one platform.</p>
                    <div class="hero-cta-stack">
                        <a href="#licensing" class="hero-btn-red"><i class="fas fa-shopping-cart" style="font-size:.75rem;"></i> Start Today</a>
                        <a href="{{ route('saas-register', ['type'=>'manager']) }}" class="hero-btn-ghost"><i class="fas fa-handshake" style="font-size:.75rem;"></i> Become a Partner</a>
                    </div>
                    <div class="hero-trust">
                        <div class="trust-dot"></div>
                        <span class="trust-text">Trusted by 60,000+ businesses across Africa</span>
                    </div>
                </div>

                {{-- 4 Right Badges --}}
                <div class="circle-badge cb-right cb-5">
                    <div class="pb-icon" style="background:#dcfce7;"><svg width="14" height="14" fill="none" stroke="#15803d" stroke-width="2.5" viewBox="0 0 24 24"><polyline points="22 7 13.5 15.5 8.5 10.5 2 17"/><polyline points="16 7 22 7 22 13"/></svg></div>
                    <div><div class="pb-main">+24.8%</div><div class="pb-sub">Monthly Revenue</div></div>
                </div>
                <div class="circle-badge cb-right cb-6">
                    <div class="pb-icon" style="background:#eff6ff;"><svg width="14" height="14" fill="none" stroke="#1c66e8" stroke-width="2.5" viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg></div>
                    <div><div class="pb-main">Real-time</div><div class="pb-sub">Live data sync</div></div>
                </div>
                <div class="circle-badge cb-right cb-7">
                    <div class="pb-icon" style="background:#ecfeff;"><svg width="14" height="14" fill="none" stroke="#0e7490" stroke-width="2.5" viewBox="0 0 24 24"><path d="M3 12h18"/><path d="M7 8l-4 4 4 4"/><path d="M17 8l4 4-4 4"/></svg></div>
                    <div><div class="pb-main">General Ledger</div><div class="pb-sub">Auto generated</div></div>
                </div>
                <div class="circle-badge cb-right cb-8">
                    <div class="pb-icon" style="background:#fef3c7;"><svg width="14" height="14" fill="none" stroke="#b45309" stroke-width="2.5" viewBox="0 0 24 24"><path d="M3 3h18v18H3z"/><path d="M7 8h10M7 12h10M7 16h6"/></svg></div>
                    <div><div class="pb-main">Profit & Loss</div><div class="pb-sub">Month-to-date</div></div>
                </div>

            </div>
        </div>

        {{-- PHONE --}}
        <div class="hero-right">
            <div class="hero-phone">
                <div class="phone-notch-bar">
                    <div class="notch-cam"></div>
                    <div class="notch-cam" style="width:18px;border-radius:4px;"></div>
                </div>
                <div class="phone-screen">
                    <div class="phone-topbar">
                        <span class="phone-greeting">Good morning, Victor</span>
                        <span class="phone-live-badge"><span class="live-dot"></span> LIVE</span>
                    </div>
                    <div class="phone-brand">SmartPro<span>book</span></div>
                    <div class="phone-balance-card">
                        <div class="pbc-label">Net Profit · Jan 2026</div>
                        <div class="pbc-value">₦2.8M</div>
                        <div class="pbc-change"><svg width="9" height="9" fill="none" stroke="currentColor" stroke-width="3" viewBox="0 0 24 24"><polyline points="18 15 12 9 6 15"/></svg> 22.4% margin this month</div>
                    </div>
                    <div class="phone-mini-stats">
                        <div class="phone-mini-stat"><div class="pms-label">Revenue</div><div class="pms-value">₦4.2M</div></div>
                        <div class="phone-mini-stat"><div class="pms-label">Expenses</div><div class="pms-value gold">₦1.4M</div></div>
                        <div class="phone-mini-stat"><div class="pms-label">Invoices</div><div class="pms-value green">1,248</div></div>
                        <div class="phone-mini-stat"><div class="pms-label">Overdue</div><div class="pms-value red">3</div></div>
                    </div>
                    <div class="phone-chart-card">
                        <div class="pcc-header"><span class="pcc-title">Monthly Performance</span><span class="pcc-val">↑ 18.4%</span></div>
                        <div class="phone-bars">
                            <div class="pbar" style="height:38%;background:rgba(197,160,89,0.28);"></div>
                            <div class="pbar" style="height:52%;background:rgba(197,160,89,0.36);"></div>
                            <div class="pbar" style="height:44%;background:rgba(197,160,89,0.32);"></div>
                            <div class="pbar" style="height:66%;background:rgba(197,160,89,0.50);"></div>
                            <div class="pbar" style="height:58%;background:rgba(197,160,89,0.44);"></div>
                            <div class="pbar" style="height:74%;background:rgba(197,160,89,0.62);"></div>
                            <div class="pbar" style="height:88%;background:#c5a059;"></div>
                            <div class="pbar" style="height:100%;background:linear-gradient(to top,#c5a059,#ffdf91);box-shadow:0 0 8px rgba(255,223,145,0.5);"></div>
                        </div>
                    </div>
                    <div class="phone-txn-list">
                        <div class="phone-txn">
                            <div class="txn-icon" style="background:rgba(34,197,94,0.15);">💰</div>
                            <div class="txn-info"><div class="txn-name">Sales — Today</div><div class="txn-time">Just now</div></div>
                            <div class="txn-amt pos">+₦84K</div>
                        </div>
                        <div class="phone-txn">
                            <div class="txn-icon" style="background:rgba(239,68,68,0.12);">👥</div>
                            <div class="txn-info"><div class="txn-name">Payroll Run</div><div class="txn-time">2h ago</div></div>
                            <div class="txn-amt neg">−₦320K</div>
                        </div>
                        <div class="phone-txn">
                            <div class="txn-icon" style="background:rgba(197,160,89,0.15);">✅</div>
                            <div class="txn-info"><div class="txn-name">VAT Filed</div><div class="txn-time">Today</div></div>
                            <div class="txn-amt neu">Done</div>
                        </div>
                    </div>
                    <div class="phone-bottom-nav">
                        <div class="pbn-item active">
                            <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><rect x="3" y="3" width="7" height="7"/><rect x="14" y="3" width="7" height="7"/><rect x="3" y="14" width="7" height="7"/><rect x="14" y="14" width="7" height="7"/></svg>
                            <span class="pbn-label">Home</span>
                        </div>
                        <div class="pbn-item">
                            <svg fill="none" stroke="rgba(255,255,255,.4)" stroke-width="2" viewBox="0 0 24 24"><line x1="12" y1="2" x2="12" y2="22"/><path d="M17 5H9.5a3.5 3.5 0 000 7h5a3.5 3.5 0 010 7H6"/></svg>
                            <span class="pbn-label" style="color:rgba(255,255,255,.35);">Sales</span>
                        </div>
                        <div class="pbn-item">
                            <svg fill="none" stroke="rgba(255,255,255,.4)" stroke-width="2" viewBox="0 0 24 24"><path d="M14 2H6a2 2 0 00-2 2v16a2 2 0 002 2h12a2 2 0 002-2V8z"/></svg>
                            <span class="pbn-label" style="color:rgba(255,255,255,.35);">Reports</span>
                        </div>
                        <div class="pbn-item">
                            <svg fill="none" stroke="rgba(255,255,255,.4)" stroke-width="2" viewBox="0 0 24 24"><path d="M20 21v-2a4 4 0 00-4-4H8a4 4 0 00-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
                            <span class="pbn-label" style="color:rgba(255,255,255,.35);">Profile</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

    </div>{{-- .hero-content --}}

    {{-- FX TICKER --}}
    <div class="hero-ticker">
        <div class="ticker-label"><span class="announce-dot"></span> FX LIVE</div>
        <div class="ticker-track-wrap">
            <div class="ticker-track" id="hero-ticker-track">
                <span style="color:rgba(255,255,255,0.4);font-size:12px;padding:0 16px;">Loading rates…</span>
            </div>
        </div>
    </div>
</section>

{{-- BENEFIT CARDS --}}
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

{{-- STATS --}}
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

{{-- FEATURES --}}
<section class="sb-section" id="platform-features">
    <div class="container">
        <div class="text-center mb-5">
            <span class="sb-eyebrow" style="justify-content:center;display:inline-flex;">Platform Features</span>
            <h2 class="sb-h1 text-center">Everything your business needs, <span class="accent">built in.</span></h2>
            <p class="sb-lead text-center mx-auto">SmartProbook is not just bookkeeping software. It's a complete business management system — from your first sale to your annual tax filing.</p>
        </div>

        {{-- Feature 1 --}}
        <div class="row align-items-center g-5 mb-5 pb-4">
            <div class="col-lg-7">
                <div class="position-relative" style="padding:24px 24px 24px 0;">
                    <div class="float-badge fb-1">
                        <div class="fb-icon" style="background:#dcfce7;"><svg width="16" height="16" fill="none" stroke="#15803d" stroke-width="2.5" viewBox="0 0 24 24"><polyline points="22 7 13.5 15.5 8.5 10.5 2 17"/><polyline points="16 7 22 7 22 13"/></svg></div>
                        <div><div class="fb-val">+24.8%</div><div class="fb-lbl">Monthly Revenue</div></div>
                    </div>
                    <div class="float-badge fb-2">
                        <div class="fb-icon" style="background:#fef9c3;"><svg width="16" height="16" fill="none" stroke="#b45309" stroke-width="2.5" viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg></div>
                        <div><div class="fb-val">Real-time</div><div class="fb-lbl">Live data sync</div></div>
                    </div>
                    <div class="gadget-stage">
                        <div class="gadget-grid">
                            <div class="gadget-stack">
                                <div class="gadget-card accent-blue">
                                    <div class="gadget-topline">
                                        <span class="gadget-chip"><span class="gadget-dot"></span> Live ledger</span>
                                        <span class="gadget-title">Financial Command Center</span>
                                    </div>
                                    <div class="gadget-stat-grid">
                                        <div class="gadget-stat">
                                            <div class="gadget-stat-label">Total Revenue</div>
                                            <div class="gadget-stat-value">₦4.2M</div>
                                            <div class="gadget-stat-meta">↑ 18.4% this month</div>
                                        </div>
                                        <div class="gadget-stat">
                                            <div class="gadget-stat-label">Total Sales</div>
                                            <div class="gadget-stat-value">1,248</div>
                                            <div class="gadget-stat-meta">↑ 12.1% vs last</div>
                                        </div>
                                        <div class="gadget-stat">
                                            <div class="gadget-stat-label">Outstanding</div>
                                            <div class="gadget-stat-value">₦380K</div>
                                            <div class="gadget-stat-meta neg">3 invoices due</div>
                                        </div>
                                    </div>
                                </div>
                                <div class="gadget-card">
                                    <div class="gadget-topline">
                                        <span class="gadget-title">Monthly revenue trend</span>
                                        <span class="gadget-chip">LIVE · {{ date('M Y') }}</span>
                                    </div>
                                    <svg viewBox="0 0 280 90" style="width:100%;">
                                        <defs><linearGradient id="ag" x1="0" y1="0" x2="0" y2="1"><stop offset="0%" stop-color="#002347" stop-opacity=".14"/><stop offset="100%" stop-color="#002347" stop-opacity="0"/></linearGradient></defs>
                                        <line x1="0" y1="20" x2="280" y2="20" stroke="#f0f4f8" stroke-width="1"/><line x1="0" y1="50" x2="280" y2="50" stroke="#f0f4f8" stroke-width="1"/><line x1="0" y1="75" x2="280" y2="75" stroke="#f0f4f8" stroke-width="1"/>
                                        <path d="M0,70 L35,55 L70,45 L105,50 L140,35 L175,40 L210,25 L245,30 L280,18 L280,90 L0,90 Z" fill="url(#ag)"/>
                                        <polyline points="0,70 35,55 70,45 105,50 140,35 175,40 210,25 245,30 280,18" fill="none" stroke="#002347" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"/>
                                        <circle cx="280" cy="18" r="4" fill="#c5a059" stroke="#fff" stroke-width="2"/>
                                        <text x="0" y="88" font-size="7" fill="#8a92a0">Jul</text><text x="68" y="88" font-size="7" fill="#8a92a0">Sep</text><text x="138" y="88" font-size="7" fill="#8a92a0">Nov</text><text x="208" y="88" font-size="7" fill="#8a92a0">Jan</text><text x="258" y="88" font-size="7" fill="#c5a059">Feb</text>
                                    </svg>
                                </div>
                            </div>
                            <div class="gadget-stack">
                                <div class="gadget-card">
                                    <div class="gadget-topline">
                                        <span class="gadget-title">Revenue split</span>
                                        <span class="gadget-chip">Sales mix</span>
                                    </div>
                                    <div class="gadget-donut">
                                        <svg viewBox="0 0 100 100">
                                            <circle cx="50" cy="50" r="36" fill="none" stroke="#f0f4f8" stroke-width="16"/>
                                            <circle cx="50" cy="50" r="36" fill="none" stroke="#002347" stroke-width="16" stroke-dasharray="113 113" stroke-dashoffset="28" transform="rotate(-90 50 50)"/>
                                            <circle cx="50" cy="50" r="36" fill="none" stroke="#c5a059" stroke-width="16" stroke-dasharray="45 181" stroke-dashoffset="-85" transform="rotate(-90 50 50)"/>
                                            <circle cx="50" cy="50" r="26" fill="white"/>
                                            <text x="50" y="47" text-anchor="middle" font-size="11" font-weight="800" fill="#002347">68%</text>
                                            <text x="50" y="56" text-anchor="middle" font-size="7" fill="#8a92a0">Sales</text>
                                        </svg>
                                    </div>
                                </div>
                                <div class="gadget-card">
                                    <div class="gadget-topline">
                                        <span class="gadget-title">Priority invoices</span>
                                        <span class="gadget-chip">Collections</span>
                                    </div>
                                    <div class="gadget-mini-list">
                                        <div class="gadget-mini-row"><span class="gadget-mini-label">Adaobi Nwosu</span><span class="gadget-mini-value">₦85,000</span></div>
                                        <div class="gadget-mini-row"><span class="gadget-mini-label">TechBridge Ltd</span><span class="gadget-mini-value">₦240,000</span></div>
                                        <div class="gadget-mini-row"><span class="gadget-mini-label">Kalu Stores</span><span class="gadget-mini-value">₦62,500</span></div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="gadget-orbit">
                            <div class="label">Clearance rate</div>
                            <div class="value">91%</div>
                            <div class="meta">2.4 days avg payment cycle</div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-lg-5">
                <span class="sb-eyebrow">01 — Sales &amp; Revenue</span>
                <h2 class="sb-h1">Know exactly <span class="accent">where every naira</span> is going</h2>
                <p class="sb-lead">Get a live, bird's-eye view of your business finances. SmartProbook's revenue dashboard gives you instant clarity on sales performance, outstanding invoices, and profit trends — all on one screen.</p>
                <div class="d-flex flex-column gap-3 mt-4">
                    <div class="feat-card"><div class="d-flex align-items-start gap-3"><div class="feat-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="22 7 13.5 15.5 8.5 10.5 2 17"/><polyline points="16 7 22 7 22 13"/></svg></div><div><h6>Live Revenue Tracking</h6><p>See your sales totals update in real time as transactions happen across your business locations.</p></div></div></div>
                    <div class="feat-card"><div class="d-flex align-items-start gap-3"><div class="feat-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 00-2 2v16a2 2 0 002 2h12a2 2 0 002-2V8z"/><polyline points="14 2 14 8 20 8"/></svg></div><div><h6>Instant Invoice Management</h6><p>Generate, send, and track invoices automatically. Get notified the moment a client pays or a payment goes overdue.</p></div></div></div>
                    <div class="feat-card"><div class="d-flex align-items-start gap-3"><div class="feat-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="2" y="3" width="20" height="14" rx="2"/><line x1="8" y1="21" x2="16" y2="21"/><line x1="12" y1="17" x2="12" y2="21"/></svg></div><div><h6>Multi-Currency Support</h6><p>Record and report in NGN, USD, GBP, EUR and more — perfect for businesses with international clients.</p></div></div></div>
                </div>
            </div>
        </div>

        {{-- Feature 2 --}}
        <div class="row align-items-center g-5 pt-4">
            <div class="col-lg-5">
                <span class="sb-eyebrow">02 — Inventory Control</span>
                <h2 class="sb-h1">Never run out of <span class="accent">stock again</span></h2>
                <p class="sb-lead">SmartProbook's inventory engine monitors every product in your store in real time. Set reorder thresholds, track expiry dates, and get alerts before stock runs dry.</p>
                <div class="mt-4">
                    <div class="prog-row"><div class="prog-labels"><span>Stock Accuracy</span><span>98.4%</span></div><div class="prog-track"><div class="prog-fill" style="--w:98.4%;"></div></div></div>
                    <div class="prog-row"><div class="prog-labels"><span>Waste Reduction</span><span>76%</span></div><div class="prog-track"><div class="prog-fill" style="--w:76%;"></div></div></div>
                    <div class="prog-row"><div class="prog-labels"><span>Reorder Automation</span><span>89%</span></div><div class="prog-track"><div class="prog-fill" style="--w:89%;"></div></div></div>
                </div>
                <div class="d-flex flex-column gap-3 mt-4">
                    <div class="feat-card"><div class="d-flex align-items-start gap-3"><div class="feat-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 16V8a2 2 0 00-1-1.73l-7-4a2 2 0 00-2 0l-7 4A2 2 0 003 8v8a2 2 0 001 1.73l7 4a2 2 0 002 0l7-4A2 2 0 0021 16z"/></svg></div><div><h6>Smart Reorder Alerts</h6><p>Automated low-stock notifications so your team restocks before customers notice an empty shelf.</p></div></div></div>
                    <div class="feat-card"><div class="d-flex align-items-start gap-3"><div class="feat-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 15s1-1 4-1 5 2 8 2 4-1 4-1V3s-1 1-4 1-5-2-8-2-4 1-4 1z"/><line x1="4" y1="22" x2="4" y2="15"/></svg></div><div><h6>Expiry Date Tracking</h6><p>Tag perishable items with expiry dates — SmartProbook flags them before they become a liability.</p></div></div></div>
                </div>
            </div>
            <div class="col-lg-7">
                <div class="position-relative" style="padding:24px 0 24px 24px;">
                    <div class="float-badge" style="top:0;right:-10px;animation:floatBob 4s ease-in-out infinite;">
                        <div class="fb-icon" style="background:#fee2e2;"><svg width="16" height="16" fill="none" stroke="#dc2626" stroke-width="2.5" viewBox="0 0 24 24"><path d="M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z"/><line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg></div>
                        <div><div class="fb-val">3 Items</div><div class="fb-lbl">Low stock alert</div></div>
                    </div>
                    <div class="gadget-stage">
                        <div class="gadget-grid">
                            <div class="gadget-stack">
                                <div class="gadget-card accent-mint">
                                    <div class="gadget-topline">
                                        <span class="gadget-chip"><span class="gadget-dot"></span> Inventory pulse</span>
                                        <span class="gadget-title">482 SKUs</span>
                                    </div>
                                    <div class="gadget-stat-grid">
                                        <div class="gadget-stat">
                                            <div class="gadget-stat-label">Total SKUs</div>
                                            <div class="gadget-stat-value">482</div>
                                            <div class="gadget-stat-meta">↑ 12 added today</div>
                                        </div>
                                        <div class="gadget-stat">
                                            <div class="gadget-stat-label">Stock Value</div>
                                            <div class="gadget-stat-value">₦2.1M</div>
                                            <div class="gadget-stat-meta">↑ 5.4% this week</div>
                                        </div>
                                        <div class="gadget-stat">
                                            <div class="gadget-stat-label">Low Stock</div>
                                            <div class="gadget-stat-value" style="color:#ef4444;">3</div>
                                            <div class="gadget-stat-meta neg">Needs restocking</div>
                                        </div>
                                    </div>
                                </div>
                                <div class="gadget-card">
                                    <div class="gadget-topline">
                                        <span class="gadget-title">Top products</span>
                                        <span class="gadget-chip">Stock levels</span>
                                    </div>
                                    @php $products=[['Paracetamol 500mg',87],['Vitamin C Tabs',62],['Amoxicillin 250mg',18],['Ibuprofen 400mg',74],['Zinc Sulphate',9]]; @endphp
                                    <div class="gadget-bars">
                                        @foreach($products as $p)
                                            <div>
                                                <div class="gadget-bar-label"><span>{{ $p[0] }}</span><span>{{ $p[1] }} units</span></div>
                                                <div class="gadget-bar-track"><div class="gadget-bar-fill" style="width:{{ $p[1] }}%;"></div></div>
                                            </div>
                                        @endforeach
                                    </div>
                                </div>
                            </div>
                            <div class="gadget-stack">
                                <div class="gadget-card">
                                    <div class="gadget-topline">
                                        <span class="gadget-title">Restock queue</span>
                                        <span class="gadget-chip">Urgent</span>
                                    </div>
                                    <div class="gadget-mini-list">
                                        <div class="gadget-mini-row"><span class="gadget-mini-label">Paracetamol 500mg</span><span class="gadget-mini-value">87</span></div>
                                        <div class="gadget-mini-row"><span class="gadget-mini-label">Zinc Sulphate</span><span class="gadget-mini-value" style="color:#ef4444;">9</span></div>
                                        <div class="gadget-mini-row"><span class="gadget-mini-label">Vitamin C Tabs</span><span class="gadget-mini-value">62</span></div>
                                    </div>
                                </div>
                                <div class="gadget-card">
                                    <div class="gadget-topline">
                                        <span class="gadget-title">Automation health</span>
                                        <span class="gadget-chip">Realtime</span>
                                    </div>
                                    <div class="gadget-mini-list">
                                        <div class="gadget-mini-row"><span class="gadget-mini-label">Stock Accuracy</span><span class="gadget-mini-value">98.4%</span></div>
                                        <div class="gadget-mini-row"><span class="gadget-mini-label">Waste Reduction</span><span class="gadget-mini-value">76%</span></div>
                                        <div class="gadget-mini-row"><span class="gadget-mini-label">Reorder Automation</span><span class="gadget-mini-value">89%</span></div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="gadget-orbit">
                            <div class="label">Critical items</div>
                            <div class="value">03</div>
                            <div class="meta">Auto-alerted to manager</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

{{-- EXPENSES — DARK --}}
<section class="sb-section sb-section--dark" id="expenses">
    <div class="container">
        <div class="row align-items-center g-5">
            <div class="col-lg-7">
                <div class="position-relative" style="padding:24px 24px 24px 0;">
                    <div class="float-badge" style="top:-10px;left:10px;animation:floatBob 4s ease-in-out infinite;">
                        <div class="fb-icon" style="background:#ede9fe;"><svg width="16" height="16" fill="none" stroke="#7c3aed" stroke-width="2.5" viewBox="0 0 24 24"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg></div>
                        <div><div class="fb-val">Auto</div><div class="fb-lbl">Bank reconciled</div></div>
                    </div>
                    <div class="gadget-stage">
                        <div class="gadget-grid">
                            <div class="gadget-stack">
                                <div class="gadget-card accent-rose">
                                    <div class="gadget-topline">
                                        <span class="gadget-chip"><span class="gadget-dot"></span> Auto generated</span>
                                        <span class="gadget-title">P&amp;L + Reports</span>
                                    </div>
                                    <div class="gadget-stat-grid">
                                        <div class="gadget-stat">
                                            <div class="gadget-stat-label">Total Expenses</div>
                                            <div class="gadget-stat-value">₦1.4M</div>
                                            <div class="gadget-stat-meta neg">↑ 6.2% vs last</div>
                                        </div>
                                        <div class="gadget-stat">
                                            <div class="gadget-stat-label">Net Profit</div>
                                            <div class="gadget-stat-value">₦2.8M</div>
                                            <div class="gadget-stat-meta">↑ 22.4% margin</div>
                                        </div>
                                        <div class="gadget-stat">
                                            <div class="gadget-stat-label">Reports Ready</div>
                                            <div class="gadget-stat-value">6</div>
                                            <div class="gadget-stat-meta">For this month</div>
                                        </div>
                                    </div>
                                </div>
                                <div class="gadget-card accent-blue">
                                    <div class="gadget-topline">
                                        <span class="gadget-title">Revenue vs expenses</span>
                                        <span class="gadget-chip">12 month view</span>
                                    </div>
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
                            </div>
                            <div class="gadget-stack">
                                <div class="gadget-card accent-gold">
                                    <div class="gadget-topline">
                                        <span class="gadget-title">Export queue</span>
                                        <span class="gadget-chip">Ready now</span>
                                    </div>
                                    <div class="gadget-mini-list">
                                        <div class="gadget-mini-row"><span class="gadget-mini-label">Monthly P&amp;L</span><span class="gadget-mini-value">PDF</span></div>
                                        <div class="gadget-mini-row"><span class="gadget-mini-label">VAT Summary</span><span class="gadget-mini-value">XLSX</span></div>
                                        <div class="gadget-mini-row"><span class="gadget-mini-label">Payroll Sheet</span><span class="gadget-mini-value">PDF</span></div>
                                    </div>
                                </div>
                                <div class="gadget-card accent-mint">
                                    <div class="gadget-topline">
                                        <span class="gadget-title">Report cadence</span>
                                        <span class="gadget-chip">Automation</span>
                                    </div>
                                    <div class="gadget-mini-list">
                                        <div class="gadget-mini-row"><span class="gadget-mini-label">Daily close packs</span><span class="gadget-mini-value">07:00</span></div>
                                        <div class="gadget-mini-row"><span class="gadget-mini-label">Weekly board digest</span><span class="gadget-mini-value">Fri</span></div>
                                        <div class="gadget-mini-row"><span class="gadget-mini-label">Tax filing prep</span><span class="gadget-mini-value">Ready</span></div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="gadget-orbit">
                            <div class="label">Delivery speed</div>
                            <div class="value">1 click</div>
                            <div class="meta">Exports ready in seconds</div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-lg-5">
                <span class="sb-eyebrow">03 — Expenses &amp; Reports</span>
                <h2 class="sb-h1 sb-h1-white">Board-ready reports <span class="accent">in one click</span></h2>
                <p class="sb-lead sb-lead-white">Stop spending weekends building spreadsheets. SmartProbook generates polished financial reports automatically — daily, weekly, monthly, or on demand.</p>
                <div class="d-flex flex-column gap-3 mt-4">
                    <div class="feat-card-dark"><div class="d-flex align-items-start gap-3"><div class="feat-icon feat-icon-dark"><svg viewBox="0 0 24 24" fill="none" stroke="#c5a059" stroke-width="2"><rect x="2" y="5" width="20" height="14" rx="2"/><line x1="2" y1="10" x2="22" y2="10"/></svg></div><div><h6>Automatic Expense Categorization</h6><p>SmartProbook learns your spending patterns and auto-tags expenses to the right accounts without manual entry.</p></div></div></div>
                    <div class="feat-card-dark"><div class="d-flex align-items-start gap-3"><div class="feat-icon feat-icon-dark"><svg viewBox="0 0 24 24" fill="none" stroke="#c5a059" stroke-width="2"><path d="M9 11l3 3L22 4"/><path d="M21 12v7a2 2 0 01-2 2H5a2 2 0 01-2-2V5a2 2 0 012-2h11"/></svg></div><div><h6>One-Click Tax Reports</h6><p>Generate VAT, PAYE, and annual tax summaries in seconds — fully formatted for FIRS submission.</p></div></div></div>
                    <div class="feat-card-dark"><div class="d-flex align-items-start gap-3"><div class="feat-icon feat-icon-dark"><svg viewBox="0 0 24 24" fill="none" stroke="#c5a059" stroke-width="2"><polyline points="23 6 13.5 15.5 8.5 10.5 1 18"/><polyline points="17 6 23 6 23 12"/></svg></div><div><h6>Bank Reconciliation</h6><p>Import your bank statements and SmartProbook matches every transaction automatically — zero manual reconciliation.</p></div></div></div>
                </div>
            </div>
        </div>
    </div>
</section>

{{-- STRIP --}}
<section class="strip-section">
    <div class="container">
        <div class="text-center mb-5">
            <span class="sb-eyebrow" style="justify-content:center;display:inline-flex;">Everything included</span>
            <h2 class="sb-h1 text-center">One platform. <span class="accent">Every function.</span></h2>
            <p class="sb-lead text-center mx-auto">SmartProbook brings together every tool your business needs to run — from staff management to customer records, POS to cloud backup.</p>
        </div>
        <div class="row g-4">
            @php $strips=[['icon'=>'👥','bg'=>'#f0f4ff','title'=>'Staff & Payroll','desc'=>'Manage employee records, attendance, and process accurate payroll in minutes. Automatic PAYE deductions calculated for you.'],['icon'=>'🧾','bg'=>'#fef9c3','title'=>'Receipts & POS','desc'=>'Turn any device into a point-of-sale terminal. Print or email branded receipts instantly after every sale.'],['icon'=>'📊','bg'=>'#dcfce7','title'=>'Reports & Analytics','desc'=>'From daily sales summaries to quarterly board reports — generate any report with a single click, no accountant needed.'],['icon'=>'🤝','bg'=>'#ffe4e6','title'=>'Customer CRM','desc'=>'Build detailed customer profiles, track purchase history, and send targeted promotions to your best buyers.'],['icon'=>'🔐','bg'=>'#ede9fe','title'=>'Access Control','desc'=>'Create staff accounts with role-based permissions. Your cashier sees only the POS; your manager sees everything.'],['icon'=>'☁️','bg'=>'#f0fdf4','title'=>'Cloud Backup','desc'=>'Your data is encrypted and backed up automatically every hour. Access your books from any device, anywhere.']]; @endphp
            @foreach($strips as $s)
            <div class="col-lg-4 col-md-6"><div class="strip-card"><div class="strip-icon" style="background:{{ $s['bg'] }};">{{ $s['icon'] }}</div><h5>{{ $s['title'] }}</h5><p>{{ $s['desc'] }}</p></div></div>
            @endforeach
        </div>
    </div>
</section>

{{-- SOLUTIONS --}}
<section class="sb-section sb-section--alt" id="solutions">
    <div class="container">
        <div class="text-center mb-5">
            <span class="sb-eyebrow" style="justify-content:center;display:inline-flex;">Operational Utility</span>
            <h2 class="sb-h1 text-center">Engine <span class="accent">Capabilities</span></h2>
        </div>
        <div class="sol-grid">
            @php $utils=[['icon'=>'fa-brain','title'=>'Neural Ledger Engine','text'=>'Utilizes transformer-based AI to handle multi-currency reconciliations across thousands of subsidiaries. Our engine reduces manual entry errors by 99.8% through autonomous pattern matching.'],['icon'=>'fa-chart-line','title'=>'Predictive Forensics','text'=>'Execute high-fidelity Monte Carlo simulations to forecast capital requirements and mitigate liquidity risks. Transform historical data into actionable 24-month financial roadmaps.'],['icon'=>'fa-fingerprint','title'=>'Sovereign Governance','text'=>'Institutional security protocols featuring Multi-Party Computation (MPC) and ZK-Proofs. Maintain absolute data sovereignty while ensuring total transparency for the executive board.'],['icon'=>'fa-file-signature','title'=>'Autonomous Auditing','text'=>'Generate board-ready audits mapped to IFRS and GAAP standards. Real-time regulatory compliance allows for zero-latency fiscal reporting across global jurisdictions.']]; @endphp
            @foreach($utils as $u)
            <div class="sol-tile"><i class="fas {{ $u['icon'] }} mb-4" style="font-size:2rem;color:var(--navy);"></i><h5 class="fw-bold mb-3" style="font-family:var(--font-display);color:var(--navy);">{{ $u['title'] }}</h5><p class="mb-0" style="font-size:13.5px;color:var(--muted);line-height:1.75;">{{ $u['text'] }}</p></div>
            @endforeach
        </div>
    </div>
</section>

{{-- CAPABILITIES --}}
<section class="sb-section" id="capabilities">
    <div class="container">
        <div class="row align-items-center g-5 mb-5 pb-5">
            <div class="col-lg-6"><div class="cap-img"><img src="https://images.pexels.com/photos/3183150/pexels-photo-3183150.jpeg?auto=compress&cs=tinysrgb&w=800" alt="Analytics" class="img-fluid"></div></div>
            <div class="col-lg-6">
                <span class="sb-eyebrow">01 — Engine Depth</span>
                <h2 class="sb-h1">Strategic <span class="accent">Liquidity</span> Ecosystem</h2>
                <p class="sb-lead">SmartProbook's proprietary Neural Forecasting Core (NFC) transcends legacy bookkeeping systems by analyzing over 600 unique financial variables in real-time. By mapping historical account volatility against current receivables, our engine provides surgical liquidity horizon with 98.4% predictive accuracy.</p>
                <div class="p-4 rounded mt-4" style="background:var(--gold-bg);border-left:4px solid var(--gold);"><p class="mb-0 fst-italic" style="font-size:13.5px;font-weight:700;color:var(--navy);">"We convert fragmented transaction streams into verified, high-definition foresight for the modern board."</p></div>
            </div>
        </div>
        <div class="row align-items-center g-5 pt-5">
            <div class="col-lg-6 order-lg-2"><div class="cap-img"><img src="https://images.pexels.com/photos/669619/pexels-photo-669619.jpeg?auto=compress&cs=tinysrgb&w=800" alt="Governance" class="img-fluid"></div></div>
            <div class="col-lg-6 order-lg-1">
                <span class="sb-eyebrow">02 — Governance</span>
                <h2 class="sb-h1">Institutional <span class="accent">Sovereignty</span> Protocols</h2>
                <p class="sb-lead">Designed for organizations with complex hierarchical needs, SmartProbook implements a "Cellular Governance" model that guarantees total transparency without compromising individual business unit security. Each subsidiary operates within a fortified node, feeding into a master dashboard while maintaining SOC2 Type II compliance.</p>
            </div>
        </div>
    </div>
</section>

{{-- PROJECTS --}}
<section class="sb-section sb-section--alt" id="team">
    <div class="container">
        <div class="text-center mb-5">
            <span class="sb-eyebrow" style="justify-content:center;display:inline-flex;">Portfolio</span>
            <h2 class="sb-h1 text-center">Our <span class="accent">Other Projects</span></h2>
        </div>
        <div class="row g-4">
            @php $team=[['name'=>'Lahome Properties','role'=>'Real Estate Platform','img'=>'https://images.pexels.com/photos/323780/pexels-photo-323780.jpeg?auto=compress&cs=tinysrgb&w=1200','bio'=>'A global real estate listing ecosystem for owners, surveyors, legal advisers, agents, and every key stakeholder in the property market.','link'=>route('landing.projects.lahome')],['name'=>'Master JAMB','role'=>'CBT Examination Platform','img'=>'https://images.unsplash.com/photo-1588072432836-e10032774350?q=80&w=1200&auto=format&fit=crop','bio'=>'An online CBT platform for schools and institutions, built for exam readiness, timed assessments, and performance tracking.','link'=>route('landing.projects.master-jamb')],['name'=>'PayPlus','role'=>'Payment Gateway','img'=>'https://images.pexels.com/photos/4968634/pexels-photo-4968634.jpeg?auto=compress&cs=tinysrgb&w=1200','bio'=>'A global payment gateway designed for secure processing of everyday transactions across web, mobile, and enterprise channels.','link'=>route('landing.projects.payplus')]]; @endphp
            @foreach($team as $m)
            <div class="col-lg-4 col-md-6">
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

{{-- TESTIMONIALS --}}
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
                <p style="font-size:0.88rem;color:rgba(255,255,255,0.88);font-style:italic;line-height:1.7;margin-bottom:22px;">"SmartProbook's neural-ledgers have fundamentally changed how we manage our global hubs. Unmatched precision."</p>
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

{{-- LICENSING --}}
<section class="sb-section" id="licensing">
    <div class="container">
        <div class="text-center mb-5">
            <span class="sb-eyebrow" style="justify-content:center;display:inline-flex;">Service Access</span>
            <h2 class="sb-h1 text-center">Enterprise <span class="accent">Licensing</span></h2>
        </div>
        <div class="row g-4">
            @php $plans=['Basic'=>['ngn'=>3000,'feat'=>false,'benefits'=>['Centralized Ledgers','5 Core User Access','Daily Cloud Backups','Standard Email Support','Unified Reporting']],'Pro'=>['ngn'=>7000,'feat'=>true,'benefits'=>['Neural Engine Core','25 Premium User Access','Dedicated Priority Node','Real-time Analytics','Predictive Forecasting']],'Enterprise'=>['ngn'=>15000,'feat'=>false,'benefits'=>['Full Neural Automation','Unlimited Access Nodes','Advanced API Gateway','Custom Fiscal Reports','IFRS Compliance Mapping']],'Institution'=>['ngn'=>null,'feat'=>false,'benefits'=>['Private Hybrid Core','SLA Performance Guarantee','On-site Technical Support','Bespoke Integrations','Governance Training']]]; @endphp
            @foreach($plans as $name => $p)
            <div class="col-lg-3 col-md-6">
                <div class="plan-card {{ $p['feat'] ? 'plan-featured' : '' }}">
                    @if($p['feat'])<div style="text-align:center;margin-bottom:12px;"><span style="background:var(--gold);color:var(--navy);font-size:0.62rem;font-weight:900;letter-spacing:2px;text-transform:uppercase;padding:4px 14px;border-radius:20px;">MOST POPULAR</span></div>@endif
                    <div class="plan-name">{{ $name }}</div>
                    <div class="plan-price">@if($p['ngn'])<span class="geo-price" data-ngn="{{ $p['ngn'] }}">₦{{ number_format($p['ngn']) }}</span>@else<span>Bespoke</span>@endif</div>
                    <div class="flex-grow-1">@foreach($p['benefits'] as $b)<div class="plan-feature"><i class="fas fa-check-circle"></i><span>{{ $b }}</span></div>@endforeach</div>
                    <div class="mt-4"><a href="{{ url('/membership-plans') }}" class="{{ $p['feat'] ? 'btn-red' : 'btn-outline-navy' }} w-100 justify-content-center">ACQUIRE SYSTEM</a></div>
                </div>
            </div>
            @endforeach
        </div>
    </div>
</section>

{{-- Floating Support Widget --}}
<div class="spb-support" id="spbSupportWidget">
    <div class="spb-support-floating" id="spbSupportFloating">
        <div class="spb-float-msg" id="spbMsgHello">👋 Hello!</div>
        <div class="spb-float-msg" id="spbMsgPrompt">Want to talk to our support team?</div>
    </div>

    <div class="spb-support-panel" id="spbSupportPanel" aria-hidden="true">
        <div class="spb-panel-head">
            <span>Speak with SmartProbook Support</span>
            <button type="button" class="spb-close-x" id="spbSupportClose">×</button>
        </div>
        <div class="spb-panel-body">
            <p class="spb-panel-note mb-2">Send us a quick WhatsApp message now.</p>
            <a class="spb-support-btn text-decoration-none text-center d-block" href="tel:08064646306">
                Call: 08064646306
            </a>
            <a class="spb-support-btn text-decoration-none text-center d-block" href="mailto:donvictorlive@gmail.com">
                Email support: donvictorlive@gmail.com
            </a>
            <div class="spb-support-mini">Quick WhatsApp form.</div>

            <form class="spb-panel-form" id="spbWhatsappForm">
                <input type="text" id="spbSupportName" placeholder="Your name" required>
                <input type="text" id="spbSupportPhone" placeholder="Your phone" value="08064646306" required>
                <textarea id="spbSupportMessage" required>I need help with SmartProbook.</textarea>
                <button type="submit" class="spb-support-btn primary mb-0">Send on WhatsApp</button>
            </form>
        </div>
    </div>

    <button type="button" class="spb-support-bubble" id="spbSupportToggle" aria-label="Open support">
        <i class="fas fa-comments"></i>
    </button>
</div>

{{-- FOOTER --}}
<footer class="sb-footer" id="contact">
    <div class="container" style="position:relative;z-index:1;">
        <div class="row g-5 mb-5 pb-5" style="border-bottom:1px solid var(--border);">
            <div class="col-lg-5">
                <h2 style="font-family:var(--font-display);font-weight:800;color:var(--navy);margin-bottom:16px;">Uplink <span style="color:var(--gold);">Support</span></h2>
                <p style="color:var(--muted);line-height:1.85;margin-bottom:24px;">Technical architects are available 24/7 for organizational assessment and rapid deployment.</p>
                <div class="mb-4">
                    <p class="mb-2" style="font-size:13.5px;font-weight:700;color:var(--navy);"><i class="fas fa-map-marker-alt me-3" style="color:var(--gold);"></i>12 Independence Layout, Enugu, Nigeria</p>
                    <p class="mb-2" style="font-size:13.5px;font-weight:700;color:var(--navy);"><i class="fas fa-phone-alt me-3" style="color:var(--gold);"></i>+234 646 463 06</p>
                    <p class="mb-0" style="font-size:13.5px;font-weight:700;color:var(--navy);"><i class="fas fa-envelope me-3" style="color:var(--gold);"></i><a href="mailto:donvictorlive@gmail.com" style="color:var(--navy);text-decoration:none;">donvictorlive@gmail.com</a></p>
                </div>
                @if(session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif
                @if(session('error'))<div class="alert alert-danger">{{ session('error') }}</div>@endif
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
            <div class="col-lg-7">
                <div class="map-wrap" style="height:100%;min-height:480px;">
                    <iframe src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d15858.987654321!2d7.508333!3d6.458333!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x1044a3d6f1a8e1e1%3A0x1234567890abcdef!2sIndependence%20Layout%2C%20Enugu!5e0!3m2!1sen!2sng!4v1234567890123" width="100%" height="100%" style="border:0;min-height:480px;" allowfullscreen="" loading="lazy"></iframe>
                </div>
            </div>
        </div>
        <div class="row g-4">
            <div class="col-lg-4">
                <h3 style="font-family:var(--font-display);font-weight:900;color:var(--navy);letter-spacing:0.5px;margin-bottom:12px;">SmartProbook</h3>
                <p style="font-size:13px;color:var(--muted);max-width:300px;line-height:1.8;">Global Institutional Accounting Intelligence. Engineered for modern wealth governance.</p>
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
            <p style="font-size:13px;color:var(--muted);margin:0;">© 2026 SmartProbook Intelligence Enterprise. Licensed for Global Financial Governance.</p>
        </div>
    </div>
</footer>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const syncNav = () => {
        const nav = document.getElementById('mainNav');
        if (!nav) return;
        const h = Math.ceil(nav.getBoundingClientRect().height || 84);
        document.documentElement.style.setProperty('--nav-h', h + 'px');
    };
    syncNav();
    ['load','resize'].forEach(e => window.addEventListener(e, syncNav, {passive:true}));
    setInterval(syncNav, 1200);

    const nav = document.getElementById('mainNav');
    window.addEventListener('scroll', () => nav && nav.classList.toggle('scrolled', scrollY > 50));

    const navToggler = document.querySelector('#mainNav .navbar-toggler');
    const navCollapse = document.getElementById('mujiNav');
    const bsCollapse = navCollapse ? bootstrap.Collapse.getOrCreateInstance(navCollapse, { toggle: false }) : null;

    if (navToggler && navCollapse && bsCollapse) {
        navToggler.addEventListener('click', function (e) {
            e.stopPropagation();
            navCollapse.classList.contains('show') ? bsCollapse.hide() : bsCollapse.show();
        });

        navCollapse.addEventListener('shown.bs.collapse', function () {
            navToggler.classList.add('active');
            navToggler.setAttribute('aria-expanded', 'true');
            document.body.style.overflow = 'hidden';
        });

        navCollapse.addEventListener('hidden.bs.collapse', function () {
            navToggler.classList.remove('active');
            navToggler.setAttribute('aria-expanded', 'false');
            document.body.style.overflow = '';
        });

        document.addEventListener('click', function (e) {
            if (!navCollapse.classList.contains('show')) return;
            if (e.target.closest('#mujiNav') || e.target.closest('#mainNav .navbar-toggler')) return;
            bsCollapse.hide();
        });

        document.addEventListener('keydown', function (e) {
            if (e.key === 'Escape' && navCollapse.classList.contains('show')) {
                bsCollapse.hide();
            }
        });
    }

    document.querySelectorAll('a[href^="#"]').forEach(a => {
        a.addEventListener('click', e => {
            const t = document.querySelector(a.getAttribute('href'));
            if (!t) return;
            e.preventDefault();
            window.scrollTo({ top: t.offsetTop - 100, behavior: 'smooth' });
            if (navCollapse?.classList.contains('show')) bsCollapse?.hide();
        });
    });

    const msgs = document.querySelectorAll('.announce-msg');
    let cur = 0;
    setInterval(() => {
        msgs[cur].classList.remove('active');
        msgs[cur].classList.add('exit');
        setTimeout(() => msgs[cur].classList.remove('exit'), 500);
        cur = (cur + 1) % msgs.length;
        msgs[cur].classList.add('active');
    }, 3500);

    document.querySelectorAll('.prog-fill').forEach(p => {
        new IntersectionObserver(entries => {
            entries.forEach(e => { if (e.isIntersecting) p.classList.add('go'); });
        }, { threshold: 0.3 }).observe(p);
    });

    const countryMap = {NG:{c:'NGN',l:'en-NG'},US:{c:'USD',l:'en-US'},CN:{c:'CNY',l:'zh-CN'},GB:{c:'GBP',l:'en-GB'},EU:{c:'EUR',l:'en-IE'},CA:{c:'CAD',l:'en-CA'},IN:{c:'INR',l:'en-IN'},AE:{c:'AED',l:'en-AE'},ZA:{c:'ZAR',l:'en-ZA'},KE:{c:'KES',l:'en-KE'},GH:{c:'GHS',l:'en-GH'}};
    const fallback = {NGN:1,USD:0.00067,CNY:0.0048,GBP:0.00053,EUR:0.00062,CAD:0.00091,INR:0.056,AED:0.00246,ZAR:0.0125,KES:0.086,GHS:0.0105};
    const euCodes =['FR','DE','ES','IT','PT','NL','BE','AT','IE','FI','SE','DK','PL','CZ','GR','RO','HU'];
    const norm = c => { const s=String(c||'').toUpperCase(); if(euCodes.includes(s)) return 'EU'; return countryMap[s]?s:'NG'; };
    const fetchRates = async () => {
        const k='sb_rates_v1',kt=k+'_t',cached=sessionStorage.getItem(k),ts=+sessionStorage.getItem(kt)||0;
        if(cached && Date.now()-ts<21600000) return JSON.parse(cached);
        try{const r=await fetch('https://open.er-api.com/v6/latest/NGN',{cache:'no-store'});const d=await r.json();if(d?.rates){sessionStorage.setItem(k,JSON.stringify(d.rates));sessionStorage.setItem(kt,String(Date.now()));return d.rates;}}catch(e){}
        return fallback;
    };
    const renderPrices = async code => {
        const geo=countryMap[norm(code)]||countryMap.NG;
        const rates=await fetchRates();
        const rate=rates[geo.c]||fallback[geo.c]||1;
        document.querySelectorAll('.geo-price').forEach(n=>{
            const v=+(n.dataset.ngn||0)*rate;
            try{n.textContent=new Intl.NumberFormat(geo.l,{style:'currency',currency:geo.c,maximumFractionDigits:0}).format(v);}catch(e){n.textContent=`${geo.c} ${Math.round(v).toLocaleString()}`;}
        });
    };
    const applyCountry = code => { const n=norm(code); localStorage.setItem('sb_country',n); renderPrices(n); };
    const saved=localStorage.getItem('sb_country');
    const cookie=(document.cookie.match(/(?:^|;\s*)sb_country=([^;]+)/)||[])[1]||'';
    applyCountry(saved||cookie||@json($geoCountry ?? 'NG'));

    const FX_PAIRS=[{label:'🇺🇸 USD/NGN',base:'USD',flag:'🇺🇸'},{label:'🇬🇧 GBP/NGN',base:'GBP',flag:'🇬🇧'},{label:'🇪🇺 EUR/NGN',base:'EUR',flag:'🇪🇺'},{label:'🇨🇳 CNY/NGN',base:'CNY',flag:'🇨🇳'},{label:'🇨🇦 CAD/NGN',base:'CAD',flag:'🇨🇦'},{label:'🇮🇳 INR/NGN',base:'INR',flag:'🇮🇳'},{label:'🇦🇪 AED/NGN',base:'AED',flag:'🇦🇪'},{label:'🇿🇦 ZAR/NGN',base:'ZAR',flag:'🇿🇦'},{label:'🇰🇪 KES/NGN',base:'KES',flag:'🇰🇪'},{label:'🇬🇭 GHS/NGN',base:'GHS',flag:'🇬🇭'}];
    const FX_FB={USD:1620,GBP:2050,EUR:1740,CNY:224,CAD:1190,INR:19.4,AED:441,ZAR:88,KES:12.5,GHS:106};
    let fxR={...FX_FB},prevR={...FX_FB};
    const fetchFX = async () => {
        try{const res=await fetch('https://open.er-api.com/v6/latest/NGN',{cache:'no-store'});const d=await res.json();if(d?.rates) FX_PAIRS.forEach(p=>{const r=d.rates[p.base];if(r&&r>0){prevR[p.base]=fxR[p.base];fxR[p.base]=+(1/r).toFixed(2);}});}catch(e){}
        buildTicker();
    };
    const buildTicker = () => {
        const track=document.getElementById('hero-ticker-track');
        if(!track) return;
        const items=FX_PAIRS.map(p=>{
            const rate=fxR[p.base]||FX_FB[p.base],prev=prevR[p.base]||rate,up=rate>=prev;
            return `<span style="display:inline-flex;align-items:center;gap:8px;padding:0 22px;font-size:12px;font-weight:700;color:#fff;border-right:1px solid rgba(197,160,89,0.2);">
                <span>${p.flag}</span>
                <span style="color:rgba(255,255,255,0.5);font-size:11px;">${p.label.split(' ')[1]}</span>
                <span>₦${rate.toLocaleString('en-NG',{maximumFractionDigits:1})}</span>
                <span style="color:${up?'#22c55e':'#ef4444'};font-size:11px;">${up?'▲':'▼'}</span>
            </span>`;
        }).join('');
        track.innerHTML=items+items;
    };
    fetchFX();
    setInterval(fetchFX, 60000);

    // Floating support widget behavior
    const supportRoot = document.getElementById('spbSupportWidget');
    const supportToggle = document.getElementById('spbSupportToggle');
    const supportClose = document.getElementById('spbSupportClose');
    const supportMsg1 = document.getElementById('spbMsgHello');
    const supportMsg2 = document.getElementById('spbMsgPrompt');
    const supportPhone = document.getElementById('spbSupportPhone');
    const supportMessage = document.getElementById('spbSupportMessage');
    const supportName = document.getElementById('spbSupportName');
    const whatsappForm = document.getElementById('spbWhatsappForm');
    const whatsappNumber = '2348064646306';
    if (supportRoot && supportToggle) {
        setTimeout(() => supportMsg1?.classList.add('show'), 900);
        setTimeout(() => supportMsg2?.classList.add('show'), 2100);
        setInterval(() => {
            supportMsg1?.classList.toggle('show');
            setTimeout(() => supportMsg2?.classList.toggle('show'), 450);
        }, 9000);

        const closeSupport = () => {
            supportRoot.classList.remove('open');
            supportToggle.setAttribute('aria-expanded', 'false');
        };
        const openSupport = () => {
            supportRoot.classList.add('open');
            supportToggle.setAttribute('aria-expanded', 'true');
        };

        supportToggle.addEventListener('click', function () {
            supportRoot.classList.contains('open') ? closeSupport() : openSupport();
        });
        supportClose?.addEventListener('click', closeSupport);
        document.addEventListener('keydown', function (e) {
            if (e.key === 'Escape' && supportRoot.classList.contains('open')) closeSupport();
        });
        document.addEventListener('click', function (e) {
            if (!supportRoot.classList.contains('open')) return;
            if (e.target.closest('#spbSupportWidget')) return;
            closeSupport();
        });

        supportPhone?.addEventListener('input', function () {
            if (!supportMessage) return;
            if (supportMessage.value.trim() === '' || supportMessage.value.startsWith('Requesting support callback.')) {
                supportMessage.value = 'I need help with SmartProbook. My phone: ' + (supportPhone.value || '08064646306');
            }
        });

        whatsappForm?.addEventListener('submit', function (e) {
            e.preventDefault();
            const name = (supportName?.value || '').trim();
            const phone = (supportPhone?.value || '').trim();
            const message = (supportMessage?.value || '').trim();
            const text =
`Hello SmartProbook Support,
Name: ${name || 'N/A'}
Phone: ${phone || 'N/A'}
Message: ${message || 'I need assistance.'}`;
            const url = `https://wa.me/${whatsappNumber}?text=${encodeURIComponent(text)}`;
            window.open(url, '_blank', 'noopener');
        });
    }
});
</script>
@endsection
