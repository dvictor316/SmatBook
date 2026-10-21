const test = require('node:test');
const assert = require('node:assert/strict');
const fs = require('node:fs');
const vm = require('node:vm');

function controls(href) {
    const handlers = {};
    class Form {
        constructor(fields) {
            this.method = 'get'; this.action = new URL(href).origin + new URL(href).pathname;
            this.elements = fields;
            this.elements.namedItem = name => this.elements.find(field => field.name === name);
        }
        appendChild(field) { this.elements.push(field); }
    }
    let destination;
    const document = {
        addEventListener: (type, listener) => { handlers[type] = listener; },
        createElement: () => ({ disabled: false }),
    };
    const window = { location: { href, assign: value => { destination = value; } } };
    vm.runInNewContext(fs.readFileSync('public/assets/js/list-controls.js', 'utf8'), {document, window, URL, HTMLFormElement: Form});
    return {handlers, Form, destination: () => new URL(destination)};
}

test('changing size retains filters and per_page while clearing every result-page cursor', () => {
    const app = controls('https://example.test/reports?search=Rice&per_page=25&page=3&inflow_page=2&branch_id=hq');
    app.handlers.change({target:{closest:()=>({value:'500',dataset:{pageName:'page'}})}});
    const params = app.destination().searchParams;
    assert.equal(params.get('per_page'), '500');
    assert.equal(params.get('search'), 'Rice');
    assert.equal(params.get('branch_id'), 'hq');
    assert.equal(params.has('page'), false);
    assert.equal(params.has('inflow_page'), false);
});

test('applying a filter retains the selected size and branch but disables the old page cursor', () => {
    const app = controls('https://example.test/reports?per_page=25&branch_id=hq&page=8');
    const form = new app.Form([{name:'search',value:'Milk',disabled:false},{name:'page',value:'8',disabled:false}]);
    app.handlers.submit({target:form});
    assert.equal(form.elements.namedItem('per_page').value, '25');
    assert.equal(form.elements.namedItem('per_page').disabled, false);
    assert.equal(form.elements.namedItem('branch_id').value, 'hq');
    assert.equal(form.elements.namedItem('page').disabled, true);
});
