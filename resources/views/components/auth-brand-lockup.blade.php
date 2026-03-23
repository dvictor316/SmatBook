@props([
    'logo' => null,
    'theme' => 'light',
    'size' => 'md',
    'stacked' => false,
    'tagline' => null,
])

@php
    $logoSrc = $logo ?: asset('assets/img/logos.png');
    $isDark = $theme === 'dark';
    $sizeMap = [
        'sm' => ['logo' => '34px', 'brand' => '1.55rem', 'tag' => '0.72rem'],
        'md' => ['logo' => '42px', 'brand' => '1.9rem', 'tag' => '0.78rem'],
        'lg' => ['logo' => '56px', 'brand' => '2.3rem', 'tag' => '0.84rem'],
    ];
    $config = $sizeMap[$size] ?? $sizeMap['md'];
    $brandColor = $isDark ? '#ffffff' : '#0b2a63';
    $accentColor = '#dc2626';
    $tagColor = $isDark ? 'rgba(255,255,255,0.74)' : '#64748b';
@endphp

<div class="spb-auth-lockup{{ $stacked ? ' is-stacked' : '' }}{{ $isDark ? ' is-on-dark' : '' }}">
    <img src="{{ $logoSrc }}" alt="SmartProbook" class="spb-auth-lockup__logo">
    <div class="spb-auth-lockup__copy">
        <div class="spb-auth-lockup__brand">
            <span class="spb-auth-lockup__brand-main">SmartPro</span><span class="spb-auth-lockup__brand-accent">book</span>
        </div>
        @if($tagline)
            <div class="spb-auth-lockup__tagline">{{ $tagline }}</div>
        @endif
    </div>
</div>

<style>
    .spb-auth-lockup {
        display: inline-flex;
        align-items: center;
        gap: 14px;
        max-width: 100%;
    }

    .spb-auth-lockup.is-stacked {
        flex-direction: column;
        text-align: center;
        gap: 10px;
    }

    .spb-auth-lockup.is-on-dark {
        padding: 14px 18px;
        border-radius: 22px;
        background: rgba(255, 255, 255, 0.98);
        box-shadow: 0 18px 40px rgba(15, 23, 42, 0.18);
        border: 1px solid rgba(255, 255, 255, 0.74);
    }

    .spb-auth-lockup__logo {
        height: {{ $config['logo'] }};
        width: auto;
        display: block;
        flex: 0 0 auto;
    }

    .spb-auth-lockup__copy {
        line-height: 1;
        min-width: 0;
    }

    .spb-auth-lockup__brand {
        font-size: {{ $config['brand'] }};
        font-weight: 800;
        letter-spacing: -0.03em;
        color: {{ $brandColor }};
        white-space: nowrap;
    }

    .spb-auth-lockup__brand-main {
        color: {{ $brandColor }};
    }

    .spb-auth-lockup__brand-accent {
        color: {{ $accentColor }};
    }

    .spb-auth-lockup__tagline {
        margin-top: 6px;
        font-size: {{ $config['tag'] }};
        font-weight: 700;
        letter-spacing: 0.16em;
        text-transform: uppercase;
        color: {{ $tagColor }};
    }

    @media (max-width: 480px) {
        .spb-auth-lockup {
            gap: 10px;
        }

        .spb-auth-lockup.is-on-dark {
            padding: 12px 14px;
            border-radius: 18px;
        }

        .spb-auth-lockup__logo {
            height: calc({{ $config['logo'] }} * 0.8);
        }

        .spb-auth-lockup__brand {
            font-size: calc({{ $config['brand'] }} * 0.72);
            letter-spacing: -0.024em;
        }

        .spb-auth-lockup__tagline {
            font-size: calc({{ $config['tag'] }} * 0.82);
            letter-spacing: 0.14em;
        }
    }

    @media (max-width: 360px) {
        .spb-auth-lockup {
            gap: 8px;
        }

        .spb-auth-lockup.is-on-dark {
            padding: 10px 12px;
            border-radius: 16px;
        }

        .spb-auth-lockup__logo {
            height: calc({{ $config['logo'] }} * 0.68);
        }

        .spb-auth-lockup__brand {
            font-size: calc({{ $config['brand'] }} * 0.62);
            letter-spacing: -0.02em;
        }

        .spb-auth-lockup__tagline {
            font-size: calc({{ $config['tag'] }} * 0.74);
            letter-spacing: 0.12em;
        }
    }
</style>
