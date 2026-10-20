export function fittedSize(width, height, imageWidth, imageHeight) {
    if (!width || !height || !imageWidth || !imageHeight) return { width: 0, height: 0 };
    const ratio = Math.min(width / imageWidth, height / imageHeight);
    return { width: imageWidth * ratio, height: imageHeight * ratio };
}

export function constrainView(view, viewport, image) {
    const scale = Math.max(1, Math.min(5, view.scale));
    const fitted = fittedSize(viewport.width, viewport.height, image.width, image.height);
    const maxX = Math.max(0, (fitted.width * scale - viewport.width) / 2);
    const maxY = Math.max(0, (fitted.height * scale - viewport.height) / 2);
    return { scale, x: Math.max(-maxX, Math.min(maxX, view.x)), y: Math.max(-maxY, Math.min(maxY, view.y)) };
}

export function zoomAt(view, scale, point) {
    const next = Math.max(1, Math.min(5, scale));
    const ratio = next / view.scale;
    return { scale: next, x: point.x - (point.x - view.x) * ratio, y: point.y - (point.y - view.y) * ratio };
}

function bindViewer(root) {
    if (root.dataset.viewerBound) return;
    root.dataset.viewerBound = 'true';
    const viewport = root.querySelector('[data-room-viewport]');
    const image = viewport.querySelector('img');
    const label = root.querySelector('[data-room-zoom]');
    const pointers = new Map();
    let view = { scale: 1, x: 0, y: 0 };
    let gesture = null;

    function point(event) {
        const rect = viewport.getBoundingClientRect();
        return { x: event.clientX - rect.left - rect.width / 2, y: event.clientY - rect.top - rect.height / 2 };
    }
    function render() {
        if (!viewport.clientWidth || !viewport.clientHeight) return;
        view = constrainView(view, { width: viewport.clientWidth, height: viewport.clientHeight }, { width: image.naturalWidth, height: image.naturalHeight });
        image.style.transform = `translate(${view.x}px, ${view.y}px) scale(${view.scale})`;
        viewport.classList.toggle('is-zoomed', view.scale > 1);
        label.textContent = `${Number(view.scale.toFixed(1))}×`;
        root.querySelector('[data-room-action="out"]').disabled = view.scale <= 1;
        root.querySelector('[data-room-action="in"]').disabled = view.scale >= 5;
    }
    function reset() {
        view = { scale: 1, x: 0, y: 0 };
        pointers.clear();
        gesture = null;
        render();
    }
    function rebase() {
        const points = [...pointers.values()];
        gesture = points.length ? { view: { ...view }, points } : null;
    }
    root.addEventListener('click', (event) => {
        const button = event.target.closest('[data-room-action]');
        if (!button) return;
        if (button.dataset.roomAction === 'fit') reset();
        else {
            view = zoomAt(view, view.scale * (button.dataset.roomAction === 'in' ? 1.3 : 1 / 1.3), { x: 0, y: 0 });
            render();
        }
    });
    viewport.addEventListener('wheel', (event) => {
        event.preventDefault();
        view = zoomAt(view, view.scale * Math.exp(-Math.max(-100, Math.min(100, event.deltaY)) * 0.004), point(event));
        render();
    }, { passive: false });
    viewport.addEventListener('dblclick', (event) => {
        if (view.scale > 1) reset();
        else { view = zoomAt(view, 2, point(event)); render(); }
    });
    viewport.addEventListener('pointerdown', (event) => {
        if (event.pointerType === 'mouse' && event.button !== 0) return;
        viewport.setPointerCapture(event.pointerId);
        pointers.set(event.pointerId, point(event));
        rebase();
    });
    viewport.addEventListener('pointermove', (event) => {
        if (!pointers.has(event.pointerId)) return;
        pointers.set(event.pointerId, point(event));
        const points = [...pointers.values()];
        if (points.length >= 2 && gesture.points.length >= 2) {
            const [a, b] = points;
            const [initialA, initialB] = gesture.points;
            const initialDistance = Math.hypot(initialB.x - initialA.x, initialB.y - initialA.y);
            if (!initialDistance) return;
            const anchor = { x: (initialA.x + initialB.x) / 2, y: (initialA.y + initialB.y) / 2 };
            view = zoomAt(gesture.view, gesture.view.scale * Math.hypot(b.x - a.x, b.y - a.y) / initialDistance, anchor);
            view.x += (a.x + b.x) / 2 - anchor.x;
            view.y += (a.y + b.y) / 2 - anchor.y;
        } else if (gesture && view.scale > 1) {
            view.x = gesture.view.x + points[0].x - gesture.points[0].x;
            view.y = gesture.view.y + points[0].y - gesture.points[0].y;
        }
        render();
    });
    function release(event) {
        pointers.delete(event.pointerId);
        rebase();
    }
    ['pointerup', 'pointercancel', 'lostpointercapture'].forEach((name) => viewport.addEventListener(name, release));
    viewport.addEventListener('keydown', (event) => {
        if (['+', '=', '-', 'Home', 'ArrowLeft', 'ArrowRight', 'ArrowUp', 'ArrowDown'].includes(event.key)) event.preventDefault();
        else return;
        if (event.key === 'Home') return reset();
        if (event.key === '+' || event.key === '=') view = zoomAt(view, view.scale * 1.3, { x: 0, y: 0 });
        if (event.key === '-') view = zoomAt(view, view.scale / 1.3, { x: 0, y: 0 });
        if (event.key === 'ArrowLeft') view.x += 40;
        if (event.key === 'ArrowRight') view.x -= 40;
        if (event.key === 'ArrowUp') view.y += 40;
        if (event.key === 'ArrowDown') view.y -= 40;
        render();
    });
    image.addEventListener('load', reset);
    root.closest('.modal')?.addEventListener('shown.bs.modal', reset);
    root.closest('.modal')?.addEventListener('hidden.bs.modal', reset);
    if (typeof ResizeObserver !== 'undefined') new ResizeObserver(render).observe(viewport);
    reset();
}

if (typeof document !== 'undefined') {
    const initialize = () => document.querySelectorAll('[data-room-viewer]').forEach(bindViewer);
    if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', initialize);
    else initialize();
}
