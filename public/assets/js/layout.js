(function () {
    'use strict';

    // Different pages have different HTML attributes. Restoring a theme must
    // never trigger another navigation or erase unrelated in-progress work.
    try {
        if (!sessionStorage.getItem('defaultAttribute')) return;

        [
            'data-layout', 'data-sidebar-size', 'data-layout-mode',
            'data-layout-width', 'data-sidebar', 'data-sidebar-image',
            'data-layout-direction', 'data-layout-position',
            'data-layout-style', 'data-topbar'
        ].forEach(function (attribute) {
            var value = sessionStorage.getItem(attribute);
            if (value) document.documentElement.setAttribute(attribute, value);
        });
    } catch (error) {
        // Storage may be disabled. The server-rendered theme is still usable.
    }
})();
