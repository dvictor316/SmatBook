const { test } = require('node:test');
const assert = require('node:assert/strict');
const { readFileSync } = require('node:fs');
const { runInNewContext } = require('node:vm');
const { join } = require('node:path');
const template = readFileSync(join(__dirname, '../../resources/views/Inventory/Products/add-products.blade.php'), 'utf8');
const start = template.indexOf('    function setMeasurementMode(mode) {');
const end = template.indexOf("    $('.measurement-mode-button').on", start);
const source = template.slice(start, end);

function changeMode(previous, next) {
    const elements = new Map();
    function $(selector) {
        if (!elements.has(selector)) {
            elements.set(selector, {
                value: '', visible: true, properties: {},
                val(value) {
                    if (arguments.length === 0) return this.value;
                    this.value = value;
                    if (selector.includes(',')) selector.split(',').forEach((part) => $(part.trim()).val(value));
                    return this;
                },
                toggle(visible) { this.visible = visible; return this; },
                prop(key, value) { this.properties[key] = value; return this; },
                each() { return this; },
                text(value) { this.textContent = value; return this; },
            });
        }
        return elements.get(selector);
    }
    $('#measurement_mode_input').val(previous);
    $('#kg_per_carton_input').val(25);
    $('#quick_pcs_per_carton_helper').val(12);
    $('input[name="stock_cartons"]').val(2);
    let unit = 'kg';
    runInNewContext(source + '\nsetMeasurementMode(next);', {
        $, next,
        applyUnitSymbol(symbol) { unit = symbol; },
        refreshQuickPackagingLabels() {}, calculateQuickCartonContent() {},
        calculateQuickStock() {}, refreshKgCartonPriceHint() {},
    });
    return { $, unit };
}

test('switching from kg to pieces clears hidden carton stock and weight conversions', () => {
    const { $, unit } = changeMode('kg_carton', 'pieces');
    assert.equal(unit, 'pcs');
    assert.equal($('#quick_pcs_per_carton_helper').val(), 0);
    assert.equal($('input[name="stock_cartons"]').val(), 0);
    assert.equal($('#kg_per_carton_input').properties.disabled, true);
    assert.equal($('.opening-carton-field, .carton-summary-field').visible, false);
});

test('piece cartons require a fresh conversion after leaving kg mode', () => {
    const { $, unit } = changeMode('kg_carton', 'pieces_carton');
    assert.equal(unit, 'pcs');
    assert.equal($('#quick_pcs_per_carton_helper').val(), '');
    assert.equal($('.pieces-carton-field').visible, true);
    assert.equal($('.carton-mode-field').visible, true);
    assert.equal($('#kg_carton_fields').visible, false);
});

test('validation redisplay preserves the selected piece carton conversion', () => {
    const { $, unit } = changeMode('pieces_carton', 'pieces_carton');
    assert.equal(unit, 'pcs');
    assert.equal($('#quick_pcs_per_carton_helper').val(), 12);
    assert.equal($('input[name="stock_cartons"]').val(), 2);
});
