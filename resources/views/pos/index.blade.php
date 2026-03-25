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

/* Split Payment */
.split-box {
    background: linear-gradient(135deg, #eff6ff 0%, #dbeafe 100%);
    border: 2px dashed var(--primary-600);
    border-radius: var(--radius-md);
    padding: 16px;
}

.split-input {
    text-align: center;
    font-weight: 700;
    font-size: 0.9375rem;
    border: 2px solid var(--border);
    font-variant-numeric: tabular-nums;
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
            <div class="product-card"
                title="{{ $p->name }}"
                data-id="{{ $p->id }}"
                data-name="{{ strtolower($p->name) }}"
                data-sku="{{ strtolower($p->sku ?? '') }}"
                data-barcode="{{ strtolower($p->barcode ?? '') }}"
                data-category="{{ strtolower($p->category->name ?? 'uncategorized') }}">
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
                        <input type="number" id="quantity" class="form-control fw-bold tabular-nums" value="1" min="1">
                    </div>
                    <div class="col-6">
                        <label style="color: var(--danger-500);">Discount %</label>
                        <input type="number" id="discount" class="form-control tabular-nums" value="0" min="0" max="100">
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

                <button id="add-btn" class="btn btn-add-cart w-100 py-2 mt-2">
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
                                <option value="Card">Card</option>
                                <option value="Transfer">Transfer</option>
                                <option value="Split">Split Payment</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label>Amount Paid</label>
                            <input type="number" id="amount-paid" class="form-control form-control-lg fw-bold text-end tabular-nums" style="font-size: 1rem; color: var(--success-500);">
                        </div>
                        <div class="col-md-12" id="payment-channel-wrap">
                            <label>Payment Channel</label>
                            <select id="payment-channel" class="form-select">
                                <option value="">Auto / Not specified</option>
                                @foreach(($bankAccounts ?? []) as $account)
                                    <option value="{{ $account->id }}">{{ $account->name }}{{ $account->account_number ? ' - ' . $account->account_number : '' }}</option>
                                @endforeach
                            </select>
                            <small class="text-muted">Pick the bank, POS terminal, wallet, or channel that received the payment.</small>
                        </div>
                    </div>

                    <!-- Split -->
                    <div id="split-box" class="split-box mt-3" style="display:none;">
                        <div class="row g-3">
                            <div class="col-4">
                                <label>Cash</label>
                                <input type="number" id="split-cash" class="form-control split-input" value="0">
                            </div>
                            <div class="col-4">
                                <label>Card</label>
                                <input type="number" id="split-card" class="form-control split-input" value="0">
                            </div>
                            <div class="col-4">
                                <label>Transfer</label>
                                <input type="number" id="split-transfer" class="form-control split-input" value="0">
                            </div>
                            <div class="col-md-6">
                                <label>Card Channel</label>
                                <select id="split-card-account" class="form-select">
                                    <option value="">Select card channel</option>
                                    @foreach(($bankAccounts ?? []) as $account)
                                        <option value="{{ $account->id }}">{{ $account->name }}{{ $account->account_number ? ' - ' . $account->account_number : '' }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label>Transfer Channel</label>
                                <select id="split-transfer-account" class="form-select">
                                    <option value="">Select transfer channel</option>
                                    @foreach(($bankAccounts ?? []) as $account)
                                        <option value="{{ $account->id }}">{{ $account->name }}{{ $account->account_number ? ' - ' . $account->account_number : '' }}</option>
                                    @endforeach
                                </select>
                            </div>
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

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>

<script>
$(document).ready(function() {
    let cart = [];
    let lastSelectedProductId = null;
    const fmt = new Intl.NumberFormat('en-NG', { style: 'currency', currency: 'NGN' });

    // Clock
    setInterval(() => $('#live-clock').text(new Date().toLocaleTimeString('en-US', { hour12: false })), 1000);

    // Select2
    $('#customer-select').select2({ width: '100%' });
    $('#product-search').select2({
        width: '100%',
        placeholder: 'Search product name...',
        allowClear: true
    });
    $(document).on('keydown', function(e) {
        if ((e.ctrlKey || e.metaKey) && e.key.toLowerCase() === 'k') {
            e.preventDefault();
            $('#quick-search').trigger('focus');
        }
    });

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
            const name = card.data('name') || '';
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
        const productId = $(this).data('id');
        $('#product-select').val(String(productId)).trigger('change');
    });

    $('#product-search').on('change', function() {
        const productId = $(this).val();
        if (!productId) return;
        $('#product-select').val(String(productId)).trigger('change');
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
            $('#product-select').val(matchedOption.val()).trigger('change');
            $('#barcode-input').val('');
            barcodeBuffer = '';
            return true;
        }

        Swal.fire({
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

        let sub = price * qty;
        let discVal = sub * (disc / 100);
        let afterDisc = sub - discVal;
        let taxVal = afterDisc * (tax / 100);
        let total = afterDisc + taxVal;

        $('#item-total').text(fmt.format(total));
        return { sub, discVal, taxVal, total };
    }

    function calculateCartLine(item) {
        const qty = parseFloat(item.qty) || 1;
        const price = parseFloat(item.price) || 0;
        const discount = parseFloat(item.discount) || 0;
        const tax = parseFloat(item.tax) || 0;

        const sub = price * qty;
        const discVal = sub * (discount / 100);
        const afterDisc = sub - discVal;
        const taxVal = afterDisc * (tax / 100);
        const total = afterDisc + taxVal;

        item.sub = sub;
        item.discVal = discVal;
        item.taxVal = taxVal;
        item.total = total;

        return item;
    }

    // Product Change
    $('#product-select').on('change', function() {
        let opt = $(this).find(':selected');
        if(!opt.val()) {
            $('#unit-price-input').val('');
            $('#qty-label').text('Quantity');
            $('#unit-helper-copy').text('Select a product to unlock the right unit packs and live pricing.');
            $('#product-img').hide();
            $('#no-img').show();
            $('#product-search').val(null).trigger('change.select2');
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
        $('#quantity').attr('max', Math.max(unitMeta.maxQty, 1));
        if ((parseFloat($('#quantity').val()) || 1) > Math.max(unitMeta.maxQty, 1)) {
            $('#quantity').val(Math.max(unitMeta.maxQty, 1));
        }
        $('#unit-helper-copy').text(
            unitMeta.type === 'unit'
                ? `Selling in single ${unitMeta.baseUnit}${unitMeta.baseUnit.endsWith('s') ? '' : 's'} using the ${basePrice.label.toLowerCase()} price.`
                : `Each ${unitMeta.type} uses ${unitMeta.multiplier} unit(s) and the ${basePrice.label.toLowerCase()} price. Available: ${unitMeta.maxQty} ${unitMeta.unitName}.`
        );
        $('#product-search').val(String(opt.val())).trigger('change.select2');
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
        syncActiveProductCard(opt.val());

        if(opt.data('img')) {
            $('#product-img').attr('src', opt.data('img')).show();
            $('#no-img').hide();
        } else {
            $('#product-img').hide();
            $('#no-img').show();
        }
        
        calculate();
    });

    $('input[name="unit_type"]').on('change', () => $('#product-select').trigger('change'));
    $('#price-tier').on('change', () => $('#product-select').trigger('change'));
    $(document).on('input', '#quantity, #discount, #tax', calculate);

    // Add to Cart
    $('#add-btn').on('click', function() {
        let opt = $('#product-select').find(':selected');
        if(!opt.val()) {
            Swal.fire({ icon: 'warning', title: 'No Product', text: 'Select a product', confirmButtonColor: '#2563eb' });
            return;
        }

        let qty = parseFloat($('#quantity').val()) || 1;
        const unitMeta = resolveUnitMetrics(opt);
        let stock = opt.data('stock');
        const maxAllowed = Math.max(unitMeta.maxQty, 0);
        
        if(maxAllowed <= 0) {
            Swal.fire({ icon: 'error', title: 'Unavailable', text: `No ${unitMeta.unitName} available for this product`, confirmButtonColor: '#ef4444' });
            return;
        }

        if(qty > maxAllowed) {
            Swal.fire({ icon: 'error', title: 'Low Stock', text: `Only ${maxAllowed} ${unitMeta.unitName} available`, confirmButtonColor: '#ef4444' });
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
            discount: parseFloat($('#discount').val()) || 0,
            tax: parseFloat($('#tax').val()) || 0,
            sub: res.sub,
            discVal: res.discVal,
            taxVal: res.taxVal,
            total: res.total
        });

        renderCart();
        $('#product-select').val('').trigger('change');
        $('#quantity').val(1);
        $('#discount, #tax').val(0);
        $('#price-tier').val('retail');
        $('#barcode-input').val('').focus();
        
        Swal.fire({ icon: 'success', title: 'Added', timer: 1000, toast: true, position: 'top-end', showConfirmButton: false });
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
                                min="1"
                                step="1"
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
        
        if($('#payment-method').val() !== 'Split') {
            $('#amount-paid').val(totGrand.toFixed(2));
        }
        
        updateChange();
        $('.cart-wrapper').scrollTop($('.cart-wrapper')[0].scrollHeight);
    }

    window.removeItem = function(i) {
        cart.splice(i, 1);
        renderCart();
    };

    window.updateCartQty = function(i, value) {
        const nextQty = Math.max(1, parseFloat(value) || 1);
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
        $('#product-select').val(String(item.id)).trigger('change');
        $('#quantity').val(item.qty);
        $('#discount').val(item.discount || 0);
        $('#tax').val(item.tax || 0);
        $('#unit-price-input').val(item.price);
        calculate();
        $('#barcode-input').focus();
    };

    // Payment
    $('#payment-method').on('change', function() {
        let total = parseFloat($('#grand-total').text().replace(/[^\d.]/g, '')) || 0;
        
        if($(this).val() === 'Split') {
            $('#split-box').slideDown(200);
            $('#payment-channel-wrap').slideUp(150);
            $('#amount-paid').prop('readonly', true).val(total.toFixed(2));
            $('#split-cash').val(total.toFixed(2));
            $('#split-card, #split-transfer').val(0);
        } else {
            $('#split-box').slideUp(200);
            $('#payment-channel-wrap').slideDown(150);
            $('#amount-paid').prop('readonly', false).val(total.toFixed(2));
        }
        
        updateChange();
    });

    $(document).on('input', '#amount-paid, .split-input', updateChange);

    function updateChange() {
        let total = parseFloat($('#grand-total').text().replace(/[^\d.]/g, '')) || 0;
        
        if($('#payment-method').val() === 'Split') {
            let sum = (parseFloat($('#split-cash').val()) || 0) + (parseFloat($('#split-card').val()) || 0) + (parseFloat($('#split-transfer').val()) || 0);
            $('#amount-paid').val(sum.toFixed(2));
        }
        
        let paid = parseFloat($('#amount-paid').val()) || 0;
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
        $('#payment-method').val('Cash').trigger('change');
        $('#payment-channel').val('');
        $('#split-card-account, #split-transfer-account').val('');
        $('#split-cash, #split-card, #split-transfer').val(0);
        $('#split-box').hide();
        $('#amount-paid').prop('readonly', false).val('0.00');

        $('#product-select').val('').trigger('change');
        $('#product-search').val(null).trigger('change.select2');
        $('#quick-search').val('');
        $('#barcode-input').val('');
        $('#quantity').val(1);
        $('#discount, #tax').val(0);
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
                payment_account_id: $('#payment-channel').val() || '',
                split_details: {
                    cash: $('#split-cash').val() || 0,
                    pos: $('#split-card').val() || 0,
                    bank: $('#split-transfer').val() || 0,
                    card_account_id: $('#split-card-account').val() || '',
                    transfer_account_id: $('#split-transfer-account').val() || ''
                }
            },
            success: function(res) {
                const invoiceUrl = "{{ route('sales.invoice.show', ':id') }}".replace(':id', res.sale_id) + '?autoprint=1';
                const balanceDue = Math.max(0, total - paid);
                window.open(invoiceUrl, '_blank');

                resetPosWorkspace();

                Swal.fire({
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
                Swal.fire({ icon: 'error', title: 'Error', text: xhr.responseJSON?.message || 'Failed', confirmButtonColor: '#ef4444' });
            }
        });
    }

    // Process Sale
    $('#process-btn').on('click', function(e) {
        e.preventDefault();
        e.stopPropagation();

        if(!cart.length) {
            Swal.fire({ icon: 'warning', title: 'Cart Empty', confirmButtonColor: '#f59e0b' });
            return;
        }

        let total = parseFloat($('#grand-total').text().replace(/[^\d.]/g, '')) || 0;
        let paid = parseFloat($('#amount-paid').val()) || 0;

        if(paid <= 0) {
            Swal.fire({ icon: 'warning', title: 'Enter Payment', text: 'Enter the amount received before processing this sale.', confirmButtonColor: '#f59e0b' });
            return;
        }

        if (paid < total) {
            const balanceDue = Math.max(0, total - paid);

            Swal.fire({
                icon: 'warning',
                title: 'Process As Deposit?',
                text: 'Amount received is short by ' + fmt.format(balanceDue) + '. This sale will be saved as pending until fully paid.',
                showCancelButton: true,
                confirmButtonText: 'Yes, Save Deposit',
                cancelButtonText: 'Cancel',
                confirmButtonColor: '#2563eb',
                cancelButtonColor: '#9ca3af'
            }).then((result) => {
                if (result.isConfirmed) {
                    submitPosSale(total, paid);
                }
            });
            return;
        }

        submitPosSale(total, paid);
    });

    syncCategoryToggle();
    filterProductCards();
    setUnitTypeAvailability(null);
});
</script>
@endsection
