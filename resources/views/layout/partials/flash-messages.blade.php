@php if (!isset($errors)) { $errors = session('errors') ?: new \Illuminate\Support\ViewErrorBag; } @endphp

@php
    $suppressGlobalFlash = request()->routeIs([
        'login',
        'saas-login',
        'saas-register',
        'forgot-password',
        'password.request',
        'password.reset',
        'lock-screen',
    ]);

    $flashMessages = collect([
        ['type' => 'success', 'icon' => 'fa-check-circle', 'message' => session('success')],
        ['type' => 'danger', 'icon' => 'fa-exclamation-triangle', 'message' => session('error')],
        ['type' => 'warning', 'icon' => 'fa-triangle-exclamation', 'message' => session('warning')],
        ['type' => 'info', 'icon' => 'fa-circle-info', 'message' => session('info') ?? session('status')],
    ])->filter(fn ($item) => filled($item['message']));

    if (($isDemoWorkspace ?? false) && filled(session('info'))) {
        $flashMessages = $flashMessages->reject(function ($item) {
            return $item['type'] === 'info'
                && str_contains(
                    strtolower(trim((string) $item['message'])),
                    'secure custom subdomain is still being finalized'
                );
        });
    }

    $flashMessages = $flashMessages->values();
    $popupMessages = $flashMessages
        ->map(fn ($item) => [
            'type' => $item['type'],
            'message' => trim((string) $item['message']),
        ])
        ->filter(fn ($item) => filled($item['message']))
        ->values();

    if ($errors->any()) {
        $popupMessages->push([
            'type' => 'danger',
            'message' => $errors->first(),
        ]);
    }

    $popupMessages = $popupMessages
        ->unique(fn ($item) => $item['type'] . '|' . strtolower($item['message']))
        ->values();
@endphp

@if (!$suppressGlobalFlash && ($flashMessages->isNotEmpty() || $errors->any()))
    <style>
        .global-flash-stack {
            position: relative;
            z-index: 20;
            width: 100%;
            display: grid;
            gap: 10px;
            box-sizing: border-box;
            padding: 0 16px 16px;
        }

        .global-flash-stack .alert {
            width: min(100%, 1280px);
            margin: 0 auto;
            border: 0;
            border-radius: 14px;
            box-shadow: 0 8px 22px rgba(15, 23, 42, 0.08);
        }

        .global-flash-stack .alert .btn-close {
            opacity: 0.7;
        }

        .global-flash-stack .alert .btn-close:hover {
            opacity: 1;
        }

        .global-flash-stack .flash-body {
            display: flex;
            align-items: flex-start;
            gap: 10px;
        }

        .global-flash-stack .flash-body i {
            margin-top: 2px;
        }

        .global-flash-stack .flash-copy {
            min-width: 0;
            font-weight: 600;
            line-height: 1.45;
        }

        .global-flash-stack .flash-copy ul {
            margin: 6px 0 0;
            padding-left: 18px;
            font-weight: 500;
        }

        @media (min-width: 992px) {
            .global-flash-stack {
                padding-inline: 24px;
            }

            body:not(.sidebar-collapsed):not(.mini-sidebar):not(.sidebar-icon-only) .global-flash-stack {
                margin-left: var(--sb-sidebar-w, 270px);
                width: calc(100% - var(--sb-sidebar-w, 270px));
            }

            body.sidebar-collapsed .global-flash-stack,
            body.mini-sidebar .global-flash-stack,
            body.sidebar-icon-only .global-flash-stack {
                margin-left: var(--sb-sidebar-collapsed, 80px);
                width: calc(100% - var(--sb-sidebar-collapsed, 80px));
            }
        }

        @media (max-width: 991.98px) {
            .global-flash-stack {
                padding-inline: 12px;
            }
        }

        .spb-flash-popup-stack {
            position: fixed;
            top: 18px;
            right: 18px;
            z-index: 10060;
            display: grid;
            gap: 10px;
            width: min(360px, calc(100vw - 28px));
            pointer-events: none;
        }

        .spb-flash-popup {
            pointer-events: auto;
            display: flex;
            align-items: flex-start;
            gap: 10px;
            padding: 13px 14px;
            border-radius: 14px;
            background: #ffffff;
            border: 1px solid rgba(148, 163, 184, 0.28);
            box-shadow: 0 18px 45px rgba(15, 23, 42, 0.18);
            color: #0f172a;
            animation: spbFlashSlideIn 0.22s ease-out both;
        }

        .spb-flash-popup[data-type="success"] { border-left: 5px solid #10b981; }
        .spb-flash-popup[data-type="danger"] { border-left: 5px solid #ef4444; }
        .spb-flash-popup[data-type="warning"] { border-left: 5px solid #f59e0b; }
        .spb-flash-popup[data-type="info"] { border-left: 5px solid #2563eb; }

        .spb-flash-popup i {
            margin-top: 2px;
        }

        .spb-flash-popup[data-type="success"] i { color: #10b981; }
        .spb-flash-popup[data-type="danger"] i { color: #ef4444; }
        .spb-flash-popup[data-type="warning"] i { color: #f59e0b; }
        .spb-flash-popup[data-type="info"] i { color: #2563eb; }

        .spb-flash-popup-copy {
            flex: 1;
            min-width: 0;
            font-size: 0.9rem;
            font-weight: 700;
            line-height: 1.4;
        }

        .spb-flash-popup-close {
            border: 0;
            background: transparent;
            color: #64748b;
            font-size: 1.05rem;
            line-height: 1;
            cursor: pointer;
            padding: 2px;
        }

        @keyframes spbFlashSlideIn {
            from { opacity: 0; transform: translate3d(16px, -8px, 0); }
            to { opacity: 1; transform: translate3d(0, 0, 0); }
        }
    </style>

    <div class="global-flash-stack" data-global-flash-stack>
        @foreach ($flashMessages as $flash)
            <div class="alert alert-{{ $flash['type'] }} alert-dismissible fade show" role="alert" data-auto-dismiss="false" data-flash-message>
                <div class="flash-body">
                    <i class="fas {{ $flash['icon'] }}"></i>
                    <div class="flash-copy">{{ $flash['message'] }}</div>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        @endforeach

        @if ($errors->any())
            <div class="alert alert-danger alert-dismissible fade show" role="alert" data-auto-dismiss="false" data-flash-message>
                <div class="flash-body">
                    <i class="fas fa-circle-xmark"></i>
                    <div class="flash-copy">
                        Please fix the following and try again.
                        <ul>
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        @endif
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const popupMessages = @json($popupMessages);
            const iconMap = {
                success: 'fa-check-circle',
                danger: 'fa-circle-xmark',
                warning: 'fa-triangle-exclamation',
                info: 'fa-circle-info',
            };

            if (Array.isArray(popupMessages) && popupMessages.length > 0) {
                const popupStack = document.createElement('div');
                popupStack.className = 'spb-flash-popup-stack';
                document.body.appendChild(popupStack);

                popupMessages.forEach((item, index) => {
                    const popup = document.createElement('div');
                    const type = item.type || 'info';
                    popup.className = 'spb-flash-popup';
                    popup.dataset.type = type;
                    popup.innerHTML = `
                        <i class="fas ${iconMap[type] || iconMap.info}"></i>
                        <div class="spb-flash-popup-copy"></div>
                        <button type="button" class="spb-flash-popup-close" aria-label="Close">&times;</button>
                    `;
                    popup.querySelector('.spb-flash-popup-copy').textContent = item.message || '';
                    popup.querySelector('.spb-flash-popup-close').addEventListener('click', () => popup.remove());
                    popupStack.appendChild(popup);

                    window.setTimeout(() => popup.remove(), 5200 + (index * 500));
                });
            }

            const flashStack = document.querySelector('[data-global-flash-stack]');
            if (!flashStack) {
                return;
            }

            const normalize = (value) => (value || '')
                .replace(/\s+/g, ' ')
                .trim()
                .toLowerCase();

            const inlineAlerts = Array.from(document.querySelectorAll('.alert'))
                .filter((alert) => !alert.closest('[data-global-flash-stack]'))
                .map((alert) => normalize(alert.textContent))
                .filter(Boolean);

            if (inlineAlerts.length === 0) {
                return;
            }

            flashStack.querySelectorAll('[data-flash-message]').forEach((alert) => {
                const flashText = normalize(alert.textContent);
                if (flashText && inlineAlerts.includes(flashText)) {
                    alert.remove();
                }
            });

            if (!flashStack.querySelector('[data-flash-message]')) {
                flashStack.remove();
            }
        });
    </script>

@endif
