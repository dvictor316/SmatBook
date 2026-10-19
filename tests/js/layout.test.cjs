const { test } = require('node:test');
const assert = require('node:assert/strict');
const { readFileSync } = require('node:fs');
const { runInNewContext } = require('node:vm');
const source = readFileSync(require('node:path').join(__dirname, '../../public/assets/js/layout.js'), 'utf8');

function restore(entries, blocked = false) {
    const storage = new Map(Object.entries(entries));
    const attributes = { lang: 'en', class: 'invoice-page', 'data-layout': 'default' };
    let reloads = 0;
    runInNewContext(source, {
        sessionStorage: {
            getItem(key) {
                if (blocked) throw new Error('Storage disabled');
                return storage.get(key) ?? null;
            },
            clear() { storage.clear(); },
        },
        document: { documentElement: {
            attributes: Object.entries(attributes).map(([nodeName, nodeValue]) => ({ nodeName, nodeValue })),
            setAttribute(key, value) { attributes[key] = value; },
        } },
        window: { location: { reload() { reloads++; } } },
    });
    return { storage, attributes, reloads };
}

test('navigating between pages restores preferences without a second request or losing drafts', () => {
    const entries = {
        defaultAttribute: JSON.stringify({ lang: 'en', class: 'dashboard' }),
        'data-layout': 'horizontal',
        'data-sidebar-size': 'small',
        'invoice-draft': '{"customer_id":12,"total":5000}',
    };
    const result = restore(entries);
    assert.equal(result.reloads, 0);
    assert.deepEqual(Object.fromEntries(result.storage), entries);
    assert.equal(result.attributes['data-layout'], 'horizontal');
    assert.equal(result.attributes['data-sidebar-size'], 'small');
    assert.equal(result.attributes.class, 'invoice-page');
});

test('first visits retain server defaults', () => {
    const result = restore({ 'data-layout': 'horizontal' });
    assert.equal(result.attributes['data-layout'], 'default');
    assert.equal(result.reloads, 0);
});

test('disabled browser storage does not prevent rendering', () => {
    const result = restore({}, true);
    assert.equal(result.attributes['data-layout'], 'default');
    assert.equal(result.reloads, 0);
});
