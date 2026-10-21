(function () {
    'use strict';

    function panelFor(trigger) {
        const scope = trigger.closest('.content, .page-wrapper') || document;
        const target = trigger.getAttribute('data-filter-target') || trigger.getAttribute('data-bs-target');
        if (target) {
            try { const panel = scope.querySelector(target); if (panel) return panel; } catch (_) { /* Ignore invalid selectors. */ }
        }
        return scope.querySelector('#filter_inputs, .filter-card');
    }

    // Capture once before legacy direct/delegated handlers can toggle the same panel again.
    document.addEventListener('click', function (event) {
        const trigger = event.target.closest('#filter_search, [data-filter-target], .btn-filters, .popup-toggle');
        if (!trigger || (trigger.tagName === 'BUTTON' && trigger.type === 'submit' && trigger.form)) return;
        const href = trigger.getAttribute('href') || '';
        if (trigger.tagName === 'A' && href && !href.startsWith('#') && !/^javascript:void\(0\);?$/.test(href) && !trigger.hasAttribute('data-filter-target')) return;
        const isFilter = trigger.id === 'filter_search' || trigger.hasAttribute('data-filter-target')
            || /filter/i.test(trigger.textContent + ' ' + (trigger.title || ''))
            || trigger.querySelector('.fe-filter, .feather-filter, img[alt="filter"], .filter-img-top');
        if (!isFilter) return;
        const panel = panelFor(trigger);
        if (!panel) return;
        event.preventDefault();
        event.stopImmediatePropagation();
        const opening = panel.hidden || window.getComputedStyle(panel).display === 'none';
        panel.hidden = false;
        panel.style.display = opening ? 'block' : 'none';
        trigger.setAttribute('aria-expanded', String(opening));
        if (panel.id) trigger.setAttribute('aria-controls', panel.id);
    }, true);

    document.addEventListener('change', function (event) {
        const select = event.target.closest('[data-spb-per-page]');
        if (!select) return;
        const url = new URL(window.location.href);
        url.searchParams.set('per_page', select.value);
        url.searchParams.delete(select.dataset.pageName || 'page');
        Array.from(url.searchParams.keys()).forEach(function (key) {
            if (key !== 'per_page' && (key === 'page' || /_page$/.test(key))) url.searchParams.delete(key);
        });
        window.location.assign(url.toString());
    });

    document.addEventListener('submit', function (event) {
        const form = event.target;
        if (!(form instanceof HTMLFormElement) || form.method.toLowerCase() !== 'get') return;
        const current = new URL(window.location.href);
        const target = new URL(form.action || current.href, current.href);
        if (target.origin !== current.origin || target.pathname !== current.pathname) return;
        ['per_page', 'branch_id', 'branch_scope', 'all_branches'].forEach(function (key) {
            if (!form.elements.namedItem(key) && current.searchParams.has(key)) {
                const hidden = document.createElement('input');
                hidden.type = 'hidden'; hidden.name = key; hidden.value = current.searchParams.get(key);
                form.appendChild(hidden);
            }
        });
        Array.from(form.elements).forEach(function (input) {
            if (input.name !== 'per_page' && (input.name === 'page' || /_page$/.test(input.name))) input.disabled = true;
        });
    }, true);

    document.addEventListener('DOMContentLoaded', function () {
        const params = new URL(window.location.href).searchParams;
        document.querySelectorAll('#filter_inputs, .filter-card').forEach(function (panel) {
            const active = Array.from(panel.querySelectorAll('[name]')).some(function (input) {
                return !['per_page', 'page', 'branch_id', 'branch_scope', 'all_branches'].includes(input.name)
                    && params.has(input.name) && params.get(input.name) !== '';
            });
            if (active) { panel.hidden = false; panel.style.display = 'block'; }
        });
    });

    if (window.jQuery && window.jQuery.fn.dataTable) {
        const $ = window.jQuery;
        $.extend(true, $.fn.dataTable.defaults, {
            pageLength: 500,
            lengthMenu: [[25, 50, 100, 250, 500], [25, 50, 100, 250, 500]]
        });
        // Custom table initializers must also avoid paginating a Laravel page a second time.
        $(document).on('init.dt', function (_event, settings) {
            const scope = settings.nTable.closest('.card') || settings.nTable.closest('.content');
            if (scope && scope.querySelector('[data-server-pagination]')) {
                $(settings.nTableWrapper).find('.dataTables_paginate, .dataTables_length, .dataTables_filter, .dataTables_info').remove();
            }
        });
        $(document).on('preInit.dt', function (_event, settings) {
            const scope = settings.nTable.closest('.card') || settings.nTable.closest('.content');
            if (scope && scope.querySelector('[data-server-pagination]')) {
                settings.oFeatures.bPaginate = false;
                settings.oFeatures.bLengthChange = false;
                settings.oFeatures.bFilter = false;
                settings.oFeatures.bInfo = false;
            }
        });
    }
})();
