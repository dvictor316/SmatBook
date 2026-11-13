(() => {
    'use strict';

    const button = document.querySelector('.store-menu-toggle');
    const menu = document.getElementById('store-navigation');
    if (!button || !menu) return;

    const compact = window.matchMedia('(max-width: 767px)');
    const setOpen = (open) => {
        const expanded = compact.matches && open;
        menu.classList.toggle('is-open', expanded);
        button.setAttribute('aria-expanded', String(expanded));
        button.setAttribute('aria-label', expanded ? 'Close store navigation' : 'Open store navigation');
    };

    button.addEventListener('click', () => setOpen(button.getAttribute('aria-expanded') !== 'true'));
    menu.addEventListener('click', (event) => {
        if (event.target.closest('a[href]')) setOpen(false);
    });
    document.addEventListener('click', (event) => {
        if (!menu.contains(event.target) && !button.contains(event.target)) setOpen(false);
    });
    document.addEventListener('keydown', (event) => {
        if (event.key === 'Escape' && button.getAttribute('aria-expanded') === 'true') {
            setOpen(false);
            button.focus();
        }
    });
    compact.addEventListener('change', () => setOpen(false));
    window.addEventListener('pageshow', () => setOpen(false));
    document.body.classList.add('store-nav-enhanced');
})();
