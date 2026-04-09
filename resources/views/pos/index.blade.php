@extends('layout.mainlayout')

@section('content')
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />

<style>
@import url('https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap');

:root {
    /* Premium Color System */
    --primary-600: #2563eb;
    --primary-700: #1d4ed8;
    --success-500: #22c55e;
    --success-600: #16a34a;
    --danger-500: #ef4444;
    --warning-500: #f59e0b;
    --indigo-600: #4f46e5;
    
    /* Neutral Palette */
    --gray-50: #f9fafb;
    --gray-100: #f3f4f6;
    --gray-200: #e5e7eb;
    --gray-300: #d1d5db;
    --gray-400: #9ca3af;
    --gray-500: #6b7280;
    --gray-600: #4b5563;
    --gray-700: #374151;
    --gray-800: #1f2937;
    --gray-900: #111827;
    
    /* Design Tokens */
    --text-primary: #111827;
    --text-secondary: #6b7280;
    --text-tertiary: #9ca3af;
    --border: #e5e7eb;
    --radius-sm: 8px;
    --radius-md: 12px;
    --radius-lg: 16px;
    --shadow-sm: 0 1px 2px 0 rgba(0, 0, 0, 0.05);
    --shadow-md: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
    --shadow-lg: 0 10px 15px -3px rgba(0, 0, 0, 0.1);
    --transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
}

* {
    font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
    -webkit-font-smoothing: antialiased;
}

/* Page Layout */
.pos-full-page-wrapper { 
    margin-left: var(--sb-sidebar-w, 270px); 
    padding: 20px; 
    background:
        radial-gradient(1200px 320px at 10% 0%, rgba(96, 165, 250, 0.10) 0%, rgba(96, 165, 250, 0) 55%),
        radial-gradient(900px 260px at 92% 6%, rgba(248, 113, 113, 0.08) 0%, rgba(248, 113, 113, 0) 58%),
        linear-gradient(to bottom, #f7fbff 0%, #fff8fa 55%, #ffffff 100%);
    min-height: 100vh; 
    transition: margin-left 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    margin-top: 37px;
}

body.mini-sidebar .pos-full-page-wrapper { 
    margin-left: var(--sb-sidebar-collapsed, 80px); 
}

@media(max-width: 991.98px) { 
    .pos-full-page-wrapper { 
        margin-left: 0 !important; 
        padding: 16px; 
        margin-top: 23px; 
    } 
}

.header-util-bar {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 10px;
    padding: 10px 14px;
    border: 1px solid rgba(59, 130, 246, 0.30);
    border-radius: var(--radius-md);
    background:
        linear-gradient(90deg, rgba(219, 234, 254, 0.96) 0%, rgba(206, 228, 255, 0.94) 52%, rgba(237, 245, 255, 0.98) 100%);
    margin-bottom: 10px;
    box-shadow:
        inset 0 1px 0 rgba(255,255,255,0.92),
        0 0 0 1px rgba(96, 165, 250, 0.08);
}

.header-stage {
    position: relative;
    padding: 10px;
    border-radius: 18px;
    background:
        radial-gradient(1200px 220px at 10% 0%, rgba(255, 255, 255, 0.18) 0%, rgba(255, 255, 255, 0) 60%),
        radial-gradient(900px 220px at 100% 0%, rgba(255, 255, 255, 0.10) 0%, rgba(255, 255, 255, 0) 62%),
        linear-gradient(135deg, rgba(59, 130, 246, 0.18) 0%, rgba(96, 165, 250, 0.16) 52%, rgba(129, 140, 248, 0.12) 100%);
    border: 1px solid rgba(59, 130, 246, 0.26);
    margin-bottom: 14px;
    box-shadow: 0 14px 30px rgba(59, 130, 246, 0.12);
}

.header-stage::after {
    content: '';
    position: absolute;
    inset: 0;
    border-radius: 18px;
    pointer-events: none;
    box-shadow: inset 0 0 0 1px rgba(96, 165, 250, 0.62);
}

.header-stage.plan-basic {
    background:
        radial-gradient(1200px 220px at 10% 0%, rgba(255, 255, 255, 0.18) 0%, rgba(255, 255, 255, 0) 60%),
        radial-gradient(900px 220px at 100% 0%, rgba(255, 255, 255, 0.10) 0%, rgba(255, 255, 255, 0) 62%),
        linear-gradient(135deg, rgba(37, 99, 235, 0.18) 0%, rgba(79, 70, 229, 0.14) 100%);
    border-color: rgba(37, 99, 235, 0.22);
}

.header-stage.plan-pro {
    background:
        radial-gradient(1200px 220px at 10% 0%, rgba(5, 150, 105, 0.11) 0%, rgba(5, 150, 105, 0) 60%),
        radial-gradient(900px 220px at 100% 0%, rgba(20, 184, 166, 0.12) 0%, rgba(20, 184, 166, 0) 62%),
        linear-gradient(180deg, #f3fffb 0%, #ecfdf5 100%);
    border-color: #a7f3d0;
}

.header-stage.plan-enterprise {
    background:
        radial-gradient(1200px 220px at 10% 0%, rgba(217, 119, 6, 0.12) 0%, rgba(217, 119, 6, 0) 60%),
        radial-gradient(900px 220px at 100% 0%, rgba(245, 158, 11, 0.12) 0%, rgba(245, 158, 11, 0) 62%),
        linear-gradient(180deg, #fffbeb 0%, #fff7ed 100%);
    border-color: #fde68a;
}

.header-stage.plan-super {
    background:
        radial-gradient(1200px 220px at 10% 0%, rgba(15, 23, 42, 0.14) 0%, rgba(15, 23, 42, 0) 62%),
        radial-gradient(900px 220px at 100% 0%, rgba(51, 65, 85, 0.14) 0%, rgba(51, 65, 85, 0) 62%),
        linear-gradient(180deg, #f8fafc 0%, #eef2ff 100%);
    border-color: #cbd5e1;
}

.util-pills {
    display: flex;
    gap: 8px;
    flex-wrap: wrap;
}

.util-pill {
    font-size: 0.7rem;
    font-weight: 700;
    color: #ffffff;
    background: linear-gradient(135deg, #4f7fff 0%, #4a67f4 100%);
    border: 1px solid rgba(255, 255, 255, 0.24);
    border-radius: 999px;
    padding: 4px 10px;
    white-space: nowrap;
    box-shadow: 0 8px 18px rgba(59, 130, 246, 0.12);
}

.header-util-note {
    font-size: 0.84rem;
    font-weight: 600;
    color: #111827;
    letter-spacing: -0.01em;
    text-align: right;
}

.header-util-note .accent {
    color: #dc2626;
    font-weight: 700;
}

/* Premium Header */
.pos-header-bar {
    background: #ffffff;
    min-height: 72px; 
    padding: 10px 28px;
    border-radius: var(--radius-lg);
    border: 1px solid rgba(96, 165, 250, 0.24);
    box-shadow: 0 8px 20px rgba(59, 130, 246, 0.08);
    margin-bottom: 0;
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 20px;
    position: relative;
}

.pos-header-bar::after {
    content: '';
    position: absolute;
    bottom: 0;
    left: 0;
    right: 0;
    height: 2px;
    background: linear-gradient(90deg, var(--primary-600) 0%, var(--indigo-600) 100%);
}

.pos-header-title { 
    color: var(--text-primary);
    font-weight: 700;
    font-size: 1rem;
    margin: 0;
    letter-spacing: -0.02em;
    white-space: nowrap;
}

.pos-header-title .gradient-text {
    background: linear-gradient(135deg, var(--primary-600) 0%, var(--indigo-600) 100%);
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    background-clip: text;
}

/* Clock Badge */
.clock-badge {
    background: linear-gradient(135deg, #f4d37a 0%, #d4af37 100%);
    border: 1px solid rgba(180, 134, 12, 0.18);
    color: #1f2937;
    padding: 6px 12px;
    border-radius: var(--radius-md);
    font-weight: 600;
    font-size: 0.75rem;
    font-variant-numeric: tabular-nums;
    letter-spacing: 0.3px;
    white-space: nowrap;
    box-shadow: 0 8px 20px rgba(212, 175, 55, 0.20);
}

/* Search Bar - FIXED ICON POSITIONING */
.search-wrapper { 
    position: relative; 
    flex: 1 1 auto;
    min-width: 360px;
    max-width: 860px;
}

.search-icon-wrapper {
    position: absolute;
    left: 14px;
    top: 50%;
    transform: translateY(-50%);
    width: 18px;
    display: flex;
    align-items: center;
    justify-content: center;
    pointer-events: none;
    z-index: 3;
}

.search-icon {
    color: var(--text-tertiary);
    font-size: 0.875rem;
}

.search-input { 
    height: 46px;
    border: 1.5px solid rgba(96, 165, 250, 0.20);
    border-radius: var(--radius-md);
    padding: 0 78px 0 50px;
    width: 100%;
    background: var(--gray-50);
    color: var(--text-primary);
    font-weight: 500;
    font-size: 0.8125rem;
    transition: var(--transition);
}

/* Prevent generic .form-control padding from collapsing search icon spacing */
.search-wrapper .search-input {
    padding: 0 78px 0 50px !important;
    height: 46px !important;
}

.search-input:focus { 
    border-color: var(--primary-600);
    outline: none;
    box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.08);
    background: #ffffff;
}

.search-input::placeholder {
    color: var(--text-tertiary);
}

.search-kbd {
    position: absolute;
    right: 12px;
    top: 50%;
    transform: translateY(-50%);
    font-size: 0.65rem;
    font-weight: 700;
    color: var(--text-tertiary);
    border: 1px solid var(--border);
    border-radius: 6px;
    background: #fff;
    padding: 2px 7px;
    line-height: 1.2;
}

/* User Profile */
.user-info {
    text-align: right;
    margin-right: 10px;
    min-width: 0;
    max-width: 180px;
}

.user-label {
    font-size: 0.625rem;
    color: #64748b;
    font-weight: 800;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    line-height: 1;
    margin-bottom: 3px;
}

.user-name {
    color: #0f172a;
    font-size: 0.9rem;
    font-weight: 800;
    line-height: 1;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

.user-avatar {
    width: 40px;
    height: 40px;
    border-radius: 50%;
    background: linear-gradient(135deg, var(--primary-600) 0%, var(--indigo-600) 100%);
    display: flex;
    align-items: center;
    justify-content: center;
    box-shadow: 0 4px 10px rgba(37, 99, 235, 0.25);
    transition: var(--transition);
    flex-shrink: 0;
}

.user-avatar:hover {
    transform: scale(1.05);
    box-shadow: 0 6px 14px rgba(37, 99, 235, 0.35);
}

/* Card Panels */
.sticky-panel { 
    position: sticky; 
    top: 84px; 
}

.pos-card { 
    border: 1px solid var(--border);
    border-radius: var(--radius-lg);
    background: #ffffff;
    box-shadow: var(--shadow-lg);
}

/* Scanner Section */
.scanner-section {
    background: linear-gradient(135deg, #eff6ff 0%, #dbeafe 100%);
    border: 2px solid var(--primary-600);
    border-radius: var(--radius-lg);
    padding: 14px;
    margin-bottom: 18px;
}

.scanner-label {
    color: var(--primary-600);
    font-weight: 700;
    font-size: 0.6875rem;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    margin-bottom: 6px;
    display: block;
}

.scanner-input {
    border: none;
    background: transparent;
    font-weight: 600;
    font-size: 0.9375rem;
    color: var(--text-primary);
    padding: 0;
}

.scanner-input:focus {
    outline: none;
    box-shadow: none;
}

/* Image Frame */
.image-frame {
    height: 140px;
    background: var(--gray-50);
    border: 2px dashed var(--border);
    border-radius: var(--radius-md);
    transition: var(--transition);
    display: flex;
    align-items: center;
    justify-content: center;
}

.image-frame:hover {
    border-color: var(--primary-600);
    background: #ffffff;
}

/* Product Browser */
.product-toolbar {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 10px;
    margin-bottom: 10px;
}

.toolbar-title {
    font-size: 0.75rem;
    font-weight: 700;
    color: var(--text-primary);
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.category-pills {
    display: flex;
    gap: 8px;
    flex-wrap: wrap;
    margin-bottom: 0;
}

.category-pills-wrap {
    display: flex;
    align-items: flex-start;
    justify-content: space-between;
    gap: 10px;
    margin-bottom: 12px;
}

.category-pills.collapsed .category-pill:nth-child(n + 8) {
    display: none;
}

.category-toggle-btn {
    border: 1px solid var(--border);
    background: #fff;
    color: var(--text-secondary);
    border-radius: 999px;
    padding: 4px 12px;
    font-size: 0.7rem;
    font-weight: 700;
    white-space: nowrap;
    transition: var(--transition);
}

.category-toggle-btn:hover {
    border-color: var(--primary-600);
    color: var(--primary-600);
    background: #eff6ff;
}

.category-pill {
    border: 1px solid var(--border);
    background: #fff;
    color: var(--text-secondary);
    border-radius: 999px;
    padding: 4px 12px;
    font-size: 0.72rem;
    font-weight: 700;
    transition: var(--transition);
}

.category-pill:hover,
.category-pill.active {
    border-color: var(--primary-600);
    color: var(--primary-600);
    background: #eff6ff;
}

.product-grid {
    display: grid;
    grid-template-columns: repeat(8, minmax(0, 1fr));
    grid-auto-rows: minmax(92px, auto);
    gap: 10px;
    max-height: clamp(220px, calc(100vh - 430px), 360px);
    overflow-y: auto;
    padding-right: 4px;
}

.product-card {
    border: 1px solid var(--border);
    border-radius: var(--radius-md);
    padding: 6px;
    background: #fff;
    cursor: pointer;
    transition: var(--transition);
    min-height: 92px;
    display: flex;
    align-items: center;
    justify-content: center;
}

.product-card:hover {
    transform: translateY(-2px);
    box-shadow: var(--shadow-md);
    border-color: #bfdbfe;
}

.product-card.active {
    border-color: var(--primary-600);
    box-shadow: 0 0 0 2px rgba(37, 99, 235, 0.1);
    background: #f8fbff;
}

.product-card.last-picked {
    border-color: var(--success-500);
    box-shadow: 0 0 0 2px rgba(34, 197, 94, 0.12);
}

.product-card-img {
    height: 78px;
    width: 100%;
    border-radius: 10px;
    background: var(--gray-100);
    display: flex;
    align-items: center;
    justify-content: center;
    overflow: hidden;
}

.product-card-img img {
    width: 100%;
    height: 100%;
    object-fit: contain;
}

.hidden-product-select {
    display: none;
}

@media (max-width: 1199px) {
    .product-grid {
        grid-template-columns: repeat(6, minmax(0, 1fr));
        grid-auto-rows: minmax(86px, auto);
        max-height: 300px;
    }
}

.controls-card {
    border: 1px solid var(--border);
    border-radius: var(--radius-md);
    background: linear-gradient(180deg, #fcfdff 0%, #ffffff 100%);
    padding: 12px;
}

.quick-fill-panel {
    border: 1px dashed #bfdbfe;
    background: linear-gradient(135deg, #eff6ff 0%, #ffffff 100%);
    border-radius: var(--radius-md);
    padding: 10px;
}

.quick-fill-title {
    font-size: 0.68rem;
    font-weight: 800;
    color: var(--primary-700);
    text-transform: uppercase;
    letter-spacing: 0.5px;
    margin-bottom: 8px;
}

.quick-fill-row {
    display: flex;
    justify-content: space-between;
    align-items: center;
    font-size: 0.75rem;
    color: var(--text-secondary);
    margin-bottom: 4px;
}

.quick-fill-row strong {
    color: var(--text-primary);
    font-weight: 700;
}

.stock-chip {
    font-size: 0.67rem;
    font-weight: 800;
    border-radius: 999px;
    padding: 3px 8px;
    border: 1px solid transparent;
}

.stock-chip.ok {
    color: #166534;
    background: #dcfce7;
    border-color: #86efac;
}

.stock-chip.low {
    color: #92400e;
    background: #fef3c7;
    border-color: #fcd34d;
}

/* Form Controls */
.form-control, .form-select {
    border: 1.5px solid rgba(96, 165, 250, 0.20);
    border-radius: var(--radius-md);
    font-weight: 500;
    color: var(--text-primary);
    padding: 9px 12px;
    font-size: 0.8125rem;
    transition: var(--transition);
}

.form-control:focus, .form-select:focus {
    border-color: var(--primary-600);
    box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.08);
    outline: none;
}

/* Unit Type Grid */
.unit-grid { 
    display: grid; 
    grid-template-columns: repeat(2, 1fr); 
    gap: 8px; 
    margin-bottom: 18px; 
}

.unit-btn {
    border: 2px solid var(--border);
    color: var(--text-secondary);
    font-weight: 700;
    font-size: 0.6875rem;
    padding: 10px;
    border-radius: var(--radius-sm);
    transition: var(--transition);
    letter-spacing: 0.5px;
    text-transform: uppercase;
}

.unit-btn:hover {
    border-color: var(--primary-600);
    background: var(--gray-50);
    transform: translateY(-1px);
}

.unit-btn.disabled {
    opacity: 0.45;
    pointer-events: none;
}

.btn-check:checked + .unit-btn {
    background: var(--primary-600);
    border-color: var(--primary-600);
    color: #ffffff;
    box-shadow: 0 4px 10px rgba(37, 99, 235, 0.3);
}

.unit-btn small {
    display: block;
    margin-top: 2px;
    font-size: 0.58rem;
    font-weight: 700;
    opacity: 0.86;
    letter-spacing: 0.3px;
    text-transform: none;
}

.unit-helper {
    font-size: 0.68rem;
    color: var(--text-secondary);
    margin: -6px 0 12px;
    line-height: 1.45;
}

/* Subtotal Display */
.subtotal-box { 
    background: linear-gradient(135deg, #fafafa 0%, #ffffff 100%);
    border: 2px solid var(--primary-600);
    border-left: 4px solid var(--primary-600);
    border-radius: var(--radius-md); 
    padding: 16px;
    text-align: center;
    margin-top: 14px;
    box-shadow: var(--shadow-sm);
}

.subtotal-label { 
    font-size: 0.625rem;
    color: var(--text-secondary);
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    margin-bottom: 4px;
}

.subtotal-amount { 
    font-size: 1.375rem;
    font-weight: 800;
    background: linear-gradient(135deg, var(--primary-600) 0%, var(--indigo-600) 100%);
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    background-clip: text;
    letter-spacing: -0.02em;
    font-variant-numeric: tabular-nums;
}

/* Cart Table */
.cart-wrapper { 
    position: relative;
    min-height: 360px;
    max-height: 480px; 
    overflow-y: auto; 
    border: 1.5px solid var(--border);
    border-radius: var(--radius-lg);
    margin-bottom: 18px;
    background:
        linear-gradient(180deg, #fdfefe 0%, #f8fbff 100%);
    box-shadow: inset 0 1px 0 rgba(255,255,255,0.9);
}

.cart-wrapper::-webkit-scrollbar {
    width: 10px;
}

.cart-wrapper::-webkit-scrollbar-track {
    background: #eef4ff;
    border-radius: 999px;
}

.cart-wrapper::-webkit-scrollbar-thumb {
    background: linear-gradient(180deg, #c7d2fe 0%, #93c5fd 100%);
    border-radius: 999px;
    border: 2px solid #eef4ff;
}

.cart-wrapper::-webkit-scrollbar-thumb:hover {
    background: linear-gradient(180deg, #93c5fd 0%, #60a5fa 100%);
}

.cart-table thead th {
    background: linear-gradient(135deg, var(--primary-600) 0%, var(--indigo-600) 100%);
    color: #ffffff;
    font-weight: 700;
    border-bottom: 2px solid #d4af37;
    font-size: 0.6875rem;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    padding: 12px 10px;
    position: sticky;
    top: 0;
    z-index: 10;
}

.cart-table thead th:first-child {
    border-top-left-radius: 14px;
}

.cart-table thead th:last-child {
    border-top-right-radius: 14px;
}

.cart-table tbody tr {
    background: rgba(255, 255, 255, 0.82);
    transition: var(--transition);
}

.cart-table tbody tr:hover {
    background: #ffffff;
}

.cart-table td {
    padding: 10px;
    font-size: 0.8125rem;
}

.cart-qty-input {
    width: 58px;
    min-width: 58px;
    text-align: center;
    border: 1px solid rgba(96, 165, 250, 0.26);
    border-radius: 10px;
    padding: 6px 8px;
    font-weight: 700;
    color: var(--primary-600);
    background: #ffffff;
    box-shadow: inset 0 1px 0 rgba(255,255,255,0.9);
}

.cart-qty-input:focus {
    outline: none;
    border-color: var(--primary-600);
    box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.10);
}

.cart-actions {
    display: inline-flex;
    align-items: center;
    gap: 8px;
}

.btn-cart-edit {
    width: 36px;
    height: 36px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    border: none;
    border-radius: 999px;
    background: linear-gradient(135deg, rgba(37, 99, 235, 0.12) 0%, rgba(79, 70, 229, 0.14) 100%);
    color: var(--primary-600);
    box-shadow: inset 0 1px 0 rgba(255,255,255,0.9);
}

.btn-cart-edit:hover {
    background: linear-gradient(135deg, rgba(37, 99, 235, 0.18) 0%, rgba(79, 70, 229, 0.18) 100%);
    color: var(--primary-700);
}

.cart-empty-state {
    position: absolute;
    top: 54px;
    left: 0;
    right: 0;
    bottom: 0;
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 28px 20px;
    background:
        radial-gradient(circle at top, rgba(250, 204, 21, 0.10) 0%, rgba(250, 204, 21, 0) 58%),
        linear-gradient(180deg, #fffefb 0%, #fffcf6 100%);
}

.cart-empty-shell {
    width: min(100%, 320px);
    padding: 26px 22px;
    text-align: center;
    border: 1px dashed #eddca2;
    border-radius: 20px;
    background: linear-gradient(180deg, rgba(255, 253, 244, 0.98) 0%, rgba(255, 250, 236, 0.96) 100%);
    box-shadow: 0 14px 34px rgba(212, 175, 55, 0.10);
}

.cart-empty-icon {
    width: 64px;
    height: 64px;
    margin: 0 auto 14px;
    border-radius: 18px;
    display: grid;
    place-items: center;
    background: linear-gradient(135deg, #fff8dc 0%, #fef3c7 100%);
    color: #9a6f00;
    font-size: 1.4rem;
    border: 1px solid rgba(212, 175, 55, 0.24);
    box-shadow: inset 0 1px 0 rgba(255,255,255,0.95);
}

.cart-empty-icon svg {
    width: 30px;
    height: 30px;
    display: block;
}

.cart-empty-title {
    color: var(--text-primary);
    font-size: 0.95rem;
    font-weight: 800;
    margin-bottom: 4px;
    letter-spacing: -0.02em;
}

.cart-empty-copy {
    color: var(--text-secondary);
    font-size: 0.8rem;
    margin-bottom: 0;
    line-height: 1.55;
}

.cart-wrapper.has-items .cart-empty-state {
    display: none;
}

/* Summary Panel */
.summary-panel {
    background: linear-gradient(135deg, #fafafa 0%, #ffffff 100%);
    border: 1.5px solid var(--border);
    border-radius: var(--radius-md);
    padding: 18px;
}

.summary-row {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 6px 0;
}

.summary-label {
    font-size: 0.6875rem;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.summary-value {
    font-size: 0.875rem;
    font-weight: 700;
    font-variant-numeric: tabular-nums;
}

/* Grand Total */
.grand-total { 
    font-size: 1.5rem;
    font-weight: 900;
    background: linear-gradient(135deg, var(--success-500) 0%, var(--success-600) 100%);
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    background-clip: text;
    letter-spacing: -0.02em;
    font-variant-numeric: tabular-nums;
}

/* Buttons */
.btn-add-cart { 
    background: linear-gradient(135deg, var(--primary-600) 0%, var(--indigo-600) 100%);
    color: #ffffff;
    border: none;
    font-weight: 700;
    font-size: 0.8125rem;
    border-radius: var(--radius-md);
    padding: 12px;
    transition: var(--transition);
    box-shadow: 0 4px 10px rgba(37, 99, 235, 0.3);
    letter-spacing: 0.3px;
}

.btn-add-cart:hover { 
    transform: translateY(-2px);
    box-shadow: 0 6px 14px rgba(37, 99, 235, 0.4);
}

.btn-process { 
    background: linear-gradient(135deg, var(--success-500) 0%, var(--success-600) 100%);
    color: #ffffff;
    border: none;
    font-weight: 700;
    padding: 16px;
    font-size: 0.875rem;
    border-radius: var(--radius-lg);
    transition: var(--transition);
    box-shadow: 0 6px 14px rgba(34, 197, 94, 0.3);
    letter-spacing: 0.3px;
}

.btn-process:hover { 
    transform: translateY(-2px);
    box-shadow: 0 8px 18px rgba(34, 197, 94, 0.4);
}

.btn-process:disabled {
    opacity: 0.7;
    cursor: not-allowed;
    transform: none;
}

.btn-remove { 
    color: var(--danger-500);
    background: transparent;
    border: 1.5px solid transparent;
    border-radius: var(--radius-sm);
    padding: 5px 9px;
    transition: var(--transition);
}

.btn-remove:hover { 
    background: #fef2f2;
    border-color: var(--danger-500);
}

/* Labels */
label { 
    font-size: 0.6875rem;
    font-weight: 700;
    color: var(--text-secondary);
    text-transform: uppercase;
    margin-bottom: 5px;
    display: block;
    letter-spacing: 0.5px;
}

/* Badge */
.qty-badge {
    font-weight: 600;
    padding: 3px 9px;
    border-radius: 6px;
    font-size: 0.6875rem;
    font-variant-numeric: tabular-nums;
}

/* Tabular Numbers */
.tabular-nums {
    font-variant-numeric: tabular-nums;
}

/* Loading State */
.processing::after {
    content: '';
    position: absolute;
    top: 0;
    left: -100%;
    width: 100%;
    height: 100%;
    background: linear-gradient(90deg, transparent, rgba(255,255,255,0.2), transparent);
    animation: shimmer 1.2s infinite;
}

@keyframes shimmer {
    to { left: 100%; }
}

/* Animations */
@keyframes fadeIn {
    from { opacity: 0; transform: translateY(10px); }
    to { opacity: 1; transform: translateY(0); }
}

.cart-table tbody tr {
    animation: fadeIn 0.3s ease;
}

/* Responsive */
@media (max-width: 1199.98px) and (min-width: 992px) {
    .pos-header-bar {
        padding: 10px 18px;
        gap: 14px;
    }

    .pos-header-bar > .d-flex:first-child,
    .pos-header-bar > .d-flex:last-child {
        min-width: 0;
        flex: 1 1 0;
    }

    .pos-header-bar > .d-flex:first-child {
        gap: 10px !important;
    }

    .pos-header-bar > .d-flex:last-child {
        justify-content: flex-end;
        gap: 10px !important;
    }

    .search-wrapper {
        min-width: 0;
        max-width: 360px;
    }

    .user-info {
        display: block !important;
        margin-right: 0;
        max-width: 120px;
    }

    .clock-badge {
        padding: 6px 10px;
        font-size: 0.7rem;
    }
}

@media (max-width: 768px) {
    .pos-header-bar {
        height: auto;
        padding: 10px;
        flex-wrap: wrap;
    }
    
    .search-wrapper {
        order: 3;
        width: 100%;
        max-width: 100%;
        min-width: 0;
        margin: 8px 0 0 0;
    }
    
    .user-info {
        display: none;
    }

    .product-grid {
        grid-template-columns: repeat(3, minmax(0, 1fr));
        max-height: 260px;
        grid-auto-rows: minmax(84px, auto);
    }

    .header-util-bar {
        flex-direction: column;
        align-items: flex-start;
    }

    .header-util-note {
        text-align: left;
    }

    .header-stage {
        padding: 8px;
    }

    .category-pills-wrap {
        flex-direction: column;
        align-items: stretch;
    }

    .category-toggle-btn {
        align-self: flex-end;
    }
}
</style>

<div class="pos-full-page-wrapper">
    @php
        $rawPlan = strtolower(
            (string) (
                optional($currentSubscription ?? null)->plan_name
                ?? optional($currentSubscription ?? null)->plan
                ?? optional(auth()->user()?->company)->plan
                ?? 'basic'
            )
        );
        $role = strtolower((string) (auth()->user()->role ?? ''));
        $headerStagePlanClass = match (true) {
            in_array($role, ['super_admin', 'administrator'], true) => 'plan-super',
            str_contains($rawPlan, 'enterprise') => 'plan-enterprise',
            str_contains($rawPlan, 'pro') || str_contains($rawPlan, 'professional') => 'plan-pro',
            default => 'plan-basic',
        };
    @endphp

    <div class="header-stage {{ $headerStagePlanClass }}">
        <div class="header-util-bar">
            <div class="util-pills">
                <span class="util-pill">Shelf: <span id="hdr-shelf-count">{{ $products->count() }}</span></span>
                <span class="util-pill">Selected: <span id="hdr-selected-product">None</span></span>
                <span class="util-pill">Cart Items: <span id="hdr-cart-count">0</span></span>
                <span class="util-pill">
                    Branch:
                    <span id="hdr-branch-name">{{ $activeBranch['name'] ?? 'Main Workspace' }}</span>
                </span>
            </div>
            <div class="header-util-note">Use <span class="accent">search</span> to quickly find products not visible on shelf.</div>
        </div>

        <!-- Header -->
        <div class="pos-header-bar">
                <div class="d-flex align-items-center gap-3">
                    <h5 class="pos-header-title">SALES <span class="gradient-text">TERMINAL</span></h5>
                    <div class="clock-badge">
                        <i class="far fa-clock me-1"></i><span id="live-clock" class="tabular-nums">00:00:00</span>
                    </div>
                    <div class="clock-badge" style="background: linear-gradient(135deg, #dbeafe 0%, #bfdbfe 100%); color: #1d4ed8; border-color: rgba(37, 99, 235, 0.16); box-shadow: 0 8px 18px rgba(59, 130, 246, 0.14);">
                        <i class="fas fa-code-branch me-1"></i>{{ $activeBranch['name'] ?? 'Main Workspace' }}
                    </div>
                </div>

            <div class="d-flex align-items-center gap-3">
                <div class="search-wrapper">
                    <div class="search-icon-wrapper">
                        <i class="fas fa-search search-icon"></i>
                    </div>
                    <input type="text" id="quick-search" class="form-control search-input" placeholder="Search product by name, SKU or category...">
                    <span class="search-kbd">Ctrl + K</span>
                </div>

                <div class="d-flex align-items-center gap-2">
                    <div class="user-info d-none d-md-block">
                        <div class="user-label">Cashier</div>
                        <div class="user-name">{{ auth()->user()->name ?? 'Admin' }}</div>
                    </div>
                    <div class="user-avatar">
                        <i class="fas fa-user text-white" style="font-size: 0.875rem;"></i>
                    </div>
                </div>
            </div>        
        </div>        
    </div>

    <div class="card pos-card p-4 mb-4">
        <div class="product-toolbar">
            <span class="toolbar-title">Product Shelf</span>
            <span class="small text-muted" id="product-count">{{ $products->count() }} item(s)</span>
        </div>

        @php
            $shelfCategories = $products->pluck('category.name')->filter()->unique()->values();
        @endphp
        <div class="category-pills-wrap">
            <div class="category-pills collapsed" id="category-pills">
                <button type="button" class="category-pill active" data-category="all">All</button>
                @foreach($shelfCategories as $categoryName)
                <button type="button" class="category-pill" data-category="{{ strtolower($categoryName) }}">{{ $categoryName }}</button>
                @endforeach
            </div>
            <button type="button" class="category-toggle-btn" id="category-toggle">Show More</button>
        </div>

        <div class="product-grid" id="product-grid">
            @foreach($products as $p)
            @php
                $retailPrice = (float) ($p->retail_price ?? $p->price ?? 0);
                $wholesalePrice = (float) ($p->wholesale_price ?? 0);
                $specialPrice = (float) ($p->special_price ?? 0);
                $rollsPerCarton = max((int) ($p->units_per_carton ?? 0), 0);
                $unitsPerRoll = max((int) ($p->units_per_roll ?? 0), 0);
                $categoryName = $p->category->name ?? 'Uncategorized';
                $minStockLevel = (int) ($p->min_stock_level ?? 15);
                $availableStock = (float) ($p->available_stock ?? $p->stock ?? 0);
                $isOutOfStock = $availableStock <= 0;
            @endphp
            <div class="product-card"
                title="{{ $p->name }}"
                data-id="{{ $p->id }}"
                data-name="{{ $p->name }}"
                data-search-name="{{ strtolower($p->name) }}"
                data-sku="{{ strtolower($p->sku ?? '') }}"
                data-barcode="{{ strtolower($p->barcode ?? '') }}"
                data-category="{{ strtolower($categoryName) }}"
                data-category-name="{{ $categoryName }}"
                data-price="{{ $retailPrice }}"
                data-retail="{{ $retailPrice }}"
                data-wholesale="{{ $wholesalePrice }}"
                data-special="{{ $specialPrice }}"
                data-stock="{{ $availableStock }}"
                data-upc="{{ $rollsPerCarton }}"
                data-upr="{{ $unitsPerRoll }}"
                data-base-unit="{{ strtolower($p->base_unit_name ?? 'unit') }}"
                data-min-stock="{{ $minStockLevel }}"
                data-img="{{ $p->image_url }}"
                data-out-of-stock="{{ $isOutOfStock ? '1' : '0' }}"
                @if($isOutOfStock) aria-disabled="true" style="pointer-events:none; opacity:.55; filter:grayscale(.15);" @endif>
                <div class="product-card-img">
                    @if($p->image_url)
                        <img src="{{ $p->image_url }}" alt="{{ $p->name }}">
                    @else
                        <div class="d-flex align-items-center justify-content-center h-100 w-100 text-primary bg-light">
                            <i class="fas fa-box-open"></i>
                        </div>
                    @endif
                </div>
            </div>
            @endforeach
        </div>
    </div>

    <div class="row g-4">
        <div class="col-xl-4">
            <div class="controls-card">
                <div class="scanner-section mb-2">
                    <label class="scanner-label"><i class="fas fa-barcode me-1"></i> Barcode Scanner</label>
                    <input type="text" id="barcode-input" class="form-control scanner-input" placeholder="Scan product..." autofocus>
                </div>

                <div id="image-frame" class="image-frame mb-2">
                    <img id="product-img" src="" style="display:none; max-height: 90%; width: auto; border-radius: 8px;">
                    <div id="no-img" class="text-center" style="color: var(--text-tertiary);">
                        <i class="fas fa-image fa-2x mb-2 opacity-50"></i>
                        <p class="small fw-bold mb-0" style="font-size: 0.6875rem;">NO IMAGE</p>
                    </div>
                </div>

                <label>Select Product</label>
                <select id="product-search" class="form-select mb-2">
                    <option value="">Search product name...</option>
                    @foreach($products as $p)
                    <option value="{{ $p->id }}">{{ $p->name }}{{ $p->sku ? ' (' . $p->sku . ')' : '' }}</option>
                    @endforeach
                </select>

                <select id="product-select" class="form-select hidden-product-select">
                    <option value="">-- Choose Product --</option>
                    @foreach($products as $p)
                    @php
                        $retailPrice = (float) ($p->retail_price ?? $p->price ?? 0);
                        $wholesalePrice = (float) ($p->wholesale_price ?? 0);
                        $specialPrice = (float) ($p->special_price ?? 0);
                        $rollsPerCarton = max((int) ($p->units_per_carton ?? 0), 0);
                        $unitsPerRoll = max((int) ($p->units_per_roll ?? 0), 0);
                        $categoryName = $p->category->name ?? 'Uncategorized';
                        $minStockLevel = (int) ($p->min_stock_level ?? 15);
                    @endphp
                    <option value="{{ $p->id }}" 
                        data-sku="{{ $p->sku }}" 
                        data-barcode="{{ $p->barcode }}" 
                        data-name="{{ $p->name }}" 
                        data-price="{{ $retailPrice }}" 
                        data-retail="{{ $retailPrice }}"
                        data-wholesale="{{ $wholesalePrice }}"
                        data-special="{{ $specialPrice }}"
                        data-stock="{{ (float) ($p->available_stock ?? $p->stock) }}" 
                        data-upc="{{ $rollsPerCarton }}"
                        data-upr="{{ $unitsPerRoll }}"
                        data-base-unit="{{ strtolower($p->base_unit_name ?? 'unit') }}"
                        data-category="{{ strtolower($categoryName) }}"
                        data-category-name="{{ $categoryName }}"
                        data-min-stock="{{ $minStockLevel }}"
                        data-img="{{ $p->image_url }}">
                        {{ $p->sku }} | {{ $p->name }}
                    </option>
                    @endforeach
                </select>

                <label class="mt-2">Unit Type</label>
                <div class="unit-grid mb-2">
                    <input type="radio" class="btn-check" name="unit_type" id="unit-type-unit" value="unit" checked>
                    <label class="btn unit-btn" for="unit-type-unit">Unit<small id="unit-meta-unit">1 unit</small></label>

                    <input type="radio" class="btn-check" name="unit_type" id="unit-type-roll" value="roll">
                    <label class="btn unit-btn" for="unit-type-roll">Roll<small id="unit-meta-roll">Set by product</small></label>

                    <input type="radio" class="btn-check" name="unit_type" id="unit-type-carton" value="carton">
                    <label class="btn unit-btn" for="unit-type-carton">Carton<small id="unit-meta-carton">Set by product</small></label>
                </div>
                <div class="unit-helper" id="unit-helper-copy">Select a product to unlock the right unit packs and live pricing.</div>

                <div class="row g-2 mb-2">
                    <div class="col-12">
                        <label>Price Level</label>
                        <select id="price-tier" class="form-select">
                            <option value="retail">Retail / Default</option>
                            <option value="wholesale">Wholesale</option>
                            <option value="special">Special Discount</option>
                        </select>
                    </div>
                    <div class="col-6">
                        <label>Price</label>
                        <input type="number" id="unit-price-input" class="form-control bg-light fw-bold tabular-nums" readonly>
                    </div>
                    <div class="col-6">
                        <label id="qty-label">Quantity</label>
                        <input type="number" id="quantity" class="form-control fw-bold tabular-nums" value="1" min="0.01" step="0.01">
                    </div>
                    <div class="col-6">
                        <label style="color: var(--danger-500);">Discount</label>
                        <div class="input-group">
                            <select id="discount-type" class="form-select">
                                <option value="percent">%</option>
                                <option value="fixed">₦</option>
                            </select>
                            <input type="number" id="discount" class="form-control tabular-nums" value="0" min="0" step="0.01" inputmode="decimal">
                        </div>
                        <small class="text-muted" id="discount-helper">Percent of item subtotal</small>
                    </div>
                    <div class="col-6">
                        <label style="color: var(--primary-600);">Tax %</label>
                        <input type="number" id="tax" class="form-control tabular-nums" value="0" min="0" max="100">
                    </div>
                </div>

                <div class="subtotal-box mt-0">
                    <div class="subtotal-label">Item Subtotal</div>
                    <div class="subtotal-amount" id="item-total">₦0.00</div>
                </div>

                <button id="add-btn" type="button" class="btn btn-add-cart w-100 py-2 mt-2">
                    <i class="fas fa-plus-circle me-2"></i> ADD TO CART
                </button>

                <div class="quick-fill-panel mt-2">
                    <div class="quick-fill-title">Quick Summary</div>
                    <div class="quick-fill-row"><span>Selected Product</span><strong id="quick-selected-name">None</strong></div>
                    <div class="quick-fill-row"><span>SKU</span><strong id="quick-selected-sku">-</strong></div>
                    <div class="quick-fill-row"><span>Category</span><strong id="quick-selected-category">-</strong></div>
                    <div class="quick-fill-row"><span>Min Stock Level</span><strong id="quick-selected-min-stock">15</strong></div>
                    <div class="quick-fill-row"><span>Available Stock</span><strong id="quick-selected-stock">0</strong></div>
                    <div class="quick-fill-row mb-0">
                        <span>Stock Health</span>
                        <span id="quick-stock-health" class="stock-chip ok">OK</span>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-8">
            <div class="card pos-card p-4">
                <!-- Customer -->
                <div class="mb-3">
                    <label>Customer</label>
                    <select id="customer-select" class="form-select">
                        <option value="">Walk-in Customer</option>
                        @foreach($customers as $c)
                        <option value="{{ $c->id }}">{{ $c->name ?? $c->customer_name ?? ('Customer #' . $c->id) }}</option>
                        @endforeach
                    </select>
                </div>

                <!-- Cart -->
                <div class="cart-wrapper">
                    <div id="cart-empty-state" class="cart-empty-state">
                        <div class="cart-empty-shell">
                            <div class="cart-empty-icon">
                                <svg viewBox="0 0 24 24" fill="none" aria-hidden="true">
                                    <path d="M3 4h2l1.4 7.2a1 1 0 0 0 1 .8h8.9a1 1 0 0 0 1-.75L19 7H7" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/>
                                    <circle cx="10" cy="18" r="1.6" fill="currentColor"/>
                                    <circle cx="17" cy="18" r="1.6" fill="currentColor"/>
                                </svg>
                            </div>
                            <div class="cart-empty-title">Cart Empty</div>
                            <p class="cart-empty-copy">Select products from the catalog and they will appear here in a smooth scrollable cart.</p>
                        </div>
                    </div>
                    <table class="table cart-table mb-0">
                        <thead>
                            <tr>
                                <th class="ps-3">Product</th>
                                <th class="text-center">Qty</th>
                                <th class="text-end">Amount</th>
                                <th class="text-center">Action</th>
                            </tr>
                        </thead>
                        <tbody id="cart-body"></tbody>
                    </table>
                </div>

                <!-- Summary -->
                <div class="summary-panel">
                    <div class="summary-row">
                        <span class="summary-label" style="color: var(--text-secondary);">Subtotal</span>
                        <span class="summary-value" style="color: var(--text-primary);" id="sum-subtotal">₦0.00</span>
                    </div>
                    <div class="summary-row">
                        <span class="summary-label" style="color: var(--danger-500);">Discount</span>
                        <span class="summary-value" style="color: var(--danger-500);" id="sum-discount">₦0.00</span>
                    </div>
                    <div class="summary-row pb-3 border-bottom">
                        <span class="summary-label" style="color: var(--primary-600);">Tax</span>
                        <span class="summary-value" style="color: var(--primary-600);" id="sum-tax">₦0.00</span>
                    </div>

                    <div class="d-flex justify-content-between align-items-center mt-3 mb-4">
                        <h6 class="mb-0 fw-bold" style="color: var(--text-primary); font-size: 0.875rem;">Total Amount</h6>
                        <div class="grand-total" id="grand-total">₦0.00</div>
                    </div>

                    <!-- Payment -->
                    <div class="row g-3 border-top pt-3">
                        <div class="col-md-6">
                            <label>Payment Method</label>
                            <select id="payment-method" class="form-select fw-bold">
                                <option value="Cash">Cash</option>
                                <option value="Split">Split (Cash + Transfer + POS)</option>
                            </select>
                            <small class="text-muted">POS accepts cash or split (cash + transfer + POS). No credit sales.</small>
                        </div>
                        <div class="col-md-6">
                            <label>Cash Amount</label>
                            <input type="number" id="amount-paid" class="form-control form-control-lg fw-bold text-end tabular-nums" style="font-size: 1rem; color: var(--success-500);">
                        </div>
                        <div class="col-md-6 d-none" id="split-transfer-wrap">
                            <label>Transfer Amount</label>
                            <input type="number" id="transfer-amount" class="form-control form-control-lg fw-bold text-end tabular-nums" style="font-size: 1rem; color: var(--primary-600);">
                        </div>
                        <div class="col-md-6 d-none" id="split-transfer-account-wrap">
                            <label>Transfer Account</label>
                            <select id="transfer-account" class="form-select">
                                <option value="">-- Choose Bank --</option>
                                @foreach($bankAccounts as $bank)
                                    <option value="{{ $bank->id }}">{{ $bank->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-6 d-none" id="split-card-wrap">
                            <label>POS Amount</label>
                            <input type="number" id="card-amount" class="form-control form-control-lg fw-bold text-end tabular-nums" style="font-size: 1rem; color: var(--primary-600);">
                        </div>
                        <div class="col-md-6 d-none" id="split-card-account-wrap">
                            <label>POS Account</label>
                            <select id="card-account" class="form-select">
                                <option value="">-- Choose Bank --</option>
                                @foreach($bankAccounts as $bank)
                                    <option value="{{ $bank->id }}">{{ $bank->name }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    <!-- Change -->
                    <div class="d-flex justify-content-between align-items-center mt-3">
                        <span class="summary-label" style="color: var(--text-secondary);">Change</span>
                        <span id="change-amount" class="fw-bold tabular-nums" style="font-size: 1.125rem; color: var(--success-500);">₦0.00</span>
                    </div>
                </div>

                <!-- Process Button -->
                <button type="button" id="process-btn" class="btn btn-process w-100 mt-3">
                    <span id="btn-text"><i class="fas fa-check-circle me-2"></i> PROCESS SALE</span>
                    <span id="btn-loading" style="display:none;"><i class="fas fa-sync fa-spin me-2"></i> PROCESSING...</span>
                </button>
            </div>
        </div>
    </div>
</div>

<script src="{{ asset('assets/js/jquery-3.7.1.min.js') }}"></script>
<script src="{{ asset('assets/js/select2.min.js') }}"></script>

<script>
window.POS_USE_SAFE_TERMINAL = true;
window.POS_FALLBACK_REQUESTED = false;
window.requestPosFallback = function(reason) {
    if (window.POS_FALLBACK_REQUESTED) return;
    window.POS_FALLBACK_REQUESTED = true;
    if (typeof window.POS_ENABLE_FALLBACK === 'function') {
        window.POS_ENABLE_FALLBACK();
    }
};
window.addEventListener('error', function() {
    window.requestPosFallback('js-error');
});

$(document).ready(function() {
    if (window.POS_USE_SAFE_TERMINAL) {
        if (typeof window.POS_ENABLE_FALLBACK === 'function') {
            window.POS_ENABLE_FALLBACK();
        }
        return;
    }

    let cart = [];
    let lastSelectedProductId = null;
    let isSyncingProductSearch = false;
    const fmt = new Intl.NumberFormat('en-NG', { style: 'currency', currency: 'NGN' });
    const showAlert = (options) => {
        if (window.Swal && typeof Swal.fire === 'function') {
            return Swal.fire(options);
        }
        if (options?.icon === 'success') {
            return;
        }
        const message = options?.text || options?.title || 'Action required';
        window.alert(message);
    };

    // Clock
    setInterval(() => $('#live-clock').text(new Date().toLocaleTimeString('en-US', { hour12: false })), 1000);

    const hasSelect2 = !!($.fn && $.fn.select2);
    if (hasSelect2) {
        $('#customer-select').select2({ width: '100%' });
        $('#product-search').select2({
            width: '100%',
            placeholder: 'Search product name...',
            allowClear: true
        });
    }
    $(document).on('keydown', function(e) {
        if ((e.ctrlKey || e.metaKey) && e.key.toLowerCase() === 'k') {
            e.preventDefault();
            $('#quick-search').trigger('focus');
        }
    });

    function syncProductSearchValue(value) {
        isSyncingProductSearch = true;
        if (value === null || value === undefined || value === '') {
            $('#product-search').val(null);
        } else {
            $('#product-search').val(String(value));
        }
        $('#product-search').trigger('change');
        if (hasSelect2) {
            $('#product-search').trigger('change.select2');
        }
        isSyncingProductSearch = false;
    }

    function syncActiveProductCard(productId) {
        $('.product-card').removeClass('active');
        if (productId) {
            $(`.product-card[data-id="${productId}"]`).addClass('active');
            lastSelectedProductId = String(productId);
            keepLastSelectedVisible();
        }
    }

    function keepLastSelectedVisible() {
        if (!lastSelectedProductId) return;
        const card = $(`.product-card[data-id="${lastSelectedProductId}"]`);
        if (!card.length) return;
        $('.product-card').removeClass('last-picked');
        card.show().addClass('last-picked').prependTo('#product-grid');
    }

    function filterProductCards() {
        const keyword = ($('#quick-search').val() || '').toLowerCase().trim();
        const activeCategory = $('.category-pill.active').data('category') || 'all';
        let visibleCount = 0;

        $('.product-card').each(function() {
            const card = $(this);
            const name = card.data('search-name') || (card.data('name') || '').toString().toLowerCase();
            const sku = card.data('sku') || '';
            const category = card.data('category') || '';

            const matchesKeyword = !keyword || name.includes(keyword) || sku.includes(keyword) || category.includes(keyword);
            const matchesCategory = activeCategory === 'all' || category === activeCategory;
            const show = matchesKeyword && matchesCategory;

            card.toggle(show);
            if (show) visibleCount += 1;
        });

        keepLastSelectedVisible();
        visibleCount = $('.product-card:visible').length;
        $('#product-count').text(`${visibleCount} item(s)`);
        $('#hdr-shelf-count').text(visibleCount);
    }

    function setUnitTypeAvailability(selectedOption) {
        const hasProduct = !!selectedOption && !!selectedOption.val();
        const unitsPerCarton = hasProduct ? (parseInt(selectedOption.data('upc')) || 0) : 0;
        const unitsPerRoll = hasProduct ? (parseInt(selectedOption.data('upr')) || 0) : 0;

        const cartonInput = $('#unit-type-carton');
        const rollInput = $('#unit-type-roll');
        const cartonLabel = $('label[for="unit-type-carton"]');
        const rollLabel = $('label[for="unit-type-roll"]');
        const unitLabel = $('label[for="unit-type-unit"]');

        const baseUnit = hasProduct ? String(selectedOption.data('base-unit') || 'unit') : 'unit';
        const cartonEnabled = !hasProduct || unitsPerCarton > 0;
        const rollEnabled = !hasProduct || unitsPerRoll > 0;

        cartonInput.prop('disabled', !cartonEnabled);
        rollInput.prop('disabled', !rollEnabled);
        cartonLabel.toggleClass('disabled', !cartonEnabled);
        rollLabel.toggleClass('disabled', !rollEnabled);

        $('#unit-meta-unit').text(hasProduct ? `1 ${baseUnit}` : '1 unit');
        $('#unit-meta-roll').text(rollEnabled ? `${unitsPerRoll} ${baseUnit}${unitsPerRoll === 1 ? '' : 's'} / roll` : 'Unavailable');
        $('#unit-meta-carton').text(
            cartonEnabled
                ? (unitsPerRoll > 0
                    ? `${unitsPerCarton * unitsPerRoll} ${baseUnit}${(unitsPerCarton * unitsPerRoll) === 1 ? '' : 's'} / carton`
                    : `${unitsPerCarton} ${baseUnit}${unitsPerCarton === 1 ? '' : 's'} / carton`)
                : 'Unavailable'
        );
        unitLabel.toggleClass('disabled', false);

        const currentType = $('input[name="unit_type"]:checked').val();
        if ((currentType === 'carton' && !cartonEnabled) || (currentType === 'roll' && !rollEnabled)) {
            $('#unit-type-unit').prop('checked', true);
        }
    }

    function resolveUnitMetrics(selectedOption) {
        const type = $('input[name="unit_type"]:checked').val() || 'unit';
        const stock = parseInt(selectedOption.data('stock')) || 0;
        const rollsPerCarton = Math.max(parseInt(selectedOption.data('upc')) || 0, 0);
        const unitsPerRoll = Math.max(parseInt(selectedOption.data('upr')) || 0, 0);
        const baseUnit = String(selectedOption.data('base-unit') || 'unit');
        const cartonUnits = rollsPerCarton > 0
            ? (unitsPerRoll > 0 ? (rollsPerCarton * unitsPerRoll) : rollsPerCarton)
            : 0;

        let multiplier = 1;
        let unitName = `${baseUnit}s`;

        if (type === 'carton' && cartonUnits > 0) {
            multiplier = cartonUnits;
            unitName = 'cartons';
        } else if (type === 'roll' && unitsPerRoll > 0) {
            multiplier = unitsPerRoll;
            unitName = 'rolls';
        } else {
            unitName = `${baseUnit}${baseUnit.endsWith('s') ? '' : 's'}`;
        }

        const maxQty = multiplier > 0 ? Math.max(Math.floor(stock / multiplier), 0) : stock;

        return {
            type,
            stock,
            multiplier,
            unitName,
            maxQty,
            baseUnit,
            rollsPerCarton,
            unitsPerRoll,
            cartonUnits
        };
    }

    function getSelectedBasePrice(selectedOption) {
        const tier = $('#price-tier').val() || 'retail';
        const retailPrice = parseFloat(selectedOption.data('retail')) || parseFloat(selectedOption.data('price')) || 0;
        const wholesalePrice = parseFloat(selectedOption.data('wholesale')) || 0;
        const specialPrice = parseFloat(selectedOption.data('special')) || 0;

        if (tier === 'wholesale' && wholesalePrice > 0) {
            return { value: wholesalePrice, key: 'wholesale', label: 'Wholesale' };
        }

        if (tier === 'special' && specialPrice > 0) {
            return { value: specialPrice, key: 'special', label: 'Special Discount' };
        }

        return { value: retailPrice, key: 'retail', label: 'Retail / Default' };
    }

    function applySelectedProductPricing(selectedOption) {
        if (!selectedOption || !selectedOption.val()) {
            $('#unit-price-input').val('');
            return null;
        }

        const unitMeta = resolveUnitMetrics(selectedOption);
        const basePrice = getSelectedBasePrice(selectedOption);
        const computedPrice = unitMeta.multiplier > 1 ? (basePrice.value * unitMeta.multiplier) : basePrice.value;
        $('#unit-price-input').val(computedPrice.toFixed(2));

        return { unitMeta, basePrice, computedPrice };
    }

    $(document).on('click', '.category-pill', function() {
        $('.category-pill').removeClass('active');
        $(this).addClass('active');
        filterProductCards();
    });

    function syncCategoryToggle() {
        const pillsCount = $('#category-pills .category-pill').length;
        if (pillsCount <= 7) {
            $('#category-pills').removeClass('collapsed');
            $('#category-toggle').hide();
            return;
        }

        $('#category-toggle').show().text(
            $('#category-pills').hasClass('collapsed') ? 'Show More' : 'Show Less'
        );
    }

    $('#category-toggle').on('click', function() {
        $('#category-pills').toggleClass('collapsed');
        syncCategoryToggle();
    });

    $('#quick-search').on('input', filterProductCards);

    $(document).on('click', '.product-card', function() {
        applyProductSelection($(this));
    });

    $('#product-search').on('change select2:select', function() {
        if (isSyncingProductSearch) {
            return;
        }
        const productId = $(this).val();
        if (!productId) return;
        const option = $(`#product-select option[value="${productId}"]`);
        $('#product-select').val(String(productId));
        applyProductSelection(option);
    });

    // Barcode
    let barcodeBuffer = '';
    let barcodeTimeout;

    function commitBarcodeScan(rawCode) {
        const normalizedCode = String(rawCode || '').trim().toLowerCase();
        if (!normalizedCode) {
            return false;
        }

        let matchedOption = null;
        $('#product-select option').each(function() {
            const option = $(this);
            const barcode = String(option.data('barcode') || '').trim().toLowerCase();
            const sku = String(option.data('sku') || '').trim().toLowerCase();

            if (barcode && barcode === normalizedCode) {
                matchedOption = option;
                return false;
            }

            if (!matchedOption && sku && sku === normalizedCode) {
                matchedOption = option;
            }
        });

        if (matchedOption && matchedOption.val()) {
            $('#product-select').val(matchedOption.val());
            applyProductSelection(matchedOption);
            $('#barcode-input').val('');
            barcodeBuffer = '';
            return true;
        }

        showAlert({
            icon: 'error',
            title: 'Product Not Found',
            text: `No product matched "${rawCode}"`,
            timer: 1800,
            toast: true,
            position: 'top-end',
            showConfirmButton: false
        });
        $('#barcode-input').select();
        return false;
    }

    $('#barcode-input').on('input', function() {
        clearTimeout(barcodeTimeout);
        barcodeBuffer = $(this).val();

        barcodeTimeout = setTimeout(() => {
            if (barcodeBuffer && barcodeBuffer.trim().length >= 3) {
                commitBarcodeScan(barcodeBuffer);
            }
        }, 220);
    });

    $('#barcode-input').on('keydown', function(e) {
        if (e.key === 'Enter') {
            e.preventDefault();
            clearTimeout(barcodeTimeout);
            barcodeBuffer = $(this).val();
            commitBarcodeScan(barcodeBuffer);
        }
    });

    // Calculate
    function calculate() {
        let price = parseFloat($('#unit-price-input').val()) || 0;
        let qty = parseFloat($('#quantity').val()) || 1;
        let disc = parseFloat($('#discount').val()) || 0;
        let tax = parseFloat($('#tax').val()) || 0;
        let discType = $('#discount-type').val() || 'percent';

        let sub = price * qty;
        let discVal = discType === 'fixed' ? Math.min(disc, sub) : (sub * (disc / 100));
        let afterDisc = sub - discVal;
        let taxVal = afterDisc * (tax / 100);
        let total = afterDisc + taxVal;

        $('#item-total').text(fmt.format(total));
        return { sub, discVal, taxVal, total, discType, disc };
    }

    function calculateCartLine(item) {
        const qty = parseFloat(item.qty) || 1;
        const price = parseFloat(item.price) || 0;
        const discount = parseFloat(item.discountValue ?? item.discount) || 0;
        const discountType = item.discountType || 'percent';
        const tax = parseFloat(item.tax) || 0;

        const sub = price * qty;
        const discVal = discountType === 'fixed'
            ? Math.min(discount, sub)
            : (sub * (discount / 100));
        const afterDisc = sub - discVal;
        const taxVal = afterDisc * (tax / 100);
        const total = afterDisc + taxVal;

        item.sub = sub;
        item.discVal = discVal;
        item.taxVal = taxVal;
        item.total = total;
        item.discountType = discountType;
        item.discountValue = discount;

        return item;
    }

    // Product Change
    function applyProductSelection(source) {
        const sourceEl = source && source.jquery ? source : $(source);
        const productId = sourceEl.val() || sourceEl.data('id') || '';
        if (productId && $('#product-select').val() !== String(productId)) {
            $('#product-select').val(String(productId));
        }
        const selectOption = productId ? $(`#product-select option[value="${productId}"]`) : $();
        const opt = selectOption.length ? selectOption : sourceEl;
        if (!productId) {
            $('#unit-price-input').val('');
            $('#qty-label').text('Quantity');
            $('#unit-helper-copy').text('Select a product to unlock the right unit packs and live pricing.');
            $('#product-img').hide();
            $('#no-img').show();
            syncProductSearchValue(null);
            $('#hdr-selected-product').text('None');
            $('#quick-selected-name').text('None');
            $('#quick-selected-sku').text('-');
            $('#quick-selected-category').text('-');
            $('#quick-selected-min-stock').text('15');
            $('#quick-selected-stock').text('0');
            $('#quick-stock-health').removeClass('low').addClass('ok').text('OK');
            setUnitTypeAvailability(null);
            syncActiveProductCard(null);
            calculate();
            return;
        }

        setUnitTypeAvailability(opt);
        const pricingState = applySelectedProductPricing(opt);
        const unitMeta = pricingState ? pricingState.unitMeta : resolveUnitMetrics(opt);
        const basePrice = pricingState ? pricingState.basePrice : getSelectedBasePrice(opt);
        $('#qty-label').text(`Quantity (${unitMeta.maxQty || 0} ${unitMeta.unitName} available)`);
        $('#quantity').attr('max', Math.max(unitMeta.maxQty, 0.01));
        if ((parseFloat($('#quantity').val()) || 0.01) > Math.max(unitMeta.maxQty, 0.01)) {
            $('#quantity').val(Math.max(unitMeta.maxQty, 0.01));
        }
        $('#unit-helper-copy').text(
            unitMeta.type === 'unit'
                ? `Selling in single ${unitMeta.baseUnit}${unitMeta.baseUnit.endsWith('s') ? '' : 's'} using the ${basePrice.label.toLowerCase()} price.`
                : `Each ${unitMeta.type} uses ${unitMeta.multiplier} unit(s) and the ${basePrice.label.toLowerCase()} price. Available: ${unitMeta.maxQty} ${unitMeta.unitName}.`
        );
        syncProductSearchValue(productId);
        $('#hdr-selected-product').text(opt.data('name'));
        $('#quick-selected-name').text(opt.data('name'));
        $('#quick-selected-sku').text(opt.data('sku') || '-');
        $('#quick-selected-category').text(opt.data('category-name') || 'Uncategorized');
        const minStock = parseInt(opt.data('min-stock')) || 15;
        const stock = parseInt(opt.data('stock')) || 0;
        $('#quick-selected-min-stock').text(minStock);
        $('#quick-selected-stock').text(stock);
        if (stock <= minStock) {
            $('#quick-stock-health').removeClass('ok').addClass('low').text('LOW');
        } else {
            $('#quick-stock-health').removeClass('low').addClass('ok').text('OK');
        }
        syncActiveProductCard(productId);

        if (opt.data('img')) {
            $('#product-img').attr('src', opt.data('img')).show();
            $('#no-img').hide();
        } else {
            $('#product-img').hide();
            $('#no-img').show();
        }

        calculate();
    }

    $('#product-select').on('change', function() {
        applyProductSelection($(this).find(':selected'));
    });

    $('input[name="unit_type"]').on('change', () => $('#product-select').trigger('change'));
    $('#price-tier').on('change', () => $('#product-select').trigger('change'));
    $(document).on('input', '#quantity, #discount, #tax', calculate);
    $(document).on('change', '#discount-type', function() {
        const type = $('#discount-type').val();
        $('#discount-helper').text(type === 'fixed' ? 'Fixed amount off item subtotal' : 'Percent of item subtotal');
        $('#discount').attr('max', type === 'fixed' ? '' : '100');
        calculate();
    });

    // Add to Cart
    $('#add-btn').on('click', function(e) {
        e.preventDefault();
        let opt = $('#product-select').find(':selected');
        if(!opt.val()) {
            showAlert({ icon: 'warning', title: 'No Product', text: 'Select a product', confirmButtonColor: '#2563eb' });
            return;
        }

        let qty = parseFloat($('#quantity').val()) || 1;
        const unitMeta = resolveUnitMetrics(opt);
        let stock = opt.data('stock');
        const maxAllowed = Math.max(unitMeta.maxQty, 0);
        
        if(maxAllowed <= 0) {
            showAlert({ icon: 'error', title: 'Unavailable', text: `No ${unitMeta.unitName} available for this product`, confirmButtonColor: '#ef4444' });
            return;
        }

        if(qty > maxAllowed) {
            showAlert({ icon: 'error', title: 'Low Stock', text: `Only ${maxAllowed} ${unitMeta.unitName} available`, confirmButtonColor: '#ef4444' });
            return;
        }

        let res = calculate();
        const selectedPriceLevel = getSelectedBasePrice(opt);

        cart.push({
            id: opt.val(),
            name: opt.data('name'),
            qty: qty,
            priceLevel: selectedPriceLevel.key,
            priceLevelLabel: selectedPriceLevel.label,
            unitType: unitMeta.type,
            unitLabel: unitMeta.type === 'unit' ? unitMeta.baseUnit : unitMeta.type,
            stockUnits: unitMeta.multiplier * qty,
            price: parseFloat($('#unit-price-input').val()),
            discount: res.discType === 'fixed' ? 0 : (parseFloat($('#discount').val()) || 0),
            discountType: res.discType,
            discountValue: parseFloat($('#discount').val()) || 0,
            tax: parseFloat($('#tax').val()) || 0,
            sub: res.sub,
            discVal: res.discVal,
            taxVal: res.taxVal,
            total: res.total
        });

        renderCart();
        $('#product-select').val('');
        applyProductSelection($('#product-select').find(':selected'));
        $('#quantity').val(1);
        $('#discount, #tax').val(0);
        $('#discount-type').val('percent');
        $('#discount-helper').text('Percent of item subtotal');
        $('#discount').attr('max', '100');
        $('#price-tier').val('retail');
        $('#barcode-input').val('').focus();
        
        showAlert({ icon: 'success', title: 'Added', timer: 1000, toast: true, position: 'top-end', showConfirmButton: false });
    });

    // Render Cart
    function renderCart() {
        let html = '';
        let totSub = 0, totDisc = 0, totTax = 0, totGrand = 0;

        if(cart.length) {
            cart.forEach((item, i) => {
                totSub += item.sub;
                totDisc += item.discVal;
                totTax += item.taxVal;
                totGrand += item.total;
                
                html += `
                    <tr>
                        <td class="ps-3">
                            <div class="fw-bold" style="color: var(--text-primary);">${item.name}</div>
                            <small style="color: var(--text-secondary); font-size: 0.75rem;">${item.qty} ${item.unitLabel || 'unit'}${item.qty === 1 ? '' : 's'} × ${fmt.format(item.price)}</small>
                            <small style="display:block; color: var(--text-tertiary); font-size: 0.7rem;">${item.priceLevelLabel || 'Retail / Default'} pricing</small>
                        </td>
                        <td class="text-center">
                            <input
                                type="number"
                                min="0.01"
                                step="0.01"
                                value="${item.qty}"
                                class="cart-qty-input"
                                onchange="updateCartQty(${i}, this.value)"
                                oninput="updateCartQty(${i}, this.value)"
                            >
                        </td>
                        <td class="text-end fw-bold tabular-nums" style="color: var(--text-primary);">${fmt.format(item.total)}</td>
                        <td class="text-center">
                            <div class="cart-actions">
                                <button class="btn btn-sm btn-cart-edit" onclick="editCartItem(${i})" title="Load item back into editor">
                                    <i class="fas fa-pen"></i>
                                </button>
                                <button class="btn btn-sm btn-remove" onclick="removeItem(${i})"><i class="fas fa-trash-alt"></i></button>
                            </div>
                        </td>
                    </tr>
                `;
            });
        }

        $('#cart-body').html(html);
        $('.cart-wrapper').toggleClass('has-items', cart.length > 0);
        $('#sum-subtotal').text(fmt.format(totSub));
        $('#sum-discount').text(totDisc > 0 ? '- ' + fmt.format(totDisc) : fmt.format(0));
        $('#sum-tax').text(totTax > 0 ? '+ ' + fmt.format(totTax) : fmt.format(0));
        $('#grand-total').text(fmt.format(totGrand));
        $('#hdr-cart-count').text(cart.length);
        
        $('#amount-paid').val(totGrand.toFixed(2));
        
        updateChange();
        $('.cart-wrapper').scrollTop($('.cart-wrapper')[0].scrollHeight);
    }

    window.removeItem = function(i) {
        cart.splice(i, 1);
        renderCart();
    };

    window.updateCartQty = function(i, value) {
        const nextQty = Math.max(0.01, parseFloat(value) || 0.01);
        if (!cart[i]) return;

        cart[i].qty = nextQty;
        calculateCartLine(cart[i]);
        renderCart();
    };

    window.editCartItem = function(i) {
        const item = cart[i];
        if (!item) return;

        $(`#unit-type-${item.unitType || 'unit'}`).prop('checked', true);
        $('#price-tier').val(item.priceLevel || 'retail');
        const option = $(`#product-select option[value="${item.id}"]`);
        $('#product-select').val(String(item.id));
        applyProductSelection(option);
        $('#quantity').val(item.qty);
        $('#discount-type').val(item.discountType || 'percent');
        $('#discount').val(item.discountValue ?? item.discount || 0);
        $('#discount-helper').text($('#discount-type').val() === 'fixed' ? 'Fixed amount off item subtotal' : 'Percent of item subtotal');
        $('#discount').attr('max', $('#discount-type').val() === 'fixed' ? '' : '100');
        $('#tax').val(item.tax || 0);
        $('#unit-price-input').val(item.price);
        calculate();
        $('#barcode-input').focus();
    };

    $(document).on('input', '#amount-paid, #transfer-amount, #card-amount', updateChange);
    $(document).on('change', '#payment-method', function() {
        const method = $('#payment-method').val();
        const isSplit = method === 'Split';
        $('#split-transfer-wrap').toggleClass('d-none', !isSplit);
        $('#split-transfer-account-wrap').toggleClass('d-none', !isSplit);
        $('#split-card-wrap').toggleClass('d-none', !isSplit);
        $('#split-card-account-wrap').toggleClass('d-none', !isSplit);
        updateChange();
    });

    function updateChange() {
        let total = parseFloat($('#grand-total').text().replace(/[^\d.]/g, '')) || 0;
        let cashPaid = parseFloat($('#amount-paid').val()) || 0;
        let transferPaid = parseFloat($('#transfer-amount').val()) || 0;
        let cardPaid = parseFloat($('#card-amount').val()) || 0;
        let method = $('#payment-method').val();
        let paid = method === 'Split' ? (cashPaid + transferPaid + cardPaid) : cashPaid;
        let change = paid - total;
        $('#change-amount').text(fmt.format(change)).css('color', change < 0 ? 'var(--danger-500)' : 'var(--success-500)');
    }

    function restoreProcessButton() {
        $('#process-btn').prop('disabled', false).removeClass('processing');
        $('#btn-text').show();
        $('#btn-loading').hide();
    }

    function resetPosWorkspace() {
        cart = [];
        lastSelectedProductId = null;

        $('#customer-select').val(null).trigger('change');
        $('#payment-method').val('Cash');
        $('#amount-paid').prop('readonly', false).val('0.00');
        $('#transfer-amount').val('0.00');
        $('#transfer-account').val('');
        $('#card-amount').val('0.00');
        $('#card-account').val('');
        $('#split-transfer-wrap').addClass('d-none');
        $('#split-transfer-account-wrap').addClass('d-none');
        $('#split-card-wrap').addClass('d-none');
        $('#split-card-account-wrap').addClass('d-none');

        $('#product-select').val('');
        applyProductSelection($('#product-select').find(':selected'));
        syncProductSearchValue(null);
        $('#quick-search').val('');
        $('#barcode-input').val('');
        $('#quantity').val(1);
        $('#discount, #tax').val(0);
        $('#discount-type').val('percent');
        $('#discount-helper').text('Percent of item subtotal');
        $('#discount').attr('max', '100');
        $('#price-tier').val('retail');
        $('#unit-type-unit').prop('checked', true).trigger('change');

        filterProductCards();
        renderCart();
        restoreProcessButton();

        setTimeout(() => $('#barcode-input').trigger('focus'), 50);
    }

    function submitPosSale(total, paid) {
        $('#process-btn').prop('disabled', true).addClass('processing');
        $('#btn-text').hide();
        $('#btn-loading').show();

        $.ajax({
            url: "{{ route('sales.store') }}",
            method: 'POST',
            data: {
                _token: "{{ csrf_token() }}",
                customer_id: $('#customer-select').val(),
                payment_method: $('#payment-method').val(),
                items: cart,
                subtotal: cart.reduce((s, i) => s + i.sub, 0),
                tax: cart.reduce((s, i) => s + i.taxVal, 0),
                discount: cart.reduce((s, i) => s + i.discVal, 0),
                total: total,
                paid: paid,
                split_details: {
                    cash: parseFloat($('#amount-paid').val()) || 0,
                    transfer: parseFloat($('#transfer-amount').val()) || 0,
                    transfer_account_id: $('#transfer-account').val() || null,
                    card: parseFloat($('#card-amount').val()) || 0,
                    card_account_id: $('#card-account').val() || null
                }
            },
            success: function(res) {
                const invoiceUrl = "{{ route('sales.invoice.show', ':id') }}".replace(':id', res.sale_id) + '?autoprint=1';
                const balanceDue = Math.max(0, total - paid);
                window.open(invoiceUrl, '_blank');

                resetPosWorkspace();

                showAlert({
                    icon: 'success',
                    title: balanceDue > 0 ? 'Deposit recorded' : 'Sale completed',
                    text: balanceDue > 0
                        ? 'Receipt opened. Remaining balance: ' + fmt.format(balanceDue)
                        : 'Receipt opened. POS is ready for the next sale.',
                    timer: 2000,
                    showConfirmButton: false,
                    toast: true,
                    position: 'top-end'
                });
            },
            error: function(xhr) {
                restoreProcessButton();
                showAlert({ icon: 'error', title: 'Error', text: xhr.responseJSON?.message || 'Failed', confirmButtonColor: '#ef4444' });
            }
        });
    }

    // Process Sale
    $('#process-btn').on('click', function(e) {
        e.preventDefault();
        e.stopPropagation();

        if(!cart.length) {
            showAlert({ icon: 'warning', title: 'Cart Empty', confirmButtonColor: '#f59e0b' });
            return;
        }

        let total = parseFloat($('#grand-total').text().replace(/[^\d.]/g, '')) || 0;
        let cashPaid = parseFloat($('#amount-paid').val()) || 0;
        let transferPaid = parseFloat($('#transfer-amount').val()) || 0;
        let method = $('#payment-method').val();
        let paid = method === 'Split' ? (cashPaid + transferPaid) : cashPaid;

        if(paid <= 0) {
            showAlert({ icon: 'warning', title: 'Enter Payment', text: 'Enter the amount received before processing this sale.', confirmButtonColor: '#f59e0b' });
            return;
        }

        submitPosSale(total, paid);
    });

    syncCategoryToggle();
    filterProductCards();
    setUnitTypeAvailability(null);
});
</script>

<script>
window.POS_ENABLE_FALLBACK = function () {
    if (window.POS_VANILLA_BOUND) return;
    window.POS_VANILLA_BOUND = true;

    const productCards = document.querySelectorAll('.product-card');
    const categoryPills = document.querySelectorAll('.category-pill');
    const categoryToggle = document.getElementById('category-toggle');
    const categoryPillsWrap = document.getElementById('category-pills');
    const quickSearch = document.getElementById('quick-search');
    const productSelect = document.getElementById('product-select');
    const productSearch = document.getElementById('product-search');
    const unitTypeInputs = document.querySelectorAll('input[name="unit_type"]');
    const priceTierInput = document.getElementById('price-tier');
    const priceInput = document.getElementById('unit-price-input');
    const qtyInput = document.getElementById('quantity');
    const discountInput = document.getElementById('discount');
    const discountTypeInput = document.getElementById('discount-type');
    const taxInput = document.getElementById('tax');
    const addBtn = document.getElementById('add-btn');
    const cartBody = document.getElementById('cart-body');
    const productImg = document.getElementById('product-img');
    const noImg = document.getElementById('no-img');
    const quickName = document.getElementById('quick-selected-name');
    const quickSku = document.getElementById('quick-selected-sku');
    const quickCategory = document.getElementById('quick-selected-category');
    const quickMinStock = document.getElementById('quick-selected-min-stock');
    const quickStock = document.getElementById('quick-selected-stock');
    const quickHealth = document.getElementById('quick-stock-health');
    const hdrSelected = document.getElementById('hdr-selected-product');
    const qtyLabel = document.getElementById('qty-label');
    const unitHelperCopy = document.getElementById('unit-helper-copy');
    const hdrShelfCount = document.getElementById('hdr-shelf-count');
    const productCount = document.getElementById('product-count');
    const sumSubtotal = document.getElementById('sum-subtotal');
    const sumDiscount = document.getElementById('sum-discount');
    const sumTax = document.getElementById('sum-tax');
    const grandTotal = document.getElementById('grand-total');
    const hdrCartCount = document.getElementById('hdr-cart-count');
    const amountPaid = document.getElementById('amount-paid');
    const paymentMethod = document.getElementById('payment-method');
    const transferAmount = document.getElementById('transfer-amount');
    const transferAccount = document.getElementById('transfer-account');
    const cardAmount = document.getElementById('card-amount');
    const cardAccount = document.getElementById('card-account');
    const splitTransferWrap = document.getElementById('split-transfer-wrap');
    const splitTransferAccountWrap = document.getElementById('split-transfer-account-wrap');
    const splitCardWrap = document.getElementById('split-card-wrap');
    const splitCardAccountWrap = document.getElementById('split-card-account-wrap');
    const changeAmount = document.getElementById('change-amount');
    const customerSelect = document.getElementById('customer-select');
    const processBtn = document.getElementById('process-btn');
    const btnText = document.getElementById('btn-text');
    const btnLoading = document.getElementById('btn-loading');
    const itemTotal = document.getElementById('item-total');
    const fmt = new Intl.NumberFormat('en-NG', { style: 'currency', currency: 'NGN' });
    const cart = [];
    let currentProductId = '';

    const alertFallback = (message) => window.alert(message);
    const saleStoreUrl = @json(route('sales.store'));
    const invoiceRouteTemplate = @json(route('sales.invoice.show', ':id'));
    const csrfToken = @json(csrf_token());
    let splitAutoSync = false;

    function getSelectedUnitType() {
        const active = document.querySelector('input[name="unit_type"]:checked');
        return active ? active.value : 'unit';
    }

    function getBasePrice(data) {
        const tier = priceTierInput?.value || 'retail';
        const retail = parseFloat(data.retail || data.price || '0') || 0;
        const wholesale = parseFloat(data.wholesale || '0') || 0;
        const special = parseFloat(data.special || '0') || 0;

        if (tier === 'wholesale' && wholesale > 0) {
            return { value: wholesale, key: 'wholesale', label: 'Wholesale' };
        }

        if (tier === 'special' && special > 0) {
            return { value: special, key: 'special', label: 'Special Discount' };
        }

        return { value: retail, key: 'retail', label: 'Retail / Default' };
    }

    function getUnitMetrics(data) {
        const type = getSelectedUnitType();
        const stock = parseFloat(data.stock || '0') || 0;
        const rollsPerCarton = Math.max(parseFloat(data.upc || '0') || 0, 0);
        const unitsPerRoll = Math.max(parseFloat(data.upr || '0') || 0, 0);
        const baseUnit = String(data.baseUnit || 'unit');
        const cartonUnits = rollsPerCarton > 0
            ? (unitsPerRoll > 0 ? (rollsPerCarton * unitsPerRoll) : rollsPerCarton)
            : 0;

        let multiplier = 1;
        let unitLabel = baseUnit;

        if (type === 'carton' && cartonUnits > 0) {
            multiplier = cartonUnits;
            unitLabel = 'carton';
        } else if (type === 'roll' && unitsPerRoll > 0) {
            multiplier = unitsPerRoll;
            unitLabel = 'roll';
        }

        const maxQty = multiplier > 0 ? stock / multiplier : stock;

        return {
            type,
            stock,
            multiplier,
            unitLabel,
            baseUnit,
            maxQty,
            cartonUnits,
            unitsPerRoll,
        };
    }

    function findOptionById(id) {
        if (!productSelect || !id) return null;
        return Array.from(productSelect.options).find((option) => option.value === String(id)) || null;
    }

    function optionData(option) {
        return option ? option.dataset || {} : {};
    }

    function applyVanillaSelection(source) {
        const data = source?.dataset || {};
        const productId = data.id || '';
        if (!productId) {
            if (priceInput) priceInput.value = '';
            if (productImg) productImg.style.display = 'none';
            if (noImg) noImg.style.display = 'block';
            if (quickName) quickName.textContent = 'None';
            if (quickSku) quickSku.textContent = '-';
            if (quickCategory) quickCategory.textContent = '-';
            if (quickMinStock) quickMinStock.textContent = '15';
            if (quickStock) quickStock.textContent = '0';
            if (quickHealth) { quickHealth.classList.remove('low'); quickHealth.classList.add('ok'); quickHealth.textContent = 'OK'; }
            if (hdrSelected) hdrSelected.textContent = 'None';
            currentProductId = '';
            return;
        }

        const stock = parseFloat(data.stock || '0') || 0;
        if (stock <= 0 || String(data.outOfStock || '0') === '1') {
            alertFallback(`${data.name || 'This product'} is out of stock and cannot be sold.`);
            if (productSelect) {
                productSelect.value = '';
            }
            if (productSearch) {
                productSearch.value = '';
            }
            currentProductId = '';
            return;
        }

        currentProductId = String(productId);
        if (productSelect) {
            productSelect.value = productId;
        }
        if (productSearch) productSearch.value = productId;

        const unitMeta = getUnitMetrics(data);
        const basePrice = getBasePrice(data);
        const computedPrice = basePrice.value * unitMeta.multiplier;
        if (priceInput) priceInput.value = computedPrice.toFixed(2);
        if (qtyInput) {
            const currentQty = parseFloat(qtyInput.value || '0') || 0;
            if (currentQty <= 0 || currentQty > Math.max(unitMeta.maxQty, 0.01)) {
                qtyInput.value = unitMeta.maxQty >= 1 ? '1' : String(Math.max(unitMeta.maxQty, 0.01));
            }
        }
        if (qtyLabel) {
            qtyLabel.textContent = `Quantity (${unitMeta.maxQty > 0 ? unitMeta.maxQty.toFixed(2).replace(/\.00$/, '') : '0'} ${unitMeta.unitLabel}${unitMeta.maxQty === 1 ? '' : 's'} available)`;
        }
        if (unitHelperCopy) {
            unitHelperCopy.textContent = unitMeta.type === 'unit'
                ? `Selling in single ${unitMeta.baseUnit}${unitMeta.baseUnit.endsWith('s') ? '' : 's'} using the ${basePrice.label.toLowerCase()} price.`
                : `Each ${unitMeta.type} uses ${unitMeta.multiplier} unit(s) and the ${basePrice.label.toLowerCase()} price.`;
        }

        if (productImg && data.img) {
            productImg.src = data.img;
            productImg.style.display = 'block';
            if (noImg) noImg.style.display = 'none';
        } else {
            if (productImg) productImg.style.display = 'none';
            if (noImg) noImg.style.display = 'block';
        }

        if (quickName) quickName.textContent = data.name || 'Product';
        if (quickSku) quickSku.textContent = data.sku || '-';
        if (quickCategory) quickCategory.textContent = data.categoryName || 'Uncategorized';
        if (quickMinStock) quickMinStock.textContent = data.minStock || '15';
        if (quickStock) quickStock.textContent = data.stock || '0';

        const stock = parseFloat(data.stock || '0') || 0;
        const minStock = parseFloat(data.minStock || '15') || 15;
        if (quickHealth) {
            if (stock <= minStock) {
                quickHealth.classList.remove('ok');
                quickHealth.classList.add('low');
                quickHealth.textContent = 'LOW';
            } else {
                quickHealth.classList.remove('low');
                quickHealth.classList.add('ok');
                quickHealth.textContent = 'OK';
            }
        }
        if (hdrSelected) hdrSelected.textContent = data.name || 'Product';
        productCards.forEach((card) => card.classList.toggle('active', card.dataset.id === String(productId)));
        updateItemTotal();
    }

    productCards.forEach((card) => {
        card.addEventListener('click', function () {
            applyVanillaSelection(card);
        });
    });

    if (productSearch) {
        productSearch.addEventListener('change', function () {
            const option = findOptionById(productSearch.value);
            if (!option) return;
            applyVanillaSelection({ dataset: {
                ...option.dataset,
                id: option.value,
            }});
        });
    }

    if (productSelect) {
        productSelect.addEventListener('change', function () {
            const option = productSelect.options[productSelect.selectedIndex];
            if (!option) return;
            applyVanillaSelection({ dataset: { ...option.dataset, id: option.value }});
        });
    }

    function updateItemTotal() {
        const price = parseFloat(priceInput?.value || '0') || 0;
        const qty = parseFloat(qtyInput?.value || '1') || 1;
        const discount = parseFloat(discountInput?.value || '0') || 0;
        const discountType = discountTypeInput?.value || 'percent';
        const tax = parseFloat(taxInput?.value || '0') || 0;
        const sub = price * qty;
        const discVal = discountType === 'fixed' ? Math.min(discount, sub) : (sub * (discount / 100));
        const afterDisc = sub - discVal;
        const taxVal = afterDisc * (tax / 100);
        const total = afterDisc + taxVal;
        if (itemTotal) itemTotal.textContent = fmt.format(total);
        return { sub, discVal, taxVal, total, discountType, discount };
    }

    function updateChange() {
        const totalText = grandTotal?.textContent || '0';
        const total = parseFloat(totalText.replace(/[^\d.]/g, '')) || 0;
        const cashPaid = parseFloat(amountPaid?.value || '0') || 0;
        const transferPaid = parseFloat(transferAmount?.value || '0') || 0;
        const cardPaid = parseFloat(cardAmount?.value || '0') || 0;
        const isSplit = paymentMethod?.value === 'Split';
        const paid = isSplit ? (cashPaid + transferPaid + cardPaid) : cashPaid;
        const change = paid - total;

        if (changeAmount) {
            changeAmount.textContent = fmt.format(change);
            changeAmount.style.color = change < 0 ? 'var(--danger-500)' : 'var(--success-500)';
        }

        return { total, paid, change };
    }

    function syncSplitCounterpart(changedField) {
        if (splitAutoSync || paymentMethod?.value !== 'Split') {
            return;
        }

        const totalText = grandTotal?.textContent || '0';
        const total = parseFloat(totalText.replace(/[^\d.]/g, '')) || 0;
        const cashPaid = parseFloat(amountPaid?.value || '0') || 0;
        const transferPaid = parseFloat(transferAmount?.value || '0') || 0;
        const cardPaid = parseFloat(cardAmount?.value || '0') || 0;

        splitAutoSync = true;
        const remainingBase = Math.max(0, total - cashPaid);

        if (changedField === 'transfer' && cardAmount) {
            cardAmount.value = Math.max(0, remainingBase - transferPaid).toFixed(2);
        }

        if (changedField === 'card' && transferAmount) {
            transferAmount.value = Math.max(0, remainingBase - cardPaid).toFixed(2);
        }

        splitAutoSync = false;
        updateChange();
    }

    function toggleSplitFields() {
        const isSplit = paymentMethod?.value === 'Split';
        splitTransferWrap?.classList.toggle('d-none', !isSplit);
        splitTransferAccountWrap?.classList.toggle('d-none', !isSplit);
        splitCardWrap?.classList.toggle('d-none', !isSplit);
        splitCardAccountWrap?.classList.toggle('d-none', !isSplit);

        if (isSplit && amountPaid) {
            const currentCash = parseFloat(amountPaid.value || '0') || 0;
            const totalText = grandTotal?.textContent || '0';
            const total = parseFloat(totalText.replace(/[^\d.]/g, '')) || 0;
            if (Math.abs(currentCash - total) < 0.01) {
                amountPaid.value = '0.00';
            }
            if (transferAmount && !transferAmount.value) {
                transferAmount.value = '0.00';
            }
            if (cardAmount && !cardAmount.value) {
                cardAmount.value = total.toFixed(2);
            }
        }

        updateChange();
    }

    function renderCart() {
        if (!cartBody) return;
        let html = '';
        let totSub = 0;
        let totDisc = 0;
        let totTax = 0;
        let totGrand = 0;

        cart.forEach((item, i) => {
            totSub += item.sub;
            totDisc += item.discVal;
            totTax += item.taxVal;
            totGrand += item.total;

            html += `
                <tr>
                    <td class="ps-3">
                        <div class="fw-bold" style="color: var(--text-primary);">${item.name}</div>
                        <small style="color: var(--text-secondary); font-size: 0.75rem;">${item.qty} ${item.unitLabel || 'unit'} × ${fmt.format(item.price)}</small>
                    </td>
                    <td class="text-center">
                        <input type="number" min="0.01" step="0.01" value="${item.qty}" class="cart-qty-input" data-index="${i}">
                    </td>
                    <td class="text-end fw-bold tabular-nums" style="color: var(--text-primary);">${fmt.format(item.total)}</td>
                    <td class="text-center">
                        <button class="btn btn-sm btn-remove" data-remove="${i}"><i class="fas fa-trash-alt"></i></button>
                    </td>
                </tr>
            `;
        });

        cartBody.innerHTML = html;
        if (sumSubtotal) sumSubtotal.textContent = fmt.format(totSub);
        if (sumDiscount) sumDiscount.textContent = totDisc > 0 ? '- ' + fmt.format(totDisc) : fmt.format(0);
        if (sumTax) sumTax.textContent = totTax > 0 ? '+ ' + fmt.format(totTax) : fmt.format(0);
        if (grandTotal) grandTotal.textContent = fmt.format(totGrand);
        if (hdrCartCount) hdrCartCount.textContent = String(cart.length);
        if (paymentMethod?.value === 'Split') {
            if (amountPaid && !amountPaid.value) {
                amountPaid.value = '0.00';
            }
        } else if (amountPaid) {
            amountPaid.value = totGrand.toFixed(2);
        }
        updateChange();
    }

    if (cartBody) {
        cartBody.addEventListener('input', function (e) {
            const target = e.target;
            if (!target || !target.classList.contains('cart-qty-input')) return;
            const index = parseInt(target.getAttribute('data-index') || '0', 10);
            if (Number.isNaN(index) || !cart[index]) return;
            const qty = parseFloat(target.value || '1') || 1;
            cart[index].qty = qty;
            const line = updateItemTotal();
            const price = cart[index].price;
            const discount = cart[index].discountValue ?? cart[index].discount ?? 0;
            const discountType = cart[index].discountType || 'percent';
            const tax = cart[index].tax || 0;
            const sub = price * qty;
            const discVal = discountType === 'fixed' ? Math.min(discount, sub) : (sub * (discount / 100));
            const afterDisc = sub - discVal;
            const taxVal = afterDisc * (tax / 100);
            cart[index].sub = sub;
            cart[index].discVal = discVal;
            cart[index].taxVal = taxVal;
            cart[index].total = afterDisc + taxVal;
            renderCart();
        });
        cartBody.addEventListener('click', function (e) {
            const target = e.target.closest('button[data-remove]');
            if (!target) return;
            const index = parseInt(target.getAttribute('data-remove') || '0', 10);
            if (Number.isNaN(index)) return;
            cart.splice(index, 1);
            renderCart();
        });
    }

    if (addBtn) {
        addBtn.addEventListener('click', function (e) {
            e.preventDefault();
            const option = productSelect?.options[productSelect.selectedIndex];
            if (!option || !option.value) {
                alertFallback('Select a product');
                return;
            }
            const data = option.dataset || {};
            const unitMeta = getUnitMetrics(data);
            const priceMeta = getBasePrice(data);
            const price = parseFloat(priceInput?.value || (priceMeta.value * unitMeta.multiplier) || '0') || 0;
            const qty = parseFloat(qtyInput?.value || '1') || 1;
            if (unitMeta.maxQty <= 0) {
                alertFallback('No stock is available for this product.');
                return;
            }
            if (qty > unitMeta.maxQty) {
                alertFallback(`Only ${unitMeta.maxQty.toFixed(2).replace(/\.00$/, '')} ${unitMeta.unitLabel}${unitMeta.maxQty === 1 ? '' : 's'} available.`);
                return;
            }
            if (price <= 0) {
                alertFallback('Price is required for this product.');
                return;
            }
            const calc = updateItemTotal();
            cart.push({
                id: option.value,
                name: data.name || option.textContent || 'Product',
                qty,
                unitType: unitMeta.type,
                unitLabel: unitMeta.unitLabel,
                stockUnits: unitMeta.multiplier * qty,
                priceLevel: priceMeta.key,
                priceLevelLabel: priceMeta.label,
                price,
                discountType: calc.discountType,
                discountValue: calc.discount,
                tax: parseFloat(taxInput?.value || '0') || 0,
                sub: calc.sub,
                discVal: calc.discVal,
                taxVal: calc.taxVal,
                total: calc.total
            });
            renderCart();
            if (productSelect) productSelect.value = '';
            if (productSearch) productSearch.value = '';
            currentProductId = '';
            if (qtyInput) qtyInput.value = '1';
            if (discountInput) discountInput.value = '0';
            if (taxInput) taxInput.value = '0';
            if (discountTypeInput) discountTypeInput.value = 'percent';
            if (priceTierInput) priceTierInput.value = 'retail';
            applyVanillaSelection({ dataset: {} });
        });
    }

    function filterProductCards() {
        const keyword = (quickSearch?.value || '').toLowerCase().trim();
        const activeCategory = document.querySelector('.category-pill.active')?.dataset.category || 'all';
        let visible = 0;

        productCards.forEach((card) => {
            const name = (card.dataset.searchName || card.dataset.name || '').toLowerCase();
            const sku = (card.dataset.sku || '').toLowerCase();
            const category = (card.dataset.category || '').toLowerCase();
            const matchesKeyword = !keyword || name.includes(keyword) || sku.includes(keyword) || category.includes(keyword);
            const matchesCategory = activeCategory === 'all' || category === activeCategory;
            const show = matchesKeyword && matchesCategory;
            card.style.display = show ? '' : 'none';
            if (show) visible += 1;
        });

        if (hdrShelfCount) hdrShelfCount.textContent = String(visible);
        if (productCount) productCount.textContent = `${visible} item(s)`;
    }

    categoryPills.forEach((pill) => {
        pill.addEventListener('click', function () {
            categoryPills.forEach((node) => node.classList.remove('active'));
            pill.classList.add('active');
            filterProductCards();
        });
    });

    quickSearch?.addEventListener('input', filterProductCards);
    categoryToggle?.addEventListener('click', function () {
        categoryPillsWrap?.classList.toggle('collapsed');
        categoryToggle.textContent = categoryPillsWrap?.classList.contains('collapsed') ? 'Show More' : 'Show Less';
    });

    unitTypeInputs.forEach((input) => {
        input.addEventListener('change', function () {
            const option = findOptionById(currentProductId);
            if (option) {
                applyVanillaSelection({ dataset: { ...option.dataset, id: option.value }});
            } else {
                updateItemTotal();
            }
        });
    });

    priceTierInput?.addEventListener('change', function () {
        const option = findOptionById(currentProductId);
        if (option) {
            applyVanillaSelection({ dataset: { ...option.dataset, id: option.value }});
        }
    });

    [qtyInput, discountInput, taxInput].forEach((input) => {
        input?.addEventListener('input', updateItemTotal);
    });
    discountTypeInput?.addEventListener('change', updateItemTotal);
    amountPaid?.addEventListener('input', function () {
        if (paymentMethod?.value === 'Split') {
            const lastEdited = document.activeElement === transferAmount ? 'transfer' : (document.activeElement === cardAmount ? 'card' : null);
            if (lastEdited) {
                syncSplitCounterpart(lastEdited);
                return;
            }
        }
        updateChange();
    });
    transferAmount?.addEventListener('input', function () {
        syncSplitCounterpart('transfer');
    });
    cardAmount?.addEventListener('input', function () {
        syncSplitCounterpart('card');
    });
    paymentMethod?.addEventListener('change', toggleSplitFields);

    processBtn?.addEventListener('click', async function (e) {
        e.preventDefault();
        let printWindow = null;

        if (!cart.length) {
            alertFallback('Cart is empty.');
            return;
        }

        const { total, paid } = updateChange();
        if (paid <= 0) {
            alertFallback('Enter payment before processing the sale.');
            return;
        }

        if (paymentMethod?.value === 'Split') {
            const transferValue = parseFloat(transferAmount?.value || '0') || 0;
            const cardValue = parseFloat(cardAmount?.value || '0') || 0;
            if (transferValue > 0 && !transferAccount?.value) {
                alertFallback('Choose the transfer account.');
                return;
            }
            if (cardValue > 0 && !cardAccount?.value) {
                alertFallback('Choose the POS account.');
                return;
            }
        }

        printWindow = window.open('', '_blank');
        if (printWindow) {
            printWindow.document.write('<!doctype html><html><head><title>Preparing receipt...</title></head><body style="font-family: Arial, sans-serif; padding: 24px;">Preparing receipt...</body></html>');
            printWindow.document.close();
        }

        processBtn.disabled = true;
        processBtn.classList.add('processing');
        if (btnText) btnText.style.display = 'none';
        if (btnLoading) btnLoading.style.display = '';

        try {
            const response = await fetch(saleStoreUrl, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': csrfToken,
                    'X-Requested-With': 'XMLHttpRequest',
                },
                body: JSON.stringify({
                    customer_id: customerSelect?.value || null,
                    payment_method: paymentMethod?.value || 'Cash',
                    items: cart,
                    subtotal: cart.reduce((sum, item) => sum + item.sub, 0),
                    tax: cart.reduce((sum, item) => sum + item.taxVal, 0),
                    discount: cart.reduce((sum, item) => sum + item.discVal, 0),
                    total,
                    paid,
                    split_details: {
                        cash: parseFloat(amountPaid?.value || '0') || 0,
                        transfer: parseFloat(transferAmount?.value || '0') || 0,
                        transfer_account_id: transferAccount?.value || null,
                        card: parseFloat(cardAmount?.value || '0') || 0,
                        card_account_id: cardAccount?.value || null,
                    },
                }),
            });

            const result = await response.json().catch(() => ({}));
            if (!response.ok) {
                throw new Error(result.message || 'Failed to process sale.');
            }

            if (result.sale_id) {
                const invoiceUrl = invoiceRouteTemplate.replace(':id', result.sale_id) + '?autoprint=1';
                if (printWindow && !printWindow.closed) {
                    printWindow.location.href = invoiceUrl;
                } else {
                    window.open(invoiceUrl, '_blank');
                }
            }

            cart.length = 0;
            renderCart();
            if (customerSelect) customerSelect.value = '';
            if (paymentMethod) paymentMethod.value = 'Cash';
            if (amountPaid) amountPaid.value = '0.00';
            if (transferAmount) transferAmount.value = '0.00';
            if (cardAmount) cardAmount.value = '0.00';
            if (transferAccount) transferAccount.value = '';
            if (cardAccount) cardAccount.value = '';
            toggleSplitFields();
            if (productSelect) productSelect.value = '';
            if (productSearch) productSearch.value = '';
            applyVanillaSelection({ dataset: {} });
        } catch (error) {
            if (printWindow && !printWindow.closed) {
                printWindow.close();
            }
            alertFallback(error.message || 'Failed to process sale.');
        } finally {
            processBtn.disabled = false;
            processBtn.classList.remove('processing');
            if (btnText) btnText.style.display = '';
            if (btnLoading) btnLoading.style.display = 'none';
        }
    });

    filterProductCards();
    toggleSplitFields();
    updateItemTotal();
};

document.addEventListener('DOMContentLoaded', function () {
    window.POS_ENABLE_FALLBACK();
});
</script>
@endsection
