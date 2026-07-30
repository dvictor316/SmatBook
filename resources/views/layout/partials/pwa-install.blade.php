<style>
    .spb-install-app {
        position: fixed;
        right: 24px;
        bottom: 24px;
        width: min(360px, calc(100vw - 32px));
        display: none;
        align-items: center;
        gap: 14px;
        padding: 16px;
        border: 1px solid rgba(6, 26, 68, 0.14);
        border-radius: 22px;
        background: linear-gradient(135deg, #ffffff 0%, #eef5ff 100%);
        box-shadow: 0 22px 60px rgba(6, 26, 68, 0.22);
        z-index: 2147482500;
        color: #061a44;
    }

    .spb-install-app.is-visible {
        display: flex;
    }

    .spb-install-app__icon {
        width: 52px;
        height: 52px;
        flex: 0 0 52px;
        display: grid;
        place-items: center;
        border-radius: 16px;
        background: #ffffff;
        box-shadow: inset 0 0 0 1px rgba(6, 26, 68, 0.08);
        overflow: hidden;
    }

    .spb-install-app__icon img {
        width: 100%;
        height: 100%;
        object-fit: contain;
    }

    .spb-install-app__copy {
        flex: 1;
        min-width: 0;
    }

    .spb-install-app__title {
        margin: 0 0 2px;
        font-size: 15px;
        font-weight: 800;
        line-height: 1.25;
    }

    .spb-install-app__text {
        margin: 0;
        color: #52627a;
        font-size: 12.5px;
        line-height: 1.35;
    }

    .spb-install-app__actions {
        display: flex;
        align-items: center;
        gap: 8px;
    }

    .spb-install-app__button {
        border: 0;
        border-radius: 999px;
        background: #0b55d9;
        color: #ffffff;
        cursor: pointer;
        font-size: 13px;
        font-weight: 800;
        line-height: 1;
        padding: 12px 16px;
        white-space: nowrap;
        box-shadow: 0 10px 24px rgba(11, 85, 217, 0.26);
    }

    .spb-install-app__close {
        width: 32px;
        height: 32px;
        border: 0;
        border-radius: 50%;
        background: rgba(6, 26, 68, 0.08);
        color: #061a44;
        cursor: pointer;
        font-size: 20px;
        line-height: 1;
    }

    @media (max-width: 767.98px) {
        .spb-install-app {
            display: none !important;
        }
    }

    @media print {
        .spb-install-app {
            display: none !important;
        }
    }
</style>

<div class="spb-install-app" id="spbInstallApp" role="region" aria-label="Install SmartProBook desktop app">
    <div class="spb-install-app__icon" aria-hidden="true">
        <img src="{{ asset('assets/pwa/icon-192.png') }}" alt="">
    </div>
    <div class="spb-install-app__copy">
        <p class="spb-install-app__title">Install desktop app</p>
        <p class="spb-install-app__text">Open SmartProBook from your system like a normal app.</p>
    </div>
    <div class="spb-install-app__actions">
        <button type="button" class="spb-install-app__button" id="spbInstallAppButton">Install</button>
        <button type="button" class="spb-install-app__close" id="spbInstallAppClose" aria-label="Dismiss install prompt">&times;</button>
    </div>
</div>

<script>
    (function () {
        const promptEl = document.getElementById('spbInstallApp');
        const installButton = document.getElementById('spbInstallAppButton');
        const closeButton = document.getElementById('spbInstallAppClose');
        const dismissedKey = 'smartprobook_install_prompt_dismissed_at';
        let installPromptEvent = null;

        const isStandalone = window.matchMedia('(display-mode: standalone)').matches || window.navigator.standalone === true;
        const isDesktopWidth = () => window.matchMedia('(min-width: 768px)').matches;
        const recentlyDismissed = () => {
            const dismissedAt = Number(localStorage.getItem(dismissedKey) || 0);
            return dismissedAt && Date.now() - dismissedAt < 7 * 24 * 60 * 60 * 1000;
        };
        const hidePrompt = () => promptEl?.classList.remove('is-visible');
        const showPrompt = () => {
            if (!promptEl || !installPromptEvent || isStandalone || !isDesktopWidth() || recentlyDismissed()) return;
            promptEl.classList.add('is-visible');
        };

        if ('serviceWorker' in navigator) {
            window.addEventListener('load', function () {
                navigator.serviceWorker.register('/pwa-sw.js').catch(function () {});
            });
        }

        window.addEventListener('beforeinstallprompt', function (event) {
            event.preventDefault();
            installPromptEvent = event;
            showPrompt();
        });

        installButton?.addEventListener('click', async function () {
            if (!installPromptEvent) return;
            hidePrompt();
            installPromptEvent.prompt();
            try {
                await installPromptEvent.userChoice;
            } finally {
                installPromptEvent = null;
            }
        });

        closeButton?.addEventListener('click', function () {
            localStorage.setItem(dismissedKey, String(Date.now()));
            hidePrompt();
        });

        window.addEventListener('appinstalled', function () {
            localStorage.removeItem(dismissedKey);
            installPromptEvent = null;
            hidePrompt();
        });

        window.addEventListener('resize', showPrompt);
    })();
</script>
