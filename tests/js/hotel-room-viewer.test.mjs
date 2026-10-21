import { test } from 'node:test';
import assert from 'node:assert/strict';
import { fittedSize, constrainView, zoomAt } from '../../public/assets/js/hotel-room-viewer.js';

test('a wide panorama starts with its full width visible', () => {
    assert.deepEqual(fittedSize(1000, 600, 4000, 1000), { width: 1000, height: 250 });
});

test('a portrait room photo fits without cropping its top or bottom', () => {
    assert.deepEqual(fittedSize(1000, 600, 1000, 2000), { width: 300, height: 600 });
});

test('pinch or wheel zoom keeps the selected point under the pointer', () => {
    const initial = { scale: 1, x: 0, y: 0 };
    const point = { x: 200, y: -80 };
    const result = zoomAt(initial, 2, point);
    assert.deepEqual(result, { scale: 2, x: -200, y: 80 });
    assert.equal((point.x - initial.x) * 2 + result.x, point.x);
    assert.equal((point.y - initial.y) * 2 + result.y, point.y);
});

test('zoom limits keep the full-room starting view and cap magnification', () => {
    assert.equal(zoomAt({ scale: 1, x: 0, y: 0 }, 0.3, { x: 0, y: 0 }).scale, 1);
    assert.equal(zoomAt({ scale: 2, x: 0, y: 0 }, 20, { x: 0, y: 0 }).scale, 5);
});

test('dragging reaches the image edges without losing the room off screen', () => {
    const viewport = { width: 800, height: 600 };
    const image = { width: 1600, height: 1200 };
    const result = constrainView({ scale: 3, x: 2000, y: -2000 }, viewport, image);
    assert.deepEqual(result, { scale: 3, x: 800, y: -600 });
    assert.equal(result.x - 800 * 3 / 2, -400);
    assert.equal(result.y + 600 * 3 / 2, 300);
});

test('fit centers an image even after it was dragged and enlarged', () => {
    const result = constrainView({ scale: 1, x: 800, y: -600 }, { width: 800, height: 600 }, { width: 3000, height: 1000 });
    assert.equal(Math.abs(result.x), 0);
    assert.equal(Math.abs(result.y), 0);
});


test('a tour fills a wide stage with a portrait photo without stretching it', () => {
    const portrait = fittedSize(1600, 600, 1200, 1600, true);
    assert.equal(portrait.width, 1600);
    assert(Math.abs(portrait.width / portrait.height - 1200 / 1600) < 1e-10);
    const covered = fittedSize(800, 450, 2000, 1000, true);
    assert.deepEqual(covered, { width: 900, height: 450 });
    assert.equal(covered.width / covered.height, 2);
});

test('tour dragging keeps the covered photo across the entire stage', () => {
    const view = constrainView({ scale: 1, x: 2000, y: -2000 }, { width: 800, height: 450 }, { width: 2000, height: 1000 }, true);
    assert.equal(view.x, 50);
    assert.equal(Math.abs(view.y), 0);
    assert.equal(view.scale, 1);
});
