<script>
    window.formatCompactAmount = window.formatCompactAmount || function (value, locale) {
        var amount = Number(value);
        if (!Number.isFinite(amount)) return '';
        var formatter = new Intl.NumberFormat(locale, { minimumFractionDigits: 2, maximumFractionDigits: 2 });
        var formatted = formatter.format(amount);
        var decimal = formatter.formatToParts(1.5).find(function (part) { return part.type === 'decimal'; })?.value || '.';
        var zeroFraction = decimal + '00';
        return formatted.endsWith(zeroFraction) ? formatted.slice(0, -zeroFraction.length) : formatted;
    };
</script>
