(function () {
    'use strict';

    if (window.__spbResponseFeedbackReady) return;
    window.__spbResponseFeedbackReady = true;

    const pendingForms = new WeakSet();
    let hideTimer;
    let pendingAjaxRequests = 0;
    let navigationPending = false;
    let navigationFallbackTimer;
    let pendingLinkTimer;

    const style = document.createElement('style');
    style.textContent = `
        #spb-navigation-progress {
            position: fixed;
            inset: 0 0 auto;
            z-index: 20000;
            height: 3px;
            overflow: hidden;
            pointer-events: none;
            background: transparent;
        }
        #spb-navigation-progress[hidden] { display: none; }
        #spb-navigation-progress::before {
            content: "";
            display: block;
            width: 35%;
            height: 100%;
            background: #2563eb;
            transform: translateX(-110%);
        }
        #spb-navigation-progress.is-active::before {
            animation: spb-navigation-progress 1.15s ease-in-out infinite;
        }
        a[data-spb-pending="true"] {
            cursor: progress;
            opacity: .78;
            pointer-events: none;
        }
        button[data-spb-pending="true"],
        input[data-spb-pending="true"] {
            cursor: progress;
            opacity: .78;
        }
        @keyframes spb-navigation-progress {
            from { transform: translateX(-110%); }
            to { transform: translateX(310%); }
        }
        @media (prefers-reduced-motion: reduce) {
            #spb-navigation-progress.is-active::before { animation-duration: 2.5s; }
        }
    `;
    document.head.appendChild(style);

    const progress = document.createElement('div');
    progress.id = 'spb-navigation-progress';
    progress.setAttribute('role', 'progressbar');
    progress.setAttribute('aria-label', 'Loading');
    progress.setAttribute('aria-valuetext', 'Loading');
    progress.hidden = true;
    document.body.appendChild(progress);

    function show() {
        window.clearTimeout(hideTimer);
        progress.hidden = false;
        progress.classList.add('is-active');
        document.documentElement.setAttribute('aria-busy', 'true');
    }

    function hide() {
        if (pendingAjaxRequests > 0 || navigationPending) return;
        window.clearTimeout(hideTimer);
        progress.classList.remove('is-active');
        progress.hidden = true;
        document.documentElement.removeAttribute('aria-busy');
        document.querySelectorAll('[data-spb-pending="true"]').forEach(function (element) {
            element.removeAttribute('data-spb-pending');
            element.removeAttribute('aria-busy');
        });
    }

    window.SPBPageLoader = { show: show, hide: hide };

    function isSamePageNavigation(link) {
        const href = (link.getAttribute('href') || '').trim();
        if (!href || href === '#' || /^javascript:/i.test(href)) return true;
        if (link.hasAttribute('download') || link.hasAttribute('data-spb-no-loading')) return true;
        if (link.hasAttribute('data-bs-toggle') || link.getAttribute('role') === 'tab') return true;
        if (link.target && link.target.toLowerCase() !== '_self') return true;

        const destination = new URL(link.href, window.location.href);
        if (destination.origin !== window.location.origin) return true;
        return destination.pathname === window.location.pathname
            && destination.search === window.location.search
            && destination.hash !== '';
    }

    document.addEventListener('click', function (event) {
        if (event.defaultPrevented || event.button !== 0 || event.metaKey || event.ctrlKey || event.shiftKey || event.altKey) return;
        const target = event.target instanceof Element ? event.target : event.target?.parentElement;
        const link = target?.closest('a[href]');
        if (!link || isSamePageNavigation(link)) return;
        if (link.dataset.spbPending === 'true') {
            event.preventDefault();
            return;
        }

        show();
        link.dataset.spbPending = 'true';
        link.setAttribute('aria-busy', 'true');
        window.clearTimeout(pendingLinkTimer);
        pendingLinkTimer = window.setTimeout(function () {
            link.removeAttribute('data-spb-pending');
            link.removeAttribute('aria-busy');
            pendingLinkTimer = null;
            hide();
        }, 30000);
    });

    document.addEventListener('submit', function (event) {
        const form = event.target;
        if (
            event.defaultPrevented
            || !(form instanceof HTMLFormElement)
            || form.hasAttribute('data-spb-no-loading')
            || form.hasAttribute('wire:submit')
            || (form.target && form.target.toLowerCase() !== '_self')
        ) return;

        if (pendingForms.has(form)) {
            event.preventDefault();
            return;
        }

        pendingForms.add(form);
        show();
        const submitter = event.submitter || document.activeElement;
        if (submitter && form.contains(submitter) && submitter.matches('button, input[type="submit"], input[type="image"]')) {
            submitter.dataset.spbPending = 'true';
            submitter.setAttribute('aria-busy', 'true');
        }
    });

    document.addEventListener('livewire:navigate', function () {
        window.clearTimeout(pendingLinkTimer);
        window.clearTimeout(navigationFallbackTimer);
        navigationPending = true;
        show();
        navigationFallbackTimer = window.setTimeout(function () {
            navigationPending = false;
            hide();
        }, 30000);
    });
    document.addEventListener('livewire:navigated', function () {
        window.clearTimeout(pendingLinkTimer);
        window.clearTimeout(navigationFallbackTimer);
        navigationPending = false;
        hide();
    });
    window.addEventListener('pageshow', function () {
        window.clearTimeout(pendingLinkTimer);
        window.clearTimeout(navigationFallbackTimer);
        navigationPending = false;
        hide();
    });

    let livewireRequestHooksRegistered = false;
    function registerLivewireRequestHooks() {
        if (
            livewireRequestHooksRegistered
            || !window.Livewire
            || typeof window.Livewire.hook !== 'function'
        ) return;

        livewireRequestHooksRegistered = true;
        window.Livewire.hook('request', function ({ succeed, fail }) {
            pendingAjaxRequests += 1;
            show();
            let requestFinished = false;
            const finishRequest = function () {
                if (requestFinished) return;
                requestFinished = true;
                pendingAjaxRequests = Math.max(0, pendingAjaxRequests - 1);
                if (pendingAjaxRequests === 0) hide();
            };
            succeed(finishRequest);
            fail(finishRequest);
        });
    }

    document.addEventListener('livewire:init', registerLivewireRequestHooks);
    registerLivewireRequestHooks();

    if (typeof window.fetch === 'function') {
        const nativeFetch = window.fetch.bind(window);
        window.fetch = function (input, init) {
            let trackRequest = false;
            try {
                const requestUrl = typeof Request !== 'undefined' && input instanceof Request ? input.url : input;
                trackRequest = new URL(requestUrl, window.location.href).origin === window.location.origin;
            } catch (_) {
                trackRequest = false;
            }

            if (trackRequest) {
                pendingAjaxRequests += 1;
                show();
            }

            return nativeFetch(input, init).finally(function () {
                if (!trackRequest) return;
                pendingAjaxRequests = Math.max(0, pendingAjaxRequests - 1);
                if (pendingAjaxRequests === 0) hide();
            });
        };
    }

    if (window.jQuery) {
        window.jQuery(document)
            .on('ajaxStart.spbResponseFeedback', function () {
                pendingAjaxRequests += 1;
                show();
            })
            .on('ajaxStop.spbResponseFeedback', function () {
                pendingAjaxRequests = Math.max(0, pendingAjaxRequests - 1);
                if (pendingAjaxRequests === 0) hide();
            });
    }
})();
