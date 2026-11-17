const assert = require('node:assert/strict');
const { readFileSync } = require('node:fs');
const { runInNewContext } = require('node:vm');
const { test } = require('node:test');
const context = { module: { exports: {} } };
runInNewContext(readFileSync('public/js/pos-product-search.js', 'utf8'), context);
const { rank, filterShelf } = context.module.exports;
const products = [
    { id: 'milk', name: 'Peak Milk 400g', sku: 'PEAK-400', barcode: '0123456789012', barcodes: '0000123456789|9999999999999', category: 'Dairy' },
    { id: 'oil', name: 'Peak Groundnut Oil 1.5L', sku: 'OIL-15', barcode: '8888888888888', category: 'Groceries' },
    { id: 'code', name: 'Other product', sku: 'peak', category: 'General' },
];
const ids = (items) => Array.from(items, (item) => item.id);
test('exact SKU outranks name matches and alternate barcodes keep leading zeros', () => {
    assert.equal(rank(products, 'peak')[0].id, 'code');
    assert.deepEqual(ids(rank(products, '0000123456789')), ['milk']);
    assert.deepEqual(ids(rank(products, '0123456789012')), ['milk']);
    assert.deepEqual(ids(rank(products, '3456789')), []);
});
test('multiword prefixes match in any order with punctuation and case normalized', () => {
    assert.deepEqual(ids(rank(products, '400g MILK peak')), ['milk']);
    assert.deepEqual(ids(rank(products, 'peak-400')), ['milk']);
    assert.deepEqual(ids(rank(products, '  OIL   ground  ')), ['oil']);
    assert.deepEqual(ids(rank(products, 'peak milkk')), []);
});
test('category filtering never leaks selected nonmatching cards, and clearing restores cards', () => {
    const cards = products.map((item) => ({ dataset: { ...item, category: item.category.toLowerCase() }, style: {} }));
    assert.equal(filterShelf(cards, 'oil', 'dairy'), 0);
    assert.ok(cards.every((card) => card.style.display === 'none' && card.hidden));
    assert.equal(filterShelf(cards, '', 'all'), 3);
    assert.ok(cards.every((card) => card.style.display === '' && !card.hidden));
});
