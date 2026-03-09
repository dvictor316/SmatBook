@php
    use Illuminate\Support\Facades\Route;
    $route = Route::currentRouteName();
    $siteTitle = \App\Models\Setting::where('key', 'company_name')->value('value') ?: 'SmartProbook';
    $faviconPath = \App\Models\Setting::where('key', 'favicon')->value('value');

    // Initialize visibility variables to prevent "undefined" errors
    $hideNavbar = $hideNavbar ?? false;
    $hideSidebar = $hideSidebar ?? false;
@endphp

<!DOCTYPE html>

@if (!Route::is(['index-two', 'landing.index']))
    <html lang="en" data-layout="vertical" data-topbar="light" data-sidebar="light" data-sidebar-size="lg" data-sidebar-image="none">
@else
    <html lang="en">
@endif

<head>
    @livewireStyles

    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    @php
        $seoNoIndex = $seoNoIndex ?? true;
        $seoTitle = $seoTitle ?? ($siteTitle . ' Dashboard');
    @endphp
    @include('layout.partials.seo-meta')
    <meta name="author" content="Dreamguys - Bootstrap Admin Template">

    {{-- CRITICAL: Prevent theme flickering on load --}}
    <script>
        (function() {
            const savedSettings = JSON.parse(localStorage.getItem('themeSettings')) || {};
            Object.keys(savedSettings).forEach(key => {
                document.documentElement.setAttribute(key, savedSettings[key]);
            });
        })();
    </script>

    <link rel="shortcut icon" href="{{ asset('assets/img/logos.png') }}">

    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@fortawesome/fontawesome-free@6.5.0/css/all.min.css" 
          integrity="sha512-Avb2QiuDEEvB4bZJYdft2mNjVShBftLdPG8FJ0V7irTLQ8Uo0qcPxh4Plq7G5tGm0rU+1SPhVotteLpBERwTkw==" 
          crossorigin="anonymous" referrerpolicy="no-referrer" />

    @include('layout.partials.head')
    @include('layout.partials.design-system')

    {{-- GLOBAL PRINT STYLES --}}
    <style>
        {{-- FIX: Automatically remove sidebar margin if sidebar is hidden --}}
        @if($hideSidebar)
        .page-wrapper, .main-wrapper {
            margin-left: 0 !important;
            padding-left: 0 !important;
            width: 100% !important;
        }
        @endif

        @media print {
            .header, .sidebar, .settings-icon, .btn, .footer, .theme-settings-offcanvas, .sidebar-settings {
                display: none !important;
            }
            .main-wrapper {
                margin: 0 !important;
                padding: 0 !important;
                width: 100% !important;
            }
            body {
                background: #fff !important;
            }
            .page-wrapper {
                margin: 0 !important;
                padding: 0 !important;
            }
        }

        /* NON-OBSTRUCTIVE LAYOUT: Let each view handle its own spacing */
        .main-wrapper {
            position: relative;
            min-height: 100vh;
        }

        /* Header should be positioned properly without blocking content */
        .header {
            position: sticky;
            top: 0;
            z-index: 1000;
            width: 100%;
        }

        /* Sidebar positioning handled by theme, not layout */
        .sidebar {
            position: fixed;
            top: 0;
            left: 0;
            height: 100vh;
            z-index: 999;
        }

        /* Default content area - views can override this */
        .page-wrapper {
            position: relative;
            margin-left: var(--sb-sidebar-w, 270px) !important;
            padding-top: 0 !important;
            margin-top: 0 !important;
            min-height: calc(100vh - var(--sb-header-h, 76px));
            transition: margin-left 0.3s ease;
        }

        body.sidebar-collapsed .page-wrapper,
        body.mini-sidebar .page-wrapper,
        body.sidebar-icon-only .page-wrapper {
            margin-left: var(--sb-sidebar-collapsed, 80px) !important;
        }

        .page-wrapper .content.container-fluid {
            padding-top: 0 !important;
            padding-left: 12px !important;
            padding-right: 12px !important;
        }

        .btn {
            border-radius: 999px !important;
            font-weight: 700 !important;
            letter-spacing: 0.01em;
            box-shadow: 0 8px 22px rgba(15, 23, 42, 0.08);
            transition: transform 0.2s ease, box-shadow 0.2s ease, filter 0.2s ease !important;
        }

        .btn:hover {
            transform: translateY(-1px);
            filter: saturate(1.05);
            box-shadow: 0 12px 28px rgba(15, 23, 42, 0.14);
        }

        @media (max-width: 991.98px) {
            .page-wrapper {
                margin-left: 0 !important;
            }

            .page-wrapper .content.container-fluid {
                padding-top: 0 !important;
                padding-left: 10px !important;
                padding-right: 10px !important;
            }
        }

        @media (max-width: 575.98px) {
            .btn {
                min-height: 40px;
            }
        }
    </style>
</head>

<body
    @if ($route === 'chat') class="chat-page"
    @elseif ($route === 'mail-pay-invoice') class="invoice-center-pay"
    @elseif (in_array($route, ['cashreceipt-1', 'cashreceipt-2', 'cashreceipt-3', 'cashreceipt-4', 'invoice-five', 'invoice-four-a', 'invoice-three', 'invoice-two', 'invoice-one-a']))
        class="no-stickybar"
    @elseif ($route === 'error-404')
        class="error-page"
    @elseif ($route === 'landing.index')
        class="landing-page-body"
    @endif
>

    {{-- MAIN WRAPPER --}}
    @if (!in_array($route, [
            'landing.index',
            'index-five',
            'mail-pay-invoice',
            'cashreceipt-1',
            'cashreceipt-2',
            'cashreceipt-3',
            'cashreceipt-4',
            'invoice-four-a',
            'invoice-one-a',
            'invoice-three',
            'invoice-two',
            'forgot-password',
            'lock-screen',
            'login',
            'register',
            'saas-login',
            'saas-register',
        ]))
        <div class="main-wrapper">
    @elseif ($route === 'landing.index')
        <div class="main-wrapper landing-wrapper" style="width: 100%; margin: 0; padding: 0;">
    @elseif ($route === 'invoice-four-a')
        <div class="main-wrapper invoice-four">
    @elseif ($route === 'invoice-one-a')
        <div class="main-wrapper invoice-one">
    @elseif ($route === 'invoice-three')
        <div class="main-wrapper invoice-three">
    @elseif ($route === 'invoice-two')
        <div class="main-wrapper invoice-two">
    @elseif ($route === 'index-five')
        <div class="main-wrapper container">
    @elseif (in_array($route, ['forgot-password', 'lock-screen', 'login', 'register', 'saas-login', 'saas-register']))
        <div class="main-wrapper login-body">
    @endif

    {{-- HEADER --}}
    @if (!$hideNavbar && !in_array($route, [
            'landing.index',
            'signature-preview-invoice',
            'mail-pay-invoice',
            'pay-online',
            'login',
            'register',
            'saas-login',
            'invoice-subscription',
            'saas-register',
            'forgot-password',
            'lock-screen',
            'error-404',
            'invoice-one-a',
            'invoice-two',
            'invoice-three',
            'invoice-four-a',
            'invoice-five',
            'cashreceipt-1',
            'cashreceipt-2',
            'cashreceipt-3',
            'cashreceipt-4',
        ]))
        @include('layout.partials.header')
    @endif

    {{-- SIDEBAR --}}
    @if (!$hideSidebar && !in_array($route, [
            'landing.index',
            'signature-preview-invoice',
            'mail-pay-invoice',
            'pay-online',
            'login',
            'register',
            'saas-login',
            'invoice-subscription',
            'saas-register',
            'forgot-password',
            'lock-screen',
            'error-404',
            'invoice-one-a',
            'invoice-two',
            'invoice-three',
            'invoice-four-a',
            'invoice-five',
            'cashreceipt-1',
            'cashreceipt-2',
            'cashreceipt-3',
            'cashreceipt-4',
        ]))
        @include('layout.partials.sidebar')
    @endif

    {{-- OPTIONAL TWO-COLUMN SIDEBAR --}}
    @if ($route === 'index-four')
        @include('layout.partials.two-col-sidebar')
    @endif

    {{-- MAIN PAGE CONTENT --}}
    @yield('content')

    @php
        $skipGlobalModals = in_array($route, [
            'landing.index',
            'landing.about',
            'landing.contact',
            'landing.team',
            'landing.policy',
            'login',
            'register',
            'saas-login',
            'saas-register',
            'forgot-password',
            'lock-screen',
            'error-404',
            'saas.checkout',
            'saas.setup',
            'saas.success',
            'saas.payment.success',
            'saas.payment.cancel',
        ], true) || request()->is('saas/*');
    @endphp

    @unless($skipGlobalModals)
        {{-- Heavy global modals: load only where needed to reduce payload/render time --}}
        @component('components.add-modal-popup') @endcomponent
        @component('components.edit-modal-popup') @endcomponent
        @component('components.modal-popup') @endcomponent
    @endunless

    {{-- CLOSE WRAPPER --}}
    @if (!in_array($route, [
            'mail-pay-invoice',
            'cashreceipt-1',
            'cashreceipt-2',
            'cashreceipt-3',
            'cashreceipt-4',
            'index-five',
            'forgot-password',
            'lock-screen',
            'login',
            'register',
            'saas-login',
            'saas-register',
        ]))
        </div>
    @endif

    {{-- THEME SETTINGS --}}
    @if (
        !$hideSidebar
        && !in_array($route, [
            'landing.index',
            'index-two',
            'index-three',
            'index-four',
            'index-five',
            'signature-preview-invoice',
            'mail-pay-invoice',
            'pay-online',
            'login',
            'register',
            'saas-login',
            'invoice-subscription',
            'saas-register',
            'forgot-password',
            'reset-password',
            'password.request',
            'password.email',
            'password.reset',
            'password.update',
            'saas.checkout',
            'lock-screen',
            'error-404',
            'invoice-one-a',
            'invoice-two',
            'invoice-three',
            'invoice-four-a',
            'invoice-five',
            'cashreceipt-1',
            'cashreceipt-2',
            'cashreceipt-3',
            'cashreceipt-4',
        ])
        && !request()->is('saas/checkout*', 'forgot-password*', 'reset-password*', 'password/*')
    )
        @include('layout.partials.ai-quick-agent')
    @endif

    {{-- FOOTER SCRIPTS --}}
    @include('layout.partials.footer-scripts')
    @stack('scripts')

    @livewireScripts

    {{-- THEME CUSTOMIZER LOGIC --}}
    <script>
    document.addEventListener('DOMContentLoaded', function () {
        const html = document.documentElement;
        const themeOffcanvas = document.getElementById('theme-settings-offcanvas');
        
        if (!themeOffcanvas) return;

        const inputs = themeOffcanvas.querySelectorAll('input');
        const resetBtn = document.getElementById('reset-layout');

        function syncUI() {
            inputs.forEach(input => {
                const attrName = input.name;
                const attrValue = html.getAttribute(attrName);
                if (attrValue && input.value === attrValue) {
                    input.checked = true;
                }
                if (input.id === 'rtl') {
                    input.checked = html.getAttribute('dir') === 'rtl';
                }
            });
        }
        syncUI();

        inputs.forEach(input => {
            input.addEventListener('change', function () {
                let name = this.name;
                let value = this.value;

                if (this.id === 'rtl') {
                    name = 'dir';
                    value = this.checked ? 'rtl' : 'ltr';
                }

                html.setAttribute(name, value);

                const currentSettings = JSON.parse(localStorage.getItem('themeSettings')) || {};
                currentSettings[name] = value;
                localStorage.setItem('themeSettings', JSON.stringify(currentSettings));
            });
        });

        if (resetBtn) {
            resetBtn.addEventListener('click', function () {
                localStorage.removeItem('themeSettings');
                window.location.reload();
            });
        }
    });
    </script>

    <script>
        (function () {
            function isDummyHref(el) {
                const href = (el.getAttribute('href') || '').trim().toLowerCase();
                return href === '' || href === '#' || href === 'javascript:void(0);' || href === 'javascript:void(0)';
            }

            function firstVisibleTable() {
                const tables = Array.from(document.querySelectorAll('table'));
                return tables.find((table) => {
                    const style = window.getComputedStyle(table);
                    return style.display !== 'none' && style.visibility !== 'hidden' && table.offsetParent !== null;
                }) || null;
            }

            function tableToMatrix(table) {
                return Array.from(table.querySelectorAll('tr')).map((row) => {
                    return Array.from(row.querySelectorAll('th,td')).map((cell) => {
                        return (cell.innerText || '').replace(/\s+/g, ' ').trim();
                    });
                }).filter((row) => row.length);
            }

            function downloadBlob(content, filename, type) {
                const blob = new Blob([content], { type });
                const url = URL.createObjectURL(blob);
                const a = document.createElement('a');
                a.href = url;
                a.download = filename;
                document.body.appendChild(a);
                a.click();
                document.body.removeChild(a);
                URL.revokeObjectURL(url);
            }

            function exportTable(format) {
                const table = firstVisibleTable();
                if (!table) {
                    if (format === 'pdf') {
                        window.print();
                    }
                    return;
                }

                const rows = tableToMatrix(table);
                const safeTitle = (document.title || 'report').toLowerCase().replace(/[^a-z0-9]+/g, '_');

                if (format === 'excel') {
                    const tsv = rows.map((row) => row.map((col) => col.replace(/\t/g, ' ')).join('\t')).join('\n');
                    downloadBlob(tsv, safeTitle + '.xls', 'application/vnd.ms-excel;charset=utf-8;');
                    return;
                }

                if (format === 'csv') {
                    const csv = rows.map((row) => row.map((col) => {
                        const escaped = String(col).replace(/"/g, '""');
                        return `"${escaped}"`;
                    }).join(',')).join('\n');
                    downloadBlob(csv, safeTitle + '.csv', 'text/csv;charset=utf-8;');
                    return;
                }

                window.print();
            }

            function findFilterPanel(link) {
                const wrapper = link.closest('.content') || document;
                const candidates = Array.from(wrapper.querySelectorAll('#filter_inputs, .filter-card'));
                const visibleCandidate = candidates.find((el) => el.offsetParent !== null);
                return visibleCandidate || candidates[0] || null;
            }

            function toggleFilterPanel(panel) {
                if (!panel) return;
                if (window.jQuery) {
                    window.jQuery(panel).stop(true, true).slideToggle(150);
                    return;
                }
                const isHidden = window.getComputedStyle(panel).display === 'none';
                panel.style.display = isHidden ? 'block' : 'none';
            }

            function isFilterTrigger(link) {
                if (!link.classList.contains('popup-toggle') && !link.classList.contains('btn-filters')) return false;
                const title = ((link.getAttribute('title') || link.getAttribute('data-bs-original-title') || '')).toLowerCase();
                const text = (link.textContent || '').toLowerCase();
                const icon = link.querySelector('.fe-filter, img[alt="filter"], .filter-img-top');
                return title.includes('filter') || text.includes('filter') || !!icon;
            }

            function dedupeHeaderActions() {
                document.querySelectorAll('.filter-list').forEach((list) => {
                    const seen = new Set();
                    list.querySelectorAll('a.btn-filters, a.popup-toggle').forEach((btn) => {
                        const raw = ((btn.getAttribute('title') || btn.getAttribute('data-bs-original-title') || btn.textContent || '') || '')
                            .toLowerCase()
                            .replace(/\s+/g, ' ')
                            .trim();
                        if (!raw) return;
                        const key = raw.includes('filter') ? 'filter'
                            : raw.includes('print') ? 'print'
                            : raw.includes('download') ? 'download'
                            : raw;
                        if (seen.has(key) && (key === 'filter' || key === 'print' || key === 'download')) {
                            const li = btn.closest('li');
                            if (li) li.style.display = 'none';
                            return;
                        }
                        seen.add(key);
                    });
                });
            }

            document.addEventListener('click', function (event) {
                const link = event.target.closest('a,button');
                if (!link) return;

                const downloadItem = event.target.closest('.download-item');
                if (downloadItem) {
                    event.preventDefault();
                    const text = (downloadItem.textContent || '').toLowerCase();
                    if (text.includes('pdf')) return exportTable('pdf');
                    if (text.includes('excel') || text.includes('xls')) return exportTable('excel');
                    return exportTable('csv');
                }

                const hasPrintIcon = !!link.querySelector('.fe-printer, .fa-print, .feather-printer');
                const title = ((link.getAttribute('title') || link.getAttribute('data-bs-original-title') || '')).toLowerCase();
                const text = (link.textContent || '').toLowerCase();
                const looksLikePrint = title.includes('print') || text.includes('print') || hasPrintIcon;

                if (looksLikePrint && isDummyHref(link)) {
                    event.preventDefault();
                    window.print();
                    return;
                }

                if (isFilterTrigger(link) && isDummyHref(link)) {
                    event.preventDefault();
                    toggleFilterPanel(findFilterPanel(link));
                    return;
                }

                if (link.hasAttribute('download') && isDummyHref(link)) {
                    event.preventDefault();
                    const lowerText = (link.textContent || '').toLowerCase();
                    if (lowerText.includes('pdf')) return exportTable('pdf');
                    if (lowerText.includes('excel') || lowerText.includes('xls')) return exportTable('excel');
                    exportTable('csv');
                }
            });

            document.addEventListener('DOMContentLoaded', dedupeHeaderActions);
        })();
    </script>

    {{-- PREFERENCE SCRIPT: PRINTING (As requested in profile) --}}
    <script>
        window.onbeforeprint = function() {
            console.log("Preparing SmartProbook page for professional printing.");
        };
    </script>

</body>
</html>
