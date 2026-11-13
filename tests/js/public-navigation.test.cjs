const test = require('node:test');
const assert = require('node:assert/strict');
const fs = require('node:fs');
const vm = require('node:vm');

function navigation() {
    const listeners = {};
    const classes = () => {
        const values = new Set();
        return {
            toggle: (name, enabled) => enabled ? values.add(name) : values.delete(name),
            contains: name => values.has(name),
        };
    };
    const menu = {
        classList: classes(),
        addEventListener: (type, handler) => { listeners[`menu:${type}`] = handler; },
        contains: () => false,
    };
    const button = {
        dataset: {},
        attributes: { 'aria-controls': 'menu' },
        classList: classes(),
        getAttribute(name) { return this.attributes[name]; },
        setAttribute(name, value) { this.attributes[name] = value; },
        addEventListener: (type, handler) => { listeners[`button:${type}`] = handler; },
        contains: () => false,
        focus: () => {},
    };
    const document = {
        readyState: 'complete',
        body: { classList: classes() },
        querySelectorAll: () => [button],
        getElementById: () => menu,
        addEventListener: (type, handler) => { listeners[`document:${type}`] = handler; },
    };
    const window = {
        matchMedia: () => ({ matches: true, addEventListener: () => {} }),
        addEventListener: (type, handler) => { listeners[`window:${type}`] = handler; },
    };
    vm.runInNewContext(fs.readFileSync('public/assets/js/public-navigation.js', 'utf8'), { document, window });
    const open = () => listeners['button:click']({ preventDefault() {} });
    const click = (portal, options = {}) => {
        const link = {
            classList: { contains: name => portal && name === 'btn-portal' },
            target: options.linkTarget || '',
            hasAttribute: name => name === 'download' && !!options.download,
        };
        listeners['menu:click']({
            target: { closest: () => link },
            button: 0,
            ...options,
        });
    };
    return { button, menu, listeners, open, click };
}

test('mobile portal stays visible while navigating and resets when the page is restored', () => {
    const nav = navigation();
    nav.open();
    nav.click(true);
    assert.equal(nav.button.attributes['aria-expanded'], 'true');
    assert.equal(nav.menu.inert, false);
    nav.listeners['window:pageshow']();
    assert.equal(nav.button.attributes['aria-expanded'], 'false');
    assert.equal(nav.menu.inert, true);
});

test('other links and portal links opened separately still close the mobile menu', () => {
    for (const [portal, options] of [
        [false, {}],
        [true, { ctrlKey: true }],
        [true, { linkTarget: '_blank' }],
        [true, { download: true }],
        [true, { defaultPrevented: true }],
    ]) {
        const nav = navigation();
        nav.open();
        nav.click(portal, options);
        assert.equal(nav.button.attributes['aria-expanded'], 'false');
        assert.equal(nav.menu.inert, true);
    }
});
