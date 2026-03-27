@php
    $flashMessages = collect([
        ['type' => 'success', 'icon' => 'fa-check-circle', 'message' => session('success')],
        ['type' => 'danger', 'icon' => 'fa-exclamation-triangle', 'message' => session('error')],
        ['type' => 'warning', 'icon' => 'fa-triangle-exclamation', 'message' => session('warning')],
        ['type' => 'info', 'icon' => 'fa-circle-info', 'message' => session('info') ?? session('status')],
    ])->filter(fn ($item) => filled($item['message']))->values();
@endphp

@if ($flashMessages->isNotEmpty() || $errors->any())
    <style>
        .global-flash-stack {
            position: fixed;
            top: calc(var(--sb-header-h, 76px) + 16px);
            right: 18px;
            z-index: 2000;
            width: min(420px, calc(100vw - 24px));
            display: grid;
            gap: 10px;
            pointer-events: none;
        }

        .global-flash-stack .alert {
            pointer-events: auto;
            margin: 0;
            border: 0;
            border-radius: 16px;
            box-shadow: 0 16px 38px rgba(15, 23, 42, 0.14);
            backdrop-filter: blur(8px);
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

        @media (max-width: 991.98px) {
            .global-flash-stack {
                left: 12px;
                right: 12px;
                width: auto;
                top: calc(var(--sb-header-h, 76px) + 12px);
            }
        }
    </style>

    <div class="global-flash-stack" data-global-flash-stack>
        @foreach ($flashMessages as $flash)
            <div class="alert alert-{{ $flash['type'] }} alert-dismissible fade show" role="alert" data-auto-dismiss="false">
                <div class="flash-body">
                    <i class="fas {{ $flash['icon'] }}"></i>
                    <div class="flash-copy">{{ $flash['message'] }}</div>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        @endforeach

        @if ($errors->any())
            <div class="alert alert-danger alert-dismissible fade show" role="alert" data-auto-dismiss="false">
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

@endif
