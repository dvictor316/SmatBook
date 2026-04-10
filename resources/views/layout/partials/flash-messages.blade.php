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
    ])->filter(fn ($item) => filled($item['message']))->values();
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
