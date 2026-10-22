(function () {
    'use strict';

    function syncReportingPeriodMenu() {
        var menu = document.querySelector('.spb-period-menu');
        if (!menu || !window.matchMedia) return;

        var desktopQuery = window.matchMedia('(min-width: 768px)');
        var updateMenu = function () {
            menu.open = desktopQuery.matches;
        };

        updateMenu();
        desktopQuery.addEventListener('change', updateMenu);
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', syncReportingPeriodMenu, { once: true });
    } else {
        syncReportingPeriodMenu();
    }

    document.addEventListener('change', function (event) {
        var field = event.target;
        if (!field || !field.closest) return;
        var form = field.closest('form[data-reporting-period]');
        if (!form) return;
        var period = form.elements.namedItem('period');
        if (!period) return;
        var names = ['date_from', 'date_to', 'sale_date', 'date_time_from', 'date_time_to'];
        if (field.name === 'period' && period.value !== 'custom') {
            names.forEach(function (name) { var input = form.elements.namedItem(name); if (input) input.value = ''; });
        } else if (names.indexOf(field.name) !== -1) {
            if (field.name === 'date_time_from' || field.name === 'date_time_to') {
                ['sale_date', 'date_from', 'date_to'].forEach(function (name) { var input = form.elements.namedItem(name); if (input) input.value = ''; });
            } else if (field.name === 'sale_date') {
                ['date_from', 'date_to', 'date_time_from', 'date_time_to'].forEach(function (name) { var input = form.elements.namedItem(name); if (input) input.value = ''; });
            } else {
                ['sale_date', 'date_time_from', 'date_time_to'].forEach(function (name) { var input = form.elements.namedItem(name); if (input) input.value = ''; });
            }
            period.value = names.some(function (name) { var input = form.elements.namedItem(name); return input && input.value; }) ? 'custom' : 'all';
        }
    });
})();
