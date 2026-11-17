/* Shared by the catalogue and product picker; no network needed for matching. */
(function (root) {
    'use strict';
    const normalize = (value) => String(value ?? '').normalize('NFKD').replace(/[\u0300-\u036f]/g, '').toLowerCase().replace(/[^\p{L}\p{N}]+/gu, ' ').trim().replace(/\s+/g, ' ');
    function score(item, query) {
        const raw = String(query ?? '').trim().toLowerCase();
        if (!raw) return 1;
        const codes = [item.sku, item.barcode, ...String(item.barcodes || '').split('|')].filter(Boolean).map((code) => String(code).trim().toLowerCase());
        if (codes.includes(raw)) return 1000;
        const key = normalize(query);
        if (!key) return 0;
        const name = normalize(item.name || item.searchName);
        if (name === key) return 900;
        // Numeric queries must match an identifier or a full token, avoiding accidental barcode substrings.
        if (/^\d+$/.test(raw)) return name.split(' ').includes(raw) ? 500 : 0;
        if (codes.some((code) => code.startsWith(raw))) return 800;
        const tokens = key.split(' ');
        const words = normalize([name, item.category, item.measurement, item.sku].join(' ')).split(' ');
        if (!tokens.every((token) => words.some((word) => word.startsWith(token)))) return 0;
        return name.startsWith(key) ? 700 : tokens.every((token) => name.split(' ').some((word) => word.startsWith(token))) ? 600 : 400;
    }
    function rank(items, query) {
        const recent = getRecent();
        return Array.from(items).map((item, index) => ({ item, index, recent: recent.indexOf(String(item.id)), score: score(item, query) }))
            .filter((entry) => entry.score > 0)
            .sort((a, b) => b.score - a.score || (a.score === 1 && b.score === 1 ? (a.recent < 0 ? Infinity : a.recent) - (b.recent < 0 ? Infinity : b.recent) : 0) || a.index - b.index).map((entry) => entry.item);
    }
    function getRecent() {
        try { return JSON.parse(root.localStorage.getItem('smartprobook.pos.product-history') || '[]').map(String); }
        catch (_) { return []; }
    }
    function remember(id) {
        if (!id) return;
        try { root.localStorage.setItem('smartprobook.pos.product-history', JSON.stringify([String(id), ...getRecent().filter((value) => value !== String(id))].slice(0, 20))); }
        catch (_) { /* Search remains usable when storage is unavailable. */ }
    }
    function filterShelf(cards, query, category) {
        const entries = Array.from(cards).map((card) => ({ card, score: score(card.dataset, query) }));
        let visible = 0;
        entries.sort((a, b) => b.score - a.score).forEach(({ card, score: relevance }, index) => {
            const show = relevance > 0 && (category === 'all' || card.dataset.category === category);
            card.hidden = !show;
            card.style.display = show ? '' : 'none';
            card.style.order = String(index);
            if (show) visible++;
        });
        if (typeof document !== 'undefined') {
            const count = document.getElementById('product-count');
            const header = document.getElementById('hdr-shelf-count');
            const empty = document.getElementById('pos-no-products');
            if (count) count.textContent = `${visible} item(s)`;
            if (header) header.textContent = String(visible);
            if (empty) empty.hidden = visible > 0;
        }
        return visible;
    }
    root.PosProductSearch = { normalize, score, rank, filterShelf, remember };
    if (typeof module !== 'undefined') module.exports = root.PosProductSearch;
})(typeof window !== 'undefined' ? window : globalThis);
