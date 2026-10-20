/* Public navigation works without Bootstrap or third-party downloads. */
(() => {
    'use strict';
    const initialize = () => {
        document.querySelectorAll('[data-public-menu-toggle]').forEach((button) => {
            const menu = document.getElementById(button.getAttribute('aria-controls'));
            if (!menu || button.dataset.navigationBound) return;
            button.dataset.navigationBound = 'true';
            const compact = window.matchMedia('(max-width: 1199.98px)');
            const openClass = button.dataset.menuOpenClass || 'active';
            let open = false;
            const setOpen = (next, restoreFocus = false) => {
                open = compact.matches && next;
                menu.classList.toggle(openClass, open);
                button.classList.toggle('active', open);
                button.setAttribute('aria-expanded', String(open));
                button.setAttribute('aria-label', open ? 'Close navigation' : 'Open navigation');
                menu.inert = compact.matches && !open;
                document.body.classList.toggle('landing-menu-open', open);
                if (restoreFocus) button.focus();
            };
            button.addEventListener('click', (event) => {
                event.preventDefault();
                setOpen(!open);
            });
            menu.addEventListener('click', (event) => {
                if (event.target.closest('a[href]')) setOpen(false);
            });
            document.addEventListener('click', (event) => {
                if (open && !menu.contains(event.target) && !button.contains(event.target)) setOpen(false);
            });
            document.addEventListener('keydown', (event) => {
                if (event.key === 'Escape' && open) setOpen(false, true);
            });
            // Height-only resizes from Android's browser toolbar must not close the menu.
            compact.addEventListener('change', () => setOpen(false));
            window.addEventListener('pageshow', () => setOpen(false));
            setOpen(false);
        });
    };
    if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', initialize);
    else initialize();
})();
