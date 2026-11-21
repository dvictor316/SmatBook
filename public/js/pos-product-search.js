/* Shared by the catalogue and product picker; no network needed for matching. */
(function (root) {
    'use strict';
    const normalize = (value) => String(value ?? '').normalize('NFKD').replace(/[\u0300-\u036f]/g, '').toLowerCase().replace(/[^\p{L}\p{N}]+/gu, ' ').trim().replace(/\s+/g, ' ');
    function score(item, query) {
        const key = normalize(query);
        if (!String(query ?? '').trim()) return 1;
        if (!key) return 0;
        const name = normalize(item.name ?? item.searchName);
        if (name === key) return 900;
        if (name.startsWith(key)) return 700;
        if (name.includes(key)) return 600;

        // Multi-word searches can match name words in any order, but never
        // search SKU, barcode, category, measurement, or stock metadata.
        const words = name.split(' ');
        const tokens = key.split(' ');
        return tokens.every((token) => words.some((word) => word.startsWith(token))) ? 400 : 0;
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
    function bindLiveShelfSearch(input, filter) {
        if (!input || typeof filter !== 'function') return;
        let lastValue = input.value;
        let framePending = false;
        const update = () => {
            const value = input.value;
            if (value === lastValue) return;
            lastValue = value;
            if (framePending) return;
            framePending = true;
            const render = () => {
                framePending = false;
                filter();
            };
            if (typeof root.requestAnimationFrame === 'function') root.requestAnimationFrame(render);
            else root.setTimeout(render, 0);
        };
        input.addEventListener('input', update);
        input.addEventListener('keyup', update);
    }
    let pinnedShelfQuery = null;
    let pinnedShelfIds = null;
    function filterShelf(cards, query, category) {
        const queryText = String(query ?? '').trim();
        const hasQuery = queryText.length > 0;
        let activeCategory = normalize(category || 'all');
        if (queryText !== pinnedShelfQuery) {
            pinnedShelfQuery = queryText;
            pinnedShelfIds = null;
            // A newly changed search starts across all categories. Once the
            // results are shown, an explicit category tap can narrow them.
            if (hasQuery && typeof document !== 'undefined') {
                activeCategory = 'all';
                document.querySelectorAll('.category-pill').forEach((pill) => {
                    const active = normalize(pill.dataset.category || 'all') === 'all';
                    pill.classList.toggle('active', active);
                    pill.setAttribute('aria-pressed', String(active));
                });
            } else if (hasQuery) {
                activeCategory = 'all';
            }
        }
        const entries = Array.from(cards).map((card) => ({ card, score: score(card.dataset, queryText) }));
        if (hasQuery && pinnedShelfIds === null) {
            pinnedShelfIds = new Set(entries.filter((entry) => entry.score > 0)
                .map(({ card }) => String(card.dataset.id || card.dataset.productId || '')));
        }
        let visible = 0;
        entries.sort((a, b) => b.score - a.score).forEach(({ card, score: relevance }, index) => {
            const cardCategory = normalize(card.dataset.category || card.dataset.categoryName || 'uncategorized');
            const id = String(card.dataset.id || card.dataset.productId || '');
            const show = relevance > 0
                && (!hasQuery || pinnedShelfIds.has(id))
                && (activeCategory === 'all' || cardCategory === activeCategory);
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
    root.PosProductSearch = { normalize, score, rank, filterShelf, remember, bindLiveShelfSearch };
    if (typeof module !== 'undefined') module.exports = root.PosProductSearch;
})(typeof window !== 'undefined' ? window : globalThis);
